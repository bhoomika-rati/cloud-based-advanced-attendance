<?php 
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Query to get class name, class arm name, and course name for the teacher
$query = "SELECT tblclass.className, tblclassarms.classArmName, course.courseName
    FROM tblclassteacher
    INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
    INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher.classArmId
    INNER JOIN course ON course.courseId = tblclassteacher.courseId
    WHERE tblclassteacher.Id = '$_SESSION[userId]'";

$rs = $conn->query($query);
$rrw = $rs->fetch_assoc();

// Query to get total number of classes for the teacher
$queryTotalClasses = "SELECT COUNT(DISTINCT classId) AS total_classes FROM tblclassteacher WHERE Id = '$_SESSION[userId]'";
$resultTotalClasses = mysqli_query($conn, $queryTotalClasses);
$rowTotalClasses = mysqli_fetch_assoc($resultTotalClasses);
$totalClasses = $rowTotalClasses['total_classes'];

// Query to get total number of class arms for the teacher
$queryTotalClassArms = "SELECT COUNT(DISTINCT classArmId) AS total_class_arms FROM tblclassteacher WHERE Id = '$_SESSION[userId]'";
$resultTotalClassArms = mysqli_query($conn, $queryTotalClassArms);
$rowTotalClassArms = mysqli_fetch_assoc($resultTotalClassArms);
$totalClassArms = $rowTotalClassArms['total_class_arms'];

// Query to get the number of sessions associated with the current teacher
$querySessions = "SELECT COUNT(*) AS numSessions 
                  FROM tblsessionterm 
                  WHERE teacher_id = '$_SESSION[userId]'";
$resultSessions = $conn->query($querySessions);
$rowSessions = $resultSessions->fetch_assoc();
$numSessions = $rowSessions['numSessions'];
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
            <h1 class="h3 mb-0 text-gray-800">Class Teacher Dashboard (<?php echo $rrw['className'].' - '.$rrw['classArmName'];?>)</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
          </div>
          
          <!-- Display the course of the teacher -->
          <div class="row mb-3">
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card h-100">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-uppercase mb-1">Course</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $rrw['courseName']; ?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-book fa-2x text-success"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          
           <!-- Display the number of sessions -->
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Sessions</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $numSessions; ?></div>
                </div>
                <div class="col-auto">
                    <i class="fas fa-clock fa-2x text-warning"></i>
                </div>
            </div>
        </div>
    </div>
</div>


            <!-- New User Card Example -->
            <?php 
            $query1=mysqli_query($conn,"SELECT * from tblstudents where classId = '$_SESSION[classId]' and classArmId = '$_SESSION[classArmId]'");                       
            $students = mysqli_num_rows($query1);
            ?>
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card h-100">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-uppercase mb-1">Students</div>
                      <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $students;?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-users fa-2x text-info"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Earnings (Monthly) Card Example -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card h-100">
                <div class="card-body">
                  <div class="row align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-uppercase mb-1">Classes</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalClasses;?></div>
                    </div>
                    <div class="col-auto">
                      <i class="fas fa-chalkboard fa-2x text-primary"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Earnings (Annual) Card Example -->
<div class="row mb-3">
  <div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="row no-gutters align-items-center">
          <div class="col mr-2">
            <div class="text-xs font-weight-bold text-uppercase mb-1">Class Arms</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalClassArms;?></div>
          </div>
          <div class="col-auto">
            <i class="fas fa-code-branch fa-2x text-success"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Pending Requests Card Example -->
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card h-100">
        <div class="card-body">
            <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Total Student Absent</div>
                    <?php 
                        // Query to get the number of absent students
                        $queryAbsent = "SELECT * FROM tblattendance WHERE classId = '$_SESSION[classId]' AND classArmId = '$_SESSION[classArmId]' AND tblattendance.status = '1'";
                        $resultAbsent = mysqli_query($conn, $queryAbsent);
                        $totAbsent = mysqli_num_rows($resultAbsent);
                    ?>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totAbsent; ?></div>
                </div>
                <div class="col-auto">
                    <i class="fas fa-user-times fa-2x text-warning"></i>
                </div>
            </div>
        </div>
    </div>
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
<script src="../vendore/chart.js/Chart.min.js"></script>
<script src="js/demo/chart-area-demo.js"></script>  
</body>

</html>

