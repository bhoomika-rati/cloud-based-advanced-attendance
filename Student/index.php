<?php
require_once __DIR__ . '/Includes/student_context.php';

$today = date('l');
$upcomingStmt = $conn->prepare(
    "SELECT st.day_name, st.start_time, st.end_time, st.slot_type,
            c.courseName, CONCAT(t.firstName, ' ', t.lastName) AS faculty_name
     FROM session_timetable st
     LEFT JOIN course c ON c.courseId = st.subject_id
     LEFT JOIN tblclassteacher t ON t.Id = st.teacher_id
     WHERE st.class_id = ? AND st.class_arm_id = ?
     ORDER BY FIELD(st.day_name, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'),
              st.start_time ASC"
);
if (!$upcomingStmt) {
    error_log('Student dashboard timetable query could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load your upcoming classes.');
}
$classId = (int)$student['classId'];
$classArmId = (int)$student['classArmId'];
$upcomingStmt->bind_param('ii', $classId, $classArmId);
$upcomingStmt->execute();
$upcomingResult = $upcomingStmt->get_result();
$allSchedule = [];
while ($scheduleRow = $upcomingResult->fetch_assoc()) {
    $allSchedule[] = $scheduleRow;
}
$upcomingStmt->close();
$todayIndex = (int)date('N');
$upcomingClasses = [];
foreach ($allSchedule as $scheduleRow) {
    $dayIndex = (int)date('N', strtotime($scheduleRow['day_name']));
    $daysAhead = ($dayIndex - $todayIndex + 7) % 7;
    if ($daysAhead === 0 && $scheduleRow['start_time'] < date('H:i:s')) {
        $daysAhead = 7;
    }
    $scheduleRow['days_ahead'] = $daysAhead;
    $scheduleRow['next_date'] = date('Y-m-d', strtotime('+' . $daysAhead . ' days'));
    $upcomingClasses[] = $scheduleRow;
}
usort($upcomingClasses, static function ($left, $right) {
    return [$left['days_ahead'], $left['start_time']] <=> [$right['days_ahead'], $right['start_time']];
});
$upcomingClasses = array_slice($upcomingClasses, 0, 5);

$announcements = [];
$announcementStmt = $conn->prepare(
    "SELECT title, body, created_at
     FROM student_announcements
     WHERE (class_id IS NULL OR class_id = ?)
       AND (class_arm_id IS NULL OR class_arm_id = ?)
     ORDER BY created_at DESC LIMIT 3"
);
if (!$announcementStmt) {
    error_log('Student dashboard announcements query could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load student announcements.');
}
$announcementStmt->bind_param('ii', $classId, $classArmId);
$announcementStmt->execute();
$announcementResult = $announcementStmt->get_result();
while ($announcement = $announcementResult->fetch_assoc()) {
    $announcements[] = $announcement;
}
$announcementStmt->close();

$monthlyBuckets = [];
for ($offset = 5; $offset >= 0; $offset--) {
    $key = date('Y-m', strtotime('first day of -' . $offset . ' months'));
    $monthlyBuckets[$key] = ['label' => date('M', strtotime($key . '-01')), 'total' => 0, 'present' => 0];
}
foreach ($attendanceRows as $record) {
    $monthKey = date('Y-m', strtotime((string)$record['dateTimeTaken']));
    if (isset($monthlyBuckets[$monthKey])) {
        $monthlyBuckets[$monthKey]['total']++;
        if (studentStatusLabel($record['status']) === 'Present') {
            $monthlyBuckets[$monthKey]['present']++;
        }
    }
}
$maxMonthTotal = max(1, max(array_column($monthlyBuckets, 'total')));
$predictorTargets = [75, 80, 85];
$profileImage = trim((string)($student['photo_path'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard | Attendly</title>
    <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="vendor/bootstrap5/bootstrap.min.css" rel="stylesheet">
    <link href="css/student-panel.css" rel="stylesheet">
</head>
<body class="student-app" id="page-top">
<div id="wrapper">
    <?php include 'Includes/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'Includes/topbar.php'; ?>
            <main class="student-page">
                <header class="student-page-heading">
                    <div class="d-flex align-items-center" style="gap:15px">
                        <div class="student-profile-avatar">
                            <?php if ($profileImage !== ''): ?>
                                <img src="<?= studentEscape($profileImage) ?>" alt="Student profile">
                            <?php else: ?>
                                <?= studentEscape(strtoupper(substr($student['firstName'], 0, 1) . substr($student['lastName'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h1>Welcome back, <?= studentEscape($student['firstName']) ?></h1>
                            <p><?= studentEscape($admissionNumber) ?> <span class="mx-1">·</span>
                                <?= studentEscape($student['className'] ?: 'Class not set') ?>
                                <?= $student['classArmName'] ? '· Group ' . studentEscape($student['classArmName']) : '' ?></p>
                        </div>
                    </div>
                    <a class="student-action" href="scanQr.php"><i class="fas fa-qrcode"></i> Scan attendance QR</a>
                </header>

                <section class="student-card student-active-qr mb-4" id="active-qr-session" aria-labelledby="active-qr-title" aria-live="polite">
                    <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:12px">
                        <div>
                            <h2 class="student-card-title mb-1" id="active-qr-title">Active QR Attendance Sessions</h2>
                            <p class="student-muted small mb-0">Live attendance session for your assigned class.</p>
                        </div>
                        <span class="student-qr-status" id="active-qr-status">Checking sessions…</span>
                    </div>
                    <div class="student-qr-content mt-3" id="active-qr-content">
                        <div class="student-empty">Checking for an active session…</div>
                    </div>
                </section>

                <section class="row mb-4" aria-label="Attendance statistics">
                    <div class="col-xl-3 col-md-6 mb-3"><article class="student-stat">
                        <span class="student-stat-icon"><i class="fas fa-book-open"></i></span>
                        <div class="student-stat-label">Total classes recorded</div><div class="student-stat-value"><?= $totalClasses ?></div>
                    </article></div>
                    <div class="col-xl-3 col-md-6 mb-3"><article class="student-stat lime">
                        <span class="student-stat-icon"><i class="fas fa-check"></i></span>
                        <div class="student-stat-label">Present classes</div><div class="student-stat-value"><?= $presentClasses ?></div>
                    </article></div>
                    <div class="col-xl-3 col-md-6 mb-3"><article class="student-stat orange">
                        <span class="student-stat-icon"><i class="fas fa-calendar-times"></i></span>
                        <div class="student-stat-label">Absent classes</div><div class="student-stat-value"><?= $absentClasses ?></div>
                    </article></div>
                    <div class="col-xl-3 col-md-6 mb-3"><article class="student-stat red">
                        <span class="student-stat-icon"><i class="fas fa-chart-line"></i></span>
                        <div class="student-stat-label">Overall attendance</div><div class="student-stat-value"><?= $attendancePercentage ?>%</div>
                    </article></div>
                </section>

                <?php if ($totalClasses > 0 && $attendancePercentage < 75): ?>
                    <div class="student-alert warning mb-4"><i class="fas fa-exclamation-triangle me-2"></i>
                        Your attendance is below 75%. Review your report and attend upcoming classes regularly.
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-xl-7 mb-4">
                        <section class="student-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="student-card-title mb-0">Attendance trend</h2>
                                <a class="student-muted small" href="attendance_reports.php">View reports</a>
                            </div>
                            <?php if ($totalClasses === 0): ?>
                                <div class="student-empty">Your attendance chart will appear after your first class record.</div>
                            <?php else: ?>
                                <svg class="student-chart" viewBox="0 0 640 230" role="img" aria-label="Attendance counts for the last six months">
                                    <line x1="30" y1="190" x2="620" y2="190" stroke="#e6ece8"/>
                                    <?php $chartIndex = 0; foreach ($monthlyBuckets as $bucket): ?>
                                        <?php
                                        $barX = 52 + $chartIndex * 96;
                                        $barHeight = (int)round(135 * $bucket['total'] / $maxMonthTotal);
                                        $presentHeight = $bucket['total'] > 0 ? (int)round($barHeight * $bucket['present'] / $bucket['total']) : 0;
                                        ?>
                                        <rect x="<?= $barX ?>" y="<?= 190 - $barHeight ?>" width="46" height="<?= max(2, $barHeight) ?>" rx="8" fill="#e8f0e9"/>
                                        <rect x="<?= $barX ?>" y="<?= 190 - $presentHeight ?>" width="46" height="<?= max(0, $presentHeight) ?>" rx="8" fill="#2854cc"/>
                                        <text x="<?= $barX + 23 ?>" y="215" text-anchor="middle" fill="#74838a" font-size="12"><?= studentEscape($bucket['label']) ?></text>
                                        <?php $chartIndex++; endforeach; ?>
                                </svg>
                                <div class="small student-muted"><span class="me-3"><i class="fas fa-square me-1" style="color:#2854cc"></i>Present</span><span><i class="fas fa-square me-1" style="color:#eaf1fb"></i>Total classes</span></div>
                            <?php endif; ?>
                        </section>
                    </div>
                    <div class="col-xl-5 mb-4">
                        <section class="student-card">
                            <h2 class="student-card-title">Eligibility predictor</h2>
                            <p class="student-muted small">Estimated additional consecutive classes to attend, based on your recorded classes.</p>
                            <div class="row">
                                <?php foreach ($predictorTargets as $target): ?>
                                    <?php
                                    $classesNeeded = $totalClasses === 0
                                        ? null
                                        : (($presentClasses / $totalClasses) >= ($target / 100)
                                            ? 0
                                            : max(0, (int)ceil(($target * $totalClasses / 100 - $presentClasses) / (1 - $target / 100))));
                                    ?>
                                    <div class="col-4 text-center mb-2">
                                        <div class="student-stat-label">Target</div>
                                        <div class="h4 fw-bold mb-1"><?= $target ?>%</div>
                                        <div class="small student-muted"><?= $classesNeeded === null ? 'No data' : ($classesNeeded === 0 ? 'On track' : $classesNeeded . ' classes') ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-6 mb-4">
                        <section class="student-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="student-card-title">Recent attendance</h2>
                                <a class="student-muted small" href="student_attendance.php">See all</a>
                            </div>
                            <?php if (!$attendanceRows): ?>
                                <div class="student-empty">No attendance records yet.</div>
                            <?php else: ?>
                                <div class="table-responsive"><table class="table student-table">
                                    <thead><tr><th>Class</th><th>Date</th><th>Status</th></tr></thead><tbody>
                                    <?php foreach (array_slice($attendanceRows, 0, 5) as $record): ?>
                                        <?php $statusLabel = studentStatusLabel($record['status']); ?>
                                        <tr>
                                            <td><?= studentEscape($record['subject_name']) ?></td>
                                            <td><?= studentEscape(date('M j, Y', strtotime($record['dateTimeTaken']))) ?></td>
                                            <td><span class="student-badge <?= strtolower($statusLabel) ?>"><?= studentEscape($statusLabel) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table></div>
                            <?php endif; ?>
                        </section>
                    </div>
                    <div class="col-xl-6 mb-4">
                        <section class="student-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="student-card-title">Upcoming classes</h2>
                                <a class="student-muted small" href="student_timetable.php">View timetable</a>
                            </div>
                            <?php if (!$upcomingClasses): ?>
                                <div class="student-empty">No classes are listed in your timetable.</div>
                            <?php else: foreach ($upcomingClasses as $class): ?>
                                <div class="student-upcoming-item">
                                    <div class="student-time-block">
                                        <?= studentEscape(date('D', strtotime($class['next_date']))) ?><br>
                                        <?= studentEscape(date('M j', strtotime($class['next_date']))) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?= studentEscape($class['courseName'] ?: $class['slot_type']) ?></div>
                                        <div class="student-muted small"><?= studentEscape($class['faculty_name'] ?: 'Faculty not assigned') ?> · <?= studentEscape(substr($class['start_time'], 0, 5)) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </section>
                    </div>
                </div>
                <section class="student-card mb-4">
                    <div class="d-flex justify-content-between align-items-center"><h2 class="student-card-title">Latest announcements</h2><a href="student_notifications.php" class="student-muted small">All notifications</a></div>
                    <?php if (!$announcements): ?>
                        <div class="student-empty">There are no announcements for your class right now.</div>
                    <?php else: foreach ($announcements as $announcement): ?>
                        <article class="student-notice">
                            <div class="d-flex justify-content-between flex-wrap"><strong><?= studentEscape($announcement['title']) ?></strong><span class="small student-muted"><?= studentEscape(date('M j, Y', strtotime($announcement['created_at']))) ?></span></div>
                            <p class="mb-0 mt-2"><?= nl2br(studentEscape($announcement['body'])) ?></p>
                        </article>
                    <?php endforeach; endif; ?>
                </section>
                <footer class="student-footer">Attendly Student Portal · <?= date('Y') ?></footer>
            </main>
        </div>
    </div>
</div>
<script src="vendor/bootstrap5/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('sidebarToggleTop').addEventListener('click', function () {
    var sidebar = document.getElementById('accordionSidebar');
    if (window.matchMedia('(max-width: 767.98px)').matches) {
        sidebar.classList.toggle('student-sidebar-expanded');
    } else {
        sidebar.classList.toggle('student-sidebar-toggled');
    }
});

(function () {
    var content = document.getElementById('active-qr-content');
    var status = document.getElementById('active-qr-status');
    var qrImages = {};
    var serverOffset = 0;

    function element(tagName, className, text) {
        var node = document.createElement(tagName);
        if (className) node.className = className;
        if (text) node.textContent = text;
        return node;
    }

    function formatDate(dateValue) {
        var parts = dateValue.split('-');
        if (parts.length !== 3) return dateValue;
        return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))
            .toLocaleDateString(undefined, {year: 'numeric', month: 'short', day: 'numeric'});
    }

    function updateCountdown() {
        Array.prototype.forEach.call(content.querySelectorAll('[data-expires-at]'), function (card) {
            var expiry = Number(card.getAttribute('data-expires-at'));
            var remaining = Math.max(0, expiry - Math.floor(Date.now() / 1000 + serverOffset));
            var countdown = card.querySelector('.student-qr-countdown');
            var minutes = Math.floor(remaining / 60);
            var seconds = remaining % 60;
            countdown.textContent = remaining > 0
                ? String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0')
                : 'Expired';
            if (remaining === 0) {
                var cardStatus = card.querySelector('.student-qr-status');
                cardStatus.textContent = 'Expired';
                cardStatus.className = 'student-qr-status expired';
                var image = card.querySelector('.student-qr-image');
                if (image) image.remove();
                if (!card.querySelector('.student-qr-expired')) {
                    card.querySelector('.student-qr-image-wrap').appendChild(
                        element('span', 'student-muted small student-qr-expired', 'This QR session has expired.')
                    );
                }
            }
        });
    }

    function renderSession(data) {
        var card = element('article', 'student-qr-session-card');
        card.setAttribute('data-expires-at', String(data.expires_at));
        var heading = element('div', 'student-qr-session-heading');
        heading.appendChild(element('h3', '', data.subject_name));
        heading.appendChild(element('span', 'student-qr-status active', 'Active'));
        var layout = element('div', 'student-qr-layout');
        var details = element('div', 'student-qr-details');
        [
            ['Class', data.class_name],
            ['Subject', data.subject_name],
            ['Faculty', data.teacher_name],
            ['Date', formatDate(data.attendance_date)],
            ['Session time', data.session_time],
            ['Your attendance', data.attendance_status === 'present' ? 'Already marked' : 'Not marked']
        ].forEach(function (item) {
            var row = element('div', 'student-qr-detail');
            row.appendChild(element('span', '', item[0]));
            row.appendChild(element('strong', '', item[1]));
            details.appendChild(row);
        });
        var timeRemaining = element('p', 'student-muted small mb-0 mt-3', 'QR expires in ');
        var countdown = element('strong', '', '—');
        countdown.className = 'student-qr-countdown';
        timeRemaining.appendChild(countdown);
        details.appendChild(timeRemaining);

        var qr = element('div', 'student-qr-image-wrap');
        var image = element('img', 'student-qr-image');
        image.alt = 'QR code for the active attendance session';
        if (typeof data.qr_image === 'string'
            && /^data:image\/(?:png|svg\+xml);base64,/.test(data.qr_image)) {
            qrImages[data.session_id] = data.qr_image;
        }
        if (qrImages[data.session_id]) {
            image.src = qrImages[data.session_id];
            qr.appendChild(image);
        } else {
            qr.appendChild(element('p', 'student-muted small mb-0', 'The session QR is unavailable. Refresh the page or contact your faculty member.'));
        }
        qr.appendChild(element('span', 'student-muted small', 'Scan this code with your phone camera.'));
        layout.appendChild(details);
        layout.appendChild(qr);
        card.appendChild(heading);
        card.appendChild(layout);
        return card;
    }

    function refreshActiveSession() {
        var url = 'activeSessionQr.php';
        var knownSessionIds = Object.keys(qrImages);
        if (knownSessionIds.length) {
            url += '?session_ids=' + encodeURIComponent(knownSessionIds.join(','));
        }
        fetch(url, {credentials: 'same-origin', headers: {'Accept': 'application/json'}})
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) throw new Error(data.error || 'Unable to load the active QR session.');
                    return data;
                });
            })
            .then(function (data) {
                if (!data.active || !Array.isArray(data.sessions) || data.sessions.length === 0) {
                    qrImages = {};
                    status.textContent = 'No active session';
                    status.className = 'student-qr-status';
                    content.replaceChildren(element('div', 'student-empty', 'No active QR attendance session is available for your class right now.'));
                    return;
                }
                serverOffset = data.server_now - Math.floor(Date.now() / 1000);
                var activeIds = {};
                var sessionList = element('div', 'student-qr-session-list');
                data.sessions.forEach(function (session) {
                    activeIds[session.session_id] = true;
                    sessionList.appendChild(renderSession(session));
                });
                Object.keys(qrImages).forEach(function (sessionId) {
                    if (!activeIds[sessionId]) delete qrImages[sessionId];
                });
                status.textContent = data.sessions.length + ' active';
                status.className = 'student-qr-status active';
                content.replaceChildren(sessionList);
                updateCountdown();
            })
            .catch(function (error) {
                status.textContent = 'Unavailable';
                status.className = 'student-qr-status expired';
                content.replaceChildren(element('div', 'student-alert warning', error.message));
            });
    }

    refreshActiveSession();
    window.setInterval(refreshActiveSession, 15000);
    window.setInterval(updateCountdown, 1000);
}());
</script>
</body>
</html>
