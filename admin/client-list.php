<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
} else {
?>
<!DOCTYPE HTML>
<html>

<head>
    <title>ALVSC || Customer List</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/modernizr.custom.js"></script>
    <link href="https://fonts.googleapis.com/css?family=Oswald|Roboto+Flex&display=swap" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
    <script src="js/wow.min.js"></script>
    <script>
        new WOW().init();
    </script>
    <script src="js/metisMenu.min.js"></script>
    <script src="js/custom.js"></script>
    <link href="css/custom.css" rel="stylesheet">
    <style>
        table {
            font-size: 14px;
        }
        th, td {
            padding: 10px;
            text-align: center;
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
                    <h4>Client List:</h4>
                    <form class="form-inline" style="margin-bottom: 10px;" method="GET">
                        <input type="search" name="search" class="form-control" placeholder="Search customer name...">
                        <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                        <div class="pull-right">
                            <a href="add-client.php" class="btn" style="background-color: #337ab7; color: #fff;">Add Client</a>
                        </div>
                    </form>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Contact Number</th>
                                <th>Address</th>
                                <th>Email</th>
                                <th>Creation Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
$limit = 5;
$offset = isset($_GET['page']) ? ($_GET['page'] - 1) * $limit : 0;

if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
    $sql = "SELECT * FROM tblclients WHERE Name LIKE '%$search_query%' ORDER BY CreationDate DESC LIMIT $offset, $limit";
} else {
    $sql = "SELECT * FROM tblclients ORDER BY CreationDate DESC LIMIT $offset, $limit";
}

$ret = mysqli_query($con, $sql);
$cnt = $offset + 1; 
while ($row = mysqli_fetch_array($ret)) {
?>
    <tr>
        <th scope="row"><?php echo $cnt; ?></th>
        <td><?php echo $row['Name']; ?></td>
        <td><?php echo $row['gender']; ?></td>
        <td><?php echo $row['ContactNumber']; ?></td>
        <td><?php echo $row['Address']; ?></td>
        <td><?php echo $row['Email']; ?></td>
        <td>
            <?php 
            $CreationDate = $row['CreationDate'];
            $datetime = $CreationDate . ' ';
            $dateTimeObj = new DateTime($datetime);
            $formattedDate = $dateTimeObj->format('m/d/Y'); 
            $formattedTime = $dateTimeObj->format('h:i:s A'); 
            echo $formattedDate . ' - ' . $formattedTime;
            ?>
        </td>
        <td style="text-align: center; vertical-align: middle;">
            <a href="update-client.php?editid=<?php echo $row['client_id']; ?>" class="btn btn-primary btn-xs" style="display: block; margin-bottom: 5px;">Edit Client</a>
            <a href="add-client-products.php?addid=<?php echo $row['client_id']; ?>" class="btn btn-success btn-xs" style="display: block; margin-bottom: 5px;">Create Transaction</a>
            <a href="client-his.php?clientid=<?php echo $row['client_id']; ?>" class="btn btn-info btn-sm" style="display: block;">Client History</a>
        </td>
    </tr>
<?php
    $cnt++;
}
?>

                        </tbody>
                    </table>
                    <div class="text-center">
                        <?php
                        $total_pages = ceil(mysqli_num_rows(mysqli_query($con, "SELECT * FROM tblclients")) / $limit);

                        if ($total_pages > 1) {
                            $page = isset($_GET['page']) ? $_GET['page'] : 1;
                            echo "<ul class='pagination'>";
                            if ($page > 1) {
                                echo "<li><a href='?page=" . ($page - 1) . "'>&laquo;</a></li>";
                            }
                            for ($i = 1; $i <= $total_pages; $i++) {
                                echo "<li " . (($page == $i) ? "class='active'" : "") . "><a href='?page=$i'>$i</a></li>";
                            }
                            if ($page < $total_pages) {
                                echo "<li><a href='?page=" . ($page + 1) . "'>&raquo;</a></li>";
                            }
                            echo "</ul>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
}
?>
