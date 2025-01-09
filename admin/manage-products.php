<?php
session_start();
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
  header('location:logout.php');
  exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
  $id = intval($_GET['id']);
  if ($id > 0) {
    $stmt = $con->prepare("DELETE FROM tblproducts WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
      echo "<script>alert('Product deleted.');</script>";
    } else {
      echo "<script>alert('Something went wrong. Please try again.');</script>";
    }
    $stmt->close();
  } else {
    echo "<script>alert('Invalid product ID.');</script>";
  }
  echo "<script>window.location.href='manage-products.php'</script>";
  exit();
}


if (isset($_POST['add_product'])) {
  $prod_image = $_FILES['prod_image']['name'];
  $target_dir = "product_image/";
  $target_file = $target_dir . basename($prod_image);

  $ProductName = mysqli_real_escape_string($con, $_POST['ProductName']);
  $description = mysqli_real_escape_string($con, $_POST['description']);
  $price = floatval($_POST['price']);

  $unit = mysqli_real_escape_string($con, $_POST['unit']);
  $ProductTypeID = intval($_POST['ProductTypeID']);
  $SubProductID = intval($_POST['SubProductID']);
  $unit = mysqli_real_escape_string($con, $_POST['unit']);

  $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
  $allowed_types = ['jpg', 'jpeg', 'png'];
  if (in_array($imageFileType, $allowed_types) && move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file)) {
    $ProductCode = strtoupper(substr($ProductName, 0, 3)) . rand(1000, 9999);

    $stmt = $con->prepare("INSERT INTO tblproducts (prod_image, ProductName, description, price, ProductTypeID, ProductCode, SubCatId, unit) VALUES (?, ?, ?,  ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssis", $prod_image, $ProductName, $description, $price, $ProductTypeID, $ProductCode, $SubProductID, $unit);

    if ($stmt->execute()) {
      $ProductID = $stmt->insert_id;
      $stmt_inventory = $con->prepare("INSERT INTO tblinventory (ProductID, Quantity, unit) VALUES (?, ?, ?)");
      $stmt_inventory->bind_param("iis", $ProductID, $Quantity, $unit);
      $stmt_inventory->execute();
      $stmt_inventory->close();
      echo "<script>alert('Product added successfully.');</script>";
    } else {
      echo "<script>alert('Something went wrong. Please try again.');</script>";
    }
    $stmt->close();
  } else {
    echo "<script>alert('Invalid image format. Allowed formats: JPG, JPEG, PNG.');</script>";
  }

  echo "<script>window.location.href='manage-products.php'</script>";
  exit();
}


if (isset($_POST['add_category'])) {
  $category_name = mysqli_real_escape_string($con, $_POST['category_name']);

  $stmt_cat = $con->prepare("INSERT INTO tblproduct_types (TypeName) VALUES(?)");
  $stmt_cat->bind_param("s", $category_name);

  if ($stmt_cat->execute()) {
    echo "<script>alert('Category added successfully.');</script>";
    echo "<script>window.location.href='manage-products.php'</script>";
  } else {
    echo "<script>alert('Something went wrong. Please try again.');</script>";
  }
}



if (isset($_POST['add_sub_category'])) {
  $ProductTypeID = mysqli_real_escape_string($con, $_POST['ProductTypeID']);
  $SubCatName = mysqli_real_escape_string($con, $_POST['SubCatName']);
  $stmt_cat = $con->prepare("INSERT INTO tblsub_category (ProductTypeID, SubCatName) VALUES(?, ?)");
  $stmt_cat->bind_param("is", $ProductTypeID, $SubCatName);

  if ($stmt_cat->execute()) {
    echo "<script>alert('Category added successfully.');</script>";
    echo "<script>window.location.href='manage-products.php'</script>";
  } else {
    echo "<script>alert('Something went wrong. Please try again.');</script>";
  }
}

