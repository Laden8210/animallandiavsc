<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Redirect to login if the user is not authenticated
if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

// Check for connection errors
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Retrieve and validate Med_ID from GET parameters
$med_id = isset($_GET['Med_ID']) ? (int)$_GET['Med_ID'] : 0;

if ($med_id <= 0) {
    echo "<p>Invalid Medical Record ID.</p>";
    exit;
}

// Initialize variables for error and success messages
$error = '';
$success = '';

// Handle form submission (if applicable)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Example: Update diagnosis, treatment, etc.
    // Implement form handling logic here
    // ...
}

// Fetch medical record and related data securely using prepared statements
$record_query = "SELECT 
                    mr.*,
                    p.pet_Name,
                    p.Breed,
                    p.Age,
                    p.pGender,
                    p.Species,
                    p.Color,
                    p.Birthdate,
                    c.Name AS owner_name,
                    c.ContactNumber AS owner_contact,
                    c.Address AS owner_address,
                    c.Email AS owner_email,
                    a.Appointment_Date,
                    GROUP_CONCAT(DISTINCT s.ServiceName SEPARATOR ', ') AS service_names,
                    CONCAT(v.vFirstname, ' ', v.vLastname) AS vet_name
                FROM tblmedical_record mr
                INNER JOIN tblpet p ON mr.pet_ID = p.pet_ID
                INNER JOIN tblclients c ON p.client_id = c.client_id
                LEFT JOIN tblappointment a ON mr.Appt_ID = a.Appt_ID
                LEFT JOIN tblpet_services ps ON p.pet_ID = ps.pet_ID
                LEFT JOIN tblservices s ON ps.service_id = s.service_id
                LEFT JOIN tblvet v ON mr.vet_ID = v.vet_ID
                WHERE mr.Med_ID = ?
                GROUP BY mr.Med_ID, p.pet_Name, c.Name, a.Appointment_Date, v.vFirstname, v.vLastname";

