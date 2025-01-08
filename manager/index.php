<?php
session_start();
error_reporting(1);

include('includes/dbconnection.php');

if (isset($_POST['login'])) {
    $UserName = $_POST['username'];
    $Password = md5($_POST['password']);

    $query = mysqli_query($con, "
        SELECT tbluser.userID, tbluser.PersonID, tblrole.rID AS rID, tblperson.Firstname, tbluser.Status 
        FROM tbluser
        JOIN tblrole ON tbluser.rID = tblrole.rID
        JOIN tblperson ON tbluser.PersonID = tblperson.PersonID
        WHERE tbluser.UserName='$UserName' AND tbluser.Password='$Password'
    ");

    $user = mysqli_fetch_array($query);

    if ($user) {
        if ($user['Status'] == '2') { 
            echo "<script>alert('Your account is inactive. Please contact the administrator.');</script>";
        } else {
            $_SESSION['id'] = $user['userID'];
            $_SESSION['Firstname'] = $user['Firstname'];
            $_SESSION['rID'] = $user['rID'];

            if ($user['rID'] == '2') {
                echo "<script type='text/javascript'> document.location ='dashboard.php'; </script>";
            }
        }
    } else {
        echo "<script>alert('Invalid username or password.');</script>";
    }
}
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALVSC | Login Page</title>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/modernizr.custom.js"></script>
    <script src="js/wow.min.js"></script>
    <script>
        new WOW().init();
    </script>
    <script src="js/metisMenu.min.js"></script>
    <script src="js/custom.js"></script>
    <style>
        * {
            font-family: "Poppins", sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            outline: none;
            border: none;
            text-decoration: none;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f1f1f1;
            margin: 0;
        }

        body::before {
            content: "";
            position: fixed; 
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('/images/store.jpg');
            background-size: cover; 
            background-position: center;
            filter: blur(3px); 
            z-index: -1; 
        }

        .form-container {
            width: 40%;
            padding: 30px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #fff;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .form-container img {
            width: 100%; 
            height: auto; 
            margin-bottom: 20px;
            max-width: 500px; 
        }

        .form-container h3 {
            font-size: 30px;
            text-transform: uppercase;
            margin-bottom: 10px;
            color: #333;
        }

        .form-container input {
            width: 100%;
            padding: 10px 15px;
            font-size: 17px;
            margin: 8px 0;
            border-radius: 5px;
        }

        .form-container .form-btn {
            background: #337ab7;
            color: white;
            font-size: 20px;
        }

        .form-container .form-btn:hover {
            background-color: #335b7d;
        }

        .form-container .error-msg {
            margin: 10px 0;
            display: block;
            background: burlywood;
            color: white;
            border-radius: 5px;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <form action="" method="post">
            <h3>Log In</h3>
            <input type="text" class="user" name="username" placeholder="Username" required>
            <input type="password" name="password" class="lock" placeholder="Password" required>
            <input type="submit" name="login" value="Login" class="form-btn">
            <br><a href="https://animallandiavsc.shop//">Go to Homepage?</a>
        </form>
    </div>
</body>
</html>
