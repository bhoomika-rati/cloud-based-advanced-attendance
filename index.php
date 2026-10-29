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
    <meta name="description" content="Attendly makes attendance management simple for administrators, teachers, and students.">
    <meta name="author" content="">
    <link href="img/logo/attendly-mark.svg" rel="icon" type="image/svg+xml">
    <title>Attendly | Attendance Management</title>
    <link href="vendore/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
    <style>
        .login-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
            background:
                radial-gradient(circle at 12% 15%, rgba(35, 183, 166, 0.12), transparent 28rem),
                radial-gradient(circle at 90% 85%, rgba(36, 90, 130, 0.1), transparent 25rem),
                #f3f7fa;
        }

        .login-shell {
            display: grid;
            grid-template-columns: minmax(280px, 0.9fr) minmax(390px, 1.1fr);
            width: 100%;
            max-width: 900px;
            min-height: 540px;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(22, 57, 78, 0.08);
            border-radius: 22px;
            box-shadow: 0 24px 70px rgba(27, 55, 75, 0.13);
        }

        .login-welcome {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: 2.5rem;
            color: #fff;
            background: linear-gradient(145deg, #123b50 0%, #145b65 58%, #168579 100%);
        }

        .login-welcome::before,
        .login-welcome::after {
            position: absolute;
            z-index: 0;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 50%;
            content: "";
            pointer-events: none;
        }

        .login-welcome::before {
            right: -120px;
            bottom: 45px;
            width: 330px;
            height: 330px;
        }

        .login-welcome::after {
            right: -75px;
            bottom: 90px;
            width: 240px;
            height: 240px;
        }

        .login-welcome > * {
            position: relative;
            z-index: 1;
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .login-logo {
            width: 44px;
            height: 44px;
            border: 2px solid rgba(255, 255, 255, 0.65);
            border-radius: 13px;
            object-fit: cover;
        }

        .welcome-copy {
            max-width: 340px;
            margin: 3rem 0;
        }

        .welcome-eyebrow {
            margin-bottom: 0.85rem;
            color: #9de5d9;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .welcome-copy h1 {
            margin-bottom: 1rem;
            font-size: clamp(2rem, 4vw, 2.7rem);
            font-weight: 700;
            line-height: 1.12;
        }

        .welcome-copy p {
            margin: 0;
            color: rgba(255, 255, 255, 0.76);
            font-size: 0.98rem;
            line-height: 1.7;
        }

        .welcome-note {
            margin: 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.78rem;
        }

        .login-panel {
            display: flex;
            align-items: center;
            padding: 3.25rem 3.5rem;
        }

        .login-form {
            width: 100%;
        }

        .login-heading {
            margin-bottom: 1.5rem;
        }

        .login-heading h2 {
            margin-bottom: 0.45rem;
            color: #18374a;
            font-size: 1.75rem;
            font-weight: 700;
        }

        .login-heading p {
            margin: 0;
            color: #71818c;
            font-size: 0.9rem;
        }

        .login-page .form-group {
            margin-bottom: 0.9rem;
        }

        .login-page .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #304957;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .login-page .role-field {
            min-width: 0;
            padding: 0;
            border: 0;
        }

        .login-page .role-field legend {
            width: auto;
            margin-bottom: 0.5rem;
            color: #304957;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .role-options {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.5rem;
        }

        .login-page .role-choice {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            min-height: 44px;
            margin: 0;
            padding: 0.5rem 0.35rem;
            border: 1px solid #dce5e9;
            border-radius: 9px;
            color: #526672;
            background: #fff;
            font-size: 0.7rem;
            text-align: center;
            white-space: nowrap;
            cursor: pointer;
            transition: border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
        }

        .login-page .role-choice input {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            clip-path: inset(50%);
        }

        .login-page .role-choice:has(input:checked) {
            border-color: #168579;
            color: #116e66;
            background: #edf8f6;
            box-shadow: inset 0 0 0 1px #168579;
        }

        .login-page .role-choice:has(input:focus-visible) {
            outline: 3px solid rgba(22, 133, 121, 0.22);
            outline-offset: 2px;
        }

        .login-page .form-control {
            height: 48px;
            padding: 0.7rem 0.9rem;
            border: 1px solid #dce5e9;
            border-radius: 9px;
            color: #18374a;
            font-size: 0.9rem;
            box-shadow: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .login-page .form-control:focus {
            border-color: #168579;
            box-shadow: 0 0 0 3px rgba(22, 133, 121, 0.13);
        }

        .login-page .form-control::placeholder {
            color: #a0adb5;
        }

        .login-page .btn-login {
            width: 100%;
            min-height: 48px;
            margin-top: 0.4rem;
            border: 0;
            border-radius: 9px;
            color: #fff;
            background: linear-gradient(100deg, #147d73, #159887);
            font-size: 0.92rem;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(20, 125, 115, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .login-page .btn-login:hover,
        .login-page .btn-login:focus {
            color: #fff;
            background: linear-gradient(100deg, #116e66, #138778);
            box-shadow: 0 10px 22px rgba(20, 125, 115, 0.27);
            transform: translateY(-1px);
        }

        .login-page .alert {
            margin-top: 1.25rem;
            margin-bottom: 0;
            border-radius: 9px;
            font-size: 0.85rem;
        }

        @media (max-height: 600px) and (min-width: 701px) {
            .login-page {
                padding: 1rem;
            }

            .login-shell {
                min-height: 0;
            }

            .login-welcome {
                padding: 1.75rem;
            }

            .welcome-copy {
                margin: 1.5rem 0;
            }

            .login-panel {
                padding: 1.75rem 2.25rem;
            }
        }

        @media (max-width: 700px) {
            .login-page {
                padding: 1rem;
            }

            .login-shell {
                grid-template-columns: 1fr;
                max-width: 480px;
                min-height: 0;
                border-radius: 18px;
            }

            .login-welcome {
                min-height: 190px;
                padding: 1.5rem 1.75rem;
            }

            .login-logo {
                width: 38px;
                height: 38px;
            }

            .welcome-copy {
                margin: 1.5rem 0 0;
            }

            .welcome-copy h1 {
                margin-bottom: 0.45rem;
                font-size: 1.65rem;
            }

            .welcome-copy p,
            .welcome-note {
                display: none;
            }

            .login-panel {
                padding: 2rem 1.75rem 2.25rem;
            }
        }

        @media (max-width: 380px) {
            .login-page {
                padding: 0.65rem;
            }

            .login-panel {
                padding: 1.6rem 1.25rem 1.8rem;
            }

            .login-brand {
                font-size: 0.7rem;
            }
        }
    </style>
</head>

<body class="login-page">
<main class="login-shell">
    <section class="login-welcome" aria-label="Attendance management system">
        <div class="login-brand">
            <img src="img/logo/attendly-mark.svg" class="login-logo" alt="">
            <span>Attendly</span>
        </div>
        <div class="welcome-copy">
            <div class="welcome-eyebrow">Built for campus life</div>
            <h1>One campus. Every program. In sync.</h1>
            <p>Manage attendance across undergraduate and postgraduate programs in one place.</p>
        </div>
        <p class="welcome-note">Supporting your campus from UG through PG.</p>
    </section>

    <section class="login-panel" aria-labelledby="login-title">
        <div class="login-form">
            <header class="login-heading">
                <h2 id="login-title">Welcome back</h2>
                <p>Sign in to continue to your account.</p>
            </header>

            <form method="POST" action="">
                <fieldset class="form-group role-field">
                    <legend>I am signing in as</legend>
                    <div class="role-options">
                        <label class="role-choice">
                            <input type="radio" name="userType" value="Administrator" required>
                            <span>Administrator</span>
                        </label>
                        <label class="role-choice">
                            <input type="radio" name="userType" value="ClassTeacher" required>
                            <span>Class Teacher</span>
                        </label>
                        <label class="role-choice">
                            <input type="radio" name="userType" value="Student" required>
                            <span>Student</span>
                        </label>
                    </div>
                </fieldset>

                <div class="form-group">
                    <label for="exampleInputEmail">Username or admission number</label>
                    <input type="text" class="form-control" required name="username"
                           id="exampleInputEmail" placeholder="Enter your username">
                </div>

                <div class="form-group">
                    <label for="exampleInputPassword">Password</label>
                    <input type="password" name="password" required class="form-control"
                           id="exampleInputPassword" placeholder="Enter your password">
                </div>

                <button type="submit" class="btn btn-login" name="login">Sign in</button>
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

        </div>
    </section>
</main>

<script src="vendore/jquery/jquery.min.js"></script>
<script src="vendore/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendore/jquery-easing/jquery.easing.min.js"></script>
<script src="js/ruang-admin.min.js?v=2"></script>
</body>
</html>
