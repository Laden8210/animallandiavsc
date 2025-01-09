<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Redirect to login if the user is not authenticated
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

// Establish database connection and check for errors
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Fetch Medical Record ID from GET parameters
$med_id = isset($_GET['Med_ID']) ? (int)$_GET['Med_ID'] : 0;

if ($med_id <= 0) {
    echo "<p>Invalid record ID.</p>";
    exit;
}

// Initialize variables for error and success messages
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize an array to hold error messages
    $errors = [];

    // Retrieve and sanitize form inputs
    // Weight and Temperature
    $weight = isset($_POST['weight']) ? trim($_POST['weight']) : null;
    $temp = isset($_POST['temp']) ? trim($_POST['temp']) : null;

    // Diagnosis, Treatment, Notes
    $diagnosis = isset($_POST['diagnosis']) ? trim($_POST['diagnosis']) : null;
    $treatment = isset($_POST['treatment']) ? trim($_POST['treatment']) : null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

    // Observations
    $eyes_observation = isset($_POST['eyes_observation']) ? trim($_POST['eyes_observation']) : null;
    $ears_observation = isset($_POST['ears_observation']) ? trim($_POST['ears_observation']) : null;
    $nose_observation = isset($_POST['nose_observation']) ? trim($_POST['nose_observation']) : null;
    $mouth_observation = isset($_POST['mouth_observation']) ? trim($_POST['mouth_observation']) : null;
    $skin_observation = isset($_POST['skin_observation']) ? trim($_POST['skin_observation']) : null;
    $musculoskeletal_observation = isset($_POST['musculoskeletal_observation']) ? trim($_POST['musculoskeletal_observation']) : null;

    // Surgery Details (Optional)
    $surgery_date = isset($_POST['surgery_date']) && !empty($_POST['surgery_date']) ? $_POST['surgery_date'] : null;
    $surgery_procedures = isset($_POST['surgery_procedures']) ? trim($_POST['surgery_procedures']) : null;
    $surgery_complications = isset($_POST['surgery_complications']) ? trim($_POST['surgery_complications']) : null;
    $anesthesia_type = isset($_POST['anesthesia_type']) ? trim($_POST['anesthesia_type']) : null;
    $surgeon_name = isset($_POST['surgeon_name']) ? trim($_POST['surgeon_name']) : null;

    // Veterinarian ID
    $vet_id = isset($_POST['vet_id']) ? (int)$_POST['vet_id'] : null;

    // Services
    $services = isset($_POST['services']) ? $_POST['services'] : [];

    // Validate required fields (e.g., Weight, Temperature, Diagnosis)
    if (empty($weight)) {
        $errors[] = "Weight is required.";
    } elseif (!is_numeric($weight) || $weight <= 0) {
        $errors[] = "Weight must be a positive number.";
    }

    if (empty($temp)) {
        $errors[] = "Temperature is required.";
    } elseif (!is_numeric($temp) || $temp <= 0) {
        $errors[] = "Temperature must be a positive number.";
    }

    if (empty($diagnosis)) {
        $errors[] = "Diagnosis is required.";
    }

    if (empty($treatment)) {
        $errors[] = "Treatment is required.";
    }

    // Additional validations can be added here (e.g., vet_id exists)

    if (empty($errors)) {
        try {
            // Begin Transaction
            mysqli_begin_transaction($con);

            // Prepare the UPDATE statement with all necessary fields
            $sql_update = "UPDATE tblmedical_record SET 
                                weight = ?, 
                                temp = ?, 
                                diagnosis = ?, 
                                treatment = ?, 
                                notes = ?, 
                                eyes_observation = ?, 
                                ears_observation = ?, 
                                nose_observation = ?, 
                                mouth_observation = ?, 
                                skin_observation = ?, 
                                musculoskeletal_observation = ?, 
                                surgery_date = ?, 
                                surgery_procedures = ?, 
                                surgery_complications = ?, 
                                anesthesia_type = ?, 
                                surgeon_name = ?, 
                                vet_ID = ?, 
                                updated_at = CURRENT_TIMESTAMP 
                           WHERE Med_ID = ?";

            if ($stmt_update = $con->prepare($sql_update)) {
                // Bind parameters (d = double, s = string, i = integer)
                $stmt_update->bind_param(
                    "ddssssssssssssssii",
                    $weight,
                    $temp,
                    $diagnosis,
                    $treatment,
                    $notes,
                    $eyes_observation,
                    $ears_observation,
                    $nose_observation,
                    $mouth_observation,
                    $skin_observation,
                    $musculoskeletal_observation,
                    $surgery_date,
                    $surgery_procedures,
                    $surgery_complications,
                    $anesthesia_type,
                    $surgeon_name,
                    $vet_id,
                    $med_id
                );

                if (!$stmt_update->execute()) {
                    throw new Exception('Error updating medical record: ' . $stmt_update->error);
                }

                $stmt_update->close();
            } else {
                throw new Exception('Error preparing update statement: ' . $con->error);
            }

            // Handle Services
            // First, delete existing services for this medical record's pet and appointment
            $sql_delete_services = "DELETE FROM tblpet_services WHERE pet_ID = (SELECT pet_ID FROM tblmedical_record WHERE Med_ID = ?) AND Appt_ID = (SELECT Appt_ID FROM tblmedical_record WHERE Med_ID = ?)";
            if ($stmt_delete = $con->prepare($sql_delete_services)) {
                $stmt_delete->bind_param("ii", $med_id, $med_id);
                if (!$stmt_delete->execute()) {
                    throw new Exception('Error deleting existing services: ' . $stmt_delete->error);
                }
                $stmt_delete->close();
            } else {
                throw new Exception('Error preparing delete services statement: ' . $con->error);
            }

            // Insert new services
            if (!empty($services)) {
                // Fetch pet_ID and Appt_ID for this Med_ID
                $sql_fetch_ids = "SELECT pet_ID, Appt_ID FROM tblmedical_record WHERE Med_ID = ?";
                if ($stmt_fetch = $con->prepare($sql_fetch_ids)) {
                    $stmt_fetch->bind_param("i", $med_id);
                    if (!$stmt_fetch->execute()) {
                        throw new Exception('Error fetching pet and appointment IDs: ' . $stmt_fetch->error);
                    }
                    $result_ids = $stmt_fetch->get_result();
                    if ($result_ids->num_rows > 0) {
                        $row_ids = $result_ids->fetch_assoc();
                        $pet_id = $row_ids['pet_ID'];
                        $appt_id = $row_ids['Appt_ID'];
                    } else {
                        throw new Exception('No pet or appointment found for this medical record.');
                    }
                    $stmt_fetch->close();
                } else {
                    throw new Exception('Error preparing fetch IDs statement: ' . $con->error);
                }

                // Prepare the INSERT statement
                $sql_insert_service = "INSERT INTO tblpet_services (pet_ID, service_id, Appt_ID) VALUES (?, ?, ?)";
                if ($stmt_insert = $con->prepare($sql_insert_service)) {
                    foreach ($services as $service_id) {
                        $service_id = (int)$service_id;
                        $stmt_insert->bind_param("iii", $pet_id, $service_id, $appt_id);
                        if (!$stmt_insert->execute()) {
                            throw new Exception('Error inserting service: ' . $stmt_insert->error);
                        }
                    }
                    $stmt_insert->close();
                } else {
                    throw new Exception('Error preparing insert service statement: ' . $con->error);
                }
            }

            // Commit Transaction
            mysqli_commit($con);

            // Redirect or display success message
            echo "<script>
                    alert('Medical record updated successfully.');
                    window.location.href = 'pet_medical_result.php';
                  </script>";
            exit;
        } catch (Exception $e) {
            // Rollback Transaction on Error
            mysqli_rollback($con);
            echo "<script>
                    alert('Error: " . addslashes($e->getMessage()) . "');
                  </script>";
        }
    }

}
    // Fetch existing medical record details
    $sql = "SELECT tblmedical_record.*,  
                   tblpet.pet_Name, 
                   tblclients.Name AS owner_name, 
                   GROUP_CONCAT(tblpet_services.service_id) AS service_ids, 
                   GROUP_CONCAT(tblservices.ServiceName SEPARATOR ', ') AS service_names, 
                   tblappointment.Appointment_Date,
                   tblvet.vet_ID AS current_vet_id
            FROM tblmedical_record 
            INNER JOIN tblpet ON tblmedical_record.pet_ID = tblpet.pet_ID
            INNER JOIN tblclients ON tblpet.client_id = tblclients.client_id
            LEFT JOIN tblpet_services ON tblpet_services.pet_ID = tblpet.pet_ID AND tblpet_services.Appt_ID = tblmedical_record.Appt_ID
            LEFT JOIN tblservices ON tblpet_services.service_id = tblservices.service_id
            LEFT JOIN tblappointment ON tblmedical_record.Appt_ID = tblappointment.Appt_ID
            LEFT JOIN tblvet ON tblmedical_record.vet_ID = tblvet.vet_ID
            WHERE tblmedical_record.Med_ID = ?
            GROUP BY tblmedical_record.Med_ID";

    if ($stmt = $con->prepare($sql)) {
        $stmt->bind_param("i", $med_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $service_ids = isset($row['service_ids']) ? explode(',', $row['service_ids']) : [];
            $pet_name = isset($row['pet_Name']) ? $row['pet_Name'] : 'N/A';
            $owner_name = isset($row['owner_name']) ? $row['owner_name'] : 'N/A';
            $appointment_date = isset($row['Appointment_Date']) ? $row['Appointment_Date'] : 'N/A';
            $weight = isset($row['weight']) ? $row['weight'] : '';
            $temp = isset($row['temp']) ? $row['temp'] : '';
            $diagnosis = isset($row['diagnosis']) ? $row['diagnosis'] : '';
            $treatment = isset($row['treatment']) ? $row['treatment'] : '';
            $notes = isset($row['notes']) ? $row['notes'] : '';
            $eyes_observation = isset($row['eyes_observation']) ? $row['eyes_observation'] : '';
            $ears_observation = isset($row['ears_observation']) ? $row['ears_observation'] : '';
            $nose_observation = isset($row['nose_observation']) ? $row['nose_observation'] : '';
            $mouth_observation = isset($row['mouth_observation']) ? $row['mouth_observation'] : '';
            $skin_observation = isset($row['skin_observation']) ? $row['skin_observation'] : '';
            $musculoskeletal_observation = isset($row['musculoskeletal_observation']) ? $row['musculoskeletal_observation'] : '';
            $surgery_date = isset($row['surgery_date']) ? $row['surgery_date'] : '';
            $surgery_procedures = isset($row['surgery_procedures']) ? $row['surgery_procedures'] : '';
            $surgery_complications = isset($row['surgery_complications']) ? $row['surgery_complications'] : '';
            $anesthesia_type = isset($row['anesthesia_type']) ? $row['anesthesia_type'] : '';
            $surgeon_name = isset($row['surgeon_name']) ? $row['surgeon_name'] : '';
            $created_at = isset($row['created_at']) ? $row['created_at'] : '';
            $updated_at = isset($row['updated_at']) ? $row['updated_at'] : '';
            $current_vet_id = isset($row['current_vet_id']) ? (int)$row['current_vet_id'] : '';
        } else {
            echo "<p>Record not found.</p>";
            exit;
        }
        $stmt->close();
    } else {
        die("Error preparing record query: " . $con->error);
    }

    // Fetch available services
    $services_result = $con->query("SELECT service_id, ServiceName, status FROM tblservices WHERE status != 'Not Available'");

    // Fetch available veterinarians
    $vets_result = $con->query("SELECT vet_ID, CONCAT(vFirstname, ' ', vLastname) AS vet_name FROM tblvet");
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Update Medical Record</title>
        <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
        <link href="css/style.css" rel='stylesheet' type='text/css' />
        <link href="css/font-awesome.css" rel="stylesheet">
        <script src="js/jquery-1.11.1.min.js"></script>
        <script src="js/modernizr.custom.js"></script>
        <style>
            body {
                background-color: #f4f7fa;
            }
            .form-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .form-section {
                margin-bottom: 20px;
                padding: 15px;
                background-color: #ffffff;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            }
            .form-section h4 {
                margin-bottom: 15px;
                color: #007bff;
            }
        </style>
    </head>
    <body class="cbp-spmenu-push">
        <div class="main-content">
            <?php include_once('includes/sidebar.php'); ?>
            <?php include_once('includes/header.php'); ?>
            <div id="page-wrapper">
                <div class="main-page">
                    <div class="tables">
                        <h2 class="form-header">Update Medical Record</h2><br>
                        <form method="POST" action="">
                            <!-- Display Basic Information -->
                            <div class="form-section">
                                <p><strong>Pet Name:</strong> <?php echo $pet_name; ?></p>
                                <p><strong>Owner Name:</strong> <?php echo $owner_name; ?></p>
                                <p><strong>Visit Date:</strong> <?php echo $appointment_date; ?></p>
                                <p><strong>Medical Record ID:</strong> <?php echo $med_id; ?></p>
                            </div>

                            <!-- Weight and Temperature -->
                            <div class="form-section">
                                <h4>Vital Signs</h4>
                                <div class="mb-3">
                                    <label for="weight" class="form-label">Weight (kg):</label>
                                    <input type="number" step="0.01" min="0" id="weight" name="weight" class="form-control" value="<?php echo $weight; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="temp" class="form-label">Temperature (°C):</label>
                                    <input type="number" step="0.01" min="0" id="temp" name="temp" class="form-control" value="<?php echo $temp; ?>" required>
                                </div>
                            </div>

                            <!-- Diagnosis, Treatment, Notes -->
                            <div class="form-section">
                                <h4>Medical Details</h4>
                                <div class="mb-3">
                                    <label for="diagnosis" class="form-label">Diagnosis Result:</label>
                                    <textarea id="diagnosis" name="diagnosis" class="form-control" rows="3" required><?php echo $diagnosis; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="treatment" class="form-label">Treatment:</label>
                                    <textarea id="treatment" name="treatment" class="form-control" rows="3" required><?php echo $treatment; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes:</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="3"><?php echo $notes; ?></textarea>
                                </div>
                            </div>

                            <!-- Observations -->
                            <div class="form-section">
                                <h4>Observations</h4>
                                <div class="mb-3">
                                    <label for="eyes_observation" class="form-label">Eyes Observation:</label>
                                    <textarea id="eyes_observation" name="eyes_observation" class="form-control" rows="2"><?php echo $eyes_observation; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="ears_observation" class="form-label">Ears Observation:</label>
                                    <textarea id="ears_observation" name="ears_observation" class="form-control" rows="2"><?php echo $ears_observation; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="nose_observation" class="form-label">Nose Observation:</label>
                                    <textarea id="nose_observation" name="nose_observation" class="form-control" rows="2"><?php echo $nose_observation; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="mouth_observation" class="form-label">Mouth Observation:</label>
                                    <textarea id="mouth_observation" name="mouth_observation" class="form-control" rows="2"><?php echo $mouth_observation; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="skin_observation" class="form-label">Skin Observation:</label>
                                    <textarea id="skin_observation" name="skin_observation" class="form-control" rows="2"><?php echo $skin_observation; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="musculoskeletal_observation" class="form-label">Musculoskeletal Observation:</label>
                                    <textarea id="musculoskeletal_observation" name="musculoskeletal_observation" class="form-control" rows="2"><?php echo $musculoskeletal_observation; ?></textarea>
                                </div>
                            </div>

                            <!-- Surgery Record (Optional) -->
                            <div class="form-section">
                                <h4>Surgery Record</h4>
                                <div class="mb-3">
                                    <label for="surgery_date" class="form-label">Surgery Date:</label>
                                    <input type="date" id="surgery_date" name="surgery_date" class="form-control" value="<?php echo $surgery_date; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="surgery_procedures" class="form-label">Surgery Procedures:</label>
                                    <textarea id="surgery_procedures" name="surgery_procedures" class="form-control" rows="3"><?php echo $surgery_procedures; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="surgery_complications" class="form-label">Surgery Complications:</label>
                                    <textarea id="surgery_complications" name="surgery_complications" class="form-control" rows="3"><?php echo $surgery_complications; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="anesthesia_type" class="form-label">Anesthesia Type:</label>
                                    <input type="text" id="anesthesia_type" name="anesthesia_type" class="form-control" value="<?php echo $anesthesia_type; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="surgeon_name" class="form-label">Surgeon Name:</label>
                                    <input type="text" id="surgeon_name" name="surgeon_name" class="form-control" value="<?php echo $surgeon_name; ?>">
                                </div>
                            </div>

                            <!-- Veterinarian Selection -->
                            <div class="form-section">
                                <h4>Veterinarian</h4>
                                <div class="mb-3">
                                    <label for="vet_id" class="form-label">Select Veterinarian:</label>
                                    <select id="vet_id" name="vet_id" class="form-control" required>
                                        <option value="">-- Select Veterinarian --</option>
                                        <?php while ($vet = $vets_result->fetch_assoc()) { ?>
                                            <option value="<?php echo $vet['vet_ID']; ?>" <?php echo ($vet['vet_ID'] == $current_vet_id) ? 'selected' : ''; ?>>
                                                <?php echo $vet['vet_name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Services Selection -->
                            <div class="form-section">
                                <h4>Services</h4>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-primary" id="toggleServicesButton">Show Services</button>
                                    <div id="servicesList" style="display: none; margin-top: 15px;">
                                        <?php while ($service = $services_result->fetch_assoc()) { ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="services[]" value="<?php echo $service['service_id']; ?>" id="service_<?php echo $service['service_id']; ?>" <?php echo in_array($service['service_id'], $service_ids) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="service_<?php echo $service['service_id']; ?>">
                                                    <?php echo $service['ServiceName']; ?>
                                                </label>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Timestamps (Read-Only) -->
                            <div class="form-section">
                                <h4>Record Information</h4>
                                <div class="mb-3">
                                    <label for="created_at" class="form-label">Created At:</label>
                                    <input type="text" id="created_at" name="created_at" class="form-control" value="<?php echo $created_at; ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="updated_at" class="form-label">Last Updated At:</label>
                                    <input type="text" id="updated_at" name="updated_at" class="form-control" value="<?php echo $updated_at; ?>" readonly>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="form-section">
                                <button type="submit" class="btn btn-success">Update Medical Record</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- JavaScript for Toggle Services -->
        <script>
            $(document).ready(function() {
                $('#toggleServicesButton').click(function() {
                    $('#servicesList').slideToggle(); 
                    var buttonText = $('#servicesList').is(':visible') ? 'Hide Services' : 'Show Services';
                    $('#toggleServicesButton').text(buttonText); 
                });
            });
        </script>
    </body>
    </html>

    <?php
    $con->close();
    ?>
