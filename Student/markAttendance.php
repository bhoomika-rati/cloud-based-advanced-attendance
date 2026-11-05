<?php
include '../Includes/dbcon.php';
include '../Includes/qr_attendance.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

function attendancePageEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function attendancePage(string $title, string $message, string $details = ''): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= attendancePageEscape($title) ?></title>
        <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { min-height: 100vh; display: grid; place-items: center; padding: 20px; background: #f3f5fa; }
            .result-card { width: 100%; max-width: 560px; border: 0; border-radius: 16px; box-shadow: 0 12px 38px rgba(20, 40, 70, .12); }
            .result-icon { font-size: 44px; margin-bottom: 12px; }
            .detail-row { display: flex; justify-content: space-between; gap: 18px; padding: 10px 0; border-bottom: 1px solid #edf0f4; }
            .detail-row:last-child { border-bottom: 0; }
        </style>
    </head>
    <body>
    <main class="card result-card">
        <div class="card-body p-4 p-md-5 text-center">
            <?= $details ?>
            <h1 class="h3 mb-3"><?= attendancePageEscape($title) ?></h1>
            <p class="text-muted mb-0"><?= attendancePageEscape($message) ?></p>
        </div>
    </main>
    </body>
    </html>
    <?php
}

$tokenValue = $_GET['token'] ?? $_POST['token'] ?? '';
$rawToken = is_string($tokenValue) ? $tokenValue : '';
if (!preg_match('/\A[a-f0-9]{64}\z/i', $rawToken)) {
    http_response_code(400);
    attendancePage('Invalid or Expired QR Code', 'Invalid or Expired QR Code. Attendance Cannot Be Marked.');
    exit;
}

$tokenHash = hash('sha256', $rawToken);
$precheckStmt = $conn->prepare(
    "SELECT session.id, session.status, session.expires_at,
            token_record.student_id AS qr_student_id
     FROM qr_attendance_sessions session
     LEFT JOIN qr_attendance_records token_record
       ON token_record.qr_session_id = session.id
      AND token_record.qr_token_hash = ?
     WHERE session.token_hash = ? OR token_record.id IS NOT NULL
     LIMIT 1"
);
if (!$precheckStmt) {
    http_response_code(500);
    attendancePage('Attendance unavailable', 'Unable to validate this QR code. Please try again later.');
    exit;
}
$precheckStmt->bind_param('ss', $tokenHash, $tokenHash);
$precheckStmt->execute();
$precheckedSession = $precheckStmt->get_result()->fetch_assoc();
$precheckStmt->close();
if (!$precheckedSession) {
    http_response_code(404);
    attendancePage('Invalid or Expired QR Code', 'Invalid or Expired QR Code. Attendance Cannot Be Marked.');
    exit;
}
$precheckedExpiry = new DateTimeImmutable($precheckedSession['expires_at'], qrAttendanceTimezone());
if ($precheckedSession['status'] === 'active' && qrAttendanceNow() >= $precheckedExpiry) {
    qrFinalizeExpiredSession($conn, (int)$precheckedSession['id']);
    $precheckedSession['status'] = 'expired';
}
$isConfirmation = ($_GET['result'] ?? '') === 'success';
$isAuthenticatedStudent = !empty($_SESSION['userId']) && ($_SESSION['userType'] ?? '') === 'Student';
$autoMark = $isAuthenticatedStudent
    && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && !$isConfirmation;
if ($precheckedSession['status'] !== 'active' && !$isConfirmation && !$isAuthenticatedStudent) {
    http_response_code(410);
    if ($precheckedSession['status'] === 'closed') {
        attendancePage('Attendance Session Closed', 'The faculty member closed this attendance session.');
    } else {
        attendancePage('This Attendance Session Has Expired.', 'This Attendance Session Has Expired.');
    }
    exit;
}

if (empty($_SESSION['userId']) || ($_SESSION['userType'] ?? '') !== 'Student') {
    $_SESSION['attendancePendingToken'] = $rawToken;
    $_SESSION['attendancePendingResult'] = $isConfirmation ? 'success' : '';
    header('Location: ../index.php?attendance=1&token=' . rawurlencode($rawToken));
    exit;
}

$studentId = filter_var($_SESSION['userId'], FILTER_VALIDATE_INT);
$studentStmt = $conn->prepare(
    "SELECT Id, firstName, lastName, otherName, admissionNumber, classId, classArmId
     FROM tblstudents WHERE Id = ? LIMIT 1"
);
if (!$studentStmt) {
    http_response_code(500);
    attendancePage('Attendance unavailable', 'Unable to load your student account. Please try again later.');
    exit;
}
$studentStmt->bind_param('i', $studentId);
$studentStmt->execute();
$student = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();
if (!$student) {
    http_response_code(403);
    attendancePage('Student sign-in required', 'Sign in with a valid student account to mark attendance.');
    exit;
}

$sessionStmt = $conn->prepare(
    "SELECT qr.id, qr.teacher_id, qr.academic_session_id, qr.class_id, qr.class_arm_id,
            qr.subject_id, qr.attendance_date, qr.generated_at, qr.expires_at, qr.status,
            token_record.student_id AS qr_student_id,
            COALESCE(course.courseName, CONCAT('Subject ', qr.subject_id)) AS subject_name,
            COALESCE(NULLIF(course.courseCode, ''), CONCAT('SUBJ-', qr.subject_id)) AS subject_code,
            CONCAT(teacher.firstName, ' ', teacher.lastName) AS teacher_name,
            term.sessionName, tblclass.className, tblclassarms.classArmName
     FROM qr_attendance_sessions qr
     LEFT JOIN course ON course.courseId = qr.subject_id
     LEFT JOIN tblclassteacher teacher ON teacher.Id = qr.teacher_id
     LEFT JOIN tblsessionterm term ON term.Id = qr.academic_session_id
     LEFT JOIN tblclass ON tblclass.Id = qr.class_id
     LEFT JOIN tblclassarms ON tblclassarms.Id = qr.class_arm_id
     LEFT JOIN qr_attendance_records token_record
       ON token_record.qr_session_id = qr.id
      AND token_record.qr_token_hash = ?
     WHERE qr.token_hash = ? OR token_record.id IS NOT NULL
     LIMIT 1"
);
if (!$sessionStmt) {
    http_response_code(500);
    attendancePage('Attendance unavailable', 'Unable to validate this QR code. Please try again later.');
    exit;
}
$sessionStmt->bind_param('ss', $tokenHash, $tokenHash);
$sessionStmt->execute();
$attendanceSession = $sessionStmt->get_result()->fetch_assoc();
$sessionStmt->close();
if (!$attendanceSession) {
    http_response_code(404);
    attendancePage('Invalid or Expired QR Code', 'Invalid or Expired QR Code. Attendance Cannot Be Marked.');
    exit;
}
if ($attendanceSession['qr_student_id'] !== null
    && (int)$attendanceSession['qr_student_id'] !== $studentId) {
    http_response_code(403);
    attendancePage(
        'You are not authorized to mark attendance for this session.',
        'You are not authorized to mark attendance for this session.'
    );
    exit;
}
if ((int)$student['classId'] !== (int)$attendanceSession['class_id']
    || (int)$student['classArmId'] !== (int)$attendanceSession['class_arm_id']) {
    http_response_code(403);
    attendancePage(
        'You are not authorized to mark attendance for this session.',
        'You are not authorized to mark attendance for this session.'
    );
    exit;
}

$sessionId = (int)$attendanceSession['id'];
$recordStmt = $conn->prepare(
    "SELECT status, marked_at, latitude, longitude, location_accuracy_m,
            distance_m, location_verified
     FROM qr_attendance_records
     WHERE qr_session_id = ? AND student_id = ? LIMIT 1"
);
$recordStmt->bind_param('ii', $sessionId, $studentId);
$recordStmt->execute();
$attendanceRecord = $recordStmt->get_result()->fetch_assoc();
$recordStmt->close();
if (!$attendanceRecord) {
    http_response_code(403);
    attendancePage(
        'You are not authorized to mark attendance for this session.',
        'You are not authorized to mark attendance for this session.'
    );
    exit;
}

$now = qrAttendanceNow();
$expiresAt = new DateTimeImmutable($attendanceSession['expires_at'], qrAttendanceTimezone());
if ($attendanceSession['status'] === 'active' && $now >= $expiresAt) {
    qrFinalizeExpiredSession($conn, $sessionId);
    $attendanceSession['status'] = 'expired';
    $attendanceRecord = null;
    $recordStmt = $conn->prepare(
        "SELECT status, marked_at, latitude, longitude, location_accuracy_m,
                distance_m, location_verified
         FROM qr_attendance_records
         WHERE qr_session_id = ? AND student_id = ? LIMIT 1"
    );
    $recordStmt->bind_param('ii', $sessionId, $studentId);
    $recordStmt->execute();
    $attendanceRecord = $recordStmt->get_result()->fetch_assoc();
    $recordStmt->close();
}

if ($attendanceRecord && $attendanceRecord['status'] === 'present') {
    if ($isConfirmation) {
        $markedAt = new DateTimeImmutable($attendanceRecord['marked_at'], qrAttendanceTimezone());
        $name = trim($student['firstName'] . ' ' . $student['lastName']);
        $otherName = trim((string)$student['otherName']);
        if ($otherName !== '' && strtolower($otherName) !== 'none') {
            $name .= ' ' . $otherName;
        }
        $dateText = $markedAt->format('j F Y');
        $dayText = $markedAt->format('l');
        $timeText = $markedAt->format('h:i A');
        $details = '<div class="text-success result-icon">&#10003;</div><div class="text-left mb-4">'
            . '<div class="detail-row"><span>Student</span><strong>' . attendancePageEscape($name) . '</strong></div>'
            . '<div class="detail-row"><span>USN / Register Number</span><strong>' . attendancePageEscape($student['admissionNumber']) . '</strong></div>'
            . '<div class="detail-row"><span>Student ID</span><strong>' . (int)$student['Id'] . '</strong></div>'
            . '<div class="detail-row"><span>Status</span><strong class="text-success">Present</strong></div>'
            . '<div class="detail-row"><span>Date</span><strong>' . attendancePageEscape($dateText) . '</strong></div>'
            . '<div class="detail-row"><span>Day</span><strong>' . attendancePageEscape($dayText) . '</strong></div>'
            . '<div class="detail-row"><span>Time</span><strong>' . attendancePageEscape($timeText) . '</strong></div>'
            . '<div class="detail-row"><span>Location</span><strong>'
            . ($attendanceRecord['latitude'] !== null && $attendanceRecord['longitude'] !== null
                ? attendancePageEscape($attendanceRecord['latitude'] . ', ' . $attendanceRecord['longitude']
                    . ($attendanceRecord['location_accuracy_m'] !== null
                        ? ' (±' . $attendanceRecord['location_accuracy_m'] . ' m)' : ''))
                : 'Unavailable')
            . '</strong></div>'
            . '<div class="detail-row"><span>Distance from college</span><strong>'
            . ($attendanceRecord['distance_m'] !== null
                ? attendancePageEscape(number_format((float)$attendanceRecord['distance_m'], 1) . ' m')
                : 'Unavailable')
            . '</strong></div>'
            . '<div class="detail-row"><span>Location verification</span><strong>'
            . ((int)$attendanceRecord['location_verified'] === 1 ? 'Inside premises' : 'Not verified')
            . '</strong></div>'
            . '<div class="detail-row"><span>Subject</span><strong>' . attendancePageEscape($attendanceSession['subject_name']) . ' (' . attendancePageEscape($attendanceSession['subject_code']) . ')</strong></div>'
            . '<div class="detail-row"><span>Faculty</span><strong>' . attendancePageEscape($attendanceSession['teacher_name']) . '</strong></div>'
            . '<div class="detail-row"><span>Class / Group</span><strong>' . attendancePageEscape($attendanceSession['className'] . ' ' . $attendanceSession['classArmName']) . '</strong></div>'
            . '<div class="detail-row"><span>Attendance Session</span><strong>' . attendancePageEscape($attendanceSession['sessionName']) . '</strong></div>'
            . '</div>';
        attendancePage('Attendance Marked Successfully!', 'Your attendance has been recorded.', $details);
    } else {
        http_response_code(409);
        attendancePage('Attendance Already Marked!', 'Your attendance for this session has already been recorded.');
    }
    exit;
}

if (!$attendanceRecord || $attendanceRecord['status'] !== 'not_marked'
    || $attendanceSession['status'] !== 'active' || qrAttendanceNow() >= $expiresAt) {
    http_response_code(410);
    if ($attendanceSession['status'] === 'closed') {
        attendancePage('Attendance Session Closed', 'The faculty member closed this attendance session.');
    } else {
        attendancePage('This Attendance Session Has Expired.', 'This Attendance Session Has Expired.');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!is_string($csrfToken) || empty($_SESSION['attendanceScanCsrf'])
        || !hash_equals($_SESSION['attendanceScanCsrf'], $csrfToken)) {
        http_response_code(400);
        attendancePage('Attendance request expired', 'Refresh the page and scan the active QR code again.');
        exit;
    }

    $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $locationAccuracy = filter_var($_POST['location_accuracy_m'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($latitude === false || $longitude === false || $locationAccuracy === false
        || !is_finite((float)$latitude) || !is_finite((float)$longitude)
        || !is_finite((float)$locationAccuracy)
        || $latitude < -90 || $latitude > 90
        || $longitude < -180 || $longitude > 180
        || $locationAccuracy <= 0) {
        http_response_code(400);
        attendancePage(
            'Location permission required',
            'Please enable location permission to mark your attendance.'
        );
        exit;
    }
    if ($locationAccuracy > 100) {
        http_response_code(400);
        attendancePage(
            'Location accuracy is too low',
            'Your location could not be verified accurately. Enable precise location and try again.'
        );
        exit;
    }

    $transactionStarted = false;
    try {
        $conn->begin_transaction();
        $transactionStarted = true;
        $lockStmt = $conn->prepare(
            "SELECT session.id, session.teacher_id, session.academic_session_id,
                    session.class_id, session.class_arm_id, session.subject_id,
                    session.attendance_date, session.expires_at, session.status
             FROM qr_attendance_sessions session
             WHERE session.id = ?
               AND (session.token_hash = ? OR EXISTS (
                   SELECT 1 FROM qr_attendance_records token_record
                   WHERE token_record.qr_session_id = session.id
                     AND token_record.student_id = ?
                     AND token_record.qr_token_hash = ?
               ))
             FOR UPDATE"
        );
        $lockStmt->bind_param('isis', $sessionId, $tokenHash, $studentId, $tokenHash);
        $lockStmt->execute();
        $lockedSession = $lockStmt->get_result()->fetch_assoc();
        $lockStmt->close();

        $now = qrAttendanceNow();
        $lockedExpiry = $lockedSession
            ? new DateTimeImmutable($lockedSession['expires_at'], qrAttendanceTimezone())
            : $now;
        if (!$lockedSession) {
            $conn->rollback();
            $transactionStarted = false;
            http_response_code(410);
            attendancePage('Invalid or Expired QR Code', 'Invalid or Expired QR Code. Attendance Cannot Be Marked.');
            exit;
        }
        if ($lockedSession['status'] !== 'active' || $now >= $lockedExpiry) {
            if ($lockedSession['status'] === 'active') {
                qrFinalizeSessionLocked($conn, $lockedSession, $lockedExpiry);
            }
            $conn->commit();
            $transactionStarted = false;
            http_response_code(410);
            if ($lockedSession['status'] === 'closed') {
                attendancePage('Attendance Session Closed', 'The faculty member closed this attendance session.');
            } else {
                attendancePage('This Attendance Session Has Expired.', 'This Attendance Session Has Expired.');
            }
            exit;
        }

        $lockedStudentStmt = $conn->prepare(
            'SELECT classId, classArmId, admissionNumber FROM tblstudents WHERE Id = ? FOR UPDATE'
        );
        if (!$lockedStudentStmt) {
            throw new RuntimeException('Unable to verify the student class assignment.');
        }
        $lockedStudentStmt->bind_param('i', $studentId);
        if (!$lockedStudentStmt->execute()) {
            $error = $lockedStudentStmt->error;
            $lockedStudentStmt->close();
            throw new RuntimeException('Unable to verify the student class assignment: ' . $error);
        }
        $lockedStudent = $lockedStudentStmt->get_result()->fetch_assoc();
        $lockedStudentStmt->close();
        if (!$lockedStudent
            || (int)$lockedStudent['classId'] !== (int)$lockedSession['class_id']
            || (int)$lockedStudent['classArmId'] !== (int)$lockedSession['class_arm_id']) {
            $conn->rollback();
            $transactionStarted = false;
            http_response_code(403);
            attendancePage(
                'You are not authorized to mark attendance for this session.',
                'You are not authorized to mark attendance for this session.'
            );
            exit;
        }

        $lockedRecordStmt = $conn->prepare(
            "SELECT status FROM qr_attendance_records
             WHERE qr_session_id = ? AND student_id = ?
               AND (qr_token_hash = ? OR (qr_token_hash IS NULL AND EXISTS (
                   SELECT 1 FROM qr_attendance_sessions
                   WHERE id = ? AND token_hash = ?
               )))
             FOR UPDATE"
        );
        $lockedRecordStmt->bind_param('iisis', $sessionId, $studentId, $tokenHash, $sessionId, $tokenHash);
        $lockedRecordStmt->execute();
        $lockedRecord = $lockedRecordStmt->get_result()->fetch_assoc();
        $lockedRecordStmt->close();
        if (!$lockedRecord || $lockedRecord['status'] !== 'not_marked') {
            $conn->commit();
            $transactionStarted = false;
            http_response_code($lockedRecord && $lockedRecord['status'] === 'present' ? 409 : 410);
            if ($lockedRecord && $lockedRecord['status'] === 'present') {
                attendancePage('Attendance Already Marked!', 'Your attendance for this session has already been recorded.');
            } elseif (!$lockedRecord) {
                http_response_code(403);
                attendancePage(
                    'You are not authorized to mark attendance for this session.',
                    'You are not authorized to mark attendance for this session.'
                );
            } else {
                attendancePage('Invalid or Expired QR Code', 'Invalid or Expired QR Code. Attendance Cannot Be Marked.');
            }
            exit;
        }

        try {
            $locationSettings = qrAttendanceLocationSettings($conn, true);
        } catch (RuntimeException $exception) {
            $conn->rollback();
            $transactionStarted = false;
            $locationMessage = 'Please contact your administrator to verify the college location settings.';
            if (strpos($exception->getMessage(), 'not been configured') !== false) {
                $locationMessage = 'The college location has not been configured. Please contact your administrator.';
            } else {
                error_log('Student QR geofence settings could not be loaded: ' . $exception->getMessage());
            }
            http_response_code(503);
            attendancePage(
                'Location verification unavailable',
                $locationMessage
            );
            exit;
        }
        $distanceMeters = qrAttendanceDistanceMeters(
            (float)$latitude,
            (float)$longitude,
            $locationSettings['latitude'],
            $locationSettings['longitude']
        );
        if ($distanceMeters > $locationSettings['radius_m']) {
            $conn->rollback();
            $transactionStarted = false;
            http_response_code(403);
            attendancePage(
                'Outside college premises',
                'You are outside the college premises. Attendance cannot be marked.'
            );
            exit;
        }

        $markedAt = qrAttendanceSqlTime($now);
        $markStmt = $conn->prepare(
            "UPDATE qr_attendance_records
             SET status = 'present', marked_at = ?, latitude = ?, longitude = ?,
                 location_accuracy_m = ?, distance_m = ?, location_verified = 1
             WHERE qr_session_id = ? AND student_id = ? AND status = 'not_marked'"
        );
        $markStmt->bind_param(
            'sddddii',
            $markedAt,
            $latitude,
            $longitude,
            $locationAccuracy,
            $distanceMeters,
            $sessionId,
            $studentId
        );
        if (!$markStmt->execute() || $markStmt->affected_rows !== 1) {
            throw new RuntimeException('Student attendance could not be marked.');
        }
        $markStmt->close();

        $classId = (string)$lockedSession['class_id'];
        $classArmId = (string)$lockedSession['class_arm_id'];
        $academicSessionId = (string)$lockedSession['academic_session_id'];
        $admissionNumber = (string)$lockedStudent['admissionNumber'];
        $legacyStmt = $conn->prepare(
            "INSERT INTO tblattendance
             (admissionNo, classId, classArmId, sessionTermId, status, dateTimeTaken, qr_session_id)
             VALUES (?, ?, ?, ?, '0', ?, ?)"
        );
        $legacyStmt->bind_param(
            'sssssi',
            $admissionNumber,
            $classId,
            $classArmId,
            $academicSessionId,
            $markedAt,
            $sessionId
        );
        if (!$legacyStmt->execute()) {
            throw new RuntimeException('Attendance report record could not be saved: ' . $legacyStmt->error);
        }
        $legacyStmt->close();

        $syncStmt = $conn->prepare(
            "UPDATE qr_attendance_records SET legacy_synced = 1
             WHERE qr_session_id = ? AND student_id = ?"
        );
        $syncStmt->bind_param('ii', $sessionId, $studentId);
        if (!$syncStmt->execute()) {
            throw new RuntimeException('Attendance report record could not be synchronized.');
        }
        $syncStmt->close();
        $conn->commit();
        $transactionStarted = false;

        header('Location: markAttendance.php?token=' . rawurlencode($rawToken) . '&result=success');
        exit;
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        error_log('Student QR attendance submission failed: ' . $exception->getMessage());
        http_response_code(500);
        attendancePage('Attendance could not be recorded', 'Please try again while the QR code is still active.');
        exit;
    }
}

if (empty($_SESSION['attendanceScanCsrf'])) {
    $_SESSION['attendanceScanCsrf'] = bin2hex(random_bytes(32));
}
$studentName = trim($student['firstName'] . ' ' . $student['lastName']);
$otherName = trim((string)$student['otherName']);
if ($otherName !== '' && strtolower($otherName) !== 'none') {
    $studentName .= ' ' . $otherName;
}
$subjectName = $attendanceSession['subject_name'];
$className = trim($attendanceSession['className'] . ' ' . $attendanceSession['classArmName']);
$csrf = attendancePageEscape($_SESSION['attendanceScanCsrf']);
$safeToken = attendancePageEscape($rawToken);
$safeStudent = attendancePageEscape($studentName);
$safeSubject = attendancePageEscape($subjectName);
$safeClass = attendancePageEscape($className);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mark Attendance</title>
    <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: grid; place-items: center; padding: 20px; background: #f3f5fa; }
        .scan-card { width: 100%; max-width: 520px; border: 0; border-radius: 16px; box-shadow: 0 12px 38px rgba(20, 40, 70, .12); }
    </style>
</head>
<body>
<main class="card scan-card">
    <div class="card-body p-4 p-md-5 text-center">
        <div class="text-primary mb-3" style="font-size:42px">&#128203;</div>
        <h1 class="h3">Confirm attendance</h1>
        <p class="text-muted"><?= $safeSubject ?> · <?= $safeClass ?></p>
        <p class="mb-4">Signed in as <strong><?= $safeStudent ?></strong> (<?= attendancePageEscape($student['admissionNumber']) ?>).</p>
        <div id="location-message" class="alert alert-info" role="status" aria-live="polite">
            <?= $autoMark ? 'Recording your attendance…' : 'Allow location access in your phone browser to record your attendance.' ?>
        </div>
        <form id="attendance-form" method="post" action="markAttendance.php">
            <input type="hidden" name="token" value="<?= $safeToken ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">
            <input type="hidden" id="location_accuracy_m" name="location_accuracy_m">
            <button id="mark-attendance" type="submit" class="btn btn-primary btn-lg btn-block">
                <?= $autoMark ? 'Retry location and mark attendance' : 'Allow location and mark attendance' ?>
            </button>
        </form>
        <p class="small text-muted mt-3 mb-0" <?= $autoMark ? 'hidden' : '' ?>>
            Location is required for manual confirmation. Your browser will ask for permission; the attendance time is recorded by the server.
        </p>
    </div>
</main>
<script>
(function () {
    var form = document.getElementById('attendance-form');
    var button = document.getElementById('mark-attendance');
    var message = document.getElementById('location-message');
    var autoMark = <?= $autoMark ? 'true' : 'false' ?>;

    function showLocationError(error) {
        var code = error && Number.isInteger(error.code) ? error.code : 0;
        var detail = error && error.message ? ' Browser detail: ' + error.message : '';
        var reason;

        if (code === 1 && !window.isSecureContext) {
            reason = 'Location is blocked because this page is not a secure context. Open the attendance link using HTTPS with a valid certificate.';
        } else if (code === 1) {
            reason = 'Location permission was denied. In Android Chrome, open the site information beside the address, set Location to Allow, enable Android Location, and retry.';
        } else if (code === 2) {
            reason = 'Your device could not determine a location. Turn on Android Location and enable precise location for Chrome, then retry.';
        } else if (code === 3) {
            reason = 'The location request timed out. Move to an area with a clearer GPS signal and retry.';
        } else {
            reason = 'The browser could not retrieve your location. Check Android Location and Chrome site permissions, then retry.';
        }

        message.className = 'alert alert-danger';
        message.textContent = reason + ' Geolocation error code: ' + code + '.' + detail;
        button.disabled = false;

        if (code === 1 && window.isSecureContext && navigator.permissions
            && typeof navigator.permissions.query === 'function') {
            navigator.permissions.query({name: 'geolocation'}).then(function (permission) {
                if (permission.state === 'denied') {
                    message.textContent += ' Browser permission state: denied.';
                }
            }).catch(function () {});
        }
    }

    function requestLocation() {
        if (!window.isSecureContext) {
            showLocationError({code: 1, message: 'Current origin: ' + window.location.origin});
            return;
        }
        if (!navigator.geolocation) {
            showLocationError({code: 0, message: 'The Geolocation API is unavailable in this browser context.'});
            return;
        }

        button.disabled = true;
        message.className = 'alert alert-info';
        message.textContent = 'Getting your location. Please allow the browser permission request.';
        navigator.geolocation.getCurrentPosition(function (position) {
            var latitude = position.coords.latitude;
            var longitude = position.coords.longitude;
            var accuracy = position.coords.accuracy;
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)
                || !Number.isFinite(accuracy) || accuracy <= 0) {
                showLocationError({code: 0, message: 'The browser returned invalid coordinates or accuracy.'});
                return;
            }
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
            document.getElementById('location_accuracy_m').value = position.coords.accuracy;
            message.className = 'alert alert-success';
            message.textContent = 'Location captured. Verifying college premises…';
            HTMLFormElement.prototype.submit.call(form);
        }, showLocationError, {enableHighAccuracy: true, maximumAge: 0, timeout: 30000});
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        requestLocation();
    });

    if (autoMark) requestLocation();
}());
</script>
</body>
</html>
