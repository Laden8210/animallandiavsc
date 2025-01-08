<?php
session_start();
error_reporting(E_ALL);
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit(); 
}

if (isset($_POST['submit'])) {
    $uid = intval($_GET['addid'] ?? 0); 

    if ($uid > 0) { 
        $transid = mt_rand(100000000, 999999999);
        
        $sids = $_POST['sids'] ?? [];
        $pids = $_POST['pids'] ?? [];
        $quantities = $_POST['quantities'] ?? []; 

        $transactions = [];
        foreach ($sids as $svid) {
            $transactions[] = ['type' => 'service', 'id' => $svid];
        }
        foreach ($pids as $pvid) {
            $qty = isset($quantities[$pvid]) ? $quantities[$pvid] : 1; 
            $transactions[] = ['type' => 'product', 'id' => $pvid, 'Qty' => $qty];
        }

        mysqli_begin_transaction($con);
        try {
            foreach ($transactions as $transaction) {
                $id = $transaction['id'];
                $column = $transaction['type'] == 'service' ? 'service_id' : 'ProductID';
                $qty = $transaction['type'] == 'product' ? $transaction['Qty'] : 1;

                if ($transaction['type'] == 'product') {
                    $check_stock_query = "SELECT Quantity FROM tblinventory WHERE ProductID = '$id'";
                    $result = mysqli_query($con, $check_stock_query);
                    $product = mysqli_fetch_assoc($result);
                    if ($product['Quantity'] < $qty) {
                        throw new Exception('Product quantity is insufficient or out of stock.');
                    }
                }

                $query = "INSERT INTO tbltransaction (client_id, $column, Trans_Code, Qty) VALUES ('$uid', '$id', '$transid', '$qty')";
                $ret = mysqli_query($con, $query);
                
                if (!$ret) {
                    throw new Exception('Error: Unable to create transaction. ' . mysqli_error($con));
                }

                if ($transaction['type'] == 'product') {
                    $stmt_inventory = $con->prepare("UPDATE tblinventory SET Quantity = Quantity - ? WHERE ProductID = ?");
                    $stmt_inventory->bind_param("ii", $qty, $id);

                    if (!$stmt_inventory->execute()) {
                        throw new Exception('Error updating inventory. ' . $stmt_inventory->error);
                    }

                    $stmt_inventory->close();
                }
            }

            mysqli_commit($con);
            echo '<script>alert("Transaction created successfully. Transaction number is ' . $transid . '");</script>';
            echo "<script>window.location.href ='transaction.php';</script>";
        } catch (Exception $e) {
            mysqli_rollback($con);
            echo '<script>
                alert("' . $e->getMessage() . '");
                window.history.back();
            </script>';
            exit(); 
        }
    } else {
        echo '<script>alert("Error: Invalid request. User ID not found.");</script>';
    }
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>ALVSC || Add Transaction</title>
    <script type="application/x-javascript">
        addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false);
        function hideURLbar() { window.scrollTo(0,1); }
    </script>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/modernizr.custom.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet" type='text/css'>
    <link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
    <script src="js/wow.min.js"></script>
    <script> new WOW().init(); </script>
    <script src="js/metisMenu.min.js"></script>
    <script src="js/custom.js"></script>
    <link href="css/custom.css" rel="stylesheet">
</head>
<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>
        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables">
                        <h4>Add Transaction:</h4>
                        <form method="post">
                        

<h5>Products</h5>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>No.</th>
            <th>Product Name</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Expiration Date</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $current_date = date('Y-m-d');
        $ret = mysqli_query($con, "SELECT * FROM tblproducts WHERE ExpirationDate >= '$current_date' OR ExpirationDate IS NULL");
        $cnt = 1;
        while ($row = mysqli_fetch_array($ret)) {
        ?>
        <tr>
            <th scope="row"><?php echo $cnt; ?></th>
            <td><?php echo htmlspecialchars($row['ProductName'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars($row['price'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td>
                <input type="number" name="quantities[<?php echo htmlspecialchars($row['ProductID'], ENT_QUOTES, 'UTF-8'); ?>]" min="1" step="any" placeholder="Enter quantity">
            </td>
            <td><?php echo $row['ExpirationDate'] ? htmlspecialchars($row['ExpirationDate'], ENT_QUOTES, 'UTF-8') : 'No Expiration'; ?></td>
            <td>
                <input type="checkbox" name="pids[]" value="<?php echo htmlspecialchars($row['ProductID'], ENT_QUOTES, 'UTF-8'); ?>">
            </td>
        </tr>
        <?php
            $cnt++;
        }
        ?>
    </tbody>
</table>

                            <tr>
                                <td colspan="5" style="text-align: right;">
                                    <button type="submit" name="submit" class="btn btn-default">Submit</button>
                                </td>
                            </tr>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
