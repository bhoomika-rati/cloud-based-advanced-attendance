<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate session user ID
if (!isset($_SESSION['userId'])) {
    die("User session not found. Please login.");
}

// Fetch student information
$query = "SELECT tblstudents.*, tblclass.className, tblclassarms.classArmName
          FROM tblstudents
          LEFT JOIN tblclass ON tblclass.Id = tblstudents.classId
          LEFT JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
          WHERE tblstudents.Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();

// Check if student record exists
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fullName = $row['firstName'] . " " . $row['lastName'];
    $className = isset($row['className']) ? $row['className'] : 'N/A';
    $classArmName = isset($row['classArmName']) ? $row['classArmName'] : 'N/A';
    $selectedClassArm = $row['classArmId'];
} else {
    die("Student record not found.");
}

$stmt->close();

// Fetch timetable entries
$timetableQuery = "SELECT st.startTime, st.endTime, st.sessionName, st.type, CONCAT(ct.firstName, ' ', ct.lastName) AS teacher, st.day_name
                   FROM timetable t
                   INNER JOIN timetable_session ts ON t.id_Timetable = ts.Timetable_id
                   INNER JOIN tblsessionterm st ON ts.Session_id = st.Id
                   INNER JOIN tblclassteacher ct ON st.teacher_id = ct.Id
                   WHERE t.tblclassarms_id = ?";
$timetableStmt = $conn->prepare($timetableQuery);
$timetableStmt->bind_param("s", $selectedClassArm);
$timetableStmt->execute();
$timetableResult = $timetableStmt->get_result();

$timetableEntries = [];
while ($timetableRow = $timetableResult->fetch_assoc()) {
    $timetableEntries[] = $timetableRow;
}

$timetableStmt->close();
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
    <title>View Timetable</title>
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
                        <h1 class="h3 mb-0 text-gray-800">View Timetable</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">View Timetable</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">View Timetable</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($timetableEntries)) : ?>
                                        <h5>Timetable for <?php echo htmlspecialchars($className . '/' . $classArmName); ?></h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Day/Time</th>
                                                        <th>08H30 - 10H30</th>
                                                        <th>10H30 - 12H30</th>
                                                        <th>14H30 - 16H30</th>
                                                        <th>16H30 - 18H30</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $timeSlots = [
                                                        '08:30:00-10:30:00' => '08H30 - 10H30',
                                                        '10:30:00-12:30:00' => '10H30 - 12H30',
                                                        '14:30:00-16:30:00' => '14H30 - 16H30',
                                                        '16:30:00-18:30:00' => '16H30 - 18H30'
                                                    ];

                                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                                    $daysTimetable = array_fill_keys($days, array_fill_keys(array_keys($timeSlots), ''));

                                                    foreach ($timetableEntries as $entry) {
                                                        $startTime = $entry['startTime'];
                                                        $endTime = $entry['endTime'];
                                                        $sessionInfo = htmlspecialchars($entry['sessionName'] . " (" . $entry['type'] . ") - " . $entry['teacher']);

                                                        foreach ($timeSlots as $slot => $label) {
                                                            list($slotStart, $slotEnd) = explode('-', $slot);
                                                            if ($startTime >= $slotStart && $endTime <= $slotEnd) {
                                                                $daysTimetable[$entry['day_name']][$slot] = $sessionInfo;
                                                            }
                                                        }
                                                    }

                                                    foreach ($daysTimetable as $day => $entries) {
                                                        echo "<tr>";
                                                        echo "<td>$day</td>";
                                                        foreach ($entries as $entry) {
                                                            echo "<td>" . ($entry ?: '') . "</td>";
                                                        }
                                                        echo "</tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else : ?>
                                        <div class="alert alert-info">No timetable entries found for the selected class/arm.</div>
                                    <?php endif; ?>
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
</body>

</html>
