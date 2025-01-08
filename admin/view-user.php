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

if (!isset($_GET['userid'])) {
    echo "No user specified.";
    exit();
}

$userID = intval($_GET['userid']);
$result = executeQuery(
    "SELECT tbluser.*, 
            tblperson.Firstname, 
            tblperson.Lastname, 
            tblperson.ContactNo, 
            tblperson.gender, 
            tblrole.role_name 
    FROM tbluser 
    JOIN tblperson ON tbluser.PersonID = tblperson.PersonID 
    JOIN tblrole ON tbluser.rID = tblrole.rID 
    WHERE tbluser.userID = ?", 
    [$userID], 
    "i"
);


$userDetails = mysqli_fetch_assoc($result);

if (!$userDetails) {
    die("User not found");
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
    <title>User Profile Details</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
</head>
<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>
        <div id="page-wrapper">
            <div class="tables">
                <div class="form-container">
                    <h4>User Profile Details:</h4>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>ID</th>
                                <td><?php echo $userDetails['userID']; ?></td>
                            </tr>
                            <tr>
                                <th>First Name</th>
                                <td><?php echo $userDetails['Firstname']; ?></td>
                            </tr>
                            <tr>
                                <th>Last Name</th>
                                <td><?php echo $userDetails['Lastname']; ?></td>
                            </tr>
                            <tr>
                                <th>Username</th>
                                <td><?php echo $userDetails['UserName']; ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?php echo $userDetails['Email']; ?></td>
                            </tr>
                            <tr>
                                <th>Contact Number</th>
                                <td><?php echo $userDetails['ContactNo']; ?></td>
                            </tr>
                            <tr>
                                <th>Password</th>
                                <td><?php echo $userDetails['Password']; ?></td>
                            </tr>
                            <tr>
                                <th>Role</th>
                                <td><?php echo $userDetails['role_name']; ?></td>
                            </tr>
                            <tr>
                                <th>Gender</th>
                                <td><?php echo $userDetails['gender']; ?></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="<?php echo $userDetails['status'] == 1 ? 'badge badge-success' : 'badge badge-danger'; ?>">
                                        <?php echo $statusLabels[$userDetails['status']]; ?>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <a href="user_account.php" class="btn btn-primary btn-back">Back</a>
                    <div class="text-right">
                        <a href="edit_user.php?editid=<?php echo urlencode($userDetails['userID']); ?>" class="btn btn-primary btn-update">Update</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.js"></script>
</body>
</html>