if (isset($_POST['edit_product'])) {
  $ProductID = intval($_POST['ProductID']);
  $ProductName = mysqli_real_escape_string($con, $_POST['ProductName']);
  $description = mysqli_real_escape_string($con, $_POST['description']);
  $price = floatval($_POST['price']);
  $ProductCode = mysqli_real_escape_string($con, $_POST['ProductCode']);
  $SubCatId = intval($_POST['SubCatId']);
  $unit = mysqli_real_escape_string($con, $_POST['unit']);

  // Handle image upload if a new image is provided
  if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] == 0) {
    $prod_image = $_FILES['prod_image']['name'];
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($prod_image);
    move_uploaded_file($_FILES['prod_image']['tmp_name'], $target_file);
  } else {
    // If no new image is uploaded, keep the existing image
    $prod_image = $_POST['existing_image']; // Assuming you have a hidden field for the existing image
  }

  // Update the product details
  $stmt = $con->prepare("UPDATE tblproducts SET ProductName=?, description=?, price=?, ProductCode=?, ProductTypeID=?, SubCatId=?, unit=?, prod_image=? WHERE ProductID=?");
  $stmt->bind_param("ssdiiissi", $ProductName, $description, $price, $ProductCode, $ProductTypeID, $SubCatId, $unit, $prod_image, $ProductID);

  if ($stmt->execute()) {
    echo "<script>alert('Product updated successfully.');</script>";
  } else {
    echo "<script>alert('Something went wrong. Please try again.');</script>";
  }
  $stmt->close();
  echo "<script>window.location.href='manage-products.php'</script>";
  exit();
}

$product_types_result = mysqli_query($con, "SELECT * FROM tblproduct_types");
$product_types = [];
while ($row = mysqli_fetch_assoc($product_types_result)) {
  $product_types[] = $row;
}

?>

<!DOCTYPE HTML>
<html>

