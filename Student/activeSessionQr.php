<?php
include '../Includes/dbcon.php';
include '../Includes/qr_attendance.php';
include '../Includes/app_config.php';
require_once '../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function activeSessionQrResponse(int $statusCode, array $response): void
{
    http_response_code($statusCode);
    echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    exit;
}

if (empty($_SESSION['userId']) || ($_SESSION['userType'] ?? '') !== 'Student') {
    activeSessionQrResponse(401, ['error' => 'Sign in with a student account to view your class QR.']);
}

$studentId = filter_var($_SESSION['userId'], FILTER_VALIDATE_INT);
if (!$studentId) {
    activeSessionQrResponse(401, ['error' => 'Sign in with a valid student account to view your class QR.']);
}

$studentStmt = $conn->prepare(
    'SELECT classId, classArmId FROM tblstudents WHERE Id = ? LIMIT 1'
);
if (!$studentStmt) {
    error_log('Student session QR could not prepare student lookup.');
    activeSessionQrResponse(500, ['error' => 'Unable to load the class session QR right now.']);
}
$studentStmt->bind_param('i', $studentId);
$studentStmt->execute();
$student = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();
if (!$student) {
    activeSessionQrResponse(403, ['error' => 'A valid student account is required to view this QR.']);
}

$now = qrAttendanceNow();
$nowSql = qrAttendanceSqlTime($now);
$sessionStmt = $conn->prepare(
    "SELECT session.id, session.token_hash, session.subject_id, session.attendance_date,
            session.generated_at, session.expires_at, record.status AS attendance_status,
            COALESCE(course.courseName, CONCAT('Subject ', session.subject_id)) AS subject_name,
            COALESCE(NULLIF(TRIM(CONCAT(teacher.firstName, ' ', teacher.lastName)), ''), 'Faculty') AS teacher_name,
            COALESCE(tblclass.className, CONCAT('Class ', session.class_id)) AS class_name
     FROM qr_attendance_sessions session
     INNER JOIN qr_attendance_records record
       ON record.qr_session_id = session.id AND record.student_id = ?
     LEFT JOIN course ON course.courseId = session.subject_id
     LEFT JOIN tblclassteacher teacher ON teacher.Id = session.teacher_id
     LEFT JOIN tblclass ON tblclass.Id = session.class_id
     WHERE session.class_id = ? AND session.class_arm_id = ?
       AND session.status = 'active' AND session.expires_at > ?
       AND record.status IN ('not_marked', 'present')
     ORDER BY session.generated_at DESC"
);
if (!$sessionStmt) {
    error_log('Student session QR could not prepare active-session lookup.');
    activeSessionQrResponse(500, ['error' => 'Unable to load the class session QR right now.']);
}
$classId = (int)$student['classId'];
$classArmId = (int)$student['classArmId'];
$sessionStmt->bind_param('iiis', $studentId, $classId, $classArmId, $nowSql);
$sessionStmt->execute();
$sessions = $sessionStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sessionStmt->close();

if (!$sessions) {
    activeSessionQrResponse(200, ['active' => false, 'sessions' => []]);
}

try {
    $knownSessionIds = [];
    $knownIdsValue = $_GET['session_ids'] ?? '';
    if (is_string($knownIdsValue) && strlen($knownIdsValue) <= 2000) {
        foreach (explode(',', $knownIdsValue) as $knownIdValue) {
            $knownId = filter_var($knownIdValue, FILTER_VALIDATE_INT);
            if ($knownId && $knownId > 0) {
                $knownSessionIds[$knownId] = true;
            }
        }
    }

    $writer = extension_loaded('gd')
        ? new \Endroid\QrCode\Writer\PngWriter()
        : new \Endroid\QrCode\Writer\SvgWriter();
    $responseSessions = [];
    $publicBaseUrl = null;
    foreach ($sessions as $session) {
        $sessionId = (int)$session['id'];
        $sessionToken = qrAttendanceSessionToken($sessionId);
        if (!hash_equals((string)$session['token_hash'], hash('sha256', $sessionToken))) {
            error_log('Student session QR does not match the stored session token.');
            activeSessionQrResponse(409, [
                'error' => 'An active session QR is out of date. Ask your faculty member to create a new session.'
            ]);
        }

        $expiresAt = new DateTimeImmutable($session['expires_at'], qrAttendanceTimezone());
        $response = [
            'session_id' => $sessionId,
            'subject_name' => $session['subject_name'],
            'teacher_name' => $session['teacher_name'],
            'class_name' => $session['class_name'],
            'attendance_date' => $session['attendance_date'],
            'session_time' => (new DateTimeImmutable($session['generated_at'], qrAttendanceTimezone()))->format('g:i A')
                . ' – ' . $expiresAt->format('g:i A'),
            'attendance_status' => $session['attendance_status'],
            'expires_at' => $expiresAt->getTimestamp()
        ];
        if (!isset($knownSessionIds[$sessionId])) {
            if ($publicBaseUrl === null) {
                $publicBaseUrl = attendancePublicWebsiteBaseUrl();
            }
            $attendanceUrl = $publicBaseUrl
                . '/index.php?attendance=1&token='
                . rawurlencode($sessionToken);
            $response['qr_image'] = $writer
                ->write(new \Endroid\QrCode\QrCode($attendanceUrl))
                ->getDataUri();
        }
        $responseSessions[] = $response;
    }

    activeSessionQrResponse(200, [
        'active' => true,
        'server_now' => $now->getTimestamp(),
        'sessions' => $responseSessions
    ]);
} catch (RuntimeException $exception) {
    activeSessionQrResponse(503, ['error' => $exception->getMessage()]);
} catch (Throwable $exception) {
    error_log('Student session QR generation failed: ' . $exception->getMessage());
    activeSessionQrResponse(500, ['error' => 'Unable to display the class session QR right now.']);
}
