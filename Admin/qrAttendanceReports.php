<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';
include '../Includes/qr_attendance.php';

if (($_SESSION['userType'] ?? '') !== 'Administrator') {
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

date_default_timezone_set('Asia/Kolkata');

function qrReportEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$pageSize = 50;
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? $page : 1;
$offset = ($page - 1) * $pageSize;
$sessionCountResult = $conn->query('SELECT COUNT(*) AS total FROM qr_attendance_sessions');
if (!$sessionCountResult) {
    error_log('Admin QR attendance report count failed: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load QR attendance sessions.');
}
$totalSessions = (int)$sessionCountResult->fetch_assoc()['total'];
$totalPages = max(1, (int)ceil($totalSessions / $pageSize));
$page = min($page, $totalPages);
$offset = ($page - 1) * $pageSize;

$sessionSql = "
    SELECT session.id, session.attendance_date, session.generated_at, session.expires_at, session.status,
           COALESCE(course.courseName, CONCAT('Subject ', session.subject_id)) AS subject_name,
           COALESCE(NULLIF(TRIM(CONCAT(teacher.firstName, ' ', teacher.lastName)), ''), 'Faculty') AS teacher_name,
           COALESCE(tblclass.className, CONCAT('Class ', session.class_id)) AS class_name,
           COALESCE(tblclassarms.classArmName, CONCAT('Group ', session.class_arm_id)) AS class_arm_name,
           COALESCE(roster_stats.total_students, 0) AS total_students,
           COALESCE(roster_stats.present_students, 0) AS present_students,
           COALESCE(roster_stats.absent_students, 0) AS absent_students,
           COALESCE(roster_stats.location_verified_students, 0) AS location_verified_students
    FROM qr_attendance_sessions session
    LEFT JOIN course ON course.courseId = session.subject_id
    LEFT JOIN tblclassteacher teacher ON teacher.Id = session.teacher_id
    LEFT JOIN tblclass ON tblclass.Id = session.class_id
    LEFT JOIN tblclassarms ON tblclassarms.Id = session.class_arm_id
    LEFT JOIN (
        SELECT qr_session_id, COUNT(*) AS total_students,
               SUM(status = 'present') AS present_students,
               SUM(status = 'absent') AS absent_students,
               SUM(location_verified = 1) AS location_verified_students
        FROM qr_attendance_records
        GROUP BY qr_session_id
    ) roster_stats ON roster_stats.qr_session_id = session.id
    ORDER BY session.generated_at DESC, session.id DESC
    LIMIT ? OFFSET ?";
$sessionsStmt = $conn->prepare($sessionSql);
if (!$sessionsStmt) {
    error_log('Admin QR attendance report query could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load QR attendance sessions.');
}
$sessionsStmt->bind_param('ii', $pageSize, $offset);
$sessionsStmt->execute();
$sessions = $sessionsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sessionsStmt->close();

$selectedSessionId = filter_var($_GET['session_id'] ?? null, FILTER_VALIDATE_INT);
$selectedSession = null;
if ($selectedSessionId && $selectedSessionId > 0) {
    $selectedStmt = $conn->prepare(
        "SELECT session.id, session.attendance_date, session.generated_at, session.expires_at, session.status,
                COALESCE(course.courseName, CONCAT('Subject ', session.subject_id)) AS subject_name,
                COALESCE(NULLIF(TRIM(CONCAT(teacher.firstName, ' ', teacher.lastName)), ''), 'Faculty') AS teacher_name,
                COALESCE(tblclass.className, CONCAT('Class ', session.class_id)) AS class_name,
                COALESCE(tblclassarms.classArmName, CONCAT('Group ', session.class_arm_id)) AS class_arm_name,
                COALESCE(roster_stats.total_students, 0) AS total_students,
                COALESCE(roster_stats.present_students, 0) AS present_students,
                COALESCE(roster_stats.absent_students, 0) AS absent_students,
                COALESCE(roster_stats.location_verified_students, 0) AS location_verified_students
         FROM qr_attendance_sessions session
         LEFT JOIN course ON course.courseId = session.subject_id
         LEFT JOIN tblclassteacher teacher ON teacher.Id = session.teacher_id
         LEFT JOIN tblclass ON tblclass.Id = session.class_id
         LEFT JOIN tblclassarms ON tblclassarms.Id = session.class_arm_id
         LEFT JOIN (
             SELECT qr_session_id, COUNT(*) AS total_students,
                    SUM(status = 'present') AS present_students,
                    SUM(status = 'absent') AS absent_students,
                    SUM(location_verified = 1) AS location_verified_students
             FROM qr_attendance_records
             GROUP BY qr_session_id
         ) roster_stats ON roster_stats.qr_session_id = session.id
         WHERE session.id = ?
         "
    );
    if (!$selectedStmt) {
        error_log('Admin QR attendance session detail query could not be prepared: ' . $conn->error);
        http_response_code(500);
        exit('Unable to load the selected QR attendance session.');
    }
    $selectedStmt->bind_param('i', $selectedSessionId);
    $selectedStmt->execute();
    $selectedSession = $selectedStmt->get_result()->fetch_assoc();
    $selectedStmt->close();
}
if (!$selectedSession && $sessions) {
    $selectedSession = $sessions[0];
    $selectedSessionId = (int)$selectedSession['id'];
}

$records = [];
if ($selectedSessionId) {
    $recordsStmt = $conn->prepare(
        "SELECT student_id, admission_number, student_name, status, marked_at,
                latitude, longitude, location_accuracy_m, distance_m, location_verified
         FROM qr_attendance_records
         WHERE qr_session_id = ?
         ORDER BY admission_number"
    );
    if (!$recordsStmt) {
        error_log('Admin QR attendance student detail query could not be prepared: ' . $conn->error);
        http_response_code(500);
        exit('Unable to load QR attendance student records.');
    }
    $recordsStmt->bind_param('i', $selectedSessionId);
    $recordsStmt->execute();
    $records = $recordsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $recordsStmt->close();
}

$currentTime = qrAttendanceNow();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Attendance Reports | Attendly Admin</title>
    <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'Includes/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'Includes/topbar.php'; ?>
            <main class="container-fluid" id="container-wrapper">
                <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
                    <div>
                        <h1 class="h3 mb-1 text-gray-800">QR Attendance Reports</h1>
                        <p class="text-muted mb-0">Faculty sessions, roster status, and GPS verification records.</p>
                    </div>
                    <a class="btn btn-outline-primary mt-2" href="qrAttendanceSettings.php">
                        <i class="fas fa-map-marker-alt mr-1"></i> College location settings
                    </a>
                </div>

                <section class="card shadow-sm mb-4">
                    <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-primary">Faculty QR sessions</h2></div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="thead-light">
                            <tr><th>Date / Time</th><th>Class / Group</th><th>Subject</th><th>Faculty</th><th>Session status</th><th>Students</th><th>Present</th><th>Absent</th><th>Location verified</th><th></th></tr>
                            </thead>
                            <tbody>
                            <?php if (!$sessions): ?>
                                <tr><td class="text-center text-muted" colspan="10">No QR attendance sessions have been created.</td></tr>
                            <?php else: foreach ($sessions as $session):
                                $status = $session['status'];
                                if ($status === 'active'
                                    && $currentTime >= new DateTimeImmutable($session['expires_at'], qrAttendanceTimezone())) {
                                    $status = 'expired';
                                }
                                ?>
                                <tr>
                                    <td><?= qrReportEscape(date('d M Y', strtotime($session['attendance_date']))) ?><br><small><?= qrReportEscape(date('h:i A', strtotime($session['generated_at']))) ?></small></td>
                                    <td><?= qrReportEscape($session['class_name'] . ' / ' . $session['class_arm_name']) ?></td>
                                    <td><?= qrReportEscape($session['subject_name']) ?></td>
                                    <td><?= qrReportEscape($session['teacher_name']) ?></td>
                                    <td><span class="badge badge-<?= $status === 'active' ? 'success' : 'secondary' ?>"><?= qrReportEscape(ucfirst($status)) ?></span></td>
                                    <td><?= (int)$session['total_students'] ?></td>
                                    <td><?= (int)$session['present_students'] ?></td>
                                    <td><?= (int)$session['absent_students'] ?></td>
                                    <td><?= (int)$session['location_verified_students'] ?></td>
                                    <td><a href="?page=<?= (int)$page ?>&amp;session_id=<?= (int)$session['id'] ?>">View records</a></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <span class="small text-muted">Page <?= (int)$page ?> of <?= (int)$totalPages ?> · <?= (int)$totalSessions ?> sessions</span>
                        <div>
                            <?php if ($page > 1): ?><a class="btn btn-sm btn-outline-secondary" href="?page=<?= $page - 1 ?>">Previous</a><?php endif; ?>
                            <?php if ($page < $totalPages): ?><a class="btn btn-sm btn-outline-secondary" href="?page=<?= $page + 1 ?>">Next</a><?php endif; ?>
                        </div>
                    </div>
                </section>

                <?php if ($selectedSession): ?>
                    <?php
                    $selectedStatus = $selectedSession['status'];
                    if ($selectedStatus === 'active'
                        && $currentTime >= new DateTimeImmutable($selectedSession['expires_at'], qrAttendanceTimezone())) {
                        $selectedStatus = 'expired';
                    }
                    ?>
                    <section class="card shadow-sm">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap">
                            <h2 class="h6 m-0 font-weight-bold text-primary">Student records · <?= qrReportEscape($selectedSession['subject_name']) ?></h2>
                            <span class="badge badge-<?= $selectedStatus === 'active' ? 'success' : 'secondary' ?>"><?= qrReportEscape(ucfirst($selectedStatus)) ?></span>
                        </div>
                        <div class="card-body">
                            <p><strong><?= qrReportEscape($selectedSession['class_name'] . ' / ' . $selectedSession['class_arm_name']) ?></strong>
                                · <?= qrReportEscape($selectedSession['teacher_name']) ?>
                                · <?= qrReportEscape(date('d M Y h:i A', strtotime($selectedSession['generated_at']))) ?></p>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="thead-light">
                                    <tr><th>Student ID</th><th>USN / Register No.</th><th>Student name</th><th>Status</th><th>Marked at</th><th>Latitude</th><th>Longitude</th><th>Accuracy</th><th>Distance</th><th>Location verification</th></tr>
                                    </thead>
                                    <tbody>
                                    <?php if (!$records): ?>
                                        <tr><td colspan="10" class="text-center text-muted">No student records are available.</td></tr>
                                    <?php else: foreach ($records as $record): ?>
                                        <tr>
                                            <td><?= (int)$record['student_id'] ?></td>
                                            <td><?= qrReportEscape($record['admission_number']) ?></td>
                                            <td><?= qrReportEscape($record['student_name']) ?></td>
                                            <td><?= qrReportEscape(ucfirst($record['status'])) ?></td>
                                            <td><?= $record['marked_at'] ? qrReportEscape(date('d M Y h:i:s A', strtotime($record['marked_at']))) : '—' ?></td>
                                            <td><?= $record['latitude'] !== null ? qrReportEscape($record['latitude']) : '—' ?></td>
                                            <td><?= $record['longitude'] !== null ? qrReportEscape($record['longitude']) : '—' ?></td>
                                            <td><?= $record['location_accuracy_m'] !== null ? qrReportEscape($record['location_accuracy_m'] . ' m') : '—' ?></td>
                                            <td><?= $record['distance_m'] !== null ? qrReportEscape(number_format((float)$record['distance_m'], 1) . ' m') : '—' ?></td>
                                            <td><?= (int)$record['location_verified'] === 1 ? 'Inside premises' : 'Not verified' ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>
<script src="../vendore/jquery/jquery.min.js"></script>
<script src="../vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/ruang-admin.min.js"></script>
</body>
</html>
