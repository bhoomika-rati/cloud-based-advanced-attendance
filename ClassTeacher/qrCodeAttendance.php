<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';
require_once '../vendor/autoload.php'; // Path to Composer autoload file

use Libern\QRCodeReader\QRCodeReader;

$statusMsg = '';

// Get class and class arm details
$query = "SELECT tblclass.Id as class_id, tblclassarms.Id as class_arm_id 
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

if ($row) {
    $classId = $row['class_id'];
    $classArmId = $row['class_arm_id'];
} else {
    die("Class and Class Arm details not found for the current user.");
}

// Session and Term
$querySessionTerm = "SELECT * FROM tblsessionterm WHERE isActive ='1' AND teacher_id = ?";
$stmtSessionTerm = $conn->prepare($querySessionTerm);
$stmtSessionTerm->bind_param("s", $_SESSION['userId']);
$stmtSessionTerm->execute();
$resultSessionTerm = $stmtSessionTerm->get_result();
$rowSessionTerm = $resultSessionTerm->fetch_assoc();
$stmtSessionTerm->close();

if ($rowSessionTerm) {
    $sessionTermId = $rowSessionTerm['Id'];
    $startTime = strtotime($rowSessionTerm['startTime']);
    $endTime = strtotime($rowSessionTerm['endTime']);
} else {
    die("Active session term not found for the current teacher.");
}

// Get the list of students' admission numbers
$admissionNumbers = [];
$queryStudents = "SELECT admissionNumber FROM tblstudents WHERE classId = ? AND classArmId = ?";
$stmtStudents = $conn->prepare($queryStudents);
$stmtStudents->bind_param("ss", $classId, $classArmId);
$stmtStudents->execute();
$resultStudents = $stmtStudents->get_result();

while ($rowStudent = $resultStudents->fetch_assoc()) {
    $admissionNumbers[] = $rowStudent['admissionNumber'];
}
$stmtStudents->close();

// Check if the timer has already started for the current session
if (!isset($_SESSION['attendanceTimerStarted'])) {
    // Start the timer
    $_SESSION['attendanceTimerStarted'] = true;
    $_SESSION['attendanceStartTime'] = time();
}

