<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$statusMsg = "";
$firstName = "";
$lastName = "";
$admissionNumber = "";
$classId = "";
$classArmId = "";

//------------------------DELETE--------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete") {
    $Id = intval($_GET['Id']);

    $delStmt = $conn->prepare("DELETE FROM tblstudents WHERE Id = ?");
    $delStmt->bind_param("i", $Id);
    $query = $delStmt->execute();
    $delStmt->close();

    if ($query) {
        header("Location: createStudents.php?status=delete-success");
        exit();
    } else {
        $statusMsg = "<div class='alert alert-danger'>An error occurred while deleting student!</div>";
    }
}

//------------------------SAVE--------------------------------------------------

if (isset($_POST['save'])) {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $password = $_POST['password'] ?? '';
    $admissionNumber = trim($_POST['admissionNumber'] ?? '');
    $classId = trim($_POST['classId'] ?? '');
    $classArmId = trim($_POST['classArmId'] ?? '');
    $dateCreated = date("Y-m-d");

    if ($firstName === '' || $lastName === '' || $admissionNumber === '' || $password === '' || $classId === '' || $classArmId === '') {
        $statusMsg = "<div class='alert alert-danger'>Please fill all required fields.</div>";
    } else {
        $checkStmt = $conn->prepare("SELECT Id FROM tblstudents WHERE TRIM(admissionNumber) = ?");
        $checkStmt->bind_param("s", $admissionNumber);
        $checkStmt->execute();
        $ret = $checkStmt->get_result();

        if ($ret->num_rows > 0) {
            $statusMsg = "<div class='alert alert-danger'>This Admission Number Already Exists!</div>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insStmt = $conn->prepare("INSERT INTO tblstudents (firstName, lastName, otherName, password, admissionNumber, classId, classArmId, dateCreated) VALUES (?, ?, 'none', ?, ?, ?, ?, ?)");
            $insStmt->bind_param("sssssss", $firstName, $lastName, $hashedPassword, $admissionNumber, $classId, $classArmId, $dateCreated);
            $query = $insStmt->execute();
            $insStmt->close();

            if ($query) {
                $statusMsg = "<div class='alert alert-success'>Student Created Successfully. The class teacher can generate the student QR code from the class student list.</div>";
                $firstName = "";
                $lastName = "";
                $admissionNumber = "";
                $classId = "";
                $classArmId = "";
            } else {
                $statusMsg = "<div class='alert alert-danger'>An error occurred while creating student!</div>";
            }
        }
        $checkStmt->close();
    }
}

