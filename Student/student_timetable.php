<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate session user ID
if (!isset($_SESSION['userId'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch student information
$query = "SELECT tblstudents.*, tblclass.className, tblclassarms.classArmName
          FROM tblstudents
          LEFT JOIN tblclass ON tblclass.Id = tblstudents.classId
          LEFT JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
          WHERE tblstudents.Id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $fullName = $row['firstName'] . " " . $row['lastName'];
    $className = $row['className'] ?: 'N/A';
    $classArmName = $row['classArmName'] ?: 'N/A';
    $selectedClassArm = intval($row['classArmId']);
} else {
    die("Student record not found.");
}
$stmt->close();

// Fetch timetable entries from session_timetable
$timetableQuery = "
    SELECT st.start_time, st.end_time, st.slot_type, st.day_name,
           c.courseName, CONCAT(ct.firstName, ' ', ct.lastName) AS teacherName,
           s.sessionName
    FROM session_timetable st
    LEFT JOIN course c ON c.courseId = st.subject_id
    LEFT JOIN tblclassteacher ct ON ct.Id = st.teacher_id
    LEFT JOIN tblsessionterm s ON s.Id = st.session_id
    WHERE st.class_arm_id = ?
    ORDER BY FIELD(st.day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), st.start_time ASC
";
$timetableStmt = $conn->prepare($timetableQuery);
$timetableStmt->bind_param("i", $selectedClassArm);
$timetableStmt->execute();
$timetableResult = $timetableStmt->get_result();

$entriesByDay = [
    'Monday' => [],
    'Tuesday' => [],
    'Wednesday' => [],
    'Thursday' => [],
    'Friday' => [],
    'Saturday' => []
];
$totalEntries = 0;
while ($tRow = $timetableResult->fetch_assoc()) {
    $d = $tRow['day_name'];
    if (isset($entriesByDay[$d])) {
        $entriesByDay[$d][] = $tRow;
    } else {
        $entriesByDay[$d] = [$tRow];
    }
    $totalEntries++;
}
$timetableStmt->close();
$currentDayName = date('l');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <title>My Timetable | Attendly</title>
    <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <link href="../Admin/css/admin-compact.css" rel="stylesheet">
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800">My Class Timetable</h1>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($className . ' — Group ' . $classArmName); ?></p>
                        </div>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Timetable</li>
                        </ol>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-calendar-alt mr-1"></i> Weekly Class Schedule
                            </h6>
                            <span class="badge badge-light border"><?php echo $totalEntries; ?> Scheduled Classes</span>
                        </div>

                        <div class="timetable-grid">
                            <?php foreach ($entriesByDay as $dayName => $dayEntries): ?>
                                <?php $isToday = (strcasecmp($dayName, $currentDayName) === 0); ?>
                                <div class="timetable-day-col">
                                    <div class="timetable-day-header <?php echo $isToday ? 'is-today' : ''; ?>">
                                        <?php echo $dayName; ?>
                                        <?php if ($isToday): ?><span class="badge badge-primary ml-1" style="font-size:0.65rem;">TODAY</span><?php endif; ?>
                                        <div class="small font-weight-normal text-muted"><?php echo count($dayEntries); ?> classes</div>
                                    </div>
                                    <div class="timetable-day-slots">
                                        <?php if (empty($dayEntries)): ?>
                                            <div class="text-center py-4 text-muted small font-italic">No classes</div>
                                        <?php else: ?>
                                            <?php foreach ($dayEntries as $entry): ?>
                                                <?php
                                                $stBadgeClass = 'slot-badge-lecture';
                                                $sType = strtolower($entry['slot_type']);
                                                if (strpos($sType, 'lab') !== false || strpos($sType, 'tp') !== false) {
                                                    $stBadgeClass = 'slot-badge-lab';
                                                } elseif (strpos($sType, 'tut') !== false || strpos($sType, 'td') !== false) {
                                                    $stBadgeClass = 'slot-badge-tutorial';
                                                } elseif (strpos($sType, 'course') !== false) {
                                                    $stBadgeClass = 'slot-badge-course';
                                                }
                                                ?>
                                                <div class="timetable-slot-card">
                                                    <div class="slot-time">
                                                        <span><i class="far fa-clock mr-1"></i> <?php echo substr($entry['start_time'], 0, 5) . ' – ' . substr($entry['end_time'], 0, 5); ?></span>
                                                        <span class="slot-badge <?php echo $stBadgeClass; ?>"><?php echo htmlspecialchars($entry['slot_type']); ?></span>
                                                    </div>
                                                    <div class="slot-subject"><?php echo htmlspecialchars($entry['courseName'] ?: 'Course'); ?></div>
                                                    <div class="slot-meta">
                                                        <div><i class="fas fa-user-tie mr-1 text-muted"></i> <?php echo htmlspecialchars($entry['teacherName'] ?: 'Assigned Teacher'); ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalEntries === 0): ?>
                            <div class="timetable-empty-state">
                                <i class="far fa-calendar-times"></i>
                                <h5>No Timetable Scheduled</h5>
                                <p class="text-muted small">Your class schedule has not been published yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <?php include "Includes/footer.php"; ?>
        </div>
    </div>

    <!-- Scroll to top -->
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <script src="../vendore/jquery/jquery.min.js"></script>
    <script src="../vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendore/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
</body>
</html>
