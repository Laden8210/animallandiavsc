<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');


if (empty($_SESSION['id'])) {
    header('location:logout.php');
    exit();
}

if (isset($_POST['submit'])) {

    $uid = isset($_GET['addid']) ? intval($_GET['addid']) : 0;

    if ($uid > 0) {

        $transid = 'TRX' . mt_rand(100000000, 999999999);


        $sids = $_POST['sids'] ?? [];
        $pids = $_POST['pids'] ?? [];
        $quantities = $_POST['quantities'] ?? [];


        $groupedProducts = [];
        foreach ($pids as $pvid) {
            if (isset($quantities[$pvid])) {
                $qty = intval($quantities[$pvid]);
                if ($qty > 0) {
                    if (isset($groupedProducts[$pvid])) {
                        $groupedProducts[$pvid] += $qty;
                    } else {
                        $groupedProducts[$pvid] = $qty;
                    }
                }
            }
        }


        $transactions = [];
        foreach ($sids as $svid) {
            $transactions[] = ['type' => 'service', 'id' => $svid];
        }
        foreach ($groupedProducts as $pvid => $qty) {
            $transactions[] = ['type' => 'product', 'id' => $pvid, 'Qty' => $qty];
        }

        mysqli_begin_transaction($con);
        try {

            foreach ($transactions as $transaction) {
                if ($transaction['type'] === 'product') {
                    $id = intval($transaction['id']);
                    $qty = intval($transaction['Qty']);

  
                    $inventoryQuery = "SELECT SUM(Quantity) as TotalQuantity FROM tblinventory WHERE ProductID = ? AND (ExpirationDate >= CURDATE() OR ExpirationDate IS NULL)";
                    if ($stmt_inv = $con->prepare($inventoryQuery)) {
                        $stmt_inv->bind_param("i", $id);
                        $stmt_inv->execute();
                        $stmt_inv->bind_result($totalAvailableQty);
                        $stmt_inv->fetch();
                        $stmt_inv->close();

                        $totalAvailableQty = $totalAvailableQty ?? 0;

                        if ($qty > $totalAvailableQty) {
                            throw new Exception('Product ID ' . $id . ' has insufficient stock. Available: ' . $totalAvailableQty);
                        }
                    } else {
                        throw new Exception('Error preparing inventory check: ' . $con->error);
                    }
                }
            }

 
            foreach ($transactions as $transaction) {
                $type = $transaction['type'];
                $id = intval($transaction['id']);
                $qty = ($type === 'product') ? intval($transaction['Qty']) : 1;
                $column = ($type === 'service') ? 'service_id' : 'ProductID';
                $status = 'pending';


                $query = "INSERT INTO tbltransaction (client_id, $column, Trans_Code, Qty, status, Transaction_Date) VALUES (?, ?, ?, ?, ?, NOW())";
                if ($stmt = $con->prepare($query)) {
                    $stmt->bind_param("isssd", $uid, $id, $transid, $qty, $status);

                    echo $transid;

                    if (!$stmt->execute()) {
                        throw new Exception('Error inserting transaction: ' . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception('Error preparing transaction insert: ' . $con->error);
                }

    
                if ($type === 'product') {
       
                    $inventoryFetchQuery = "SELECT InventoryID, Quantity FROM tblinventory WHERE ProductID = ? AND (ExpirationDate >= CURDATE() OR ExpirationDate IS NULL) ORDER BY ExpirationDate ASC";
                    if ($stmt_fetch = $con->prepare($inventoryFetchQuery)) {
                        $stmt_fetch->bind_param("i", $id);
                        $stmt_fetch->execute();
                        $result_fetch = $stmt_fetch->get_result();

                        while ($row_fetch = $result_fetch->fetch_assoc()) {
                            if ($qty <= 0) break;

                            $inventoryId = $row_fetch['InventoryID'];
                            $availableQty = $row_fetch['Quantity'];

                            if ($availableQty >= $qty) {
                                // Update this inventory entry
                                $updateInventoryQuery = "UPDATE tblinventory SET Quantity = Quantity - ? WHERE InventoryID = ?";
                                if ($stmt_upd = $con->prepare($updateInventoryQuery)) {
                                    $stmt_upd->bind_param("ii", $qty, $inventoryId);
                                    if (!$stmt_upd->execute()) {
                                        throw new Exception('Error updating inventory: ' . $stmt_upd->error);
                                    }
                                    $stmt_upd->close();
                                    $qty = 0;
                                } else {
                                    throw new Exception('Error preparing inventory update: ' . $con->error);
                                }
                            } else {
               
                                $updateInventoryQuery = "UPDATE tblinventory SET Quantity = 0 WHERE InventoryID = ?";
                                if ($stmt_upd = $con->prepare($updateInventoryQuery)) {
                                    $stmt_upd->bind_param("i", $inventoryId);
                                    if (!$stmt_upd->execute()) {
                                        throw new Exception('Error updating inventory: ' . $stmt_upd->error);
                                    }
                                    $stmt_upd->close();
                                    $qty -= $availableQty;
                                } else {
                                    throw new Exception('Error preparing inventory update: ' . $con->error);
                                }
                            }
                        }

                        $stmt_fetch->close();

                        if ($qty > 0) {
                            throw new Exception('Product ID ' . $id . ' has insufficient stock during inventory update.');
                        }
                    } else {
                        throw new Exception('Error preparing inventory fetch: ' . $con->error);
                    }
                }
            }

            // Commit the transaction
            mysqli_commit($con);
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    Transaction created successfully. Transaction number is <strong>' . htmlspecialchars($transid, ENT_QUOTES, 'UTF-8') . '</strong>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
             echo "<script>setTimeout(() => { window.location.href = 'transaction.php'; }, 3000);</script>";
        } catch (Exception $e) {
    
            mysqli_rollback($con);
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                  </div>';
        }
    } else {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                Error: Invalid request. User ID not found.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}


$products = [];
$services = [];


$productQuery = "SELECT p.ProductID, p.ProductName, p.ProductCode, p.price, 
                SUM(i.Quantity) AS TotalQuantity, 
                MIN(i.ExpirationDate) AS EarliestExpiration
                FROM tblproducts p
                LEFT JOIN tblinventory i ON p.ProductID = i.ProductID
                GROUP BY p.ProductID, p.ProductName, p.ProductCode, p.price";
$productResult = mysqli_query($con, $productQuery);
if ($productResult) {
    while ($row = mysqli_fetch_assoc($productResult)) {
        $products[] = [
            'ProductID' => $row['ProductID'],
            'ProductName' => $row['ProductName'],
            'ProductCode' => $row['ProductCode'],
            'price' => $row['price'],
            'TotalQuantity' => $row['TotalQuantity'] ?? 0,
            'EarliestExpiration' => $row['EarliestExpiration'] ? htmlspecialchars($row['EarliestExpiration'], ENT_QUOTES, 'UTF-8') : 'No Expiration'
        ];
    }
} else {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            Error fetching products: ' . htmlspecialchars(mysqli_error($con), ENT_QUOTES, 'UTF-8') . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}


$serviceResult = mysqli_query($con, "SELECT service_id, ServiceName, Cost FROM tblservices WHERE status != 'Not Available'");
if ($serviceResult) {
    while ($row = mysqli_fetch_assoc($serviceResult)) {
        $services[] = $row;
    }
} else {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            Error fetching services: ' . htmlspecialchars(mysqli_error($con), ENT_QUOTES, 'UTF-8') . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>';
}


?>

<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALVSC || Add Transaction</title>
    <!-- CSS Links -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet"
        type='text/css'>
    <link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
    <link href="css/custom.css" rel="stylesheet">

    <!-- JavaScript Links -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"
        integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script src="js/modernizr.custom.js"></script>
    <script src="js/wow.min.js"></script>
    <script src="js/metisMenu.min.js"></script>
    <script src="js/custom.js"></script>

    <style>
        body {
            background-color: #f4f7fa;
            font-family: 'Roboto Flex', sans-serif;
        }

        .main-content {
            padding: 20px;
        }

        .tables h4 {
            margin-bottom: 20px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .table th,
        .table td {
            vertical-align: middle !important;
        }

        /* Additional Styles */
        .badge-custom {
            background-color: #17a2b8;
            color: white;
            border-radius: 5px;
            padding: 5px 10px;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }

        .alert-custom {
            margin-top: 20px;
        }

        /* Disable number input spinner */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
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
                    <!-- Display Alerts -->
                    <?php
                    // Alerts are already handled in PHP backend
                    ?>

                    <h4>Add Transaction:</h4>
                    <form method="post">
                        <!-- Products Section -->
                        <div class="form-section">
                            <h5>Products</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No.</th>
                                            <th>Product Code</th>
                                            <th>Product Name</th>
                                            <th>Price</th>
                                            <th>Available Quantity</th>
                                            <th>Expiration Date</th>
                                            <th>Select</th>
                                            <th>Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (!empty($products)) {
                                            $cnt = 1;
                                            foreach ($products as $row) {
                                                $availableQty = intval($row['TotalQuantity']);
                                                $isOutOfStock = ($availableQty <= 0);
                                                $expiration = $row['EarliestExpiration'];

                                                echo "<tr>
                                                        <th scope='row'>" . $cnt . "</th>
                                                        <td>" . htmlspecialchars($row['ProductCode'], ENT_QUOTES, 'UTF-8') . "</td>
                                                        <td>" . htmlspecialchars($row['ProductName'], ENT_QUOTES, 'UTF-8') . "</td>
                                                        <td>₱" . htmlspecialchars(number_format($row['price'], 2), ENT_QUOTES, 'UTF-8') . "</td>
                                                        <td>" . htmlspecialchars($availableQty, ENT_QUOTES, 'UTF-8') . "</td>
                                                        <td>" . $expiration . "</td>
                                                        <td>
                                                            <input type='checkbox' class='product-checkbox' name='pids[]' value='" . htmlspecialchars($row['ProductID'], ENT_QUOTES, 'UTF-8') . "' " . ($isOutOfStock ? "disabled" : "") . ">
                                                        </td>
                                                        <td>";

                                                if ($isOutOfStock) {
                                                    echo "<input type='text' class='form-control' value='Out of Stock' readonly>";
                                                } else {
                                                    echo "<input type='number' class='form-control product-quantity' name='quantities[" . htmlspecialchars($row['ProductID'], ENT_QUOTES, 'UTF-8') . "]' min='1' max='" . htmlspecialchars($availableQty, ENT_QUOTES, 'UTF-8') . "' step='1' placeholder='Enter quantity' disabled required>";
                                                }

                                                echo "</td>
                                                      </tr>";
                                                $cnt++;
                                            }
                                        } else {
                                            echo "<tr><td colspan='8' class='text-center'>No products available.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Services Section -->
                        <div class="form-section">
                            <h5>Services</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No.</th>
                                            <th>Service Name</th>
                                            <th>Cost</th>
                                            <th>Select</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (!empty($services)) {
                                            $cnt = 1;
                                            foreach ($services as $service) {
                                                echo "<tr>
                                                        <th scope='row'>" . $cnt . "</th>
                                                        <td>" . htmlspecialchars($service['ServiceName'], ENT_QUOTES, 'UTF-8') . "</td>
                                                        <td>₱" . htmlspecialchars(number_format($service['Cost'], 2), ENT_QUOTES, 'UTF-8') . "</td>
                                                        <td>
                                                            <input type='checkbox' name='sids[]' value='" . htmlspecialchars($service['service_id'], ENT_QUOTES, 'UTF-8') . "'>
                                                        </td>
                                                      </tr>";
                                                $cnt++;
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center'>No services available.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-group text-end">
                            <button type="submit" name="submit" class="btn btn-primary">Submit Transaction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Optional JavaScript for Enhancements -->
    <script>
        $(document).ready(function () {
            // Enable/Disable quantity input based on product selection
            $('.product-checkbox').change(function () {
                var checkbox = $(this);
                var quantityInput = checkbox.closest('tr').find('.product-quantity');
                var outOfStockInput = checkbox.closest('tr').find('input[type="text"]');

                if (checkbox.is(':checked')) {
                    quantityInput.prop('disabled', false);
                    quantityInput.focus();
                    outOfStockInput.val('');
                } else {
                    quantityInput.prop('disabled', true);
                    quantityInput.val('');
                }
            });

            // Form validation before submission
            $('form').on('submit', function (e) {
                var selectedProducts = $('input[name="pids[]"]:checked').length;
                var selectedServices = $('input[name="sids[]"]:checked').length;

                if (selectedProducts === 0 && selectedServices === 0) {
                    alert('Please select at least one product or service.');
                    e.preventDefault();
                    return false;
                }

                // Validate quantities for selected products
                var valid = true;
                $('input[name="pids[]"]:checked').each(function () {
                    var productId = $(this).val();
                    var quantity = $('input[name="quantities[' + productId + ']"]').val();
                    var maxQty = $('input[name="quantities[' + productId + ']"]').attr('max');

                    if (!quantity || quantity < 1) {
                        alert('Please enter a valid quantity for selected products.');
                        valid = false;
                        return false;
                    }

                    if (parseInt(quantity) > parseInt(maxQty)) {
                        alert('Entered quantity for a product exceeds available stock.');
                        valid = false;
                        return false;
                    }
                });

                if (!valid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>

</html>
