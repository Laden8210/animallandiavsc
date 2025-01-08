<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>ALVSC || Home Page</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,300i,400,400i,500,500i,700,700i%7cMontserrat:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>

        @media (max-width: 768px) {
            .hero-section .container {
                padding-top: 30px;
                text-align: center;
            }

            .space-medium.bg-default .container {
                padding: 20px;
            }

            .space-medium.bg-default .col-lg-5,
            .space-medium.bg-default .col-lg-7 {
                text-align: center;
            }

            .contact_section iframe {
                width: 100%;
                height: 350px;
            }

            .heading_container img {
                width: 100%;
                height: auto;
            }
        }

        @media (max-width: 576px) {
            .space-medium.bg-default .col-lg-5 {
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <?php include_once('includes/header.php'); ?>
    <div class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
                </div>
            </div>
        </div>
    </div><br>
    <div class="space-medium bg-default">
        <div class="container">
            <div class="row">
                <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                    <img src="images/about-img.jpg" alt="" class="img-responsive">
                </div>
                <div class="col-lg-7 col-md-7 col-sm-12 col-xs-12">
                    <div class="well-block">
                        <?php
                        $ret_about = mysqli_query($con, "select * from tblpage where PageType='aboutus'");
                        $cnt = 1;
                        while ($row = mysqli_fetch_array($ret_about)) {
                        ?>
                            <div>
                                <h1><?php echo $row['PageTitle']; ?></h1>
                                <p><?php echo $row['PageDescription']; ?></p>
                            </div>
                        <?php
                        }

                        $ret_contact = mysqli_query($con, "select * from tblpage where PageType='contactus'");
                        while ($row = mysqli_fetch_array($ret_contact)) {
                        ?>
                            <br>
                            <div>
                                <h2 class="widget-title">Contact Info</h2>
                                <address>
                                    <strong>Phone:</strong>
                                    <?php echo $row['MobileNumber']; ?>
                                </address>
                                <address>
                                    <strong>Email:</strong>
                                    <?php echo $row['Email']; ?>
                                </address>
                                <address>
                                    <strong>Time:</strong>
                                    <?php echo $row['Time']; ?>
                                </address>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="contact_section layout_padding-top">
    <div class="container-fluid">
        <div class="heading_container text-center">
            <img src="images/heading-img.png" alt="" class="img-fluid">
            <h2>HERE'S OUR LOCATION!</h2>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3964.254266570183!2d124.8342495!3d6.4964885!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x32f818eb8383e9d5%3A0xcef24446c75c410a!2sAnimal%20Landia%20Veterinary%20Solutions%20%26%20Clinic!5e0!3m2!1sen!2sph!4v1701870011852!5m2!1sen!2sph"
                    width="100%" height="500" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</section>

<style>
    
    .heading_container img {
        max-width: 100%;
        height: auto;
    }

    
    .map-container iframe {
        width: 100%;
        height: 500px;
    }

    @media (max-width: 768px) {
        .map-container iframe {
            height: 350px;
        }
    }

    @media (max-width: 576px) {
        .contact_section {
            padding-top: 30px;
        }
    }
</style>


    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/menumaker.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/sticky-header.js"></script>

</body>

</html>