// Function to mark students as absent after 20 minutes
function markAbsentStudents($admissionNumbers, $classId, $classArmId, $sessionTermId, $conn) {
    foreach ($admissionNumbers as $admissionNumber) {
        $queryCheck = "SELECT COUNT(*) AS count FROM tblattendance WHERE admissionNo = ? AND classId = ? AND classArmId = ? AND sessionTermId = ?";
        $stmtCheck = $conn->prepare($queryCheck);
        $stmtCheck->bind_param("sssi", $admissionNumber, $classId, $classArmId, $sessionTermId);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $rowCheck = $resultCheck->fetch_assoc();
        $stmtCheck->close();

        if ($rowCheck['count'] == 0) {
            $dateTimeTaken = date("Y-m-d H:i:s");
            $status = 0;
            $stmtAbsent = $conn->prepare("INSERT INTO tblattendance (admissionNo, classId, classArmId, sessionTermId, status, dateTimeTaken) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtAbsent->bind_param("sssiss", $admissionNumber, $classId, $classArmId, $sessionTermId, $status, $dateTimeTaken);
            $stmtAbsent->execute();
            $stmtAbsent->close();
        }
    }
}

// Start a timer for 20 minutes
if (!isset($_SESSION['attendanceTimerExpired']) && time() >= $startTime && time() <= $endTime) {
    $startTime = $_SESSION['attendanceStartTime'];
    $currentTime = time();
    $elapsedTime = $currentTime - $startTime;
    
    if ($elapsedTime >= 1200) { // 20 minutes in seconds
        // 20 minutes have passed, mark absent students
        markAbsentStudents($admissionNumbers, $classId, $classArmId, $sessionTermId, $conn);
        unset($_SESSION['attendanceTimerStarted']);
        unset($_SESSION['attendanceStartTime']);
        $_SESSION['attendanceTimerExpired'] = true;
    }
}

// Handle QR code submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if file was uploaded without errors
    if (isset($_FILES["qrCode"]) && $_FILES["qrCode"]["error"] == 0) {
        // Load the uploaded QR code image
        $filename = $_FILES["qrCode"]["tmp_name"];
        
        // Instantiate the QR reader
        $qrcodeReader = new QRCodeReader();
        $qrCodeContent = $qrcodeReader->decode($filename);
        
        // Check if the QR code content matches any admission number
        if (in_array($qrCodeContent, $admissionNumbers)) {
            // Insert the student as present
            $dateTimeTaken = date("Y-m-d H:i:s");
            $status = 1;
            $stmt = $conn->prepare("INSERT INTO tblattendance (admissionNo, classId, classArmId, sessionTermId, status, dateTimeTaken) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiss", $qrCodeContent, $classId, $classArmId, $sessionTermId, $status, $dateTimeTaken);
            $stmt->execute();
            $stmt->close();
            $statusMsg = "Student with admission number $qrCodeContent marked as present.";
        } else {
            $statusMsg = "QR code content does not match any student.";
        }
    } else {
        $statusMsg = "Error uploading file.";
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include "Includes/sidebar.php"; ?>
        <!--Sidebar -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- TopBar -->
                <?php include "Includes/topbar.php"; ?>
                <!-- Topbar -->

                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">QR Code Attendance</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">QR Code Attendance</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Upload QR Code</h6>
                                </div>
                                <div class="card-body">
                                    <form action="qrCodeAttendance.php" method="post" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label for="qrCode">Select QR Code Image</label>
                                            <?php if (!isset($_SESSION['attendanceTimerExpired']) && time() >= $startTime && time() <= $endTime): ?>
                                            <input type="file" class="form-control" name="qrCode" id="qrCode" required>
                                            <?php else: ?>
                                            <p>Attendance marking time over.</p>
                                            <?php endif; ?>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Upload</button>
                                        <?php if ($statusMsg): ?>
                                            <div class="alert alert-info mt-3" role="alert">
                                                <?php echo $statusMsg; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!isset($_SESSION['attendanceTimerExpired']) && time() >= $startTime && time() <= $endTime): ?>
                                        <div id="timer"></div>
                                        <?php endif; ?>
                                    </form>
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

    <script src="../vendore/jquery/jquery.min.js"></script>
    <script src="../vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendore/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>

    <script>
        <?php if (!isset($_SESSION['attendanceTimerExpired']) && time() >= $startTime && time() <= $endTime): ?>
        var startTime = <?php echo $startTime; ?>;
        var endTime = <?php echo $endTime; ?>;
        var countdown = setInterval(function () {
            var now = Math.floor(Date.now() / 1000);
            var remainingTime = endTime - now;

            if (remainingTime <= 0) {
                clearInterval(countdown);
                document.getElementById('qrCode').style.display = 'none';
                document.getElementById('timer').innerHTML = 'Attendance marking time over.';
                
                // Automatically mark absent students who haven't marked attendance
                markAbsentStudents(<?php echo json_encode($admissionNumbers); ?>, <?php echo $classId; ?>, <?php echo $classArmId; ?>, <?php echo $sessionTermId; ?>, <?php echo json_encode($conn); ?>);
            } else {
                var minutes = Math.floor(remainingTime / 60);
                var seconds = remainingTime % 60;
                document.getElementById('timer').innerHTML = 'Time Remaining: ' + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            }
        }, 1000);
        <?php endif; ?>

        // Function to mark absent students after 20 minutes
        function markAbsentStudents(admissionNumbers, classId, classArmId, sessionTermId, conn) {
            admissionNumbers.forEach(function(admissionNumber) {
                $.ajax({
                    url: 'qrCodeAttendance.php',
                    type: 'POST',
                    data: {
                        markAbsent: true,
                        admissionNumber: admissionNumber,
                        classId: classId,
                        classArmId: classArmId,
                        sessionTermId: sessionTermId
                    },
                    success: function(response) {
                        console.log(response); // Optional: Log the response
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            });
        }
    </script>

</body>

</html>
