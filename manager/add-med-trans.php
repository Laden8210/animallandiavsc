<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$servername = "localhost";
$username = "u920096089_vmscdb";
$password = "Vmscdb2024";
$dbname = "u920096089_vmscdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$med_id = isset($_GET['Med_ID']) ? (int)$_GET['Med_ID'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $med_id > 0) {
    
    $client_check_query = "SELECT tblpet.client_id 
                           FROM tblmedical_record 
                           INNER JOIN tblpet ON tblmedical_record.pet_ID = tblpet.pet_ID
                           WHERE tblmedical_record.Med_ID = '$med_id'";

    $client_check_result = mysqli_query($con, $client_check_query);
    $client_row = mysqli_fetch_assoc($client_check_result);
    $client_id = $client_row['client_id'] ?? 0;

    if ($client_id == 0) {
        echo "<script>alert('Client not found for this medical record.');</script>";
        exit;
    }

    $transid = mt_rand(100000000, 999999999);
    $pids = $_POST['pids'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $sids = $_POST['sids'] ?? [];

    mysqli_begin_transaction($con);
    try {
        
foreach ($sids as $svid) {
    $quantity = 1; 

    $pet_service_query = "SELECT pet_ID FROM tblpet_services WHERE service_id = '$svid' AND pet_ID = (SELECT tblmedical_record.pet_ID FROM tblmedical_record WHERE Med_ID = '$med_id')";
    $pet_service_result = mysqli_query($con, $pet_service_query);
    $pet_service_row = mysqli_fetch_assoc($pet_service_result);
    $pet_id = $pet_service_row['pet_ID'] ?? 0;

    if ($pet_id == 0) {
        throw new Exception("No pet found for service ID $svid.");
    }

    $insert_service = "INSERT INTO tbltransaction (client_id, service_id, Trans_Code, Qty, pet_ID) 
                       VALUES ('$client_id', '$svid', '$transid', '$quantity', '$pet_id')";
    if (!mysqli_query($con, $insert_service)) {
        throw new Exception("Failed to insert service transaction.");
    }
}



    
       foreach ($pids as $product_id) {
    
    $qty = isset($quantities[$product_id]) ? (int)$quantities[$product_id] : 0;

    if ($qty <= 0) {
        throw new Exception("Invalid quantity for product ID $product_id.");
    }

    
    $stock_check_query = "SELECT Quantity FROM tblinventory WHERE ProductID = '$product_id'";
    $stock_result = mysqli_query($con, $stock_check_query);
    $stock_row = mysqli_fetch_assoc($stock_result);

    if (!$stock_row || $stock_row['Quantity'] < $qty) {
        throw new Exception("Insufficient stock for product ID $product_id.");
    }

    
    $insert_product_transaction = "INSERT INTO tbltransaction (client_id, ProductID, Trans_Code, Qty) 
                                   VALUES ('$client_id', '$product_id', '$transid', '$qty')";
    if (!mysqli_query($con, $insert_product_transaction)) {
        throw new Exception("Failed to insert product transaction for product ID $product_id.");
    }

   
    $update_inventory = "UPDATE tblinventory SET Quantity = Quantity - $qty WHERE ProductID = '$product_id'";
    if (!mysqli_query($con, $update_inventory)) {
        throw new Exception("Failed to update inventory for product ID $product_id.");
    }
}
        mysqli_commit($con);
        echo "<script>alert('Transaction created successfully. Transaction ID: $transid');</script>";
        echo "<script>window.location.href = 'transaction.php';</script>";
    } catch (Exception $e) {
        mysqli_rollback($con);
        echo "<script>alert('Transaction failed: " . $e->getMessage() . "');</script>";
        echo "<script>window.location.href = 'transaction.php';</script>";
    }
}

