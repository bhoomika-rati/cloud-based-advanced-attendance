<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Includes/dbcon.php';
include '../Includes/session.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $emailAddress = $_POST['emailAddress'];
    $password = md5($_POST['password']); // Hash the password with MD5

    // Confirm Password
    $confirmPassword = md5($_POST['confirmPassword']);

    if ($password !== $confirmPassword) {
        // Passwords do not match, display an error message
        $errorMsg = "Passwords do not match.";
    } else {
        // Update Administrator Information including hashed password
        $updateQuery = "UPDATE tbladmin SET firstName='$firstName', lastName='$lastName', emailAddress='$emailAddress', password='$password' WHERE Id = 1";
        $conn->query($updateQuery);

        // Redirect to the same page to avoid form resubmission on page reload
        header("Location: administrator_profile.php");
        exit();
    }
}

// Retrieve Administrator Information
$query = "SELECT * FROM tbladmin WHERE Id = 1"; // Assuming there's only one admin with ID 1
$result = $conn->query($query);
$admin = $result->fetch_assoc();
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
    <title>Administrator Profile</title>
    <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <script>
        // JavaScript function to validate password confirmation
        function validatePassword() {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("confirmPassword").value;

            if (password !== confirmPassword) {
                // Passwords do not match, show an alert and prevent form submission
                alert("Passwords do not match.");
                return false;
            }
            return true;
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Administrator Profile</h1>
                        <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Administrator Profile</li>
            </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Update Profile</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post" onsubmit="return validatePassword();">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-4">
                                                <label class="form-control-label">First Name</label>
                                                <input type="text" class="form-control" name="firstName" value="<?php echo $admin['firstName']; ?>" required>
                                            </div>
                                            <div class="col-xl-4">
                                                <label class="form-control-label">Last Name</label>
                                                <input type="text" class="form-control" name="lastName" value="<?php echo $admin['lastName']; ?>" required>
                                            </div>
                                            <div class="col-xl-4">
                                                <label class="form-control-label">Email Address</label>
                                                <input type="email" class="form-control" name="emailAddress" value="<?php echo $admin['emailAddress']; ?>" required>
                                            </div>
                                        </div>
                                        <!-- Password Fields -->
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-4">
                                               
                                            <label class="form-control-label">New Password</label>
                                                <input type="text" class="form-control" name="password" id="password" placeholder="Enter New Password" required>
                                            </div>
                                            <div class="col-xl-4">
                                                <label class="form-control-label">Confirm Password</label>
                                                <input type="password" class="form-control" name="confirmPassword" id="confirmPassword" placeholder="Confirm New Password" required>
                                            </div>
                                        </div>
                                        <!-- Display error message if passwords don't match -->
                                        <?php if (isset($errorMsg)) : ?>
                                            <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-primary">Update Profile</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!---Container Fluid-->
            </div>
            <!-- Footer -->
            <?php include "Includes/footer.php"; ?>
            <!-- Footer -->
        </div>
    </div>

    <!-- Scroll to top -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendore/jquery/jquery.min.js"></script>
    <script src="../vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendore/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
</body>

</html>
