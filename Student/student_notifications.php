<?php
require_once __DIR__ . '/Includes/student_context.php';

$announcements = [];
$announcementStmt = $conn->prepare(
    "SELECT title, body, created_at
     FROM student_announcements
     WHERE (class_id IS NULL OR class_id = ?)
       AND (class_arm_id IS NULL OR class_arm_id = ?)
     ORDER BY created_at DESC LIMIT 20"
);
if (!$announcementStmt) {
    error_log('Student notifications announcements query could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load your announcements.');
}
$classId = (int)$student['classId'];
$classArmId = (int)$student['classArmId'];
$announcementStmt->bind_param('ii', $classId, $classArmId);
$announcementStmt->execute();
$announcementResult = $announcementStmt->get_result();
while ($announcement = $announcementResult->fetch_assoc()) {
    $announcements[] = $announcement;
}
$announcementStmt->close();

$timetableUpdate = null;
$timetableStmt = $conn->prepare(
    'SELECT MAX(updated_at) AS last_updated FROM session_timetable WHERE class_id = ? AND class_arm_id = ?'
);
if (!$timetableStmt) {
    error_log('Student timetable update query could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load timetable notifications.');
}
$timetableStmt->bind_param('ii', $classId, $classArmId);
$timetableStmt->execute();
$timetableUpdate = $timetableStmt->get_result()->fetch_assoc()['last_updated'] ?? null;
$timetableStmt->close();

$pageTitle = 'Notifications';
require __DIR__ . '/Includes/student_page_start.php';
?>
<header class="student-page-heading"><div><h1>Notifications</h1><p>Attendance updates, class announcements and timetable notices.</p></div></header>
<section class="student-alert warning mb-4" id="active-session-notice" hidden role="status" aria-live="polite">
    <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:12px">
        <div><strong>Attendance session available</strong><div id="active-session-copy" class="small mt-1"></div></div>
        <a class="student-action" href="scanQr.php"><i class="fas fa-qrcode"></i> Scan QR</a>
    </div>
</section>
<div class="row">
    <div class="col-lg-7 mb-4">
        <section class="student-card">
            <h2 class="student-card-title">Class announcements</h2>
            <?php if (!$announcements): ?><div class="student-empty">There are no announcements for your class right now.</div>
            <?php else: foreach ($announcements as $announcement): ?>
                <article class="student-notice">
                    <div class="d-flex justify-content-between flex-wrap"><strong><?= studentEscape($announcement['title']) ?></strong><span class="small student-muted"><?= studentEscape(date('M j, Y · g:i A', strtotime($announcement['created_at']))) ?></span></div>
                    <p class="mb-0 mt-2"><?= nl2br(studentEscape($announcement['body'])) ?></p>
                </article>
            <?php endforeach; endif; ?>
        </section>
    </div>
    <div class="col-lg-5 mb-4">
        <section class="student-card">
            <h2 class="student-card-title">Attendance alerts</h2>
            <?php if ($totalClasses > 0 && $attendancePercentage < 75): ?>
                <div class="student-notice"><strong>Attendance below 75%</strong><p class="mb-0 mt-2">Your current overall rate is <?= $attendancePercentage ?>%. View your report to see subject details.</p></div>
            <?php else: ?>
                <div class="student-notice"><strong>Attendance standing</strong><p class="mb-0 mt-2"><?= $totalClasses ? 'Your overall attendance is ' . $attendancePercentage . '%.' : 'No attendance records are available yet.' ?></p></div>
            <?php endif; ?>
            <?php if ($attendanceRows): ?>
                <?php $lastRecord = $attendanceRows[0]; ?>
                <div class="student-notice mt-3"><strong>Latest attendance update</strong><p class="mb-0 mt-2"><?= studentEscape(studentStatusLabel($lastRecord['status'])) ?> · <?= studentEscape($lastRecord['subject_name']) ?> · <?= studentEscape(date('M j, Y', strtotime($lastRecord['dateTimeTaken']))) ?></p></div>
            <?php endif; ?>
            <?php if ($timetableUpdate): ?>
                <div class="student-notice mt-3"><strong>Timetable update</strong><p class="mb-0 mt-2">Your class timetable was last updated <?= studentEscape(date('M j, Y · g:i A', strtotime($timetableUpdate))) ?>.</p></div>
            <?php endif; ?>
        </section>
    </div>
</div>
<script>
(function () {
    var notice = document.getElementById('active-session-notice');
    var copy = document.getElementById('active-session-copy');
    function checkSession() {
        fetch('activeSessionQr.php', {credentials: 'same-origin', cache: 'no-store', headers: {'Accept': 'application/json'}})
            .then(function (response) {
                if (!response.ok) throw new Error('Unable to check active attendance sessions.');
                return response.json();
            })
            .then(function (data) {
                notice.querySelector('a').hidden = false;
                notice.hidden = !data.active || data.attendance_status === 'present';
                if (data.active && data.attendance_status !== 'present') {
                    copy.textContent = data.subject_name + ' · ' + data.attendance_date + '. Scan the faculty QR before it expires.';
                }
            })
            .catch(function (error) {
                notice.hidden = false;
                notice.className = 'student-alert warning mb-4';
                copy.textContent = error.message;
                notice.querySelector('a').hidden = true;
            });
    }
    checkSession();
    window.setInterval(checkSession, 15000);
}());
</script>
<?php require __DIR__ . '/Includes/student_page_end.php'; ?>
