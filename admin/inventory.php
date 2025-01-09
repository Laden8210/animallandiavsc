<?php
session_start();
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

// Define stock thresholds
define('MIN_THRESHOLD', 5);
define('MAX_THRESHOLD', 100);

// Define expiration threshold (in days)
define('EXPIRATION_THRESHOLD', 7);

// Fetch all product types for dropdowns
$product_types_result = mysqli_query($con, "SELECT * FROM tblproduct_types");
$product_types = [];
while ($row = mysqli_fetch_assoc($product_types_result)) {
    $product_types[] = $row;
}

// ------------------------------------------------------
// ADD NEW STOCK (to tblinventory) with date_created
// ------------------------------------------------------
if (isset($_POST['add_product'])) {
    $product   = intval($_POST['product']);
    $Quantity  = intval($_POST['Quantity']);
    $date      = $_POST['date'];

    // Validate Quantity
    if ($Quantity < 0) {
        echo "<script>alert('Quantity cannot be negative.'); window.location.href='inventory.php';</script>";
        exit();
    }

    // Validate Date format (YYYY-MM-DD)
    if (!empty($date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo "<script>alert('Invalid date format. Please use YYYY-MM-DD.'); window.location.href='inventory.php';</script>";
        exit();
    }

    // Fetch current total quantity for the product
    $current_qty_query = "SELECT SUM(IFNULL(Quantity, 0)) as current_qty FROM tblinventory WHERE ProductID = $product";
    $current_qty_result = mysqli_query($con, $current_qty_query);
    $current_qty_row = mysqli_fetch_assoc($current_qty_result);
    $current_qty = (int)$current_qty_row['current_qty'];

    // Check if adding the new quantity exceeds max_threshold
    if (($current_qty + $Quantity) > MAX_THRESHOLD) {
        echo "<script>alert('Cannot add stock. Total quantity would exceed the maximum threshold of " . MAX_THRESHOLD . ".'); window.location.href='inventory.php';</script>";
        exit();
    }

    // Insert into tblinventory
    $stmt = $con->prepare("INSERT INTO tblinventory (ProductID, Quantity,OrderQuantity , ExpirationDate, Date_Created) 
                           VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiis", $product, $Quantity, $Quantity, $date);
    if ($stmt->execute()) {
        echo "<script>alert('Stock added successfully.');</script>";
    } else {
        echo "<script>alert('Something went wrong while adding stock.');</script>";
    }
    $stmt->close();
    echo "<script>window.location.href='inventory.php'</script>";
    exit();
}

// ------------------------------------------------------
// UPDATE / EDIT (only Quantity & Expiration Date)
// ------------------------------------------------------
if (isset($_POST['update_product'])) {
    $ProductID        = intval($_POST['ProductID']);
    $newQuantity      = intval($_POST['Quantity']);
    $ExpirationDate    = $_POST['ExpirationDate'];

    // Validate date format if not empty
    if (!empty($ExpirationDate) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ExpirationDate)) {
        echo "<script>alert('Invalid Expiration Date format.'); window.location.href='inventory.php';</script>";
        exit();
    }

    // Validate Quantity against thresholds
    if ($newQuantity < MIN_THRESHOLD) {
        echo "<script>alert('Quantity cannot be less than the minimum threshold of " . MIN_THRESHOLD . ".'); window.location.href='inventory.php';</script>";
        exit();
    }

    if ($newQuantity > MAX_THRESHOLD) {
        echo "<script>alert('Quantity cannot exceed the maximum threshold of " . MAX_THRESHOLD . ".'); window.location.href='inventory.php';</script>";
        exit();
    }

    // Update inventory
    // Note: This simplistic approach updates all inventory records for the product.
    // If you have multiple batches, consider handling each batch separately.
    $update_inventory_query = "
        UPDATE tblinventory
           SET Quantity       = ?,
               ExpirationDate = ?
         WHERE ProductID      = ?
    ";
    $stmt_inv = $con->prepare($update_inventory_query);
    $stmt_inv->bind_param("isi", $newQuantity, $ExpirationDate, $ProductID);

    if (!$stmt_inv->execute()) {
        echo "<script>alert('Error updating inventory.'); window.location.href='inventory.php';</script>";
        exit();
    }
    $stmt_inv->close();

    echo "<script>alert('Inventory updated successfully.'); window.location.href='inventory.php';</script>";
    exit();
}
?>

<!DOCTYPE HTML>
<html>

<head>
    <title>ALVSC || Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/modernizr.custom.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap"
        rel="stylesheet" type='text/css'>
    <link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
    <script src="js/wow.min.js"></script>
    <script>
        new WOW().init();
    </script>
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
                    <h2>Inventory:</h2>

                    <div class="d-flex justify-content-between align-content-center">

                        <!-- FILTER FORM -->
                        <form class="form-inline" method="GET" style="margin-bottom: 10px;">
                            <div class="grid d-flex justify-content-start gap-2">
                                <!-- Search Input -->
                                <div class="form-group">
                                    <input type="search" name="search" class="form-control" placeholder="Search products..."
                                        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                </div>
                                <!-- Product Type Dropdown -->
                                <div class="form-group">
                                    <select name="ProductTypeID" class="form-control" id="product">
                                        <option value="">All Types</option>
                                        <?php foreach ($product_types as $type): ?>
                                            <option value="<?php echo $type['ProductTypeID']; ?>"
                                                <?php echo (isset($_GET['ProductTypeID']) && $_GET['ProductTypeID'] == $type['ProductTypeID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['TypeName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- Subcategory Dropdown -->
                                <div class="form-group">
                                    <select name="SubProductID" id="SubProduct" class="form-control">
                                        <option value="">All Sub Type</option>
                                    </select>
                                </div>
                                <!-- Submit Button -->
                                <div>
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fa fa-search"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- AJAX to load subcategories -->
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const productSelect = document.getElementById('product');
                                const subProductSelect = document.getElementById('SubProduct');

                                productSelect.addEventListener('change', function() {
                                    const productTypeId = this.value;
                                    subProductSelect.innerHTML = '<option value="">Loading...</option>';

                                    fetch('controller/fetch_subcategory.php?ProductTypeID=' + productTypeId)
                                        .then(response => response.json())
                                        .then(data => {
                                            subProductSelect.innerHTML = '<option value="">All Sub Type</option>';
                                            data.forEach(function(subCategory) {
                                                subProductSelect.innerHTML += `
                                              <option value="${subCategory.SubCatId}">
                                                  ${subCategory.SubCatName}
                                              </option>`;
                                            });
                                        })
                                        .catch(error => {
                                            console.error('Error fetching subcategories:', error);
                                            subProductSelect.innerHTML = '<option value="">Error loading subcategories</option>';
                                        });
                                });
                            });
                        </script>

                        <!-- ADD NEW STOCK BUTTON -->
                        <div class="pull-right">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                Add New Stock
                            </button>
                        </div>
                    </div>

                    <!-- TABLE: INVENTORY LIST (group by ProductID) -->
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Product Code</th>
                                <th>Product Name</th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th>Total Quantity</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Sub Category</th>
                                <th>Earliest Expiration</th>
                                <th>Quantity Status</th>
                                <th>Expiration Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Build filters
                            $search_query           = $con->real_escape_string($_GET['search'] ?? '');
                            $product_type_filter    = intval($_GET['ProductTypeID'] ?? 0);
                            $subproduct_type_filter = intval($_GET['SubProductID'] ?? 0);

                            /*
                      We group by ProductID, summing all inventory's quantity,
                      also get the MIN(ExpirationDate) as earliest expiration.
                    */
                            $query = "
                    SELECT 
                      p.ProductID,
                      p.ProductCode,
                      p.ProductName,
                      p.description,
                      p.price,
                      p.unit,
                      pt.TypeName,
                      sc.SubCatName,
                      SUM(IFNULL(i.Quantity, 0)) as totalQuantity,
                      SUM(IFNULL(i.OrderQuantity, 0)) as totalOrderQuantity,
                      MIN(i.ExpirationDate) as earliestExpiration,
                      SUM(CASE 
                            WHEN i.ExpirationDate < CURDATE() THEN 1 
                            ELSE 0 
                          END) as expired_count,
                      SUM(CASE 
                            WHEN i.ExpirationDate >= CURDATE() 
                                 AND i.ExpirationDate <= DATE_ADD(CURDATE(), INTERVAL " . EXPIRATION_THRESHOLD . " DAY) 
                            THEN 1 
                            ELSE 0 
                          END) as expiring_soon_count
                    FROM tblproducts p
                    LEFT JOIN tblinventory i ON p.ProductID = i.ProductID
                    LEFT JOIN tblproduct_types pt ON p.ProductTypeID = pt.ProductTypeID
                    LEFT JOIN tblsub_category sc ON sc.SubCatId = p.SubCatId
                    WHERE (p.ProductName LIKE '%$search_query%' 
                           OR p.ProductCode LIKE '%$search_query%')
                ";

                            if ($product_type_filter > 0) {
                                $query .= " AND p.ProductTypeID = $product_type_filter ";
                            }
                            if ($subproduct_type_filter > 0) {
                                $query .= " AND p.SubCatId = $subproduct_type_filter ";
                            }

                            // Group by product, order by product desc
                            $query .= "
                    GROUP BY p.ProductID
                    ORDER BY p.ProductID DESC
                ";


                            $result = mysqli_query($con, $query);
                            $cnt = 1;

                            while ($row = mysqli_fetch_assoc($result)) {
                                $productID          = $row['ProductID'];
                                $earliestExpiration = $row['earliestExpiration'];
                                $totalQuantity      = (int)$row['totalQuantity'];
                                $price              = number_format((float)$row['price'], 2, '.', ',');
                                $expirationDateObj  = (!empty($earliestExpiration)) ? new DateTime($earliestExpiration) : null;
                                $currentDate        = new DateTime();
                                $quantity_status    = '';
                                $expiration_status  = '';

                                // Determine Quantity Status
                                if ($totalQuantity <= 0) {
                                    $quantity_status = '<span class="badge bg-secondary">Out of Stock</span>';
                                } elseif ($totalQuantity < MIN_THRESHOLD) {
                                    $quantity_status = '<span class="badge bg-danger">Critical Stock</span>';
                                } elseif ($totalQuantity > MAX_THRESHOLD) {
                                    $quantity_status = '<span class="badge bg-warning text-dark">Exceeds Maximum</span>';
                                } else {
                                    $quantity_status = '<span class="badge bg-success">Available</span>';
                                }

                                if ($row['expired_count'] > 0) {
                                    $expiration_status = '<span class="badge bg-danger">Expired</span>';
                                }
                                elseif ($row['expiring_soon_count'] > 0) {
                                    $expiration_status = '<span class="badge bg-warning text-dark">Expiring Soon</span>';
                                }
                                else {
                                    $expiration_status = '<span class="badge bg-success">Valid</span>';
                                }
                            ?>
                                <tr>
                                    <th scope="row"><?php echo $cnt; ?></th>
                                    <td><?php echo htmlspecialchars($row['ProductCode']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td><?php echo htmlspecialchars($row['unit']); ?></td>
                                    <td><?php echo $totalQuantity; ?></td>
                                    <td>₱<?php echo $price; ?></td>
                                    <td><?php echo htmlspecialchars($row['TypeName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['SubCatName']); ?></td>
                                    <td>
                                        <?php
                                        echo (!empty($earliestExpiration))
                                            ? htmlspecialchars($earliestExpiration)
                                            : 'N/A';
                                        ?>
                                    </td>
                                    <td><?php echo $quantity_status; ?></td>
                                    <td><?php echo $expiration_status; ?></td>
                                    <td>
                                        <!-- EDIT BUTTON (for quantity & expiration) 
                                     Because we grouped by ProductID, if you actually have multiple inventory rows,
                                     you may need a different approach or rely on a separate "batches" approach.
                                -->
                                        <button
                                            class="btn btn-primary btn-xs"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal"
                                            data-id="<?php echo $row['ProductID']; ?>"
                                            data-code="<?php echo $row['ProductCode']; ?>"
                                            data-name="<?php echo htmlspecialchars($row['ProductName']); ?>"
                                            data-qty="<?php echo $row['totalQuantity']; ?>"
                                            data-expirationdate="<?php echo $row['earliestExpiration']; ?>">
                                            Edit
                                        </button>

                                        <!-- VIEW BATCHES BUTTON -->
                                        <button
                                            class="btn btn-info btn-xs"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewBatchesModal"
                                            data-productid="<?php echo $row['ProductID']; ?>"
                                            data-productname="<?php echo htmlspecialchars($row['ProductName']); ?>">
                                            View Batches
                                        </button>
                                    </td>
                                </tr>
                            <?php
                                $cnt++;
                            }
                            ?>
                        </tbody>
                    </table>

                    <button class="btn btn-default" onclick="history.back()">Previous</button>

                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
     MODAL: ADD NEW STOCK
     ========================================== -->
    <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="inventory.php">
                    <div class="modal-header">
                        <h4 class="modal-title" id="addProductModalLabel">Add New Stock</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">

                        <div class="form-group">
                            <label for="unit" class="control-label">Select Product:</label>
                            <select class="form-control" id="unit" name="product" required>
                                <option value="">Select Product</option>
                                <?php
                                $ret = mysqli_query($con, "SELECT * FROM tblproducts");
                                while ($row = mysqli_fetch_array($ret)) {
                                ?>
                                    <option value="<?php echo $row['ProductID']; ?>">
                                        <?php echo htmlspecialchars($row['ProductName']); ?>
                                    </option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="Quantity" class="control-label">Quantity:</label>
                            <input type="number" class="form-control" id="Quantity" name="Quantity" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="date" class="control-label">Expiration Date (Optional):</label>
                            <input type="date" class="form-control" id="date" name="date">
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="add_product">Add Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==========================================
     MODAL: EDIT (ONLY QUANTITY & EXPIRATION)
     ========================================== -->
    <div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- If multiple inventory rows exist per product, you'd normally select which row to update. 
                 For simplicity, we're editing "the earliest" or "grouped" inventory. Adjust as needed. 
            -->
                <form method="POST" action="inventory.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Inventory (Grouped)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <!-- Hidden field for ProductID -->
                        <input type="hidden" id="edit_ProductID" name="ProductID">

                        <!-- READ-ONLY FIELDS (ProductCode, ProductName) -->
                        <div class="mb-2">
                            <label for="edit_ProductCode" class="form-label">Product Code</label>
                            <input type="text" class="form-control" id="edit_ProductCode" name="ProductCode" readonly>
                        </div>
                        <div class="mb-2">
                            <label for="edit_ProductName" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="edit_ProductName" name="ProductName" readonly>
                        </div>

                        <!-- ACTUAL EDITABLE FIELDS (Quantity, Expiration Date) -->
                        <div class="form-group mb-2">
                            <label for="edit_Quantity" class="form-label">Total Quantity</label>
                            <input type="number" class="form-control" id="edit_Quantity" name="Quantity" min="0" required>
                        </div>

                        <div class="form-group mb-2">
                            <label for="edit_ExpirationDate" class="form-label">Earliest Expiration Date (Optional)</label>
                            <input type="date" class="form-control" id="edit_ExpirationDate" name="ExpirationDate">
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==========================================
     MODAL: VIEW BATCHES (All Inventory Rows)
     ========================================== -->
    <div id="viewBatchesModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="viewBatchesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="viewBatchesModalLabel">Inventory Batches</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5 id="viewBatchesProductTitle"></h5>
                    <div id="batchesTableContainer">
                        <!-- Table loaded via AJAX will go here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BOOTSTRAP SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        // EDIT MODAL
        var editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget; // Button that triggered the modal

            var ProductID = button.getAttribute('data-id');
            var ProductCode = button.getAttribute('data-code');
            var ProductName = button.getAttribute('data-name');
            var totalQuantity = button.getAttribute('data-qty');
            var earliestExp = button.getAttribute('data-expirationdate');

            // Fill in read-only fields
            document.getElementById('edit_ProductID').value = ProductID;
            document.getElementById('edit_ProductCode').value = ProductCode;
            document.getElementById('edit_ProductName').value = ProductName;

            // Fill in editable fields
            document.getElementById('edit_Quantity').value = totalQuantity;
            document.getElementById('edit_ExpirationDate').value = earliestExp || '';
        });

        // VIEW BATCHES MODAL
        var viewBatchesModal = document.getElementById('viewBatchesModal');
        viewBatchesModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var productID = button.getAttribute('data-productid');
            var productName = button.getAttribute('data-productname');

            // Set modal title
            document.getElementById('viewBatchesProductTitle').innerText = "All Batches for: " + productName;

            // Load the inventory rows via AJAX
            fetch("controller/view_batches.php?productID=" + productID)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('batchesTableContainer').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading batches:', error);
                    document.getElementById('batchesTableContainer').innerHTML = "<p class='text-danger'>Unable to load batch details.</p>";
                });
        });

        // SUB-CATEGORY AJAX
        document.addEventListener('DOMContentLoaded', function() {
            const productSelect = document.getElementById('product');
            const subProductSelect = document.getElementById('SubProduct');

            productSelect.addEventListener('change', function() {
                const productTypeId = this.value;
                subProductSelect.innerHTML = '<option value="">Loading...</option>';

                fetch('controller/fetch_subcategory.php?ProductTypeID=' + productTypeId)
                    .then(response => response.json())
                    .then(data => {
                        subProductSelect.innerHTML = '<option value="">All Sub Type</option>';
                        data.forEach(function(subCategory) {
                            subProductSelect.innerHTML += `
                            <option value="${subCategory.SubCatId}">
                                ${subCategory.SubCatName}
                            </option>`;
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching subcategories:', error);
                        subProductSelect.innerHTML = '<option value="">Error loading subcategories</option>';
                    });
            });
        });
    </script>

</body>

</html>