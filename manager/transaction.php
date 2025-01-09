<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Manila'); // Set timezone to Manila
include('includes/dbconnection.php');

// Redirect to logout if user is not authenticated
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}
?>
<!DOCTYPE HTML>
<html>

<head>
    <title>ALVSC || Transaction</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <link href="css/font-awesome.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet" media="all">
    <link href="css/custom.css" rel="stylesheet">
    <!-- WOW.js for Animations -->
    <script src="js/wow.min.js"></script>
    <script>
        new WOW().init();
    </script>
    <style>
        .badge {
            padding: 3px 7px;
            border-radius: 5px;
            color: #fff;
            font-weight: bold;
        }

        .badge-paid {
            background-color: #8bc34a;
        }

        .badge-unpaid {
            background-color: #dc3545;
        }

        .badge-default {
            background-color: #6c757d;
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
                    <div class="d-flex justify-content-between">
                        <div class="">
                            <h4 class="mb-2">Transaction History:</h4>
                            <form class="form-inline" style="margin-bottom: 10px;" method="get">
                                <div class="d-flex justify-content-start gap-2">
                                    <input type="search" name="search" class="form-control" placeholder="Search...">
                                    <button class="btn btn-info" type="submit"><i class="fa fa-search"></i></button>
                                </div>
                            </form>
                        </div>

                        <div>
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Add Transaction
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#oldClientModal">
                                            Old Client
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="new-client-products.php">Walk In</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Old Client Selection Modal -->
                    <div class="modal fade" id="oldClientModal" tabindex="-1" aria-labelledby="oldClientModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <!-- Modal Header -->
                                <div class="modal-header">
                                    <h5 class="modal-title" id="oldClientModalLabel">Select Existing Client</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <!-- Modal Body -->
                                <div class="modal-body">
                                    <?php
                                    // Fetch all clients from the database
                                    $clientQuery = "SELECT client_id, Name, ContactNumber, Email FROM tblclients ORDER BY Name ASC";
                                    $clientResult = mysqli_query($con, $clientQuery);
                                    if ($clientResult && mysqli_num_rows($clientResult) > 0) {
                                        echo '<table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Client Name</th>
                                                        <th>Contact Number</th>
                                                        <th>Email</th>
                                                        <th>Select</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                        $clientCount = 1;
                                        while ($client = mysqli_fetch_assoc($clientResult)) {
                                            echo '<tr>
                                                    <td>' . $clientCount++ . '</td>
                                                    <td>' . htmlspecialchars($client['Name'], ENT_QUOTES, 'UTF-8') . '</td>
                                                    <td>' . htmlspecialchars($client['ContactNumber'], ENT_QUOTES, 'UTF-8') . '</td>
                                                    <td>' . htmlspecialchars($client['Email'], ENT_QUOTES, 'UTF-8') . '</td>
                                                    <td>
                                                        <button type="button" class="btn btn-success btn-sm select-client-btn" data-client-id="' . $client['client_id'] . '" data-client-name="' . htmlspecialchars($client['Name'], ENT_QUOTES, 'UTF-8') . '">
                                                            Select
                                                        </button>
                                                    </td>
                                                </tr>';
                                        }
                                        echo '</tbody></table>';
                                    } else {
                                        echo '<p class="text-center">No clients found.</p>';
                                    }
                                    ?>
                                </div>

                                <!-- Modal Footer -->
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction History Table -->
                    <table class="table table-bordered mt-3">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Client Name</th>
                                <th>Date and Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "";
                            if (isset($_GET['search']) && !empty($_GET['search'])) {
                                $search_query = mysqli_real_escape_string($con, $_GET['search']);
                                $query = "SELECT DISTINCT
                                    tblclients.Name,
                                    tbltransaction.Trans_Code,
                                    tbltransaction.Transaction_Date,
                                    tbltransaction.status
                                FROM
                                    tblclients
                                    JOIN tbltransaction ON tblclients.client_id = tbltransaction.client_id
                                WHERE
                                    tblclients.Name LIKE '%$search_query%' OR
                                    tbltransaction.Trans_Code LIKE '%$search_query%'
                                ORDER BY
                                    tbltransaction.Transaction_Date DESC";
                            } else {
                                $query = "SELECT DISTINCT
                                    tblclients.Name,
                                    tbltransaction.Trans_Code,
                                    tbltransaction.Transaction_Date,
                                    tbltransaction.status
                                FROM
                                    tblclients
                                    JOIN tbltransaction ON tblclients.client_id = tbltransaction.client_id
                                ORDER BY
                                    tbltransaction.Transaction_Date DESC";
                            }

                            $result = mysqli_query($con, $query);
                            if (mysqli_num_rows($result) > 0) {
                                $cnt = 1;
                                while ($row = mysqli_fetch_array($result)) {
                                    echo '<tr>';
                                    echo '<th scope="row">' . $cnt . '</th>';
                                    echo '<td>' . htmlspecialchars($row['Name']) . '</td>';

                                    $Transaction_Date = $row['Transaction_Date'];
                                    $dateTimeObj = new DateTime($Transaction_Date, new DateTimeZone('UTC'));
                                    $dateTimeObj->setTimezone(new DateTimeZone('Asia/Manila'));
                                    $formattedDate = $dateTimeObj->format('m/d/Y');
                                    $formattedTime = $dateTimeObj->format('h:i:s A');
                                    echo '<td>' . $formattedDate . ' - ' . $formattedTime . '</td>';

                                    $status = $row['status'];
                                    $badgeClass = '';
                                    if ($status == 'Paid') {
                                        $badgeClass = 'badge-paid';
                                    } elseif ($status == 'Unpaid') {
                                        $badgeClass = 'badge-unpaid';
                                    } else {
                                        $badgeClass = 'badge-default';
                                    }
                                    echo '<td><span class="badge ' . $badgeClass . '">' . htmlspecialchars($status) . '</span></td>';

                                    echo '<td>
                        <a href="pay-transaction.php?transid=' . urlencode($row['Trans_Code']) . '" class="btn btn-success btn-sm">Pay</a>
                        <a href="view.php?transid=' . urlencode($row['Trans_Code']) . '" class="btn btn-info btn-sm">View</a>
                      </td>';
                                    echo '</tr>';
                                    $cnt++;
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center">No transactions found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-default" onclick="history.back()">Previous</button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper (Ensure it's included) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- Custom JavaScript for Handling Client Selection -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Handle client selection from the modal
            const selectButtons = document.querySelectorAll('.select-client-btn');
            
            selectButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const clientID = this.getAttribute('data-client-id');
                    const clientName = this.getAttribute('data-client-name');
                    
                    // Optional: Display a confirmation dialog
                    const confirmSelection = confirm(`Select client: ${clientName}?`);
                    if (confirmSelection) {
                        // Redirect to add-client-products.php with the selected client ID
                        window.location.href = `add-client-products.php?addid=${clientID}`;
                    }
                });
            });
        });
    </script>
</body>

</html>
