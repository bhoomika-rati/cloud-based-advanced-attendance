<?php
require_once __DIR__ . '/Includes/student_context.php';

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestType = trim((string)($_POST['request_type'] ?? ''));
    $details = trim((string)($_POST['details'] ?? ''));
    $attendanceId = trim((string)($_POST['attendance_id'] ?? ''));
    if (!studentVerifyCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Your form session expired. Refresh the page and try again.';
    } elseif (!in_array($requestType, ['Correction', 'Incorrect record'], true)) {
        $errorMessage = 'Choose a valid request type.';
    } elseif (strlen($details) < 10 || strlen($details) > 2000) {
        $errorMessage = 'Please describe the issue in 10 to 2,000 characters.';
    } else {
        $ownedAttendanceId = null;
        if ($attendanceId !== '') {
            foreach ($attendanceRows as $record) {
                if ((string)$record['attendance_id'] === $attendanceId) {
                    $ownedAttendanceId = (int)$record['attendance_id'];
                    break;
                }
            }
            if ($ownedAttendanceId === null) {
                $errorMessage = 'Choose an attendance record belonging to your account.';
            }
        }
        if ($errorMessage === '') {
            $requestStmt = $conn->prepare(
                "INSERT INTO student_attendance_requests (student_id, attendance_id, request_type, details)
                 VALUES (?, ?, ?, ?)"
            );
            if (!$requestStmt) {
                error_log('Student support request could not be prepared: ' . $conn->error);
                http_response_code(500);
                $errorMessage = 'Unable to submit your request right now.';
            } else {
                $requestStmt->bind_param('iiss', $studentId, $ownedAttendanceId, $requestType, $details);
                if (!$requestStmt->execute()) {
                    error_log('Student support request failed: ' . $requestStmt->error);
                    http_response_code(500);
                    $errorMessage = 'Unable to submit your request right now.';
                } else {
                    $requestStmt->close();
                    header('Location: student_support.php?submitted=1');
                    exit;
                }
                $requestStmt->close();
            }
        }
    }
}

$requests = [];
$requestsStmt = $conn->prepare(
    "SELECT r.id, r.request_type, r.details, r.status, r.created_at,
            a.dateTimeTaken, COALESCE(c.courseName, 'Attendance record') AS subject_name
     FROM student_attendance_requests r
     LEFT JOIN tblattendance a ON a.Id = r.attendance_id
     LEFT JOIN tblsessionterm term ON term.Id = a.sessionTermId
     LEFT JOIN course c ON c.courseId = term.course_id
     WHERE r.student_id = ? ORDER BY r.created_at DESC LIMIT 50"
);
if (!$requestsStmt) {
    error_log('Student support requests query could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load your support requests.');
}
$requestsStmt->bind_param('i', $studentId);
$requestsStmt->execute();
$requestsResult = $requestsStmt->get_result();
while ($request = $requestsResult->fetch_assoc()) {
    $requests[] = $request;
}
$requestsStmt->close();

$faculty = null;
$facultyStmt = $conn->prepare(
    "SELECT t.firstName, t.lastName, t.emailAddress
     FROM session_timetable st
     INNER JOIN tblclassteacher t ON t.Id = st.teacher_id
     WHERE st.class_id = ? AND st.class_arm_id = ?
     ORDER BY FIELD(st.day_name, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), st.start_time
     LIMIT 1"
);
if (!$facultyStmt) {
    error_log('Student support faculty query could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load your faculty contact.');
}
$classId = (int)$student['classId'];
$classArmId = (int)$student['classArmId'];
$facultyStmt->bind_param('ii', $classId, $classArmId);
$facultyStmt->execute();
$faculty = $facultyStmt->get_result()->fetch_assoc();
$facultyStmt->close();

