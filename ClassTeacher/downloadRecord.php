<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

$classId = $_SESSION['classId'] ?? null;
$classArmId = $_SESSION['classArmId'] ?? null;

if ($classId === null || $classArmId === null) {
    http_response_code(403);
    exit('Your teacher account is not assigned to a class group.');
}

$attendanceColumns = [];
$columnsResult = $conn->query("SHOW COLUMNS FROM tblattendance");
if (!$columnsResult) {
    http_response_code(500);
    exit('Unable to inspect attendance records.');
}
while ($column = $columnsResult->fetch_assoc()) {
    $attendanceColumns[$column['Field']] = true;
}
$columnsResult->free();

$optionalFields = ['lateArrival', 'justification', 'latenessDuration', 'description'];
$optionalSelections = [];
foreach ($optionalFields as $field) {
    $selection = isset($attendanceColumns[$field]) ? "attendance.`{$field}`" : 'NULL';
    $optionalSelections[] = "{$selection} AS `{$field}`";
}

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime($today . ' +1 day'));
$query = "SELECT attendance.status, attendance.dateTimeTaken,
                 " . implode(",\n                 ", $optionalSelections) . ",
                 class.className, classArm.classArmName,
                 academicTerm.sessionName, term.termName,
                 student.firstName, student.lastName, student.admissionNumber
          FROM tblattendance attendance
          LEFT JOIN tblclass class ON class.Id = attendance.classId
          LEFT JOIN tblclassarms classArm ON classArm.Id = attendance.classArmId
          LEFT JOIN tblsessionterm academicTerm ON academicTerm.Id = attendance.sessionTermId
          LEFT JOIN tblterm term ON term.Id = academicTerm.termId
          LEFT JOIN tblstudents student ON student.admissionNumber = attendance.admissionNo
          WHERE attendance.classId = ?
            AND attendance.classArmId = ?
            AND attendance.dateTimeTaken >= ?
            AND attendance.dateTimeTaken < ?
          ORDER BY attendance.dateTimeTaken, student.lastName, student.firstName";

$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    exit('Unable to prepare today’s attendance report.');
}
$startOfDay = $today;
$startOfTomorrow = $tomorrow;
$stmt->bind_param('ssss', $classId, $classArmId, $startOfDay, $startOfTomorrow);
if (!$stmt->execute()) {
    $stmt->close();
    http_response_code(500);
    exit('Unable to load today’s attendance records.');
}
$records = $stmt->get_result();
if (!$records) {
    $stmt->close();
    http_response_code(500);
    exit('Unable to read today’s attendance records.');
}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="attendance-report-' . $today . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

function reportCell($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function attendanceFlag($value) {
    if ($value === null || $value === '') {
        return '';
    }

    return (string)$value === '1' ? 'Yes' : 'No';
}
?>
<table border="1">
    <caption>Today's Attendance Report - <?php echo reportCell($today); ?></caption>
    <thead>
        <tr>
            <th>#</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Admission No</th>
            <th>Class</th>
            <th>Class Arm</th>
            <th>Session</th>
            <th>Term</th>
            <th>Status</th>
            <th>Date</th>
            <th>Late Arrival</th>
            <th>Justification</th>
            <th>Lateness Duration</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($records->num_rows > 0): ?>
            <?php $count = 1; ?>
            <?php while ($row = $records->fetch_assoc()): ?>
                <?php
                $status = (string)$row['status'] === '0'
                    ? 'Present'
                    : ((string)$row['status'] === '1' ? 'Absent' : 'Unknown');
                ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td><?php echo reportCell($row['firstName']); ?></td>
                    <td><?php echo reportCell($row['lastName']); ?></td>
                    <td><?php echo reportCell($row['admissionNumber']); ?></td>
                    <td><?php echo reportCell($row['className']); ?></td>
                    <td><?php echo reportCell($row['classArmName']); ?></td>
                    <td><?php echo reportCell($row['sessionName']); ?></td>
                    <td><?php echo reportCell($row['termName']); ?></td>
                    <td><?php echo reportCell($status); ?></td>
                    <td><?php echo reportCell($row['dateTimeTaken']); ?></td>
                    <td><?php echo reportCell(attendanceFlag($row['lateArrival'])); ?></td>
                    <td><?php echo reportCell(attendanceFlag($row['justification'])); ?></td>
                    <td><?php echo reportCell($row['latenessDuration']); ?></td>
                    <td><?php echo reportCell($row['description']); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="14">No attendance records were found for today.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php
$stmt->close();
?>
