
<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['userId'])) {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['userId'];

$query = "SELECT s.*, c.className, ca.classArmName
          FROM tblstudents s
          LEFT JOIN tblclass c ON c.Id = s.classId
          LEFT JOIN tblclassarms ca ON ca.Id = s.classArmId
          WHERE s.Id = ? LIMIT 1";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Database query failed.");
}

$stmt->bind_param("s", $userId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student record not found. Please log in again.");
}

$fullName = trim($student['firstName'] . ' ' . $student['lastName']);
$admissionNumber = $student['admissionNumber'];
$className = $student['className'] ?? 'N/A';
$classArmName = $student['classArmName'] ?? 'N/A';

$totalSessions = 0;
$presentCount = 0;
$absentCount = 0;
$attendancePercent = 0;

/*
 * Calculate attendance for the current month.
 * Existing project convention: status 0 = present, status 1 = absent.
 */
$attendanceQuery = "
    SELECT
        COUNT(*) AS total_sessions,
        COALESCE(SUM(CASE WHEN status = '0' THEN 1 ELSE 0 END), 0) AS presents,
        COALESCE(SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END), 0) AS absences
    FROM tblattendance
    WHERE admissionNo = ?
      AND dateTimeTaken >= ?
      AND dateTimeTaken < ?
";

$monthStart = date('Y-m-01');
$nextMonth = date('Y-m-01', strtotime('first day of next month'));

$attendanceStmt = $conn->prepare($attendanceQuery);
if ($attendanceStmt) {
    $attendanceStmt->bind_param(
        "sss",
        $admissionNumber,
        $monthStart,
        $nextMonth
    );
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();
    $attendance = $attendanceResult->fetch_assoc();

    if ($attendance) {
        $totalSessions = (int)$attendance['total_sessions'];
        $presentCount = (int)$attendance['presents'];
        $absentCount = (int)$attendance['absences'];
    }

    $attendanceStmt->close();
}

if ($totalSessions > 0) {
    $attendancePercent = (int)round(
        ($presentCount / $totalSessions) * 100
    );
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard | AttendPro</title>

    <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ruang-admin.min.css" rel="stylesheet">

    <style>
        :root {
            --navy: #101a30;
            --blue: #2864dc;
            --page: #f3f5fa;
            --muted: #6b7890;
        }

        body {
            background: var(--page);
            color: #17233c;
            font-family: Arial, sans-serif;
        }

        #wrapper { min-height: 100vh; }

        #content-wrapper { background: var(--page); }

        #accordionSidebar {
            background: var(--navy) !important;
            min-height: 100vh;
        }

        #accordionSidebar .sidebar-brand {
            background: var(--navy) !important;
            min-height: 82px;
        }

        #accordionSidebar .sidebar-brand-text,
        #accordionSidebar .sidebar-heading,
        #accordionSidebar .nav-link,
        #accordionSidebar .nav-link i {
            color: #e4eaf7 !important;
        }

        #accordionSidebar .sidebar-heading {
            padding-top: 18px;
        }

        #accordionSidebar .nav-item.active .nav-link,
        #accordionSidebar .nav-link:hover {
            background: var(--blue) !important;
            border-radius: 9px;
            color: #fff !important;
        }

        #accordionSidebar .collapse-inner {
            background: #fff;
        }

        #accordionSidebar .collapse-item {
            color: #26344d !important;
        }

        .dashboard-main { padding: 26px; }

        .welcome-title {
            font-size: clamp(25px, 3vw, 36px);
            font-weight: 800;
            margin-bottom: 5px;
        }

        .student-subtitle { color: var(--muted); }

        .dashboard-card {
            background: #fff;
            border: 1px solid #edf0f6;
            border-radius: 18px;
            padding: 24px;
            height: 100%;
            box-shadow: 0 8px 25px rgba(25, 43, 78, .06);
        }

        .card-heading {
            font-size: 19px;
            font-weight: 750;
            margin-bottom: 20px;
        }

        .stat-label { color: var(--muted); font-size: 14px; }

        .stat-number {
            font-size: 34px;
            line-height: 1.3;
            font-weight: 800;
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #edf3ff;
            color: var(--blue);
            font-size: 19px;
        }

        .progress {
            height: 10px;
            background: #e6e9f0;
            border-radius: 20px;
        }

        .progress-bar {
            background: var(--blue);
            border-radius: 20px;
        }

        .scanner-frame {
            min-height: 270px;
            border-radius: 18px;
            background: #101a30;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px;
        }

        .scanner-frame i {
            color: #6ea0ff;
            font-size: 95px;
            margin-bottom: 20px;
        }

        .scanner-note {
            font-size: 13px;
            color: #cbd5e7;
            margin-top: 12px;
        }

        .profile-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #edf0f6;
            padding: 12px 0;
        }

        .profile-row:last-child { border-bottom: 0; }

        .profile-label { color: var(--muted); }

        .dashboard-footer {
            color: var(--muted);
            text-align: center;
            padding: 22px 0 5px;
            font-size: 13px;
        }

        @media (max-width: 767px) {
            .dashboard-main { padding: 16px; }
            .dashboard-card { padding: 19px; }
            .stat-number { font-size: 29px; }
        }
    </style>
