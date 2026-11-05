<?php
include '../Includes/dbcon.php';
include '../Includes/qr_attendance.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['userType'] ?? '') !== 'ClassTeacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Teacher sign-in required.']);
    exit;
}

$teacherId = filter_var($_SESSION['userId'] ?? null, FILTER_VALIDATE_INT);
$sessionId = filter_var($_POST['session_id'] ?? null, FILTER_VALIDATE_INT);
$csrfToken = $_POST['csrf_token'] ?? '';
if (!$teacherId || !$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid attendance session.']);
    exit;
}
if (!is_string($csrfToken) || empty($_SESSION['qrAttendanceCsrf'])
    || !hash_equals($_SESSION['qrAttendanceCsrf'], $csrfToken)) {
    http_response_code(400);
    echo json_encode(['error' => 'Your request expired. Refresh the page and try again.']);
    exit;
}

try {
    $closed = qrCloseAttendanceSession($conn, $sessionId, $teacherId);
    if (!$closed) {
        http_response_code(409);
        echo json_encode(['error' => 'This attendance session is already closed or expired.']);
        exit;
    }

    unset($_SESSION['teacherQrTokens'][$sessionId]);
    echo json_encode(['status' => 'closed']);
} catch (Throwable $exception) {
    error_log('Unable to close QR attendance session: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to close attendance. Please try again.']);
}