//------------------------EDIT POPULATE & UPDATE---------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit") {
    $editId = intval($_GET['Id']);

    $fetchStmt = $conn->prepare("SELECT * FROM tblstudents WHERE Id = ?");
    $fetchStmt->bind_param("i", $editId);
    $fetchStmt->execute();
    $editRes = $fetchStmt->get_result();
    $row = $editRes ? $editRes->fetch_assoc() : null;
    $fetchStmt->close();

    if ($row) {
        $firstName = $row['firstName'];
        $lastName = $row['lastName'];
        $admissionNumber = $row['admissionNumber'];
        $classId = $row['classId'];
        $classArmId = $row['classArmId'];
    }

    if (isset($_POST['update'])) {
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $newPassword = $_POST['password'] ?? '';
        $admissionNumber = trim($_POST['admissionNumber'] ?? '');
        $classId = trim($_POST['classId'] ?? '');
        $classArmId = trim($_POST['classArmId'] ?? '');
        if (empty($classArmId) && $row) {
            $classArmId = $row['classArmId'];
        }

        if ($firstName === '' || $lastName === '' || $admissionNumber === '' || $classId === '' || $classArmId === '') {
            $statusMsg = "<div class='alert alert-danger'>Please fill all required fields.</div>";
        } else {
            $dupStmt = $conn->prepare("SELECT Id FROM tblstudents WHERE TRIM(admissionNumber) = ? AND Id != ?");
            $dupStmt->bind_param("si", $admissionNumber, $editId);
            $dupStmt->execute();
            if ($dupStmt->get_result()->num_rows > 0) {
                $statusMsg = "<div class='alert alert-danger'>This Admission Number Already Exists!</div>";
            } else {
                // Admission numbers are encoded in student QR codes; clear old codes if it changes.
                $qrPath = ($row && $row['admissionNumber'] === $admissionNumber)
                    ? $row['qrCode_path']
                    : null;

                if (!empty($newPassword)) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $upStmt = $conn->prepare("UPDATE tblstudents SET firstName=?, lastName=?, password=?, admissionNumber=?, classId=?, classArmId=?, qrCode_path=? WHERE Id=?");
                    $upStmt->bind_param("sssssssi", $firstName, $lastName, $hashedPassword, $admissionNumber, $classId, $classArmId, $qrPath, $editId);
                } else {
                    $upStmt = $conn->prepare("UPDATE tblstudents SET firstName=?, lastName=?, admissionNumber=?, classId=?, classArmId=?, qrCode_path=? WHERE Id=?");
                    $upStmt->bind_param("ssssssi", $firstName, $lastName, $admissionNumber, $classId, $classArmId, $qrPath, $editId);
                }

                $query = $upStmt->execute();
                $upStmt->close();

                if ($query) {
                    header("Location: createStudents.php?status=update-success");
                    exit();
                } else {
                    $statusMsg = "<div class='alert alert-danger'>An error occurred while updating student!</div>";
                }
            }
            $dupStmt->close();
        }
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'update-success') {
        $statusMsg = "<div class='alert alert-success'>Student Updated Successfully!</div>";
    }
    if ($_GET['status'] == 'delete-success') {
        $statusMsg = "<div class='alert alert-success'>Student Deleted Successfully!</div>";
    }
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

    <script>
        function classArmDropdown(str) {
            if (str == "") {
                document.getElementById("txtHint").innerHTML = "";
                return;
            } else {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        document.getElementById("txtHint").innerHTML = this.responseText;
                    }
                };
                xmlhttp.open("GET", "ajaxClassArms2.php?cid=" + encodeURIComponent(str), true);
                xmlhttp.send();
            }
        }

        function confirmDelete(Id) {
            if (confirm("Are you sure you want to delete this student?")) {
                window.location.href = "?action=delete&Id=" + Id;
            }
        }
    </script>
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
                        <h1 class="h3 mb-0 text-gray-800"><?php echo isset($_GET['Id']) ? 'Edit Student' : 'Create Students'; ?></h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item"><a href="createStudents.php">Students</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo isset($_GET['Id']) ? 'Edit' : 'Create'; ?></li>
                        </ol>
                    </div>

                    <?php if (!empty($statusMsg)) echo $statusMsg; ?>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Admission Details</h6>
                                    <?php if (isset($_GET['Id'])): ?>
                                        <a href="createStudents.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-plus mr-1"></i> Add New Student</a>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-2">
                                            <div class="col-md-6">
                                                <label class="form-control-label">First Name<span class="text-danger ml-1">*</span></label>
                                                <input type="text" class="form-control" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" id="exampleInputFirstName" required placeholder="First Name">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-control-label">Last Name<span class="text-danger ml-1">*</span></label>
                                                <input type="text" class="form-control" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" id="exampleInputLastName" required placeholder="Last Name">
                                            </div>
                                        </div>

                                        <div class="form-group row mb-2">
                                            <div class="col-md-6">
                                                <label class="form-control-label">Admission Number (Login ID)<span class="text-danger ml-1">*</span></label>
                                                <input type="text" class="form-control" name="admissionNumber" value="<?php echo htmlspecialchars($admissionNumber); ?>" id="exampleInputAdmissionNumber" required placeholder="e.g. STU2026001">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-control-label">
                                                    Password
                                                    <?php if (isset($_GET['Id'])): ?>
                                                        <small class="text-muted font-italic ml-1">(Leave blank to keep current password)</small>
                                                    <?php else: ?>
                                                        <span class="text-danger ml-1">*</span>
                                                    <?php endif; ?>
                                                </label>
                                                <div class="password-field-group">
                                                    <input type="password" class="form-control" name="password" id="exampleInputPassword" <?php echo isset($_GET['Id']) ? '' : 'required'; ?> placeholder="<?php echo isset($_GET['Id']) ? 'Enter new password or leave blank' : 'Enter login password'; ?>">
                                                    <button type="button" class="password-toggle-btn" aria-label="Show password" title="Show password" tabindex="0">
                                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-control-label">Select Class<span class="text-danger ml-1">*</span></label>
                                                <?php
                                                $qry = "SELECT * FROM tblclass ORDER BY className ASC";
                                                $result = $conn->query($qry);
                                                ?>
                                                <select required name="classId" class="form-control" onChange="classArmDropdown(this.value)">
                                                    <option value="">-- Select Class --</option>
                                                    <?php
                                                    if ($result && $result->num_rows > 0) {
                                                        while ($cRow = $result->fetch_assoc()) {
                                                            $selected = ($cRow['Id'] == $classId) ? 'selected' : '';
                                                            echo '<option value="' . htmlspecialchars($cRow['Id']) . '" ' . $selected . '>' . htmlspecialchars($cRow['className']) . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-control-label">Select Class Group (Arm)<span class="text-danger ml-1">*</span></label>
                                                <div id="txtHint">
                                                    <?php
                                                    if (isset($_GET['Id']) && !empty($classArmId)) {
                                                        $armQry = $conn->query("SELECT * FROM tblclassarms WHERE Id = '" . intval($classArmId) . "'");
                                                        if ($armQry && $armQry->num_rows > 0) {
                                                            $armRow = $armQry->fetch_assoc();
                                                            echo '<input type="hidden" name="classArmId" value="' . htmlspecialchars($armRow['Id']) . '">';
                                                            echo '<input type="text" class="form-control" value="' . htmlspecialchars($armRow['classArmName']) . '" readonly>';
                                                        } else {
                                                            echo '<select required name="classArmId" class="form-control"><option value="">-- Select Class Group --</option></select>';
                                                        }
                                                    } else {
                                                        echo '<select required name="classArmId" class="form-control"><option value="">-- Select Class Group --</option></select>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row mb-0">
                                            <div class="col-xl-12 d-flex gap-2">
                                                <button type="submit" name="<?php echo isset($_GET['Id']) ? 'update' : 'save'; ?>" class="btn btn-primary">
                                                    <i class="fas fa-<?php echo isset($_GET['Id']) ? 'save' : 'check'; ?> mr-1"></i>
                                                    <?php echo isset($_GET['Id']) ? 'Update Student' : 'Save Student'; ?>
                                                </button>
                                                <?php if (isset($_GET['Id'])): ?>
                                                    <a href="createStudents.php" class="btn btn-outline-secondary ml-2">Cancel</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Students List Table -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">All Registered Students</h6>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Full Name</th>
                                                <th>Admission Number (Login)</th>
                                                <th>Class</th>
                                                <th>Class Group</th>
                                                <th>Attendance QR</th>
                                                <th>Date Created</th>
                                                <th class="text-center" style="width: 140px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT tblstudents.Id, tblstudents.firstName, tblstudents.lastName,
                                                      tblstudents.admissionNumber, tblstudents.dateCreated,
                                                      tblclass.className, tblclassarms.classArmName
                                                      FROM tblstudents
                                                      LEFT JOIN tblclass ON tblclass.Id = tblstudents.classId
                                                      LEFT JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
                                                      ORDER BY tblstudents.Id DESC";
                                            $rs = $conn->query($query);
                                            $sn = 0;
                                            if ($rs && $rs->num_rows > 0) {
                                                while ($rows = $rs->fetch_assoc()) {
                                                    $sn++;
                                                    $sId = intval($rows['Id']);
                                                    $fullName = htmlspecialchars($rows['firstName'] . " " . $rows['lastName']);
                                                    $admNo = htmlspecialchars($rows['admissionNumber']);
                                                    $cName = htmlspecialchars($rows['className'] ?: 'N/A');
                                                    $caName = htmlspecialchars($rows['classArmName'] ?: 'N/A');
                                                    $dateCreated = htmlspecialchars($rows['dateCreated']);

                                                    echo "<tr>
                                                        <td>{$sn}</td>
                                                        <td><strong>{$fullName}</strong></td>
                                                        <td><code>{$admNo}</code></td>
                                                        <td>{$cName}</td>
                                                        <td>{$caName}</td>
                                                        <td><span class='text-muted small'>Generated by the class teacher for each attendance session</span></td>
                                                        <td>{$dateCreated}</td>
                                                        <td class='text-center'>
                                                            <a href='?action=edit&Id={$sId}' class='btn btn-sm btn-primary ml-1' title='Edit'><i class='fas fa-edit'></i></a>
                                                            <button onclick='confirmDelete({$sId})' class='btn btn-sm btn-danger ml-1' title='Delete'><i class='fas fa-trash'></i></button>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='8' class='text-center text-muted py-3'>No students enrolled yet.</td></tr>";
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
            <?php include 'Includes/footer.php'; ?>
        </div>
    </div>

    <!-- Scroll to top -->
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <script src="../vendore/jquery/jquery.min.js"></script>
    <script src="../vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendore/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
    <script src="js/password-toggle.js"></script>
    <script src="../vendore/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendore/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#dataTableHover').DataTable({
                "pageLength": 10,
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search students..."
                }
            });
        });
    </script>
</body>
</html>
