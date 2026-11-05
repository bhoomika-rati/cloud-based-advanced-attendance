<?php
include '../Includes/dbcon.php';
include '../Includes/qr_attendance.php';
include '../Includes/app_config.php';
require_once '../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

if (($_SESSION['userType'] ?? '') !== 'ClassTeacher') {
    header('Location: ../index.php');
    exit;
}

date_default_timezone_set('Asia/Kolkata');
$teacherId = filter_var($_SESSION['userId'] ?? null, FILTER_VALIDATE_INT);
if (!$teacherId) {
    header('Location: ../index.php');
    exit;
}

$websiteBaseUrl = '';
$configurationError = '';
try {
    $websiteBaseUrl = attendanceWebsiteBaseUrl();
} catch (RuntimeException $exception) {
    $configurationError = $exception->getMessage();
}
try {
    qrAttendanceTokenKey();
} catch (RuntimeException $exception) {
    $configurationError = trim($configurationError . ' ' . $exception->getMessage());
}

if (empty($_SESSION['qrAttendanceCsrf'])) {
    $_SESSION['qrAttendanceCsrf'] = bin2hex(random_bytes(32));
}
function qrPageEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$teacherStmt = $conn->prepare(
    "SELECT Id FROM tblclassteacher WHERE Id = ? LIMIT 1"
);
if (!$teacherStmt) {
    die('Unable to load your class assignment.');
}
$teacherStmt->bind_param('i', $teacherId);
$teacherStmt->execute();
$teacher = $teacherStmt->get_result()->fetch_assoc();
$teacherStmt->close();
if (!$teacher) {
    die('Your teacher account does not have a class assignment.');
}

$dashboardNow = qrAttendanceNow();
$today = $dashboardNow->format('Y-m-d');
$todayDay = $dashboardNow->format('l');
$scheduleStmt = $conn->prepare(
    "SELECT DISTINCT st.id, st.session_id, st.class_id, st.class_arm_id,
            st.subject_id, st.day_name, st.start_time, st.end_time,
            COALESCE(course.courseName, CONCAT('Subject ', st.subject_id)) AS subject_name,
            COALESCE(NULLIF(course.courseCode, ''), CONCAT('SUBJ-', st.subject_id)) AS subject_code,
            term.sessionName, tblclass.className, tblclassarms.classArmName
     FROM session_timetable st
     INNER JOIN tblsessionterm term ON term.Id = st.session_id AND term.isActive = '1'
     LEFT JOIN course ON course.courseId = st.subject_id
     LEFT JOIN tblclass ON tblclass.Id = st.class_id
     LEFT JOIN tblclassarms ON tblclassarms.Id = st.class_arm_id
     WHERE st.teacher_id = ? AND st.day_name = ?
     ORDER BY st.day_name, st.start_time, subject_name"
);
if (!$scheduleStmt) {
    die('Unable to load your active timetable.');
}
$scheduleStmt->bind_param('is', $teacherId, $todayDay);
$scheduleStmt->execute();
$scheduleResult = $scheduleStmt->get_result();
$schedules = [];
while ($schedule = $scheduleResult->fetch_assoc()) {
    $schedules[(int)$schedule['id']] = $schedule;
}
$scheduleStmt->close();

