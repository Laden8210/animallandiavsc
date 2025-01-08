<?php
session_start();
error_reporting(1);
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

function isAppointmentTaken($appointmentDate, $appointmentTime, $Appt_ID)
{
    global $con;

    $query = "SELECT COUNT(*) AS appointmentCount FROM tblappointment 
              WHERE Appointment_Date = '$appointmentDate' 
              AND Appointment_Time = '$appointmentTime' 
              AND Appt_ID != '$Appt_ID'";

    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);

    return $row['appointmentCount'] > 0;
}

if (isset($_POST['editAppointment'])) {
    $Appt_ID = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $appointmentDate = mysqli_real_escape_string($con, $_POST['AptDate']);
    $appointmentTime = mysqli_real_escape_string($con, $_POST['AptTime']);
    $userID = $_SESSION['id']; // Current user

    if (isAppointmentTaken($appointmentDate, $appointmentTime, $Appt_ID)) {
        echo "<script>alert('This scheduled time is already taken. Please choose another time.');</script>";
    } else {
        $editQuery = "UPDATE tblappointment 
                      SET Appointment_Date = '$appointmentDate', 
                          Appointment_Time = '$appointmentTime', 
                          Status = '2', 
                          userID = '$userID'
                      WHERE Appt_ID = '$Appt_ID'";

        if (mysqli_query($con, $editQuery)) {
            echo "<script>alert('Appointment rescheduled successfully!');</script>";
        } else {
            echo "<script>alert('Error rescheduling appointment: " . mysqli_error($con) . "');</script>";
        }
    }
}


