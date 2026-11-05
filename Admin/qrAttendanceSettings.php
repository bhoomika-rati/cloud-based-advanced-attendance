<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (($_SESSION['userType'] ?? '') !== 'Administrator') {
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

if (empty($_SESSION['qrAttendanceSettingsCsrf'])) {
    $_SESSION['qrAttendanceSettingsCsrf'] = bin2hex(random_bytes(32));
}

function qrSettingsEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$messageClass = 'info';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $latitudeValue = $_POST['latitude'] ?? '';
    $longitudeValue = $_POST['longitude'] ?? '';
    $coordinateInputsAreStrings = is_string($latitudeValue) && is_string($longitudeValue);
    $latitudeInput = $coordinateInputsAreStrings ? trim($latitudeValue) : '';
    $longitudeInput = $coordinateInputsAreStrings ? trim($longitudeValue) : '';
    $radiusInput = filter_var($_POST['radius_m'] ?? null, FILTER_VALIDATE_INT);

    if (!$coordinateInputsAreStrings) {
        http_response_code(400);
        $message = 'Enter valid latitude and longitude coordinates.';
        $messageClass = 'danger';
    } elseif (!is_string($csrfToken) || !hash_equals($_SESSION['qrAttendanceSettingsCsrf'], $csrfToken)) {
        http_response_code(400);
        $message = 'Your request expired. Refresh this page and try again.';
        $messageClass = 'danger';
    } elseif ($radiusInput === false || $radiusInput < 1 || $radiusInput > 10000) {
        $message = 'Enter a permitted radius between 1 and 10,000 metres.';
        $messageClass = 'danger';
    } elseif (($latitudeInput === '') !== ($longitudeInput === '')) {
        $message = 'Enter both college coordinates, or clear both fields to disable GPS check-in.';
        $messageClass = 'danger';
    } else {
        $latitude = null;
        $longitude = null;
        if ($latitudeInput !== '') {
            $latitude = filter_var($latitudeInput, FILTER_VALIDATE_FLOAT);
            $longitude = filter_var($longitudeInput, FILTER_VALIDATE_FLOAT);
            if ($latitude === false || $longitude === false
                || !is_finite((float)$latitude) || !is_finite((float)$longitude)
                || $latitude < -90 || $latitude > 90
                || $longitude < -180 || $longitude > 180) {
                $message = 'Enter valid latitude and longitude coordinates.';
                $messageClass = 'danger';
            }
        }

        if ($message === '') {
            $adminId = (int)$_SESSION['userId'];
            $stmt = $conn->prepare(
                'UPDATE qr_attendance_settings
                 SET latitude = ?, longitude = ?, radius_m = ?, updated_by = ?
                 WHERE id = 1'
            );
            if (!$stmt) {
                error_log('QR attendance settings update could not be prepared: ' . $conn->error);
                http_response_code(500);
                $message = 'Unable to save the college location settings.';
                $messageClass = 'danger';
            } else {
                $radius = (int)$radiusInput;
                $stmt->bind_param('ddii', $latitude, $longitude, $radius, $adminId);
                if (!$stmt->execute()) {
                    error_log('QR attendance settings update failed: ' . $stmt->error);
                    http_response_code(500);
                    $message = 'Unable to save the college location settings.';
                    $messageClass = 'danger';
                } else {
                    $message = $latitude === null
                        ? 'College GPS check-in is disabled until real college coordinates are entered.'
                        : 'College GPS location settings saved.';
                    $messageClass = 'success';
                }
                $stmt->close();
            }
        }
    }
}

$settingsResult = $conn->query(
    'SELECT latitude, longitude, radius_m, updated_at FROM qr_attendance_settings WHERE id = 1'
);
if (!$settingsResult) {
    error_log('QR attendance settings could not be loaded: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load the college location settings.');
}
$settings = $settingsResult->fetch_assoc();
if (!$settings) {
    http_response_code(500);
    exit('The college location settings have not been initialized. Apply the QR attendance schema migration.');
}
$configured = $settings['latitude'] !== null
    && $settings['longitude'] !== null
    && $settings['radius_m'] !== null
    && is_numeric($settings['latitude'])
    && is_numeric($settings['longitude'])
    && (float)$settings['latitude'] >= -90 && (float)$settings['latitude'] <= 90
    && (float)$settings['longitude'] >= -180 && (float)$settings['longitude'] <= 180
    && (int)$settings['radius_m'] >= 1 && (int)$settings['radius_m'] <= 10000;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Attendance Location | Attendly Admin</title>
    <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'Includes/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'Includes/topbar.php'; ?>
            <main class="container-fluid" id="container-wrapper">
                <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
                    <div>
                        <h1 class="h3 mb-1 text-gray-800">QR Attendance Location</h1>
                        <p class="text-muted mb-0">Set the real college location used to verify QR check-ins.</p>
                    </div>
                    <a class="btn btn-outline-primary mt-2" href="qrAttendanceReports.php">
                        <i class="fas fa-map-marker-alt mr-1"></i> QR attendance reports
                    </a>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?= qrSettingsEscape($messageClass) ?>" role="status"><?= qrSettingsEscape($message) ?></div>
                <?php endif; ?>

                <section class="card shadow-sm">
                    <div class="card-header py-3">
                        <h2 class="h6 m-0 font-weight-bold text-primary">College geofence</h2>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-<?= $configured ? 'success' : 'warning' ?>" role="status">
                            <?= $configured
                                ? 'GPS check-in is enabled. Attendance is accepted only inside the configured radius.'
                                : 'GPS check-in is disabled until an administrator enters the actual college coordinates and permitted radius. No sample location or radius is pre-filled.' ?>
                        </div>
                        <form method="post" action="qrAttendanceSettings.php">
                            <input type="hidden" name="csrf_token" value="<?= qrSettingsEscape($_SESSION['qrAttendanceSettingsCsrf']) ?>">
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="latitude">College latitude</label>
                                    <input class="form-control" type="number" step="any" min="-90" max="90" id="latitude" name="latitude"
                                           value="<?= $settings['latitude'] === null ? '' : qrSettingsEscape($settings['latitude']) ?>"
                                           placeholder="Enter actual latitude">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="longitude">College longitude</label>
                                    <input class="form-control" type="number" step="any" min="-180" max="180" id="longitude" name="longitude"
                                           value="<?= $settings['longitude'] === null ? '' : qrSettingsEscape($settings['longitude']) ?>"
                                           placeholder="Enter actual longitude">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="radius_m">Permitted radius (metres)</label>
                                    <input class="form-control" type="number" min="1" max="10000" step="1" id="radius_m" name="radius_m"
                                           value="<?= $settings['radius_m'] === null ? '' : (int)$settings['radius_m'] ?>" required>
                                </div>
                            </div>
                            <p class="small text-muted">Coordinates are checked on the server using the Haversine distance. Browser location accuracy must be 100 metres or better. Clear both coordinates and save to disable GPS check-in.</p>
                            <button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i> Save location settings</button>
                        </form>
                        <?php if ($configured && $settings['updated_at']): ?>
                            <p class="small text-muted mt-3 mb-0">Last updated <?= qrSettingsEscape($settings['updated_at']) ?>.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>
</div>
<script src="../vendore/jquery/jquery.min.js"></script>
<script src="../vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="js/ruang-admin.min.js"></script>
</body>
</html>
