<?php
session_start();
error_reporting(1);
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
} else {
    if (isset($_GET['editid'])) {
        $client_id = intval($_GET['editid']);
        $query = $con->prepare("SELECT * FROM tblclients WHERE client_id = ?");
        $query->bind_param("i", $client_id);
        $query->execute();
        $result = $query->get_result();
        $row = $result->fetch_assoc();

        
        if (!$row) {
            echo "<script>alert('Invalid client ID.'); window.location.href = 'client-list.php';</script>";
            exit;
        }

        
        $name = $_POST['name'] ?? $row['Name'];
        $gender = $_POST['gender'] ?? $row['gender'];
        $Address = $_POST['Address'] ?? $row['Address'];
        $contactNumber = $_POST['contactNumber'] ?? $row['ContactNumber'];
        $email = $_POST['email'] ?? $row['Email'];

        if (isset($_POST['submit'])) {
            
            $updateQuery = $con->prepare("UPDATE tblclients SET Name=?, gender=?, Address=?, ContactNumber=?, Email=? WHERE client_id=?");
            $updateQuery->bind_param("sssssi", $name, $gender, $Address, $contactNumber, $email, $client_id);

            if ($updateQuery->execute()) {
                echo "<script>alert('Client details updated successfully');</script>";
                echo "<script>window.location.href = 'client-list.php';</script>";
                exit;
            } else {
                echo "<script>alert('Error updating client details.');</script>";
            }
        }
    } else {
        echo "<script>alert('No client ID provided.'); window.location.href = 'client-list.php';</script>";
        exit;
    }
?>
<!DOCTYPE HTML>
<html>

<head>
    <title>ALVSC || Update Client</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
</head>

<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>
        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables">
                    <h3 class="title1" style="color: #777777;">Update Client</h3>
                    <div class="form-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="name">Client Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" class="form-control" required="true">
                            </div>
                            <div class="form-group">
                                <label for="Address">Address</label>
                                <input type="text" name="Address" value="<?php echo htmlspecialchars($Address); ?>" class="form-control" required="true">
                            </div>
                            <div class="form-group">
                                <label for="contactNumber">Contact Number</label>
                                <input type="text" name="contactNumber" value="<?php echo htmlspecialchars($contactNumber); ?>" class="form-control" required="true" maxlength="11" pattern="^[0-9]{11}$" title="Contact number must be exactly 11 digits and contain no letters">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="form-control" required="true">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select class="form-control" id="gender" name="gender" required="true">
                                    <option value="Male" <?php echo ($gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            <button type="submit" name="submit" class="btn btn-primary">Update Client</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="js/bootstrap.js"></script>
</body>

</html>
<?php
}
?>
