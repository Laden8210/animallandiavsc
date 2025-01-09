<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Manila'); // Set timezone to Manila
include('includes/dbconnection.php');

// Redirect to logout if user is not authenticated
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

if (isset($_POST['submit'])) {

    // Collect form data
    $name = isset($_POST['Name']) ? trim($_POST['Name']) : '';
    $address = isset($_POST['Address']) ? trim($_POST['Address']) : '';
    $email = isset($_POST['Email']) ? trim($_POST['Email']) : '';
    $contactNumber = isset($_POST['ContactNumber']) ? trim($_POST['ContactNumber']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($username) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Proceed with inserting client and transaction
        mysqli_begin_transaction($con);
        try {
            // Insert new client
            $insertClientQuery = "INSERT INTO tblclients (Name, Address, Email, ContactNumber, gender, username, password, CreationDate, UpdationDate) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            if ($stmt_client = $con->prepare($insertClientQuery)) {
                // Hash the password before storing
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt_client->bind_param("sssssss", $name, $address, $email, $contactNumber, $gender, $username, $hashedPassword);
                if (!$stmt_client->execute()) {
                    throw new Exception('Error inserting client: ' . $stmt_client->error);
                }
                // Get the inserted client_id
                $clientID = $stmt_client->insert_id;
                $stmt_client->close();
            } else {
                throw new Exception('Error preparing client insert: ' . $con->error);
            }

            // Generate a unique transaction code
            $transid = 'TRX' . mt_rand(100000000, 999999999);

            // Collect selected services and products
            $sids = $_POST['sids'] ?? [];
            $pids = $_POST['pids'] ?? [];
            $quantities = $_POST['quantities'] ?? [];

            // Group products by ProductID and sum their quantities
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

            // Prepare transactions array
            $transactions = [];
            foreach ($sids as $svid) {
                $transactions[] = ['type' => 'service', 'id' => $svid];
            }
            foreach ($groupedProducts as $pvid => $qty) {
                $transactions[] = ['type' => 'product', 'id' => $pvid, 'Qty' => $qty];
            }

            // Validate all products for stock
            foreach ($transactions as $transaction) {
                if ($transaction['type'] === 'product') {
                    $id = intval($transaction['id']);
                    $qty = intval($transaction['Qty']);

                    // Fetch total available quantity for the product
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

            // Proceed with inserting transactions
            foreach ($transactions as $transaction) {
                $type = $transaction['type'];
                $id = intval($transaction['id']);
                $qty = ($type === 'product') ? intval($transaction['Qty']) : 1;
                $column = ($type === 'service') ? 'service_id' : 'ProductID';
                $status = 'pending'; // Default status, adjust as needed

                // Insert into tbltransaction
                $query = "INSERT INTO tbltransaction (client_id, $column, Trans_Code, Qty, status, Transaction_Date) VALUES (?, ?, ?, ?, ?, NOW())";
                if ($stmt = $con->prepare($query)) {
                    $stmt->bind_param("isssd", $clientID, $id, $transid, $qty, $status);

                    if (!$stmt->execute()) {
                        throw new Exception('Error inserting transaction: ' . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception('Error preparing transaction insert: ' . $con->error);
                }

                // For products, update inventory
                if ($type === 'product') {
                    // Fetch inventory entries ordered by ExpirationDate ASC
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
                                // Deduct entire availableQty and continue
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
            $success = 'Transaction created successfully. Transaction number is <strong>' . htmlspecialchars($transid, ENT_QUOTES, 'UTF-8') . '</strong>. Redirecting to Transaction History...';
            // Redirect after 3 seconds
            echo "<script>setTimeout(() => { window.location.href = 'transaction.php'; }, 3000);</script>";
        } catch (Exception $e) {
            // Rollback the transaction on error
            mysqli_rollback($con);
            $error = $e->getMessage();
        }
    }
}
    // Fetch available products
    $products = [];
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
        $error = 'Error fetching products: ' . mysqli_error($con);
    }

    // Fetch available services
    $services = [];
    $serviceResult = mysqli_query($con, "SELECT service_id, ServiceName, Cost FROM tblservices WHERE status != 'Not Available'");
    if ($serviceResult) {
        while ($row = mysqli_fetch_assoc($serviceResult)) {
            $services[] = $row;
        }
    } else {
        $error = 'Error fetching services: ' . mysqli_error($con);
    }
?>
<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALVSC || Add Transaction</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Custom CSS -->
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet"
        type='text/css'>
    <!-- Animate CSS -->
    <link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
    <link href="css/custom.css" rel="stylesheet">
</head>

<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>
        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables">
                    <!-- Display Success or Error Messages -->
                    <?php
                    if (isset($success)) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                ' . $success . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
                    }

                    if (isset($error)) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                ' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
                    }
                    ?>

                    <h4>Add Transaction:</h4>
                    <form method="post">
                        <!-- Client Information Section -->
                        <div class="form-section">
                            <h5>Client Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="Name" class="form-label">Name<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="Name" name="Name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="Address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="Address" name="Address">
                                </div>
                                <div class="col-md-6">
                                    <label for="Email" class="form-label">Email<span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="Email" name="Email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="ContactNumber" class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" id="ContactNumber" name="ContactNumber">
                                </div>
                                <div class="col-md-4">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="username" class="form-label">Username<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="password" class="form-label">Password<span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="form-text text-muted">Use at least 8 characters.</small>
                                </div>
                            </div>
                        </div>

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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <!-- Custom JavaScript for Enhancements -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle product checkbox change to enable/disable quantity input
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            productCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    const quantityInput = this.closest('tr').querySelector('.product-quantity');
                    if (this.checked) {
                        quantityInput.disabled = false;
                        quantityInput.focus();
                    } else {
                        quantityInput.disabled = true;
                        quantityInput.value = '';
                    }
                });
            });

            // Form validation before submission
            const form = document.querySelector('form');
            form.addEventListener('submit', function (e) {
                const selectedProducts = document.querySelectorAll('input[name="pids[]"]:checked').length;
                const selectedServices = document.querySelectorAll('input[name="sids[]"]:checked').length;

                if (selectedProducts === 0 && selectedServices === 0) {
                    alert('Please select at least one product or service.');
                    e.preventDefault();
                    return false;
                }

                // Validate quantities for selected products
                let valid = true;
                document.querySelectorAll('input[name="pids[]"]:checked').forEach(function (checkbox) {
                    const productId = checkbox.value;
                    const quantityInput = document.querySelector(`input[name="quantities[${productId}]"]`);
                    const quantity = parseInt(quantityInput.value, 10);
                    const maxQty = parseInt(quantityInput.getAttribute('max'), 10);

                    if (isNaN(quantity) || quantity < 1) {
                        alert('Please enter a valid quantity for selected products.');
                        valid = false;
                        return false;
                    }

                    if (quantity > maxQty) {
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
