<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$statusMsg = "";

// -----------------------------------------------------------------------------
// 1. Resolve Current Academic Session
// -----------------------------------------------------------------------------
$selectedSessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

// Fetch all available academic sessions
$sessionsQuery = $conn->query("
    SELECT st.Id, st.sessionName, st.termId, st.isActive, t.termName
    FROM tblsessionterm st
    LEFT JOIN tblterm t ON t.Id = st.termId
    ORDER BY st.isActive DESC, st.Id DESC
");
$allSessions = [];
$activeSessionId = 0;
while ($sRow = $sessionsQuery->fetch_assoc()) {
    $allSessions[] = $sRow;
    if ($sRow['isActive'] == '1' && $activeSessionId === 0) {
        $activeSessionId = intval($sRow['Id']);
    }
}

// Fallback: default to active session, or first available session
if ($selectedSessionId === 0) {
    if ($activeSessionId > 0) {
        $selectedSessionId = $activeSessionId;
    } elseif (!empty($allSessions)) {
        $selectedSessionId = intval($allSessions[0]['Id']);
    }
}

// Get details of selected session
$currentSession = null;
foreach ($allSessions as $s) {
    if (intval($s['Id']) === $selectedSessionId) {
        $currentSession = $s;
        break;
    }
}

// -----------------------------------------------------------------------------
// 2. Handle Delete Timetable Entry
// -----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = intval($_GET['id']);
    $delStmt = $conn->prepare("DELETE FROM session_timetable WHERE id = ? AND session_id = ?");
    $delStmt->bind_param("ii", $deleteId, $selectedSessionId);
    if ($delStmt->execute()) {
        $delStmt->close();
        header("Location: createTimetable.php?session_id=" . $selectedSessionId . "&status=delete-success");
        exit();
    } else {
        $statusMsg = "<div class='alert alert-danger'>Failed to delete timetable entry.</div>";
        $delStmt->close();
    }
}

