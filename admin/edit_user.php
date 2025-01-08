<?php
session_start();
error_reporting(1);
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

function executeQuery($query, $params, $types = "") {
    global $con;
    $stmt = mysqli_prepare($con, $query);
    if ($types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

if (!isset($_GET['editid'])) {
    echo "No user specified.";
    exit();
}

$userID = intval($_GET['editid']);
$result = executeQuery(
    "SELECT tbluser.*, tblperson.Firstname, tblperson.Lastname, tblperson.ContactNo, tblperson.gender, tblrole.role_name 
    FROM tbluser 
    JOIN tblperson ON tbluser.PersonID = tblperson.PersonID 
    JOIN tblrole ON tbluser.rID = tblrole.rID 
    WHERE tbluser.userID = ?", 
    [$userID], 
    "i"
);

$userDetails = mysqli_fetch_assoc($result);

if (!$userDetails) {
    die("User not found.");
}

if (isset($_POST['update'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $contactNo = $_POST['contactNo'];
    $gender = $_POST['gender'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    $newPassword = $_POST['password'];

    if (!preg_match('/^\d{11}$/', $contactNo)) {
        echo "<script>alert('Contact number must be exactly 11 digits and contain no letters.');</script>";
    } else {
        executeQuery(
            "UPDATE tblperson 
             SET Firstname = ?, Lastname = ?, ContactNo = ?, gender = ? 
             WHERE PersonID = ?", 
            [$firstname, $lastname, $contactNo, $gender, $userDetails['PersonID']], 
            "ssssi"
        );

        if (!empty($newPassword)) {
            $hashedPassword = md5($newPassword); // Use MD5 hashing
            executeQuery(
                "UPDATE tbluser 
                 SET UserName = ?, Email = ?, rID = ?, status = ?, Password = ? 
                 WHERE userID = ?", 
                [$username, $email, $role, $status, $hashedPassword, $userID], 
                "sssisi"
            );
        } else {
            executeQuery(
                "UPDATE tbluser 
                 SET UserName = ?, Email = ?, rID = ?, status = ? 
                 WHERE userID = ?", 
                [$username, $email, $role, $status, $userID], 
                "sssii"
            );
        }

        echo "<script>
                alert('User information updated successfully.');
                window.location.href = 'user_account.php';
              </script>";
    }
}

$statusLabels = [
    1 => 'Active',
    2 => 'Inactive'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/font-awesome.css" rel="stylesheet">
</head>
<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>
        <div id="page-wrapper">
            <div class="tables">
                <div class="form-container">
                    <h4>Edit User:</h4>
                    <form method="post">
                        <div class="form-group">
                            <label for="firstname">First Name:</label>
                            <input type="text" name="firstname" value="<?php echo htmlspecialchars($userDetails['Firstname']); ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="lastname">Last Name:</label>
                            <input type="text" name="lastname" value="<?php echo htmlspecialchars($userDetails['Lastname']); ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($userDetails['UserName']); ?>" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($userDetails['Email']); ?>" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="contactNo">Contact Number:</label>
                            <input type="text" name="contactNo" value="<?php echo htmlspecialchars($userDetails['ContactNo']); ?>" class="form-control" required pattern="\d{11}" title="Contact number must be exactly 11 digits and contain no letters.">
                        </div>
                        <div class="form-group">
                            <label for="password">New Password (leave blank if unchanged):</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="role">Role:</label>
                            <select name="role" class="form-control" required>
                                <?php
                                $rolesResult = executeQuery("SELECT * FROM tblrole", []);
                                while ($roleRow = mysqli_fetch_assoc($rolesResult)) {
                                    $selected = $roleRow['rID'] == $userDetails['rID'] ? 'selected' : '';
                                    echo "<option value=\"{$roleRow['rID']}\" $selected>{$roleRow['role_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender:</label>
                            <select name="gender" class="form-control" required>
                                <option value="Male" <?php echo $userDetails['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $userDetails['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select name="status" class="form-control" required>
                                <option value="1" <?php echo $userDetails['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="2" <?php echo $userDetails['status'] == 2 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" name="update" class="btn btn-primary">Update</button>
                        <a href="javascript:history.back()" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.js"></script>
</body>
</html>
