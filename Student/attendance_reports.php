<?php
require_once __DIR__ . '/Includes/student_context.php';

$subjectStats = [];
$monthlyStats = [];
foreach ($attendanceRows as $row) {
    $subjectKey = (string)($row['subject_id'] ?? 'unknown');
    if (!isset($subjectStats[$subjectKey])) {
        $subjectStats[$subjectKey] = ['name' => $row['subject_name'], 'total' => 0, 'present' => 0, 'absent' => 0];
    }
    $subjectStats[$subjectKey]['total']++;
    $status = studentStatusLabel($row['status']);
    if ($status === 'Present') {
        $subjectStats[$subjectKey]['present']++;
    } elseif ($status === 'Absent') {
        $subjectStats[$subjectKey]['absent']++;
    }
    $monthKey = date('Y-m', strtotime((string)$row['dateTimeTaken']));
    if (!isset($monthlyStats[$monthKey])) {
        $monthlyStats[$monthKey] = ['total' => 0, 'present' => 0, 'absent' => 0];
    }
    $monthlyStats[$monthKey]['total']++;
    if ($status === 'Present') {
        $monthlyStats[$monthKey]['present']++;
    } elseif ($status === 'Absent') {
        $monthlyStats[$monthKey]['absent']++;
    }
}
krsort($monthlyStats);
$pageTitle = 'Attendance Reports';
require __DIR__ . '/Includes/student_page_start.php';
?>
<header class="student-page-heading">
    <div><h1>Attendance reports</h1><p>Subject-wise, monthly and overall attendance from your records.</p></div>
    <div class="d-flex" style="gap:8px">
        <a class="student-action secondary" href="attendance_export.php?format=csv"><i class="fas fa-file-csv"></i> Download CSV</a>
        <a class="student-action secondary" href="attendance_export.php?format=pdf"><i class="fas fa-file-pdf"></i> Download PDF</a>
    </div>
</header>
<div class="row mb-4">
    <div class="col-md-4 mb-3"><article class="student-stat"><span class="student-stat-icon"><i class="fas fa-book"></i></span><div class="student-stat-label">Classes recorded</div><div class="student-stat-value"><?= $totalClasses ?></div></article></div>
    <div class="col-md-4 mb-3"><article class="student-stat lime"><span class="student-stat-icon"><i class="fas fa-check"></i></span><div class="student-stat-label">Present</div><div class="student-stat-value"><?= $presentClasses ?></div></article></div>
    <div class="col-md-4 mb-3"><article class="student-stat red"><span class="student-stat-icon"><i class="fas fa-chart-line"></i></span><div class="student-stat-label">Overall attendance</div><div class="student-stat-value"><?= $attendancePercentage ?>%</div></article></div>
</div>
<div class="row">
    <div class="col-xl-7 mb-4">
        <section class="student-card">
            <h2 class="student-card-title">Subject-wise attendance</h2>
            <?php if (!$subjectStats): ?><div class="student-empty">No attendance records available to report.</div>
            <?php else: foreach ($subjectStats as $subject): ?>
                <?php $percent = $subject['total'] ? (int)round($subject['present'] / $subject['total'] * 100) : 0; ?>
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2"><strong><?= studentEscape($subject['name']) ?></strong><span class="student-muted"><?= $percent ?>% · <?= $subject['present'] ?>/<?= $subject['total'] ?></span></div>
                    <div class="progress" style="height:9px;border-radius:20px"><div class="progress-bar" role="progressbar" style="width:<?= $percent ?>%;background:#2854cc;border-radius:20px" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div></div>
                </div>
            <?php endforeach; endif; ?>
        </section>
    </div>
    <div class="col-xl-5 mb-4">
        <section class="student-card">
            <h2 class="student-card-title">Monthly attendance</h2>
            <?php if (!$monthlyStats): ?><div class="student-empty">Monthly results will appear when attendance is recorded.</div>
            <?php else: ?>
                <div class="table-responsive"><table class="table student-table" style="min-width:380px">
                    <thead><tr><th>Month</th><th>Classes</th><th>Present</th><th>Rate</th></tr></thead><tbody>
                    <?php foreach (array_slice($monthlyStats, 0, 12, true) as $month => $stats): ?>
                        <tr><td><?= studentEscape(date('M Y', strtotime($month . '-01'))) ?></td><td><?= $stats['total'] ?></td><td><?= $stats['present'] ?></td><td><?= (int)round($stats['present'] / $stats['total'] * 100) ?>%</td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/Includes/student_page_end.php'; ?>
