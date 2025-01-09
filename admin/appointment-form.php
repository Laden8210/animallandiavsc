<?php 
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/dbconnection.php');

// Redirect to logout if the user is not authenticated
if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

if (isset($_POST['addAppointment'])) {
    try {
        mysqli_begin_transaction($con); 

        // Handle Client Information
        if (!empty($_POST['client_id'])) {
            $client_id = mysqli_real_escape_string($con, $_POST['client_id']);
        } else {
            $Name = mysqli_real_escape_string($con, $_POST['Name']);
            // Basic validation for Name
            if (empty($Name)) {
                throw new Exception('Client name is required.');
            }
            $clientQuery = "INSERT INTO tblclients (Name) VALUES (?)";
            if ($stmt_client = $con->prepare($clientQuery)) {
                $stmt_client->bind_param("s", $Name);
                if (!$stmt_client->execute()) {
                    throw new Exception('Error inserting client: ' . $stmt_client->error);
                }
                $client_id = $stmt_client->insert_id;
                $stmt_client->close();
            } else {
                throw new Exception('Error preparing client insert statement: ' . $con->error);
            }
        }

        // Handle Appointment Date and Time
        if (!empty($_POST['AptDate']) && !empty($_POST['AptTime'])) {
            $appointmentDate = mysqli_real_escape_string($con, $_POST['AptDate']);
            $appointmentTime = mysqli_real_escape_string($con, $_POST['AptTime']);
        } else {
            throw new Exception('Appointment date and time are required.');
        }

        // Handle Pets and Services
        $petNames = $_POST['pname'];
        $breeds = $_POST['Breed'];
        $pGenders = $_POST['pGender'];
        $ages = $_POST['Age'];
        $species = $_POST['Species'];
        $birthdates = $_POST['Birthdate'];
        $colors = $_POST['Color'];
        $serviceGroups = $_POST['service_id'];

        foreach ($petNames as $index => $petName) {
            // Validate pet name
            if (empty($petName)) {
                throw new Exception('Pet name is required.');
            }

            $petName = mysqli_real_escape_string($con, $petName);
            $breed = mysqli_real_escape_string($con, $breeds[$index]);
            $petGender = mysqli_real_escape_string($con, $pGenders[$index]);
            $age = mysqli_real_escape_string($con, $ages[$index]);
            $specie = mysqli_real_escape_string($con, $species[$index]);
            $birthdate = mysqli_real_escape_string($con, $birthdates[$index]);
            $color = mysqli_real_escape_string($con, $colors[$index]);

            // Insert Pet with additional information
            $petQuery = "INSERT INTO tblpet (pet_Name, Breed, pGender, Age, Species, Birthdate, Color, client_id) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt_pet = $con->prepare($petQuery)) {
                $stmt_pet->bind_param("sssisssi", $petName, $breed, $petGender, $age, $specie, $birthdate, $color, $client_id);
                if (!$stmt_pet->execute()) {
                    throw new Exception('Error inserting pet: ' . $stmt_pet->error);
                }
                $pet_id = $stmt_pet->insert_id;
                $stmt_pet->close();
            } else {
                throw new Exception('Error preparing pet insert statement: ' . $con->error);
            }

            // Insert Appointment
            $appointmentQuery = "INSERT INTO tblappointment (client_id, pet_ID, Appointment_Date, Appointment_Time, Status) 
                                 VALUES (?, ?, ?, ?, '0')";
            if ($stmt_appt = $con->prepare($appointmentQuery)) {
                $stmt_appt->bind_param("iiss", $client_id, $pet_id, $appointmentDate, $appointmentTime);
                if (!$stmt_appt->execute()) {
                    throw new Exception('Error inserting appointment: ' . $stmt_appt->error);
                }
                $Appt_ID = $stmt_appt->insert_id;
                $stmt_appt->close();
            } else {
                throw new Exception('Error preparing appointment insert statement: ' . $con->error);
            }

            // Insert Services for Pet
            if (isset($serviceGroups[$index]) && is_array($serviceGroups[$index])) {
                foreach ($serviceGroups[$index] as $service_id) {
                    $service_id = mysqli_real_escape_string($con, $service_id);
                    
                    // Insert into tblpet_services
                    $insertService = "INSERT INTO tblpet_services (Appt_ID, pet_ID, service_id) 
                                      VALUES (?, ?, ?)";
                    if ($stmt_service = $con->prepare($insertService)) {
                        $stmt_service->bind_param("iii", $Appt_ID, $pet_id, $service_id);
                        if (!$stmt_service->execute()) {
                            throw new Exception('Error inserting appointment service: ' . $stmt_service->error);
                        }
                        $stmt_service->close();
                    } else {
                        throw new Exception('Error preparing pet_service insert statement: ' . $con->error);
                    }

                    // Insert Medical Record
                    $medicalRecordQuery = "INSERT INTO tblmedical_record (pet_ID, vet_ID, Appt_ID, diagnosis, treatment, notes, created_at, weight, temp) 
                                           VALUES (?, NULL, ?, '', '', '', CURDATE(), 0, 0)";
                    if ($stmt_medical = $con->prepare($medicalRecordQuery)) {
                        $stmt_medical->bind_param("ii", $pet_id, $Appt_ID);
                        if (!$stmt_medical->execute()) {
                            throw new Exception('Error inserting medical record: ' . $stmt_medical->error);
                        }
                        $stmt_medical->close();
                    } else {
                        throw new Exception('Error preparing medical record insert statement: ' . $con->error);
                    }
                }
            }
        }

        mysqli_commit($con);
        echo "<script>alert('Appointment(s) added successfully.');</script>";
    } catch (Exception $e) {
        mysqli_rollback($con);
        echo "<script>alert('Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "');</script>";
    }
}

