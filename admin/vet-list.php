<?php
session_start();
error_reporting(E_ALL);
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

$query = "SELECT * FROM tblvet ORDER BY CreationDate DESC";
$result = $con->query($query);

if (isset($_POST['add_vet'])) {
    $vFirstname = $con->real_escape_string($_POST['vFirstname']);
    $vLastname = $con->real_escape_string($_POST['vLastname']);
    $Gender = $con->real_escape_string($_POST['Gender']);
    $Email = $con->real_escape_string($_POST['Email']);

    $sql = "INSERT INTO tblvet (vFirstname, vLastname, Gender, Email) 
            VALUES ('$vFirstname', '$vLastname', '$Gender', '$Email')";

    if ($con->query($sql)) {
        echo "<script>alert('Vet added successfully!'); window.location.href='vet-list.php';</script>";
    } else {
        echo "<script>alert('Error: {$con->error}');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Record List</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/modernizr.custom.js"></script>
   
    <link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
    <script src="js/wow.min.js"></script>
    <script> new WOW().init(); </script>
    <script src="js/metisMenu.min.js"></script>
    <script src="js/custom.js"></script>
    <link href="css/custom.css" rel="stylesheet">
    <title>Vet Management</title>
    <link href="css/style.css" rel="stylesheet">
    <style>
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            width: 50%;
            border-radius: 8px;
        }
        .close-btn {
            float: right;
            font-size: 24px;
            cursor: pointer;
        }
    </style>
</head>
<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>

        <div id="page-wrapper">
            <h2>Vet Management</h2>
<button class="btn btn-primary" style="float: right; margin-bottom: 10px;" onclick="openModal('addVetModal')">Add New Vet</button>


            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Vet ID</th>
                        <th>Firstname</th>
                        <th>Lastname</th>
                        <th>Gender</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['vet_ID']; ?></td>
                            <td><?php echo $row['vFirstname']; ?></td>
                            <td><?php echo $row['vLastname']; ?></td>
                            <td><?php echo $row['Gender']; ?></td>
                            <td><?php echo $row['Email']; ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="openModal('editVetModal<?php echo $row['vet_ID']; ?>')">Edit</button>
                            </td>
                        </tr>

                        <div id="editVetModal<?php echo $row['vet_ID']; ?>" class="modal">
                            <div class="modal-content">
                                <span class="close-btn" onclick="closeModal('editVetModal<?php echo $row['vet_ID']; ?>')">&times;</span>
                                <h2>Edit Vet</h2>
                                <form method="POST">
                                    <input type="hidden" name="vet_ID" value="<?php echo $row['vet_ID']; ?>">
                                    <div>
                                        <label>Firstname</label>
                                        <input type="text" name="vFirstname" value="<?php echo $row['vFirstname']; ?>" required>
                                    </div>
                                    <div>
                                        <label>Lastname</label>
                                        <input type="text" name="vLastname" value="<?php echo $row['vLastname']; ?>" required>
                                    </div>
                                    <div>
                                        <label>Gender</label>
                                        <select name="Gender" required>
                                            <option value="Male" <?php echo $row['Gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $row['Gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label>Email</label>
                                        <input type="email" name="Email" value="<?php echo $row['Email']; ?>" required>
                                    </div>
                                    <button type="submit" name="update_vet" class="btn btn-success">Save Changes</button>
                                    <button type="button" class="btn btn-secondary" onclick="closeModal('editVetModal<?php echo $row['vet_ID']; ?>')">Close</button>
                                </form>
                            </div>
                        </div>
                    <?php } ?>
                </tbody>
            </table>

            <div id="addVetModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn" onclick="closeModal('addVetModal')">&times;</span>
                    <h2>Add New Vet</h2>
                    <form method="POST">
                        <div>
                            <label>Firstname</label>
                            <input type="text" name="vFirstname" required>
                        </div>
                        <div>
                            <label>Lastname</label>
                            <input type="text" name="vLastname" required>
                        </div>
                        <div>
                            <label>Gender</label>
                            <select name="Gender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="Email" required>
                        </div>
                        <button type="submit" name="add_vet" class="btn btn-primary">Add Vet</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('addVetModal')">Close</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
    </script>
</body>
</html>
