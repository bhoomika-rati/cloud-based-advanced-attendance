<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Fetch number of absences by each class/class arms
$query = "SELECT CONCAT(tblclass.className, ' (', tblclassarms.classArmName, ')') AS classArm, COUNT(*) AS count
          FROM tblattendance
          INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
          INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
          INNER JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
          WHERE tblattendance.status = '0'
          GROUP BY tblstudents.classId, tblstudents.classArmId";
$classArmAbsences = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classArmAbsences[] = $row;
    }
} else {
    die('Error in query: ' . $conn->error);
}

// Fetch number of absences by each course
$query = "SELECT course.courseName, COUNT(*) AS count
          FROM tblattendance
          INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
          INNER JOIN course ON tblsessionterm.course_id = course.courseId
          WHERE tblattendance.status = '0'
          GROUP BY course.courseName";
$courseAbsences = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courseAbsences[] = $row;
    }
} else {
    die('Error in query: ' . $conn->error);
}

// Fetch number of absences by each session
$query = "SELECT tblsessionterm.sessionName, COUNT(*) AS count
          FROM tblattendance
          INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
          WHERE tblattendance.status = '0'
          GROUP BY tblsessionterm.sessionName";
$sessionAbsences = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sessionAbsences[] = $row;
    }
} else {
    die('Error in query: ' . $conn->error);
}

// Fetch total number of absences
$query = "SELECT COUNT(*) AS totalAbsences FROM tblattendance WHERE status = '0'";
$result = $conn->query($query);
$totalAbsences = $result->fetch_assoc()['totalAbsences'];

// Fetch number of absences by each teacher
$query = "SELECT CONCAT( tblclassteacher.firstName, ' ', tblclassteacher.lastName) AS teacherName, COUNT(*) AS count
          FROM tblattendance
          INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
          INNER JOIN  tblclassteacher ON  tblclassteacher.Id= tblsessionterm.teacher_id
          WHERE tblattendance.status = '0'
          GROUP BY tblsessionterm.teacher_id";
$teacherAbsences = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teacherAbsences[] = $row;
    }
} else {
    die('Error in query: ' . $conn->error);
}

// Fetch number of absences by each class
$query = "SELECT tblclass.className, COUNT(*) AS count
          FROM tblattendance
          INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
          INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
          WHERE tblattendance.status = '0'
          GROUP BY tblstudents.classId";
$classAbsences = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classAbsences[] = $row;
    }
} else {
    die('Error in query: ' . $conn->error);
}

// Fetch class/class arms with the most absences and the number of absences they have
$query = "SELECT CONCAT(tblclass.className, ' (', tblclassarms.classArmName, ')') AS classArm, COUNT(*) AS count
          FROM tblattendance
          INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
          INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
          INNER JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
          WHERE tblattendance.status = '0'
          GROUP BY tblstudents.classId, tblstudents.classArmId
          ORDER BY COUNT(*) DESC
          LIMIT 1";
$mostAbsentClassArm = $conn->query($query)->fetch_assoc();

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
            <h1 class="h3 mb-0 text-gray-800">Attendance Statistics</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Attendance Statistics</li>
            </ol>
          </div>

          <!-- Absences by Class -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Absences by Class</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover">
                    <thead>
                      <tr>
                        <th>Class</th>
                        <th>Absence Count</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      foreach ($classAbsences as $classAbsence) {
                        echo '<tr>
                                <td>' . $classAbsence['className'] . '</td>
                                <td>' . $classAbsence['count'] . '</td>
                              </tr>';
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <!-- /Absences by Class -->

          <!-- Absences by Class/Class Arms -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Absences by Class/Class Arms</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover">
                    <thead>
                      <tr>
                        <th>Class/Class Arms</th>
                        <th>Absence Count</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      foreach ($classArmAbsences as $classArmAbsence) {
                        echo '<tr>
                                <td>' . $classArmAbsence['classArm'] . '</td>
                                <td>' . $classArmAbsence['count'] . '</td>
                              </tr>';
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <!-- /Absences by Class/Class Arms -->

          <!-- Absences by Course -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Absences by Course</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover">
                    <thead>
                      <tr>
                        <th>Course</th>
                        <th>Absence Count</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      foreach ($courseAbsences as $courseAbsence) {
                        echo '<tr>
                                <td>' . $courseAbsence['courseName'] . '</td>
                                <td>' . $courseAbsence['count'] . '</td>
                              </tr>';
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <!-- /Absences by Course -->

          <!-- Absences by Session -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Absences by Session</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover">
                    <thead>
                      <tr>
                        <th>Session</th>
                        <th>Absence Count</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      foreach ($sessionAbsences as $sessionAbsence) {
                        echo '<tr>
                                <td>' . $sessionAbsence['sessionName'] . '</td>
                                <td>' . $sessionAbsence['count'] . '</td>
                              </tr>';
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <!-- /Absences by Session -->

          <!-- Total Absences -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Total Absences</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover">
                    <thead>
                      <tr>
                        <th>Total Absences</th>
                        <th>Most Absent Class/Class Arms</th>
                        <th>Number of Absences</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td><?php echo $totalAbsences; ?></td>
                        <td><?php echo $mostAbsentClassArm['classArm']; ?></td>
                        <td><?php echo $mostAbsentClassArm['count']; ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <!-- /Total Absences -->

          <!-- Absences by Teacher -->
          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">Absences by Teacher</h6>
                </div>
                <div class="table-responsive p-3">
                  <table class="table align-items-center table-flush table-hover">
                    <thead>
                      <tr>
                        <th>Teacher Name</th>
                        <th>Absence Count</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      foreach ($teacherAbsences as $teacherAbsence) {
                        echo '<tr>
                                <td>' . $teacherAbsence['teacherName'] . '</td>
                                <td>' . $teacherAbsence['count'] . '</td>
                              </tr>';
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <!-- /Absences by Teacher -->

        </div>
        <!-- /.container-fluid -->

      </div>
      <!-- End of Main Content -->

      <!-- Footer -->
      <?php include 'Includes/footer.php'; ?>
      <!-- End of Footer -->

    </div>
    <!-- End of Content Wrapper -->

  </div>
  <!-- End of Page Wrapper -->

  <!-- Scroll to Top Button-->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendore/jquery/jquery.min.js"></script>
  <script src="../vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendore/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
  <!-- Page level plugins -->
  <script src="../vendore/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendore/datatables/dataTables.bootstrap4.min.js"></script>

  <!-- Page level custom scripts -->
  <script>
    $(document).ready(function() {
      $('#dataTable').DataTable(); // ID From dataTable 
      $('#dataTableHover').DataTable(); // ID From dataTable with Hover
    });
  </script>
</body>

</html>


