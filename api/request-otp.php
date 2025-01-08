<?php
// Set the response format
header("Content-Type: application/json; charset=UTF-8");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require 'dbconnection.php'; 
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get raw POST data
$inputData = file_get_contents('php://input');

// Debugging: Log raw input
error_log("Raw Input Data: " . $inputData);

// Decode JSON input
$data = json_decode($inputData, true);

// Validate JSON decoding
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON Decode Error: " . json_last_error_msg());
    echo json_encode(["success" => false, "message" => "Invalid JSON format."]);
    exit();
}

// Validate required field: Email
if (!isset($data['Email']) || empty($data['Email'])) {
    echo json_encode(["success" => false, "message" => "Email is required."]);
    exit();
}

$Email = $data['Email'];

// Check if email exists in the database
$stmt = $con->prepare("SELECT client_id FROM tblclients WHERE Email = ?");
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error: Failed to prepare statement."]);
    error_log("SQL Error: " . $con->error);
    exit();
}

$stmt->bind_param("s", $Email);
$stmt->execute();
$result = $stmt->get_result();

// If email is found, proceed with OTP generation
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $client_id = $row['client_id'];

    // Generate OTP and set expiry
    $otp = rand(100000, 999999);
    $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes")); // OTP valid for 10 minutes

    // Insert OTP into the database or update if it already exists
    $stmt = $con->prepare(
        "INSERT INTO password_reset (client_id, otp, expiry)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE otp = ?, expiry = ?"
    );
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Database error: Failed to prepare statement."]);
        error_log("SQL Error: " . $con->error);
        exit();
    }
    $stmt->bind_param("issss", $client_id, $otp, $expiry, $otp, $expiry);
    $stmt->execute();

    // Initialize PHPMailer
    $mail = new PHPMailer(true);
    try {
        // Configure PHPMailer
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'nicolesaludez2@gmail.com'; // Your email
        $mail->Password = 'zipu maxu sqmo vmxj'; // App-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Set email content
        $mail->setFrom('nicolesaludez2@gmail.com', 'AnimalLandiavscShop');
        $mail->addAddress($Email);
        $mail->Subject = 'Your OTP for Password Reset';
        $mail->Body = "Your OTP is: $otp. It will expire in 10 minutes.";

        // Send the email
        if ($mail->send()) {
            echo json_encode(["success" => true, "message" => "OTP sent to email.",'client_id' => $client_id]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to send OTP."]);
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
        }
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Mailer Error: " . $e->getMessage()]);
        error_log("Mailer Exception: " . $e->getMessage());
    }
} else {
    echo json_encode(["success" => false, "message" => "Email not registered."]);
    error_log("Email not registered: " . $Email);
}
?>
