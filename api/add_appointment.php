<?php
include('dbconnection.php');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit;
}


$client_id = $input['client_id'] ?? null;
$Appointment_Date = $input['Appointment_Date'] ?? null;
$Appointment_Time = isset($input['Appointment_Time']) ? trim(preg_replace('/[^\x20-\x7E]/', '', $input['Appointment_Time'])) : null;

$pets = $input['pets'] ?? null;

if (!$client_id || !$Appointment_Date || !$Appointment_Time || !$pets || !is_array($pets)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields or invalid pets data.']);
    exit;
}

try {
    mysqli_begin_transaction($con);

    foreach ($pets as $pet) {
        $pet_Name = $pet['pet_Name'] ?? null;
        $pGender = $pet['pGender'] ?? null;
        $Breed = $pet['Breed'] ?? null;
        $selectedServices = $pet['selectedServices'] ?? null;

        if (!$pet_Name || !$pGender || !$Breed || !$selectedServices || !is_array($selectedServices)) {
            throw new Exception('Invalid pet data provided.');
        }


        $query_pet = "INSERT INTO tblpet (client_id, pet_Name, pGender, Breed) 
                      VALUES ('$client_id', '$pet_Name', '$pGender', '$Breed')";
        if (!mysqli_query($con, $query_pet)) {
            throw new Exception('Failed to add pet.');
        }

        $pet_ID = mysqli_insert_id($con);


        $query_appt = "INSERT INTO tblappointment (client_id, pet_ID, Appointment_Date, Appointment_Time) 
                       VALUES ('$client_id', '$pet_ID', '$Appointment_Date', '$Appointment_Time')";
        if (!mysqli_query($con, $query_appt)) {
            throw new Exception('Failed to create appointment.');
        }

        $Appt_ID = mysqli_insert_id($con);


        foreach ($selectedServices as $service_id) {
            $query_service = "INSERT INTO tblpet_services (Appt_ID, service_id, pet_id) 
                              VALUES ('$Appt_ID', '$service_id', '$pet_ID')";
            if (!mysqli_query($con, $query_service)) {
                throw new Exception('Failed to link services.');
            }
        }

        $medicalRecordQuery = "INSERT INTO tblmedical_record (client_id, service_id, Appt_ID, pet_ID) 
        VALUES ('$client_id', '$service_id', '$Appt_ID', '$pet_ID')";
        if (!mysqli_query($con, $medicalRecordQuery)) {
            throw new Exception('Error inserting medical record: ' . mysqli_error($con));
        }
    }


    mysqli_commit($con);


    echo json_encode(['success' => true, 'message' => 'Appointment created successfully.']);
} catch (Exception $e) {

    mysqli_rollback($con);

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
