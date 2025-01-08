<?php
session_start();
include('includes/dbconnection.php');

date_default_timezone_set('Asia/Manila');

$con->query("SET time_zone = '+08:00';");

if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit();
}

$client_id = isset($_GET['clientid']) ? $_GET['clientid'] : 0;

$query = "
    SELECT 
        tbltransaction.Trans_Code, 
        tbltransaction.client_id, 
        tblclients.Name,
        tbltransaction.Transaction_Date 
    FROM 
        tbltransaction 
    JOIN 
        tblclients 
    ON 
        tbltransaction.client_id = tblclients.client_id
    WHERE 
        tbltransaction.client_id = ? 
    ORDER BY 
        tbltransaction.Transaction_Date DESC
";

$stmt = $con->prepare($query);
$stmt->bind_param("i", $client_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die('Query Error: ' . $con->error);
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>ALVSC || Client History</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .btn-back {
            margin-top: 10px;
        }
    </style>
</head>
<body class="cbp-spmenu-push">
    <div class="main-content">
        
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>

        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables">
                    <h4>Client History for Client ID: <?php echo htmlspecialchars($client_id); ?></h4>
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction No.</th>
                                <th>Client Name</th>
                                <th>Transaction Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                $displayed_trans_codes = []; 

                                while ($row = $result->fetch_assoc()) {
                                    if (!in_array($row['Trans_Code'], $displayed_trans_codes)) {
                                        $displayed_trans_codes[] = $row['Trans_Code'];
                            ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['Trans_Code']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Name']); ?></td>
                                        <td>
                                    <?php
                                    $Transaction_Date = $row['Transaction_Date'];
                                    $dateTimeObj = new DateTime($Transaction_Date, new DateTimeZone('Asia/Manila'));
                                    echo htmlspecialchars($dateTimeObj->format('m/d/Y - h:i:s A'));
                                    ?>
                                </td>
                                        <td>
                                            <a href="cli-his-view.php?transid=<?php echo urlencode($row['Trans_Code']); ?>" class="btn btn-info btn-sm">
                                                View Transaction
                                            </a>
                                        </td>
                                    </tr>
                            <?php 
                                    }
                                }
                            } else { ?>
                                <tr>
                                    <td colspan="4">No records found</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <br><a href="client-list.php" class="btn btn-primary btn-back">Back</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
