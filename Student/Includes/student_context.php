<?php
require_once __DIR__ . '/../../Includes/dbcon.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['userId']) || ($_SESSION['userType'] ?? '') !== 'Student') {
    header('Location: ../index.php');
    exit;
}

date_default_timezone_set('Asia/Kolkata');

function studentEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function studentCsrfToken(): string
{
    if (empty($_SESSION['studentCsrfToken'])) {
        $_SESSION['studentCsrfToken'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['studentCsrfToken'];
}

function studentVerifyCsrf($token): bool
{
    return is_string($token)
        && !empty($_SESSION['studentCsrfToken'])
        && hash_equals($_SESSION['studentCsrfToken'], $token);
}

function studentStatusLabel($status): string
{
    $value = strtolower(trim((string)$status));
    if ($value === '0' || $value === 'present') {
        return 'Present';
    }
    if ($value === '1' || $value === 'absent') {
        return 'Absent';
    }
    if ($value === 'late') {
        return 'Late';
    }

    return 'Recorded';
}

$studentId = (int)$_SESSION['userId'];
$studentStmt = $conn->prepare(
    "SELECT s.*, c.className, ca.classArmName,
            p.email_address, p.phone_number, p.department, p.semester,
            p.academic_year, p.photo_path
     FROM tblstudents s
     LEFT JOIN tblclass c ON c.Id = s.classId
     LEFT JOIN tblclassarms ca ON ca.Id = s.classArmId
     LEFT JOIN student_profiles p ON p.student_id = s.Id
     WHERE s.Id = ? LIMIT 1"
);
if (!$studentStmt) {
    error_log('Student panel profile query could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load your student profile. Please try again later.');
}
$studentStmt->bind_param('i', $studentId);
$studentStmt->execute();
$student = $studentStmt->get_result()->fetch_assoc();
$studentStmt->close();

if (!$student) {
    http_response_code(403);
    exit('Your student account could not be found. Please sign in again.');
}

$fullName = trim($student['firstName'] . ' ' . $student['lastName']);
$admissionNumber = (string)$student['admissionNumber'];
$attendanceRows = [];
$attendanceStmt = $conn->prepare(
    "SELECT a.Id AS attendance_id, a.dateTimeTaken, a.status,
            COALESCE(q.subject_id, term.course_id) AS subject_id,
            COALESCE(course.courseName, CONCAT('Class ', a.sessionTermId)) AS subject_name,
            COALESCE(q.teacher_id, term.teacher_id) AS teacher_id,
            NULLIF(TRIM(CONCAT(teacher.firstName, ' ', teacher.lastName)), '') AS teacher_name,
            qr.marked_at
     FROM tblattendance a
     LEFT JOIN tblsessionterm term ON term.Id = a.sessionTermId
     LEFT JOIN qr_attendance_sessions q ON q.id = a.qr_session_id
     LEFT JOIN course ON course.courseId = COALESCE(q.subject_id, term.course_id)
     LEFT JOIN tblclassteacher teacher ON teacher.Id = COALESCE(q.teacher_id, term.teacher_id)
     LEFT JOIN qr_attendance_records qr
       ON qr.qr_session_id = a.qr_session_id
      AND qr.student_id = ?
     WHERE a.admissionNo = ?
     ORDER BY a.dateTimeTaken DESC, a.Id DESC"
);
if (!$attendanceStmt) {
    error_log('Student panel attendance query could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load your attendance history. Please try again later.');
}
$attendanceStmt->bind_param('is', $studentId, $admissionNumber);
$attendanceStmt->execute();
$attendanceResult = $attendanceStmt->get_result();
while ($row = $attendanceResult->fetch_assoc()) {
    $attendanceRows[] = $row;
}
$attendanceStmt->close();

$totalClasses = count($attendanceRows);
$presentClasses = 0;
$absentClasses = 0;
foreach ($attendanceRows as $attendanceRow) {
    $status = strtolower(trim((string)$attendanceRow['status']));
    if ($status === '0' || $status === 'present') {
        $presentClasses++;
    } elseif ($status === '1' || $status === 'absent') {
        $absentClasses++;
    }
}
$attendancePercentage = $totalClasses > 0
    ? (int)round(($presentClasses / $totalClasses) * 100)
    : 0;

$studentSubjects = [];
foreach ($attendanceRows as $attendanceRow) {
    $subjectKey = (string)($attendanceRow['subject_id'] ?? 'unknown');
    $studentSubjects[$subjectKey] = (string)$attendanceRow['subject_name'];
}
asort($studentSubjects, SORT_NATURAL | SORT_FLAG_CASE);
