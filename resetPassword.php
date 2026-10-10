<?php
include 'Includes/dbcon.php';
session_start();

if (!isset($_SESSION['verification_code'])) {
    header("Location: ForgotPassword.php");
    exit();
}

if (isset($_POST['submit'])) {
    $verification_code = $_POST['verification_code'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if the verification code matches
    if ($verification_code != $_SESSION['verification_code']) {
        echo "<div class='alert alert-danger' role='alert'>Invalid verification code!</div>";
    } elseif ($password !== $confirm_password) {
        echo "<div class='alert alert-danger' role='alert'>Passwords do not match!</div>";
    } else {
        // Hash the new password
        $hashed_password = md5($password); // This is for demonstration; use stronger hashing in production

        // Update password in database (assuming tbladmin table)
        $email = $_SESSION['email'];
        $update_query = "UPDATE tbladmin SET password = ? WHERE emailAddress = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ss", $hashed_password, $email);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // Password updated successfully; clear session variables
            unset($_SESSION['verification_code']);
            unset($_SESSION['email']);

            // Success message with automatic redirect
            echo <<<HTML
                <div id="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Password reset successful!</strong> You can now <a href="index.php" class="alert-link">login</a> with your new password.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = "index.php";
                    }, 3000); // 3 seconds
                </script>
            HTML;
        } else {
            echo "<div class='alert alert-danger' role='alert'>Failed to update password. Please try again.</div>";
        }

        $stmt->close();
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
    <title>Code Camp BD - Reset Password</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-login">
    <!-- Login Content -->
    <div class="container-login">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card shadow-sm my5">
                    <div class="card-body p0">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="login-form">
                                    <div class="text-center">
                                        <img src="img/logo/attnlg.jpg" style="width:100px;height:100px">
                                        <br><br>
                                        <h1 class="h4 text-gray-900 mb4">Reset Password</h1>
                                    </div>
                                    <form class="user" method="post">
                                        <div class="form-group">
                                            <input type="text" class="form-control" name="verification_code" placeholder="Enter Verification Code" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" class="form-control" name="password" placeholder="Enter New Password" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm New Password" required>
                                        </div>
                                        <div class="form-group">
                                            <input type="submit" class="btn btn-primary btn-block" value="Reset Password" name="submit">
                                        </div>
                                        <div class="form-group">
                                            <a href="index.php" class="btn btn-secondary btn-block">Back to Login</a>
                                        </div>
                                    </form>
                                    <hr>
                                    <div class="text-center"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Login Content -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
</body>
</html>
