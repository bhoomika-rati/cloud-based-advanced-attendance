<?php
require_once __DIR__ . '/Includes/student_context.php';

$format = strtolower(trim((string)($_GET['format'] ?? '')));
if (!in_array($format, ['csv', 'pdf'], true)) {
    http_response_code(400);
    exit('Choose a valid report format.');
}
$selectedDate = trim((string)($_GET['date'] ?? ''));
$selectedMonth = trim((string)($_GET['month'] ?? ''));
$selectedSubject = trim((string)($_GET['subject'] ?? ''));
if (($selectedDate !== '' && !preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $selectedDate))
    || ($selectedMonth !== '' && !preg_match('/\A\d{4}-\d{2}\z/', $selectedMonth))) {
    http_response_code(400);
    exit('The selected report filters are invalid.');
}
$filteredRows = array_values(array_filter($attendanceRows, static function ($row) use ($selectedDate, $selectedMonth, $selectedSubject) {
    $date = substr((string)$row['dateTimeTaken'], 0, 10);
    return ($selectedDate === '' || $date === $selectedDate)
        && ($selectedMonth === '' || substr($date, 0, 7) === $selectedMonth)
        && ($selectedSubject === '' || (string)($row['subject_id'] ?? '') === $selectedSubject);
}));

if ($format === 'csv') {
    $csvSafe = static function ($value): string {
        $text = (string)$value;
        return preg_match('/\A[=+\-@]/', $text) ? "'" . $text : $text;
    };
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="student-attendance.csv"');
    $output = fopen('php://output', 'wb');
    if ($output === false) {
        http_response_code(500);
        exit('Unable to create the attendance export.');
    }
    fputcsv($output, ['Subject', 'Faculty', 'Date', 'Time', 'Status']);
    foreach ($filteredRows as $row) {
        $date = strtotime((string)$row['dateTimeTaken']);
        $timeValue = $row['marked_at'] ?: (strlen((string)$row['dateTimeTaken']) > 10 ? $row['dateTimeTaken'] : '');
        fputcsv($output, [
            $csvSafe($row['subject_name']),
            $csvSafe($row['teacher_name'] ?: 'Not recorded'),
            $date ? date('Y-m-d', $date) : $row['dateTimeTaken'],
            $timeValue !== '' ? date('H:i:s', strtotime($timeValue)) : '',
            studentStatusLabel($row['status'])
        ]);
    }
    fclose($output);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
if (!class_exists('TCPDF')) {
    error_log('Student attendance PDF export requires TCPDF.');
    http_response_code(503);
    exit('PDF export is not available. Please contact the system administrator.');
}
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Attendly');
$pdf->SetTitle('Student attendance report');
$pdf->SetMargins(12, 14, 12);
$pdf->AddPage();
$html = '<h1>Attendance Report</h1><p>Student: '
    . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ' ('
    . htmlspecialchars($admissionNumber, ENT_QUOTES, 'UTF-8') . ')</p>'
    . '<table border="1" cellpadding="6"><thead><tr><th>Subject</th><th>Faculty</th><th>Date</th><th>Time</th><th>Status</th></tr></thead><tbody>';
foreach ($filteredRows as $row) {
    $date = strtotime((string)$row['dateTimeTaken']);
    $timeValue = $row['marked_at'] ?: (strlen((string)$row['dateTimeTaken']) > 10 ? $row['dateTimeTaken'] : '');
    $values = [
        $row['subject_name'],
        $row['teacher_name'] ?: 'Not recorded',
        $date ? date('M j, Y', $date) : $row['dateTimeTaken'],
        $timeValue !== '' ? date('g:i A', strtotime($timeValue)) : '—',
        studentStatusLabel($row['status'])
    ];
    $html .= '<tr>';
    foreach ($values as $value) {
        $html .= '<td>' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '</td>';
    }
    $html .= '</tr>';
}
$html .= '</tbody></table>';
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('student-attendance.pdf', 'D');
exit;