$pageTitle = 'Help & Support';
require __DIR__ . '/Includes/student_page_start.php';
?>
<header class="student-page-heading"><div><h1>Help &amp; support</h1><p>Request a review if an attendance record looks incorrect.</p></div></header>
<?php if (!empty($_GET['submitted'])): ?><div class="student-alert success mb-4" role="status">Your request was submitted. Only authorized faculty or administrators can review and approve attendance corrections.</div><?php endif; ?>
<?php if ($errorMessage !== ''): ?><div class="alert alert-danger"><?= studentEscape($errorMessage) ?></div><?php endif; ?>
<div class="row">
    <div class="col-lg-7 mb-4">
        <section class="student-card">
            <h2 class="student-card-title">Submit an attendance request</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= studentEscape(studentCsrfToken()) ?>">
                <div class="form-group"><label class="student-form-label" for="request-type">Request type</label>
                    <select class="form-control student-form-control" name="request_type" id="request-type" required>
                        <option value="Correction">Attendance correction request</option>
                        <option value="Incorrect record">Report incorrect attendance</option>
                    </select>
                </div>
                <div class="form-group"><label class="student-form-label" for="attendance-id">Related record (optional)</label>
                    <select class="form-control student-form-control" name="attendance_id" id="attendance-id"><option value="">No specific record</option>
                        <?php foreach (array_slice($attendanceRows, 0, 100) as $record): ?>
                            <option value="<?= (int)$record['attendance_id'] ?>"><?= studentEscape($record['subject_name'] . ' · ' . date('M j, Y', strtotime($record['dateTimeTaken'])) . ' · ' . studentStatusLabel($record['status'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="student-form-label" for="request-details">Details</label>
                    <textarea class="form-control student-form-control" id="request-details" name="details" rows="5" minlength="10" maxlength="2000" required placeholder="Explain what appears incorrect and provide any relevant details."></textarea>
                </div>
                <button class="student-action" type="submit"><i class="fas fa-paper-plane"></i> Submit for review</button>
            </form>
        </section>
    </div>
    <div class="col-lg-5 mb-4">
        <section class="student-card">
            <h2 class="student-card-title">Contact faculty</h2>
            <?php if ($faculty && filter_var($faculty['emailAddress'], FILTER_VALIDATE_EMAIL)): ?>
                <p class="mb-1 fw-bold"><?= studentEscape(trim($faculty['firstName'] . ' ' . $faculty['lastName'])) ?></p>
                <p class="student-muted"><?= studentEscape($faculty['emailAddress']) ?></p>
                <a class="student-action secondary" href="mailto:<?= rawurlencode($faculty['emailAddress']) ?>?subject=Attendance%20support"><i class="fas fa-envelope"></i> Email faculty</a>
            <?php else: ?>
                <p class="student-muted mb-0">Faculty contact details are not available in your class timetable.</p>
            <?php endif; ?>
            <p class="student-muted small mt-3 mb-0">Your request is read-only while pending. Students cannot change their own attendance status.</p>
        </section>
    </div>
</div>
<section class="student-card">
    <h2 class="student-card-title">My requests</h2>
    <?php if (!$requests): ?><div class="student-empty">No support requests have been submitted.</div>
    <?php else: ?>
        <div class="table-responsive"><table class="table student-table">
            <thead><tr><th>Request</th><th>Related class</th><th>Submitted</th><th>Status</th></tr></thead><tbody>
            <?php foreach ($requests as $request): ?>
                <tr><td><?= studentEscape($request['request_type']) ?><div class="small student-muted"><?= studentEscape($request['details']) ?></div></td>
                    <td><?= studentEscape($request['subject_name']) ?><?= $request['dateTimeTaken'] ? ' · ' . studentEscape(date('M j, Y', strtotime($request['dateTimeTaken']))) : '' ?></td>
                    <td><?= studentEscape(date('M j, Y', strtotime($request['created_at']))) ?></td>
                    <td><span class="student-badge <?= strtolower($request['status']) ?>"><?= studentEscape($request['status']) ?></span></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/Includes/student_page_end.php'; ?>
