<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

function aa_count(mysqli $conn, string $sql): int {
    $result = mysqli_query($conn, $sql);
    if (!$result) return 0;
    $row = mysqli_fetch_assoc($result);
    return (int)($row['total'] ?? 0);
}
function aa_escape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$totalStudents = aa_count($conn, "SELECT COUNT(*) AS total FROM tblstudents");
$totalAttendanceRecords = aa_count($conn, "SELECT COUNT(*) AS total FROM tblattendance");
// Existing records use YYYY-MM-DD in dateTimeTaken; status 0 = present, 1 = absent.
$presentToday = aa_count($conn, "SELECT COUNT(*) AS total FROM tblattendance WHERE status='0' AND dateTimeTaken=CURDATE()");
$absentToday = aa_count($conn, "SELECT COUNT(*) AS total FROM tblattendance WHERE status='1' AND dateTimeTaken=CURDATE()");
$qrSessionCount = aa_count($conn, "SELECT COUNT(*) AS total FROM qr_attendance_sessions");
$qrActiveSessions = aa_count($conn, "SELECT COUNT(*) AS total FROM qr_attendance_sessions WHERE status='active' AND expires_at > NOW()");
$qrGpsVerified = aa_count($conn, "SELECT COUNT(*) AS total FROM qr_attendance_records WHERE location_verified=1");
$qrLocationResult = $conn->query("SELECT latitude, longitude, radius_m FROM qr_attendance_settings WHERE id=1");
if (!$qrLocationResult) {
    error_log('Admin QR location status could not be loaded: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load QR attendance location status.');
}
$qrLocation = $qrLocationResult ? $qrLocationResult->fetch_assoc() : null;
$qrLocationConfigured = $qrLocation
    && $qrLocation['latitude'] !== null
    && $qrLocation['longitude'] !== null
    && $qrLocation['radius_m'] !== null;
