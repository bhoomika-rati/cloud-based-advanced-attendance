<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

//------------------------SAVE--------------------------------------------------

if (isset($_POST['save'])) {
  $courseName = $_POST['courseName'];
  $selectedModules = $_POST['modules']; // Retrieve selected modules

  // Check if the course already exists
  $query = mysqli_query($conn, "SELECT * FROM course WHERE courseName ='$courseName'");
  $ret = mysqli_fetch_array($query);

  if ($ret > 0) {
    $statusMsg = "<div id='alertMessage' class='alert alert-danger' style='margin-right:700px;'>This Course Name Already Exists!</div>";
  } else {
    // Insert the new course into the course table
    $query = mysqli_query($conn, "INSERT INTO course(courseName) VALUES ('$courseName')");

    // Retrieve the ID of the newly inserted course
    $courseId = mysqli_insert_id($conn);

    // Insert module-course associations into the module_course table
    foreach ($selectedModules as $moduleId) {
      mysqli_query($conn, "INSERT INTO module_course(moduleId, courseId) VALUES ($moduleId, $courseId)");
    }

    if ($query) {
      $statusMsg = "<div id='alertMessage' class='alert alert-success' style='margin-right:700px;'>Course Created Successfully!</div>";
    } else {
      $statusMsg = "<div id='alertMessage' class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
    }
  }
}

//--------------------EDIT------------------------------------------------------------

if (isset($_GET['edit'])) {
  $courseId = $_GET['edit'];

  // Fetch course details
  $query = mysqli_query($conn, "SELECT * FROM course WHERE courseId ='$courseId'");
  $courseRow = mysqli_fetch_array($query);

  // Fetch associated modules
  $moduleQuery = mysqli_query($conn, "SELECT moduleId FROM module_course WHERE courseId = $courseId");
  $moduleIds = [];
  while ($moduleRow = mysqli_fetch_assoc($moduleQuery)) {
    $moduleIds[] = $moduleRow['moduleId'];
  }

  //------------UPDATE-----------------------------

  if (isset($_POST['update'])) {
    $courseName = $_POST['courseName'];
    $selectedModules = $_POST['modules'];

    // Update course details
    $query = mysqli_query($conn, "UPDATE course SET courseName='$courseName' WHERE courseId='$courseId'");

    // Remove existing module-course associations
    mysqli_query($conn, "DELETE FROM module_course WHERE courseId='$courseId'");

    // Insert new module-course associations
    foreach ($selectedModules as $moduleId) {
      mysqli_query($conn, "INSERT INTO module_course(moduleId, courseId) VALUES ($moduleId, $courseId)");
    }

    if ($query) {
      $statusMsg = "<div id='alertMessage' class='alert alert-success' style='margin-right:700px;'>Course Updated Successfully!</div>";
      echo "<script>
                setTimeout(function() {
                    window.location.href = 'createCourse.php';
                }, 2000);
              </script>";
    } else {
      $statusMsg = "<div id='alertMessage' class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
    }
  }
}

//--------------------DELETE------------------------------------------------------------

if (isset($_GET['delete'])) {
  $courseId = $_GET['delete'];

  // Delete from course table
  $query = mysqli_query($conn, "DELETE FROM course WHERE courseId = $courseId");

  // Delete from module_course table
  mysqli_query($conn, "DELETE FROM module_course WHERE courseId = $courseId");

  if ($query) {
    $statusMsg = "<div id='alertMessage' class='alert alert-success' style='margin-right:700px;'>Course Deleted Successfully!</div>";
  } else {
    $statusMsg = "<div id='alertMessage' class='alert alert-danger' style='margin-right:700px;'>An error Occurred!</div>";
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
            <h1 class="h3 mb-0 text-gray-800">Manage Courses</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Manage Courses</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary"><?php echo isset($courseRow) ? 'Edit Course' : 'Create Course'; ?></h6>
                  <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                      <div class="col-xl-12">
                        <label class="form-control-label">Course Name<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="courseName" value="<?php echo isset($courseRow) ? $courseRow['courseName'] : ''; ?>" required>
                      </div>
                    </div>
                    <div class="form-group row mb-3">
                      <div class="col-xl-12">
                        <label class="form-control-label">Select Modules<span class="text-danger ml-2">*</span></label>
                        <?php
                        // Retrieve modules from the module table
                        $query = "SELECT * FROM module";
                        $result = $conn->query($query);
                        if ($result->                        num_rows > 0) {
                          while ($row = $result->fetch_assoc()) {
                            $checked = isset($moduleIds) && in_array($row["moduleId"], $moduleIds) ? "checked" : "";
                            echo "<div class='form-check'>
                                                                <input class='form-check-input' type='checkbox' name='modules[]' value='" . $row["moduleId"] . "' id='moduleCheckbox" . $row["moduleId"] . "' $checked>
                                                                <label class='form-check-label' for='moduleCheckbox" . $row["moduleId"] . "'>" . $row["moduleName"] . "</label>
                                                              </div>";
                          }
                        }
                        ?>
                      </div>
                    </div>
                    <?php if (isset($courseRow)) { ?>
                      <input type="hidden" name="courseId" value="<?php echo $courseId; ?>">
                      <button type="submit" name="update" class="btn btn-warning">Update</button>
                    <?php } else { ?>
                      <button type="submit" name="save" class="btn btn-primary">Save</button>
                    <?php } ?>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!--Row-->
          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Courses</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                    <thead class="thead-light">
                      <tr>
                        <th>Course Name</th>
                        <th>Modules</th>
                        <th>Edit</th>
                        <th>Delete</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $query = "SELECT * FROM course";
                      $rs = $conn->query($query);
                      $num = $rs->num_rows;
                      if ($num > 0) {
                        while ($rows = $rs->fetch_assoc()) {
                          // Fetch associated modules for each course
                          $courseId = $rows['courseId'];
                          $moduleQuery = mysqli_query($conn, "SELECT moduleName FROM module m JOIN module_course mc ON m.moduleId = mc.moduleId WHERE mc.courseId = $courseId");
                          $moduleNames = [];
                          while ($moduleRow = mysqli_fetch_assoc($moduleQuery)) {
                            $moduleNames[] = $moduleRow['moduleName'];
                          }
                          $moduleNamesString = implode(", ", $moduleNames);

                          echo "
                                                                                               <tr>
                                                                                                   <td>" . $rows['courseName'] . "</td>
                                                                                                   <td>" . $moduleNamesString . "</td>
                                                                                                   <td><a href='?edit=" . $rows['courseId'] . "'><i class='fas fa-fw fa-edit'></i>Edit</a></td>
                                                                                                   <td><a href='?delete=" . $rows['courseId'] . "' onclick='return confirm(\"Are you sure you want to delete this course?\")'><i class='fas fa-fw fa-trash'></i>Delete</a></td>
                                                                                               </tr>";
                        }
                      } else {
                        echo "
                                                                                           <tr>
                                                                                           <td colspan='4' class='text-center'>No Record Found!</td>
                                                                                           </tr>";
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

      // Auto-hide alert messages after a few seconds
      setTimeout(function() {
        $("#alertMessage").fadeOut("slow");
      }, 3000); // Adjust the timeout as needed
    });
  </script>
</body>

</html>

