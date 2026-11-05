<?php
require_once __DIR__ . '/Includes/student_context.php';

$selectedDate = trim((string)($_GET['date'] ?? ''));
$selectedMonth = trim((string)($_GET['month'] ?? ''));
$selectedSubject = trim((string)($_GET['subject'] ?? ''));
$filterError = '';
if ($selectedDate !== '' && !preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $selectedDate)) {
    $filterError = 'Choose a valid date.';
    $selectedDate = '';
}
if ($selectedMonth !== '' && !preg_match('/\A\d{4}-\d{2}\z/', $selectedMonth)) {
    $filterError = 'Choose a valid month.';
    $selectedMonth = '';
}
$filteredRows = array_values(array_filter($attendanceRows, static function ($row) use ($selectedDate, $selectedMonth, $selectedSubject) {
    $date = substr((string)$row['dateTimeTaken'], 0, 10);
    if ($selectedDate !== '' && $date !== $selectedDate) {
        return false;
    }
    if ($selectedMonth !== '' && substr($date, 0, 7) !== $selectedMonth) {
        return false;
    }
    if ($selectedSubject !== '' && (string)($row['subject_id'] ?? '') !== $selectedSubject) {
        return false;
    }
    return true;
}));
$pageTitle = 'My Attendance';
require __DIR__ . '/Includes/student_page_start.php';
?>
<header class="student-page-heading">
    <div><h1>My attendance</h1><p>Your complete attendance history, with subject and date filters.</p></div>
    <a class="student-action secondary" href="attendance_reports.php"><i class="fas fa-chart-bar"></i> Attendance reports</a>
</header>
<?php if ($filterError !== ''): ?><div class="alert alert-warning"><?= studentEscape($filterError) ?></div><?php endif; ?>
<section class="student-card mb-4">
    <form method="get" class="row align-items-end">
        <div class="col-lg-3 col-sm-6 mb-3">
            <label class="student-form-label" for="filter-date">Date</label>
            <input id="filter-date" class="form-control student-form-control" type="date" name="date" value="<?= studentEscape($selectedDate) ?>">
        </div>
        <div class="col-lg-3 col-sm-6 mb-3">
            <label class="student-form-label" for="filter-month">Month</label>
            <input id="filter-month" class="form-control student-form-control" type="month" name="month" value="<?= studentEscape($selectedMonth) ?>">
        </div>
        <div class="col-lg-3 col-sm-6 mb-3">
            <label class="student-form-label" for="filter-subject">Subject</label>
            <select id="filter-subject" class="form-control student-form-control" name="subject">
                <option value="">All subjects</option>
                <?php foreach ($studentSubjects as $subjectId => $subjectName): ?>
                    <?php if ($subjectId !== 'unknown'): ?>
                        <option value="<?= studentEscape($subjectId) ?>" <?= $selectedSubject === $subjectId ? 'selected' : '' ?>><?= studentEscape($subjectName) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-3 col-sm-6 mb-3 d-flex" style="gap:8px">
            <button type="submit" class="student-action flex-grow-1"><i class="fas fa-filter"></i> Filter</button>
            <a href="student_attendance.php" class="student-action secondary">Reset</a>
        </div>
    </form>
</section>
<section class="student-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div><h2 class="student-card-title mb-1">Attendance records</h2><span class="student-muted small"><?= count($filteredRows) ?> record<?= count($filteredRows) === 1 ? '' : 's' ?></span></div>
        <div class="d-flex mt-2 mt-md-0" style="gap:8px">
            <a class="student-action secondary" href="attendance_export.php?format=csv&amp;date=<?= rawurlencode($selectedDate) ?>&amp;month=<?= rawurlencode($selectedMonth) ?>&amp;subject=<?= rawurlencode($selectedSubject) ?>"><i class="fas fa-file-csv"></i> CSV</a>
            <a class="student-action secondary" href="attendance_export.php?format=pdf&amp;date=<?= rawurlencode($selectedDate) ?>&amp;month=<?= rawurlencode($selectedMonth) ?>&amp;subject=<?= rawurlencode($selectedSubject) ?>"><i class="fas fa-file-pdf"></i> PDF</a>
        </div>
    </div>
    <div class="table-responsive"><table class="table student-table">
        <thead><tr><th>Subject</th><th>Faculty</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (!$filteredRows): ?>
            <tr><td colspan="5" class="student-empty">No attendance records match these filters.</td></tr>
        <?php else: foreach ($filteredRows as $record): ?>
            <?php $statusLabel = studentStatusLabel($record['status']); ?>
            <tr>
                <td><?= studentEscape($record['subject_name']) ?></td>
                <td><?= studentEscape($record['teacher_name'] ?: 'Not recorded') ?></td>
                <td><?= studentEscape(date('M j, Y', strtotime($record['dateTimeTaken']))) ?></td>
                <td><?= studentEscape($record['marked_at'] ? date('g:i A', strtotime($record['marked_at'])) : (strlen($record['dateTimeTaken']) > 10 ? date('g:i A', strtotime($record['dateTimeTaken'])) : '—')) ?></td>
                <td><span class="student-badge <?= strtolower($statusLabel) ?>"><?= studentEscape($statusLabel) ?></span></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table></div>
</section>
<?php require __DIR__ . '/Includes/student_page_end.php'; ?>