</head>

<body id="page-top">
<div id="wrapper">

    <?php include "Includes/sidebar.php"; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include "Includes/topbar.php"; ?>

            <main class="dashboard-main">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="welcome-title">
                            Hello, <?= e($student['firstName']) ?>!
                        </h1>
                        <div class="student-subtitle">
                            Student ID: <?= e($admissionNumber) ?>
                            &nbsp;·&nbsp; Class: <?= e($className) ?>
                            (<?= e($classArmName) ?>)
                        </div>
                    </div>
                    <div class="text-muted mt-3 mt-md-0">
                        <i class="far fa-calendar-alt mr-2"></i>
                        <?= date('l, F j, Y') ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-5 col-lg-6 mb-4">
                        <section class="dashboard-card">
                            <h2 class="card-heading">
                                <i class="fas fa-qrcode mr-2 text-primary"></i>
                                QR Check-in
                            </h2>

                            <div class="scanner-frame">
                                <i class="fas fa-qrcode"></i>
                                <h4>QR Scanner</h4>
                                <p class="mb-0">
                                    Scan your school's attendance QR code.
                                </p>
                                <div class="scanner-note">
                                    Scanner setup is not enabled yet.
                                </div>
                            </div>

                            <button type="button"
                                    class="btn btn-primary btn-block mt-3"
                                    disabled
                                    title="QR scanning will be enabled after scanner setup">
                                <i class="fas fa-camera mr-2"></i>
                                Scan QR — Coming Soon
                            </button>
                        </section>
                    </div>

                    <div class="col-xl-7 col-lg-6 mb-4">
                        <section class="dashboard-card">
                            <h2 class="card-heading">
                                <i class="fas fa-chart-line mr-2 text-primary"></i>
                                Attendance Overview
                            </h2>

                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <div class="stat-label">Present this month</div>
                                    <div class="stat-number">
                                        <?= $attendancePercent ?>%
                                    </div>
                                </div>
                                <div class="text-muted text-right">
                                    <?= $presentCount ?> present
                                    <br>
                                    out of <?= $totalSessions ?> recorded
                                </div>
                            </div>

                            <div class="progress my-3">
                                <div class="progress-bar"
                                     role="progressbar"
                                     style="width: <?= $attendancePercent ?>%;"
                                     aria-valuenow="<?= $attendancePercent ?>"
                                     aria-valuemin="0"
                                     aria-valuemax="100"></div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-sm-6 mb-3">
                                    <div class="p-3 rounded" style="background:#eef8f2">
                                        <div class="text-success">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Presents
                                        </div>
                                        <div class="stat-number text-success">
                                            <?= $presentCount ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <div class="p-3 rounded" style="background:#fff3ed">
                                        <div style="color:#c46b31">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            Absences
                                        </div>
                                        <div class="stat-number" style="color:#c46b31">
                                            <?= $absentCount ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <small class="text-muted">
                                Statistics are based on records for
                                <?= date('F Y') ?>.
                            </small>
                        </section>

                        <section class="dashboard-card mt-4">
                            <h2 class="card-heading">
                                <i class="fas fa-user-graduate mr-2 text-primary"></i>
                                Student Information
                            </h2>

                            <div class="profile-row">
                                <span class="profile-label">Full name</span>
                                <strong><?= e($fullName) ?></strong>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Student ID</span>
                                <strong><?= e($admissionNumber) ?></strong>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Class</span>
                                <strong><?= e($className) ?></strong>
                            </div>
                            <div class="profile-row">
                                <span class="profile-label">Class arm</span>
                                <strong><?= e($classArmName) ?></strong>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="dashboard-footer">
                    &copy; <?= date('Y') ?> AttendPro · Student Dashboard
                </div>
            </main>
        </div>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/ruang-admin.min.js"></script>
</body>
</html>
