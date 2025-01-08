<?php
header("Content-Type: application/json");
include "dbconnection.php"; 

$response = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $Name = filter_input(INPUT_POST, 'Name', FILTER_SANITIZE_STRING);
    $Email = filter_input(INPUT_POST, 'Email', FILTER_VALIDATE_EMAIL);
    $ContactNumber = filter_input(INPUT_POST, 'ContactNumber', FILTER_SANITIZE_STRING); 
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING); 
    $Address = filter_input(INPUT_POST, 'Address', FILTER_SANITIZE_STRING); 
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING); 
    $password = $_POST['password'];


    if (!$Name || strlen($Name) < 2) {
        $response['error'] = true;
        $response['message'] = "Invalid name. It must be at least 2 characters.";
        echo json_encode($response);
        exit;
    }
    if (!$Email) {
        $response['error'] = true;
        $response['message'] = "Invalid email address.";
        echo json_encode($response);
        exit;
    }
    if (!preg_match("/^[0-9]{11}$/", $ContactNumber)) {
        $response['error'] = true;
        $response['message'] = "Contact number must be 11 digits.";
        echo json_encode($response);
        exit;
    }


    $checkQuery = $con->prepare("SELECT * FROM tblclients WHERE Email = ? OR username = ?");
    $checkQuery->bind_param("ss", $Email, $username); 
    $checkQuery->execute();
    $result = $checkQuery->get_result();

    if ($result->num_rows > 0) {
        $response['error'] = true;
        $response['message'] = "Email or Username already exists.";
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert data into the database
        $insertQuery = $con->prepare("INSERT INTO tblclients (username, Name, Address, Email, ContactNumber,gender, password, CreationDate, UpdationDate) VALUES (?, ?, ?, ?,?, ?, ?, NOW(), NOW())");
        $insertQuery->bind_param("sssssss", $username, $Name, $Address, $Email, $ContactNumber,$gender, $hashedPassword);

        if ($insertQuery->execute()) {
            $response['error'] = false;
            $response['message'] = "Client registered successfully.";
        } else {
            $response['error'] = true;
            $response['message'] = "Failed to register client.";
        }
    }
} else {
    $response['error'] = true;
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
?>
