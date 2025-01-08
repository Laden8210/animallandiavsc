<?php
header('Content-type:application/json');
include 'dbconnection.php';

$query = "SELECT * FROM tblservices";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) > 0) {
    $services = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $services[] = $row;
    }
    echo json_encode(['success' => true, 'message' => $services]);
} else {
    echo json_encode(['success' => false, 'message' => 'No services available found']);
}
?>

