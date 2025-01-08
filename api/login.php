<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


include("dbconnection.php");

$response = array();


$data = json_decode(file_get_contents("php://input"), true);


if (isset($data['Email']) && isset($data['password'])) {
    $Email = $data['Email'];
    $password = $data['password'];

  
    if (empty($Email) || empty($password)) {
        $response['error'] = true;
        $response['message'] = "Email and password are required.";
        echo json_encode($response);
        exit;
    }


    $stmt = $con->prepare("SELECT * FROM tblclients WHERE Email = ?");
    $stmt->bind_param("s", $Email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Login successful
            $response['error'] = false;
            $response['message'] = "Login successful!";
            $response['user'] = $user; 
        } else {
            // Invalid password
            $response['error'] = true;
            $response['message'] = "Incorrect password.";
        }
    } else {
       
        $response['error'] = true;
        $response['message'] = "No user found with this email.";
    }

   
    $stmt->close();
    $con->close();
} else {

    $response['error'] = true;
    $response['message'] = "Email and password are required.";
}

header('Content-Type: application/json'); 
echo json_encode($response); 
?>
