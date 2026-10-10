<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

require '../vendor/autoload.php';

$firstName = "";
$lastName = "";
$admissionNumber = "";
$classId = "";
$classArmId = "";

//------------------------SAVE--------------------------------------------------

if (isset($_POST['save'])) {

  $firstName = $_POST['firstName'];
  $lastName = $_POST['lastName'];
  $password = $_POST['password']; // Modified from otherName to password
  $admissionNumber = $_POST['admissionNumber'];
  $classId = $_POST['classId'];
  $classArmId = $_POST['classArmId'];
  $dateCreated = date("Y-m-d");

  $query = mysqli_query($conn, "select * from tblstudents where admissionNumber ='$admissionNumber'");
  $ret = mysqli_fetch_array($query);

  if ($ret > 0) {
    $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>This Admission Number Already Exists!</div>";
  } else {
    // Hash the password using MD5
    $hashedPassword = md5($password);

    // Generate QR Code
    $qrCode = new QrCode($admissionNumber);
    $writer = new PngWriter();
    $qrCodePath = '../Student/qr-codes/' . $admissionNumber . '.png';
    $writer->write($qrCode)->saveToFile($qrCodePath);

    $query = mysqli_query($conn, "insert into tblstudents(firstName,lastName,password,admissionNumber,classId,classArmId,dateCreated,qrCode_path) 
        values('$firstName','$lastName','$hashedPassword','$admissionNumber','$classId','$classArmId','$dateCreated','$qrCodePath')");

    if ($query) {
      $statusMsg = "<div class='alert alert-success' style='margin-right:700px;'>Created Successfully!</div>";
      // Clear the entered values after successful creation
      $firstName = "";
      $lastName = "";
      $admissionNumber = "";
      $classId = "";
      $classArmId = "";
    } else {
      $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
    }
  }
}

//---------------------------------------EDIT-------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit") {
  $Id = $_GET['Id'];

  $query = mysqli_query($conn, "select * from tblstudents where Id ='$Id'");
  $row = mysqli_fetch_array($query);

  // Populate form fields with existing values
  $firstName = $row['firstName'];
  $lastName = $row['lastName'];
  $admissionNumber = $row['admissionNumber'];
  $classId = $row['classId'];
  $classArmId = $row['classArmId'];

  //------------UPDATE-----------------------------

  if (isset($_POST['update'])) {

    // Retrieve form data
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $password = $_POST['password']; // Assuming you handle password security appropriately
    $admissionNumber = $_POST['admissionNumber'];
    $classId = $_POST['classId'];
    $classArmId = $_POST['classArmId']; // Ensure this field is correctly submitted

    // Ensure hashed password is used securely (e.g., using password_hash and password_verify for modern PHP applications)
    $hashedPassword = md5($password); // This should be updated for better security practices

    // Handle QR Code generation and path
    $qrCodePath = $row['qrCode_path']; // Use existing QR code path if already generated

    // Check if classArmId is not submitted (i.e., when not changed in form), retain existing classArmId
    if (empty($classArmId)) {
        $classArmId = $row['classArmId']; // Use the existing classArmId from the database
    }

    // Perform the update query
    $query = mysqli_query($conn, "UPDATE tblstudents SET firstName='$firstName', lastName='$lastName',
        password='$hashedPassword', admissionNumber='$admissionNumber', classId='$classId', classArmId='$classArmId', qrCode_path='$qrCodePath'
        WHERE Id='$Id'");

    if ($query) {
        // Redirect with success message after successful update
        header("Location: createStudents.php?status=update-success");
        exit();
    } else {
        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
    }
}
}

//--------------------------------DELETE------------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete") {
  $Id = $_GET['Id'];

  $query = mysqli_query($conn, "DELETE FROM tblstudents WHERE Id='$Id'");

  if ($query == TRUE) {

    echo "<script type = \"text/javascript\">
        window.location = (\"createStudents.php?status=delete-success\")
        </script>";
  } else {

    $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
  }
}

if (isset($_GET['status'])) {
  if ($_GET['status'] == 'update-success') {
    $statusMsg = "<div class='alert alert-success' style='margin-right:700px;'>Updated Successfully!</div>";
  }
  if ($_GET['status'] == 'delete-success') {
    $statusMsg = "<div class='alert alert-success' style='margin-right:700px;'>Deleted Successfully!</div>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <?php include 'Includes/title.php'; ?>
  <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">

  <script>
    function classArmDropdown(str) {
      if (str == "") {
        document.getElementById("txtHint").innerHTML = "";
        return;
      } else {
        if (window.XMLHttpRequest) {
          // code for IE7+, Firefox, Chrome, Opera, Safari
          xmlhttp = new XMLHttpRequest();
        } else {
          // code for IE6, IE5
          xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
            document.getElementById("txtHint").innerHTML = this.responseText;
          }
        };
        xmlhttp.open("GET", "ajaxClassArms2.php?cid=" + str, true);
        xmlhttp.send();
      }
    }

    // Confirmation dialog for delete action
    function confirmDelete(Id) {
      if (confirm("Are you sure you want to delete this student?")) {
        window.location.href = "?action=delete&Id=" + Id;
      }
    }
  </script>
</head>

<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
    <?php include "Includes/sidebar.php"; ?>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
        <?php include "Includes/topbar.php"; ?>
        <!-- Topbar -->

        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Create Students</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Create Students</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Create Students</h6>
                  <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                      <div class="col-xl-6">
                        <label class="form-control-label">Firstname<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="firstName" value="<?php echo $firstName; ?>" id="exampleInputFirstName">
                      </div>
                      <div class="col-xl-6">
                        <label class="form-control-label">Lastname<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="lastName" value="<?php echo $lastName; ?>" id="exampleInputLastName">
                      </div>
                    </div>

                    <div class="form-group row mb-3">
                      <div class="col-xl-6">
                        <label class="form-control-label">Admission Number<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="admissionNumber" value="<?php echo $admissionNumber; ?>" id="exampleInputAdmissionNumber" placeholder="Enter Admission Number">
                      </div>
                      <div class="col-xl-6">
                        <label class="form-control-label">Password<span class="text-danger ml-2">*</span></label>
                        <input type="password" class="form-control" name="password" id="exampleInputPassword" placeholder="Enter Password">
                      </div>
                    </div>

                    <div class="form-group row mb-3">
                      <div class="col-xl-6">
                        <label class="form-control-label">Select Class<span class="text-danger ml-2">*</span></label>
                        <?php
                        $qry = "SELECT * FROM tblclass ORDER BY className ASC";
                        $result = $conn->query($qry);
                        $num = $result->num_rows;
                        if ($num > 0) {
                          echo ' <select required name="classId" class="form-control mb-3" onChange="classArmDropdown(this.value)">';
                          echo '<option value="">--Select Class--</option>';
                          while ($rows = $result->fetch_assoc()) {
                            echo '<option value="' . $rows['Id'] . '" ' . (($rows['Id'] == $classId) ? 'selected="selected"' : '') . '>' . $rows['className'] . '</option>';
                          }
                          echo '</select>';
                        }
                        ?>
                      </div>
                      <div class="col-xl-6">
                        <label class="form-control-label">Select Class Arm<span class="text-danger ml-2">*</span></label>
                        <div id="txtHint">
                          <?php
                          if (isset($_GET['Id'])) {
                            $qry = "SELECT * FROM tblclassarms WHERE Id = '$classArmId'";
                            $result = $conn->query($qry);
                            $num = $result->num_rows;
                            if ($num > 0) {
                              $rows = $result->fetch_assoc();
                              echo '<input type="text" class="form-control" value="' . $rows['classArmName'] . '" readonly>';
                            }
                          } else {
                            echo '<select required name="classArmId" class="form-control mb-3">';
                            echo '<option value="">--Select Class Arm--</option>';
                            echo '</select>';
                          }
                          ?>
                        </div>
                      </div>
                    </div>

                    <button type="submit" name="<?php echo isset($_GET['Id']) ? 'update' : 'save'; ?>" class="btn btn-primary">
                      <?php echo isset($_GET['Id']) ? 'Update' : 'Save'; ?>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!-- Row -->
          <div class="row">
            <!-- DataTable with Hover -->
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Admission Number</th>
                        <th>Class</th>
                        <th>Class Arm</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>

                      <?php
                      $query = "SELECT tblstudents.Id, tblstudents.firstName, tblstudents.lastName,
                                tblstudents.admissionNumber, tblstudents.dateCreated, tblclass.className,
                                tblclassarms.classArmName
                                FROM tblstudents
                                INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
                                INNER JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
                                ORDER BY tblstudents.dateCreated DESC";
                      $rs = $conn->query($query);
                      $num = $rs->num_rows;
                      $sn = 0;
                      if ($num > 0) {
                        while ($rows = $rs->fetch_assoc()) {
                          $sn = $sn + 1;
                          echo "
                              <tr>
                                <td>" . $sn . "</td>
                                <td>" . $rows['firstName'] . " " . $rows['lastName'] . "</td>
                                <td>" . $rows['admissionNumber'] . "</td>
                                <td>" . $rows['className'] . "</td>
                                <td>" . $rows['classArmName'] . "</td>
                                <td>" . $rows['dateCreated'] . "</td>
                                <td>
                                  <a href='?action=edit&Id=" . $rows['Id'] . "' class='btn btn-sm btn-primary'>Edit</a>
                                  <button onclick='confirmDelete(" . $rows['Id'] . ")' class='btn btn-sm btn-danger'>Delete</button>
                                </td>
                              </tr>";
                        }
                      } else {
                        echo "<tr><td colspan='7' class='text-center'>No records found</td></tr>";
                      }
                      ?>

                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <!--Row-->

        </div>
        <!---Container Fluid-->
      </div>
      <!-- Footer -->
      <?php include 'Includes/footer.php'; ?>
      <!-- Footer -->
    </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendore/jquery/jquery.min.js"></script>
  <script src="../vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendore/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
  <script src="../vendore/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendore/datatables/dataTables.bootstrap4.min.js"></script>

  <script>
    $(document).ready(function() {
      $('#dataTableHover').DataTable();
    });
  </script>

</body>

</html>
