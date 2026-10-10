

<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Get class and class arm details
$query = "SELECT tblclass.className, tblclassarms.classArmName 
          FROM tblclassteacher
          INNER JOIN tblclass ON tblclass.Id = tblclassteacher.classId
          INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher.classArmId
          WHERE tblclassteacher.Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

// Store class ID, class arm ID, and teacher ID in variables
$classId = $row['class_id'];
$classArmId = $row['class_arm_id'];
$teacherId = $_SESSION['userId'];
// Session and Term
$querySessionTerm = "SELECT * FROM tblsessionterm WHERE isActive ='1' AND teacher_id = ?";
$stmtSessionTerm = $conn->prepare($querySessionTerm);
$stmtSessionTerm->bind_param("s", $teacherId);
$stmtSessionTerm->execute();
$resultSessionTerm = $stmtSessionTerm->get_result();
$rowSessionTerm = $resultSessionTerm->fetch_assoc();
$sessionTermId = $rowSessionTerm['Id'];
$stmtSessionTerm->close();


$dateTaken = date("Y-m-d");

$statusMsg = "";

// Check if the form was submitted
if (isset($_POST['save'])) {
    // Check if attendance has already been taken for the current date and class
    $checkQuery = "SELECT COUNT(*) AS attendance_count FROM tblattendance WHERE classId = ? AND classArmId = ? AND DATE(dateTimeTaken) = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("sss", $_SESSION['classId'], $_SESSION['classArmId'], $dateTaken);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkRow = $checkResult->fetch_assoc();
    $checkStmt->close();

    if ($checkRow['attendance_count'] > 0) {
        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>Attendance has already been taken for today.</div>";
    } else {
        // Get the current date and time
        $dateTimeTaken = date("Y-m-d H:i:s");

        // Check if admission numbers are submitted
        if (isset($_POST['admissionNo'])) {
            // Loop through each submitted student data
            foreach ($_POST['admissionNo'] as $index => $admissionNo) {
                // Retrieve data for the current student
                $status = isset($_POST['status'][$index]) ? $_POST['status'][$index] : 0;
                $lateArrival = isset($_POST['lateArrival'][$index]) ? 1 : 0;
                $justification = isset($_POST['justifier'][$index]) ? 1 : 0;
                $latenessDuration = isset($_POST['latenessDuration'][$index]) ? $_POST['latenessDuration'][$index] : 0;
                $description = isset($_POST['description'][$index]) ? $_POST['description'][$index] : '';

                // Insert data into tblattendance table
                $stmt = $conn->prepare("INSERT INTO tblattendance (admissionNo, classId, classArmId, sessionTermId, status, dateTimeTaken, lateArrival, justification, latenessDuration, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssisiiis", $admissionNo, $_SESSION['classId'], $_SESSION['classArmId'], $sessionTermId, $status, $dateTimeTaken, $lateArrival, $justification, $latenessDuration, $description);
                $stmt->execute();
                $stmt->close();
            }

            // Display success message for 5 seconds before redirecting
            $statusMsg = "<div class='alert alert-success' style='margin-right:700px;'>Attendance Taken Successfully!</div>";
            $statusMsg .= "<script>setTimeout(function() { window.location.href = 'takeAttendance.php'; }, 5000);</script>";
        } else {
            // If admission numbers are not submitted, show an error message
            $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>No students selected.</div>";
        }
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
    <title>Dashboard</title>
    <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">

    <script>
        function toggleLatenessDuration(checkbox) {
            var latenessDurationInput = checkbox.closest('tr').querySelector('.lateness-duration-input');
            latenessDurationInput.disabled = !checkbox.checked;
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
                        <h1 class="h3 mb-0 text-gray-800">Take Attendance (Today's Date: <?php echo $todaysDate = date("m-d-Y"); ?>)</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Take Attendance</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <?php if (!empty($statusMsg)) echo $statusMsg; ?>
                            <!-- Input Group -->
                            <form method="post">
                                <div class="row">
                                <div class="col-lg-12">
                                        <div class="card mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                                <h6 class="m-0 font-weight-bold text-primary">All Students in (<?php echo $row['className'] . ' ' . $row['classArmName']; ?>)</h6>
                                            </div>
                                            <div class="table-responsive p-3">
                                                <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Admission No</th>
                                                            <th>Name</th>
                                                            <th>Class</th>
                                                            <th>Status</th>
                                                            <th>Late Arrival</th>
                                                            <th>Justification</th>
                                                            <th>Lateness Duration</th>
                                                            <th>Description</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $queryStudents = "SELECT * FROM tblstudents WHERE classId = ? AND classArmId = ?";
                                                        $stmtStudents = $conn->prepare($queryStudents);
                                                        $stmtStudents->bind_param("ss", $_SESSION['classId'], $_SESSION['classArmId']);
                                                        $stmtStudents->execute();
                                                        $resultStudents = $stmtStudents->get_result();

                                                        while ($rowStudent = $resultStudents->fetch_assoc()) {
                                                        ?>
                                                            <tr>
                                                                <td><?php echo $rowStudent['admissionNumber']; ?></td>
                                                                <td><?php echo $rowStudent['firstName'] . ' ' . $rowStudent['lastName']; ?></td>
                                                                <td><?php echo $row['className'] . ' ' . $row['classArmName']; ?></td>
                                                                <td>
                                                                    <input type="hidden" name="admissionNo[]" value="<?php echo $rowStudent['admissionNumber']; ?>">
                                                                    <input type="checkbox" name="status[]" value="1" <?php if ($rowStudent['status'] == 1) echo "checked"; ?>>
                                                                </td>
                                                                <td>
                                                                    <input type="checkbox" name="lateArrival[]" value="<?php echo $rowStudent['admissionNumber']; ?>" onchange="toggleLatenessDuration(this);" <?php if ($rowStudent['lateArrival'] == 1) echo "checked"; ?>>
                                                                </td>
                                                                <td>
                                                                    <input type="checkbox" name="justifier[]" value="<?php echo $rowStudent['admissionNumber']; ?>" <?php if ($rowStudent['justification'] == 1) echo "checked"; ?>>
                                                                </td>
                                                                <td><input type="text" name="latenessDuration[]" class="lateness-duration-input" value="<?php echo $rowStudent['latenessDuration']; ?>" <?php if ($rowStudent['lateArrival'] != 1) echo "disabled"; ?>></td>
                                                                <td><textarea name="description[]"><?php echo $rowStudent['description']; ?></textarea></td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="card-footer"></div>
                                            <input type="submit" class="btn btn-primary" name="save" value="Save Attendance">
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <!-- Input Group -->
                        </div>
                    </div>
                    <!--Row-->

                </div>
                <!---Container Fluid-->
            </div>
            <!-- Footer -->
            <?php include "Includes/footer.php"; ?>
            <!-- Footer -->
        </div>
    </div>
    <!-- Scroll to Top Button-->
    <?php include "Includes/scroll-to-top.php"; ?>
    <!-- Logout Modal-->
    <?php include "Includes/logout-modal.php"; ?>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/ruang-admin.min.js"></script>
    <script src="../vendor/chart.js/Chart.min.js"></script>
    <script src="../js/demo/chart-area-demo.js"></script>

</body>

</html>
