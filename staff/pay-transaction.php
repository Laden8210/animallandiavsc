<?php
session_start();
error_reporting(1);
include('includes/dbconnection.php');
if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit();
}

$trid = intval($_GET['transid']);

$statusQuery = $con->prepare("SELECT status, pet_ID FROM tbltransaction WHERE Trans_Code = ?");
$statusQuery->bind_param('i', $trid);
$statusQuery->execute();
$statusResult = $statusQuery->get_result();
$statusRow = $statusResult->fetch_assoc();
$isPaid = ($statusRow['status'] == 'Paid') ? true : false;
$petID = $statusRow['pet_ID']; 

if (isset($_POST['updateStatus'])) {
    $status = $_POST['status'];
    $query = "UPDATE tbltransaction SET status = ? WHERE Trans_Code = ?";
    $stmt = $con->prepare($query);
    $stmt->bind_param('si', $status, $trid);
    if ($stmt->execute()) {
        echo "<script>alert('Payment status updated successfully.');</script>";
    } else {
        echo "<script>alert('Failed to update payment status.');</script>";
    }
}

if (isset($_POST['cashReceived']) && !$isPaid) {
    $cashReceived = $_POST['cashReceived'];
    
    $gtotal = 0;

    $serviceQuery = $con->prepare("SELECT SUM(tblservices.Cost * tbltransaction.Qty) AS totalCost 
    FROM tbltransaction 
    JOIN tblservices ON tblservices.service_id = tbltransaction.service_id 
    WHERE tbltransaction.Trans_Code = ?");
$serviceQuery->bind_param('i', $trid);
$serviceQuery->execute();
$result = $serviceQuery->get_result();
if ($row = $result->fetch_assoc()) {
    $gtotal += $row['totalCost'] ?? 0;
}

    $productQuery = $con->prepare("SELECT SUM(tblproducts.price * tbltransaction.Qty) AS totalCost FROM tbltransaction 
        JOIN tblproducts ON tblproducts.ProductID = tbltransaction.ProductID 
        WHERE tbltransaction.Trans_Code = ?");
    $productQuery->bind_param('i', $trid);
    $productQuery->execute();
    $result = $productQuery->get_result();
    if ($row = $result->fetch_assoc()) {
        $gtotal += $row['totalCost'] ?? 0;
    }

    if ($cashReceived < $gtotal) {
        echo "<script>
            alert('Warning: Cash received is less than the total amount. Please provide sufficient payment.');
            window.location.href = window.location.href; // Reload the page for correction
        </script>";
        exit(); 
    }

    $changeAmount = $cashReceived - $gtotal;

    $billingQuery = "INSERT INTO tblbilling (totalAmount, cashReceived, changeAmount) VALUES (?, ?, ?)";
    $stmt = $con->prepare($billingQuery);
    $stmt->bind_param('ddd', $gtotal, $cashReceived, $changeAmount);
    $stmt->execute();

    $billingid = $con->insert_id;

    $updateTransactionQuery = "UPDATE tbltransaction SET billingid = ?, status = 'Paid' WHERE Trans_Code = ?";
    $stmt = $con->prepare($updateTransactionQuery);
    $stmt->bind_param('ii', $billingid, $trid);
    $stmt->execute();

    echo "<script>alert('Payment received successfully, and status updated to Paid.');</script>";
}

$billingQuery = $con->prepare("SELECT cashReceived, changeAmount FROM tblbilling 
    WHERE billingid = (SELECT billingid FROM tbltransaction WHERE Trans_Code = ? LIMIT 1)");
$billingQuery->bind_param('i', $trid);
$billingQuery->execute();
$billingData = $billingQuery->get_result()->fetch_assoc();
$cashReceived = $billingData['cashReceived'] ?? 0;
$changeAmount = $billingData['changeAmount'] ?? 0;

?>

<!DOCTYPE HTML>
<head>
    <title>ALVSC || Pay Transaction</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet" type='text/css'>
    <style>
        .bordered-form { 
            border: 1px solid black; 
            padding: 20px; 
            border-radius: 10px; 
            background-color: #f9f9f9; 
            margin-top: 20px; 
        }
        .form-header {
            text-align: center; 
            font-weight: bold; 
            margin-bottom: 10px; 
        }
        table, th, td { 
            border-collapse: collapse; 
            padding: 8px; margin: 0; 
            border: 1px solid black; 
        }
        th, td { 
            text-align: left; 
        }
        button { 
            padding: 10px 20px; 
            background-color: #337ab7;
            color: #fff; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
        }
        button:hover { 
            background-color: #2e6da4;
        }
        @media print {
            .print-hide {
                display: none;
            }
        }
        .status-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px; 
        }
        .status-form label {
            margin-bottom: 10px;
            font-weight: bold;
        }
        .status-form select,
        .status-form button {
            margin: 5px;
            font-size: 14px; 
        }
        .status-form button {
            background-color: #337ab7;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .status-form button:hover {
            background-color: #2e6da4;
        }
        .cash-received {
            margin-top: 20px;
        }
        .cash-received input {
            margin-left: 10px;
        }
    </style>
</head> 
<body class="cbp-spmenu-push">
    <div class="main-content">
    <?php include_once('includes/sidebar.php'); ?>
    <?php include_once('includes/header.php'); ?>
        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables" id="exampl">
                    <?php
                    $ret = $con->prepare("SELECT DISTINCT DATE_FORMAT(tbltransaction.Transaction_Date, '%Y-%m-%d %H:%i:%s') AS Transaction_Date, tblclients.Name, tblclients.Address,  tblclients.Email, tblclients.ContactNumber, tblclients.CreationDate, tbltransaction.status, tblpet.pet_Name
                    FROM tbltransaction 
                    JOIN tblclients ON tblclients.client_id = tbltransaction.client_id
                    LEFT JOIN tblpet ON tblpet.pet_ID = tbltransaction.pet_ID
                    WHERE tbltransaction.Trans_Code = ?");
                    
                    $ret->bind_param('i', $trid);
                    $ret->execute();
                    $result = $ret->get_result();
                    while ($row = $result->fetch_assoc()) {
                    ?>

                    <div class="bordered-form">
                        <h4 class="form-header">Transaction No.<?php echo htmlspecialchars($trid); ?></h4>
                        <table width="100%"> 
                            <tr>
                                <th colspan="6">Client Details</th>  
                            </tr>
                            <tr> 
    <th>Name</th> 
    <th>Pet Name</th> 
    <th>Address</th> 
    <th>Registration Date</th> 
    <th>Transaction Date</th> 
    <th>Status</th> 
</tr> 
<tr> 
    <td><?php echo htmlspecialchars($row['Name']); ?></td> 
    <td><?php echo htmlspecialchars($row['pet_Name'] ?? 'No Pet'); ?></td> 
    <td><?php echo htmlspecialchars($row['Address']); ?></td>
    <td><?php 
        $CreationDate = $row['CreationDate'];
$datetime = $CreationDate . ' ';
$dateTimeObj = new DateTime($datetime, new DateTimeZone('UTC'));
$dateTimeObj->setTimezone(new DateTimeZone('Asia/Manila'));
$formattedDate = $dateTimeObj->format('m/d/Y'); 
$formattedTime = $dateTimeObj->format('h:i:s A'); 
echo htmlspecialchars($formattedDate . ' - ' . $formattedTime);

    ?></td> 
    <td><?php 
        $Transaction_Date = $row['Transaction_Date'];
$datetime = $Transaction_Date . ' ';
$dateTimeObj = new DateTime($datetime, new DateTimeZone('UTC'));
$dateTimeObj->setTimezone(new DateTimeZone('Asia/Manila'));
$formattedDate = $dateTimeObj->format('m/d/Y'); 
$formattedTime = $dateTimeObj->format('h:i:s A'); 
echo htmlspecialchars($formattedDate . ' - ' . $formattedTime);
    ?></td> 
    <td><?php echo htmlspecialchars($row['status']); ?></td> 
</tr>

<table width="100%"> 
                            <tr>
                                <th colspan="4">Transaction Details</th>  
                            </tr>
                            <tr>
                                <th>No.</th>  
                                <th>Description</th>
                                <th>Cost</th>
                                <th>Quantity</th>
                            </tr>

                            <?php
                            $cnt = 1;
                            $gtotal = 0;

                            $ret = $con->prepare("SELECT 'Service' AS Type, tblservices.ServiceName AS Description, tblservices.Cost , tbltransaction.Qty
                            FROM tbltransaction 
                            JOIN tblservices ON tblservices.service_id = tbltransaction.service_id 
                            WHERE tbltransaction.Trans_Code = ?
                            UNION ALL
                            SELECT 'Product' AS Type, tblproducts.ProductName AS Description, tblproducts.price , tbltransaction.Qty
                            FROM tbltransaction 
                            JOIN tblproducts ON tblproducts.ProductID = tbltransaction.ProductID 
                            WHERE tbltransaction.Trans_Code = ?");
                            $ret->bind_param('ii', $trid, $trid);
                            $ret->execute();
                            $result = $ret->get_result();
                            while ($row = $result->fetch_assoc()) {
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cnt); ?></td> 
                                <td><?php echo htmlspecialchars($row['Description']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($row['Cost'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($row['Qty']); ?></td>
                            </tr> 
                            <?php
                            $cnt++;
                            $subtotal = $row['Cost'] * $row['Qty'];
                            $gtotal += $subtotal;
                            } 
                            ?>
                            <tr>
                                <th colspan="3" style="text-align:right;">Total</th>
                                <td><?php echo htmlspecialchars(number_format($gtotal, 2)); ?></td>
                            </tr>
                        
<form method="post" onsubmit="return confirmCashReceived();">
    <tr>
        <th colspan="3" style="text-align:center">Cash Received</th>
        <td>
            <input type="number" name="cashReceived" value="<?php echo htmlspecialchars($cashReceived); ?>" required>
            <button type="submit" class="print-hide">Submit</button>
        </td>
    </tr>
</form>


                        <tr>
                            <th colspan="3" style="text-align:center">Change</th>
                            <td><?php echo number_format($changeAmount, 2); ?></td>
                        </tr>

                    </div>
                        </table>
                        </table>
                        
                    <?php } ?>
                        <a href="transaction.php" class="btn btn-primary btn-back">Back</a>
                    </div>
                    <script>
function confirmCashReceived() {
    return confirm("Please double check the amount before submitting! This will never be changeable. Do you want to proceed?");
}
</script>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
