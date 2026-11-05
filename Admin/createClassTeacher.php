<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$statusMsg = "";
$firstName = "";
$lastName = "";
$emailAddress = "";
$phoneNo = "";
$courseId = 0;
$moduleId = 0;
$selectedClassArms = [];

//------------------------DELETE--------------------------------------------------

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['Id'])) {
    $teacherId = intval($_GET['Id']);

    $delStmt = $conn->prepare("DELETE FROM tblclassteacher WHERE Id = ?");
    $delStmt->bind_param("i", $teacherId);
    $delResult = $delStmt->execute();
    $delStmt->close();

    $delRel = $conn->prepare("DELETE FROM tblclassteacher_classarms WHERE classteacher_Id = ?");
    $delRel->bind_param("i", $teacherId);
    $delRel->execute();
    $delRel->close();

    if ($delResult) {
        header("Location: createClassTeacher.php?status=delete-success");
        exit();
    } else {
        $statusMsg = "<div class='alert alert-danger'>Failed to delete teacher!</div>";
    }
}

//------------------------SAVE--------------------------------------------------

if (isset($_POST['save'])) {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $emailAddress = trim($_POST['emailAddress'] ?? '');
    $phoneNo = trim($_POST['phoneNo'] ?? '');
    $password = $_POST['password'] ?? '';
    $dateCreated = date("Y-m-d");

    $classArmList = $_POST['selectionList'] ?? [];
    $courseId = intval($_POST['courseId'] ?? 0);
    $moduleId = intval($_POST['moduleId'] ?? 0);

    if ($firstName === '' || $lastName === '' || $emailAddress === '' || $password === '' || empty($classArmList)) {
        $statusMsg = "<div class='alert alert-danger'>Please fill all required fields, including password and class group.</div>";
    } elseif (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        $statusMsg = "<div class='alert alert-danger'>Please enter a valid email address.</div>";
    } else {
        $checkStmt = $conn->prepare("SELECT Id FROM tblclassteacher WHERE LOWER(TRIM(emailAddress)) = LOWER(TRIM(?))");
        $checkStmt->bind_param("s", $emailAddress);
        $checkStmt->execute();
        $ret = $checkStmt->get_result();

        if ($ret->num_rows > 0) {
            $statusMsg = "<div class='alert alert-danger'>This Email Address Already Exists!</div>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $firstClassArm = explode("-", $classArmList[0], 2);
            $classId = $firstClassArm[0];
            $classArmId = $firstClassArm[1] ?? '';

            $insertStmt = $conn->prepare("INSERT INTO tblclassteacher (firstName, lastName, emailAddress, password, phoneNo, classId, classArmId, dateCreated, courseId, moduleId) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("ssssssssii", $firstName, $lastName, $emailAddress, $hashedPassword, $phoneNo, $classId, $classArmId, $dateCreated, $courseId, $moduleId);
            $insertTeacherResult = $insertStmt->execute();

            if ($insertTeacherResult) {
                $teacherId = $conn->insert_id;

                foreach ($classArmList as $classArm) {
                    $classArmIds = explode("-", $classArm, 2);
                    if (count($classArmIds) === 2) {
                        $relArmId = intval($classArmIds[1]);
                        $relStmt = $conn->prepare("INSERT INTO tblclassteacher_classarms (classteacher_Id, classarm_Id) VALUES (?, ?)");
                        $relStmt->bind_param("ii", $teacherId, $relArmId);
                        $relStmt->execute();
                        $relStmt->close();
                    }
                }

                $statusMsg = "<div class='alert alert-success'>Class Teacher Created Successfully!</div>";
                $firstName = "";
                $lastName = "";
                $emailAddress = "";
                $phoneNo = "";
                $courseId = 0;
                $moduleId = 0;
            } else {
                $statusMsg = "<div class='alert alert-danger'>An error occurred while inserting class teacher!</div>";
            }
            $insertStmt->close();
        }
        $checkStmt->close();
    }
}

//------------------------EDIT / POPULATE---------------------------------------

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['Id'])) {
    $editTeacherId = intval($_GET['Id']);

    $fetchStmt = $conn->prepare("SELECT * FROM tblclassteacher WHERE Id = ?");
    $fetchStmt->bind_param("i", $editTeacherId);
    $fetchStmt->execute();
    $fetchResult = $fetchStmt->get_result();

    if ($fetchResult && $fetchResult->num_rows > 0) {
        $teacherData = $fetchResult->fetch_assoc();
        $teacherId = $teacherData['Id'];
        $firstName = $teacherData['firstName'];
        $lastName = $teacherData['lastName'];
        $emailAddress = $teacherData['emailAddress'];
        $phoneNo = $teacherData['phoneNo'];
        $courseId = $teacherData['courseId'];
        $moduleId = $teacherData['moduleId'];

        $selectedClassArms = [];
        $armStmt = $conn->prepare("SELECT CONCAT(tblclassarms.classId, '-', tblclassarms.Id) AS selectionValue, CONCAT(tblclass.className, ' - ', tblclassarms.classArmName) AS selectionLabel FROM tblclassteacher_classarms INNER JOIN tblclassarms ON tblclassarms.Id = tblclassteacher_classarms.classarm_Id INNER JOIN tblclass ON tblclass.Id = tblclassarms.classId WHERE tblclassteacher_classarms.classteacher_Id = ?");
        $armStmt->bind_param("i", $teacherId);
        $armStmt->execute();
        $armRes = $armStmt->get_result();
        while ($armRes && ($classArm = $armRes->fetch_assoc())) {
            $selectedClassArms[] = $classArm;
        }
        $armStmt->close();
    }
    $fetchStmt->close();
}

