<?php  
include('includes/dbconnection.php');
session_start();
error_reporting(0);

function displayServiceImage($imageName, $defaultImage = 'product_image/default.jpg') {
    $baseUrl = '/public/admin/'; 
    $imagePath = $baseUrl . 'product_image/' . ($imageName ? $imageName : 'default.jpg');
    $defaultImagePath = $baseUrl . $defaultImage;

    if ($imageName && file_exists($_SERVER['DOCUMENT_ROOT'] . $imagePath)) {
        return "<img src='$imagePath' height='60' width='90' class='img-thumbnail'>";
    } else {
        return "<img src='$defaultImagePath' height='60' width='90' class='img-thumbnail'>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALVSC || Service List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .form-wrapper {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .badge-status {
            padding: 3px 10px;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include_once('includes/header.php'); ?>
    <div class="page-header">
        <div class="container">
            <div class="row"></div>
        </div>
    </div>
    <div class="content">
        <div class="container">
            <h2 class="mb-4">Manage Services</h2>
            <div class="row">
                <?php
                $ret = mysqli_query($con, "SELECT * FROM tblservices");
                while ($row = mysqli_fetch_array($ret)) {
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow" style="border-radius: 10px;">
                        <img src="<?php echo displayServiceImage($row['image']); ?>" class="card-img-top" alt="<?php echo $row['ServiceName']; ?>" style="border-top-left-radius: 10px; border-top-right-radius: 10px;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $row['ServiceName']; ?></h5>
                            <p class="card-text">₱<?php echo $row['Cost']; ?></p>
                            <p class="card-text"><?php echo $row['Description']; ?></p>
                            <p class="card-text">
                                <?php
                                $status = $row['status'];
                                if ($status == 'Available') {
                                    echo '<span class="badge-status" style="background-color: #8bc34a; color: white;">' . $status . '</span>';
                                } else {
                                    echo '<span class="badge-status" style="background-color: red; color: white;">' . $status . '</span>';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/menumaker.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/sticky-header.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