<head>
  <title>ALVSC || Manage Products</title>
  <script type="application/x-javascript">
    addEventListener("load", function() {
      setTimeout(hideURLbar, 0);
    }, false);

    function hideURLbar() {
      window.scrollTo(0, 1);
    }
  </script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">>
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
          <h4>Manage Products:</h4>
          <form class="form-inline" style="margin-bottom: 10px;">

            <div class="d-flex justify-content-between align-content-center">

              <div class=" grid d-flex justify-content-start gap-2">
                <div class="form-group">
                  <input type="search" name="search" class="form-control" placeholder="Search products...">
                </div>
                <div class="col-1">
                  <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                </div>
              </div>

              <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">Add New Product</button>
              </div>


            </div>

          </form>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>No.</th>
                <th>Product Code</th>
                <th>Image</th>
                <th>Name</th>
                <th>Description</th>
                <th>Unit</th>
                <th>Product Price</th>
                <th>Category</th>
                <th>Sub Category</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php

              if (isset($_GET['search'])) {
                $search_query = $_GET['search'];

                // Use a prepared statement to prevent SQL injection
                $stmt = $con->prepare("
                    SELECT * FROM tblproducts
                    LEFT JOIN tblsub_category ON tblsub_category.SubCatId = tblproducts.SubCatId
                    LEFT JOIN tblproduct_types ON tblproduct_types.ProductTypeID = tblproducts.ProductTypeID
                    WHERE ProductName LIKE ?
                ");

                $like_query = '%' . $search_query . '%';
                $stmt->bind_param('s', $like_query); // 's' specifies the type of the parameter (string)
                $stmt->execute();
                $ret = $stmt->get_result();
                
              } else {
                $ret = $con->query("
                    SELECT * FROM tblproducts
                    LEFT JOIN tblsub_category ON tblsub_category.SubCatId = tblproducts.SubCatId
                    LEFT JOIN tblproduct_types ON tblproduct_types.ProductTypeID = tblproducts.ProductTypeID
                    ORDER BY ProductID DESC
                ");
              }


              $cnt = 1;
              while ($row = mysqli_fetch_array($ret)) {

              ?>
                <tr>
                  <th scope="row"><?php echo $cnt; ?></th>
                  <td><?php echo $row['ProductCode']; ?></td>
                  <td style="text-align: center;">
                    <?php
                    $imagePath = 'product_image/' . ($row['prod_image'] ? $row['prod_image'] : 'default.jpg');
                    echo "<img src='$imagePath' height='60' width='90' class='img-thumbnail'>";
                    ?>
                  </td>
                  <td><?php echo $row['ProductName']; ?></td>
                  <td><?php echo $row['description']; ?></td>
                  <td><?php echo $row['unit']; ?></td>
                  <td>₱<?php echo $row['price']; ?></td>
                  <td><?php echo $row['TypeName']; ?></td>
                  <td><?php echo $row['SubCatName']; ?></td>
                  <td>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $row['ProductID']; ?>">Edit</button>
                  </td>
                </tr>
              <?php
                $cnt = $cnt + 1;
              }
              ?>
            </tbody>
          </table>
          <button class="btn btn-default" onclick="history.back()">Previous</button>
        </div>
      </div>
    </div>
  </div>


  <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <form method="POST" action="manage-products.php" enctype="multipart/form-data">
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
              <label for="price" class="control-label">Price:</label>
              <input type="number" class="form-control" id="price" name="price" min="1" required>
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
                  <select name="SubProductID" id="SubProduct" class="form-control" required>
                    <option value="">Select Sub Category</option>
                  </select>
                </div>
                <button type="button" class="btn btn-info" data-bs-target="#subCategoryModal" data-bs-toggle="modal">Add Sub Category</button>
              </div>
            </div>
            <script>
              document.getElementById('ProductTypeID').addEventListener('change', function() {
                var categoryId = this.value;
                var subProductSelect = document.getElementById('SubProduct');
                subProductSelect.innerHTML = '<option value="">Loading...</option>';

                fetch('controller/fetch_subcategory.php?ProductTypeID=' + categoryId)
                  .then(response => response.json())
                  .then(data => {
                    subProductSelect.innerHTML = '<option value="">Select Sub Category</option>'; // Reset options
                    data.forEach(function(subCategory) {
                      subProductSelect.innerHTML += '<option value="' + subCategory.SubCatID + '">' + subCategory.SubCatName + '</option>';
                    });
                  })
                  .catch(error => {
                    console.error('Error fetching subcategories:', error);
                    subProductSelect.innerHTML = '<option value="">Error loading subcategories</option>';
                  });
              });
            </script>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" name="add_product">Add Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div id="categoryModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

        </div>
        <form method="POST" action="manage-products.php">
          <div class="modal-body">
            <div class="form-group">
              <label for="editImage">Category Name</label>
              <input type="text" class="form-control" id="editImage" name="category_name">

            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" name="add_category" class="btn btn-primary">Save</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>


  <div id="subCategoryModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

        </div>
        <form method="POST" action="manage-products.php">
          <div class="modal-body">
            <div class="form-group">
              <label for="editImage">Category Name</label>
              <select name="ProductTypeID" id="ProductTypeID" class="form-control" required>
                <option value="">Select Category</option>
                <?php foreach ($product_types as $type): ?>
                  <option value="<?php echo $type['ProductTypeID']; ?>" <?php echo (isset($_GET['ProductTypeID']) && $_GET['ProductTypeID'] == $type['ProductTypeID']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type['TypeName']); ?>
                  </option>
                <?php endforeach; ?>
              </select>

            </div>
            <div class="form-group">
              <label for="editImage">Sub Category Name</label>
              <input type="text" class="form-control" name="SubCatName">

            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" name="add_sub_category" class="btn btn-primary">Save</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php
  foreach ($ret as $row) {
  ?>
    <div class="modal fade" id="editProductModal<?php echo $row['ProductID']; ?>" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="manage-products.php" enctype="multipart/form-data">
            <div class="modal-header">
              <h4 class="modal-title" id="editProductModalLabel">Edit Product</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="ProductID" value="<?php echo $row['ProductID']; ?>">
              <input type="hidden" name="existing_image" value="<?php echo $row['prod_image']; ?>">
              <div class="form-group">
                <label for="ProductName" class="control-label">Product Name:</label>
                <input type="text" class="form-control" id="ProductName" name="ProductName" value="<?php echo htmlspecialchars($row['ProductName']); ?>" required>
              </div>
              <div class="form-group">
                <label for="description" class="control-label">Description:</label>
                <textarea class="form-control" id="description" name="description" required><?php echo htmlspecialchars($row['description']); ?></textarea>
              </div>
              <div class="form-group">
                <label for="ProductCode" class="control-label">Product Code:</label>
                <input type="text" class="form-control" id="ProductCode" name="ProductCode" value="<?php echo htmlspecialchars($row['ProductCode']); ?>" required>
              </div>

              <div class="form-group">
                <label for="unit" class="control-label">Unit:</label>
                <select class="form-control" id="unit" name="unit" required>
                  <option value="pcs" <?php echo ($row['unit'] == 'pcs') ? 'selected' : ''; ?>>pcs</option>
                  <option value="ml" <?php echo ($row['unit'] == 'ml') ? 'selected' : ''; ?>>ml</option>
                  <option value="kg" <?php echo ($row['unit'] == 'kg') ? 'selected' : ''; ?>>kg</option>
                  <option value="g" <?php echo ($row['unit'] == 'g') ? 'selected' : ''; ?>>g</option>
                  <option value="l" <?php echo ($row['unit'] == 'l') ? 'selected' : ''; ?>>l</option>
                </select>
              </div>
              <div class="form-group">
                <label for="price" class="control-label">Price:</label>
                <input type="number" class="form-control" id="price" name="price" value="<?php echo $row['price']; ?>" min="1" required>
              </div>
              <div class="form-group">
                <label for="prod_image" class="control-label">Product Image:</label>
                <input type="file" class="form-control" id="prod_image" name="prod_image">
                <small class="form-text text-muted">Leave blank to keep the existing image.</small>
              </div>
              <div class="form-group">
                <label for="ProductTypeID" class="col-form-label">Category:</label>
                <select name="ProductTypeID" id="EditProductTypeID" class="form-control" required>
                  <option value="">Select Category</option>
                  <?php foreach ($product_types as $type): ?>
                    <option value="<?php echo $type['ProductTypeID']; ?>" <?php echo ($row['ProductTypeID'] == $type['ProductTypeID']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($type['TypeName']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="SubCatId" class="control-label">Sub Category:</label>
                <select name="SubCatId" id="EditSubCatId" class="form-control" required>
                  <option value="">Select Sub Category</option>

                </select>
              </div>

              <script>
                document.getElementById('EditProductTypeID').addEventListener('change', function() {
                  var categoryId = this.value;
                  var subProductSelect = document.getElementById('EditSubCatId');
                  subProductSelect.innerHTML = '<option value="">Loading...</option>';

                  fetch('controller/fetch_subcategory.php?ProductTypeID=' + categoryId)
                    .then(response => response.json())
                    .then(data => {
                      subProductSelect.innerHTML = '<option value="">Select Sub Category</option>'; // Reset options
                      data.forEach(function(subCategory) {
                        subProductSelect.innerHTML += '<option value="' + subCategory.SubCatId + '">' + subCategory.SubCatName + '</option>';
                      });
                    })
                    .catch(error => {
                      console.error('Error fetching subcategories:', error);
                      subProductSelect.innerHTML = '<option value="">Error loading subcategories</option>';
                    });
                });
              </script>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary" name="edit_product">Update Product</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php
  }
  ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>



</body>

</html>
<?php  ?>