// -----------------------------------------------------------------------------
// 3. Handle Create / Update Entry
// -----------------------------------------------------------------------------
if (isset($_POST['save_entry']) || isset($_POST['update_entry'])) {
    $isUpdate = isset($_POST['update_entry']);
    $entryId = $isUpdate ? intval($_POST['entry_id'] ?? 0) : 0;
    $postSessionId = intval($_POST['session_id'] ?? $selectedSessionId);
    $classId = intval($_POST['classId'] ?? 0);
    $classArmId = intval($_POST['classArmId'] ?? 0);
    $teacherId = intval($_POST['teacherId'] ?? 0);
    $subjectId = intval($_POST['subjectId'] ?? 0);
    $dayName = trim($_POST['day_name'] ?? '');
    $startTime = trim($_POST['startTime'] ?? '');
    $endTime = trim($_POST['endTime'] ?? '');
    $slotType = trim($_POST['slot_type'] ?? 'Lecture');

    // Validation checks
    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    if ($postSessionId <= 0 || $classId <= 0 || $classArmId <= 0 || $teacherId <= 0 || $subjectId <= 0 || $dayName === '' || $startTime === '' || $endTime === '') {
        $statusMsg = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle mr-2'></i>Please complete all required fields.</div>";
    } elseif (!in_array($dayName, $validDays, true)) {
        $statusMsg = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle mr-2'></i>Invalid weekday selected.</div>";
    } elseif ($startTime >= $endTime) {
        $statusMsg = "<div class='alert alert-danger'><i class='fas fa-clock mr-2'></i>Invalid Schedule Times: End time (" . htmlspecialchars($endTime) . ") must be later than start time (" . htmlspecialchars($startTime) . ").</div>";
    } else {
        $associationStmt = $conn->prepare("
            SELECT
                EXISTS(SELECT 1 FROM tblclassarms WHERE Id = ? AND classId = ?) AS valid_arm,
                EXISTS(
                    SELECT 1
                    FROM tblclassteacher ct
                    LEFT JOIN tblclassteacher_classarms rel ON rel.classteacher_Id = ct.Id
                    LEFT JOIN tblclassarms ca ON ca.Id = rel.classarm_Id
                    WHERE ct.Id = ?
                      AND (
                          ca.classId = ?
                          OR (
                              ct.classId = ?
                              AND NOT EXISTS (
                                  SELECT 1 FROM tblclassteacher_classarms assigned
                                  WHERE assigned.classteacher_Id = ct.Id
                              )
                          )
                      )
                ) AS valid_teacher,
                EXISTS(
                    SELECT 1
                    FROM tblclassteacher ct
                    LEFT JOIN tblclassteacher_classarms rel ON rel.classteacher_Id = ct.Id
                    LEFT JOIN tblclassarms ca ON ca.Id = rel.classarm_Id
                    WHERE ct.courseId = ?
                      AND (
                          ca.classId = ?
                          OR (
                              ct.classId = ?
                              AND NOT EXISTS (
                                  SELECT 1 FROM tblclassteacher_classarms assigned
                                  WHERE assigned.classteacher_Id = ct.Id
                              )
                          )
                      )
                ) AS valid_subject
        ");
        $associationStmt->bind_param(
            "iiiiiiii",
            $classArmId,
            $classId,
            $teacherId,
            $classId,
            $classId,
            $subjectId,
            $classId,
            $classId
        );
        $associationStmt->execute();
        $associations = $associationStmt->get_result()->fetch_assoc();
        $associationStmt->close();

        if (!$associations || !$associations['valid_arm'] || !$associations['valid_teacher'] || !$associations['valid_subject']) {
            $statusMsg = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle mr-2'></i>The selected class group, teacher, or subject is not associated with this class. Please review your selections.</div>";
        } else {
        // Teacher scheduling conflict check:
        $tCheck = $conn->prepare("
            SELECT st.id, st.start_time, st.end_time, c.courseName, ca.classArmName, cl.className, ct.firstName, ct.lastName
            FROM session_timetable st
            LEFT JOIN course c ON c.courseId = st.subject_id
            LEFT JOIN tblclassarms ca ON ca.Id = st.class_arm_id
            LEFT JOIN tblclass cl ON cl.Id = st.class_id
            LEFT JOIN tblclassteacher ct ON ct.Id = st.teacher_id
            WHERE st.session_id = ?
              AND st.day_name = ?
              AND st.teacher_id = ?
              AND NOT (st.end_time <= ? OR st.start_time >= ?)
              AND st.id != ?
            LIMIT 1
        ");
        $tCheck->bind_param("issssi", $postSessionId, $dayName, $teacherId, $startTime, $endTime, $entryId);
        $tCheck->execute();
        $tConflict = $tCheck->get_result()->fetch_assoc();
        $tCheck->close();

        if ($tConflict) {
            $tName = trim(($tConflict['firstName'] ?? '') . ' ' . ($tConflict['lastName'] ?? 'Teacher'));
            $cName = $tConflict['courseName'] ?? 'Subject';
            $grpName = ($tConflict['className'] ?? '') . ' - ' . ($tConflict['classArmName'] ?? '');
            $statusMsg = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle mr-2'></i><strong>Teacher Scheduling Conflict:</strong> {$tName} is already assigned to {$cName} ({$grpName}) on {$dayName} from " . substr($tConflict['start_time'], 0, 5) . " to " . substr($tConflict['end_time'], 0, 5) . ".</div>";
        } else {
            // Class Arm / Section conflict check:
            $cCheck = $conn->prepare("
                SELECT st.id, st.start_time, st.end_time, c.courseName, ct.firstName, ct.lastName, ca.classArmName, cl.className
                FROM session_timetable st
                LEFT JOIN course c ON c.courseId = st.subject_id
                LEFT JOIN tblclassteacher ct ON ct.Id = st.teacher_id
                LEFT JOIN tblclassarms ca ON ca.Id = st.class_arm_id
                LEFT JOIN tblclass cl ON cl.Id = st.class_id
                WHERE st.session_id = ?
                  AND st.day_name = ?
                  AND st.class_arm_id = ?
                  AND NOT (st.end_time <= ? OR st.start_time >= ?)
                  AND st.id != ?
                LIMIT 1
            ");
            $cCheck->bind_param("issssi", $postSessionId, $dayName, $classArmId, $startTime, $endTime, $entryId);
            $cCheck->execute();
            $armConflict = $cCheck->get_result()->fetch_assoc();
            $cCheck->close();

            if ($armConflict) {
                $grpName = ($armConflict['className'] ?? '') . ' - ' . ($armConflict['classArmName'] ?? 'Section');
                $cName = $armConflict['courseName'] ?? 'Subject';
                $tName = trim(($armConflict['firstName'] ?? '') . ' ' . ($armConflict['lastName'] ?? 'Teacher'));
                $statusMsg = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle mr-2'></i><strong>Class Group Conflict:</strong> {$grpName} is already scheduled for {$cName} with {$tName} on {$dayName} from " . substr($armConflict['start_time'], 0, 5) . " to " . substr($armConflict['end_time'], 0, 5) . ".</div>";
            } else {
                if ($isUpdate) {
                    $upStmt = $conn->prepare("
                        UPDATE session_timetable
                        SET session_id = ?, class_id = ?, class_arm_id = ?, teacher_id = ?, subject_id = ?, day_name = ?, start_time = ?, end_time = ?, slot_type = ?
                        WHERE id = ?
                    ");
                    $upStmt->bind_param("iiiiissssi", $postSessionId, $classId, $classArmId, $teacherId, $subjectId, $dayName, $startTime, $endTime, $slotType, $entryId);
                    if ($upStmt->execute()) {
                        $statusMsg = "<div class='alert alert-success'><i class='fas fa-check-circle mr-2'></i>Timetable entry updated successfully!</div>";
                    } else {
                        $statusMsg = "<div class='alert alert-danger'>Failed to update timetable entry.</div>";
                    }
                    $upStmt->close();
                } else {
                    $insStmt = $conn->prepare("
                        INSERT INTO session_timetable (session_id, class_id, class_arm_id, teacher_id, subject_id, day_name, start_time, end_time, slot_type)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insStmt->bind_param("iiiiissss", $postSessionId, $classId, $classArmId, $teacherId, $subjectId, $dayName, $startTime, $endTime, $slotType);
                    if ($insStmt->execute()) {
                        $statusMsg = "<div class='alert alert-success'><i class='fas fa-check-circle mr-2'></i>Timetable entry added successfully!</div>";
                    } else {
                        $statusMsg = "<div class='alert alert-danger'>Failed to add timetable entry.</div>";
                    }
                    $insStmt->close();
                }
            }
        }
        }
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'delete-success') {
    $statusMsg = "<div class='alert alert-success'><i class='fas fa-check-circle mr-2'></i>Timetable entry deleted successfully!</div>";
}

// -----------------------------------------------------------------------------
// 4. Edit Entry Population
// -----------------------------------------------------------------------------
$editingEntry = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = intval($_GET['id']);
    $eStmt = $conn->prepare("SELECT * FROM session_timetable WHERE id = ? AND session_id = ?");
    $eStmt->bind_param("ii", $editId, $selectedSessionId);
    $eStmt->execute();
    $editingEntry = $eStmt->get_result()->fetch_assoc();
    $eStmt->close();
}

$formClassId = isset($_POST['classId']) ? intval($_POST['classId']) : intval($editingEntry['class_id'] ?? 0);
$formClassArmId = isset($_POST['classArmId']) ? intval($_POST['classArmId']) : intval($editingEntry['class_arm_id'] ?? 0);
$formTeacherId = isset($_POST['teacherId']) ? intval($_POST['teacherId']) : intval($editingEntry['teacher_id'] ?? 0);
$formSubjectId = isset($_POST['subjectId']) ? intval($_POST['subjectId']) : intval($editingEntry['subject_id'] ?? 0);

// -----------------------------------------------------------------------------
// 5. Query Filtered Timetable Entries for Selected Session
// -----------------------------------------------------------------------------
$filterClassId = isset($_GET['filter_class']) ? intval($_GET['filter_class']) : 0;
$filterArmId = isset($_GET['filter_arm']) ? intval($_GET['filter_arm']) : 0;
$filterTeacherId = isset($_GET['filter_teacher']) ? intval($_GET['filter_teacher']) : 0;
$filterSubjectId = isset($_GET['filter_subject']) ? intval($_GET['filter_subject']) : 0;
$filterDay = isset($_GET['filter_day']) ? trim($_GET['filter_day']) : '';

$whereClauses = ["st.session_id = ?"];
$params = [$selectedSessionId];
$types = "i";

if ($filterClassId > 0) {
    $whereClauses[] = "st.class_id = ?";
    $params[] = $filterClassId;
    $types .= "i";
}
if ($filterArmId > 0) {
    $whereClauses[] = "st.class_arm_id = ?";
    $params[] = $filterArmId;
    $types .= "i";
}
if ($filterTeacherId > 0) {
    $whereClauses[] = "st.teacher_id = ?";
    $params[] = $filterTeacherId;
    $types .= "i";
}
if ($filterSubjectId > 0) {
    $whereClauses[] = "st.subject_id = ?";
    $params[] = $filterSubjectId;
    $types .= "i";
}
if ($filterDay !== '') {
    $whereClauses[] = "st.day_name = ?";
    $params[] = $filterDay;
    $types .= "s";
}

$whereSql = implode(" AND ", $whereClauses);

$listQuery = "
    SELECT st.*,
           c.courseName,
           ct.firstName AS teacherFirstName, ct.lastName AS teacherLastName,
           cl.className,
           ca.classArmName
    FROM session_timetable st
    LEFT JOIN course c ON c.courseId = st.subject_id
    LEFT JOIN tblclassteacher ct ON ct.Id = st.teacher_id
    LEFT JOIN tblclass cl ON cl.Id = st.class_id
    LEFT JOIN tblclassarms ca ON ca.Id = st.class_arm_id
    WHERE {$whereSql}
    ORDER BY FIELD(st.day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), st.start_time ASC
";

$listStmt = $conn->prepare($listQuery);
if ($listStmt) {
    $listStmt->bind_param($types, ...$params);
    $listStmt->execute();
    $listRes = $listStmt->get_result();
    $entriesByDay = [
        'Monday' => [],
        'Tuesday' => [],
        'Wednesday' => [],
        'Thursday' => [],
        'Friday' => [],
        'Saturday' => []
    ];
    $totalEntries = 0;
    while ($row = $listRes->fetch_assoc()) {
        $d = $row['day_name'];
        if (isset($entriesByDay[$d])) {
            $entriesByDay[$d][] = $row;
        } else {
            $entriesByDay[$d] = [$row];
        }
        $totalEntries++;
    }
    $listStmt->close();
}

$currentDayName = date('l');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <?php include 'Includes/title.php'; ?>
    <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <link href="css/admin-compact.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include "Includes/sidebar.php"; ?>
        <!-- Sidebar -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- TopBar -->
                <?php include "Includes/topbar.php"; ?>
                <!-- Topbar -->

                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">
                    <!-- Page Header & Session Selector Toolbar -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800">Session Timetable Management</h1>
                            <p class="text-muted small mb-0">Organize and manage academic schedules by academic session.</p>
                        </div>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item"><a href="createSessionTerm.php">Sessions</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Timetable</li>
                        </ol>
                    </div>

                    <?php if (!empty($statusMsg)) echo $statusMsg; ?>

                    <!-- Session Selector Card -->
                    <div class="card mb-3 border-left-primary">
                        <div class="card-body py-2 px-3">
                            <form method="get" action="createTimetable.php" class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <label class="mb-0 font-weight-bold text-dark mr-2"><i class="fas fa-calendar-alt text-primary mr-1"></i> Academic Session:</label>
                                    <select name="session_id" class="form-control form-control-sm" style="min-width: 250px; display: inline-block; width: auto;" onchange="this.form.submit()">
                                        <?php if (empty($allSessions)): ?>
                                            <option value="">No sessions available</option>
                                        <?php else: ?>
                                            <?php foreach ($allSessions as $s): ?>
                                                <option value="<?php echo htmlspecialchars($s['Id']); ?>" <?php echo (intval($s['Id']) === $selectedSessionId) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($s['sessionName'] . ' (' . ($s['termName'] ?: 'Term ' . $s['termId']) . ')'); ?>
                                                    <?php echo ($s['isActive'] == '1') ? ' [ACTIVE]' : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <noscript><button type="submit" class="btn btn-sm btn-primary ml-1">Switch</button></noscript>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($currentSession && $currentSession['isActive'] == '1'): ?>
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Active Session</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary px-2 py-1">Archived / Inactive Session</span>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-primary ml-2" data-toggle="collapse" data-target="#collapseEntryForm" aria-expanded="<?php echo $editingEntry ? 'true' : 'false'; ?>">
                                        <i class="fas fa-<?php echo $editingEntry ? 'edit' : 'plus'; ?> mr-1"></i>
                                        <?php echo $editingEntry ? 'Editing Entry #' . $editingEntry['id'] : 'Add Timetable Entry'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Collapsible Add / Edit Entry Form Card -->
                    <div class="collapse <?php echo $editingEntry ? 'show' : ''; ?>" id="collapseEntryForm">
                        <div class="card mb-4 shadow-sm border-primary">
                            <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between bg-primary text-white">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i class="fas fa-<?php echo $editingEntry ? 'edit' : 'calendar-plus'; ?> mr-1"></i>
                                    <?php echo $editingEntry ? 'Edit Timetable Schedule Entry' : 'Add New Timetable Entry'; ?>
                                </h6>
                                <button type="button" class="close text-white" data-toggle="collapse" data-target="#collapseEntryForm" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <form method="post" action="createTimetable.php?session_id=<?php echo htmlspecialchars($selectedSessionId); ?>">
                                    <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($selectedSessionId); ?>">
                                    <?php if ($editingEntry): ?>
                                        <input type="hidden" name="entry_id" value="<?php echo htmlspecialchars($editingEntry['id']); ?>">
                                    <?php endif; ?>

                                    <div class="form-group row mb-2">
                                        <div class="col-md-3">
                                            <label class="form-control-label">Class<span class="text-danger ml-1">*</span></label>
                                            <select name="classId" id="classSelect" class="form-control" required>
                                                <option value="">-- Select Class --</option>
                                                <?php
                                                $clQ = $conn->query("SELECT * FROM tblclass ORDER BY className ASC");
                                                while ($clRow = $clQ->fetch_assoc()) {
                                                    $sel = ($clRow['Id'] == $formClassId) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($clRow['Id']) . '" ' . $sel . '>' . htmlspecialchars($clRow['className']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-control-label">Class Group (Arm)<span class="text-danger ml-1">*</span></label>
                                            <select name="classArmId" id="classArmSelect" class="form-control" required data-selected="<?php echo htmlspecialchars($formClassArmId ?: ''); ?>" disabled>
                                                <option value="">-- Select Group --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-control-label">Subject (Course)<span class="text-danger ml-1">*</span></label>
                                            <select name="subjectId" id="subjectSelect" class="form-control" required data-selected="<?php echo htmlspecialchars($formSubjectId ?: ''); ?>" disabled>
                                                <option value="">-- Select Subject --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-control-label">Teacher<span class="text-danger ml-1">*</span></label>
                                            <select name="teacherId" id="teacherSelect" class="form-control" required data-selected="<?php echo htmlspecialchars($formTeacherId ?: ''); ?>" disabled>
                                                <option value="">-- Select Teacher --</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-3">
                                        <div class="col-md-3">
                                            <label class="form-control-label">Weekday<span class="text-danger ml-1">*</span></label>
                                            <select name="day_name" class="form-control" required>
                                                <option value="">-- Select Day --</option>
                                                <?php
                                                $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                                $assignedDay = $editingEntry ? $editingEntry['day_name'] : '';
                                                foreach ($weekdays as $d) {
                                                    $sel = ($d === $assignedDay) ? 'selected' : '';
                                                    echo "<option value='{$d}' {$sel}>{$d}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-control-label">Start Time<span class="text-danger ml-1">*</span></label>
                                            <input type="time" name="startTime" class="form-control" required value="<?php echo $editingEntry ? htmlspecialchars(substr($editingEntry['start_time'], 0, 5)) : '09:00'; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-control-label">End Time<span class="text-danger ml-1">*</span></label>
                                            <input type="time" name="endTime" class="form-control" required value="<?php echo $editingEntry ? htmlspecialchars(substr($editingEntry['end_time'], 0, 5)) : '10:30'; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-control-label">Slot Type<span class="text-danger ml-1">*</span></label>
                                            <select name="slot_type" class="form-control" required>
                                                <?php
                                                $slotTypes = ['Lecture', 'Tutorial', 'Lab', 'Course', 'TD', 'TP', 'Seminar'];
                                                $assignedSlot = $editingEntry ? $editingEntry['slot_type'] : 'Lecture';
                                                foreach ($slotTypes as $st) {
                                                    $sel = (strcasecmp($st, $assignedSlot) === 0) ? 'selected' : '';
                                                    echo "<option value='{$st}' {$sel}>{$st}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row mb-0">
                                        <div class="col-12 d-flex gap-2">
                                            <?php if ($editingEntry): ?>
                                                <button type="submit" name="update_entry" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update Schedule Entry</button>
                                                <a href="createTimetable.php?session_id=<?php echo htmlspecialchars($selectedSessionId); ?>" class="btn btn-outline-secondary ml-2">Cancel Edit</a>
                                            <?php else: ?>
                                                <button type="submit" name="save_entry" class="btn btn-primary"><i class="fas fa-check mr-1"></i> Add Entry to Timetable</button>
                                                <button type="reset" class="btn btn-outline-secondary ml-2">Reset</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Timetable Container & Weekly Schedule Grid -->
                    <div class="card mb-4">
                        <!-- Toolbar / Filter Bar -->
                        <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between flex-wrap gap-2">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-th-list mr-1"></i>
                                Weekly Schedule &bull; <?php echo $currentSession ? htmlspecialchars($currentSession['sessionName'] . ' (' . ($currentSession['termName'] ?: 'Term ' . $currentSession['termId']) . ')') : 'No Session Selected'; ?>
                                <span class="badge badge-light border ml-2"><?php echo $totalEntries; ?> Scheduled <?php echo $totalEntries === 1 ? 'Slot' : 'Slots'; ?></span>
                            </h6>

                            <!-- Filter Controls Form -->
                            <form method="get" action="createTimetable.php" class="form-inline mb-0">
                                <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($selectedSessionId); ?>">
                                
                                <select name="filter_day" class="form-control form-control-sm mr-1 mb-1" onchange="this.form.submit()">
                                    <option value="">All Days</option>
                                    <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $d): ?>
                                        <option value="<?php echo $d; ?>" <?php echo ($filterDay === $d) ? 'selected' : ''; ?>><?php echo $d; ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="filter_teacher" class="form-control form-control-sm mr-1 mb-1" onchange="this.form.submit()">
                                    <option value="">All Teachers</option>
                                    <?php
                                    $tFiltQ = $conn->query("SELECT Id, firstName, lastName FROM tblclassteacher ORDER BY firstName ASC");
                                    while ($tF = $tFiltQ->fetch_assoc()) {
                                        $sel = ($tF['Id'] == $filterTeacherId) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($tF['Id']) . '" ' . $sel . '>' . htmlspecialchars($tF['firstName'] . ' ' . $tF['lastName']) . '</option>';
                                    }
                                    ?>
                                </select>

                                <select name="filter_subject" class="form-control form-control-sm mr-1 mb-1" onchange="this.form.submit()">
                                    <option value="">All Subjects</option>
                                    <?php
                                    $sFiltQ = $conn->query("SELECT courseId, courseName FROM course ORDER BY courseName ASC");
                                    while ($sF = $sFiltQ->fetch_assoc()) {
                                        $sel = ($sF['courseId'] == $filterSubjectId) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($sF['courseId']) . '" ' . $sel . '>' . htmlspecialchars($sF['courseName']) . '</option>';
                                    }
                                    ?>
                                </select>

                                <?php if ($filterDay !== '' || $filterTeacherId > 0 || $filterSubjectId > 0): ?>
                                    <a href="createTimetable.php?session_id=<?php echo htmlspecialchars($selectedSessionId); ?>" class="btn btn-sm btn-outline-danger mb-1" title="Clear Filters">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <!-- Weekly Grid Schedule -->
                        <div class="timetable-grid">
                            <?php foreach ($entriesByDay as $dayName => $dayEntries): ?>
                                <?php
                                if ($filterDay !== '' && $filterDay !== $dayName) {
                                    continue;
                                }
                                $isToday = (strcasecmp($dayName, $currentDayName) === 0);
                                ?>
                                <div class="timetable-day-col">
                                    <div class="timetable-day-header <?php echo $isToday ? 'is-today' : ''; ?>">
                                        <?php echo $dayName; ?>
                                        <?php if ($isToday): ?><span class="badge badge-primary ml-1" style="font-size:0.65rem;">TODAY</span><?php endif; ?>
                                        <div class="small font-weight-normal text-muted"><?php echo count($dayEntries); ?> <?php echo count($dayEntries) === 1 ? 'class' : 'classes'; ?></div>
                                    </div>
                                    <div class="timetable-day-slots">
                                        <?php if (empty($dayEntries)): ?>
                                            <div class="text-center py-4 text-muted small font-italic">
                                                No classes scheduled
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($dayEntries as $entry): ?>
                                                <?php
                                                $stBadgeClass = 'slot-badge-lecture';
                                                $sType = strtolower($entry['slot_type']);
                                                if (strpos($sType, 'lab') !== false || strpos($sType, 'tp') !== false) {
                                                    $stBadgeClass = 'slot-badge-lab';
                                                } elseif (strpos($sType, 'tut') !== false || strpos($sType, 'td') !== false) {
                                                    $stBadgeClass = 'slot-badge-tutorial';
                                                } elseif (strpos($sType, 'course') !== false) {
                                                    $stBadgeClass = 'slot-badge-course';
                                                }
                                                ?>
                                                <div class="timetable-slot-card">
                                                    <div class="slot-time">
                                                        <span><i class="far fa-clock mr-1"></i> <?php echo substr($entry['start_time'], 0, 5) . ' – ' . substr($entry['end_time'], 0, 5); ?></span>
                                                        <span class="slot-badge <?php echo $stBadgeClass; ?>"><?php echo htmlspecialchars($entry['slot_type']); ?></span>
                                                    </div>
                                                    <div class="slot-subject"><?php echo htmlspecialchars($entry['courseName'] ?: 'Course #' . $entry['subject_id']); ?></div>
                                                    <div class="slot-meta">
                                                        <div><i class="fas fa-user-tie mr-1 text-muted"></i> <?php echo htmlspecialchars(trim(($entry['teacherFirstName'] ?? '') . ' ' . ($entry['teacherLastName'] ?? ''))); ?></div>
                                                        <div><i class="fas fa-users mr-1 text-muted"></i> <?php echo htmlspecialchars($entry['className'] . ' / ' . $entry['classArmName']); ?></div>
                                                    </div>
                                                    <div class="slot-actions">
                                                        <a href="createTimetable.php?session_id=<?php echo htmlspecialchars($selectedSessionId); ?>&action=edit&id=<?php echo htmlspecialchars($entry['id']); ?>" class="btn btn-xs btn-outline-primary py-0 px-1" title="Edit" style="font-size: 0.7rem;">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <a href="createTimetable.php?session_id=<?php echo htmlspecialchars($selectedSessionId); ?>&action=delete&id=<?php echo htmlspecialchars($entry['id']); ?>" class="btn btn-xs btn-outline-danger py-0 px-1" title="Delete" style="font-size: 0.7rem;" onclick="return confirm('Delete this schedule entry?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalEntries === 0): ?>
                            <div class="timetable-empty-state">
                                <i class="far fa-calendar-times"></i>
                                <h5>No Timetable Entries Found</h5>
                                <p class="text-muted small mb-3">There are no schedule entries for this academic session yet.</p>
                                <button type="button" class="btn btn-sm btn-primary" data-toggle="collapse" data-target="#collapseEntryForm">
                                    <i class="fas fa-plus mr-1"></i> Create First Schedule Entry
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <?php include "Includes/footer.php"; ?>
        </div>
    </div>

    <!-- Scroll to top -->
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <script src="../vendore/jquery/jquery.min.js"></script>
    <script src="../vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendore/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>

    <script>
        (function() {
            var classSelect = document.getElementById("classSelect");
            var dependentSelects = {
                arms: document.getElementById("classArmSelect"),
                teachers: document.getElementById("teacherSelect"),
                subjects: document.getElementById("subjectSelect")
            };
            var optionsRequestNumber = 0;

            function resetSelect(select, placeholder) {
                select.innerHTML = "";
                var option = document.createElement("option");
                option.value = "";
                option.textContent = placeholder;
                select.appendChild(option);
                select.value = "";
                select.disabled = true;
            }

            function populateSelect(select, placeholder, items, selectedValue) {
                resetSelect(select, placeholder);
                items.forEach(function(item) {
                    var option = document.createElement("option");
                    option.value = item.id;
                    option.textContent = item.name;
                    select.appendChild(option);
                });
                select.disabled = items.length === 0;
                if (selectedValue) {
                    select.value = selectedValue;
                    if (select.value !== selectedValue) {
                        select.value = "";
                    }
                }
            }

            function loadClassOptions(classId, restoreSelections) {
                var requestNumber = ++optionsRequestNumber;
                var selectedValues = restoreSelections ? {
                    arms: dependentSelects.arms.getAttribute("data-selected"),
                    teachers: dependentSelects.teachers.getAttribute("data-selected"),
                    subjects: dependentSelects.subjects.getAttribute("data-selected")
                } : { arms: "", teachers: "", subjects: "" };

                populateSelect(dependentSelects.arms, "-- Select Group --", [], "");
                populateSelect(dependentSelects.teachers, "-- Select Teacher --", [], "");
                populateSelect(dependentSelects.subjects, "-- Select Subject --", [], "");
                if (!classId) {
                    return;
                }

                populateSelect(dependentSelects.arms, "Loading groups...", [], "");
                populateSelect(dependentSelects.teachers, "Loading teachers...", [], "");
                populateSelect(dependentSelects.subjects, "Loading subjects...", [], "");
                fetch("ajaxTimetableOptions.php?class_id=" + encodeURIComponent(classId), {
                    headers: { "Accept": "application/json" }
                })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error("Unable to load class options.");
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        if (requestNumber !== optionsRequestNumber || classSelect.value !== classId) {
                            return;
                        }
                        populateSelect(dependentSelects.arms, "-- Select Group --", data.arms, selectedValues.arms);
                        populateSelect(dependentSelects.teachers, "-- Select Teacher --", data.teachers, selectedValues.teachers);
                        populateSelect(dependentSelects.subjects, "-- Select Subject --", data.subjects, selectedValues.subjects);
                    })
                    .catch(function() {
                        if (requestNumber !== optionsRequestNumber || classSelect.value !== classId) {
                            return;
                        }
                        populateSelect(dependentSelects.arms, "Groups unavailable", [], "");
                        populateSelect(dependentSelects.teachers, "Teachers unavailable", [], "");
                        populateSelect(dependentSelects.subjects, "Subjects unavailable", [], "");
                    });
            }

            classSelect.addEventListener("change", function() {
                dependentSelects.arms.setAttribute("data-selected", "");
                dependentSelects.teachers.setAttribute("data-selected", "");
                dependentSelects.subjects.setAttribute("data-selected", "");
                loadClassOptions(classSelect.value, false);
            });

            if (classSelect.value) {
                loadClassOptions(classSelect.value, true);
            }
        })();
    </script>
</body>
</html>
