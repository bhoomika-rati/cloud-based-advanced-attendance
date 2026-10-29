```php
<?php
include 'Includes/dbcon.php';
session_start();

// Function to get the real dates for each day of the week for the current week
function getDatesForCurrentWeek() {
    $dates = [];
    $current = new DateTime();
    $current->modify('monday this week');

    for ($i = 0; $i < 6; $i++) {
        $dates[$current->format('l')] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }

    return $dates;
}

// Optional holiday data
$days_off = [];
$table_check = $conn->query("SHOW TABLES LIKE 'daysoff'");
if ($table_check && $table_check->num_rows > 0) {
    $holiday_result = $conn->query("SELECT date_DayOff FROM daysoff");
    if ($holiday_result) {
        while ($holiday_row = $holiday_result->fetch_assoc()) {
            $days_off[] = $holiday_row['date_DayOff'];
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
    <title>EPG AMS - Login</title>
    <link href="vendore/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-login">
<div class="container-login">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12 col-md-9">
            <div class="card shadow-sm my-5">
                <div class="card-body p-0">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="login-form">
                                <h5 align="center">EPG ABSENCE MANAGEMENT SYSTEM</h5>
                                <div class="text-center">
                                    <img src="img/logo/attnlg.jpg" style="width:100px;height:100px">
                                    <br><br>
                                    <h1 class="h4 text-gray-900 mb-4">Login Panel</h1>
                                </div>

                                <form class="user" method="POST" action="">
                                    <div class="form-group">
                                        <select required name="userType" class="form-control mb-3">
                                            <option value="">--Select User Roles--</option>
                                            <option value="Administrator">Administrator</option>
                                            <option value="ClassTeacher">ClassTeacher</option>
                                            <option value="Student">Student</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <input type="text" class="form-control" required name="username"
                                               id="exampleInputEmail" placeholder="Enter User Name">
                                    </div>

                                    <div class="form-group">
                                        <input type="password" name="password" required class="form-control"
                                               id="exampleInputPassword" placeholder="Enter Password">
                                    </div>

                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox small" style="line-height: 1.5rem;">
                                            <input type="checkbox" class="custom-control-input" id="customCheck">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <input type="submit" class="btn btn-success btn-block"
                                               value="Login" name="login">
                                    </div>
                                </form>

<?php
date_default_timezone_set('Africa/Casablanca');

if (isset($_POST['login'])) {
    $userType = isset($_POST['userType']) ? (string)$_POST['userType'] : '';
    $username = trim(isset($_POST['username']) ? (string)$_POST['username'] : '');
    $passwordInput = isset($_POST['password']) ? (string)$_POST['password'] : '';

    // Keep plain-text comparison for existing teacher and student accounts.
    $password = $passwordInput;

    if (!in_array($userType, ['Administrator', 'ClassTeacher', 'Student'], true)
        || $username === '' || $passwordInput === '') {

        echo "<div class='alert alert-danger' role='alert'>Please select a role and enter your login details.</div>";

    } elseif ($userType == "Administrator") {

        // ADMINISTRATOR ONLY: compare the MD5 hash with tbladmin.password.
        $adminPassword = md5($passwordInput);
        $stmt = $conn->prepare(
            "SELECT * FROM tbladmin WHERE emailAddress = ? AND password = ?"
        );
        $stmt->bind_param("ss", $username, $adminPassword);
        $stmt->execute();
        $rs = $stmt->get_result();

        if ($rs === false) {
            die("Error: " . $conn->error);
        }

        $num = $rs->num_rows;
        $rows = $rs->fetch_assoc();

        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 1;
        } else {
            $_SESSION['login_attempts']++;
        }

        if ($num > 0) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['userId'] = $rows['Id'];
            $_SESSION['firstName'] = $rows['firstName'];
            $_SESSION['lastName'] = $rows['lastName'];
            $_SESSION['emailAddress'] = $rows['emailAddress'];

            echo '<script>window.location = "Admin/index.php";</script>';
        } else {
            $max_login_attempts = 2;

            if ($_SESSION['login_attempts'] >= $max_login_attempts) {
                echo "<div class='alert alert-danger' role='alert'>
                      Too many incorrect login attempts.</div>
                      <div class='mt-3'>
                      <a href='forgotPassword.php' class='btn btn-success'>Forgot Password?</a>
                      </div>";
            } else {
                echo "<div class='alert alert-danger' role='alert'>
                      Invalid Username/Password!</div>";

                if ($_SESSION['login_attempts'] === 2) {
                    echo "<div class='mt-3'>
                          <a href='forgotPassword.php' class='btn btn-success'>Forgot Password?</a>
                          </div>";
                }
            }
        }

    
    } elseif ($userType == "ClassTeacher") {

        // Teacher login: support plain-text and MD5 passwords.
        $teacherPasswordPlain = $passwordInput;
        $teacherPasswordMD5 = md5($passwordInput);

        $query = "SELECT * FROM tblclassteacher
                  WHERE emailAddress = ?
                  AND (password = ? OR password = ?)";

        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "sss",
            $username,
            $teacherPasswordPlain,
            $teacherPasswordMD5
        );
        $stmt->execute();
        $rs = $stmt->get_result();

        if ($rs === false) {
            die("Error: " . $conn->error);
        }

        $num = $rs->num_rows;
        $rows = $rs->fetch_assoc();
        $stmt->close();

        if ($num > 0) {
            $teacherId = $rows['Id'];

            $_SESSION['userId'] = $rows['Id'];
            $_SESSION['firstName'] = $rows['firstName'];
            $_SESSION['lastName'] = $rows['lastName'];
            $_SESSION['emailAddress'] = $rows['emailAddress'];
            $_SESSION['classId'] = $rows['classId'];
            $_SESSION['classArmId'] = $rows['classArmId'];

            $currentTime = date('H:i:s');
            $day_name = date('l');

            $timeCheckQuery = "SELECT * FROM tblsessionterm
                               WHERE teacher_id = ?
                               AND isActive = '1'
                               AND day_name = ?";

            $timeCheckStmt = $conn->prepare($timeCheckQuery);
            $timeCheckStmt->bind_param("is", $teacherId, $day_name);
            $timeCheckStmt->execute();
            $timeCheckRs = $timeCheckStmt->get_result();

            if ($timeCheckRs->num_rows > 0) {
                $validSession = false;

                while ($row = $timeCheckRs->fetch_assoc()) {
                    $startTime = $row['startTime'];
                    $endTime = $row['endTime'];

                    if ($startTime < $endTime) {
                        if ($currentTime >= $startTime &&
                            $currentTime <= $endTime) {
                            $validSession = true;
                            break;
                        }
                    } else {
                        // Handles sessions that cross midnight.
                        if ($currentTime >= $startTime ||
                            $currentTime <= $endTime) {
                            $validSession = true;
                            break;
                        }
                    }
                }

                $timeCheckStmt->close();

                if ($validSession) {
                    echo '<script>window.location = "ClassTeacher/index.php";</script>';
                } else {
                    echo "<div class='alert alert-danger' role='alert'>
                          Password accepted, but it is not the time of any
                          of your sessions. Current time: "
                          . htmlspecialchars($currentTime, ENT_QUOTES, 'UTF-8')
                          . "</div>";
                }
            } else {
                $timeCheckStmt->close();

                echo "<div class='alert alert-danger' role='alert'>
                      Password accepted, but no active session was found
                      for today.</div>";
            }
        } else {
            echo "<div class='alert alert-danger' role='alert'>
                  Invalid teacher email or password!</div>";
        }

    } elseif ($userType == "Student") {


        // STUDENT LOGIN: unchanged
        $stmt = $conn->prepare(
            "SELECT * FROM tblstudents WHERE admissionNumber = ? AND password = ?"
        );
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $rs = $stmt->get_result();

        if ($rs === false) {
            die("Error: " . $conn->error);
        }

        $num = $rs->num_rows;
        $rows = $rs->fetch_assoc();

        if ($num > 0) {
            $_SESSION['userId'] = $rows['Id'];
            $_SESSION['firstName'] = $rows['firstName'];
            $_SESSION['lastName'] = $rows['lastName'];
            $_SESSION['emailAddress'] = $rows['emailAddress'];
            $_SESSION['classId'] = $rows['classId'];
            $_SESSION['classArmId'] = $rows['classArmId'];

            echo '<script>window.location = "Student/index.php";</script>';
        } else {
            echo "<div class='alert alert-danger' role='alert'>
                  Invalid Admission Number/Password!</div>";
        }
    }
}
?>

                                <div class="text-center"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="vendore/jquery/jquery.min.js"></script>
<script src="vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendore/jquery-easing/jquery.easing.min.js"></script>
<script src="js/ruang-admin.min.js?v=2"></script>
</body>
</html>
