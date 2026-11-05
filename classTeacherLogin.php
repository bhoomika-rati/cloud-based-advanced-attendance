<?php 
include 'Includes/dbcon.php';
session_start();
ob_start();
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
    <title>Code Camp BD - Login</title>
    <link href="vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <link href="Admin/css/admin-compact.css" rel="stylesheet">
</head>

<body class="bg-gradient-login">
    <!-- Login Content -->
    <div class="container-login">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card shadow-sm my-5">
                    <div class="card-body p-0">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="login-form">
                                    <div class="text-center">
                                        <img src="img/logo/attnlg.jpg" style="width:100px;height:100px">
                                        <br><br>
                                        <h1 class="h4 text-gray-900 mb-4">Login</h1>
                                    </div>
                                    <form class="user" method="Post" action="">
                                        <div class="form-group">
                                            <input type="text" class="form-control" required name="username" id="exampleInputEmail" placeholder="Enter Email Address">
                                        </div>
                                        <div class="form-group">
                                            <div class="password-field-group">
                                                <input type="password" name="password" required class="form-control" id="exampleInputPassword" placeholder="Enter Password">
                                                <button type="button" class="password-toggle-btn" aria-label="Show password" title="Show password" tabindex="0">
                                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <input type="submit" class="btn btn-primary btn-block" value="Login" name="login" />
                                        </div>
                                    </form>

                                    <?php

  if(isset($_POST['login'])){

    $username = trim($_POST['username'] ?? '');
    $passwordInput = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM tblclassteacher WHERE LOWER(TRIM(emailAddress)) = LOWER(?)");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $rs = $stmt->get_result();
    $rows = $rs ? $rs->fetch_assoc() : null;
    $stmt->close();

    $valid = false;
    if($rows){
      if(password_verify($passwordInput, $rows['password'])){
        $valid = true;
      } elseif($rows['password'] === md5($passwordInput) || $rows['password'] === $passwordInput){
        $valid = true;
        $newHash = password_hash($passwordInput, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE tblclassteacher SET password = ? WHERE Id = ?");
        $up->bind_param("si", $newHash, $rows['Id']);
        $up->execute();
        $up->close();
      }
    }

    if($valid){

      session_regenerate_id(true);
      $_SESSION['userId'] = $rows['Id'];
      $_SESSION['userType'] = 'ClassTeacher';
      $_SESSION['firstName'] = $rows['firstName'];
      $_SESSION['lastName'] = $rows['lastName'];
      $_SESSION['emailAddress'] = $rows['emailAddress'];
      $_SESSION['classId'] = $rows['classId'];
      $_SESSION['classArmId'] = $rows['classArmId'];

      echo "<script type = \"text/javascript\">
      window.location = (\"ClassTeacher/index.php\");
      </script>";
      exit();
    }

    else{

      echo "<div class='alert alert-danger' role='alert'>
      Invalid Username/Password!
      </div>";

    }
  }
?>

                                    <!-- <hr>
                    <a href="index.html" class="btn btn-google btn-block">
                      <i class="fab fa-google fa-fw"></i> Login with Google
                    </a>
                    <a href="index.html" class="btn btn-facebook btn-block">
                      <i class="fab fa-facebook-f fa-fw"></i> Login with Facebook
                    </a> -->
                                    <hr>
                                    <div class="text-center">
                                        <a class="font-weight-bold small" href="classTeacherLogin.php">Class Teacher Login!</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        <!-- <a class="font-weight-bold small" href=".php">Cooperative Account!</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a class="font-weight-bold small" href="forgotPassword.php">Forgot Password?</a> -->

                                    </div>
                                    <div class="text-center">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Login Content -->
    <script src="vendore/jquery/jquery.min.js"></script>
    <script src="vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendore/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
    <script src="js/password-toggle.js"></script>
</body>

</html>