<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

function displayServiceImage($imageName, $defaultImage = 'product_image/default.jpg') {
    $baseUrl = '/public/admin/'; 
    
    $imagePath = $baseUrl . 'product_image/' . $imageName; 
    $defaultImagePath = $baseUrl . $defaultImage;
    
    if (!empty($imageName) && file_exists($_SERVER['DOCUMENT_ROOT'] . $imagePath)) {
        return "<img src='$imagePath' height='60' width='90' class='img-thumbnail'>";
    } else {
        return "<img src='$defaultImagePath' height='60' width='90' class='img-thumbnail'>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>ALVSC || Order Menu</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <?php include_once('includes/header.php'); ?>
    <div class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="mb-4">Manage Products</h2>
                </div>
            </div>
        </div>
    </div>
    <div class="content">
        <div class="container">
            <div class="row">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Product Price</th>
                            <th>Product Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM tblproducts";
                        $result = mysqli_query($con, $query);

                        while ($row = mysqli_fetch_array($result)) {
                            $expirationDate = $row['ExpirationDate'];
                            $status = '';

                            if (!empty($expirationDate)) {
                                try {
                                    $currentDate = new DateTime();
                                    $expirationDateObj = new DateTime($expirationDate);

                                    if ($currentDate > $expirationDateObj) {
                                        $status = '<span class="badge" style="background-color: red; color: white;">Expired</span>';
                                    } elseif ($currentDate->diff($expirationDateObj)->days <= 7) {
                                        $status = '<span class="badge" style="background-color: orange; color: white;">Expiring Soon</span>';
                                    } else {
                                        $status = '<span class="badge" style="background-color: #8bc34a; color: white;">Available</span>';
                                    }
                                } catch (Exception $e) {
                                    $status = '<span class="badge" style="background-color: gray; color: white;">Invalid Date</span>';
                                }
                            } else {
                                $status = '<span class="badge" style="background-color: gray; color: white;">No Expiration Date</span>';
                            }
                        ?>
                        <tr>
                            <td><?php echo displayServiceImage($row['prod_image']); ?></td>
                            <td><?php echo htmlspecialchars($row['ProductName']); ?></td>
                            <td>₱<?php echo htmlspecialchars($row['price']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo $status; ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/menumaker.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/sticky-header.js"></script>
</body>

</html>
