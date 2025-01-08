<?php 
session_start();
error_reporting(1);
include('includes/dbconnection.php');

if (strlen($_SESSION['id']) == 0) {
  header('location:logout.php');
  exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
  $id = intval($_GET['id']);
  if ($id > 0) {
      $stmt = $con->prepare("SELECT * FROM tblservices WHERE service_id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $result = $stmt->get_result();
      $service = $result->fetch_assoc();
      $stmt->close();

      if ($service) {
          $stmt = $con->prepare("DELETE FROM tblservices_archive WHERE service_id = ?");
          $stmt->bind_param("i", $id);
          $stmt->execute();
          $stmt->close();

          $stmt = $con->prepare("INSERT INTO tblservices_archive (service_id, ServiceName, Description, Cost, image, status) VALUES (?, ?, ?, ?, ?, ?)");
          $stmt->bind_param("issdss", $service['service_id'], $service['ServiceName'], $service['Description'], $service['Cost'], $service['image'], $service['status']);
          $stmt->execute();
          $stmt->close();

          $stmt = $con->prepare("DELETE FROM tblservices WHERE service_id = ?");
          $stmt->bind_param("i", $id);
          if ($stmt->execute()) {
              echo "<script>alert('Service deleted and archived successfully.');</script>";
          } else {
              echo "<script>alert('Failed to delete service. Please try again.');</script>";
          }
          $stmt->close();
      } else {
          echo "<script>alert('Service not found.');</script>";
      }
  } else {
      echo "<script>alert('Invalid service ID.');</script>";
  }
  echo "<script>window.location.href='manage-services.php'</script>";
  exit();
}

if (isset($_POST['submit'])) {
  $serviceName = $_POST['sername'];
  $description = $_POST['des'];
  $cost = $_POST['cost'];
  $status = $_POST['status'];
  
  $image = $_FILES['image']['name'];
  $target = "product_image/" . basename($image);

  if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    $stmt = $con->prepare("INSERT INTO tblservices (ServiceName, Description, Cost, image, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $serviceName, $description, $cost, $image, $status);
    if ($stmt->execute()) {
      echo "<script>
              alert('Service added successfully.');
              $('#addServiceModal').modal('hide');
              window.location.reload();
            </script>";
    } else {
      echo "<script>alert('Failed to add service.');</script>";
    }
    $stmt->close();
  } else {
    echo "<script>alert('Failed to upload image.');</script>";
  }
}

if (isset($_POST['update'])) {
  $id = $_POST['service_id'];
  $serviceName = $_POST['sername'];
  $description = $_POST['des'];
  $cost = $_POST['cost'];
  $status = $_POST['status'];
  
  $image = $_FILES['image']['name'];
  $target = "product_image/" . basename($image);
  
  if ($image) {
      if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
          $stmt = $con->prepare("UPDATE tblservices SET ServiceName = ?, Description = ?, Cost = ?, image = ?, status = ? WHERE service_id = ?");
          $stmt->bind_param("ssissi", $serviceName, $description, $cost, $image, $status, $id);
      } else {
          echo "<script>alert('Failed to upload image.');</script>";
          exit;
      }
  } else {
      $stmt = $con->prepare("UPDATE tblservices SET ServiceName = ?, Description = ?, Cost = ?, status = ? WHERE service_id = ?");
      $stmt->bind_param("ssisi", $serviceName, $description, $cost, $status, $id);
  }

  if ($stmt->execute()) {
      echo "<script>
              alert('Service updated successfully.');
              $('#editServiceModal').modal('hide');
              window.location.reload();
            </script>";
  } else {
      echo "<script>alert('Failed to update service.');</script>";
  }
  $stmt->close();
}

?>
<!DOCTYPE HTML>
<html>
<head>
  <title>ALVSC || Manage Services</title>
  <script type="application/x-javascript"> 
    addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); 
    function hideURLbar(){ window.scrollTo(0,1); } 
  </script>
  <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
  <link href="css/style.css" rel='stylesheet' type='text/css' />
  <link href="css/font-awesome.css" rel="stylesheet">
  <script src="js/jquery-1.11.1.min.js"></script>
  <script src="js/modernizr.custom.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet" type='text/css'>
  <link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
  <script src="js/wow.min.js"></script>
  <script> new WOW().init(); </script>
  <script src="js/metisMenu.min.js"></script>
  <script src="js/custom.js"></script>
  <link href="css/custom.css" rel="stylesheet">
  <script src="js/bootstrap.js"></script>
</head>
<style>
  
