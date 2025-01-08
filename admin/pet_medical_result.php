<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../includes/dbconnection.php');

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}



$search = isset($_GET['search']) ? $con->real_escape_string($_GET['search']) : '';

$sql = "SELECT tblmedical_record.*,  
               tblpet.pet_Name, 
               tblclients.Name, 
               GROUP_CONCAT(tblpet_services.service_id) AS service_ids, 
               GROUP_CONCAT(tblservices.ServiceName) AS service_names, 
               tblappointment.Appointment_Date,
               tblmedical_record.treatment, 
               tblmedical_record.diagnosis, 
               tblmedical_record.notes
        FROM tblmedical_record 
        INNER JOIN tblpet ON tblmedical_record.pet_ID = tblpet.pet_ID
        INNER JOIN tblclients ON tblpet.client_id = tblclients.client_id
        LEFT JOIN tblpet_services ON tblpet_services.pet_ID = tblpet.pet_ID
        LEFT JOIN tblservices ON tblpet_services.service_id = tblservices.service_id
        LEFT JOIN tblappointment ON tblmedical_record.Appt_ID = tblappointment.Appt_ID";

if (!empty($search)) {
    $sql .= " WHERE tblpet.pet_Name LIKE '%$search%' OR tblclients.Name LIKE '%$search%'";
}

$sql .= " GROUP BY tblmedical_record.Med_ID ORDER BY tblmedical_record.Med_ID DESC";

$result = $con->query($sql);
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
    <style>
        .styled-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .styled-table thead tr {
            background-color: #f5f5f5;
            text-align: left;
        }

        .styled-table th,
        .styled-table td {
            padding: 12px;
            border: 1px solid #dddddd;
        }

        .styled-table tbody tr:nth-of-type(even) {
            background-color: #f3f3f3;
        }

        .styled-table tbody tr:hover {
            background-color: #f1f1f1;
        }

        .search-bar {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .search-bar input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
        }

        .search-bar button {
            background-color: #337ab7;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
        }

        .search-bar button:hover {
            background-color: #285e8e;
        }

        .update-button {
            background-color: #5cb85c;
            color: white;
            border: none;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .view-button {
            background-color: #5bc0de;
            color: white;
            border: none;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .add-button {
            background-color: #337ab7;
            color: white;
            border: none;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .update-button:hover {
            background-color: #4cae4c;
        }
        .view-button:hover {
            background-color: #46b8da;
        }
        .add-button:hover {
            background-color: #2e6da4;
        }
    </style>
</head>

<body>
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>

        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables">
                    <br><br><br><h2 class="mt-4">Medical Record List</h2>
                    <br>
                    <div class="search-bar">
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search by Pet or Owner Name" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>
</div>

                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Owner Name</th>
                                <th>Pet Name</th>
                                <th>Visit Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                        <td>" . htmlspecialchars($row['Med_ID']) . "</td>
                                        <td>" . htmlspecialchars($row['Name']) . "</td>
                                        <td>" . htmlspecialchars($row['pet_Name']) . "</td>
                                        <td>" . (isset($row['Appointment_Date']) ? htmlspecialchars($row['Appointment_Date']) : 'N/A') . "</td>
                                        <td>
                                            <a href='record-details.php?Med_ID=" . htmlspecialchars($row['Med_ID']) . "' class='view-button'>View</a>
                                            <a href='add-med-trans.php?Med_ID=" . htmlspecialchars($row['Med_ID']) . "' class='add-button'>+Transaction</a>
                                            <a href='update-record.php?Med_ID=" . htmlspecialchars($row['Med_ID']) . "' class='update-button'>Update</a>
                                             
                                             </td>
                                        
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No records found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-default" onclick="history.back()">Previous</button>
        </div>
    </div>
</body>

</html>

<?php
$con->close();
?>