$attendanceSql = "SELECT a.Id, a.admissionNo, a.classId, a.classArmId, a.status, a.dateTimeTaken,
                         s.firstName, s.lastName
                  FROM tblattendance a
                  LEFT JOIN tblstudents s ON s.admissionNumber = a.admissionNo
                  ORDER BY a.dateTimeTaken DESC, a.Id DESC LIMIT 100";

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportResult = mysqli_query($conn, $attendanceSql);
    if (!$exportResult) { http_response_code(500); exit('Could not export attendance records.'); }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="advanced-attendance-log.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Attendance ID', 'Admission Number', 'Student Name', 'Class ID', 'Class Group ID', 'Date', 'Status']);
    while ($r = mysqli_fetch_assoc($exportResult)) {
        $name = trim(($r['firstName'] ?? '') . ' ' . ($r['lastName'] ?? ''));
        fputcsv($out, [$r['Id'], $r['admissionNo'], $name ?: 'Student not found', $r['classId'], $r['classArmId'], $r['dateTimeTaken'], ((string)$r['status'] === '1') ? 'Absent' : 'Present']);
    }
    fclose($out); exit;
}
$searchTerm = isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '';
$pageSize = 10;
$requestedPage = isset($_GET['page']) && is_string($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$attendanceFilter = '';
if ($searchTerm !== '') {
    $attendanceFilter = " WHERE (a.admissionNo LIKE ? OR CONCAT_WS(' ', s.firstName, s.lastName) LIKE ?)";
    $searchPattern = '%' . $searchTerm . '%';
}

$countSql = "SELECT COUNT(*) AS total FROM tblattendance a"
    . ($searchTerm !== '' ? " LEFT JOIN tblstudents s ON s.admissionNumber = a.admissionNo" : '')
    . $attendanceFilter;
$countStatement = mysqli_prepare($conn, $countSql);
if (!$countStatement) {
    error_log('Admin attendance log count could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load attendance records.');
}
if ($searchTerm !== '' && !mysqli_stmt_bind_param($countStatement, 'ss', $searchPattern, $searchPattern)) {
    error_log('Admin attendance log count parameters could not be bound: ' . mysqli_stmt_error($countStatement));
    http_response_code(500);
    exit('Unable to load attendance records.');
}
if (!mysqli_stmt_execute($countStatement)) {
    error_log('Admin attendance log count could not be loaded: ' . mysqli_stmt_error($countStatement));
    http_response_code(500);
    exit('Unable to load attendance records.');
}
$countResult = mysqli_stmt_get_result($countStatement);
$attendanceCountRow = $countResult ? mysqli_fetch_assoc($countResult) : null;
mysqli_stmt_close($countStatement);
if (!$attendanceCountRow) {
    error_log('Admin attendance log count returned no result.');
    http_response_code(500);
    exit('Unable to load attendance records.');
}
$totalMatchingRecords = (int)$attendanceCountRow['total'];
$totalPages = max(1, (int)ceil($totalMatchingRecords / $pageSize));
$attendancePage = min($requestedPage, $totalPages);
$offset = ($attendancePage - 1) * $pageSize;

$pageSql = "SELECT a.Id, a.admissionNo, a.classId, a.classArmId, a.status, a.dateTimeTaken,
                   s.firstName, s.lastName
            FROM tblattendance a
            LEFT JOIN tblstudents s ON s.admissionNumber = a.admissionNo"
    . $attendanceFilter
    . " ORDER BY a.dateTimeTaken DESC, a.Id DESC LIMIT ? OFFSET ?";
$pageStatement = mysqli_prepare($conn, $pageSql);
if (!$pageStatement) {
    error_log('Admin attendance log page could not be prepared: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load attendance records.');
}
if ($searchTerm !== '') {
    if (!mysqli_stmt_bind_param($pageStatement, 'ssii', $searchPattern, $searchPattern, $pageSize, $offset)) {
        error_log('Admin attendance log page parameters could not be bound: ' . mysqli_stmt_error($pageStatement));
        http_response_code(500);
        exit('Unable to load attendance records.');
    }
} elseif (!mysqli_stmt_bind_param($pageStatement, 'ii', $pageSize, $offset)) {
    error_log('Admin attendance log page parameters could not be bound: ' . mysqli_stmt_error($pageStatement));
    http_response_code(500);
    exit('Unable to load attendance records.');
}
if (!mysqli_stmt_execute($pageStatement)) {
    error_log('Admin attendance log page could not be loaded: ' . mysqli_stmt_error($pageStatement));
    http_response_code(500);
    exit('Unable to load attendance records.');
}
$attendanceResult = mysqli_stmt_get_result($pageStatement);
$attendanceRows = [];
if (!$attendanceResult) {
    error_log('Admin attendance log result could not be read: ' . mysqli_stmt_error($pageStatement));
    mysqli_stmt_close($pageStatement);
    http_response_code(500);
    exit('Unable to load attendance records.');
}
while ($r = mysqli_fetch_assoc($attendanceResult)) $attendanceRows[] = $r;
mysqli_stmt_close($pageStatement);
$firstRecord = $totalMatchingRecords > 0 ? $offset + 1 : 0;
$lastRecord = min($offset + $pageSize, $totalMatchingRecords);
$pageWindowStart = max(1, $attendancePage - 2);
$pageWindowEnd = min($totalPages, $pageWindowStart + 4);
$pageWindowStart = max(1, $pageWindowEnd - 4);
$pageQuery = ['search' => $searchTerm];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Advanced Attendance and QR Check-in System admin dashboard">
<title>Advanced Attendance and QR Check-in System | Admin Dashboard</title>
<link href="img/logo/attnlg.jpg" rel="icon">
<link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet">
<link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="css/ruang-admin.min.css" rel="stylesheet">
<style>
:root{--aa-navy:#172844;--aa-blue:#2451c6;--aa-page:#eef1f6;--aa-muted:#748198;--aa-border:#e7eaf0}
body,#content-wrapper{background:var(--aa-page);color:var(--aa-navy)}body{font-family:Arial,Helvetica,sans-serif}#container-wrapper{padding:28px 25px 36px}
.aa-header{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:25px}.aa-title{font-size:30px;font-weight:750;margin:0 0 7px;color:#101b32}.aa-subtitle{margin:0;color:#68758a;font-size:14px}.aa-header-actions{display:flex;align-items:center;gap:12px}.aa-notice{color:#15223a;font-size:19px;padding:9px 12px}.aa-export{border:1px solid #9aa5b7;color:#172844;border-radius:9px;background:transparent;padding:10px 15px;font-weight:600}.aa-export:hover{background:#fff;color:#2451c6}
.aa-stat-card{border:1px solid var(--aa-border);border-radius:13px;background:#fff;min-height:220px;box-shadow:0 5px 15px rgba(25,39,67,.07)}.aa-stat-card .card-body{padding:22px 16px 19px;display:flex;flex-direction:column;align-items:center;text-align:center}.aa-stat-icon{width:76px;height:76px;border-radius:17px;display:flex;align-items:center;justify-content:center;font-size:31px;margin-bottom:15px}.aa-blue{background:#3f73df;color:#fff}.aa-green{background:#20ad79;color:#fff}.aa-red{background:#e4484d;color:#fff}.aa-orange{background:#f49a31;color:#fff}.aa-stat-label{color:#141c2c;font-size:15px;font-weight:700;margin-bottom:5px}.aa-stat-value{color:#101b32;font-size:37px;font-weight:750;line-height:1.2}.aa-stat-note{color:#4b688f;font-size:12px;margin-top:6px}
.aa-log-card{border:1px solid var(--aa-border);border-radius:13px;background:#fff;box-shadow:0 5px 15px rgba(25,39,67,.07);overflow:hidden}.aa-log-header{padding:21px 23px 15px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:15px}.aa-log-title{margin:0 0 5px;font-size:24px;font-weight:750;color:#101b32}.aa-log-subtitle{margin:0;color:#68758a;font-size:13px}.aa-search-wrap{position:relative}.aa-search-wrap i{position:absolute;left:13px;top:13px;color:#5e6878}.aa-search{min-width:280px;padding:10px 12px 10px 37px;border:1px solid #e5e8ee;border-radius:8px;background:#f5f6f8;outline:none}.aa-search:focus{border-color:#8da7ed;background:#fff}.aa-table-wrap{padding:0 22px;overflow-x:auto}.aa-table{width:100%;border-collapse:separate;border-spacing:0;min-width:760px;color:#1d2637;font-size:13px}.aa-table thead th{background:#e9ebef;padding:13px 11px;font-weight:700;white-space:nowrap}.aa-table thead th:first-child{border-radius:8px 0 0 0}.aa-table thead th:last-child{border-radius:0 8px 0 0}.aa-table tbody td{padding:11px;border-bottom:1px solid #edf0f4;vertical-align:middle}.aa-table tbody tr:nth-child(even){background:#f7f8fa}.aa-table tbody tr:hover{background:#eef3ff}.aa-student{display:flex;align-items:center;gap:9px;white-space:nowrap;font-weight:600}.aa-avatar{display:inline-flex;align-items:center;justify-content:center;width:31px;height:31px;border-radius:50%;color:#fff;background:#4975d4;font-size:11px;font-weight:700;flex-shrink:0}.aa-status{display:inline-block;border-radius:30px;padding:5px 11px;font-size:11px;font-weight:700}.aa-status-present{background:#d8f5e5;color:#13794c}.aa-status-absent{background:#ffe0e1;color:#b62831}.aa-empty{text-align:center;padding:28px!important;color:#748198}.aa-log-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:15px 23px 19px;color:#596579;font-size:12px}
@media(max-width:767px){#container-wrapper{padding:18px 12px 26px}.aa-header{flex-direction:column}.aa-title{font-size:25px}.aa-stat-card{min-height:190px}.aa-stat-icon{width:62px;height:62px;font-size:25px}.aa-stat-value{font-size:31px}.aa-log-header{padding:17px 15px}.aa-search-wrap,.aa-search{width:100%;min-width:0}.aa-table-wrap{padding:0 12px}.aa-log-footer{padding:14px}}
</style>
</head>
<body id="page-top"><div id="wrapper">
<?php include "Includes/sidebar.php"; ?>
<div id="content-wrapper" class="d-flex flex-column"><div id="content">
<?php include "Includes/topbar.php"; ?>
<div class="container-fluid" id="container-wrapper">
<div class="aa-header"><div><h1 class="aa-title">Attendance Dashboard</h1><p class="aa-subtitle">Advanced Attendance and QR Check-in System &bull; Admin Management Overview &bull; <?php echo date('l, M j, Y'); ?></p></div><div class="aa-header-actions"><span class="aa-notice" title="Notifications"><i class="far fa-bell"></i></span><a class="btn aa-export" href="index.php?export=csv"><i class="fas fa-download mr-2"></i>Export CSV</a></div></div>
<div class="row aa-cards">
<div class="col-xl-3 col-md-6 mb-4"><div class="card aa-stat-card h-100"><div class="card-body"><div class="aa-stat-icon aa-blue"><i class="far fa-user"></i></div><div class="aa-stat-label">Total Students</div><div class="aa-stat-value"><?php echo $totalStudents; ?></div><div class="aa-stat-note">Registered students</div></div></div></div>
<div class="col-xl-3 col-md-6 mb-4"><div class="card aa-stat-card h-100"><div class="card-body"><div class="aa-stat-icon aa-green"><i class="far fa-check-circle"></i></div><div class="aa-stat-label">Present Today</div><div class="aa-stat-value"><?php echo $presentToday; ?></div><div class="aa-stat-note">Records dated today</div></div></div></div>
<div class="col-xl-3 col-md-6 mb-4"><div class="card aa-stat-card h-100"><div class="card-body"><div class="aa-stat-icon aa-red"><i class="fas fa-exclamation-circle"></i></div><div class="aa-stat-label">Absent Today</div><div class="aa-stat-value"><?php echo $absentToday; ?></div><div class="aa-stat-note">Records dated today</div></div></div></div>
<div class="col-xl-3 col-md-6 mb-4"><div class="card aa-stat-card h-100"><div class="card-body"><div class="aa-stat-icon aa-orange"><i class="far fa-clock"></i></div><div class="aa-stat-label">Attendance Records</div><div class="aa-stat-value"><?php echo $totalAttendanceRecords; ?></div><div class="aa-stat-note">All recorded entries</div></div></div></div>
</div>
<section class="aa-log-card mb-4"><div class="aa-log-header"><div><h2 class="aa-log-title">QR &amp; GPS Attendance</h2><p class="aa-log-subtitle">Monitor faculty sessions and location-verified attendance.</p></div><div class="aa-header-actions"><a class="btn btn-outline-primary" href="qrAttendanceSettings.php"><i class="fas fa-map-marker-alt mr-1"></i>College location</a><a class="btn btn-primary" href="qrAttendanceReports.php"><i class="fas fa-chart-bar mr-1"></i>View QR reports</a></div></div><div class="row px-3 pb-3 text-center"><div class="col-md-3 mb-2"><strong><?php echo $qrSessionCount; ?></strong><div class="small text-muted">QR sessions</div></div><div class="col-md-3 mb-2"><strong><?php echo $qrActiveSessions; ?></strong><div class="small text-muted">Currently active</div></div><div class="col-md-3 mb-2"><strong><?php echo $qrGpsVerified; ?></strong><div class="small text-muted">Location verified check-ins</div></div><div class="col-md-3 mb-2"><span class="badge badge-<?php echo $qrLocationConfigured ? 'success' : 'warning'; ?>"><?php echo $qrLocationConfigured ? 'Configured' : 'Needs configuration'; ?></span><div class="small text-muted">College geofence</div></div></div></section>
<section class="aa-log-card"><div class="aa-log-header"><div><h2 class="aa-log-title">Attendance Log</h2><p class="aa-log-subtitle">Attendance records from your database</p></div><form class="aa-header-actions" method="get" action="index.php"><div class="aa-search-wrap"><i class="fas fa-search"></i><input type="search" name="search" class="aa-search" value="<?php echo aa_escape($searchTerm); ?>" placeholder="Search student or admission no..." aria-label="Search attendance records"></div><button class="btn btn-primary" type="submit">Search</button><?php if ($searchTerm !== ''): ?><a class="btn btn-outline-secondary" href="index.php">Clear</a><?php endif; ?></form></div>
<div class="aa-table-wrap"><table class="aa-table" id="attendanceTable"><thead><tr><th>Student Name</th><th>Admission No.</th><th>Class / Group</th><th>Date</th><th>Status</th></tr></thead><tbody>
<?php if (!$attendanceRows): ?><tr><td colspan="5" class="aa-empty"><?php echo $searchTerm !== '' ? 'No matching attendance records were found.' : 'No attendance records were found.'; ?></td></tr><?php else: foreach ($attendanceRows as $r):
$name=trim(($r['firstName']??'').' '.($r['lastName']??'')); if($name==='') $name='Student not found';
$initials=strtoupper(substr((string)($r['firstName']??'S'),0,1).substr((string)($r['lastName']??''),0,1)); $isAbsent=((string)$r['status']==='1'); ?>
<tr><td><div class="aa-student"><span class="aa-avatar"><?php echo aa_escape($initials); ?></span><span><?php echo aa_escape($name); ?></span></div></td><td><?php echo aa_escape($r['admissionNo']); ?></td><td><?php echo aa_escape($r['classId']).' / '.aa_escape($r['classArmId']); ?></td><td><?php echo aa_escape($r['dateTimeTaken']); ?></td><td><span class="aa-status <?php echo $isAbsent?'aa-status-absent':'aa-status-present'; ?>"><?php echo $isAbsent?'Absent':'Present'; ?></span></td></tr>
<?php endforeach; endif; ?></tbody></table></div><div class="aa-log-footer"><span>Showing <?php echo $firstRecord; ?>–<?php echo $lastRecord; ?> of <?php echo $totalMatchingRecords; ?> records</span><?php if ($totalPages > 1): ?><nav aria-label="Attendance log pages"><ul class="pagination pagination-sm mb-0">
<li class="page-item <?php echo $attendancePage === 1 ? 'disabled' : ''; ?>"><?php if ($attendancePage > 1): ?><a class="page-link" href="index.php?<?php echo aa_escape(http_build_query($pageQuery + ['page' => $attendancePage - 1])); ?>" aria-label="Previous page">&laquo;</a><?php else: ?><span class="page-link" aria-hidden="true">&laquo;</span><?php endif; ?></li>
<?php for ($pageNumber = $pageWindowStart; $pageNumber <= $pageWindowEnd; $pageNumber++): ?><li class="page-item <?php echo $pageNumber === $attendancePage ? 'active' : ''; ?>"><?php if ($pageNumber === $attendancePage): ?><span class="page-link" aria-current="page"><?php echo $pageNumber; ?></span><?php else: ?><a class="page-link" href="index.php?<?php echo aa_escape(http_build_query($pageQuery + ['page' => $pageNumber])); ?>"><?php echo $pageNumber; ?></a><?php endif; ?></li><?php endfor; ?>
<li class="page-item <?php echo $attendancePage === $totalPages ? 'disabled' : ''; ?>"><?php if ($attendancePage < $totalPages): ?><a class="page-link" href="index.php?<?php echo aa_escape(http_build_query($pageQuery + ['page' => $attendancePage + 1])); ?>" aria-label="Next page">&raquo;</a><?php else: ?><span class="page-link" aria-hidden="true">&raquo;</span><?php endif; ?></li>
</ul></nav><?php endif; ?></div></section>
</div></div><?php include 'Includes/footer.php'; ?></div></div>
<a class="scroll-to-top rounded" href="#page-top" aria-label="Scroll to top"><i class="fas fa-angle-up"></i></a>
<script src="../vendore/jquery/jquery.min.js"></script><script src="../vendore/bootstrap/js/bootstrap.bundle.min.js"></script><script src="../vendore/jquery-easing/jquery.easing.min.js"></script><script src="js/ruang-admin.min.js"></script>
</body></html>