//------------------------UPDATE------------------------------------------------

if (isset($_POST['update'])) {
    $teacherId = intval($_POST['teacherId'] ?? 0);
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $emailAddress = trim($_POST['emailAddress'] ?? '');
    $phoneNo = trim($_POST['phoneNo'] ?? '');
    $courseId = intval($_POST['courseId'] ?? 0);
    $moduleId = intval($_POST['moduleId'] ?? 0);
    $classArmList = $_POST['selectionList'] ?? [];
    $newPassword = $_POST['password'] ?? '';

    if ($firstName === '' || $lastName === '' || $emailAddress === '' || empty($classArmList)) {
        $statusMsg = "<div class='alert alert-danger'>Select at least one class group and fill all required fields.</div>";
    } elseif (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        $statusMsg = "<div class='alert alert-danger'>Please enter a valid email address.</div>";
    } else {
        $dupStmt = $conn->prepare("SELECT Id FROM tblclassteacher WHERE LOWER(TRIM(emailAddress)) = LOWER(TRIM(?)) AND Id != ?");
        $dupStmt->bind_param("si", $emailAddress, $teacherId);
        $dupStmt->execute();
        if ($dupStmt->get_result()->num_rows > 0) {
            $statusMsg = "<div class='alert alert-danger'>This Email Address Already Exists!</div>";
        } else {
            $firstClassArm = explode("-", $classArmList[0], 2);
            $classId = $firstClassArm[0];
            $classArmId = $firstClassArm[1] ?? '';

            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $upStmt = $conn->prepare("UPDATE tblclassteacher SET firstName=?, lastName=?, emailAddress=?, phoneNo=?, courseId=?, moduleId=?, classId=?, classArmId=?, password=? WHERE Id=?");
                $upStmt->bind_param("ssssiissii", $firstName, $lastName, $emailAddress, $phoneNo, $courseId, $moduleId, $classId, $classArmId, $hashedPassword, $teacherId);
            } else {
                $upStmt = $conn->prepare("UPDATE tblclassteacher SET firstName=?, lastName=?, emailAddress=?, phoneNo=?, courseId=?, moduleId=?, classId=?, classArmId=? WHERE Id=?");
                $upStmt->bind_param("ssssiissi", $firstName, $lastName, $emailAddress, $phoneNo, $courseId, $moduleId, $classId, $classArmId, $teacherId);
            }
            $updateTeacherResult = $upStmt->execute();
            $upStmt->close();

            if ($updateTeacherResult) {
                $delRel = $conn->prepare("DELETE FROM tblclassteacher_classarms WHERE classteacher_Id = ?");
                $delRel->bind_param("i", $teacherId);
                $delRel->execute();
                $delRel->close();

                foreach ($classArmList as $classArm) {
                    $classArmIds = explode("-", $classArm, 2);
                    if (count($classArmIds) === 2) {
                        $relArmId = intval($classArmIds[1]);
                        $insRel = $conn->prepare("INSERT INTO tblclassteacher_classarms (classteacher_Id, classarm_Id) VALUES (?, ?)");
                        $insRel->bind_param("ii", $teacherId, $relArmId);
                        $insRel->execute();
                        $insRel->close();
                    }
                }

                $statusMsg = "<div class='alert alert-success'>Teacher Updated Successfully!</div>";
            } else {
                $statusMsg = "<div class='alert alert-danger'>An error occurred while updating teacher details.</div>";
            }
        }
        $dupStmt->close();
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'delete-success') {
    $statusMsg = "<div class='alert alert-success'>Teacher Deleted Successfully!</div>";
}
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-3">
                        <h1 class="h3 mb-0 text-gray-800"><?php echo isset($teacherId) ? 'Edit Class Teacher' : 'Create Class Teachers'; ?></h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item"><a href="createClassTeacher.php">Teachers</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo isset($teacherId) ? 'Edit' : 'Create'; ?></li>
                        </ol>
                    </div>

                    <?php if (!empty($statusMsg)) echo $statusMsg; ?>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Teacher Account Details</h6>
                                    <?php if (isset($teacherId)): ?>
                                        <a href="createClassTeacher.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-plus mr-1"></i> Add New Teacher</a>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <form method="post" onsubmit="return prepareClassGroupSelection();">
                                        <input type="hidden" name="teacherId" value="<?php echo isset($teacherId) ? htmlspecialchars($teacherId) : ''; ?>">

                                        <div class="form-group row mb-2">
                                            <div class="col-md-6">
                                                <label class="form-control-label">First Name<span class="text-danger ml-1">*</span></label>
                                                <input type="text" class="form-control" required name="firstName" id="exampleInputFirstName" value="<?php echo htmlspecialchars($firstName); ?>" placeholder="First Name">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-control-label">Last Name<span class="text-danger ml-1">*</span></label>
                                                <input type="text" class="form-control" required name="lastName" id="exampleInputLastName" value="<?php echo htmlspecialchars($lastName); ?>" placeholder="Last Name">
                                            </div>
                                        </div>

                                        <div class="form-group row mb-2">
                                            <div class="col-md-6">
                                                <label class="form-control-label">Email Address (Login Username)<span class="text-danger ml-1">*</span></label>
                                                <input type="email" class="form-control" required name="emailAddress" id="exampleInputEmail" value="<?php echo htmlspecialchars($emailAddress); ?>" placeholder="teacher@school.edu">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-control-label">Phone Number<span class="text-danger ml-1">*</span></label>
                                                <input type="text" class="form-control" required name="phoneNo" id="exampleInputPhone" value="<?php echo htmlspecialchars($phoneNo); ?>" placeholder="+1234567890">
                                            </div>
                                        </div>

                                        <div class="form-group row mb-2">
                                            <div class="col-md-6">
                                                <label class="form-control-label">
                                                    Password
                                                    <?php if (isset($teacherId)): ?>
                                                        <small class="text-muted font-italic ml-1">(Leave blank to keep current password)</small>
                                                    <?php else: ?>
                                                        <span class="text-danger ml-1">*</span>
                                                    <?php endif; ?>
                                                </label>
                                                <div class="password-field-group">
                                                    <input type="password" class="form-control" name="password" id="exampleInputPassword" <?php echo isset($teacherId) ? '' : 'required'; ?> placeholder="<?php echo isset($teacherId) ? 'Enter new password or leave blank' : 'Enter login password'; ?>">
                                                    <button type="button" class="password-toggle-btn" aria-label="Show password" title="Show password" tabindex="0">
                                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-control-label">Course<span class="text-danger ml-1">*</span></label>
                                                <select class="form-control" required name="courseId" id="exampleInputCourseId">
                                                    <option value="">-- Select Course --</option>
                                                    <?php
                                                    $course_query = mysqli_query($conn, "SELECT * FROM course ORDER BY courseName ASC");
                                                    while ($course_row = mysqli_fetch_assoc($course_query)) {
                                                        $selected = ($course_row['courseId'] == $courseId) ? 'selected' : '';
                                                        echo '<option value="' . htmlspecialchars($course_row['courseId']) . '" ' . $selected . '>' . htmlspecialchars($course_row['courseName']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row mb-3">
                                            <div class="col-md-4">
                                                <label class="form-control-label">Module<span class="text-danger ml-1">*</span></label>
                                                <select class="form-control" required name="moduleId" id="exampleInputModuleId">
                                                    <option value="">-- Select Module --</option>
                                                    <?php
                                                    $module_query = mysqli_query($conn, "SELECT * FROM module ORDER BY moduleName ASC");
                                                    while ($module_row = mysqli_fetch_assoc($module_query)) {
                                                        $selected = ($module_row['moduleId'] == $moduleId) ? 'selected' : '';
                                                        echo '<option value="' . htmlspecialchars($module_row['moduleId']) . '" ' . $selected . '>' . htmlspecialchars($module_row['moduleName']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-control-label">Select Class<span class="text-danger ml-1">*</span></label>
                                                <?php
                                                $assignedClassArm = $selectedClassArms[0] ?? null;
                                                $assignedClassParts = $assignedClassArm ? explode('-', $assignedClassArm['selectionValue'], 2) : [];
                                                $assignedClassId = $assignedClassParts[0] ?? '';
                                                $assignedClassArmId = $assignedClassParts[1] ?? '';
                                                $qry = "SELECT * FROM tblclass ORDER BY className ASC";
                                                $result = $conn->query($qry);
                                                ?>
                                                <select name="classId" id="classId" onchange="classArmDropdown(this.value)" class="form-control" required>
                                                    <option value="">-- Select Class --</option>
                                                    <?php while ($rows = $result->fetch_assoc()) { ?>
                                                        <option value="<?php echo htmlspecialchars($rows['Id']); ?>" <?php echo $rows['Id'] == $assignedClassId ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($rows['className']); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-control-label">Class Group<span class="text-danger ml-1">*</span></label>
                                                <select name="classArmId" id="classArmId" data-selected-value="<?php echo htmlspecialchars($assignedClassArmId); ?>" class="form-control" required>
                                                    <option value="">-- Select Class Group --</option>
                                                </select>
                                                <input type="hidden" name="selectionList[]" id="selectionList">
                                            </div>
                                        </div>

                                        <div class="form-group row mb-0">
                                            <div class="col-xl-12 d-flex gap-2">
                                                <?php if (isset($teacherId)) : ?>
                                                    <button type="submit" name="update" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update Teacher</button>
                                                    <a href="createClassTeacher.php" class="btn btn-outline-secondary ml-2">Cancel</a>
                                                <?php else: ?>
                                                    <button type="submit" name="save" class="btn btn-primary"><i class="fas fa-check mr-1"></i> Save Teacher</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table of All Teachers -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">All Class Teachers</h6>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Teacher Name</th>
                                                <th>Email (Username)</th>
                                                <th>Phone</th>
                                                <th>Classes Taught</th>
                                                <th>Course</th>
                                                <th>Module</th>
                                                <th class="text-center" style="width: 90px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT tblclassteacher.Id, tblclassteacher.firstName, tblclassteacher.lastName, tblclassteacher.emailAddress, tblclassteacher.phoneNo,
                                                      GROUP_CONCAT(CONCAT(tblclass.className, ' - ', tblclassarms.classArmName) SEPARATOR '<br>') AS classes_taught,
                                                      course.courseName AS course,
                                                      module.moduleName AS module
                                                      FROM tblclassteacher
                                                      LEFT JOIN tblclassteacher_classarms ON tblclassteacher.Id = tblclassteacher_classarms.classteacher_Id
                                                      LEFT JOIN tblclassarms ON tblclassteacher_classarms.classarm_Id = tblclassarms.Id
                                                      LEFT JOIN tblclass ON tblclassarms.classId = tblclass.Id
                                                      LEFT JOIN course ON tblclassteacher.courseId = course.courseId
                                                      LEFT JOIN module ON tblclassteacher.moduleId = module.moduleId
                                                      GROUP BY tblclassteacher.Id
                                                      ORDER BY tblclassteacher.firstName ASC";
                                            $rs = $conn->query($query);
                                            $sn = 0;
                                            if ($rs && $rs->num_rows > 0) {
                                                while ($rows = $rs->fetch_assoc()) {
                                                    $sn++;
                                                    $tName = htmlspecialchars($rows['firstName'] . ' ' . $rows['lastName']);
                                                    $tEmail = htmlspecialchars($rows['emailAddress']);
                                                    $tPhone = htmlspecialchars($rows['phoneNo'] ?: 'N/A');
                                                    $tCourse = htmlspecialchars($rows['course'] ?: 'N/A');
                                                    $tModule = htmlspecialchars($rows['module'] ?: 'N/A');
                                                    $tClasses = $rows['classes_taught'] ?: '<span class="text-muted">None assigned</span>';
                                                    $id = intval($rows['Id']);
                                                    echo "<tr>
                                                        <td>{$sn}</td>
                                                        <td><strong>{$tName}</strong></td>
                                                        <td>{$tEmail}</td>
                                                        <td>{$tPhone}</td>
                                                        <td>{$tClasses}</td>
                                                        <td><span class='badge badge-light border'>{$tCourse}</span></td>
                                                        <td><span class='badge badge-light border'>{$tModule}</span></td>
                                                        <td class='text-center'>
                                                            <a href='?action=edit&Id={$id}' class='btn btn-sm btn-primary' title='Edit'><i class='fas fa-edit'></i></a>
                                                            <a href='?action=delete&Id={$id}' class='btn btn-sm btn-danger ml-1' onclick='return confirmDelete();' title='Delete'><i class='fas fa-trash'></i></a>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='8' class='text-center text-muted py-3'>No teachers registered yet.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
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
    <script src="js/password-toggle.js"></script>

    <!-- Page level plugins -->
    <script src="../vendore/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendore/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#dataTableHover').DataTable({
                "pageLength": 10,
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search teachers..."
                }
            });

            var classSelect = document.getElementById("classId");
            if (classSelect && classSelect.value !== "") {
                classArmDropdown(
                    classSelect.value,
                    document.getElementById("classArmId").getAttribute("data-selected-value")
                );
            }
        });

        function confirmDelete() {
            return confirm('Are you sure you want to delete this teacher?');
        }

        function prepareClassGroupSelection() {
            var classId = document.getElementById("classId").value;
            var classArmId = document.getElementById("classArmId").value;

            if (classId === "" || classArmId === "") {
                alert("Please select both a class and a class group.");
                return false;
            }

            document.getElementById("selectionList").value = classId + "-" + classArmId;
            return true;
        }

        function classArmDropdown(classId, selectedClassArmId) {
            var classArmSelect = document.getElementById("classArmId");
            classArmSelect.innerHTML = '<option value="">-- Select Class Group --</option>';

            if (classId === "") {
                return;
            }

            var request = new XMLHttpRequest();
            request.onreadystatechange = function() {
                if (this.readyState === 4 && this.status === 200) {
                    classArmSelect.innerHTML = this.responseText;
                    if (selectedClassArmId) {
                        classArmSelect.value = selectedClassArmId;
                    }
                }
            };
            request.open("GET", "ajaxClassArms.php?cid=" + encodeURIComponent(classId), true);
            request.send();
        }
    </script>
</body>
</html>
