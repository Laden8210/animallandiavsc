<?php
// Database configuration

$host = "localhost"; 
$user = "u278537438_animal_8210";
$password = "Animal_8210";
$database = "u278537438_animal_8210";

// Create connection
$con = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$con) {
    // Log the error instead of displaying it in production
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Connection failed. Please try again later."); // User-friendly error
}
?>
