<?php
include '../Includes/dbcon.php';
include '../Includes/qr_attendance.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['userType'] ?? '') !== 'ClassTeacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Teacher sign-in required.']);
    exit;
}

$teacherId = filter_var($_SESSION['userId'] ?? null, FILTER_VALIDATE_INT);
$sessionId = filter_var($_GET['session_id'] ?? null, FILTER_VALIDATE_INT);
if (!$teacherId || !$sessionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session request.']);
    exit;
}

try {
    $ownerStmt = $conn->prepare(
        "SELECT id FROM qr_attendance_sessions WHERE id = ? AND teacher_id = ?"
    );
    $ownerStmt->bind_param('ii', $sessionId, $teacherId);
    $ownerStmt->execute();
    $ownsSession = $ownerStmt->get_result()->num_rows === 1;
    $ownerStmt->close();
    if (!$ownsSession) {
        http_response_code(404);
        echo json_encode(['error' => 'Attendance session not found.']);
        exit;
    }

    qrFinalizeExpiredSession($conn, $sessionId);
    $sessionStmt = $conn->prepare(
        "SELECT status, expires_at, attendance_date
         FROM qr_attendance_sessions WHERE id = ? AND teacher_id = ?"
    );
    $sessionStmt->bind_param('ii', $sessionId, $teacherId);
    $sessionStmt->execute();
    $session = $sessionStmt->get_result()->fetch_assoc();
    $sessionStmt->close();
    if (!$session || $session['status'] !== 'active') {
        unset($_SESSION['teacherQrTokens'][$sessionId]);
    }

    $recordsStmt = $conn->prepare(
        "SELECT student_id, admission_number, student_name, status, marked_at,
                latitude, longitude, location_accuracy_m, distance_m, location_verified
         FROM qr_attendance_records WHERE qr_session_id = ?
         ORDER BY admission_number"
    );
    $recordsStmt->bind_param('i', $sessionId);
    $recordsStmt->execute();
    $records = $recordsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $recordsStmt->close();

    $counts = ['present' => 0, 'absent' => 0, 'not_marked' => 0];
    $totalStudents = count($records);
    $locationVerifiedStudents = 0;
    $responseRecords = [];
    foreach ($records as $record) {
        $status = isset($counts[$record['status']]) ? $record['status'] : 'not_marked';
        $counts[$status]++;
        if ((int)$record['location_verified'] === 1) {
            $locationVerifiedStudents++;
        }
        $attendanceTime = $record['marked_at']
            ? strtotime($record['marked_at'])
            : strtotime($session['attendance_date']);
        $responseRecords[] = [
            'student_id' => (int)$record['student_id'],
            'admission_number' => $record['admission_number'],
            'student_name' => $record['student_name'],
            'date' => date('d-m-Y', $attendanceTime),
            'time' => $record['marked_at'] ? date('h:i A', $attendanceTime) : '—',
            'day' => date('l', $attendanceTime),
            'latitude' => $record['latitude'] !== null ? (float)$record['latitude'] : null,
            'longitude' => $record['longitude'] !== null ? (float)$record['longitude'] : null,
            'location_accuracy_m' => $record['location_accuracy_m'] !== null
                ? (float)$record['location_accuracy_m'] : null,
            'distance_m' => $record['distance_m'] !== null ? (float)$record['distance_m'] : null,
            'location_verified' => (int)$record['location_verified'] === 1,
            'label' => $status === 'not_marked' ? 'Not Marked' : ucfirst($status),
            'class' => $status === 'present' ? 'success'
                : ($status === 'absent' ? 'danger' : 'warning')
        ];
    }

    $expiresAt = new DateTimeImmutable($session['expires_at'], qrAttendanceTimezone());
    echo json_encode([
        'status' => $session['status'],
        'server_now' => qrAttendanceNow()->getTimestamp(),
        'expires_at_epoch' => $expiresAt->getTimestamp(),
        'total_students' => $totalStudents,
        'location_verified_students' => $locationVerifiedStudents,
        'counts' => $counts,
        'attendance_percentage' => $totalStudents > 0
            ? (int)round(($counts['present'] / $totalStudents) * 100) : 0,
        'records' => $responseRecords
    ]);
} catch (Throwable $exception) {
    error_log('QR attendance status refresh failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Unable to refresh attendance status.']);
}