if ($stmt = $con->prepare($record_query)) {
    $stmt->bind_param("i", $med_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
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
    <title>Pet Medical Record</title>
    <style>
        /* Page Setup */
        @page {
            size: A4;
            margin: 15mm 15mm 15mm 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #212529;
        }

        .report-container {
            width: 100%;
            height: 100%;
            padding: 20px;
            box-sizing: border-box;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            text-decoration: underline;
        }

        h3 {
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 18px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
            color: #343a40;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border: 1px solid #dee2e6;
            font-size: 14px;
        }

        th {
            background-color: #e9ecef;
            color: #495057;
            width: 30%;
        }

        td {
            width: 70%;
        }

        .print-button {
            text-align: center;
            margin-top: 20px;
        }

        .print-button button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 5px;
        }

        .print-button button:hover {
            background-color: #0056b3;
        }

        @media print {
            .print-button {
                display: none;
            }
        }

        /* Alert Styles */
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="report-container">
        <h2>Pet Medical Record</h2>

        <!-- Display Success or Error Messages -->
        <?php if (!empty($success)) : ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Pet Information -->
        <h3>Pet Information</h3>
        <table>
            <tr>
                <th>Pet Name</th>
                <td><?php echo $row['pet_Name']; ?></td>
            </tr>
            <tr>
                <th>Breed</th>
                <td><?php echo $row['Breed']; ?></td>
            </tr>
            <tr>
                <th>Age</th>
                <td><?php echo $row['Age']; ?> years</td>
            </tr>
            <tr>
                <th>Gender</th>
                <td><?php echo $row['pGender']; ?></td>
            </tr>
            <tr>
                <th>Species</th>
                <td><?php echo $row['Species']; ?></td>
            </tr>
            <tr>
                <th>Color</th>
                <td><?php echo $row['Color']; ?></td>
            </tr>
            <tr>
                <th>Birthdate</th>
                <td><?php echo $row['Birthdate']; ?></td>
            </tr>
        </table>

        <!-- Owner Information -->
        <h3>Owner Information</h3>
        <table>
            <tr>
                <th>Owner Name</th>
                <td><?php echo $row['owner_name']; ?></td>
            </tr>
            <tr>
                <th>Contact Number</th>
                <td><?php echo $row['owner_contact']; ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?php echo $row['owner_address']; ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo $row['owner_email']; ?></td>
            </tr>
        </table>

        <!-- Medical History -->
        <h3>Medical History</h3>
        <table>
            <tr>
                <th>Date</th>
                <td><?php
                    // Format the Transaction_Date
                    $transactionDate = new DateTime($row['created_at']);
                    echo $transactionDate->format('F j, Y, g:i a');
                    ?></td>
            </tr>
            <tr>
                <th>Diagnosis</th>
                <td><?php echo $row['diagnosis']; ?></td>
            </tr>
            <tr>
                <th>Treatment</th>
                <td><?php echo $row['treatment']; ?></td>
            </tr>
            <tr>
                <th>Notes</th>
                <td><?php echo $row['notes']; ?></td>
            </tr>
            <tr>
                <th>Weight</th>
                <td><?php echo $row['weight']; ?> kg</td>
            </tr>
            <tr>
                <th>Temperature</th>
                <td><?php echo $row['temp']; ?> °C</td>
            </tr>
            <tr>
                <th>Veterinarian</th>
                <td><?php echo $row['vet_name']; ?></td>
            </tr>
            <tr>
                <th>Services Provided</th>
                <td><?php echo $row['service_names']; ?></td>
            </tr>
        </table>

        <!-- Appointment Information (if available) -->
        <?php if (!empty($row['Appointment_Date'])) : ?>
            <h3>Appointment Information</h3>
            <table>
                <tr>
                    <th>Appointment Date</th>
                    <td><?php
                        // Format the Appointment_Date
                        $appointmentDate = new DateTime($row['Appointment_Date']);
                        echo $appointmentDate->format('F j, Y, g:i a');
                        ?></td>
                </tr>
            </table>
        <?php endif; ?>

        <!-- Observation of Each Body System -->
        <h3>Observation of Each Body System</h3>
        <table>
            <tr>
                <th>Eyes</th>
                <td><?php echo $row['eyes_observation']; ?></td>
            </tr>
            <tr>
                <th>Ears</th>
                <td><?php echo$row['ears_observation']; ?></td>
            </tr>
            <tr>
                <th>Nose</th>
                <td><?php echo $row['nose_observation']; ?></td>
            </tr>
            <tr>
                <th>Mouth</th>
                <td><?php echo $row['mouth_observation']; ?></td>
            </tr>
            <tr>
                <th>Skin</th>
                <td><?php echo $row['skin_observation']; ?></td>
            </tr>
            <tr>
                <th>Musculoskeletal System</th>
                <td><?php echo $row['musculoskeletal_observation']; ?></td>
            </tr>
        </table>

        <!-- Surgery Record (if available) -->
        <?php if (!empty($row['surgery_date'])) : ?>
            <h3>Surgery Record</h3>
            <table>
                <tr>
                    <th>Date</th>
                    <td><?php
                        // Format the Surgery Date
                        $surgeryDate = new DateTime($row['surgery_date']);
                        echo $surgeryDate->format('F j, Y');
                        ?></td>
                </tr>
                <tr>
                    <th>Procedures</th>
                    <td><?php echo nl2br($row['surgery_procedures']); ?></td>
                </tr>
                <tr>
                    <th>Complications</th>
                    <td><?php echo nl2br($row['surgery_complications']); ?></td>
                </tr>
                <tr>
                    <th>Anesthesia Type</th>
                    <td><?php echo $row['anesthesia_type']; ?></td>
                </tr>
                <tr>
                    <th>Surgeon</th>
                    <td><?php echo $row['surgeon_name']; ?></td>
                </tr>
            </table>
        <?php endif; ?>

        <!-- Print Button -->
        <div class="print-button">
            <button onclick="window.print();">Print</button>
        </div>
    </div>
</body>

</html>

<?php
$con->close();
?>
