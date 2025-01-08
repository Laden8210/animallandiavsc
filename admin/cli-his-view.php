<?php 
session_start();
error_reporting(1);
include('includes/dbconnection.php');

if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit();
}

$trid = intval($_GET['transid']);

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
    <title>ALVSC || View Transaction</title>
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
        .button-container {
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .print-hide {
      padding: 10px 20px;
      font-size: 16px;
      cursor: pointer;
    }


    </style>
</head> 
<body>
            <div class="main-page">
                <div class="tables" id="exampl">
                    <?php
                    $ret = $con->prepare("
                        SELECT DISTINCT 
                            CONVERT_TZ(tbltransaction.Transaction_Date, '+00:00', '+08:00') AS Transaction_Date,
                            tblclients.Name, tblclients.Address, tblclients.Email, tblclients.ContactNumber,
                            tblclients.CreationDate, tbltransaction.status, tblpet.pet_Name
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
                                <td><?php echo htmlspecialchars($row['pet_Name']); ?></td> 
                                <td><?php echo htmlspecialchars($row['Address']); ?></td> 
                                  <td><?php 
                                    $CreationDate = $row['CreationDate'];
                                    $datetime = $CreationDate . ' ' ;
                                    $dateTimeObj = new DateTime($datetime);
                                    $formattedDate = $dateTimeObj->format('m/d/Y'); 
                                    $formattedTime = $dateTimeObj->format('h:i:s A'); 
                                    echo htmlspecialchars($formattedDate . ' - ' . $formattedTime);
                                ?></td> 
                                <td>
                                    <?php
                                    $Transaction_Date = $row['Transaction_Date'];
                                    $dateTimeObj = new DateTime($Transaction_Date, new DateTimeZone('Asia/Manila'));
                                    echo htmlspecialchars($dateTimeObj->format('m/d/Y - h:i:s A'));
                                    ?>
                                </td>
                                <td style="text-align:center"><?php echo htmlspecialchars($row['status'] == 'Paid' ? 'Paid' : 'Unpaid'); ?></td>
                            </tr> 
                        </table>

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

                        </table>

                        <table width="100%">
                            <tr>
                                <th colspan="3" style="text-align:center">Cash Received</th>
                                <td>
                                    <?php echo htmlspecialchars(number_format($cashReceived, 2)); ?>
                                </td>
                            </tr>
                            <tr>
                                <th colspan="3" style="text-align:center">Change</th>
                                <td><?php echo number_format($changeAmount, 2); ?></td>
                            </tr>
                        </table>
                    </div>
                    <?php } ?>
                     <div class="button-container">
    <button class="print-hide" onclick="window.print()">Print</button>
  </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
