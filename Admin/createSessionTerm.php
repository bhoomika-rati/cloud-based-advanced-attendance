<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

$statusMsg = "";
$sessionName = "";
$termId = "";

//------------------------DELETE--------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "delete") {
    $Id = intval($_GET['Id']);

    $stmt = $conn->prepare("DELETE FROM tblsessionterm WHERE Id = ?");
    $stmt->bind_param("i", $Id);
    if ($stmt->execute()) {
        header("Location: createSessionTerm.php?status=delete-success");
        exit();
    } else {
        $statusMsg = "<div class='alert alert-danger'>An error occurred while deleting the session.</div>";
    }
    $stmt->close();
}

//------------------------ACTIVATE------------------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "activate") {
    $Id = intval($_GET['Id']);

    $conn->query("UPDATE tblsessionterm SET isActive='0'");
    $actStmt = $conn->prepare("UPDATE tblsessionterm SET isActive='1' WHERE Id = ?");
    $actStmt->bind_param("i", $Id);
    if ($actStmt->execute()) {
        header("Location: createSessionTerm.php?status=activate-success");
        exit();
    } else {
        $statusMsg = "<div class='alert alert-danger'>An error occurred while activating the session.</div>";
    }
    $actStmt->close();
}

//------------------------SAVE--------------------------------------------------

if (isset($_POST['save'])) {
    $sessionName = trim($_POST['sessionName'] ?? '');
    $termId = trim($_POST['termId'] ?? '');
    $dateCreated = date("Y-m-d");

    if ($sessionName === '' || $termId === '') {
        $statusMsg = "<div class='alert alert-danger'>Please enter session name and select a term.</div>";
    } else {
        $checkStmt = $conn->prepare("SELECT Id FROM tblsessionterm WHERE sessionName = ? AND termId = ?");
        $checkStmt->bind_param("ss", $sessionName, $termId);
        $checkStmt->execute();
        $ret = $checkStmt->get_result();

        if ($ret->num_rows > 0) {
            $statusMsg = "<div class='alert alert-danger'>This Session and Term Already Exists!</div>";
        } else {
            $insStmt = $conn->prepare("INSERT INTO tblsessionterm (sessionName, termId, isActive, dateCreated) VALUES (?, ?, '0', ?)");
            $insStmt->bind_param("sss", $sessionName, $termId, $dateCreated);
            if ($insStmt->execute()) {
                $statusMsg = "<div class='alert alert-success'>Session Created Successfully!</div>";
                $sessionName = "";
                $termId = "";
            } else {
                $statusMsg = "<div class='alert alert-danger'>An error occurred while creating session.</div>";
            }
            $insStmt->close();
        }
        $checkStmt->close();
    }
}

//------------------------EDIT & UPDATE-----------------------------------------

