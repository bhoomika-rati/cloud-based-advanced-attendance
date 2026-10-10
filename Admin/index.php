<?php 
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Get total number of students
$queryTotalStudents = "SELECT COUNT(*) AS total_students FROM tblstudents";
$resultTotalStudents = mysqli_query($conn, $queryTotalStudents);
$rowTotalStudents = mysqli_fetch_assoc($resultTotalStudents);
$totalStudents = $rowTotalStudents['total_students'];

// Get total number of absences
$queryTotalAbsences = "SELECT COUNT(*) AS total_absences FROM tblattendance WHERE status = '1'";
$resultTotalAbsences = mysqli_query($conn, $queryTotalAbsences);
$rowTotalAbsences = mysqli_fetch_assoc($resultTotalAbsences);
$totalAbsences = $rowTotalAbsences['total_absences'];

// Get total number of classes
$queryTotalClasses = "SELECT COUNT(*) AS total_classes FROM tblclass";
$resultTotalClasses = mysqli_query($conn, $queryTotalClasses);
$rowTotalClasses = mysqli_fetch_assoc($resultTotalClasses);
$totalClasses = $rowTotalClasses['total_classes'];

// Get total number of class arms
$queryTotalClassArms = "SELECT COUNT(*) AS total_class_arms FROM tblclassarms";
$resultTotalClassArms = mysqli_query($conn, $queryTotalClassArms);
$rowTotalClassArms = mysqli_fetch_assoc($resultTotalClassArms);
$totalClassArms = $rowTotalClassArms['total_class_arms'];

// Get total number of timetables
$queryTotalTimetables = "SELECT COUNT(*) AS total_timetables FROM timetable";
$resultTotalTimetables = mysqli_query($conn, $queryTotalTimetables);
$rowTotalTimetables = mysqli_fetch_assoc($resultTotalTimetables);
$totalTimetables = $rowTotalTimetables['total_timetables'];

// Get total number of sessions
$queryTotalSessions = "SELECT COUNT(*) AS total_sessions FROM tblsessionterm";
$resultTotalSessions = mysqli_query($conn, $queryTotalSessions);
$rowTotalSessions = mysqli_fetch_assoc($resultTotalSessions);
$totalSessions = $rowTotalSessions['total_sessions'];

// Get total number of teachers
$queryTotalTeachers = "SELECT COUNT(*) AS total_teachers FROM tblclassteacher";
$resultTotalTeachers = mysqli_query($conn, $queryTotalTeachers);
$rowTotalTeachers = mysqli_fetch_assoc($resultTotalTeachers);
$totalTeachers = $rowTotalTeachers['total_teachers'];
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
  <title>Dashboard</title>
  <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
    <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
        <?php include "Includes/topbar.php";?>
        <!-- Topbar -->
        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Administrator Dashboard</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
          </div>

          <div class="row mb-3">
           <!-- Total Students Card -->
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Students</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalStudents; ?></div>
                </div>
                <div class="col-auto">
                    <i class="fas fa-users fa-2x text-primary"></i>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- Total Absences Card -->
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Absences</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalAbsences; ?></div>
                </div>
                <div class="col-auto">
                    <i class="fas fa-user-times fa-2x text-primary"></i>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- Total Classes Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-uppercase mb-1">Total Classes</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalClasses;?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-chalkboard fa-2x text-primary"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Total Class Arms Card -->
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Class Arms</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalClassArms; ?></div>
                </div>
                <div class="col-auto">
                    <i class="fas fa-code-branch fa-2x text-primary"></i>
                </div>
            </div>
        </div>
    </div>
</div>

          </div>

          <div class="row mb-3">
            <!-- Total Timetables Card -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card h-100">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-uppercase mb-1">Total Timetables</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalTimetables;?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-calendar-alt fa-2x text-warning"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
           <!-- Total Sessions Card -->
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Sessions</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalSessions; ?></div>
                </div>
                <div class="col-auto">
                    <!-- Use inline style to set icon color to orange -->
                    <i class="fas fa-clock fa-2x" style="color: orange;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- Total Teachers Card -->
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Teachers</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalTeachers; ?></div>
                </div>
                <div class="col-auto">
                    <i class="fas fa-chalkboard-teacher fa-2x text-warning"></i>
                </div>
            </div>
        </div>
    </div>
</div>

          </div>

        </div>
        <!---Container Fluid-->
      </div>
      <!-- Footer -->
      <?php include 'includes/footer.php';?>
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
<!--
<script src="../vendore/chart.js/Chart.min.js"></script>
<script src="js/demo/chart-area-demo.js"></script>
-->
 
</body>

</html>

