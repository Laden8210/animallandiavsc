<?php 
session_start();
error_reporting(1);
include('includes/dbconnection.php');

// Ensure the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only GET requests are allowed.']);
    exit;
}



// Get transaction ID from query parameter
$trid = intval($_GET['transid']);

try {
    // Prepare the query to fetch billing details
    $billingQuery = $con->prepare("SELECT cashReceived, changeAmount FROM tblbilling 
        WHERE billingid = (SELECT billingid FROM tbltransaction WHERE Trans_Code = ? LIMIT 1)");
    $billingQuery->bind_param('i', $trid);
    $billingQuery->execute();
    $billingData = $billingQuery->get_result()->fetch_assoc();
    $cashReceived = $billingData['cashReceived'] ?? 0;
    $changeAmount = $billingData['changeAmount'] ?? 0;

    // Fetch transaction and client details
    $transactionQuery = $con->prepare("
        SELECT DISTINCT 
            CONVERT_TZ(tbltransaction.Transaction_Date, '+00:00', '+08:00') AS Transaction_Date,
            tblclients.Name, tblclients.Address, tblclients.Email, tblclients.ContactNumber,
            tblclients.CreationDate, tbltransaction.status, tblpet.pet_Name
        FROM tbltransaction
        JOIN tblclients ON tblclients.client_id = tbltransaction.client_id
        LEFT JOIN tblpet ON tblpet.pet_ID = tbltransaction.pet_ID
        WHERE tbltransaction.Trans_Code = ?");
    $transactionQuery->bind_param('i', $trid);
    $transactionQuery->execute();
    $transactionResult = $transactionQuery->get_result()->fetch_assoc();

    // Format dates
    $creationDate = new DateTime($transactionResult['CreationDate'], new DateTimeZone('UTC'));
    $creationDate->setTimezone(new DateTimeZone('Asia/Manila'));
    $formattedCreationDate = $creationDate->format('m/d/Y - h:i:s A');

    $transactionDate = new DateTime($transactionResult['Transaction_Date'], new DateTimeZone('Asia/Manila'));
    $formattedTransactionDate = $transactionDate->format('m/d/Y - h:i:s A');

    // Fetch transaction details (services and products)
    $detailsQuery = $con->prepare("
        SELECT 'Service' AS Type, tblservices.ServiceName AS Description, tblservices.Cost , tbltransaction.Qty
        FROM tbltransaction 
        JOIN tblservices ON tblservices.service_id = tbltransaction.service_id 
        WHERE tbltransaction.Trans_Code = ?
        UNION ALL
        SELECT 'Product' AS Type, tblproducts.ProductName AS Description, tblproducts.price , tbltransaction.Qty
        FROM tbltransaction 
        JOIN tblproducts ON tblproducts.ProductID = tbltransaction.ProductID 
        WHERE tbltransaction.Trans_Code = ?");
    $detailsQuery->bind_param('ii', $trid, $trid);
    $detailsQuery->execute();
    $detailsResult = $detailsQuery->get_result();

    // Calculate the total amount
    $totalAmount = 0;
    $items = [];
    while ($row = $detailsResult->fetch_assoc()) {
        $subtotal = $row['Cost'] * $row['Qty'];
        $items[] = [
            'type' => $row['Type'],
            'description' => $row['Description'],
            'cost' => number_format($row['Cost'], 2),
            'quantity' => $row['Qty'],
            'subtotal' => number_format($subtotal, 2)
        ];
        $totalAmount += $subtotal;
    }

    // Prepare the response
    $response = [
        'success' => true,
        'transaction' => [
            'transaction_id' => $trid,
            'client_name' => $transactionResult['Name'],
            'pet_name' => $transactionResult['pet_Name'],
            'address' => $transactionResult['Address'],
            'creation_date' => $formattedCreationDate,
            'transaction_date' => $formattedTransactionDate,
            'status' => $transactionResult['status'] == 'Paid' ? 'Paid' : 'Unpaid',
            'items' => $items,
            'total_amount' => number_format($totalAmount, 2),
            'cash_received' => number_format($cashReceived, 2),
            'change_amount' => number_format($changeAmount, 2)
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