if (isset($_GET['Id']) && isset($_GET['action']) && $_GET['action'] == "edit") {
    $editId = intval($_GET['Id']);

    $fetchStmt = $conn->prepare("SELECT * FROM tblsessionterm WHERE Id = ?");
    $fetchStmt->bind_param("i", $editId);
    $fetchStmt->execute();
    $editRow = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();

    if ($editRow) {
        $sessionName = $editRow['sessionName'];
        $termId = $editRow['termId'];
    }

    if (isset($_POST['update'])) {
        $sessionName = trim($_POST['sessionName'] ?? '');
        $termId = trim($_POST['termId'] ?? '');

        if ($sessionName === '' || $termId === '') {
            $statusMsg = "<div class='alert alert-danger'>Please enter session name and select a term.</div>";
        } else {
            $upStmt = $conn->prepare("UPDATE tblsessionterm SET sessionName = ?, termId = ? WHERE Id = ?");
            $upStmt->bind_param("ssi", $sessionName, $termId, $editId);
            if ($upStmt->execute()) {
                header("Location: createSessionTerm.php?status=update-success");
                exit();
            } else {
                $statusMsg = "<div class='alert alert-danger'>An error occurred while updating session.</div>";
            }
            $upStmt->close();
        }
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'update-success') {
        $statusMsg = "<div class='alert alert-success'>Session Updated Successfully!</div>";
    }
    if ($_GET['status'] === 'delete-success') {
        $statusMsg = "<div class='alert alert-success'>Session Deleted Successfully!</div>";
    }
    if ($_GET['status'] === 'activate-success') {
        $statusMsg = "<div class='alert alert-success'>Session Activated Successfully!</div>";
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
                        <h1 class="h3 mb-0 text-gray-800"><?php echo isset($_GET['Id']) ? 'Edit Academic Session' : 'Create Academic Session'; ?></h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item"><a href="createSessionTerm.php">Sessions</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo isset($_GET['Id']) ? 'Edit' : 'Create'; ?></li>
                        </ol>
                    </div>

                    <?php if (!empty($statusMsg)) echo $statusMsg; ?>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Academic Session & Term</h6>
                                    <?php if (isset($_GET['Id'])): ?>
                                        <a href="createSessionTerm.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-plus mr-1"></i> Add New Session</a>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-control-label">Session Name<span class="text-danger ml-1">*</span></label>
                                                <input type="text" class="form-control" name="sessionName" value="<?php echo htmlspecialchars($sessionName); ?>" required placeholder="e.g. 2026/2027 or Fall 2026">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-control-label">Academic Term<span class="text-danger ml-1">*</span></label>
                                                <?php
                                                $tResult = $conn->query("SELECT * FROM tblterm ORDER BY Id ASC");
                                                ?>
                                                <select required name="termId" class="form-control">
                                                    <option value="">-- Select Term --</option>
                                                    <?php
                                                    if ($tResult && $tResult->num_rows > 0) {
                                                        while ($tRow = $tResult->fetch_assoc()) {
                                                            $selected = ($tRow['Id'] == $termId) ? 'selected' : '';
                                                            echo '<option value="' . htmlspecialchars($tRow['Id']) . '" ' . $selected . '>' . htmlspecialchars($tRow['termName']) . '</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row mb-0">
                                            <div class="col-12 d-flex gap-2">
                                                <button type="submit" name="<?php echo isset($_GET['Id']) ? 'update' : 'save'; ?>" class="btn btn-primary">
                                                    <i class="fas fa-<?php echo isset($_GET['Id']) ? 'save' : 'check'; ?> mr-1"></i>
                                                    <?php echo isset($_GET['Id']) ? 'Update Session' : 'Save Session'; ?>
                                                </button>
                                                <?php if (isset($_GET['Id'])): ?>
                                                    <a href="createSessionTerm.php" class="btn btn-outline-secondary ml-2">Cancel</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sessions List Table -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">All Academic Sessions</h6>
                                    <small class="text-muted"><i class="fas fa-info-circle mr-1"></i> Click the check icon to make a session active system-wide.</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Session Name</th>
                                                <th>Term</th>
                                                <th>Status</th>
                                                <th>Date Created</th>
                                                <th>Manage Timetable</th>
                                                <th class="text-center" style="width: 140px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT st.Id, st.sessionName, st.isActive, st.dateCreated, t.termName
                                                      FROM tblsessionterm st
                                                      LEFT JOIN tblterm t ON t.Id = st.termId
                                                      ORDER BY st.isActive DESC, st.Id DESC";
                                            $rs = $conn->query($query);
                                            $sn = 0;
                                            if ($rs && $rs->num_rows > 0) {
                                                while ($rows = $rs->fetch_assoc()) {
                                                    $sn++;
                                                    $sId = intval($rows['Id']);
                                                    $sName = htmlspecialchars($rows['sessionName']);
                                                    $tName = htmlspecialchars($rows['termName'] ?: 'Term ' . $rows['termId']);
                                                    $isActive = ($rows['isActive'] == '1');
                                                    $dCreated = htmlspecialchars($rows['dateCreated']);

                                                    // Status badge
                                                    $statusBadge = $isActive
                                                        ? "<span class='badge badge-success'><i class='fas fa-check-circle mr-1'></i>Active</span>"
                                                        : "<span class='badge badge-secondary'>Inactive</span>";

                                                    // Timetable count
                                                    $ttCountQ = $conn->query("SELECT COUNT(*) AS total FROM session_timetable WHERE session_id = {$sId}");
                                                    $ttCount = $ttCountQ ? intval($ttCountQ->fetch_assoc()['total']) : 0;

                                                    echo "<tr>
                                                        <td>{$sn}</td>
                                                        <td><strong>{$sName}</strong></td>
                                                        <td>{$tName}</td>
                                                        <td>{$statusBadge}</td>
                                                        <td>{$dCreated}</td>
                                                        <td>
                                                            <a href='createTimetable.php?session_id={$sId}' class='btn btn-xs btn-outline-primary' style='font-size:0.75rem; padding: 2px 7px;'>
                                                                <i class='fas fa-calendar-alt mr-1'></i> Timetable ({$ttCount})
                                                            </a>
                                                        </td>
                                                        <td class='text-center'>
                                                            " . (!$isActive ? "<a href='?action=activate&Id={$sId}' class='btn btn-sm btn-success' title='Make Active Session'><i class='fas fa-check'></i></a>" : "") . "
                                                            <a href='?action=edit&Id={$sId}' class='btn btn-sm btn-primary ml-1' title='Edit'><i class='fas fa-edit'></i></a>
                                                            <a href='?action=delete&Id={$sId}' class='btn btn-sm btn-danger ml-1' onclick=\"return confirm('Delete this session and term?');\" title='Delete'><i class='fas fa-trash'></i></a>
                                                        </td>
                                                    </tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='7' class='text-center text-muted py-3'>No academic sessions found.</td></tr>";
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
    <script src="../vendore/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendore/datatables/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#dataTableHover').DataTable({
                "pageLength": 10
            });
        });
    </script>
</body>
</html>
