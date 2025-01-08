<?php 
include('../includes/dbconnection.php');
if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $ProductTypeID = $_GET['ProductTypeID'];
    $product_types_result = mysqli_query($con, "SELECT * FROM tblsub_category WHERE ProductTypeID = '$ProductTypeID'");
    $product_types = [];
    while ($row = mysqli_fetch_assoc($product_types_result)) {
        $product_types[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($product_types);
}
?>