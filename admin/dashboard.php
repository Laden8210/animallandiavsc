<?php
session_start();
error_reporting(0);
include('../includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0 || !isset($_SESSION['rID']) || $_SESSION['rID'] != '1') {
    header('Location: ../logout.php');
    exit();
}
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALVSC | Admin Dashboard</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel='stylesheet' />

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>

    <style>
        /* Custom Styles for Modal */
        #calendar {
            width: 100%;
            max-width: 800px;
            height: 100vh;
        }

        .calendar-container {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .stats-card {
            background-color: #f8f9fa;

            border-radius: 10px;

            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);

            transition: transform 0.3s ease;

        }

        .stats-card:hover {
            transform: translateY(-5px);
       
        }

        .stats-card .content {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;

        }

        .stats-card .content .label {
            font-size: 1.2rem;
            color: #555;
            margin-right: 10px;

        }

        .stats-card .content .icon {
            font-size: 2rem;

            color: #007bff;

        }

        .stats-card .value {
            font-size: 2.5rem;
  
            font-weight: bold;
            color: #333;

        }

        .stats-card h4 {
            font-size: 1.2rem;
            color: #555;
        }

        .row.g-4 {
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 15px;
            }
        }
    </style>
</head>

<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>

        <div id="page-wrapper">
            <div class="main-page">
                <div class="row g-4">
                    <!-- Clients Card -->
                    <div class="col-md-4 col-sm-12">
                        <div class="stats-card">
                            <?php
                            $query1 = mysqli_query($con, "SELECT * FROM tblclients");
                            $totalclie = mysqli_num_rows($query1);
                            ?>
                 
                            <div>
                                <label><?php echo $totalclie; ?></label>
                            </div>
                            <div>
                                <h4>Client</h4>
                            </div>
                            <i class="fas fa-user"></i>
                        </div>
                    </div>

                    <!-- Appointment Card -->
                    <div class="col-md-4 col-sm-12">
                        <div class="stats-card">
                            <?php
                            $query2 = mysqli_query($con, "SELECT * FROM tblappointment");
                            $totalappointment = mysqli_num_rows($query2);
                            ?>
                            <i class="fas fa-calendar-alt"></i>
                            <div>
                                <label><?php echo $totalappointment; ?></label>
                            </div>
                            <div>
                                <h4>Appointment</h4>
                            </div>
                        </div>
                    </div>

                    <!-- User Card -->
                    <div class="col-md-4 col-sm-12">
                        <div class="stats-card">
                            <?php
                            $query3 = mysqli_query($con, "SELECT * FROM tbluser");
                            $totaluser = mysqli_num_rows($query3);
                            ?>
                            <i class="fas fa-users"></i>
                            <div>
                                <label><?php echo $totaluser; ?></label>
                            </div>
                            <div>
                                <h4>User</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Product Card -->
                    <div class="col-md-4 col-sm-12">
                        <div class="stats-card">
                            <?php
                            $query4 = mysqli_query($con, "SELECT * FROM tblproducts");
                            $totalprod = mysqli_num_rows($query4);
                            ?>
                            <i class="fas fa-box"></i>
                            <div>
                                <label><?php echo $totalprod; ?></label>
                            </div>
                            <div>
                                <h4>Product</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Service Card -->
                    <div class="col-md-4 col-sm-12">
                        <div class="stats-card">
                            <?php
                            $query5 = mysqli_query($con, "SELECT * FROM tblservices");
                            $totalser = mysqli_num_rows($query5);
                            ?>
                            <i class="fas fa-paw"></i>
                            <div>
                                <label><?php echo $totalser; ?></label>
                            </div>
                            <div>
                                <h4>Services</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Card -->
                    <div class="col-md-4 col-sm-12">
                        <div class="stats-card">
                            <?php
                            $query6 = mysqli_query($con, "SELECT DISTINCT Trans_Code FROM tbltransaction");
                            $totaltran = mysqli_num_rows($query6);
                            ?>
                            <i class="fas fa-handshake"></i>
                            <div>
                                <label><?php echo $totaltran; ?></label>
                            </div>
                            <div>
                                <h4>Transaction</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar Section -->
                <div class="calendar-container">
                    <div class="m-auto w-75 stats-card">
                        <div class="card-body">
                            <h4>Appointment Schedule:</h4>
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Bootstrap Modal for Appointment Details -->
        <div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="appointmentModalLabel">Appointments on <span id="modalDate"></span></h5>
                        <!-- Close button for the modal -->
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Add a message if no appointments are scheduled -->
                        <ul id="appointmentList">
                            <li>No appointments for this date.</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <!-- Close button to dismiss the modal -->
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js" integrity="sha512-hUhvpC5f8cgc04OZb55j0KNGh4eh7dLxd/dPSJ5VyzqDWxsayYbojWyl5Tkcgrmb/RVKCRJI1jNlRbVP4WWC4w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var appointmentModal = new bootstrap.Modal(document.getElementById('appointmentModal'));
            var appointmentList = document.getElementById("appointmentList");
            var modalDate = document.getElementById("modalDate");

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: [
                    <?php
                    $sql = "
                    SELECT tblappointment.Appointment_Date, tblappointment.Appointment_Time, tblclients.Name, tblpet.pet_Name
                    FROM tblappointment
                    JOIN tblclients ON tblappointment.client_id = tblclients.client_id 
                    JOIN tblpet ON tblappointment.pet_id = tblpet.pet_id  
                    ";
                    $result = mysqli_query($con, $sql);
                    while ($row = mysqli_fetch_assoc($result)) {
                        $datetime = $row['Appointment_Date'] . 'T' . $row['Appointment_Time'];
                        echo "{
                            title: '" . $row['Name'] . " - " . $row['pet_Name'] . "',
                            start: '" . $datetime . "',
                            date: '" . $row['Appointment_Date'] . "'
                        },";
                    }
                    ?>
                ],
                dateClick: function(info) {
                    appointmentList.innerHTML = '';
                    modalDate.innerText = info.dateStr;
                    var events = calendar.getEvents().filter(event => event.startStr.includes(info.dateStr));

                    if (events.length > 0) {
                        events.forEach(event => {
                            var li = document.createElement("li");
                            li.textContent = event.title + ' at ' + event.start.toLocaleTimeString();
                            appointmentList.appendChild(li);
                        });
                        appointmentModal.show();
                    }
                }
            });

            calendar.render();
        });
    </script>
</body>

</html>