</style>
<body class="cbp-spmenu-push">
  <div class="main-content">
    <?php include_once('includes/sidebar.php'); ?>
    <?php include_once('includes/header.php'); ?>
    <div id="page-wrapper">
      <div class="main-page">
        <div class="tables">
            <h4>Manage Services:</h4>
            <form class="form-inline" style="margin-bottom: 10px;">
              <input type="search" name="search" class="form-control" placeholder="Search services...">
              <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
              <div class="pull-right">
                <button type="button" class="btn" style="background-color: #337ab7; color: #fff;" data-toggle="modal" data-target="#addServiceModal">Add New Service</button>
              </div>
            </form>
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>No.</th>
                  <th>Image</th>
                  <th>Service Name</th>
                  <th>Description</th>
                  <th>Service Price</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if (isset($_GET['search'])) {
                  $search_query = $_GET['search'];
                  $ret = mysqli_query($con, "SELECT * FROM tblservices WHERE ServiceName LIKE '%$search_query%'");
                } else {
                  $ret = mysqli_query($con, "SELECT * FROM tblservices ORDER BY service_id DESC");
                }
                $cnt = 1;
                while ($row = mysqli_fetch_array($ret)) {
                ?>
                  <tr>
                    <th scope="row"><?php echo $cnt; ?></th>
                    <td style="text-align: center;">
                    <?php
                     $imagePath = 'product_image/' . ($row['image'] ? $row['image'] : 'default.jpg');
                     echo "<img src='$imagePath' height='60' width='90' class='img-thumbnail'>";
                    ?>
                    </td>
                    <td class="service-name"><?php echo $row['ServiceName']; ?></td>
                    <td class="description"><?php echo $row['Description']; ?></td>
                    <td class="service-cost">₱<?php echo $row['Cost']; ?></td>
                    <td class="service-status">
    <?php 
        if ($row['status'] == 'Available') {
            echo '<span class="badge" style="background-color: #8bc34a;; color: white;">Available</span>';
        } else {
            echo '<span class="badge" style="background-color: red; color: white;">Not Available</span>';
        }
    ?>
</td>

                    <td>
                      <button type="button" class="btn btn-primary btn-xs btn-edit">Edit</button>
                      
                    </td>
                    <td class="service-id" style="display: none;"><?php echo $row['service_id']; ?></td>
                    <td class="service-description" style="display: none;"><?php echo $row['Description']; ?></td>
                  </tr>
                <?php
                  $cnt = $cnt + 1;
                }
                ?>
              </tbody>
            </table>
            <button class="btn btn-default" onclick="history.back()">Previous</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="addServiceModal" tabindex="-1" role="dialog" aria-labelledby="addServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addServiceModalLabel">Add Service</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?php if (isset($error_message)) { ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
          <?php } ?>

          <form method="post" enctype="multipart/form-data">
            <div class="form-group">
              <label for="image">Service Image</label>
              <input type="file" class="form-control" id="image" name="image" required>
            </div>

            <div class="form-group">
              <label for="sername">Service Name</label>
              <input type="text" class="form-control" id="sername" name="sername" placeholder="Service Name" required>
            </div>

            <div class="form-group">
              <label for="des">Description</label>
              <textarea class="form-control" id="des" name="des" rows="5" placeholder="Description" required></textarea>
            </div>

            <div class="form-group">
              <label for="cost">Cost</label>
              <input type="text" class="form-control" id="cost" name="cost" placeholder="Cost" required>
            </div>
            <div class="form-group">
          <label for="editStatus">Status</label>
          <select class="form-control" id="editStatus" name="status">
           <option value="Available">Available</option>
           <option value="Not Available">Not Available</option>
           </select>
          </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" name="submit" class="btn btn-primary">Add Service</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editServiceModal" tabindex="-1" role="dialog" aria-labelledby="editServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editServiceModalLabel">Edit Service</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?php if (isset($error_message)) { ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
          <?php } ?>

          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="service_id" id="editServiceId">
            
            <div class="form-group">
    <label for="editImage">Service Image</label>
    <input type="file" class="form-control" id="editImage" name="image">
    <img id="editImagePreview" src="" alt="Current Image" style="margin-top: 10px; max-height: 100px; display: none;">
</div>


            <div class="form-group">
              <label for="editSername">Service Name</label>
              <input type="text" class="form-control" id="editSername" name="sername" placeholder="Service Name" required>
            </div>

            <div class="form-group">
              <label for="editDes">Description</label>
              <textarea class="form-control" id="editDes" name="des" rows="5" placeholder="Description" required></textarea>
            </div>

            <div class="form-group">
              <label for="editCost">Cost</label>
              <input type="text" class="form-control" id="editCost" name="cost" placeholder="Cost" required>
            </div>
            <div class="form-group">
            <label for="editStatus">Status</label>
             <select class="form-control" id="editStatus" name="status">
              <option value="Available">Available</option>
              <option value="Not Available">Not Available</option>
               </select>
              </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" name="update" class="btn btn-primary">Update Service</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    $(document).ready(function () {
  $('.btn-edit').on('click', function () {
    var row = $(this).closest('tr');

    var id = row.find('.service-id').text().trim();
    var name = row.find('.service-name').text().trim();
    var description = row.find('.description').text().trim();
    var cost = row.find('.service-cost').text().replace('₱', '').trim();
    var status = row.find('.service-status .badge').text().trim(); 

    $('#editServiceId').val(id);
    $('#editSername').val(name);
    $('#editDes').val(description);
    $('#editCost').val(cost);
    $('#editStatus').val(status === 'Available' ? 'Available' : 'Not Available'); 

    $('#editServiceModal').modal('show');
  });

  $('#editServiceModal').on('hidden.bs.modal', function () {
    $('#editServiceModal form')[0].reset();
  });
});

  </script>
</body>
</html>
