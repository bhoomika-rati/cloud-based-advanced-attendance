<?php
require_once __DIR__ . '/env.php';

$host = trim((string)(getenv('DB_HOST') ?: 'localhost'));
$portValue = getenv('DB_PORT');
$port = $portValue === false || $portValue === '' ? 3306 : filter_var($portValue, FILTER_VALIDATE_INT);
$user = (string)(getenv('DB_USER') !== false ? getenv('DB_USER') : 'root');
$pass = (string)(getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
$db = trim((string)(getenv('DB_NAME') ?: 'attendancemsystem'));

if ($host === '' || $db === '' || $port === false || $port < 1 || $port > 65535) {
	throw new RuntimeException('Check DB_HOST, DB_PORT, and DB_NAME in the server environment or .env file.');
}

$conn = new mysqli($host, $user, $pass, $db, (int)$port);
if ($conn->connect_error) {
	error_log('Database connection failed: ' . $conn->connect_error);
	http_response_code(500);
	exit('Database connection unavailable. Check the server database configuration.');
}
?>