if (isset($_POST['updateStatus'])) {
    $Appt_ID = mysqli_real_escape_string($con, $_POST['appointment_id']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $userID = $_SESSION['id'];

    $statusQuery = ($status == '3')
        ? "UPDATE tblappointment 
           SET Status = '$status', userID = '$userID', Appointment_Date = NULL, Appointment_Time = NULL 
           WHERE Appt_ID = '$Appt_ID'"
        : "UPDATE tblappointment 
           SET Status = '$status', userID = '$userID' 
           WHERE Appt_ID = '$Appt_ID'";

    if (mysqli_query($con, $statusQuery)) {
        echo "<script>alert('Status updated successfully.');</script>";
    } else {
        echo "<script>alert('Error updating status: " . mysqli_error($con) . "');</script>";
    }
}

$serviceQuery = "SELECT service_id, ServiceName FROM tblservices";
$serviceResult = mysqli_query($con, $serviceQuery);

?>


<!DOCTYPE HTML>
<html>

<head>
    <title>ALVSC || All Appointment</title>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/bootstrap.js"></script>
</head>
<style>
    .button-container {
        display: flex;
        gap: 2px;
    }

    .custom-btn {
        font-size: 12px;
        padding: 5px 2px;
        min-width: 14px;
    }

    .badge-secondary {
        background-color: grey;
        color: white;
    }

    .badge-danger {
        background-color: red;
        color: white;
    }

    .badge-warning {
        background-color: yellow;
        color: black;
    }

    .badge-success {
        background-color: #8bc34a;
        color: white;
    }
</style>

<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>
        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables">
                    <h4>Appointment List:</h4>
                    <form class="form-inline" style="margin-bottom: 10px;">
                        <input type="search" name="search" class="form-control" placeholder="Search customer name...">
                        <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
                    </form>
                    <div class="pull-right">
                        <a href="appointment-form.php" class="btn btn-primary">
                            Add Appointment
                        </a>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Appointment ID</th>
                                <th>Client Name</th>
                                <th>Address</th>
                                <th>Pet Name</th>
                                <th>Contact Number</th>
                                <th>Schedule</th>
                                <th>Status</th>
                                <th>Approved By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $limit = 5;
                            $offset = isset($_GET['page']) ? ($_GET['page'] - 1) * $limit : 0;

                            $sql = isset($_GET['search']) ?
                                "SELECT tblappointment.*, tblclients.Name, tblclients.Address, tblclients.ContactNumber, tblpet.pet_Name, tblperson.Firstname 
 FROM tblappointment 
 LEFT JOIN tblclients ON tblappointment.client_id = tblclients.client_id 
 LEFT JOIN tblpet ON tblappointment.pet_ID = tblpet.pet_ID 
 LEFT JOIN tbluser ON tblappointment.userID = tbluser.userID 
 LEFT JOIN tblperson ON tbluser.PersonID = tblperson.PersonID 
 WHERE tblclients.Name LIKE '%" . $_GET['search'] . "%' 
 ORDER BY tblappointment.Appt_ID DESC 
 LIMIT $offset, $limit" :
                                "SELECT tblappointment.*, tblclients.Name, tblclients.Address, tblclients.ContactNumber, tblpet.pet_Name, tblperson.Firstname 
 FROM tblappointment 
 LEFT JOIN tblclients ON tblappointment.client_id = tblclients.client_id 
 LEFT JOIN tblpet ON tblappointment.pet_ID = tblpet.pet_ID 
 LEFT JOIN tbluser ON tblappointment.userID = tbluser.userID 
 LEFT JOIN tblperson ON tbluser.PersonID = tblperson.PersonID  
 ORDER BY tblappointment.Appt_ID DESC 
 LIMIT $offset, $limit";



                            $ret = mysqli_query($con, $sql);


                            while ($row = mysqli_fetch_array($ret)) {
                                $Status = $row['Status'];
                            ?>
                                <tr>
                                    <td><?php echo $row['Appt_ID']; ?></td>
                                    <td><?php echo $row['Name'] ?? 'No Client'; ?></td>
                                    <td><?php echo $row['Address'] ?? 'No Address'; ?></td>
                                    <td><?php echo $row['pet_Name'] ?? 'No Pet'; ?></td>
                                    <td><?php echo $row['ContactNumber'] ?? 'No Contact'; ?></td>
                                    <td>
                                        <?php
                                        $Appointment_Date = $row['Appointment_Date'];
                                        $Appointment_Time = $row['Appointment_Time'];
                                        $datetime = $Appointment_Date . ' ' . $Appointment_Time;
                                        $dateTimeObj = new DateTime($datetime);
                                        echo $dateTimeObj->format('m/d/Y h:i:s A');
                                        ?>
                                    </td>

                                    <td class="text-center">
                                        <?php
                                        if ($Status === null || $Status === '') {
                                            echo "<span class='badge badge-secondary'>No Status</span>";
                                        } else {
                                            switch ($Status) {
                                                case '1':
                                                    echo "<span class='badge badge-success'>Confirmed</span>";
                                                    break;
                                                case '2':
                                                    echo "<span class='badge badge-warning'>Reschedule</span>";
                                                    break;
                                                case '3':
                                                    echo "<span class='badge badge-danger'>Cancel</span>";
                                                    break;
                                                default:
                                                    echo "<span class='badge badge-secondary'>Pending</span>";
                                                    break;
                                            }
                                        }
                                        ?>

                                    </td>

                                    <td><?php echo $row['Firstname'] ?? 'Not Approved'; ?></td>
                                    <td>
                                        <div class="button-container">
                                            <button class="btn btn-info custom-btn view-details-btn"
                                                data-id="<?php echo $row['Appt_ID']; ?>"
                                                data-toggle="modal"
                                                data-target="#viewDetailsModal">
                                                View Details
                                            </button>
                                            <button class="btn btn-warning custom-btn edit-btn" data-id="<?php echo $row['Appt_ID']; ?>" data-date="<?php echo $row['Appointment_Date']; ?>" data-time="<?php echo $row['Appointment_Time']; ?>" data-toggle="modal" data-target="#editAppointmentModal">Reschedule</button>
                                            <button class="btn btn-primary custom-btn update-status-btn" data-id="<?php echo $row['Appt_ID']; ?>" data-status="<?php echo $Status; ?>" data-toggle="modal" data-target="#updateStatusModal">Update Status</button>
                                        </div>

                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-default" onclick="history.back()">Previous</button>
            </div>
        </div>
    </div>
    <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="appointment_id" id="updateAppointmentID">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" class="form-control" id="statusSelect" required>
                                <option value="0">Pending</option>
                                <option value="1">Confirmed</option>
                                <option value="3">Cancel</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" name="updateStatus">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div id="editAppointmentModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Appointment</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="appointment_id" id="appointment_id">
                        <div class="form-group">
                            <label for="edit_AptDate">Appointment Date</label>
                            <input type="date" name="AptDate" id="edit_AptDate" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_AptTime">Appointment Time</label>
                            <input type="time" name="AptTime" id="edit_AptTime" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="editAppointment" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="modal fade" id="viewDetailsModal" tabindex="-1" role="dialog" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDetailsModalLabel">Appointment Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped table-bordered">
                        <tbody id="appointmentDetailsContent">
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('statusSelect').addEventListener('change', function() {
            var selectedStatus = this.value;

            if (selectedStatus == '2') {
                var appointmentId = 123;
                var appointmentDate = '2024-12-12';
                var appointmentTime = '14:00';

                document.getElementById('appointment_id').value = appointmentId;
                document.getElementById('edit_AptDate').value = appointmentDate;
                document.getElementById('edit_AptTime').value = appointmentTime;

                $('#editAppointmentModal').modal('show');
            }
        });

        $(document).ready(function() {
            $('.status-btn').click(function() {
                var apptId = $(this).data('id');
                var status = $(this).data('status');
                $('#statusApptId').val(apptId);
                $('#status').val(status);
            });

            $('.edit-btn').click(function() {
                var apptId = $(this).data('id');
                var date = $(this).data('date');
                var time = $(this).data('time');
                $('#appointment_id').val(apptId);
                $('#edit_AptDate').val(date);
                $('#edit_AptTime').val(time);
            });
        });

        $(document).on('click', '.update-status-btn', function() {
            var appointmentId = $(this).data('id');
            var status = $(this).data('status');
            $('#updateAppointmentID').val(appointmentId);
            $('#statusSelect').val(status);
        });

        $(document).on('click', '.view-details-btn', function() {
            var appointmentID = $(this).data('id');

            $.ajax({
                url: 'get_appointment_details.php',
                type: 'POST',
                data: {
                    appointment_id: appointmentID
                },
                success: function(response) {
                    $('#appointmentDetailsContent').html(response);
                },
                error: function() {
                    alert('Failed to retrieve appointment details.');
                }
            });
        });

        document.getElementById('statusSelect').addEventListener('change', function() {
            var selectedStatus = this.value;
            if (selectedStatus === '3') {
                $('#edit_AptDate').val('');
                $('#edit_AptTime').val('');
            }
        });
    </script>
    <div class="text-center">
        <?php

        $total_pages = ceil(mysqli_num_rows(mysqli_query($con, "SELECT * FROM tblclients")) / $limit);

        if ($total_pages > 1) {
            $page = isset($_GET['page']) ? $_GET['page'] : 1;
            echo "<ul class='pagination'>";
            if ($page > 1) {
                echo "<li><a href='?page=" . ($page - 1) . "'>&laquo;</a></li>";
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                echo "<li " . (($page == $i) ? "class='active'" : "") . "><a href='?page=$i'>$i</a></li>";
            }
            if ($page < $total_pages) {
                echo "<li><a href='?page=" . ($page + 1) . "'>&raquo;</a></li>";
            }
            echo "</ul>";
        }
        ?>
    </div>
</body>

</html>
</body>