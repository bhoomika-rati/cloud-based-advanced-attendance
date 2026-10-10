<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Function to fetch and count attendance records for specific conditions
function fetchCount($conn, $condition) {
    $query = "SELECT COUNT(*) AS count
              FROM tblattendance
              INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
              WHERE tblstudents.Id = ? AND $condition";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $_SESSION['userId']);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return $count;
    } else {
        die('Error in query: ' . $conn->error);
    }
}

// Fetch various statistics
$totalAbsences = fetchCount($conn, "tblattendance.status = '0'");
$justifiedAbsences = fetchCount($conn, "tblattendance.status = '0' AND tblattendance.justification = '1'");
$unjustifiedAbsences = fetchCount($conn, "tblattendance.status = '0' AND tblattendance.justification = '0'");

// Fetch attendance count by course
$query = "SELECT course.courseName, COUNT(*) AS count
          FROM tblattendance
          INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
          INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
          INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
          INNER JOIN course ON tblsessionterm.course_id = course.courseId
          WHERE tblstudents.Id = ? AND tblattendance.status = '0'
          GROUP BY course.courseName";
$courseAttendance = [];
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $courseAttendance[] = $row;
    }
    $stmt->close();
} else {
    die('Error in query: ' . $conn->error);
}

// Fetch attendance count by session
$query = "SELECT tblsessionterm.sessionName, COUNT(*) AS count
          FROM tblattendance
          INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
          INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
          WHERE tblstudents.Id = ? AND tblattendance.status = '0'
          GROUP BY tblsessionterm.sessionName";
$sessionAttendance = [];
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sessionAttendance[] = $row;
    }
    $stmt->close();
} else {
    die('Error in query: ' . $conn->error);
}

// Fetch absence records
$query = "SELECT tblattendance.dateTimeTaken, course.courseName, tblsessionterm.sessionName
          FROM tblattendance
          INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
          INNER JOIN course ON tblsessionterm.course_id = course.courseId
          INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
          WHERE tblstudents.Id = ? AND tblattendance.status = '0'";
$absences = [];
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $absences[] = $row;
    }
    $stmt->close();
} else {
    die('Error in query: ' . $conn->error);
}

// Fetch absence count by teacher
$query = "SELECT CONCAT(tblclassteacher.firstName, ' ', tblclassteacher.lastName) AS teacherName, COUNT(*) AS count
          FROM tblattendance
          INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
          INNER JOIN tblclassteacher ON tblclassteacher.Id = tblsessionterm.teacher_id
          INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
          WHERE tblstudents.Id = ? AND tblattendance.status = '0'
          GROUP BY tblclassteacher.Id";
$teacherAttendance = [];
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $_SESSION['userId']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $teacherAttendance[] = $row;
    }
    $stmt->close();
} else {
    die('Error in query: ' . $conn->error);
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
    <title>Dashboard</title>
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
                        <h1 class="h3 mb-0 text-gray-800">View My Absences</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">View My Absences</li>
                        </ol>
                    </div>

                    <!-- Attendance Statistics -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Absences Statistics</h6>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table align-items-center table-flush table-hover">
                                        <thead>
                                            <tr>
                                                <th>Statistic</th>
                                                <th>Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Total Absences</td>
                                                <td><?php echo $totalAbsences; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Justified Absences</td>
                                                <td><?php echo $justifiedAbsences; ?></td>
                                            </tr>
                                            <tr>
                                                <td>Unjustified Absences</td>
                                                <td><?php echo $unjustifiedAbsences; ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Attendance Statistics -->

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
                                            foreach ($courseAttendance as $attendance) {
                                                echo '<tr>
                                                        <td>'.$attendance['courseName'].'</td>
                                                        <td>'.$attendance['count'].'</td>
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
                                            foreach ($sessionAttendance as $session) {
                                                echo '<tr>
                                                        <td>'.$session['sessionName'].'</td>
                                                        <td>'.$session['count'].'</td>
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

                    <!-- Absence Records -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Absence Records</h6>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table align-items-center table-flush table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Course</th>
                                                <th>Session</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach ($absences as $absence) {
                                                echo '<tr>
                                                        <td>'.$absence['dateTimeTaken'].'</td>
                                                        <td>'.$absence['courseName'].'</td>
                                                        <td>'.$absence['sessionName'].'</td>
                                                      </tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Absence Records -->

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
                                                <th>Teacher</th>
                                                <th>Absence Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach ($teacherAttendance as $teacher) {
                                                echo '<tr>
                                                        <td>'.$teacher['teacherName'].'</td>
                                                        <td>'.$teacher['count'].'</td>
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

                    <!-- Footer -->
                    <?php include 'includes/footer.php'; ?>
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
            <script src="../vendore/chart.js/Chart.min.js"></script>
            <script src="js/demo/chart-area-demo.js"></script>
</body>

</html>

