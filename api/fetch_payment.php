<?php
include('dbconnection.php');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only GET requests are allowed.']);
    exit;
}

try {
    if (!isset($_GET['client'])) {
        throw new Exception('Client ID is required.');
    }
    $client_id = $_GET['client'];


    mysqli_begin_transaction($con);


    $query = "
        SELECT DISTINCT
            tblclients.Name,
            tbltransaction.Trans_Code,
            tbltransaction.Transaction_Date,
            tbltransaction.status
        FROM
            tblclients
        JOIN
            tbltransaction ON tblclients.client_id = tbltransaction.client_id
        WHERE
            tbltransaction.client_id = ?
        ORDER BY
            tbltransaction.Transaction_Date DESC
    ";

    if ($stmt = mysqli_prepare($con, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $client_id); 
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

 
        $appointments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $appointments[] = $row;
        }

        mysqli_stmt_close($stmt);


        mysqli_commit($con);


        echo json_encode(['success' => true, 'appointments' => $appointments]);

    } else {
        throw new Exception('Failed to prepare the SQL statement.');
    }
} catch (Exception $e) {

    mysqli_rollback($con);

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
