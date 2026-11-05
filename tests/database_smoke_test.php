<?php

require_once __DIR__ . '/../Includes/dbcon.php';

$requiredTables = [
    'qr_attendance_sessions',
    'qr_attendance_records',
    'qr_attendance_settings',
    'session_timetable',
    'tblstudents'
];
$tableStmt = $conn->prepare(
    'SELECT COUNT(*) AS table_count
     FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = ?'
);
if (!$tableStmt) {
    throw new RuntimeException('Unable to prepare database schema check.');
}

$missingTables = [];
foreach ($requiredTables as $tableName) {
    $tableStmt->bind_param('s', $tableName);
    if (!$tableStmt->execute()) {
        throw new RuntimeException('Unable to check the local attendance schema.');
    }
    $row = $tableStmt->get_result()->fetch_assoc();
    if ((int)$row['table_count'] !== 1) {
        $missingTables[] = $tableName;
    }
}
$tableStmt->close();

if ($missingTables) {
    fwrite(STDERR, 'Missing attendance tables: ' . implode(', ', $missingTables) . PHP_EOL);
    exit(1);
}

echo "Database connectivity and QR attendance schema checks passed.\n";
