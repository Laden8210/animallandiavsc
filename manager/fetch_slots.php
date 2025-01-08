<?php
include('includes/dbconnection.php');

if (isset($_GET['date'])) {
    $date = mysqli_real_escape_string($con, $_GET['date']);

    $query = "SELECT COUNT(*) AS total_appointments FROM tblappointment WHERE Appointment_Date = '$date'";
    $result = mysqli_query($con, $query);
    $data = mysqli_fetch_assoc($result);

    echo json_encode($data);
}
?>
