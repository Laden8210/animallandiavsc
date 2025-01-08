<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style type="text/css">
  
  #leftCol {
    width: 230px;
    height: 100vh; 
    top: 0;
    bottom: 0;
    overflow-y: auto; 
    background-color: #f8f9fa; 
  }

  #leftCol::-webkit-scrollbar {
    width: 8px; 
  }

  #leftCol::-webkit-scrollbar-thumb {
    background: #888; 
    border-radius: 4px; 
  }

  #leftCol::-webkit-scrollbar-thumb:hover {
    background: #555; 
  }

  #leftCol::-webkit-scrollbar-track {
    background: #f1f1f1; 
  }

  .nav li {
    border-bottom: 1px solid #ccc;
  }

  .nav li a {
    padding-left: 10px;
    display: block; 
  }

  h1 {
    text-align: center;
    color: #333;
  }
</style>

<div class="sidebar" role="navigation">
  <div class="navbar-collapse">
    <nav class="cbp-spmenu cbp-spmenu-vertical cbp-spmenu-left" id="cbp-spmenu-s1">
      <ul class="nav" id="leftCol">
        <li>
          <a>
            <h1>ALVSC</h1>
            <span></span>
          </a>
        </li>
        <li>
          <a href="dashboard.php"><i class="fa fa-dashboard nav_icon"></i> Dashboard</a>
        </li>
        <li>
          <a href="manage-services.php"><i class="fa-solid fa-paw nav_icon"></i> Services</a>
        </li>
        <li>
          <a href="manage-products.php"><i class="fa-solid fa-bone nav_icon"></i> Products</a>
        </li>
        <li>
          <a href="all-appointment.php"><i class="fa-solid fa-dog nav_icon"></i> Appointment</a>
        </li>
        <li>
          <a href="pet_medical_result.php"><i class="fa-solid fa-pencil-square-o nav_icon"></i> Medical Record</a>
        </li>
        <li>
          <a href="inventory.php"><i class="fa-solid fa-table nav_icon"></i> Inventory</a>
        </li>
        <li>
          <a href="client-list.php"><i class="fa fa-user nav_icon"></i> Client List</a>
        </li>
        <li>
          <a href="transaction.php"><i class="fa fa-handshake nav_icon"></i> Transaction</a>
        </li>
        <li>
          <a href="reports.php" class="chart-nav"><i class="fa fa-pencil nav_icon"></i> Reports</a>
        </li>
        <li>
          <a href="user_account.php" class="chart-nav"><i class="fa fa-users nav_icon"></i> Users</a>
        </li>
        <li>
          <a href="vet-list.php" class="chart-nav"><i class="fa fa-user-md nav_icon"></i> Veterinarian</a>
        </li>
        <li>
          <a href="logout.php" class="chart-nav"><i class="fa fa-power-off nav_icon"></i> Logout</a>
        </li>
      </ul>
    </nav>
  </div>
</div>
