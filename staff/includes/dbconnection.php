<?php

$host = "localhost"; 
$user = "root";
$password = "Laden8210";
$database = "u920096089_vmscdb";

$con = mysqli_connect($host, $user, $password, $database);

if (!$con) {
    
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Connection failed. Please try again later.");
}
?>
