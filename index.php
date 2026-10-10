<?php
include 'Includes/dbcon.php';
session_start();

// Function to get the real dates for each day of the week for the current week
function getDatesForCurrentWeek() {
    $dates = [];
    $current = new DateTime();

    // Move to the start of the week (Monday)
    $current->modify('monday this week');

    // Get dates from Monday to Saturday
    for ($i = 0; $i < 6; $i++) {
        $dates[$current->format('l')] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }

    return $dates;
}

// Do not update session activation flags during every page load.
// The supplied legacy SQL dump does not include the timetable schema; run
// DATABASE FILE/repair_schema.sql and configure sessions in the Admin panel.
// Holiday data is optional until the daysoff table is installed.
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
    <link href="vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-login" style="background-image: url('img/logo/loral1.jpg');">
    <!-- Login Content -->
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
                                            <input type="text" class="form-control" required name="username" id="exampleInputEmail" placeholder="Enter User Name">
                                        </div>
                                        <div class="form-group">
                                            <input type="password" name="password" required class="form-control" id="exampleInputPassword" placeholder="Enter Password">
                                        </div>
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox small" style="line-height: 1.5rem;">
                                                <input type="checkbox" class="custom-control-input" id="customCheck">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <input type="submit" class="btn btn-success btn-block" value="Login" name="login" />
                                        </div>
                                    </form>

                                    <?php
date_default_timezone_set('Africa/Casablanca'); // Set to Morocco timezone

