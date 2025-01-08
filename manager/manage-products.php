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
  <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
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
            <input type="search" name="search" class="form-control" placeholder="Search products...">
            <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
          </form>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>No.</th>
                <th>Image</th>
                <th>Name</th>
                <th>Description</th>
                <th>Product Price</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if (isset($_GET['search'])) {
                $search_query = $_GET['search'];
                $ret = mysqli_query($con, "SELECT * FROM tblproducts WHERE ProductName LIKE '%$search_query%'");
              } else {
                $ret = mysqli_query($con, "SELECT * FROM tblproducts ORDER BY ProductID DESC");
              }
              $cnt = 1;
              while ($row = mysqli_fetch_array($ret)) {
                // Check if ExpirationDate is valid
                $expirationDate = $row['ExpirationDate'];
                $status = '';
                if (!empty($expirationDate)) {
                  $expirationDate = new DateTime($row['ExpirationDate']);
                  $currentDate = new DateTime();

                  if ($currentDate > $expirationDate) {
                    $status = '<span class="badge" style="background-color: red; color: white;">Expired</span>';
                  } elseif ($currentDate->diff($expirationDate)->days <= 7) {
                    $status = '<span class="badge" style="background-color: orange; color: white;">Expiring Soon</span>';
                  } else {
                    $status = '<span class="badge" style="background-color: #8bc34a; color: white;">Active</span>';
                  }
                } else {
                  $status = '<span class="badge" style="background-color: gray; color: white;">No Expiration</span>';
                }
              ?>
                <tr>
                  <th scope="row"><?php echo $cnt; ?></th>
                  <td style="text-align: center;">
                    <?php
                    $imagePath = 'product_image/' . ($row['prod_image'] ? $row['prod_image'] : 'default.jpg');
                    echo "<img src='$imagePath' height='60' width='90' class='img-thumbnail'>";
                    ?>
                  </td>
                  <td><?php echo $row['ProductName']; ?></td>
                  <td><?php echo $row['description']; ?></td>
                  <td>₱<?php echo $row['price']; ?></td>
                  <td><?php echo $status; ?></td>
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
</body>

</html>
<?php  ?>
