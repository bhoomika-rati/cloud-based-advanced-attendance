<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

include __DIR__ . '/Includes/dbcon.php';
include __DIR__ . '/Includes/qr_attendance.php';
date_default_timezone_set('Asia/Kolkata');

$result = $conn->query(
    "SELECT id FROM qr_attendance_sessions WHERE status = 'active' ORDER BY id"
);
if (!$result) {
    fwrite(STDERR, 'Unable to load QR attendance sessions: ' . $conn->error . PHP_EOL);
    exit(1);
}

$failures = 0;
while ($row = $result->fetch_assoc()) {
    try {
        qrFinalizeExpiredSession($conn, (int)$row['id']);
    } catch (Throwable $exception) {
        error_log('Scheduled QR attendance expiry failed for session ' . (int)$row['id'] . ': ' . $exception->getMessage());
        $failures++;
    }
}

if ($failures > 0) {
    fwrite(STDERR, $failures . ' QR attendance session(s) could not be finalized.' . PHP_EOL);
    exit(1);
}
