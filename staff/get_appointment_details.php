<?php
include('includes/dbconnection.php');

if (isset($_POST['appointment_id'])) {
    $appointmentID = mysqli_real_escape_string($con, $_POST['appointment_id']);

    $query = "
    SELECT tblappointment.*, 
           tblclients.Name AS ClientName, tblclients.gender AS ClientGender, tblclients.Address, tblclients.ContactNumber, tblclients.Email, 
           tblpet.pet_Name AS PetName, tblpet.Breed, tblpet.pGender AS PetGender,
           GROUP_CONCAT(DISTINCT tblservices.ServiceName ORDER BY tblservices.ServiceName ASC) AS ServiceName,
           GROUP_CONCAT(DISTINCT tblpet_services.service_id ORDER BY tblpet_services.service_id ASC) AS ServiceIDs
    FROM tblappointment
    LEFT JOIN tblclients ON tblappointment.client_id = tblclients.client_id
    LEFT JOIN tblpet ON tblappointment.pet_ID = tblpet.pet_ID
    LEFT JOIN tblpet_services ON tblappointment.Appt_ID = tblpet_services.Appt_ID
    LEFT JOIN tblservices ON tblpet_services.service_id = tblservices.service_id
    WHERE tblappointment.Appt_ID = '$appointmentID'
    GROUP BY tblappointment.Appt_ID";

    $result = mysqli_query($con, $query);

    if (!$result) {
        echo "<tr><td colspan='2'>Error retrieving data: " . mysqli_error($con) . "</td></tr>";
        exit;
    }

    if ($row = mysqli_fetch_assoc($result)) {
        print_r($row);  

        echo "
        <tr>
            <td><strong>Client Name</strong></td>
            <td>{$row['ClientName']}</td>
        </tr>
        <tr>
            <td><strong>Gender</strong></td>
            <td>{$row['ClientGender']}</td>
        </tr>
        <tr>
            <td><strong>Address</strong></td>
            <td>{$row['Address']}</td>
        </tr>
        <tr>
            <td><strong>Contact Number</strong></td>
            <td>{$row['ContactNumber']}</td>
        </tr>
        <tr>
            <td><strong>Email</strong></td>
            <td>{$row['Email']}</td>
        </tr>
        <tr>
            <td><strong>Pet Name</strong></td>
            <td>{$row['PetName']}</td>
        </tr>
        <tr>
            <td><strong>Breed</strong></td>
            <td>{$row['Breed']}</td>
        </tr>
        <tr>
            <td><strong>Pet Gender</strong></td>
            <td>{$row['PetGender']}</td>
        </tr>
        <tr>
            <td><strong>Appointment Date</strong></td>
            <td>" . (new DateTime($row['Appointment_Date']))->format('F j, Y') . "</td>
        </tr>
        <tr>
            <td><strong>Appointment Time</strong></td>
            <td>" . (new DateTime($row['Appointment_Time']))->format('h:i A') . "</td>
        </tr>
        <tr>
            <td><strong>Service(s)</strong></td>
            <td>{$row['ServiceName']}</td>
        </tr>
        </tr>";
    } else {
        echo "<tr><td colspan='2'>No details found for this appointment.</td></tr>";
    }
} else {
    echo "<tr><td colspan='2'>No appointment ID provided.</td></tr>";
}


?>
