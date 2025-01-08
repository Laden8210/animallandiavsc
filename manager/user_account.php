<?php
session_start();
error_reporting(E_ALL);
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

function executeQuery($query, $params = [], $types = "") {
    global $con;
    $stmt = mysqli_prepare($con, $query);
    if (!$stmt) {
        die('Prepare failed: ' . mysqli_error($con));
    }
    if ($types) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_error($stmt)) {
        die('Execute failed: ' . mysqli_stmt_error($stmt));
    }

    return mysqli_stmt_get_result($stmt);
}

$searchQuery = "";
$searchParam = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $searchQuery = " WHERE tbluser.UserName LIKE ? ";
    $searchParam = ["%$search%"];
}

$sql = "SELECT tbluser.*, tblrole.role_name 
        FROM tbluser 
        JOIN tblrole ON tbluser.rID = tblrole.rID 
        $searchQuery
        ORDER BY tbluser.userID DESC";

$result = executeQuery($sql, $searchParam, $searchParam ? "s" : "");

$statusLabels = [
    1 => 'Active',
    0 => 'Inactive'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALVSC || User</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #f0f0f0; 
        }
        th, td {
            border: 2px solid #ddd; 
            padding: 8px;
            text-align: left;
            background-color: #fff; 
        }
        th {
            background-color: #f5f5f5;
        }
        .form-container {
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            font-size: 14px;
            color: white;
            background-color: #337ab7; 
            text-decoration: none;
            cursor: pointer;
            border: none; 
        }
        
        .pull-right {
            display: flex;
            justify-content: flex-end;
            padding: 20px;
        }
        .badge {
            padding: 0.5em 0.5em;
            border-radius: 0.25rem;
            font-size: 0.700rem;
            font-weight: 500;
            color: #fff;
        }
        .badge-success {
            background-color: #8bc34a;
        }
        .badge-danger {
            background-color: #dc3545;
        }
        .no-results {
            text-align: center;
            font-size: 18px;
            color: #999;
            margin: 20px 0;
        }
        .pull-right .btn {
    margin-right: 5px; 
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
    <div class="pull-right">
        <a href="signup.php" class="btn">Add User</a>
    </div>
</div>

                    </div>
                    <h4>User List:</h4>
                    <br>
                    <div class="form-container">
                        <form class="form-inline" style="margin-bottom: 10px;" method="get">
                            <input type="search" name="search" class="form-control" placeholder="Search...">
                            <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                        </form>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                    <tr>
                                        <td><?php echo $row['userID']; ?></td>
                                        <td><?php echo htmlspecialchars($row['UserName']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['role_name'] ?? 'Unknown Role'); ?></td>
                                        <td>
                                            <?php
                                                $status = $row['status'];
                                                $badgeClass = ($status == 1) ? 'badge-success' : 'badge-danger';
                                                $statusText = $statusLabels[$status] ?? 'Inactive';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view-user.php?userid=<?php echo $row['userID']; ?>" class="btn btn-info btn-xs">View Details</a>
                                            

                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="no-results">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-default" onclick="history.back()">Previous</button>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.js"></script>
</body>
</html>
