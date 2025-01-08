<?php
session_start();
error_reporting(1);
include('includes/dbconnection.php');

$roleQuery = mysqli_query($con, "SELECT * FROM tblrole");

if (isset($_POST['signup'])) {
    $Firstname = $_POST['Firstname'];
    $Lastname = $_POST['Lastname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $ContactNo = $_POST['ContactNo'];
    $gender = strtolower(trim($_POST['gender']));
    $rID = $_POST['rID'];
    $password = md5($_POST['password']);
    $confirmPassword = md5($_POST['confirm_password']);
    $statusText = $_POST['status'];
    $status = ($statusText === "Active") ? 1 : 2; 

    $error = [];

    if (!preg_match('/^\d{11}$/', $ContactNo)) {
        $error[] = 'Contact number must be exactly 11 digits and contain no letters.';
    }

    if (!in_array($gender, ['male', 'female'])) {
        $error[] = 'Invalid gender selected. Please choose "male" or "female".';
    }

    if ($password != $confirmPassword) {
        $error[] = 'Passwords do not match!';
    }

    if (empty($error)) {
        $query = mysqli_query($con, "SELECT * FROM tbluser WHERE UserName='$username' AND Email='$email' AND rID='$rID'");
        $ret = mysqli_fetch_array($query);

        if ($ret > 0) {
            $error[] = 'User with these details already exists!';
        } else {
            $stmt = $con->prepare("INSERT INTO tblperson (Firstname, Lastname, ContactNo, gender) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $Firstname, $Lastname, $ContactNo, $gender);

            if ($stmt->execute()) {
                $personID = mysqli_insert_id($con);

                $stmt2 = $con->prepare("INSERT INTO tbluser (PersonID, UserName, Email, Password, rID, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("isssis", $personID, $username, $email, $password, $rID, $status);

                if ($stmt2->execute()) {
                    echo "<script>alert('Registration successful!');</script>";
                    echo "<script type='text/javascript'> document.location ='user_account.php'; </script>";
                } else {
                    $error[] = 'User registration failed. Please try again.';
                }
            } else {
                $error[] = 'Person registration failed: ' . mysqli_error($con);
            }

            $stmt->close();
        }
    }

    if (!empty($error)) {
        foreach ($error as $err) {
            echo "<div class='error'>$err</div>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/font-awesome.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            outline: none;
            border: none;
            text-decoration: none;
        }

        .form-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-container form {
            padding: 10px;
            border-radius: 1px;
            background: #ffffff:
            text-align: center;
            width: 1500px;
        }

        .form-container h3 {
            font-size: 30px;
            text-transform: uppercase;
            margin-bottom: 1px;
            color: #000;
            text-align: left;
        }

        .form-container input,
        .form-container select {
            width: 100%;
            padding: 10px 15px;
            font-size: 12px;
            margin: 8px 0;
            border-radius: 5px;
            background-color: #ddd;
        }

        .form-container select option {
            background: white;
        }

        .form-container .form-btn {
            background: #337ab7;
            color: white;
            text-transform: capitalize;
            font-size: 20px;
        }

        .form-container form .error-msg {
            margin: 10px 0;
            display: block;
            background: #ec0707;
            color: white;
            border-radius: 5px;
            font-size: 20px;
        }

        .form-btn { 
            border: none; 
            color: white; 
            padding: 5px 5px; 
            text-align: center; 
            text-decoration: none; 
            display: inline-block; 
            font-size: 10px; 
            margin: 4px 2px; 
            cursor: pointer; 
            border-radius: 4px;  
        }    
      

    .form-container form {
    padding: 20px;
    text-align: center; 
    }


    </style>
</head> 

<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>
        <div id="page-wrapper">
<div class="form-container">
    <form action="" method="post">
        <br><br><h3>Add User</h3>
        <?php
        if (isset($error)) {
            foreach ($error as $error) {
                echo '<span class="error-msg">', $error . '</span>';
            };
        };
        ?>
        
        <input type="text" name="Firstname" placeholder="First Name" required="true">
        <input type="text" name="Lastname" placeholder="Last Name" required="true">
        <input type="text" name="username" placeholder="Username" required="true">
        <input type="email" name="email" placeholder="Email" required="true">
        <input type="ContactNo" name="ContactNo" placeholder="Contact Number" required="true">
        
        <select class="form-control" name="gender" id="gender" required>
        <option value="" disabled selected>Select Gender</option>
        <option value="male">Male</option>
        <option value="female">Female</option>
        </select>


        <input type="password" name="password" placeholder="Password" required="true">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required="true">

        <select name="rID" required>
            <option value="" disabled selected>Select Role</option>
            <?php
            while ($row = mysqli_fetch_array($roleQuery)) {
                echo '<option value="' . $row['rID'] . '">' . $row['role_name'] . '</option>';
            }
            ?>
        </select>
        <select class="form-control" name="status" id="status" required>
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
        </select>

        <button type="submit" name="signup" class="form-btn">Submit</button>
    </form>
</div>

<script src="js/jquery-1.11.1.min.js"></script>
<script src="js/modernizr.custom.js"></script>
<script src="js/wow.min.js"></script>
<script src="js/metisMenu.min.js"></script>
<script src="js/custom.js"></script>
<script src="js/scripts.js"></script>
<script src="js/bootstrap.js"></script>

</body>
</html>
