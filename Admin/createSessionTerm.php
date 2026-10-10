<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

//------------------------SAVE--------------------------------------------------

if (isset($_POST['save'])) {

  $sessionName = $_POST['sessionName'];
  $termId = $_POST['termId'];
  $startTime = $_POST['startTime'];
  $endTime = $_POST['endTime'];
  $teacherId = $_POST['teacherId'];
  $type = $_POST['type']; // Add this line
  $day_name = $_POST['day_name']; // Add this line
  $dateCreated = date("Y-m-d");

  $query = mysqli_query($conn, "SELECT * FROM tblsessionterm WHERE sessionName ='$sessionName' AND termId = '$termId'");
  $ret = mysqli_fetch_array($query);

  if ($ret > 0) {
    $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>This Session and Term Already Exists!</div>";
  } else {
    $query = mysqli_query($conn, "INSERT INTO tblsessionterm (sessionName, termId, isActive, dateCreated, startTime, endTime, teacher_id, type, day_name) VALUES ('$sessionName', '$termId', '0', '$dateCreated', '$startTime', '$endTime', '$teacherId', '$type', '$day_name')");
    if ($query) {
      $statusMsg = "<div class='alert alert-success'  style='margin-right:700px;'>Created Successfully!</div>";
    } else {
      $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
    }
  }
}

//---------------------------------------EDIT-------------------------------------------------------------

