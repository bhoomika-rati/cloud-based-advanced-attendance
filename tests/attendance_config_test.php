<?php

require_once __DIR__ . '/../Includes/app_config.php';
require_once __DIR__ . '/../Includes/qr_attendance.php';

function expectSame($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message);
    }
}

function expectConfigFailure(string $environment, string $url): void
{
    putenv('APP_ENV=' . $environment);
    $variable = $environment === 'development'
        ? 'DEVELOPMENT_WEBSITE_BASE_URL'
        : ($environment === 'staging' ? 'STAGING_WEBSITE_BASE_URL' : 'WEBSITE_BASE_URL');
    putenv($variable . '=' . $url);
    try {
        attendanceWebsiteBaseUrl();
    } catch (RuntimeException $exception) {
        return;
    }

    throw new RuntimeException('Expected URL configuration to be rejected: ' . $url);
}

putenv('APP_ENV=development');
putenv('WEBSITE_BASE_URL=https://production.example.edu/project');
putenv('DEVELOPMENT_WEBSITE_BASE_URL=http://localhost/Attendance-Management-System-master');
expectSame(
    'http://localhost/Attendance-Management-System-master',
    attendanceWebsiteBaseUrl(),
    'Development localhost URL was not selected independently from production configuration.'
);

putenv('DEVELOPMENT_WEBSITE_BASE_URL=http://192.168.1.25/Attendance-Management-System-master');
expectSame(
    'http://192.168.1.25/Attendance-Management-System-master',
    attendanceWebsiteBaseUrl(),
    'Development private-LAN URL was not accepted.'
);

expectConfigFailure('development', 'http://attendance.example.edu/project');
expectConfigFailure('production', 'http://localhost/project');
expectConfigFailure('production', 'https://192.168.1.25/project');
expectConfigFailure('production', 'https://attendance.example.edu/project?debug=1');
expectConfigFailure('invalid-environment', 'https://attendance.example.edu/project');

putenv('APP_ENV=production');
putenv('WEBSITE_BASE_URL=https://attendance.example.edu/project');
expectSame(
    'https://attendance.example.edu/project',
    attendanceWebsiteBaseUrl(),
    'Production HTTPS URL was not accepted.'
);

putenv('DEVELOPMENT_QR_ATTENDANCE_TOKEN_KEY=' . base64_encode(str_repeat('k', 32)));
putenv('QR_ATTENDANCE_TOKEN_KEY=' . base64_encode(str_repeat('p', 32)));
putenv('APP_ENV=development');
$firstToken = qrAttendanceSessionToken(123);
expectSame(64, strlen($firstToken), 'Attendance session token has an unexpected length.');
expectSame($firstToken, qrAttendanceSessionToken(123), 'Attendance session token is not stable.');
expectSame(
    hash_hmac('sha256', 'qr-attendance-session:123', str_repeat('k', 32)),
    $firstToken,
    'Development QR token did not use the development-only signing key.'
);
if ($firstToken === qrAttendanceSessionToken(124)) {
    throw new RuntimeException('Different attendance sessions received the same token.');
}

if (qrAttendanceDistanceMeters(0, 0, 0, 0) !== 0.0) {
    throw new RuntimeException('Distance calculation should return zero for identical coordinates.');
}

echo "Attendance configuration checks passed.\n";
