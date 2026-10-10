<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

// Validate session user ID
if (!isset($_SESSION['userId'])) {
    // Redirect or handle the case where session user ID is not set
    die("User session not found. Please login.");
}

// Fetch student information
$query = "SELECT tblstudents.*, tblclass.className, tblclassarms.classArmName
          FROM tblstudents
          LEFT JOIN tblclass ON tblclass.Id = tblstudents.classId
          LEFT JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
          WHERE tblstudents.Id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $_SESSION['userId']);
$stmt->execute();
$result = $stmt->get_result();

// Check if student record exists
if ($result->num_rows > 0) {
    $rows = $result->fetch_assoc();
    $fullName = $rows['firstName'] . " " . $rows['lastName'];
    $className = isset($rows['className']) ? $rows['className'] : 'N/A';
    $classArmName = isset($rows['classArmName']) ? $rows['classArmName'] : 'N/A';
} else {
    // Handle case where student record is not found
    die("Student record not found.");
}

$stmt->close();
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
        <?php include "Includes/sidebar.php"; ?>
        <!-- Sidebar -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- TopBar -->
                <?php include "Includes/topbar.php"; ?>
                <!-- Topbar -->
                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Students Dashboard </h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </div>

                    <!-- View Student Info -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                                </div>
                                <div class="table-responsive p-3">
                                    <table class="table align-items-center table-flush table-hover">
                                        <tbody>
                                            <tr>
                                                <th>Full Name:</th>
                                                <td><?php echo htmlspecialchars($fullName); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Class:</th>
                                                <td><?php echo htmlspecialchars($className); ?></td>
                                            </tr>
                                            <tr>
                                                <th>Class Arm:</th>
                                                <td><?php echo htmlspecialchars($classArmName); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /View Student Info -->

                    <!-- Footer -->
                    <?php include 'includes/footer.php'; ?>
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
            <script src="../vendor/chart.js/Chart.min.js"></script>
            <script src="js/demo/chart-area-demo.js"></script>
</body>

</html>