// Fetch services available
$serviceResult = mysqli_query($con, "SELECT * FROM tblservices WHERE status != 'Not Available'");
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALVSC || Appointment Availability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <link href="css/font-awesome.css" rel="stylesheet">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: #f4f7fa;
        }
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin: 20px;
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
            /* Removed transform: translateX(45px); to align calendar correctly */
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
            align-items: center;
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
            margin-right: 5px;
            margin-bottom: 5px;
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

                    <!-- Add Appointment Modal -->
                    <div class="modal fade" id="addAppointmentModal" tabindex="-1" role="dialog" aria-labelledby="addAppointmentModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addAppointmentModalLabel">Add Appointment</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post">
                                    <div class="modal-body">
                                        <!-- Client Selection -->
                                        <div class="form-group">
                                            <label for="client_id">Client Name</label>
                                            <select class="form-control" name="client_id" id="client_id">
                                                <option value="">Select Client</option>
                                                <?php
                                                $clientQuery = "SELECT client_id, Name FROM tblclients";
                                                $clientResult = mysqli_query($con, $clientQuery);
                                                while ($client = mysqli_fetch_assoc($clientResult)) {
                                                    echo "<option value='" . htmlspecialchars($client['client_id'], ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($client['Name'], ENT_QUOTES, 'UTF-8') . "</option>";
                                                }
                                                ?>
                                                <option value="">Add New Client</option>
                                            </select>
                                        </div>

                                        <!-- Add New Client Option -->
                                        <div class="form-group" id="newClientSection" style="display: none;">
                                            <label for="Name">New Client Name</label>
                                            <input type="text" class="form-control" name="Name" id="Name" placeholder="Enter client name">
                                        </div>

                                        <!-- Pet Information -->
                                        <div id="pet-section">
                                            <div class="pet-group form-group">
                                                <label for="pname[]">Pet Name</label>
                                                <input type="text" class="form-control" name="pname[]" required placeholder="Enter pet name">

                                                <label for="Breed[]">Breed</label>
                                                <input type="text" class="form-control" name="Breed[]" required placeholder="Enter pet breed">

                                                <label for="pGender[]">Pet Gender</label>
                                                <select class="form-control" name="pGender[]" required>
                                                    <option value="" disabled selected>Select pet gender</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Other">Other</option>
                                                </select>

                                                <label for="Age[]">Age (Years)</label>
                                                <input type="number" min="0" class="form-control" name="Age[]" required placeholder="Enter pet age">

                                                <label for="Species[]">Species</label>
                                                <input type="text" class="form-control" name="Species[]" required placeholder="Enter pet species">

                                                <label for="Birthdate[]">Birthdate</label>
                                                <input type="date" class="form-control" name="Birthdate[]" required>

                                                <label for="Color[]">Color</label>
                                                <input type="text" class="form-control" name="Color[]" required placeholder="Enter pet color">

                                                <label for="service_id[]">Services for this Pet</label>
                                                <div class="service-container">
                                                    <div class="service-entry">
                                                        <select name="service_id[0][]" class="form-control serviceSelect" multiple required>
                                                            <?php 
                                                            // Reset the pointer to the beginning
                                                            mysqli_data_seek($serviceResult, 0);
                                                            while ($service = mysqli_fetch_assoc($serviceResult)) { 
                                                            ?>
                                                                <option value="<?php echo htmlspecialchars($service['service_id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($service['ServiceName'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                            <?php 
                                                            } 
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Button to Add More Pets -->
                                        <button type="button" class="btn btn-secondary mt-2" id="addPetBtn">Add Another Pet</button>

                                        <!-- Selected Services Display -->
                                        <div id="selectedServices" class="selected-services"></div>

                                        <!-- Appointment Date -->
                                        <div class="form-group mt-3">
                                            <label for="AptDate">Appointment Date</label>
                                            <input type="date" class="form-control" name="AptDate" required>
                                        </div>

                                        <!-- Appointment Time -->
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
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary" name="addAppointment">Add Appointment</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Scripts -->
                    <script>
                        // Show/Hide New Client Section based on Client Selection
                        document.getElementById('client_id').addEventListener('change', function() {
                            const newClientSection = document.getElementById('newClientSection');
                            if (this.value === "") {
                                newClientSection.style.display = 'block';
                            } else {
                                newClientSection.style.display = 'none';
                            }
                        });

                        // Initialize Calendar
                        document.addEventListener('DOMContentLoaded', () => {
                            const timeDropdown = document.querySelector('select[name="AptTime"]');

                            timeDropdown.addEventListener('change', (event) => {
                                const selectedOption = event.target.selectedOptions[0];
                                if (selectedOption.disabled) {
                                    alert('This time slot is fully booked. Please choose another time.');
                                }
                            });

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
                                for (let i = 0; i < 6; i++) { // 6 weeks to cover all possible days
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

                                        // Disable cell if no slots are available
                                        if (remainingSlots <= 0) {
                                            cell.classList.add('disabled');
                                            cell.removeEventListener('click', null);
                                        }
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

                        // Dynamic Pet Addition
                        document.getElementById('addPetBtn').addEventListener('click', function() {
                            const petSection = document.getElementById('pet-section');
                            const petGroups = petSection.getElementsByClassName('pet-group');
                            const newIndex = petGroups.length;

                            const newPetGroup = document.createElement('div');
                            newPetGroup.classList.add('pet-group', 'form-group');
                            newPetGroup.innerHTML = `
                                <hr>
                                <label for="pname[]">Pet Name</label>
                                <input type="text" class="form-control" name="pname[]" required placeholder="Enter pet name">

                                <label for="Breed[]">Breed</label>
                                <input type="text" class="form-control" name="Breed[]" required placeholder="Enter pet breed">

                                <label for="pGender[]">Pet Gender</label>
                                <select class="form-control" name="pGender[]" required>
                                    <option value="" disabled selected>Select pet gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>

                                <label for="Age[]">Age (Years)</label>
                                <input type="number" min="0" class="form-control" name="Age[]" required placeholder="Enter pet age">

                                <label for="Species[]">Species</label>
                                <input type="text" class="form-control" name="Species[]" required placeholder="Enter pet species">

                                <label for="Birthdate[]">Birthdate</label>
                                <input type="date" class="form-control" name="Birthdate[]" required>

                                <label for="Color[]">Color</label>
                                <input type="text" class="form-control" name="Color[]" required placeholder="Enter pet color">

                                <label for="service_id[]">Services for this Pet</label>
                                <div class="service-container">
                                    <div class="service-entry">
                                        <select name="service_id[${newIndex}][]" class="form-control serviceSelect" multiple required>
                                            <?php 
                                            // Reset the pointer to the beginning
                                            mysqli_data_seek($serviceResult, 0);
                                            while ($service = mysqli_fetch_assoc($serviceResult)) { 
                                            ?>
                                                <option value="<?php echo htmlspecialchars($service['service_id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($service['ServiceName'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php 
                                            } 
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            `;
                            petSection.appendChild(newPetGroup);
                        });

                        // Update Selected Services Labels
                        document.addEventListener('change', function(event) {
                            if (event.target.classList.contains('serviceSelect')) {
                                const select = event.target;
                                const selectedServicesDiv = select.parentElement.parentElement.parentElement.querySelector('.selected-services');

                                // Clear previous selections
                                selectedServicesDiv.innerHTML = '';

                                // Add new selections
                                Array.from(select.selectedOptions).forEach(option => {
                                    const label = document.createElement('span');
                                    label.textContent = option.text; 
                                    label.className = 'badge badge-info'; 
                                    selectedServicesDiv.appendChild(label);
                                });
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
