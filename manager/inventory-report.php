<?php
session_start();
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

$search_query = $con->real_escape_string($_GET['search'] ?? '');
$product_type = $_GET['product_type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$query = "
    SELECT tblproducts.*, tblinventory.Quantity, tblinventory.unit, tblproduct_types.TypeName
    FROM tblproducts
    LEFT JOIN tblinventory ON tblproducts.ProductID = tblinventory.ProductID
    LEFT JOIN tblproduct_types ON tblproducts.ProductTypeID = tblproduct_types.ProductTypeID
    WHERE 1=1
";

if (!empty($search_query)) {
    $query .= " AND (tblproducts.ProductName LIKE '%$search_query%' OR tblproducts.ProductCode LIKE '%$search_query%')";
}

if (!empty($product_type)) {
    $query .= " AND tblproducts.ProductTypeID = '$product_type'";
}

if (!empty($start_date) && !empty($end_date)) {
    $query .= " AND tblproducts.ExpirationDate BETWEEN '$start_date' AND '$end_date'";
}

$query .= " ORDER BY tblproducts.ProductID DESC";
$result = mysqli_query($con, $query);
$cnt = 1;
?>

<!DOCTYPE HTML>
<html>

<head>
    <title>ALVSC | Inventory Report</title>
    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/modernizr.custom.js"></script>
    <link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
    <script src="js/wow.min.js"></script>
    <script>new WOW().init();</script>
    <script src="js/metisMenu.min.js"></script>
    <script src="js/custom.js"></script>
    <link href="css/custom.css" rel="stylesheet">
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

<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>
        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables" id="printableArea"> 
                    <h3 class="title1">Inventory Report</h3>
                    <form class="form-inline" method="GET" action="" style="margin-bottom: 10px;">
                        <input type="search" name="search" class="form-control" placeholder="Search by Product Name" value="<?php echo $_GET['search'] ?? ''; ?>">

                        <select name="product_type" class="form-control">
                            <option value="">All Types</option>
                            <?php
                            $type_query = "SELECT * FROM tblproduct_types";
                            $type_result = mysqli_query($con, $type_query);
                            while ($type = mysqli_fetch_assoc($type_result)) {
                                $selected = (isset($_GET['product_type']) && $_GET['product_type'] == $type['ProductTypeID']) ? 'selected' : '';
                                echo "<option value='{$type['ProductTypeID']}' $selected>{$type['TypeName']}</option>";
                            }
                            ?>
                        </select>

                        

                        <button class="btn btn-primary" type="submit"><i class="fa fa-filter"></i> Filter</button>
                    </form>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Product Type</th>
                                <th>Expiration Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $expiration_threshold = 7; 
                            while ($row = mysqli_fetch_array($result)) {
                                $ExpirationDate = !empty($row['ExpirationDate']) ? new DateTime($row['ExpirationDate']) : null;
                                $currentDate = new DateTime();
                                $status = '';

                                if (!$ExpirationDate) {
                                    $status = '<span class="badge bg-secondary">No Expiration</span>';
                                } elseif ($currentDate > $ExpirationDate) {
                                    $status = '<span class="badge bg-danger">Expired</span>';
                                } elseif ($currentDate->diff($ExpirationDate)->days <= $expiration_threshold) {
                                    $status = '<span class="badge bg-warning text-dark">Expiring Soon</span>';
                                } else {
                                    $status = '<span class="badge bg-success">Available</span>';
                                }
                            ?>
                                <tr>
                                    <th scope="row"><?php echo $cnt; ?></th>
                                    <td><?php echo htmlspecialchars($row['ProductCode']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td><?php echo htmlspecialchars($row['unit']); ?></td>
                                    <td>
                                        <?php
                                        if ($row['Quantity'] < 10) {
                                            echo '<span class="badge bg-danger">Low Stock</span>';
                                        } else {
                                            echo htmlspecialchars($row['Quantity']);
                                        }
                                        ?>
                                    </td>
                                    <td>₱<?php echo number_format($row['price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['TypeName']); ?></td>
                                    <td><?php echo $ExpirationDate ? $ExpirationDate->format('Y-m-d') : ''; ?></td>
                                    <td><?php echo $status; ?></td>
                                </tr>
                            <?php $cnt++; } ?>
                            <?php if (mysqli_num_rows($result) == 0) { ?>
                                <tr>
                                    <td colspan="10" class="text-center">No records found.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <div style="display: flex; justify-content: center;">
                    <button onclick="printPage()" class="btn btn-secondary">Print Page</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.js"></script>
</body>

</html>
