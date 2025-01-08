<?php
session_start();
error_reporting(1);
include('includes/dbconnection.php');
if (strlen($_SESSION['id'] == 0)) {
    header('location:logout.php');
} else {
    if (isset($_POST['submit'])) {
        $Name = $_POST['Name'];
        $gender = $_POST['gender'];
        $Address = $_POST['Address'];
        $Email = $_POST['Email'];
        $ContactNumber = $_POST['ContactNumber'];
    
        $query = mysqli_query($con, "INSERT INTO tblclients(Name, gender, Address, Email, ContactNumber) 
                                     VALUES ('$Name', '$gender', '$Address', '$Email', '$ContactNumber')");
        if ($query) {
            echo "<script>alert('Customer has been added.');</script>";
            echo "<script>window.location.href = 'client-list.php';</script>";
        } else {
            echo "<script>alert('Something went wrong. Please try again.');</script>";
        }
    }    
}
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>ALVSC | Add Clients</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
</head>
<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php');?>
        <?php include_once('includes/header.php');?>
        <div id="page-wrapper">
            <div class="main-page">
                <div class="forms">
                    <div class="form-grids row widget-shadow">
                        <div class="form-body">
                            <form method="post">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" class="form-control" id="name" name="Name" placeholder="Full Name" required="true">
                                    <div class="form-group">

                                    <label for="gender">Gender</label>
                                    <select class="form-control" id="gender" name="gender" required="true">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    </select>
                                    </div>

                                <div class="form-group">
                                    <label for="Address">Address</label>
                                    <input type="Address" class="form-control" id="Address" name="Address" placeholder="Address" required="true">
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="Email" placeholder="Email" required="true">
                                </div>
                                <div class="form-group">
    <label for="ContactNumber">Mobile Number</label>
    <input type="text" class="form-control" id="ContactNumber" name="ContactNumber" placeholder="Mobile Number" required="true" maxlength="11" pattern="^[0-9]{11}$" title="Contact number must be exactly 11 digits and contain no letters">
</div>

                                <button type="submit" name="submit" class="btn btn-default">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    <script src="js/bootstrap.js"></script>
</body>
</html>
