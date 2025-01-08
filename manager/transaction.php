<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>ALVSC || Transaction</title>
    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <link href="css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet" media="all">
    <link href="css/custom.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/wow.min.js"></script>
    <script>new WOW().init();</script>
</head> 
<style>
    .badge {
        padding: 3px 7px;
        border-radius: 5px;
        color: #fff;
        font-weight: bold;
    }
    .badge-paid { background-color: #8bc34a; }
    .badge-unpaid { background-color: #dc3545; }
    .badge-default { background-color: #6c757d; }
</style>

<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>
        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables">
                    <h4>Transaction History:</h4>
                    <form class="form-inline" style="margin-bottom: 10px;" method="get">
                        <input type="search" name="search" class="form-control" placeholder="Search...">
                        <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                        
                    </form>

                    <table class="table table-bordered mt-3"> 
                        <thead> 
                            <tr> 
                                <th>No.</th> 
                                <th>Client Name</th> 
                                <th>Date and Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr> 
                        </thead> 
                        <tbody>
    <?php
    $query = "";
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search_query = mysqli_real_escape_string($con, $_GET['search']);
        $query = "SELECT DISTINCT
                    tblclients.Name,
                    tbltransaction.Trans_Code,
                    tbltransaction.Transaction_Date,
                    tbltransaction.status
                  FROM
                    tblclients
                    JOIN tbltransaction ON tblclients.client_id = tbltransaction.client_id
                  WHERE
                    tblclients.Name LIKE '%$search_query%' OR
                    tbltransaction.Trans_Code LIKE '%$search_query%'
                  ORDER BY
                    tbltransaction.Transaction_Date DESC";
    } else {
        $query = "SELECT DISTINCT
                    tblclients.Name,
                    tbltransaction.Trans_Code,
                    tbltransaction.Transaction_Date,
                    tbltransaction.status
                  FROM
                    tblclients
                    JOIN tbltransaction ON tblclients.client_id = tbltransaction.client_id
                  ORDER BY
                    tbltransaction.Transaction_Date DESC";
    }

    $result = mysqli_query($con, $query);
    if (mysqli_num_rows($result) > 0) {
        $cnt = 1;
        while ($row = mysqli_fetch_array($result)) {
            echo '<tr>';
            echo '<th scope="row">' . $cnt . '</th>';
            echo '<td>' . htmlspecialchars($row['Name']) . '</td>';
            
           $Transaction_Date = $row['Transaction_Date'];
$dateTimeObj = new DateTime($Transaction_Date, new DateTimeZone('UTC'));
$dateTimeObj->setTimezone(new DateTimeZone('Asia/Manila'));
$formattedDate = $dateTimeObj->format('m/d/Y');
$formattedTime = $dateTimeObj->format('h:i:s A');
echo '<td>' . $formattedDate . ' - ' . $formattedTime . '</td>';

            
            $status = $row['status'];
            $badgeClass = $status == 'Paid' ? 'badge-paid' : ($status == 'Unpaid' ? 'badge-unpaid' : 'badge-default');
            echo '<td><span class="badge ' . $badgeClass . '">' . htmlspecialchars($status) . '</span></td>';
            
            echo '<td>
                    <a href="pay-transaction.php?transid=' . htmlspecialchars($row['Trans_Code']) . '" class="btn btn-success btn-sm">Pay</a>
                    <a href="view.php?transid=' . htmlspecialchars($row['Trans_Code']) . '" class="btn btn-info btn-sm">View</a>
                  </td>';
            echo '</tr>';
            $cnt++;
        }
    } else {
        echo '<tr><td colspan="5" class="text-center">No transactions found.</td></tr>';
    }
    ?>
</tbody>

                    </table> 
                </div>
                <button class="btn btn-default" onclick="history.back()">Previous</button>

            </div>
        </div>
    </div>
</body>
</html>