//--------------------EDIT------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit") {
  $Id = $_GET['Id'];

  $query = mysqli_query($conn, "SELECT * FROM tblsessionterm WHERE Id ='$Id'");
  $row = mysqli_fetch_array($query);

  //------------UPDATE-----------------------------

  if (isset($_POST['update'])) {

    $sessionName = $_POST['sessionName'];
    $termId = $_POST['termId'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $teacherId = $_POST['teacherId'];
    $type = $_POST['type']; // Add this line
    $day_name = $_POST['day_name']; // Add this line
    $dateCreated = date("Y-m-d");

    $query = mysqli_query($conn, "UPDATE tblsessionterm SET sessionName='$sessionName', termId='$termId', isActive='0', startTime='$startTime', endTime='$endTime', teacher_id='$teacherId', type='$type', day_name='$day_name' WHERE Id='$Id'");

    if ($query) {
      echo "<script type = \"text/javascript\">
                window.location = (\"createSessionTerm.php\")
                </script>";
    } else {
      $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
    }
  }
}


//--------------------------------DELETE------------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete") {
  $Id = $_GET['Id'];

  $query = mysqli_query($conn, "DELETE FROM tblsessionterm WHERE Id='$Id'");

  if ($query == TRUE) {
    echo "<script type = \"text/javascript\">
                window.location = (\"createSessionTerm.php\")
                </script>";
  } else {
    $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
  }
}


//--------------------------------ACTIVATE------------------------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "activate") {
  $Id = $_GET['Id'];

  $query = mysqli_query($conn, "UPDATE tblsessionterm SET isActive='0' WHERE isActive='1'");

  if ($query) {
    $que = mysqli_query($conn, "UPDATE tblsessionterm SET isActive='1' WHERE Id='$Id'");

    if ($que) {
      echo "<script type = \"text/javascript\">
                    window.location = (\"createSessionTerm.php\")
                    </script>";
    } else {
      $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
    }
  } else {
    $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
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
  <?php include 'includes/title.php'; ?>
  <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
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
            <h1 class="h3 mb-0 text-gray-800">Create Session</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Create Session</li> <!-- Fix typo here -->
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Create Session and Term</h6>
                  <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                      <div class="col-xl-4">
                        <label class="form-control-label">Session Name<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="sessionName" value="<?php echo $row['sessionName']; ?>"id="exampleInputFirstName" placeholder="Session">
                    </div>
                    <div class="col-xl-4">
                        <label class="form-control-label">Term<span class="text-danger ml-2">*</span></label>
                        <?php
                        $qry = "SELECT * FROM tblterm ORDER BY termName ASC";
                        $result = $conn->query($qry);
                        $num = $result->num_rows;
                        if ($num > 0) {
                            echo ' <select required name="termId" class="form-control mb-3">';
                            echo '<option value="">--Select Term--</option>';
                            while ($rows = $result->fetch_assoc()) {
                                echo '<option value="' . $rows['Id'] . '" >' . $rows['termName'] . '</option>';
                            }
                            echo '</select>';
                        }
                        ?>
                    </div>
                    <div class="col-xl-2">
                        <label class="form-control-label">Start Time<span class="text-danger ml-2">*</span></label>
                        <input type="time" class="form-control" name="startTime" value="<?php echo $row['startTime']; ?>" placeholder="Start Time">
                    </div>
                    <div class="col-xl-2">
                        <label class="form-control-label">End Time<span class="text-danger ml-2">*</span></label>
                        <input type="time" class="form-control" name="endTime" value="<?php echo $row['endTime']; ?>" placeholder="End Time">
                    </div>
                    <div class="col-xl-4">
                        <label class="form-control-label">Teacher<span class="text-danger ml-2">*</span></label>
                        <?php
                        $qry = "SELECT * FROM tblclassteacher ORDER BY firstName ASC"; // Modify this query to get the teachers
                        $result = $conn->query($qry);
                        $num = $result->num_rows;
                        if ($num > 0) {
                            echo ' <select required name="teacherId" class="form-control mb-3">';
                            echo '<option value="">--Select Teacher--</option>';
                            while ($rows = $result->fetch_assoc()) {
                                echo '<option value="' . $rows['Id'] . '" >' . $rows['firstName'] . ' ' . $rows['lastName'] . '</option>';
                            }
                            echo '</select>';
                        }
                        ?>
                    </div>
                </div>

                <div class="form-group row mb-3">
                    <div class="col-xl-4">
                        <label class="form-control-label">Day<span class="text-danger ml-2">*</span></label>
                        <!-- Add your day select option here -->
                        <select required name="day_name" class="form-control">
                            <option value="">--Select Day--</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>

                    <div class="col-xl-4">
                        <label class="form-control-label">Session Type<span class="text-danger ml-2">*</span></label>
                        <!-- Add your session type select option here -->
                        <select required name="type" class="form-control">
                            <option value="">--Select Session Type--</option>
                            <option value="Course">Course</option>
                            <option value="TD">TD</option>
                            <option value="TP">TP</option>
                            <!-- Add more options as needed -->
                        </select>
                    </div>

                    <div class="col-xl-4">
                        <label class="form-control-label">Subject<span class="text-danger ml-2">*</span></label>
                        <?php
                        $qry = "SELECT * FROM course ORDER BY courseName ASC"; // Query to get the subjects from course table
                        $result = $conn->query($qry);
                        $num = $result->num_rows;
                        if ($num > 0) {
                            echo ' <select required name="subjectId" class="form-control mb-3">';
                            echo '<option value="">--Select Subject--</option>';
                            while ($rows = $result->fetch_assoc()) {
                                echo '<option value="' . $rows['courseId'] . '" >' . $rows['courseName'] . '</option>'; // Populate the dropdown with course names
                            }
                            echo '</select>';
                        }
                        ?>
                    </div>


                <?php
                if (isset($Id)) {
                ?>
                    <button type="submit" name="update" class="btn btn-warning">Update</button>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <?php
                } else {
                ?>
                    <button type="submit" name="save" class="btn btn-primary">Save</button>
                <?php
                }
                ?>
                </form>
            </div>


            </div>
          </div>

          <!-- Input Group -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Session</h6>
                  <h6 class="m-0 font-weight-bold text-danger">Note: <i>Click on the check symbol besides each to make session  active!</i></h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>Session</th>
                        <th>Term</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Teacher</th>
                        <th>Type</th> <!-- Added Type column header -->
                        <th>Day</th> <!-- Added Day column header -->
                        <th>Status</th>
                        <th>Activate</th>
                        <th>Edit</th>
                        <th>Delete</th>
                      </tr>
                    </thead>

                    <tbody>

                      <?php
                      $query = "SELECT tblsessionterm.Id,tblsessionterm.sessionName,tblsessionterm.isActive,tblterm.termName, tblsessionterm.startTime, tblsessionterm.endTime, tblsessionterm.teacher_id,
                    tblclassteacher.firstName, tblclassteacher.lastName, tblsessionterm.type, tblsessionterm.day_name
                    FROM tblsessionterm
                    INNER JOIN tblterm ON tblterm.Id = tblsessionterm.termId
                    INNER JOIN tblclassteacher ON tblclassteacher.Id = tblsessionterm.teacher_id"; // Modify this query to include teacher's details
                      $rs = $conn->query($query);
                      $num = $rs->num_rows;
                      $sn = 0;
                      if ($num > 0) {
                        while ($rows = $rs->fetch_assoc()) {
                          if ($rows['isActive'] == '1') {
                            $status = "Active";
                          } else {
                            $status = "InActive";
                          }
                          $sn = $sn + 1;
                          echo "
                            <tr>
                              <td>" . $rows['sessionName'] . "</td>
                              <td>" . $rows['termName'] . "</td>
                              <td>" . $rows['startTime'] . "</td>
                              <td>" . $rows['endTime'] . "</td>
                              <td>" . $rows['firstName'] . ' ' . $rows['lastName'] . "</td> <!-- Display teacher's name -->
                              <td>" . $rows['type'] . "</td> <!-- Display session type -->
                              <td>" . $rows['day_name'] . "</td> <!-- Display day -->
                              <td>" . $status . "</td>
                              <td><a href='?action=activate&Id=" . $rows['Id'] . "'><i class='fas fa-fw fa-check'></i></a></td>
                              <td><a href='?action=edit&Id=" . $rows['Id'] . "'><i class='fas fa-fw fa-edit'></i></a></td>
                              <td><a href='javascript:void(0);' onclick='confirmDelete(" . $rows['Id'] . ")'><i class='fas fa-fw fa-trash'></i></a></td> <!-- Add onclick event for confirm delete -->
                            </tr>";
                        }
                      } else {
                        echo
                        "<div class='alert alert-danger' role='alert'>
                          No Record Found!
                          </div>";
                      }

                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!---Container Fluid-->
      </div>
      <!-- Footer -->
      <?php include "Includes/footer.php"; ?>
      <!-- Footer -->
    </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <!-- JavaScript for Confirm Delete -->
  <script>
    function confirmDelete(id) {
      var confirmation = confirm("Are you sure you want to delete this session?");
      if (confirmation) {
        window.location = "?action=delete&Id=" + id;
      }
    }
  </script>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
  <!-- Page level plugins -->
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

  <!-- Page level custom scripts -->
  <script>
    $(document).ready(function() {
      $('#dataTable').DataTable(); // ID From dataTable 
      $('#dataTableHover').DataTable(); // ID From dataTable with Hover
    });
  </script>
</body>

</html>


