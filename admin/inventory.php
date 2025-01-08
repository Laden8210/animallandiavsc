<?php
session_start();
ini_set('display_errors', 1);
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

$product_types_result = mysqli_query($con, "SELECT * FROM tblproduct_types");
$product_types = [];
while ($row = mysqli_fetch_assoc($product_types_result)) {
    $product_types[] = $row;
}

if (isset($_POST['update_product'])) {
    $Quantity = intval($_POST['Quantity']);
    $unit = mysqli_real_escape_string($con, $_POST['unit']);
    $ProductID = intval($_POST['ProductID']);
    $ProductName = mysqli_real_escape_string($con, $_POST['ProductName']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $price = floatval($_POST['price']);
    $ProductTypeID = intval($_POST['ProductTypeID']);
    $ExpirationDate = empty($_POST['ExpirationDate']) ? null : $_POST['ExpirationDate'];

    if ($Quantity < 0) {
        echo "<script>alert('Quantity cannot be negative.'); window.location.href='inventory.php';</script>";
        exit();
    }

    $prod_image = $_FILES['prod_image']['name'];
    $target_dir = "product_image/";
    if ($prod_image) {
        $target_file = $target_dir . basename($prod_image);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png'];

        if (in_array($imageFileType, $allowed_types)) {
            if (!move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file)) {
                echo "<script>alert('Error uploading file. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Invalid image format. Allowed formats: JPG, JPEG, PNG.'); window.location.href='inventory.php';</script>";
            exit();
        }
    }

    $stmt = $con->prepare($prod_image
        ? "UPDATE tblproducts SET prod_image=?, ProductName=?, description=?, price=?, ProductTypeID=?, ExpirationDate=? WHERE ProductID=?"
        : "UPDATE tblproducts SET ProductName=?, description=?, price=?, ProductTypeID=?, ExpirationDate=? WHERE ProductID=?");

    $prod_image
        ? $stmt->bind_param("sssdssi", $prod_image, $ProductName, $description, $price, $ProductTypeID, $ExpirationDate, $ProductID)
        : $stmt->bind_param("ssdssi", $ProductName, $description, $price, $ProductTypeID, $ExpirationDate, $ProductID);

    if ($stmt->execute()) {

        $stmt_inventory = $con->prepare("UPDATE tblinventory SET Quantity=?, unit=? WHERE ProductID=?");
        $stmt_inventory->bind_param("isi", $Quantity, $unit, $ProductID);
        $stmt_inventory->execute();
        $stmt_inventory->close();
        echo "<script>alert('Product updated successfully.');</script>";
    } else {
        echo "<script>alert('Something went wrong. Please try again.');</script>";
    }
    $stmt->close();
    echo "<script>window.location.href='inventory.php'</script>";
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
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet" type='text/css'>
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
                    <h2>Inventory: </h2>

                    <div class="d-flex justify-content-between align-content-center">


                        <form class="form-inline" method="GET" style="margin-bottom: 10px;">

                            <div class=" grid d-flex justify-content-start gap-2">
                                <div class="form-group">
                                    <input type="search" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <select name="ProductTypeID" class="form-control">
                                        <option value="">All Types</option>
                                        <?php foreach ($product_types as $type): ?>
                                            <option value="<?php echo $type['ProductTypeID']; ?>" <?php echo (isset($_GET['ProductTypeID']) && $_GET['ProductTypeID'] == $type['ProductTypeID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['TypeName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>

                                    <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Filter</button>
                                </div>


                            </div>


                        </form>

                        <div class="pull-right">

                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">Add New Product</button>
                        </div>
                    </div>

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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $expiration_threshold = 7;
                            $search_query = $con->real_escape_string($_GET['search'] ?? '');
                            $product_type_filter = intval($_GET['ProductTypeID'] ?? 0);

                            $query = "
                        SELECT tblproducts.*, tblinventory.Quantity, tblinventory.ExpirationDate, tblinventory.unit, tblproduct_types.TypeName 
                        FROM tblproducts
                        LEFT JOIN tblinventory ON tblproducts.ProductID = tblinventory.ProductID 
                        LEFT JOIN tblproduct_types ON tblproducts.ProductTypeID = tblproduct_types.ProductTypeID 
                        WHERE (tblproducts.ProductName LIKE '%$search_query%' 
                        OR tblproducts.ProductCode LIKE '%$search_query%')";

                            if ($product_type_filter > 0) {
                                $query .= " AND tblproducts.ProductTypeID = $product_type_filter";
                            }

                            $query .= " ORDER BY tblproducts.ProductID DESC";



                            $result = mysqli_query($con, $query);
                            $cnt = 1;

                            while ($row = mysqli_fetch_array($result)) {
                                $expirationDate = new DateTime($row['ExpirationDate']);
                                $currentDate = new DateTime();
                                $status = '';

                                if (empty($row['ExpirationDate'])) {
                                    $status = '<span class="badge" style="background-color: gray; color: white;"></span>';
                                } else if ($currentDate > $expirationDate) {
                                    $status = '<span class="badge" style="background-color: red; color: white;">Expired</span>';
                                } elseif ($currentDate->diff($expirationDate)->days <= $expiration_threshold) {
                                    $status = '<span class="badge" style="background-color: orange; color: white;">Expiring Soon</span>';
                                } else {
                                    $status = '<span class="badge" style="background-color: #8bc34a; color: white;">Available</span>';
                                }
                            ?>
                                <tr>
                                    <th scope="row"><?php echo $cnt; ?></th>
                                    <td><?php echo htmlspecialchars($row['ProductCode']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td><?php echo htmlspecialchars($row['unit']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Quantity']); ?></td>
                                    <td>₱<?php echo htmlspecialchars($row['price']); ?></td>
                                    <td><?php echo htmlspecialchars($row['TypeName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['ExpirationDate']); ?></td>
                                    <td><?php echo $status; ?></td>
                                    <td>
                                        <a href="#" class="btn btn-primary btn-xs" data-bs-toggle="modal" data-bs-target="#editModal" data-id="<?php echo $row['ProductID']; ?>" data-name="<?php echo $row['ProductName']; ?>" data-description="<?php echo $row['description']; ?>" data-price="<?php echo $row['price']; ?>" data-qty="<?php echo $row['Quantity']; ?>" data-unit="<?php echo $row['unit']; ?>" data-type="<?php echo $row['ProductTypeID']; ?>" data-expirationdate="<?php echo $row['ExpirationDate']; ?>">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php $cnt++;
                            } ?>
                        </tbody>
                    </table>
                    <button class="btn btn-default" onclick="history.back()">Previous</button>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="inventory.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h4 class="modal-title" id="addProductModalLabel">Add Product</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Product Image</label>
                            <input type="file" name="prod_image" class="btn btn-outline-success form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="ProductName" class="control-label">Product Name:</label>
                            <input type="text" class="form-control" id="ProductName" name="ProductName" required>
                        </div>
                        <div class="form-group">
                            <label for="description" class="control-label">Description:</label>
                            <textarea class="form-control" id="description" name="description" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="unit" class="control-label">Unit:</label>
                            <select class="form-control" id="unit" name="unit" required>
                                <option value="pcs">pcs</option>
                                <option value="ml">ml</option>
                                <option value="kg">kg</option>
                                <option value="g">g</option>
                                <option value="l">l</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="Quantity" class="control-label">Quantity:</label>
                            <input type="number" class="form-control" id="Quantity" name="Quantity" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="price" class="control-label">Price:</label>
                            <input type="text" class="form-control" id="price" name="price" required>
                        </div>

                        <div class="form-group">
                            <label for="price" class="control-label">Price:</label>
                            <input type="text" class="form-control" id="price" name="price" required>
                        </div>

                        <div class="form-group">
                            <label for="type" class="col-form-label">Category:</label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-grow-1">
                                    <select name="ProductTypeID" id="ProductTypeID" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($product_types as $type): ?>
                                            <option value="<?php echo $type['ProductTypeID']; ?>" <?php echo (isset($_GET['ProductTypeID']) && $_GET['ProductTypeID'] == $type['ProductTypeID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['TypeName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-info" data-bs-target="#categoryModal" data-bs-toggle="modal">Add Category</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="type" class="col-form-label">Sub Category:</label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-grow-1">
                                    <select name="ProductTypeID" id="ProductTypeID" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($product_types as $type): ?>
                                            <option value="<?php echo $type['ProductTypeID']; ?>" <?php echo (isset($_GET['ProductTypeID']) && $_GET['ProductTypeID'] == $type['ProductTypeID']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['TypeName']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-info">Add Sub Category</button>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="add_product">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="inventory.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="ProductID" name="ProductID">
                        <div class="form-group">
                            <label for="editImage">Product Image</label>
                            <input type="file" class="form-control" id="editImage" name="prod_image">
                            <img id="editImagePreview" src="" alt="Current Image" style="margin-top: 10px; max-height: 100px; display: none;">
                        </div>
                        <div class="form-group">
                            <label for="edit_ProductName">Product Name</label>
                            <input type="text" class="form-control" id="edit_ProductName" name="ProductName" placeholder="Product Name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">Description</label>
                            <input type="text" class="form-control" id="edit_description" name="description" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_price">Price</label>
                            <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_Quantity">Quantity</label>
                            <input type="number" class="form-control" id="edit_Quantity" name="Quantity" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_unit">Unit</label>
                            <select class="form-control" id="edit_unit" name="unit" required>
                                <option value="pcs">pcs</option>
                                <option value="ml">ml</option>
                                <option value="kg">kg</option>
                                <option value="g">g</option>
                                <option value="l">l</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_ProductTypeID">Product Type</label>
                            <select class="form-control" id="edit_ProductTypeID" name="ProductTypeID" required>
                                <?php foreach ($product_types as $type) : ?>
                                    <option value="<?php echo $type['ProductTypeID']; ?>">
                                        <?php echo $type['TypeName']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ExpirationDate" class="control-label">Expiration Date (Optional):</label>
                            <input type="date" class="form-control" id="ExpirationDate" name="ExpirationDate">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        $('#editModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var id = button.data('id');
            var ProductName = button.data('name');
            var description = button.data('description');
            var price = button.data('price');
            var qty = button.data('qty');
            var unit = button.data('unit');
            var productType = button.data('type');
            var expirationDate = button.data('expirationdate');
            var currentImage = button.data('image');

            $('#ProductID').val(id);
            $('#edit_ProductName').val(ProductName);
            $('#edit_description').val(description);
            $('#edit_price').val(price);
            $('#edit_Quantity').val(qty);
            $('#edit_unit').val(unit);
            $('#edit_ProductTypeID').val(productType);
            $('#ExpirationDate').val(expirationDate);


            if (currentImage) {
                $('#editImagePreview').attr('src', 'product_image/' + currentImage).show();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>