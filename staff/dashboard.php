<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0 || !isset($_SESSION['rID']) || $_SESSION['rID'] != '3') {
    header('Location: ../logout.php'); 
    exit();
}

?>

<!DOCTYPE HTML>
<html>
<head>
    <title>ALVSC | Admin Dashboard</title>

    <script type="application/x-javascript">
        addEventListener("load", function() {
            setTimeout(hideURLbar, 0);
        }, false);

        function hideURLbar() {
            window.scrollTo(0, 1);
        }
    </script>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .leftCol {
             position: fixed;
             width: 100px;
             top: 0;
             bottom: 0;
        }
        .widget {
    padding: 10px;
    background-color: #f1f1f1;
    margin: 4px;
    border-radius: 5px;
    text-align: center;
    flex: 1;
    }
        .widget i {
            font-size: 50px;
            margin-bottom: 10px;
        }
        
        .stats-left h5,
.stats-left h4 {
    padding: 5px 20px; 
}

        .stats-right label {
            font-size: 24px;
            font-weight: bold;
        }
        
        .main-page {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .tables {
            display: flex;
            flex-direction: column;
            width: 80%;
            text-align: center;
        }

        .table-responsive {
            display: flex;
            flex-direction: column;
            width: 125%;
        }

        #calendar {
            width: 100%;
            max-width: 1000px; 
            margin: 0 auto;
        }
        #calendar {
        width: 100%;
        max-width: 1000px; 
        margin: 0 auto;
        background-color: #ffffff;
        padding: 20px; 
        border-radius: 8px; 
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
                <div class="row-one">
                    <div class="col-md-4 widget">
                        <?php 
                        $query1 = mysqli_query($con, "SELECT * FROM tblclients");
                        $totalclie = mysqli_num_rows($query1);
                        ?>
                        <i class="fas fa-user"></i>
                        <div class="stats-right">
                            <label><?php echo $totalclie; ?></label>
                        </div>
                        <div class="stats-left">
                            <h5></h5>
                            <h4>Client</h4>
                        </div> 
                    </div>
                    <div class="col-md-4 widget states-mdl">
                        <?php 
                        $query2 = mysqli_query($con, "SELECT * FROM tblappointment");
                        $totalappointment = mysqli_num_rows($query2);
                        ?>
                        <i class="fas fa-cat"></i>
                        <div class="stats-right">
                            <label><?php echo $totalappointment; ?></label>
                        </div>
                        <div class="stats-left">
                            <h5></h5>
                            <h4>Appointment</h4>
                        </div>   
                    </div>
                   
                    <div class="col-md-4 widget states-last">
                     <?php 
                     $query6 = mysqli_query($con, "SELECT DISTINCT Trans_Code FROM tbltransaction");
                     $totaltran = mysqli_num_rows($query6);
                     ?>
                     <i class="fas fa-handshake"></i>
                     <div class="stats-right">
                       <label><?php echo $totaltran; ?></label>
                         </div>
                         <div class="stats-left">
                         <h5></h5>
                       <h4>Transaction</h4>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
     <div class="main-page">
        <div class="tables">
            <div class="table-responsive bs-example widget-shadow">
                <br><h4>Appointment Schedule:</h4>
                <div id='calendar'></div>
            </div>
        </div>
    </div>

    <div id="appointmentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span id="closeModal" style="cursor: pointer;">&times;</span>
            <h4>Appointments on <span id="modalDate"></span></h4>
            <ul id="appointmentList"></ul>
        </div>
    </div>
</div>


<script src="js/jquery.min.js"></script>
<script src="js/moment.min.js"></script>
<script src="js/fullcalendar.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var modal = document.getElementById("appointmentModal");
    var closeModal = document.getElementById("closeModal");
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
                
                modal.style.display = "block";
            }
        }
    });

    closeModal.onclick = function() {
        modal.style.display = "none";
    };

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };

    calendar.render();
});
</script>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    width: 300px;
}

#closeModal {
    float: right;
    font-size: 20px;
}

</style>

</body>
</html>