$pageError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = $_POST['csrf_token'] ?? '';
    $timetableId = filter_var($_POST['timetable_id'] ?? null, FILTER_VALIDATE_INT);
    $attendanceDate = trim((string)($_POST['attendance_date'] ?? ''));
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $attendanceDate, qrAttendanceTimezone());
    $dateErrors = DateTimeImmutable::getLastErrors();
    $today = qrAttendanceNow()->format('Y-m-d');

    if (!is_string($postedCsrf) || !hash_equals($_SESSION['qrAttendanceCsrf'], $postedCsrf)) {
        $pageError = 'Your request expired. Refresh the page and try again.';
    } elseif ($configurationError !== '') {
        $pageError = $configurationError;
    } elseif (!$timetableId || !isset($schedules[$timetableId])) {
        $pageError = 'Select a subject assigned to your class.';
    } elseif (!$date || ($dateErrors && ($dateErrors['warning_count'] || $dateErrors['error_count']))
        || $date->format('Y-m-d') !== $attendanceDate || $attendanceDate !== $today) {
        $pageError = 'Attendance QR sessions can only be generated for today.';
    } elseif ($date->format('l') !== $schedules[$timetableId]['day_name']) {
        $pageError = 'The selected subject is not scheduled for this day.';
    } else {
        $schedule = $schedules[$timetableId];
        $scheduleClassId = (int)$schedule['class_id'];
        $scheduleClassArmId = (int)$schedule['class_arm_id'];
        $lockName = 'qr_attendance_class_' . $scheduleClassId . '_arm_' . $scheduleClassArmId;
        $lockStmt = $conn->prepare('SELECT GET_LOCK(?, 10) AS acquired');
        $lockStmt->bind_param('s', $lockName);
        $lockStmt->execute();
        $lockAcquired = (int)$lockStmt->get_result()->fetch_assoc()['acquired'] === 1;
        $lockStmt->close();

        if (!$lockAcquired) {
            $pageError = 'Another attendance session is being created. Please try again.';
        } else {
            $transactionStarted = false;
            try {
                $conn->begin_transaction();
                $transactionStarted = true;
                $activeStmt = $conn->prepare(
                    "SELECT * FROM qr_attendance_sessions
                     WHERE class_id = ? AND class_arm_id = ? AND subject_id = ?
                       AND status = 'active'
                     FOR UPDATE"
                );
                $activeSubjectId = (int)$schedule['subject_id'];
                $activeStmt->bind_param('iii', $scheduleClassId, $scheduleClassArmId, $activeSubjectId);
                $activeStmt->execute();
                $activeSessions = $activeStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $activeStmt->close();

                $now = qrAttendanceNow();
                foreach ($activeSessions as $activeSession) {
                    $expiresAt = new DateTimeImmutable($activeSession['expires_at'], qrAttendanceTimezone());
                    if ($now < $expiresAt) {
                        throw new RuntimeException('An active attendance QR already exists for this class and subject.');
                    }
                    qrFinalizeSessionLocked($conn, $activeSession, $expiresAt);
                }

                $studentsStmt = $conn->prepare(
                    "SELECT Id, admissionNumber, firstName, lastName, otherName
                     FROM tblstudents WHERE classId = ? AND classArmId = ?
                     ORDER BY admissionNumber"
                );
                $studentsStmt->bind_param('ii', $scheduleClassId, $scheduleClassArmId);
                $studentsStmt->execute();
                $students = $studentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $studentsStmt->close();
                if (!$students) {
                    throw new RuntimeException('There are no students enrolled in your class.');
                }

                $durationMinutes = 10;
                $generatedAtTime = qrAttendanceNow();
                $placeholderTokenHash = hash('sha256', random_bytes(32));
                $generatedAt = qrAttendanceSqlTime($generatedAtTime);
                $expiresAtSql = qrAttendanceSqlTime(
                    $generatedAtTime->modify('+' . $durationMinutes . ' minutes')
                );
                $sessionInsert = $conn->prepare(
                    "INSERT INTO qr_attendance_sessions
                     (token_hash, teacher_id, timetable_id, academic_session_id, class_id,
                      class_arm_id, subject_id, attendance_date, generated_at, expires_at,
                      duration_minutes, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')"
                );
                $sessionId = null;
                if (!$sessionInsert) {
                    throw new RuntimeException('Unable to prepare QR session creation.');
                }
                $academicSessionId = (int)$schedule['session_id'];
                $subjectId = (int)$schedule['subject_id'];
                $sessionInsert->bind_param(
                    'siiiiiisssi',
                    $placeholderTokenHash,
                    $teacherId,
                    $timetableId,
                    $academicSessionId,
                    $scheduleClassId,
                    $scheduleClassArmId,
                    $subjectId,
                    $attendanceDate,
                    $generatedAt,
                    $expiresAtSql,
                    $durationMinutes
                );
                if (!$sessionInsert->execute()) {
                    throw new RuntimeException('Unable to create the QR attendance session.');
                }
                $sessionId = (int)$conn->insert_id;
                $sessionInsert->close();

                $sessionTokenHash = hash('sha256', qrAttendanceSessionToken($sessionId));
                $tokenUpdate = $conn->prepare(
                    'UPDATE qr_attendance_sessions SET token_hash = ? WHERE id = ? AND token_hash = ?'
                );
                if (!$tokenUpdate) {
                    throw new RuntimeException('Unable to prepare the session QR token update.');
                }
                $tokenUpdate->bind_param('sis', $sessionTokenHash, $sessionId, $placeholderTokenHash);
                if (!$tokenUpdate->execute() || $tokenUpdate->affected_rows !== 1) {
                    $tokenUpdate->close();
                    throw new RuntimeException('Unable to save the session QR token.');
                }
                $tokenUpdate->close();

                $rosterInsert = $conn->prepare(
                    "INSERT INTO qr_attendance_records
                     (qr_session_id, student_id, admission_number, student_name, qr_token_hash, status)
                     VALUES (?, ?, ?, ?, ?, 'not_marked')"
                );
                if (!$rosterInsert) {
                    throw new RuntimeException('Unable to prepare attendance roster creation.');
                }
                foreach ($students as $student) {
                    $studentId = (int)$student['Id'];
                    $admissionNumber = (string)$student['admissionNumber'];
                    $studentName = trim($student['firstName'] . ' ' . $student['lastName']);
                    $otherName = trim((string)$student['otherName']);
                    if ($otherName !== '' && strtolower($otherName) !== 'none') {
                        $studentName .= ' ' . $otherName;
                    }
                    $rosterTokenHash = null;
                    $rosterInsert->bind_param(
                        'iisss',
                        $sessionId,
                        $studentId,
                        $admissionNumber,
                        $studentName,
                        $rosterTokenHash
                    );
                    if (!$rosterInsert->execute()) {
                        throw new RuntimeException('Unable to save the class attendance roster.');
                    }
                }
                $rosterInsert->close();
                $conn->commit();
                $transactionStarted = false;

                header('Location: qrCodeAttendance.php?session_id=' . $sessionId);
                exit;
            } catch (Throwable $exception) {
                if ($transactionStarted) {
                    $conn->rollback();
                }
                if (strpos($exception->getMessage(), 'active attendance QR already exists') !== false
                    || strpos($exception->getMessage(), 'no students enrolled') !== false) {
                    $pageError = $exception->getMessage();
                } else {
                    error_log('QR attendance session creation failed: ' . $exception->getMessage());
                    $pageError = 'The attendance QR session could not be created. Please try again.';
                }
            } finally {
                $releaseStmt = $conn->prepare('SELECT RELEASE_LOCK(?)');
                if ($releaseStmt) {
                    $releaseStmt->bind_param('s', $lockName);
                    $releaseStmt->execute();
                    $releaseStmt->close();
                }
            }
        }
    }
}