if (isset($_POST['login'])) {
    $userType = isset($_POST['userType']) ? (string)$_POST['userType'] : '';
    $username = trim(isset($_POST['username']) ? (string)$_POST['username'] : '');
    $passwordInput = isset($_POST['password']) ? (string)$_POST['password'] : '';
    // Legacy accounts in the supplied SQL dump use MD5. This preserves compatibility;
    // migrate existing accounts to password_hash/password_verify before production.
    $password = md5($passwordInput);
    if (!in_array($userType, ['Administrator', 'ClassTeacher', 'Student'], true) || $username === '' || $passwordInput === '') {
        echo "<div class='alert alert-danger' role='alert'>Please select a role and enter your login details.</div>";
    } elseif ($userType == "Administrator") {
        $stmt = $conn->prepare("SELECT * FROM tbladmin WHERE emailAddress = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $rs = $stmt->get_result();
        if ($rs === false) {
            die("Error: " . $conn->error);
        }
        $num = $rs->num_rows;
        $rows = $rs->fetch_assoc();

        // Track login attempts
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 1;
        } else {
            $_SESSION['login_attempts']++;
        }

        if ($num > 0) {
            // Reset login attempts on successful login
            $_SESSION['login_attempts'] = 0;

            $_SESSION['userId'] = $rows['Id'];
            $_SESSION['firstName'] = $rows['firstName'];
            $_SESSION['lastName'] = $rows['lastName'];
            $_SESSION['emailAddress'] = $rows['emailAddress'];

            echo "<script type = \"text/javascript\">
            window.location = (\"Admin/index.php\")
            </script>";
        } else {
            // Check if login attempts exceed threshold
            $max_login_attempts = 2; // Set the maximum number of login attempts here
            if ($_SESSION['login_attempts'] >= $max_login_attempts) {
                echo "<div class='alert alert-danger' role='alert'>
                Too many incorrect login attempts.</div>
                <div class='mt-3'>
                    <a href='forgotPassword.php' class='btn btn-success'>Forgot Password?</a>
                </div>";
            } else {
                echo "<div class='alert alert-danger' role='alert'>
                Invalid Username/Password!
                </div>";

                // Check if the error message has been displayed twice
                if ($_SESSION['login_attempts'] === 2) {
                    echo "<div class='mt-3'>
                        <a href='forgotPassword.php' class='btn btn-success'>Forgot Password?</a>
                    </div>";
                }
            }
    
}
                                        }else if ($userType == "ClassTeacher") {

                                            $query = "SELECT * FROM tblclassteacher WHERE emailAddress = ? AND password = ?";
                                            $stmt = $conn->prepare($query);
                                            $stmt->bind_param("ss", $username, $password);
                                            $stmt->execute();
                                            $rs = $stmt->get_result();
                                        
                                            if ($rs === false) {
                                                die("Error: " . $conn->error);
                                            }
                                        
                                            $num = $rs->num_rows;
                                            $rows = $rs->fetch_assoc();
                                        
                                            if ($num > 0) {
                                                $teacherId = $rows['Id'];
                                                $_SESSION['userId'] = $rows['Id'];
                                                $_SESSION['firstName'] = $rows['firstName'];
                                                $_SESSION['lastName'] = $rows['lastName'];
                                                $_SESSION['emailAddress'] = $rows['emailAddress'];
                                                $_SESSION['classId'] = $rows['classId'];
                                                $_SESSION['classArmId'] = $rows['classArmId'];
                                        
                                                // Get current time
                                                $currentTime = date('H:i:s');
                                        
                                                // Check if the current time is within the session times
                                                $timeCheckQuery = "SELECT * FROM tblsessionterm WHERE teacher_id = ? AND isActive = '1' AND day_name = ?";
                                                $timeCheckStmt = $conn->prepare($timeCheckQuery);
                                                $day_name = date('l'); // Get the current day name
                                                $timeCheckStmt->bind_param("is", $teacherId, $day_name);
                                                $timeCheckStmt->execute();
                                                $timeCheckRs = $timeCheckStmt->get_result();
                                                $timeCheckNum = $timeCheckRs->num_rows;
                                        
                                                if ($timeCheckNum > 0) {
                                                    // Debugging: Log query results
                                                  
                                        
                                                    $validSession = false;
                                        
                                                    while ($row = $timeCheckRs->fetch_assoc()) {
                                                        $startTime = $row['startTime'];
                                                        $endTime = $row['endTime'];
                                                        
                                        
                                                        if ($startTime < $endTime) { // Same day session
                                                            if ($currentTime >= $startTime && $currentTime <= $endTime) {
                                                                $validSession = true;
                                                                break;
                                                            }
                                                        } else { // Session that spans midnight
                                                            if ($currentTime >= $startTime && $currentTime <= $endTime) {
                                                                $validSession = true;
                                                                break;
                                                            }
                                                        }
                                                    }
                                        
                                                    if ($validSession) {
                                                        echo "<script type = \"text/javascript\">
                                                              window.location = (\"ClassTeacher/index.php\")
                                                              </script>";
                                                    } else {
                                                        // Show alert with current time
                                                        echo "<div class='alert alert-danger' role='alert'>
                                                              It is not the time of any of your sessions. Current Time: $currentTime
                                                              </div>";
                                                    }
                                        
                                                    $timeCheckStmt->close();
                                                } else {
                                                    echo "<div class='alert alert-danger' role='alert'>
                                                          No active sessions, or the day is a holiday!
                                                          </div>";
                                                }
                                            } else {
                                                echo "<div class='alert alert-danger' role='alert'>
                                                      Invalid Username/Password!
                                                      </div>";
                                            }
                                        
                                        
                                        } else if ($userType == "Student") {

                                            $stmt = $conn->prepare("SELECT * FROM tblstudents WHERE admissionNumber = ? AND password = ?");
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

                                                echo "<script type = \"text/javascript\">
                                                window.location = (\"Student/index.php\")
                                                </script>";
                                            } else {
                                                echo "<div class='alert alert-danger' role='alert'>
                                                Invalid Admission Number/Password!
                                                </div>";
                                            }
                                        } else {
                                            echo "<div class='alert alert-danger' role='alert'>
                                            Invalid Username/Password!
                                            </div>";
                                        }
                                    }
                                    ?>

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
</body>

</html>
