<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);
    
    $Email = $data['Email'];
    $otp = $data['otp'];
    $newPassword = $data['newPassword']; 
    $client_id = $data['client_id']; 


    if (empty($Email) || empty($otp) || empty($newPassword) || empty($client_id)) {
        echo json_encode(["success" => false, "message" => "Email, OTP, client ID, and new password are required."]);
        exit();
    }

  
    if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Invalid email format."]);
        exit();
    }


    $stmt = $con->prepare("SELECT client_id FROM tblclients WHERE Email = ?");
    $stmt->bind_param("s", $Email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $client_id = $row['client_id'];

       
        $stmt = $con->prepare("SELECT * FROM password_reset WHERE client_id = ? AND otp = ? AND expiry > NOW()");
        $stmt->bind_param("is", $client_id, $otp);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
         

           
            if (strlen($newPassword) < 8) {
                echo json_encode(["success" => false, "message" => "Password must be at least 8 characters."]);
                exit();
            }

           
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Query to update the password
            $stmt = $con->prepare("UPDATE tblclients SET password = ? WHERE client_id = ?");
            $stmt->bind_param("si", $hashedPassword, $client_id); 

            if ($stmt->execute()) {
             
                $deleteOtpStmt = $con->prepare("DELETE FROM password_reset WHERE client_id = ? AND otp = ?");
                $deleteOtpStmt->bind_param("is", $client_id, $otp);
                $deleteOtpStmt->execute();

                echo json_encode(["success" => true, "message" => "Password successfully updated."]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to update password."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Invalid or expired OTP."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Email not registered."]);
    }
}
?>
