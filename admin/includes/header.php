<?php

ini_set('display_errors', 1);

include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

$appointment_query = "SELECT Appt_ID, client_id FROM tblappointment WHERE Status=''";
$appointment_result = mysqli_query($con, $appointment_query);
$appointment_count = mysqli_num_rows($appointment_result);

$low_stock_query = "SELECT ProductName, Quantity FROM tblproducts 
LEFT JOIN tblinventory ON tblproducts.ProductID = tblinventory.ProductID 
WHERE Quantity <= 5";
$low_stock_result = mysqli_query($con, $low_stock_query);
$low_stock_products = [];
while ($row = mysqli_fetch_assoc($low_stock_result)) {
    $low_stock_products[] = $row;
}

// $expiring_soon_query = "SELECT ProductName, ExpirationDate FROM tblproducts 
// WHERE ExpirationDate IS NOT NULL AND DATEDIFF(ExpirationDate, NOW()) <= 7";
// $expiring_soon_result = mysqli_query($con, $expiring_soon_query);
// $expiring_soon_products = [];
// while ($row = mysqli_fetch_assoc($expiring_soon_result)) {
//     $expiring_soon_products[] = $row;
// }

// echo "<script>
//     var lowStockProducts = " . json_encode($low_stock_products) . ";
//     var expiringSoonProducts = " . json_encode($expiring_soon_products) . ";
// </script>";

// $expired_query = "SELECT ProductName, ExpirationDate FROM tblproducts 
// WHERE ExpirationDate IS NOT NULL AND ExpirationDate < NOW()";
// $expired_result = mysqli_query($con, $expired_query);
// $expired_products = [];
// while ($row = mysqli_fetch_assoc($expired_result)) {
//     $expired_products[] = $row;
// }

echo "<script>
    var expiredProducts = " . json_encode($expired_products) . ";
</script>";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Page Title</title>
    <link rel="icon" type="image/x-icon" href="uploads/clinic-logo.ico" />
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
      .power-icon, .nav_icon, .notification-icon {
            color: black;
            font-size: 19px; 
        }
        .profile_img {
            position: relative;
        }
        .calendar-wrapper {
            display: flex; 
            justify-content: flex-end; 
            width: 100%;
        }
        #calendar {
            max-width: 950px;
            width: 100%;
        }
        .user-name {
            color: black;
            font-size: 20px;
            font-weight: bold;
        }
        .sticky-header {
            display: flex;
            justify-content: space-between; 
            align-items: center;
            padding: 10px;
            background-color: #ddd;
        }
        .header-left {
            flex: 1;
        }
        .profile_details {
            display: flex;
            align-items: center;
            gap: 15px; 
        }
        .nofitications-dropdown {
            list-style: none;
            margin: 0;
            padding: 0;
            position: relative;
        }
        .dropdown-menu {
            position: absolute;
            top: 120%;     
            right: 10px;   
            z-index: 1000;
            display: none;
            min-width: 200px;
            max-width: 250px; 
            padding: 10px 15px; 
            margin: 2px 0 0;
            font-size: 14px;
            text-align: left;
            list-style: none;
            background-color: #fff;
            border: 1px solid rgba(0, 0, 0, .15);
            border-radius: 4px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
        }
        .dropdown-menu.show {
            display: block;
        }
        .notification-icon {
            color: black;
            position: relative;
            cursor: pointer;
        }
        .notification-icon .badge {
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            position: absolute;
            top: -5px;
            right: -10px;
        }
        .notification-bell {
            position: relative;
            cursor: pointer;
            margin-right: 15px;
        }

        .notification-bell .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px;
            font-size: 12px;
        }

        #notification-dropdown {
            position: absolute;
            top: 50px;
            right: 0;
            background: white;
            border: 1px solid #ccc;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            width: 300px;
            z-index: 1000;
            display: none;
        }

        #notification-dropdown ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        #notification-dropdown ul li {
            padding: 10px;
            border-bottom: 1px solid #f1f1f1;
        }

        #notification-dropdown ul li:hover {
            background-color: #f9f9f9;
        }

        .nav_icon {
            font-size: 20px;
            color: #333;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="sticky-header header-section">
        <div class="header-left">
            <div class="logo"></div>
        </div>

        <div class="header-right">
            <div class="profile_details">
                <div class="user-name">
                    <?php
                    if (isset($_SESSION['Firstname'])) {
                        echo "Welcome, " . $_SESSION['Firstname'];
                    } else {
                        echo "Welcome, Guest";
                    }
                    ?>
                </div>

                <div class="notification-bell">
                    <i class="fa fa-bell"></i>
                    <span id="notification-count" class="badge" style="display: none;"></span>
                </div>
                <div id="notification-dropdown">
                    <ul id="notification-list"></ul>
                </div>

                <a href="settings.php" class="nav_icon"><i class="fa fa-cog"></i></a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const notificationBell = document.querySelector('.notification-bell');
        const notificationCount = document.getElementById('notification-count');
        const notificationList = document.getElementById('notification-list');
        const notificationDropdown = document.getElementById('notification-dropdown');

        let notifications = [];

        const appointmentCount = <?php echo $appointment_count; ?>;
        if (appointmentCount > 0) {
            notifications.push(`You have ${appointmentCount} new appointment notification(s).`);
        }

        lowStockProducts.forEach(product => {
            notifications.push(`Low Stock: ${product.ProductName} (${product.Quantity} left)`);
        });

        expiringSoonProducts.forEach(product => {
            notifications.push(`Expiring Soon: ${product.ProductName} (Expires on ${product.ExpirationDate})`);
        });

        expiredProducts.forEach(product => {
            notifications.push(`Expired: ${product.ProductName} (Expired on ${product.ExpirationDate})`);
        });

        if (notifications.length > 0) {
            notificationCount.textContent = notifications.length;
            notificationCount.style.display = 'block';

            notifications.forEach(notification => {
                const listItem = document.createElement('li');
                listItem.textContent = notification;
                notificationList.appendChild(listItem);
            });
        }

        notificationBell.addEventListener('click', () => {
            notificationDropdown.classList.toggle('show');
        });

        document.addEventListener('click', (event) => {
            if (!notificationBell.contains(event.target) && !notificationDropdown.contains(event.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
    });
</script>

</body>
</html>