if ($med_id > 0) {
    
    $sql = "SELECT tblmedical_record.*, 
                   tblpet.pet_Name, 
                   tblclients.Name, 
                   tblappointment.Appointment_Date, 
                   tblmedical_record.weight, 
                   tblmedical_record.temp,
                   tblmedical_record.diagnosis, 
                   tblmedical_record.treatment, 
                   tblmedical_record.notes,
                   GROUP_CONCAT(DISTINCT tblservices.ServiceName) AS service_names,
                   CONCAT(tblvet.vFirstname, ' ', tblvet.vLastname) AS vet_name,
                   GROUP_CONCAT(DISTINCT tblpet.pet_Name) AS transaction_pets
            FROM tblmedical_record
            INNER JOIN tblpet ON tblmedical_record.pet_ID = tblpet.pet_ID
            INNER JOIN tblclients ON tblpet.client_id = tblclients.client_id
            LEFT JOIN tblappointment ON tblmedical_record.Appt_ID = tblappointment.Appt_ID
            LEFT JOIN tblpet_services ON tblpet_services.pet_ID = tblpet.pet_ID
            LEFT JOIN tblservices ON tblpet_services.service_id = tblservices.service_id
            LEFT JOIN tblvet ON tblmedical_record.vet_ID = tblvet.vet_ID
            LEFT JOIN tbltransaction ON tbltransaction.pet_ID = tblpet.pet_ID
            WHERE tblmedical_record.Med_ID = $med_id
            GROUP BY tblmedical_record.Med_ID";

    $result = $con->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Record Details</title>
    <link rel="stylesheet" href="path/to/your/styles.css">
    <style>
        
    </style>
</head>
<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>

        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables">
                    <?php
                    error_reporting(E_ALL);
                    include('includes/dbconnection.php');

                    if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
                        header('location:logout.php');
                        exit();
                    }

                    $med_id = isset($_GET['Med_ID']) ? (int)$_GET['Med_ID'] : 0;

                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $med_id > 0) {
                        
                    }

                    if ($med_id > 0) {
                        $sql = "SELECT tblmedical_record.*, 
                                       tblpet.pet_Name, 
                                       tblclients.Name, 
                                       tblappointment.Appointment_Date, 
                                       tblmedical_record.weight, 
                                       tblmedical_record.temp,
                                       tblmedical_record.diagnosis, 
                                       tblmedical_record.treatment, 
                                       tblmedical_record.notes,
                                       GROUP_CONCAT(DISTINCT tblservices.ServiceName) AS service_names,
                                       CONCAT(tblvet.vFirstname, ' ', tblvet.vLastname) AS vet_name,
                                       GROUP_CONCAT(DISTINCT tblpet.pet_Name) AS transaction_pets
                                FROM tblmedical_record
                                INNER JOIN tblpet ON tblmedical_record.pet_ID = tblpet.pet_ID
                                INNER JOIN tblclients ON tblpet.client_id = tblclients.client_id
                                LEFT JOIN tblappointment ON tblmedical_record.Appt_ID = tblappointment.Appt_ID
                                LEFT JOIN tblpet_services ON tblpet_services.pet_ID = tblpet.pet_ID
                                LEFT JOIN tblservices ON tblpet_services.service_id = tblservices.service_id
                                LEFT JOIN tblvet ON tblmedical_record.vet_ID = tblvet.vet_ID
                                LEFT JOIN tbltransaction ON tbltransaction.pet_ID = tblpet.pet_ID
                                WHERE tblmedical_record.Med_ID = $med_id
                                GROUP BY tblmedical_record.Med_ID";

                        $result = $con->query($sql);
                        if ($result && $result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            ?>
                            <h3>Medical Record Details</h3>
                            <form method="POST">
                                <h4>Add Products and Services for Transaction</h4>

                                
                                <h5>Services</h5>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Service Name</th>
                                            <th>Price</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $service_query = "SELECT * FROM tblservices WHERE status != 'Not Available'";
                                        $service_result = mysqli_query($con, $service_query);
                                        $cnt = 1;

                                        $associated_services = explode(',', $row['service_names']);

                                        while ($service = mysqli_fetch_array($service_result)) {
                                            $isChecked = in_array($service['ServiceName'], $associated_services) ? 'checked' : '';
                                            ?>
                                            <tr>
                                                <th scope="row"><?php echo $cnt; ?></th>
                                                <td><?php echo htmlspecialchars($service['ServiceName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($service['Cost'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><input type="checkbox" name="sids[]" value="<?php echo htmlspecialchars($service['service_id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isChecked; ?>></td>
                                            </tr>
                                            <?php
                                            $cnt++;
                                        }
                                        ?>
                                    </tbody>
                                </table>

                                
                                <h5>Products</h5>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Product Name</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Expiration Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $current_date = date('Y-m-d');
                                        $product_query = "SELECT * FROM tblproducts WHERE ExpirationDate >= '$current_date' OR ExpirationDate IS NULL";
                                        $product_result = mysqli_query($con, $product_query);
                                        $cnt = 1;
                                        while ($product = mysqli_fetch_array($product_result)) {
                                            ?>
                                            <tr>
                                                <th scope="row"><?php echo $cnt; ?></th>
                                                <td><?php echo htmlspecialchars($product['ProductName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <input type="number" name="quantities[<?php echo htmlspecialchars($product['ProductID'], ENT_QUOTES, 'UTF-8'); ?>]" min="1" step="any" placeholder="Enter quantity">
                                                </td>
                                                <td><?php echo $product['ExpirationDate'] ? htmlspecialchars($product['ExpirationDate'], ENT_QUOTES, 'UTF-8') : 'No Expiration'; ?></td>
                                                <td><input type="checkbox" name="pids[]" value="<?php echo htmlspecialchars($product['ProductID'], ENT_QUOTES, 'UTF-8'); ?>"></td>
                                            </tr>
                                            <?php
                                            $cnt++;
                                        }
                                        ?>
                                    </tbody>
                                </table>

                                <button type="submit">Submit Transaction</button>
                            </form>
                            <?php
                        } else {
                            echo "<p>Record not found.</p>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
        <?php
    } else {
        echo "<p>Record not found.</p>";
    }
}
?>
