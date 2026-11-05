<?php
include '../Includes/dbcon.php';
include '../Includes/qr_attendance.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Asia/Kolkata');
if (($_SESSION['userType'] ?? '') !== 'ClassTeacher') {
    http_response_code(403);
    exit('Teacher sign-in required.');
}

$teacherId = filter_var($_SESSION['userId'] ?? null, FILTER_VALIDATE_INT);
$sessionId = filter_var($_GET['session_id'] ?? null, FILTER_VALIDATE_INT);
if (!$teacherId || !$sessionId) {
    http_response_code(400);
    exit('Invalid attendance session.');
}

$sessionStmt = $conn->prepare(
    "SELECT session.attendance_date, session.subject_id, session.class_id, session.class_arm_id,
            COALESCE(course.courseName, CONCAT('Subject ', session.subject_id)) AS subject_name,
            COALESCE(NULLIF(course.courseCode, ''), CONCAT('SUBJ-', session.subject_id)) AS subject_code,
            CONCAT(teacher.firstName, ' ', teacher.lastName) AS teacher_name,
            class.className, arms.classArmName
     FROM qr_attendance_sessions session
     LEFT JOIN course ON course.courseId = session.subject_id
     LEFT JOIN tblclassteacher teacher ON teacher.Id = session.teacher_id
     LEFT JOIN tblclass class ON class.Id = session.class_id
     LEFT JOIN tblclassarms arms ON arms.Id = session.class_arm_id
     WHERE session.id = ? AND session.teacher_id = ?"
);
$sessionStmt->bind_param('ii', $sessionId, $teacherId);
$sessionStmt->execute();
$session = $sessionStmt->get_result()->fetch_assoc();
$sessionStmt->close();
if (!$session) {
    http_response_code(404);
    exit('Attendance session not found.');
}
qrFinalizeExpiredSession($conn, $sessionId);

$recordsStmt = $conn->prepare(
    "SELECT student_id, admission_number, student_name, status, marked_at,
            latitude, longitude, location_accuracy_m, distance_m, location_verified
     FROM qr_attendance_records WHERE qr_session_id = ?
     ORDER BY admission_number"
);
$recordsStmt->bind_param('i', $sessionId);
$recordsStmt->execute();
$records = $recordsStmt->get_result();

$fileDate = preg_replace('/[^0-9]/', '-', $session['attendance_date']);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="attendance-session-' . $sessionId . '-' . $fileDate . '.csv"');
$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, [
    'Student ID', 'USN / Roll Number', 'Student Name', 'Subject', 'Subject Code',
    'Faculty', 'Class', 'Date', 'Time', 'Day', 'Latitude', 'Longitude',
    'Location Accuracy (m)', 'Distance from College (m)', 'Location Verification', 'Attendance Status'
]);
while ($record = $records->fetch_assoc()) {
    $status = $record['status'] === 'not_marked' ? 'Not Marked' : ucfirst($record['status']);
    $attendanceTime = $record['marked_at']
        ? strtotime($record['marked_at'])
        : strtotime($session['attendance_date']);
    fputcsv($output, [
        $record['student_id'],
        $record['admission_number'],
        $record['student_name'],
        $session['subject_name'],
        $session['subject_code'],
        $session['teacher_name'],
        trim($session['className'] . ' ' . $session['classArmName']),
        date('d-m-Y', $attendanceTime),
        $record['marked_at'] ? date('h:i A', $attendanceTime) : '',
        date('l', $attendanceTime),
        $record['latitude'],
        $record['longitude'],
        $record['location_accuracy_m'],
        $record['distance_m'],
        (int)$record['location_verified'] === 1 ? 'Inside Premises' : 'Not Verified',
        $status
    ]);
}
fclose($output);
$recordsStmt->close();
