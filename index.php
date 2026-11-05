<?php
session_start();

$loginRole = isset($_GET['role']) && is_string($_GET['role']) ? $_GET['role'] : '';
$loginRoles = ['Administrator', 'ClassTeacher', 'Student'];
$attendanceToken = $_GET['token'] ?? '';
$isAttendanceLanding = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && ($_GET['attendance'] ?? '') === '1'
    && is_string($attendanceToken)
    && preg_match('/\A[a-f0-9]{64}\z/i', $attendanceToken);

if ($isAttendanceLanding) {
    $_SESSION['attendancePendingToken'] = strtolower($attendanceToken);
    unset($_SESSION['attendancePendingResult']);
    if (($_SESSION['userType'] ?? '') === 'Student' && !empty($_SESSION['userId'])) {
        header('Location: Student/markAttendance.php?token=' . rawurlencode(strtolower($attendanceToken)));
        exit;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && !in_array($loginRole, $loginRoles, true)) {
    require __DIR__ . '/landing.php';
    exit;
}

include 'Includes/dbcon.php';
ob_start();

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

        .password-field-group {
            position: relative;
            display: flex;
            align-items: stretch;
            width: 100%;
        }

        .password-field-group .form-control {
            padding-right: 42px !important;
        }

        .password-toggle-btn {
            position: absolute;
            right: 1px;
            top: 1px;
            bottom: 1px;
            width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: none;
            color: #71818c;
            cursor: pointer;
            z-index: 5;
            border-radius: 0 9px 9px 0;
            transition: color 0.15s ease;
        }

        .password-toggle-btn:hover {
            color: #168579;
        }

        .password-toggle-btn:focus {
            outline: 2px solid #168579;
            outline-offset: -2px;
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
                <?php if (!empty($_SESSION['attendancePendingToken'])): ?>
                    <div class="alert alert-info" role="status">
                        Sign in as a student to complete your attendance scan.
                    </div>
                <?php endif; ?>
                <fieldset class="form-group role-field">
                    <legend>I am signing in as</legend>
                    <div class="role-options">
                        <label class="role-choice">
                            <input type="radio" name="userType" value="Administrator" required
                                <?= $loginRole === 'Administrator' ? 'checked' : '' ?>>
                            <span>Administrator</span>
                        </label>
                        <label class="role-choice">
                            <input type="radio" name="userType" value="ClassTeacher" required
                                <?= $loginRole === 'ClassTeacher' ? 'checked' : '' ?>>
                            <span>Class Teacher</span>
                        </label>
                        <label class="role-choice">
                            <input type="radio" name="userType" value="Student" required
                                <?= $loginRole === 'Student' || !empty($_SESSION['attendancePendingToken']) ? 'checked' : '' ?>>
                            <span>Student</span>
                        </label>
                    </div>
                </fieldset>

                <div class="form-group">
                    <label for="exampleInputEmail">Username, student name, or admission number</label>
                    <input type="text" class="form-control" required name="username"
                           id="exampleInputEmail" placeholder="Enter username or student full name/admission number">
                </div>

                <div class="form-group">
                    <label for="exampleInputPassword">Password</label>
                    <div class="password-field-group">
                        <input type="password" name="password" required class="form-control"
                               id="exampleInputPassword" placeholder="Enter your password">
                        <button type="button" class="password-toggle-btn" aria-label="Show password" title="Show password" tabindex="0">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-login" name="login">Sign in</button>
            </form>

<?php
if (isset($_POST['login'])) {
    $userType = isset($_POST['userType']) ? (string)$_POST['userType'] : '';
    $username = trim(isset($_POST['username']) ? (string)$_POST['username'] : '');
    $passwordInput = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if (!in_array($userType, ['Administrator', 'ClassTeacher', 'Student'], true)
        || $username === '' || $passwordInput === '') {

        echo "<div class='alert alert-danger' role='alert'>Please select a role and enter your login details.</div>";

    } elseif ($userType === "Administrator") {

        $stmt = $conn->prepare("SELECT * FROM tbladmin WHERE LOWER(TRIM(emailAddress)) = LOWER(?)");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $rs = $stmt->get_result();
        $admin = $rs ? $rs->fetch_assoc() : null;
        $stmt->close();

        $validAdmin = false;
        if ($admin) {
            if (password_verify($passwordInput, $admin['password'])) {
                $validAdmin = true;
            } elseif ($admin['password'] === md5($passwordInput) || $admin['password'] === $passwordInput) {
                $validAdmin = true;
                // Upgrade password to modern password_hash
                $newHash = password_hash($passwordInput, PASSWORD_DEFAULT);
                $upStmt = $conn->prepare("UPDATE tbladmin SET password = ? WHERE Id = ?");
                $upStmt->bind_param("si", $newHash, $admin['Id']);
                $upStmt->execute();
                $upStmt->close();
            }
        }

        if ($validAdmin) {
            session_regenerate_id(true);
            $_SESSION['userId'] = $admin['Id'];
            $_SESSION['userType'] = 'Administrator';
            $_SESSION['firstName'] = $admin['firstName'];
            $_SESSION['lastName'] = $admin['lastName'];
            $_SESSION['emailAddress'] = $admin['emailAddress'];

            unset($_SESSION['attendancePendingToken']);
            unset($_SESSION['attendancePendingResult']);
            echo '<script>window.location = "Admin/index.php";</script>';
            exit();
        } else {
            echo "<div class='alert alert-danger' role='alert'>Invalid Username/Password!</div>";
        }

    } elseif ($userType === "ClassTeacher") {

        $stmt = $conn->prepare("SELECT * FROM tblclassteacher WHERE LOWER(TRIM(emailAddress)) = LOWER(?)");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $rs = $stmt->get_result();
        $teacher = $rs ? $rs->fetch_assoc() : null;
        $stmt->close();

        $validTeacher = false;
        if ($teacher) {
            if (password_verify($passwordInput, $teacher['password'])) {
                $validTeacher = true;
            } elseif ($teacher['password'] === md5($passwordInput) || $teacher['password'] === $passwordInput) {
                $validTeacher = true;
                // Upgrade password to modern password_hash
                $newHash = password_hash($passwordInput, PASSWORD_DEFAULT);
                $upStmt = $conn->prepare("UPDATE tblclassteacher SET password = ? WHERE Id = ?");
                $upStmt->bind_param("si", $newHash, $teacher['Id']);
                $upStmt->execute();
                $upStmt->close();
            }
        }

        if ($validTeacher) {
            session_regenerate_id(true);
            $_SESSION['userId'] = $teacher['Id'];
            $_SESSION['userType'] = 'ClassTeacher';
            $_SESSION['firstName'] = $teacher['firstName'];
            $_SESSION['lastName'] = $teacher['lastName'];
            $_SESSION['emailAddress'] = $teacher['emailAddress'];
            $_SESSION['classId'] = $teacher['classId'];
            $_SESSION['classArmId'] = $teacher['classArmId'];

            unset($_SESSION['attendancePendingToken']);
            unset($_SESSION['attendancePendingResult']);
            echo '<script>window.location = "ClassTeacher/index.php";</script>';
            exit();
        } else {
            echo "<div class='alert alert-danger' role='alert'>Invalid teacher email or password!</div>";
        }

    } elseif ($userType === "Student") {

        $stmt = $conn->prepare("SELECT * FROM tblstudents WHERE TRIM(admissionNumber) = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $rs = $stmt->get_result();
        $student = $rs ? $rs->fetch_assoc() : null;
        $stmt->close();

        $studentCandidates = $student ? [$student] : [];
        if (!$student) {
            $stmt = $conn->prepare(
                "SELECT * FROM tblstudents
                 WHERE LOWER(TRIM(CONCAT(firstName, ' ', lastName))) = LOWER(?)"
            );
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $rs = $stmt->get_result();
            if ($rs) {
                while ($candidate = $rs->fetch_assoc()) {
                    $studentCandidates[] = $candidate;
                }
            }
            $stmt->close();
        }

        $matchingStudents = [];
        foreach ($studentCandidates as $candidate) {
            $passwordMatches = password_verify($passwordInput, $candidate['password'])
                || $candidate['password'] === md5($passwordInput)
                || $candidate['password'] === $passwordInput;
            if ($passwordMatches) {
                $matchingStudents[] = $candidate;
            }
        }

        $student = count($matchingStudents) === 1 ? $matchingStudents[0] : null;
        $validStudent = $student !== null;
        if ($validStudent) {
            if (!password_verify($passwordInput, $student['password'])) {
                // Upgrade legacy plaintext or MD5 passwords after a successful login.
                $newHash = password_hash($passwordInput, PASSWORD_DEFAULT);
                $upStmt = $conn->prepare("UPDATE tblstudents SET password = ? WHERE Id = ?");
                $upStmt->bind_param("si", $newHash, $student['Id']);
                $upStmt->execute();
                $upStmt->close();
            }

            session_regenerate_id(true);
            $_SESSION['userId'] = $student['Id'];
            $_SESSION['userType'] = 'Student';
            $_SESSION['firstName'] = $student['firstName'];
            $_SESSION['lastName'] = $student['lastName'];
            $_SESSION['admissionNumber'] = $student['admissionNumber'];
            $_SESSION['classId'] = $student['classId'];
            $_SESSION['classArmId'] = $student['classArmId'];

            $pendingAttendanceToken = $_SESSION['attendancePendingToken'] ?? '';
            if (is_string($pendingAttendanceToken)
                && preg_match('/\A[a-f0-9]{64}\z/i', $pendingAttendanceToken)) {
                $pendingAttendanceResult = $_SESSION['attendancePendingResult'] ?? '';
                unset($_SESSION['attendancePendingToken']);
                unset($_SESSION['attendancePendingResult']);
                $redirectUrl = 'Student/markAttendance.php?token=' . rawurlencode($pendingAttendanceToken);
                if ($pendingAttendanceResult === 'success') {
                    $redirectUrl .= '&result=success';
                } else {
                    $redirectUrl .= '&auto=1';
                }
            } else {
                unset($_SESSION['attendancePendingResult']);
                $redirectUrl = 'Student/index.php';
            }
            echo '<script>window.location = ' . json_encode($redirectUrl) . ';</script>';
            exit();
        } else {
            echo "<div class='alert alert-danger' role='alert'>Invalid student name/admission number or password. If multiple students share a name, use the admission number.</div>";
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
<script src="js/password-toggle.js"></script>
</body>
</html>
