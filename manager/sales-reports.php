<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit();
}

$filterType = isset($_POST['filterType']) ? $_POST['filterType'] : 'daily';
$startDate = isset($_POST['startDate']) ? $_POST['startDate'] : null;
$endDate = isset($_POST['endDate']) ? $_POST['endDate'] : null;

$dateFilter = '';
$params = [];

if (!empty($startDate) && !empty($endDate)) {
    if ($filterType == 'monthly') {
        $dateFilter = "WHERE BINARY DATE_FORMAT(tbltransaction.Transaction_Date, '%Y-%m') BETWEEN ? AND ?";
        $params = [date('Y-m', strtotime($startDate)), date('Y-m', strtotime($endDate))];
    } elseif ($filterType == 'yearly') {
        $dateFilter = "WHERE BINARY DATE_FORMAT(tbltransaction.Transaction_Date, '%Y') BETWEEN ? AND ?";
        $params = [date('Y', strtotime($startDate)), date('Y', strtotime($endDate))];
    } else {
        $dateFilter = "WHERE BINARY DATE(tbltransaction.Transaction_Date) BETWEEN ? AND ?";
        $params = [$startDate, $endDate];
    }
}


try {
    $clientSalesQuery = "SELECT tblclients.Name, 
        tbltransaction.Transaction_Date,
        SUM(CASE WHEN tblservices.service_id IS NOT NULL THEN tblservices.Cost * tbltransaction.Qty ELSE 0 END) AS serviceTotal,
        SUM(CASE WHEN tblproducts.ProductID IS NOT NULL THEN tblproducts.price * tbltransaction.Qty ELSE 0 END) AS productTotal,
        (SUM(CASE WHEN tblservices.service_id IS NOT NULL THEN tblservices.Cost * tbltransaction.Qty ELSE 0 END) + 
        SUM(CASE WHEN tblproducts.ProductID IS NOT NULL THEN tblproducts.price * tbltransaction.Qty ELSE 0 END)) AS totalSpent
        FROM tbltransaction
        JOIN tblclients ON tblclients.client_id = tbltransaction.client_id
        LEFT JOIN tblservices ON tblservices.service_id = tbltransaction.service_id
        LEFT JOIN tblproducts ON tblproducts.ProductID = tbltransaction.ProductID
        $dateFilter
        GROUP BY tblclients.client_id";

    if ($filterType == 'monthly') {
        $clientSalesQuery .= ", DATE_FORMAT(tbltransaction.Transaction_Date, '%Y-%m')";
    } elseif ($filterType == 'yearly') {
        $clientSalesQuery .= ", DATE_FORMAT(tbltransaction.Transaction_Date, '%Y')";
    } else {
        $clientSalesQuery .= ", tbltransaction.Transaction_Date";
    }

    $clientSalesQuery .= " ORDER BY tbltransaction.Transaction_Date DESC";

    $stmt = $con->prepare($clientSalesQuery);
    if (!empty($params)) {
        $stmt->bind_param('ss', ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $overallTotal = 0;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    exit();
}
?>


<!DOCTYPE HTML>
<head>
    <title>ALVSC || Sales Report</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script>
        function printPage() {
    var content = document.getElementById('printableArea').innerHTML; 
    var newWindow = window.open('', '', 'height=400,width=600');
    newWindow.document.write('<html><head><title>Print Sales Report</title>');
    newWindow.document.write('<link rel="stylesheet" href="css/bootstrap.css" type="text/css" />');
    newWindow.document.write('</head><body>');
    newWindow.document.write(content); 
    newWindow.document.write('</body></html>');
    newWindow.document.close();
    newWindow.print();
}
    </script>
</head>
<style>
    h3.title1 {
        font-size: 2em;
        color: #000000;
        margin-bottom: 0.8em;
    }
</style>
<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>

        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables" id="printableArea"> 
                    <h3 class="title1">Sales Report</h3>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="filterType">Filter by:</label>
                            <select name="filterType" id="filterType" class="form-control">
                                <option value="daily" <?php echo ($filterType == 'daily') ? 'selected' : ''; ?>>Daily</option>
                                <option value="monthly" <?php echo ($filterType == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                                <option value="yearly" <?php echo ($filterType == 'yearly') ? 'selected' : ''; ?>>Yearly</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="startDate">Start Date:</label>
                            <input type="date" name="startDate" id="startDate" value="<?php echo htmlspecialchars($startDate); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="endDate">End Date:</label>
                            <input type="date" name="endDate" id="endDate" value="<?php echo htmlspecialchars($endDate); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>

                    <div class="text-right">
                        <a href="sales-reports-detail.php" class="btn btn-primary">View Chart </a>
                    </div>

                    <table class="table table-bordered" id="salesReportTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Client Name</th>
                                <th>Service Sales</th>
                                <th>Product Sales</th>
                                <th>Total Sales</th>
                                <th>Date</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cnt = 1;
                            while ($row = $result->fetch_assoc()) {
                                $serviceTotal = $row['serviceTotal'];
                                $productTotal = $row['productTotal'];
                                $totalSpent = $row['totalSpent'];
                                $transactionDate = ($filterType == 'monthly') ? date("M-Y", strtotime($row['Transaction_Date'])) : 
                                    (($filterType == 'yearly') ? date("Y", strtotime($row['Transaction_Date'])) : date("d-M-Y", strtotime($row['Transaction_Date'])));

                                $overallTotal += $totalSpent;
                            ?>
                                <tr>
                                    <td><?php echo $cnt++; ?></td>
                                    <td><?php echo htmlspecialchars($row['Name']); ?></td>
                                    <td><?php echo number_format($serviceTotal, 2); ?></td>
                                    <td><?php echo number_format($productTotal, 2); ?></td>
                                    <td><?php echo number_format($totalSpent, 2); ?></td>
                                    <td><?php echo htmlspecialchars($transactionDate); ?></td> 
                                </tr>
                            <?php
                            }
                            ?>
                            <tr>
                                <th colspan="4" style="text-align:right;">Overall Total Sales</th>
                                <th><?php echo number_format($overallTotal, 2); ?></th>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="display: flex; justify-content: center;">
                    <button onclick="printPage()" class="btn btn-secondary">Print Page</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