$expireQuery = $conn->prepare(
    "SELECT id FROM qr_attendance_sessions
     WHERE teacher_id = ? AND status = 'active'"
);
$expireQuery->bind_param('i', $teacherId);
$expireQuery->execute();
$activeSessionRows = $expireQuery->get_result()->fetch_all(MYSQLI_NUM);
$expireQuery->close();
foreach ($activeSessionRows as $activeSessionRow) {
    $activeSessionId = (int)$activeSessionRow[0];
    qrFinalizeExpiredSession($conn, $activeSessionId);
}

$historyStmt = $conn->prepare(
    "SELECT session.id, session.token_hash, session.attendance_date, session.generated_at, session.expires_at,
           session.duration_minutes, session.status, session.timetable_id, session.class_id, session.class_arm_id,
            COALESCE(course.courseName, CONCAT('Subject ', session.subject_id)) AS subject_name,
           COALESCE(NULLIF(course.courseCode, ''), CONCAT('SUBJ-', session.subject_id)) AS subject_code,
           CONCAT(teacher.firstName, ' ', teacher.lastName) AS teacher_name,
           term.sessionName, tblclass.className, tblclassarms.classArmName
    FROM qr_attendance_sessions session
    LEFT JOIN course ON course.courseId = session.subject_id
    LEFT JOIN tblclassteacher teacher ON teacher.Id = session.teacher_id
     LEFT JOIN tblsessionterm term ON term.Id = session.academic_session_id
     LEFT JOIN tblclass ON tblclass.Id = session.class_id
     LEFT JOIN tblclassarms ON tblclassarms.Id = session.class_arm_id
     WHERE session.teacher_id = ?
     ORDER BY session.id DESC LIMIT 20"
);
$historyStmt->bind_param('i', $teacherId);
$historyStmt->execute();
$history = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$historyStmt->close();
$selectedSessionId = filter_var($_GET['session_id'] ?? null, FILTER_VALIDATE_INT);
if (!$selectedSessionId && $history) {
    $selectedSessionId = (int)$history[0]['id'];
}
$selectedSession = null;
$roster = [];
if ($selectedSessionId) {
    foreach ($history as $historyItem) {
        if ((int)$historyItem['id'] === $selectedSessionId) {
            $selectedSession = $historyItem;
            break;
        }
    }
    if ($selectedSession) {
        $rosterStmt = $conn->prepare(
            "SELECT student_id, admission_number, student_name, status, marked_at,
                    latitude, longitude, location_accuracy_m, distance_m, location_verified
             FROM qr_attendance_records WHERE qr_session_id = ?
             ORDER BY admission_number"
        );
        $rosterStmt->bind_param('i', $selectedSessionId);
        $rosterStmt->execute();
        $roster = $rosterStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $rosterStmt->close();
    }
}

