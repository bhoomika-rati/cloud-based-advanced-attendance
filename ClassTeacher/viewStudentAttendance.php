<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';
date_default_timezone_set('Asia/Kolkata');

function attendanceViewEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function attendanceViewValidDate(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

$teacherId = filter_var($_SESSION['userId'] ?? null, FILTER_VALIDATE_INT);
if (!$teacherId) {
    http_response_code(403);
    exit('A valid faculty account is required to view student attendance.');
}

$teacherEmail = $_SESSION['emailAddress'] ?? '';
if (!is_string($teacherEmail) || $teacherEmail === '') {
    http_response_code(403);
    exit('Faculty access is required to view student attendance.');
}

$teacherCheck = $conn->prepare('SELECT Id FROM tblclassteacher WHERE Id = ? AND emailAddress = ?');
if (!$teacherCheck) {
    error_log('Faculty attendance view could not prepare faculty authorization lookup: ' . $conn->error);
    http_response_code(500);
    exit('Unable to verify faculty access.');
}
$teacherCheck->bind_param('is', $teacherId, $teacherEmail);
if (!$teacherCheck->execute()) {
    error_log('Faculty attendance view faculty authorization lookup failed: ' . $teacherCheck->error);
    http_response_code(500);
    exit('Unable to verify faculty access.');
}
$teacherResult = $teacherCheck->get_result();
$isFaculty = $teacherResult->num_rows === 1;
$teacherCheck->close();
if (!$isFaculty) {
    http_response_code(403);
    exit('Faculty access is required to view student attendance.');
}

$allowedClassArmIds = [];
$assignmentStmt = $conn->prepare(
    'SELECT DISTINCT ca.Id
     FROM tblclassteacher_classarms assigned
     INNER JOIN tblclassarms ca ON ca.Id = assigned.classarm_Id
     WHERE assigned.classteacher_Id = ?'
);
if (!$assignmentStmt) {
    error_log('Faculty attendance view could not prepare class assignment lookup: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load faculty class assignments.');
}
$assignmentStmt->bind_param('i', $teacherId);
if (!$assignmentStmt->execute()) {
    error_log('Faculty attendance view class assignment lookup failed: ' . $assignmentStmt->error);
    http_response_code(500);
    exit('Unable to load faculty class assignments.');
}
$assignmentResult = $assignmentStmt->get_result();
while ($assignment = $assignmentResult->fetch_assoc()) {
    $allowedClassArmIds[] = (int)$assignment['Id'];
}
$assignmentStmt->close();

if (!$allowedClassArmIds) {
    $fallbackStmt = $conn->prepare(
        'SELECT ct.classId, ct.classArmId, ca.Id AS validArmId
         FROM tblclassteacher ct
         INNER JOIN tblclassarms ca ON ca.Id = ct.classArmId AND ca.classId = ct.classId
         WHERE ct.Id = ?'
    );
    if (!$fallbackStmt) {
        error_log('Faculty attendance view could not prepare legacy class assignment lookup: ' . $conn->error);
        http_response_code(500);
        exit('Unable to load faculty class assignments.');
    }
    $fallbackStmt->bind_param('i', $teacherId);
    if (!$fallbackStmt->execute()) {
        error_log('Faculty attendance view legacy assignment lookup failed: ' . $fallbackStmt->error);
        http_response_code(500);
        exit('Unable to load faculty class assignments.');
    }
    $fallbackResult = $fallbackStmt->get_result();
    while ($assignment = $fallbackResult->fetch_assoc()) {
        $allowedClassArmIds[] = (int)$assignment['validArmId'];
    }
    $fallbackStmt->close();
}

$statusMsg = '';
$students = [];
$attendanceRows = [];
$selectedStudentId = isset($_GET['student_id']) ? filter_var($_GET['student_id'], FILTER_VALIDATE_INT) : 0;
$attendanceType = isset($_GET['attendance_type']) && is_string($_GET['attendance_type'])
    ? $_GET['attendance_type']
    : 'all';
$specificDate = isset($_GET['specific_date']) && is_string($_GET['specific_date'])
    ? trim($_GET['specific_date'])
    : '';
$fromDate = isset($_GET['from_date']) && is_string($_GET['from_date'])
    ? trim($_GET['from_date'])
    : '';
$toDate = isset($_GET['to_date']) && is_string($_GET['to_date'])
    ? trim($_GET['to_date'])
    : '';
$hasSearched = isset($_GET['view']);

if ($allowedClassArmIds) {
    $armPlaceholders = implode(',', array_fill(0, count($allowedClassArmIds), '?'));
    $studentSql = "
        SELECT student.Id, student.firstName, student.lastName, student.admissionNumber
        FROM tblstudents student
        INNER JOIN tblclassarms arm
            ON arm.Id = student.classArmId AND arm.classId = student.classId
        WHERE student.classArmId IN ({$armPlaceholders})
        ORDER BY student.firstName ASC, student.lastName ASC, student.admissionNumber ASC
    ";
    $studentStmt = $conn->prepare($studentSql);
    if (!$studentStmt) {
        error_log('Faculty attendance view could not prepare student list: ' . $conn->error);
        http_response_code(500);
        exit('Unable to load students assigned to your classes.');
    }
    $studentTypes = str_repeat('i', count($allowedClassArmIds));
    $studentStmt->bind_param($studentTypes, ...$allowedClassArmIds);
    if (!$studentStmt->execute()) {
        error_log('Faculty attendance view student list query failed: ' . $studentStmt->error);
        http_response_code(500);
        exit('Unable to load students assigned to your classes.');
    }
    $studentResult = $studentStmt->get_result();
    while ($student = $studentResult->fetch_assoc()) {
        $students[] = $student;
    }
    $studentStmt->close();
}

if ($hasSearched) {
    $validTypes = ['all', 'today', 'specific', 'range'];
    if (!in_array($attendanceType, $validTypes, true)) {
        $statusMsg = 'Select a valid attendance type.';
    } elseif (!$selectedStudentId || !array_filter($students, static function ($student) use ($selectedStudentId) {
        return (int)$student['Id'] === (int)$selectedStudentId;
    })) {
        $statusMsg = 'Select a student assigned to your classes.';
    } elseif ($attendanceType === 'specific' && !attendanceViewValidDate($specificDate)) {
        $statusMsg = 'Select a valid date to view attendance.';
    } elseif ($attendanceType === 'range'
        && (!attendanceViewValidDate($fromDate) || !attendanceViewValidDate($toDate) || $fromDate > $toDate)) {
        $statusMsg = 'Select a valid date range. The From Date must not be later than the To Date.';
    } else {
        $dateExpression = "COALESCE(DATE(qrRecord.marked_at), DATE(attendance.dateTimeTaken), qrSession.attendance_date)";
        $whereClauses = [
            'student.Id = ?',
            'attendance.classArmId IN (' . $armPlaceholders . ')'
        ];
        $queryParams = array_merge([(int)$selectedStudentId], $allowedClassArmIds);
        $queryTypes = 'i' . str_repeat('i', count($allowedClassArmIds));

        if ($attendanceType === 'today') {
            $today = date('Y-m-d');
            $whereClauses[] = "{$dateExpression} >= ? AND {$dateExpression} < DATE_ADD(?, INTERVAL 1 DAY)";
            $queryParams[] = $today;
            $queryParams[] = $today;
            $queryTypes .= 'ss';
        } elseif ($attendanceType === 'specific') {
            $whereClauses[] = "{$dateExpression} >= ? AND {$dateExpression} < DATE_ADD(?, INTERVAL 1 DAY)";
            $queryParams[] = $specificDate;
            $queryParams[] = $specificDate;
            $queryTypes .= 'ss';
        } elseif ($attendanceType === 'range') {
            $whereClauses[] = "{$dateExpression} >= ? AND {$dateExpression} < DATE_ADD(?, INTERVAL 1 DAY)";
            $queryParams[] = $fromDate;
            $queryParams[] = $toDate;
            $queryTypes .= 'ss';
        }

        $attendanceSql = "
            SELECT student.firstName, student.lastName, student.admissionNumber,
                   classInfo.className, arm.classArmName, academicSession.sessionName,
                   term.termName,
                   CASE
                       WHEN qrRecord.status = 'present' THEN 'Present'
                       WHEN qrRecord.status = 'absent' THEN 'Absent'
                       WHEN LOWER(attendance.status) IN ('1', 'absent') THEN 'Absent'
                       WHEN LOWER(attendance.status) IN ('0', 'present') THEN 'Present'
                       ELSE attendance.status
                   END AS attendance_status,
                   {$dateExpression} AS attendance_date,
                   qrRecord.latitude,
                   qrRecord.longitude,
                   qrRecord.location_verified,
                   CASE
                       WHEN qrRecord.status = 'present' AND qrRecord.marked_at IS NOT NULL
                           THEN TIME(qrRecord.marked_at)
                       WHEN LOWER(attendance.status) IN ('0', 'present')
                           AND CHAR_LENGTH(attendance.dateTimeTaken) > 10
                           THEN TIME(STR_TO_DATE(attendance.dateTimeTaken, '%Y-%m-%d %H:%i:%s'))
                       ELSE NULL
                   END AS check_in_time
            FROM tblattendance attendance
            INNER JOIN tblstudents student ON student.admissionNumber = attendance.admissionNo
            LEFT JOIN tblclass classInfo ON classInfo.Id = attendance.classId
            LEFT JOIN tblclassarms arm
                ON arm.Id = attendance.classArmId AND arm.classId = attendance.classId
            LEFT JOIN tblsessionterm academicSession ON academicSession.Id = attendance.sessionTermId
            LEFT JOIN tblterm term ON term.Id = academicSession.termId
            LEFT JOIN qr_attendance_sessions qrSession ON qrSession.id = attendance.qr_session_id
            LEFT JOIN qr_attendance_records qrRecord
                ON qrRecord.qr_session_id = attendance.qr_session_id
               AND qrRecord.student_id = student.Id
            WHERE " . implode(' AND ', $whereClauses) . "
            ORDER BY {$dateExpression} DESC, attendance.Id DESC
        ";
        $attendanceStmt = $conn->prepare($attendanceSql);
        if (!$attendanceStmt) {
            error_log('Faculty attendance view query could not be prepared: ' . $conn->error);
            http_response_code(500);
            $statusMsg = 'Unable to load attendance records right now.';
        } else {
            $attendanceStmt->bind_param($queryTypes, ...$queryParams);
            if (!$attendanceStmt->execute()) {
                error_log('Faculty attendance view query failed: ' . $attendanceStmt->error);
                http_response_code(500);
                $statusMsg = 'Unable to load attendance records right now.';
            } else {
                $attendanceResult = $attendanceStmt->get_result();
                while ($row = $attendanceResult->fetch_assoc()) {
                    $attendanceRows[] = $row;
                }
            }
            $attendanceStmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <link href="img/logo/attnlg.jpg" rel="icon">
  <title>Dashboard</title>
  <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">

</head>

<body id="page-top">
  <div id="wrapper">
    <!-- Sidebar -->
      <?php include "Includes/sidebar.php";?>
    <!-- Sidebar -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">
        <!-- TopBar -->
       <?php include "Includes/topbar.php";?>
        <!-- Topbar -->

        <!-- Container Fluid-->
        <div class="container-fluid" id="container-wrapper">
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">View Student Attendance</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">View Student Attendance</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">Search Student Attendance</h6>
                </div>
                <div class="card-body">
                  <?php if ($statusMsg !== ''): ?>
                    <div class="alert alert-warning" role="alert"><?php echo attendanceViewEscape($statusMsg); ?></div>
                  <?php endif; ?>
                  <?php if (!$allowedClassArmIds): ?>
                    <div class="alert alert-warning" role="alert">No class arms are assigned to your faculty account.</div>
                  <?php elseif (!$students): ?>
                    <div class="alert alert-info" role="alert">No students are currently assigned to your class arms.</div>
                  <?php endif; ?>
                  <form method="get" id="attendance-filter-form">
                    <input type="hidden" name="view" value="1">
                    <div class="form-row">
                      <div class="form-group col-lg-6">
                        <label for="student_id">Select Student <span class="text-danger">*</span></label>
                        <select required name="student_id" id="student_id" class="form-control">
                          <option value="">-- Select Student --</option>
                          <?php foreach ($students as $student): ?>
                            <option value="<?php echo (int)$student['Id']; ?>"
                                <?php echo (int)$selectedStudentId === (int)$student['Id'] ? 'selected' : ''; ?>>
                              <?php echo attendanceViewEscape($student['firstName'] . ' ' . $student['lastName'] . ' (' . $student['admissionNumber'] . ')'); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-group col-lg-6">
                        <label for="attendance_type">Attendance Type <span class="text-danger">*</span></label>
                        <select required name="attendance_type" id="attendance_type" class="form-control">
                          <option value="all" <?php echo $attendanceType === 'all' ? 'selected' : ''; ?>>All Attendance</option>
                          <option value="today" <?php echo $attendanceType === 'today' ? 'selected' : ''; ?>>Today's Attendance</option>
                          <option value="specific" <?php echo $attendanceType === 'specific' ? 'selected' : ''; ?>>Specific Date</option>
                          <option value="range" <?php echo $attendanceType === 'range' ? 'selected' : ''; ?>>Date Range</option>
                        </select>
                      </div>
                    </div>
                    <div class="form-row">
                      <div class="form-group col-md-6" id="specific-date-group">
                        <label for="specific_date">Date</label>
                        <input type="date" name="specific_date" id="specific_date" class="form-control"
                            value="<?php echo attendanceViewEscape($specificDate); ?>">
                      </div>
                      <div class="form-group col-md-6" id="from-date-group">
                        <label for="from_date">From Date</label>
                        <input type="date" name="from_date" id="from_date" class="form-control"
                            value="<?php echo attendanceViewEscape($fromDate); ?>">
                      </div>
                      <div class="form-group col-md-6" id="to-date-group">
                        <label for="to_date">To Date</label>
                        <input type="date" name="to_date" id="to_date" class="form-control"
                            value="<?php echo attendanceViewEscape($toDate); ?>">
                      </div>
                    </div>
                    <button type="submit" class="btn btn-primary" <?php echo !$students ? 'disabled' : ''; ?>>
                      <i class="fas fa-search mr-1"></i> View Attendance
                    </button>
                  </form>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-12">
                  <div class="card mb-4">
                    <div class="card-header py-3">
                      <h6 class="m-0 font-weight-bold text-primary">Student Attendance Records</h6>
                    </div>
                    <?php if ($hasSearched && $statusMsg === ''): ?>
                      <?php if ($attendanceRows): ?>
                        <div class="table-responsive p-3">
                          <table class="table table-bordered table-hover mb-0">
                            <thead class="thead-light">
                              <tr>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Admission Number</th>
                                <th>Class</th>
                                <th>Class Arm</th>
                                <th>Session</th>
                                <th>Term</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Location Verification</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($attendanceRows as $row): ?>
                                <?php
                                  $rowDate = $row['attendance_date'] ? date('d M Y', strtotime($row['attendance_date'])) : 'Not Recorded';
                                  $rowStartTime = $row['check_in_time'] ? date('h:i:s A', strtotime($row['check_in_time'])) : 'Not Recorded';
                                  $rowStatus = $row['attendance_status'] !== null && $row['attendance_status'] !== ''
                                      ? $row['attendance_status']
                                      : 'Not Recorded';
                                  $statusClass = strtolower($rowStatus) === 'present'
                                      ? 'badge-success'
                                      : (strtolower($rowStatus) === 'absent' ? 'badge-danger' : 'badge-secondary');
                                ?>
                                <tr>
                                  <td><?php echo attendanceViewEscape($row['firstName']); ?></td>
                                  <td><?php echo attendanceViewEscape($row['lastName']); ?></td>
                                  <td><?php echo attendanceViewEscape($row['admissionNumber']); ?></td>
                                  <td><?php echo attendanceViewEscape($row['className'] ?? 'Not Recorded'); ?></td>
                                  <td><?php echo attendanceViewEscape($row['classArmName'] ?? 'Not Recorded'); ?></td>
                                  <td><?php echo attendanceViewEscape($row['sessionName'] ?? 'Not Recorded'); ?></td>
                                  <td><?php echo attendanceViewEscape($row['termName'] ?? 'Not Recorded'); ?></td>
                                  <td><span class="badge <?php echo $statusClass; ?>"><?php echo attendanceViewEscape($rowStatus); ?></span></td>
                                  <td><?php echo attendanceViewEscape($rowDate); ?></td>
                                  <td><?php echo attendanceViewEscape($rowStartTime); ?></td>
                                  <td><?php echo $row['latitude'] !== null ? attendanceViewEscape($row['latitude']) : '—'; ?></td>
                                  <td><?php echo $row['longitude'] !== null ? attendanceViewEscape($row['longitude']) : '—'; ?></td>
                                  <td><?php echo $row['location_verified'] === null
                                      ? 'Not Recorded'
                                      : ((int)$row['location_verified'] === 1 ? 'Verified' : 'Not Verified'); ?></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      <?php else: ?>
                        <div class="card-body">
                          <div class="alert alert-info mb-0" role="status">No attendance records found for the selected filters.</div>
                        </div>
                      <?php endif; ?>
                    <?php else: ?>
                      <div class="card-body">
                        <p class="text-muted mb-0">Select a student and attendance period, then choose View Attendance.</p>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
          <!--Row-->

        </div>
        <!---Container Fluid-->
      </div>
      <!-- Footer -->
       <?php include "Includes/footer.php";?>
      <!-- Footer -->
    </div>
  </div>

  <!-- Scroll to top -->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <script src="../vendor/jquery/jquery.min.js"></script>
  <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
   <script>
     (function () {
       var typeSelect = document.getElementById('attendance_type');
       var specificGroup = document.getElementById('specific-date-group');
       var fromGroup = document.getElementById('from-date-group');
       var toGroup = document.getElementById('to-date-group');
       var specificDate = document.getElementById('specific_date');
       var fromDate = document.getElementById('from_date');
       var toDate = document.getElementById('to_date');

       function updateDateFields() {
         var type = typeSelect.value;
         var showSpecific = type === 'specific';
         var showRange = type === 'range';
         specificGroup.hidden = !showSpecific;
         fromGroup.hidden = !showRange;
         toGroup.hidden = !showRange;
         specificDate.required = showSpecific;
         fromDate.required = showRange;
         toDate.required = showRange;
       }

       typeSelect.addEventListener('change', updateDateFields);
       updateDateFields();
     }());
   </script>
</body>

</html>
