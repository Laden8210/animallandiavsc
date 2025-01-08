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

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$med_id = isset($_GET['Med_ID']) ? (int)$_GET['Med_ID'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $med_id > 0) {
    $client_check_query = "SELECT tblpet.client_id 
                           FROM tblmedical_record 
                           INNER JOIN tblpet ON tblmedical_record.pet_ID = tblpet.pet_ID
                           WHERE tblmedical_record.Med_ID = '$med_id'";

    $client_check_result = mysqli_query($con, $client_check_query);
    $client_row = mysqli_fetch_assoc($client_check_result);
    $client_id = $client_row['client_id'] ?? 0;

    if ($client_id == 0) {
        echo "<script>alert('Client not found for this medical record.');</script>";
        exit;
    }

   
}

if ($med_id > 0) {
    
    $sql = "SELECT tblmedical_record.*, 
                   tblpet.pet_Name, 
                   tblclients.Name, 
                   tblappointment.Appointment_Date, 
                   tblmedical_record.weight, 
                   tblmedical_record.temp,
                   tblmedical_record.diagnosis, 
                   tblmedical_record.treatment, 
                   tblmedical_record.notes,
                   GROUP_CONCAT(DISTINCT tblservices.ServiceName) AS service_names,
                   CONCAT(tblvet.vFirstname, ' ', tblvet.vLastname) AS vet_name,
                   GROUP_CONCAT(DISTINCT tblpet.pet_Name) AS transaction_pets
            FROM tblmedical_record
            INNER JOIN tblpet ON tblmedical_record.pet_ID = tblpet.pet_ID
            INNER JOIN tblclients ON tblpet.client_id = tblclients.client_id
            LEFT JOIN tblappointment ON tblmedical_record.Appt_ID = tblappointment.Appt_ID
            LEFT JOIN tblpet_services ON tblpet_services.pet_ID = tblpet.pet_ID
            LEFT JOIN tblservices ON tblpet_services.service_id = tblservices.service_id
            LEFT JOIN tblvet ON tblmedical_record.vet_ID = tblvet.vet_ID
            LEFT JOIN tbltransaction ON tbltransaction.pet_ID = tblpet.pet_ID
            WHERE tblmedical_record.Med_ID = $med_id
            GROUP BY tblmedical_record.Med_ID";

    $result = $con->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        ?>
        <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Record Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
        }
        .report-container {
            max-width: 800px;
            margin: auto;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 10px;
        }
        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-content {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
        }
        .print-button {
            text-align: center;
        }
        .print-button button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .print-button button:hover {
            background-color: #45a049;
        }
        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <img src="/public/admin/uploads/clinic-logo.jpg" alt="Clinic Logo" style="width: 110px; height: auto; margin-bottom: 5px;">
        <h2 class="report-header">Medical Record Details</h2>
        <table>
            <tr>
                <th>Field</th>
                <th>Details</th>
            </tr>
            <tr>
                <td>Medical Record ID</td>
                <td><?php echo $row['Med_ID']; ?></td>
            </tr>
            <tr>
                <td>Pet Name</td>
                <td><?php echo $row['pet_Name']; ?></td>
            </tr>
            <tr>
                <td>Owner Name</td>
                <td><?php echo $row['Name']; ?></td>
            </tr>
            <tr>
                <td>Visit Date</td>
                <td><?php echo $row['Appointment_Date']; ?></td>
            </tr>
            <tr>
                <td>Weight</td>
                <td><?php echo $row['weight']; ?> kg</td>
            </tr>
            <tr>
                <td>Temperature</td>
                <td><?php echo $row['temp']; ?> °C</td>
            </tr>
            <tr>
                <td>Diagnosis Result</td>
                <td><?php echo $row['diagnosis']; ?></td>
            </tr>
            <tr>
                <td>Treatment</td>
                <td><?php echo $row['treatment']; ?></td>
            </tr>
            <tr>
                <td>Notes</td>
                <td><?php echo $row['notes']; ?></td>
            </tr>
            <tr>
                <td>Services</td>
                <td><?php echo $row['service_names']; ?></td>
            </tr>
            <tr>
                <td>Veterinarian</td>
                <td><?php echo $row['vet_name']; ?></td>
            </tr>
        </table>
        <div class="print-button">
            <button onclick="window.print();">Print</button>
        </div>
    </div>
</body>
</html>
        <?php
    } else {
        echo "<p>Record not found.</p>";
    }
}
?>