$sessionQrImage = '';
$sessionQrError = '';
$sessionIsActive = $selectedSession && $selectedSession['status'] === 'active';
if ($sessionIsActive && $websiteBaseUrl !== '' && $configurationError === '') {
    try {
        $sessionToken = qrAttendanceSessionToken((int)$selectedSessionId);
        $sessionTokenHash = hash('sha256', $sessionToken);
        if (!hash_equals((string)$selectedSession['token_hash'], $sessionTokenHash)) {
            $sessionQrError = 'This session was created before shared QR support. End it or wait for it to expire, then create a new session.';
        } else {
            $attendanceUrl = $websiteBaseUrl
                . '/index.php?attendance=1&token='
                . rawurlencode($sessionToken);
            $qrCode = new QrCode($attendanceUrl);
            $writer = extension_loaded('gd') ? new PngWriter() : new SvgWriter();
            $sessionQrImage = $writer->write($qrCode)->getDataUri();
        }
    } catch (Throwable $exception) {
        error_log('Session QR attendance image generation failed: ' . $exception->getMessage());
        $sessionQrError = 'The session QR could not be displayed. Check the QR configuration and refresh the page.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Code Attendance</title>
    <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .session-qr { max-width: 240px; width: 100%; height: auto; }
        .qr-countdown { font-size: 2rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .status-pill { min-width: 95px; }
        .session-history a { display: block; padding: 10px 12px; border-bottom: 1px solid #edf0f4; }
        .session-history a:last-child { border-bottom: 0; }
        @media print {
            body * { visibility: hidden; }
            #qr-session-card, #qr-session-card * { visibility: visible; }
            #qr-session-card { position: absolute; left: 0; top: 0; width: 100%; border: 0; }
            #qr-session-card .table-responsive { overflow: visible; }
            #qr-session-card .session-qr { width: 200px; max-width: 200px; }
            #qr-session-card .no-print { display: none !important; }
        }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'Includes/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'Includes/topbar.php'; ?>
            <div class="container-fluid" id="container-wrapper">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">QR Code Attendance</h1>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="./">Home</a></li>
                        <li class="breadcrumb-item active">QR Code Attendance</li>
                    </ol>
                </div>

                <?php if ($pageError !== ''): ?>
                    <div class="alert alert-warning" role="alert"><?= qrPageEscape($pageError) ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-5 mb-4">
                        <div class="card h-100">
                            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Generate session QR</h6></div>
                            <div class="card-body">
                                <?php if (!$schedules): ?>
                                    <div class="alert alert-info mb-0">
                                    No active timetable subjects are scheduled for your class today. Ask an administrator to activate the academic session and check the timetable.
                                    </div>
                                <?php else: ?>
                                    <?php if ($configurationError !== ''): ?>
                                        <div class="alert alert-warning" role="alert">
                                            <?= qrPageEscape($configurationError) ?>
                                            Configure the server's <code>WEBSITE_BASE_URL</code> and <code>QR_ATTENDANCE_TOKEN_KEY</code> as described in the README before generating a QR code.
                                        </div>
                                    <?php endif; ?>
                                    <form method="post" action="qrCodeAttendance.php">
                                        <input type="hidden" name="csrf_token" value="<?= qrPageEscape($_SESSION['qrAttendanceCsrf']) ?>">
                                        <div class="form-group">
                                            <label for="timetable_id">Class and subject</label>
                                            <select class="form-control" id="timetable_id" name="timetable_id" required>
                                                <option value="">Select a scheduled subject</option>
                                                <?php foreach ($schedules as $schedule): ?>
                                                    <option value="<?= (int)$schedule['id'] ?>">
                                                        <?= qrPageEscape($schedule['className'] . ' ' . $schedule['classArmName'] . ' · ' . $schedule['subject_name'] . ' (' . $schedule['subject_code'] . ')' . ' · ' . $schedule['day_name'] . ' ' . substr($schedule['start_time'], 0, 5)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="attendance_date">Attendance date</label>
                                            <input class="form-control" type="date" id="attendance_date" name="attendance_date"
                                                   value="<?= qrPageEscape($today) ?>" min="<?= qrPageEscape($today) ?>" max="<?= qrPageEscape($today) ?>" required>
                                        </div>
                                        <button class="btn btn-primary" type="submit" <?= $configurationError !== '' ? 'disabled' : '' ?>>
                                            <i class="fas fa-qrcode mr-1"></i> Generate session QR
                                        </button>
                                        <p class="small text-muted mt-3 mb-0">Creates one shared QR code for this class and subject. Different subjects may have active sessions at the same time. It expires after 10 minutes.</p>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7 mb-4">
                        <div class="card h-100" id="qr-session-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Session details</h6>
                                <?php if ($selectedSession): ?>
                                    <div class="no-print">
                                        <button class="btn btn-sm btn-outline-primary mr-1" type="button" onclick="window.print()">
                                            <i class="fas fa-print mr-1"></i> Print session QR
                                        </button>
                                        <a class="btn btn-sm btn-outline-success" href="exportQrAttendance.php?session_id=<?= (int)$selectedSession['id'] ?>">
                                            <i class="fas fa-file-csv mr-1"></i> Export CSV
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div id="status-refresh-error" class="alert alert-warning" role="alert" hidden></div>
                                <?php if (!$selectedSession): ?>
                                    <p class="text-muted mb-0">Generate a QR session to see its countdown and attendance roster here.</p>
                                <?php else: ?>
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <dl class="row mb-0">
                                                <dt class="col-5">Class</dt><dd class="col-7"><?= qrPageEscape($selectedSession['className'] . ' ' . $selectedSession['classArmName']) ?></dd>
                                                <dt class="col-5">Subject</dt><dd class="col-7"><?= qrPageEscape($selectedSession['subject_name']) ?> (<?= qrPageEscape($selectedSession['subject_code']) ?>)</dd>
                                                <dt class="col-5">Faculty</dt><dd class="col-7"><?= qrPageEscape($selectedSession['teacher_name']) ?></dd>
                                                <dt class="col-5">Academic session</dt><dd class="col-7"><?= qrPageEscape($selectedSession['sessionName']) ?></dd>
                                                <dt class="col-5">Date</dt><dd class="col-7"><?= qrPageEscape(date('d M Y', strtotime($selectedSession['attendance_date']))) ?></dd>
                                                <dt class="col-5">Duration</dt><dd class="col-7"><?= (int)$selectedSession['duration_minutes'] ?> minutes</dd>
                                                <dt class="col-5">Status</dt><dd class="col-7"><span id="session-status" class="badge badge-<?= $sessionIsActive ? 'success' : 'secondary' ?>"><?= $sessionIsActive ? 'Active' : ucfirst(qrPageEscape($selectedSession['status'])) ?></span></dd>
                                            </dl>
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <div id="countdown" class="qr-countdown" aria-live="polite">
                                                <?= $sessionIsActive ? sprintf('%02d:00', (int)$selectedSession['duration_minutes']) : ucfirst(qrPageEscape($selectedSession['status'])) ?>
                                            </div>
                                            <p class="small text-muted mb-2">Students scan this session QR with their phones. Location permission is required.</p>
                                            <?php if ($sessionIsActive && $sessionQrImage !== ''): ?>
                                                <img
                                                    id="session-qr-image"
                                                    class="session-qr mb-3"
                                                    src="<?= qrPageEscape($sessionQrImage) ?>"
                                                    alt="Shared attendance QR for this class session">
                                            <?php endif; ?>
                                            <div class="row text-center">
                                                <div class="col-6 col-md"><strong id="total-students-count"><?= count($roster) ?></strong><br><span>Total students</span></div>
                                                <div class="col-6 col-md"><strong id="present-count">0</strong><br><span class="text-success">Present</span></div>
                                                <div class="col-6 col-md"><strong id="absent-count">0</strong><br><span class="text-danger">Absent</span></div>
                                                <div class="col-6 col-md"><strong id="not-marked-count">0</strong><br><span class="text-warning">Not Marked</span></div>
                                                <div class="col-6 col-md"><strong id="location-verified-count">0</strong><br><span class="text-success">Location verified</span></div>
                                            </div>
                                            <div class="mt-3">
                                                <strong id="attendance-percentage">0%</strong> attendance
                                                <?php if ($sessionIsActive): ?>
                                                    <button type="button" id="close-session" class="btn btn-sm btn-outline-danger ml-2">
                                                        End attendance now
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($sessionQrError !== ''): ?>
                                        <div class="alert alert-warning mt-3" role="alert">
                                            <?= qrPageEscape($sessionQrError) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="table-responsive mt-4">
                                        <div class="form-group">
                                            <label for="roster-search">Search students</label>
                                            <input type="search" class="form-control" id="roster-search" placeholder="Search by name, student ID, or register number">
                                        </div>
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                            <tr>
                                                <th data-sort-column>Student ID</th>
                                                <th data-sort-column>USN / Roll Number</th>
                                                <th data-sort-column>Student Name</th>
                                                <th data-sort-column>Date</th>
                                                <th data-sort-column>Time</th>
                                                <th data-sort-column>Day</th>
                                                <th>GPS Location</th>
                                                <th>Distance</th>
                                                <th>Location Verification</th>
                                                <th data-sort-column>Status</th>
                                            </tr>
                                            </thead>
                                            <tbody id="attendance-roster">
                                            <?php foreach ($roster as $record): ?>
                                                <?php
                                                $statusLabel = $record['status'] === 'present' ? 'Present'
                                                    : ($record['status'] === 'absent' ? 'Absent' : 'Not Marked');
                                                $statusClass = $record['status'] === 'present' ? 'success'
                                                    : ($record['status'] === 'absent' ? 'danger' : 'warning');
                                                $markedAt = $record['marked_at'];
                                                $attendanceTime = $markedAt
                                                    ? strtotime($markedAt)
                                                    : strtotime($selectedSession['attendance_date']);
                                                ?>
                                                <tr>
                                                    <td><?= (int)$record['student_id'] ?></td>
                                                    <td><?= qrPageEscape($record['admission_number']) ?></td>
                                                    <td><?= qrPageEscape($record['student_name']) ?></td>
                                                    <td><?= qrPageEscape(date('d-m-Y', $attendanceTime)) ?></td>
                                                    <td><?= $markedAt ? qrPageEscape(date('h:i A', strtotime($markedAt))) : '—' ?></td>
                                                    <td><?= qrPageEscape(date('l', $attendanceTime)) ?></td>
                                                    <td><?= isset($record['latitude'], $record['longitude'])
                                                        ? qrPageEscape($record['latitude'] . ', ' . $record['longitude']
                                                            . ($record['location_accuracy_m'] !== null
                                                                ? ' (±' . $record['location_accuracy_m'] . ' m)' : ''))
                                                        : '—' ?></td>
                                                    <td><?= $record['distance_m'] !== null
                                                        ? qrPageEscape(number_format((float)$record['distance_m'], 1) . ' m')
                                                        : '—' ?></td>
                                                    <td><?= (int)$record['location_verified'] === 1 ? 'Inside premises' : 'Not verified' ?></td>
                                                    <td><span class="badge badge-<?= $statusClass ?> status-pill"><?= $statusLabel ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (!$roster): ?><tr><td colspan="10" class="text-muted text-center">No roster records found.</td></tr><?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Recent QR attendance sessions</h6></div>
                    <div class="card-body session-history">
                        <?php if (!$history): ?>
                            <p class="text-muted mb-0">No attendance sessions have been created yet.</p>
                        <?php else: foreach ($history as $historyItem): ?>
                            <a href="qrCodeAttendance.php?session_id=<?= (int)$historyItem['id'] ?>">
                                <strong><?= qrPageEscape($historyItem['subject_name']) ?></strong>
                                <span class="text-muted"> · <?= qrPageEscape($historyItem['className'] . ' ' . $historyItem['classArmName']) ?>
                                    · <?= qrPageEscape(date('d M Y', strtotime($historyItem['attendance_date']))) ?>
                                    · <?= qrPageEscape(date('h:i A', strtotime($historyItem['generated_at']))) ?></span>
                                <span class="badge badge-<?= $historyItem['status'] === 'active' ? 'success' : 'secondary' ?> float-right">
                                    <?= qrPageEscape(ucfirst($historyItem['status'])) ?>
                                </span>
                            </a>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'Includes/footer.php'; ?>
    </div>
</div>
<script src="../vendore/jquery/jquery.min.js"></script>
<script src="../vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/ruang-admin.min.js"></script>
<?php if ($selectedSession): ?>
<script>
(function () {
    var sessionId = <?= (int)$selectedSessionId ?>;
    var countdown = document.getElementById('countdown');
    var roster = document.getElementById('attendance-roster');
    var rosterSearch = document.getElementById('roster-search');
    var clockOffset = 0;
    var expiresAt = 0;
    var pollUrl = 'qrAttendanceStatus.php?session_id=' + encodeURIComponent(sessionId);
    var closeButton = document.getElementById('close-session');
    var statusRefreshError = document.getElementById('status-refresh-error');
    var sortDirections = {};

    function filterRoster() {
        var term = rosterSearch ? rosterSearch.value.trim().toLowerCase() : '';
        Array.prototype.forEach.call(roster.rows, function (row) {
            row.hidden = term !== '' && row.textContent.toLowerCase().indexOf(term) === -1;
        });
    }

    function sortRoster(columnIndex) {
        var direction = sortDirections[columnIndex] === 'asc' ? 'desc' : 'asc';
        sortDirections[columnIndex] = direction;
        var rows = Array.prototype.slice.call(roster.rows);
        rows.sort(function (left, right) {
            var leftText = left.cells[columnIndex].textContent.trim().toLowerCase();
            var rightText = right.cells[columnIndex].textContent.trim().toLowerCase();
            var result = leftText.localeCompare(rightText, undefined, {numeric: true});
            return direction === 'asc' ? result : -result;
        });
        rows.forEach(function (row) { roster.appendChild(row); });
    }

    function setSessionEnded(status) {
        var sessionQr = document.getElementById('session-qr-image');
        if (sessionQr) sessionQr.remove();
        countdown.textContent = status === 'closed' ? 'Closed' : 'Expired';
        var sessionStatus = document.getElementById('session-status');
        sessionStatus.textContent = status === 'closed' ? 'Closed' : 'Expired';
        sessionStatus.className = 'badge badge-secondary';
        if (closeButton) closeButton.remove();
        window.clearInterval(countdownTimer);
    }

    function updateCountdown() {
        if (!expiresAt || !countdown) return;
        var remaining = Math.max(0, Math.ceil(expiresAt - (Date.now() + clockOffset)));
        countdown.textContent = remaining > 0
            ? String(Math.floor(remaining / 60)).padStart(2, '0') + ':' + String(remaining % 60).padStart(2, '0')
            : 'Expired';
        if (remaining === 0) {
            var sessionQr = document.getElementById('session-qr-image');
            if (sessionQr) sessionQr.remove();
        }
    }

    function refreshStatus() {
        fetch(pollUrl, {credentials: 'same-origin', headers: {'Accept': 'application/json'}})
            .then(function (response) {
                if (!response.ok) throw new Error('Unable to refresh attendance status.');
                return response.json();
            })
            .then(function (data) {
                clockOffset = data.server_now * 1000 - Date.now();
                expiresAt = data.expires_at_epoch * 1000;
                if (statusRefreshError) {
                    statusRefreshError.hidden = true;
                    statusRefreshError.textContent = '';
                }
                var active = data.status === 'active';
                var status = document.getElementById('session-status');
                status.textContent = active ? 'Active' : (data.status === 'closed' ? 'Closed' : 'Expired');
                status.className = 'badge badge-' + (active ? 'success' : 'secondary');
                document.getElementById('total-students-count').textContent = data.total_students;
                document.getElementById('present-count').textContent = data.counts.present;
                document.getElementById('absent-count').textContent = data.counts.absent;
                document.getElementById('not-marked-count').textContent = data.counts.not_marked;
                document.getElementById('location-verified-count').textContent = data.location_verified_students;
                document.getElementById('attendance-percentage').textContent = data.attendance_percentage + '%';
                roster.innerHTML = '';
                data.records.forEach(function (record) {
                    var row = document.createElement('tr');
                    [record.student_id, record.admission_number, record.student_name, record.date, record.time, record.day].forEach(function (value) {
                        var cell = document.createElement('td');
                        cell.textContent = value;
                        row.appendChild(cell);
                    });
                    var locationCell = document.createElement('td');
                    if (record.latitude !== null && record.longitude !== null) {
                        locationCell.textContent = record.latitude + ', ' + record.longitude
                            + (record.location_accuracy_m !== null
                                ? ' (±' + record.location_accuracy_m + ' m)' : '');
                    } else {
                        locationCell.textContent = '—';
                    }
                    row.appendChild(locationCell);
                    var distanceCell = document.createElement('td');
                    distanceCell.textContent = record.distance_m === null
                        ? '—' : Number(record.distance_m).toFixed(1) + ' m';
                    row.appendChild(distanceCell);
                    var verificationCell = document.createElement('td');
                    verificationCell.textContent = record.location_verified
                        ? 'Inside premises' : 'Not verified';
                    row.appendChild(verificationCell);
                    var statusCell = document.createElement('td');
                    var pill = document.createElement('span');
                    pill.className = 'badge status-pill badge-' + record.class;
                    pill.textContent = record.label;
                    statusCell.appendChild(pill);
                    row.appendChild(statusCell);
                    roster.appendChild(row);
                });
                filterRoster();
                if (!active) {
                    setSessionEnded(data.status);
                }
                updateCountdown();
            })
            .catch(function (error) {
                if (statusRefreshError) {
                    statusRefreshError.textContent = error.message || 'Unable to refresh attendance status.';
                    statusRefreshError.hidden = false;
                }
            });
    }

    refreshStatus();
    if (rosterSearch) rosterSearch.addEventListener('input', filterRoster);
    Array.prototype.forEach.call(
        document.querySelectorAll('th[data-sort-column]'),
        function (header, index) {
            header.style.cursor = 'pointer';
            header.title = 'Sort by ' + header.textContent;
            header.addEventListener('click', function () { sortRoster(index); });
        }
    );
    var countdownTimer = window.setInterval(updateCountdown, 250);
    window.setInterval(refreshStatus, 3000);
    if (closeButton) {
        closeButton.addEventListener('click', function () {
            if (!window.confirm('End this attendance session now? Students who have not checked in will be marked absent.')) return;
            closeButton.disabled = true;
            fetch('closeQrAttendance.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    session_id: String(sessionId),
                    csrf_token: <?= json_encode($_SESSION['qrAttendanceCsrf']) ?>
                })
            }).then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) throw new Error(data.error || 'Unable to close attendance.');
                    refreshStatus();
                });
            }).catch(function (error) {
                closeButton.disabled = false;
                window.alert(error.message);
            });
        });
    }
}());
</script>
<?php endif; ?>
</body>
</html>
