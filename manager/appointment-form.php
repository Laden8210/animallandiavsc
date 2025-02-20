<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

if (isset($_POST['addAppointment'])) {
    try {
        mysqli_begin_transaction($con); 

        
        if (!empty($_POST['client_id'])) {
            $client_id = mysqli_real_escape_string($con, $_POST['client_id']);
        } else {
            $Name = mysqli_real_escape_string($con, $_POST['Name']);
            $clientQuery = "INSERT INTO tblclients (Name) VALUES ('$Name')";
            if (!mysqli_query($con, $clientQuery)) {
                throw new Exception('Error inserting client: ' . mysqli_error($con));
            }
            $client_id = mysqli_insert_id($con);
        }

       
        if (!empty($_POST['AptDate']) && !empty($_POST['AptTime'])) {
            $appointmentDate = mysqli_real_escape_string($con, $_POST['AptDate']);
            $appointmentTime = mysqli_real_escape_string($con, $_POST['AptTime']);
        } else {
            throw new Exception('Appointment date and time are required.');
        }

      
        $petNames = $_POST['pname'];
        $breeds = $_POST['Breed'];
        $pGender = $_POST['pGender'];
        $serviceGroups = $_POST['service_id'];

        foreach ($petNames as $index => $petName) {
            $petName = mysqli_real_escape_string($con, $petName);
            $breed = mysqli_real_escape_string($con, $breeds[$index]);
            $petGender = mysqli_real_escape_string($con, $pGender[$index]);

            
            $petQuery = "INSERT INTO tblpet (pet_Name, Breed, pGender, client_id) 
                         VALUES ('$petName', '$breed', '$petGender', '$client_id')";
            if (!mysqli_query($con, $petQuery)) {
                throw new Exception('Error inserting pet: ' . mysqli_error($con));
            }
            $pet_id = mysqli_insert_id($con);

            
            $appointmentQuery = "INSERT INTO tblappointment (client_id, pet_ID, Appointment_Date, Appointment_Time, Status) 
                                 VALUES ('$client_id', '$pet_id', '$appointmentDate', '$appointmentTime', '0')";
            if (!mysqli_query($con, $appointmentQuery)) {
                throw new Exception('Error inserting appointment: ' . mysqli_error($con));
            }

            
            $Appt_ID = mysqli_insert_id($con);

            
            foreach ($serviceGroups[$index] as $service_id) {
                $service_id = mysqli_real_escape_string($con, $service_id);
                $insertService = "INSERT INTO tblpet_services (Appt_ID, pet_ID, Service_ID) 
                                  VALUES ('$Appt_ID', '$pet_id', '$service_id')";
                if (!mysqli_query($con, $insertService)) {
                    throw new Exception('Error inserting appointment service: ' . mysqli_error($con));
                }
            }

            
$medicalRecordQuery = "INSERT INTO tblmedical_record (client_id, service_id, Appt_ID, pet_ID) 
VALUES ('$client_id', '$service_id', '$Appt_ID', '$pet_id')";
if (!mysqli_query($con, $medicalRecordQuery)) {
throw new Exception('Error inserting medical record: ' . mysqli_error($con));
}

        }

        mysqli_commit($con);
        echo "<script>alert('Appointment(s) added successfully.');</script>";
    } catch (Exception $e) {
        mysqli_rollback($con);
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}


$serviceResult = mysqli_query($con, "SELECT * FROM tblservices WHERE status != 'Not Available'");
?>


<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALVSC || Appointment Availability</title>
    <link href="css/bootstrap.css" rel="stylesheet" type="text/css" />
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <style>
         body {
            background-color: #f4f7fa;
        }
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin: 20px ;
        }
        .calendar-day {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            position: relative;
            cursor: pointer;
            background-color: #ffffff;
            transition: background-color 0.3s;
            transform: translateX(45px);
        }
        .calendar-day:hover {
            background-color: #e3f2fd;
        }
        .day-name {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .slot-count {
            position: absolute;
            bottom: 5px;
            right: 5px;
            font-size: 0.8em;
            background-color: #e0f7fa;
            padding: 2px 5px;
            border-radius: 3px;
            color: #00796b;
        }
        .header {
            grid-column: span 7;
            text-align: center;
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .month-nav {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .month-nav button {
            padding: 5px 10px;
            font-size: 1em;
        }
        .selected-services {
    margin-top: 10px;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    background-color: #17a2b8; 
    color: white;
    border-radius: 5px;
}
.calendar-day.disabled {
    color: #ccc; 
    pointer-events: none;
    cursor: not-allowed;
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
                    <h4>Appointment Availability:</h4>
                <div class="month-nav">
                    <button id="prevMonth" class="btn btn-secondary">Previous</button>
                    <div id="monthDisplay" class="header"></div>
                    <button id="nextMonth" class="btn btn-secondary">Next</button>
                </div>
                <div id="calendar" class="calendar"></div>

                <div class="modal fade" id="addAppointmentModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Appointment</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="post">
                            <div class="form-group">
    <label for="client_id">Client Name</label>
    <select class="form-control" name="client_id" id="client_id" required>
        <option value="">Select Client</option>
        <?php
        
        $clientQuery = "SELECT client_id, Name FROM tblclients";
        $clientResult = mysqli_query($con, $clientQuery);
        while ($client = mysqli_fetch_assoc($clientResult)) {
            echo "<option value='" . $client['client_id'] . "'>" . $client['Name'] . "</option>";
        }
        ?>
    </select>
</div>

                                    <div id="pet-section">
                                     <div class="form-group pet-group">
                                     <label for="pname[]">Pet Name</label>
                                    <input type="text" class="form-control" name="pname[]" required placeholder="Enter pet name">
        
                                     <label for="Breed[]">Breed</label>
                                     <input type="text" class="form-control" name="Breed[]" required placeholder="Enter pet breed">
        
                                     <label for="pGender[]">Pet Gender</label>
                                     <select class="form-control" name="pGender[]" id="pGender[]" required>
                                     <option value="" disabled selected>Select pet gender</option>
                                     <option value="male">male</option>
                                     <option value="female">female</option>
                                     </select>
        
                                     <label for="service_id">Services for this Pet</label>
<div id="service-container">
    <div class="service-entry">
        <select name="service_id[0][]" class="form-control" multiple required>
            <option value="" disabled selected>(Select service)</option>
            <?php 
            while ($row = $serviceResult->fetch_assoc()) { 
            ?>
                <option value="<?php echo $row['service_id']; ?>"><?php echo $row['ServiceName']; ?></option>
            <?php 
            } 
            ?>
        </select>
    </div>
</div>

        </div>
    </div>

<script>

<div id="selectedServices" class="selected-services">
</div>

<script>
   
<div id="selectedServices" class="selected-services">
</div>

<script>
function updateLabels() {
    const select = document.getElementById('serviceSelect');
    const selectedServicesDiv = document.getElementById('selectedServices');
    const selectedOptions = Array.from(select.selectedOptions);
    
    selectedServicesDiv.innerHTML = '';

    selectedOptions.forEach(option => {
        const label = document.createElement('span');
        label.textContent = option.text; 
        label.className = 'badge badge-info m-1'; 
        selectedServicesDiv.appendChild(label);
    });
}
</script>
                                    <div class="form-group">
                                        <label for="AptDate">Appointment Date</label>
                                        <input type="date" class="form-control" name="AptDate" required>
                                    </div>
                                    <div class="form-group">
    <label for="AptTime">Appointment Time</label>
    <select name="AptTime" class="form-control" required>
        <option value="">Select Time</option>
                                            <?php 
                                            $start_time = strtotime('08:30');
                                            $end_time = strtotime('16:30');
                                            for ($i = $start_time; $i <= $end_time; $i += 30 * 60) {
                                                $time = date('H:i', $i);
                                                echo "<option value='$time'>$time</option>";
                                            }
                                            
                                            
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary" name="addAppointment">Add Appointment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <script>

document.addEventListener('DOMContentLoaded', () => {
    const timeDropdown = document.querySelector('select[name="AptTime"]');

    timeDropdown.addEventListener('change', (event) => {
        const selectedOption = event.target.selectedOptions[0];
        if (selectedOption.disabled) {
            alert('This time slot is fully booked. Please choose another time.');
        }
    });
});


document.addEventListener('DOMContentLoaded', function () {
    const calendarElement = document.getElementById('calendar');
    const monthDisplay = document.getElementById('monthDisplay');
    const totalSlots = 30;
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    let selectedDate = '';
    const today = new Date();
    today.setHours(0, 0, 0, 0); 

    function initCalendar() {
        buildCalendar(currentMonth, currentYear);
    }

    function buildCalendar(month, year) {
        calendarElement.innerHTML = '';
        monthDisplay.textContent = `${year} - ${new Date(year, month).toLocaleString('default', { month: 'long' })}`;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        daysOfWeek.forEach(day => {
            const dayHeader = document.createElement('div');
            dayHeader.className = 'calendar-day day-name';
            dayHeader.textContent = day;
            calendarElement.appendChild(dayHeader);
        });

        let day = 1;
        for (let i = 0; i < 6; i++) {
            for (let j = 0; j < 7; j++) {
                const cell = document.createElement('div');
                cell.classList.add('calendar-day');

                if (i === 0 && j < firstDay) {
                    cell.textContent = '';
                } else if (day > daysInMonth) {
                    cell.textContent = '';
                } else {
                    const currentDate = new Date(year, month, day);
                    currentDate.setHours(0, 0, 0, 0); 
                    const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

                    cell.textContent = day;
                    cell.setAttribute('data-date', dateString);

                    if (currentDate >= today) {
                        
                        fetchAvailableSlots(cell, dateString);
                        cell.addEventListener('click', function () {
                            selectedDate = cell.getAttribute('data-date');
                            $('#addAppointmentModal').modal('show');
                            document.querySelector('input[name="AptDate"]').value = selectedDate;
                        });
                    } else {
                        
                        cell.classList.add('disabled');
                    }

                    day++;
                }
                calendarElement.appendChild(cell);
            }
        }
    }
                        

                        function fetchAvailableSlots(cell, date) {
                            const xhr = new XMLHttpRequest();
                            xhr.open('GET', `fetch_slots.php?date=${date}`, true);
                            xhr.onload = function () {
                                if (this.status === 200) {
                                    const data = JSON.parse(this.responseText);
                                    const totalAppointments = data.total_appointments;
                                    const remainingSlots = totalSlots - totalAppointments;
                                    const slotCount = document.createElement('div');
                                    slotCount.className = 'slot-count';
                                    slotCount.textContent = `${remainingSlots} slots available`;
                                    cell.appendChild(slotCount);
                                }
                            };
                            xhr.send();
                        }

                        document.getElementById('prevMonth').addEventListener('click', function() {
                            if (currentMonth === 0) {
                                currentMonth = 11;
                                currentYear--;
                            } else {
                                currentMonth--;
                            }
                            buildCalendar(currentMonth, currentYear);
                        });

                        document.getElementById('nextMonth').addEventListener('click', function() {
                            if (currentMonth === 11) {
                                currentMonth = 0;
                                currentYear++;
                            } else {
                                currentMonth++;
                            }
                            buildCalendar(currentMonth, currentYear);
                        });

                        initCalendar();
                    });
                </script>
            </div>
        </div>
    </div>
</body>
</html>