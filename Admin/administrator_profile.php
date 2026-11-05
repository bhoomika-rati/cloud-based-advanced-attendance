<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

include '../Includes/dbcon.php';
include '../Includes/session.php';

$adminId = isset($_SESSION['userId']) ? intval($_SESSION['userId']) : 1;
$statusMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $emailAddress = trim($_POST['emailAddress'] ?? '');
    $rawPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if ($firstName === '' || $lastName === '' || $emailAddress === '') {
        $statusMsg = "<div class='alert alert-danger'>First Name, Last Name, and Email Address are required.</div>";
    } elseif (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
        $statusMsg = "<div class='alert alert-danger'>Please enter a valid email address.</div>";
    } elseif (!empty($rawPassword) && $rawPassword !== $confirmPassword) {
        $statusMsg = "<div class='alert alert-danger'>Passwords do not match.</div>";
    } else {
        if (!empty($rawPassword)) {
            $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE tbladmin SET firstName = ?, lastName = ?, emailAddress = ?, password = ? WHERE Id = ?");
            $stmt->bind_param("ssssi", $firstName, $lastName, $emailAddress, $hashedPassword, $adminId);
        } else {
            $stmt = $conn->prepare("UPDATE tbladmin SET firstName = ?, lastName = ?, emailAddress = ? WHERE Id = ?");
            $stmt->bind_param("sssi", $firstName, $lastName, $emailAddress, $adminId);
        }

        if ($stmt->execute()) {
            $_SESSION['firstName'] = $firstName;
            $_SESSION['lastName'] = $lastName;
            $_SESSION['emailAddress'] = $emailAddress;
            $statusMsg = "<div class='alert alert-success'>Profile updated successfully!</div>";
        } else {
            $statusMsg = "<div class='alert alert-danger'>Failed to update profile. Please try again.</div>";
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT * FROM tbladmin WHERE Id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    $admin = ['firstName' => '', 'lastName' => '', 'emailAddress' => ''];
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
                        <h1 class="h3 mb-0 text-gray-800">Administrator Profile</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Profile</li>
                        </ol>
                    </div>

                    <?php if (!empty($statusMsg)) echo $statusMsg; ?>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-4">
                                <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Edit Profile Information</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group row mb-2">
                                            <div class="col-md-4">
                                                <label class="form-control-label">First Name<span class="text-danger ml-1">*</span></label>
                                                <input type="text" class="form-control" name="firstName" value="<?php echo htmlspecialchars($admin['firstName']); ?>" required placeholder="First Name">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-control-label">Last Name<span class="text-danger ml-1">*</span></label>
                                                <input type="text" class="form-control" name="lastName" value="<?php echo htmlspecialchars($admin['lastName']); ?>" required placeholder="Last Name">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-control-label">Email Address (Login)<span class="text-danger ml-1">*</span></label>
                                                <input type="email" class="form-control" name="emailAddress" value="<?php echo htmlspecialchars($admin['emailAddress']); ?>" required placeholder="admin@domain.com">
                                            </div>
                                        </div>

                                        <div class="form-group row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-control-label">New Password <small class="text-muted">(Leave blank to keep current)</small></label>
                                                <div class="password-field-group">
                                                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter new password">
                                                    <button type="button" class="password-toggle-btn" aria-label="Show password" title="Show password" tabindex="0">
                                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-control-label">Confirm New Password</label>
                                                <div class="password-field-group">
                                                    <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" placeholder="Confirm new password">
                                                    <button type="button" class="password-toggle-btn" aria-label="Show password" title="Show password" tabindex="0">
                                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row mb-0">
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update Profile</button>
                                            </div>
                                        </div>
                                    </form>
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
</body>
</html>
