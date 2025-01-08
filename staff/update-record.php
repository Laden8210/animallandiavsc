<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$servername = "localhost";
$username = "u920096089_vmscdb";
$password = "Vmscdb2024";
$dbname = "u920096089_vmscdb";

$con = new mysqli($servername, $username, $password, $dbname);

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

$med_id = isset($_GET['Med_ID']) ? (int)$_GET['Med_ID'] : 0;

if ($med_id <= 0) {
    echo "<p>Invalid record ID.</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight = $con->real_escape_string($_POST['weight']);
    $temp = $con->real_escape_string($_POST['temp']);
    $diagnosis = $con->real_escape_string($_POST['diagnosis']);
    $treatment = $con->real_escape_string($_POST['treatment']);
    $notes = $con->real_escape_string($_POST['notes']);
    $vet_id = (int)$_POST['vet_id']; // Vet ID
    $services = isset($_POST['services']) ? $_POST['services'] : [];

    $sql_update = "UPDATE tblmedical_record 
                   SET 
                       weight = '$weight', 
                       temp = '$temp', 
                       diagnosis = '$diagnosis',
                       treatment = '$treatment', 
                       notes = '$notes', 
                       vet_ID = $vet_id 
                   WHERE Med_ID = $med_id";

    if ($con->query($sql_update)) {
        $appt_id_result = $con->query("SELECT Appt_ID FROM tblmedical_record WHERE Med_ID = $med_id");
        if ($appt_id_result && $appt_id_result->num_rows > 0) {
            $appt_id_row = $appt_id_result->fetch_assoc();
            $appt_id = $appt_id_row['Appt_ID'];
        } else {
            echo "<p>No appointment found for this medical record.</p>";
            exit;
        }

        
        $con->query("DELETE FROM tblpet_services WHERE pet_ID = (SELECT pet_ID FROM tblmedical_record WHERE Med_ID = $med_id)");
        foreach ($services as $service_id) {
            $con->query("INSERT INTO tblpet_services (pet_ID, service_id, Appt_ID) 
                         SELECT pet_ID, $service_id, $appt_id FROM tblmedical_record WHERE Med_ID = $med_id");
        }

        
        echo "<script>
                alert('Update Successfully');
                window.location.href = 'pet_medical_result.php';
              </script>";
    } else {
        echo "<p>Error: " . $con->error . "</p>";
    }
}


$sql = "SELECT tblmedical_record.*,  
               tblpet.pet_Name, 
               tblclients.Name, 
               GROUP_CONCAT(tblpet_services.service_id) AS service_ids, 
               GROUP_CONCAT(tblservices.ServiceName) AS service_names, 
               tblappointment.Appointment_Date,
               tblvet.vet_ID AS current_vet_id
        FROM tblmedical_record 
        INNER JOIN tblpet ON tblmedical_record.pet_ID = tblpet.pet_ID
        INNER JOIN tblclients ON tblpet.client_id = tblclients.client_id
        LEFT JOIN tblpet_services ON tblpet_services.pet_ID = tblpet.pet_ID
        LEFT JOIN tblservices ON tblpet_services.service_id = tblservices.service_id
        LEFT JOIN tblappointment ON tblmedical_record.Appt_ID = tblappointment.Appt_ID
        LEFT JOIN tblvet ON tblmedical_record.vet_ID = tblvet.vet_ID
        WHERE tblmedical_record.Med_ID = $med_id
        GROUP BY tblmedical_record.Med_ID";

$result = $con->query($sql);
if (!$result) {
    die("<p>SQL Error: " . $con->error . "</p>");
}

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $service_ids = isset($row['service_ids']) ? explode(',', $row['service_ids']) : [];
    $pet_name = isset($row['pet_Name']) ? htmlspecialchars($row['pet_Name']) : 'N/A';
    $owner_name = isset($row['Name']) ? htmlspecialchars($row['Name']) : 'N/A';
    $appointment_date = isset($row['Appointment_Date']) ? htmlspecialchars($row['Appointment_Date']) : 'N/A';
    $weight = isset($row['weight']) ? htmlspecialchars($row['weight']) : '';
    $temp = isset($row['temp']) ? htmlspecialchars($row['temp']) : '';
    $diagnosis = isset($row['diagnosis']) ? htmlspecialchars($row['diagnosis']) : '';
    $treatment = isset($row['treatment']) ? htmlspecialchars($row['treatment']) : '';
    $notes = isset($row['notes']) ? htmlspecialchars($row['notes']) : '';
    $current_vet_id = isset($row['current_vet_id']) ? $row['current_vet_id'] : '';
} else {
    echo "<p>Record not found.</p>";
    exit;
}

$services_result = $con->query("SELECT service_id, ServiceName, status FROM tblservices WHERE status != 'Not Available'");
$vets_result = $con->query("SELECT vet_ID, CONCAT(vFirstname, ' ', vLastname) AS vet_name FROM tblvet");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Record List</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/modernizr.custom.js"></script>
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
                        <p><strong>Pet Name:</strong> <?php echo $pet_name; ?></p><br>
                        <p><strong>Owner Name:</strong> <?php echo $owner_name; ?></p><br>
                        <p><strong>Visit Date:</strong> <?php echo $appointment_date; ?></p><br>

                        <div class="mb-3">
                            <label for="weight" class="form-label">Weight:</label>
                            <textarea id="weight" name="weight" class="form-control"><?php echo $weight; ?>kg</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="temp" class="form-label">Temperature:</label>
                            <textarea id="temp" name="temp" class="form-control"><?php echo $temp; ?>°C</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="diagnosis" class="form-label">Diagnosis:</label>
                            <textarea id="diagnosis" name="diagnosis" class="form-control"><?php echo $diagnosis; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="treatment" class="form-label">Treatment:</label>
                            <textarea id="treatment" name="treatment" class="form-control"><?php echo $treatment; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes:</label>
                            <textarea id="notes" name="notes" class="form-control"><?php echo $notes; ?></textarea>
                        </div>
                        <br>

                        <div class="mb-3">
                            <label for="vet_id" class="form-label">Veterinarian:</label>
                            <select id="vet_id" name="vet_id" class="form-control">
                                <?php while ($vet = $vets_result->fetch_assoc()) { ?>
                                    <option value="<?php echo $vet['vet_ID']; ?>" <?php echo $vet['vet_ID'] == $current_vet_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vet['vet_name']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <button type="button" class="btn btn-primary" id="toggleServicesButton">Show Services</button>
                            <div id="servicesList" style="display: none;">
                                <label class="form-label">Services:</label><br>
                                <?php while ($service = $services_result->fetch_assoc()) { ?>
                                    <label>
                                        <input type="checkbox" name="services[]" value="<?php echo $service['service_id']; ?>" <?php echo in_array($service['service_id'], $service_ids) ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($service['ServiceName']); ?>
                                    </label><br>
                                <?php } ?>
                            </div>
                        </div>

                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

                        <script>
                            $(document).ready(function() {
                                $('#toggleServicesButton').click(function() {
                                    $('#servicesList').slideToggle(); 
                                    var buttonText = $('#servicesList').is(':visible') ? 'Hide Services' : 'Show Services';
                                    $('#toggleServicesButton').text(buttonText); 
                                });
                            });
                        </script>

                        <button type="submit" class="btn btn-success">Update Record</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$con->close();
?>
