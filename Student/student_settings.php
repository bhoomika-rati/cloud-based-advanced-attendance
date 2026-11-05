<?php
require_once __DIR__ . '/Includes/student_context.php';

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!studentVerifyCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Your form session expired. Refresh the page and try again.';
    } else {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $matchesCurrent = password_verify($currentPassword, (string)$student['password'])
            || (string)$student['password'] === md5($currentPassword)
            || (string)$student['password'] === $currentPassword;
        if (!$matchesCurrent) {
            $errorMessage = 'Your current password is incorrect.';
        } elseif (strlen($newPassword) < 8 || strlen($newPassword) > 4096) {
            $errorMessage = 'Your new password must be at least 8 characters long.';
        } elseif (!hash_equals($newPassword, $confirmPassword)) {
            $errorMessage = 'The new passwords do not match.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordStmt = $conn->prepare('UPDATE tblstudents SET password = ? WHERE Id = ?');
            if (!$passwordStmt) {
                error_log('Student password update could not be prepared: ' . $conn->error);
                http_response_code(500);
                $errorMessage = 'Unable to change your password right now.';
            } else {
                $passwordStmt->bind_param('si', $newHash, $studentId);
                if (!$passwordStmt->execute()) {
                    error_log('Student password update failed: ' . $passwordStmt->error);
                    http_response_code(500);
                    $errorMessage = 'Unable to change your password right now.';
                } else {
                    $passwordStmt->close();
                    header('Location: student_settings.php?updated=1');
                    exit;
                }
                $passwordStmt->close();
            }
        }
    }
}
$pageTitle = 'Settings';
require __DIR__ . '/Includes/student_page_start.php';
?>
<header class="student-page-heading"><div><h1>Settings</h1><p>Keep your account secure and your details up to date.</p></div></header>
<?php if (!empty($_GET['updated'])): ?><div class="student-alert success mb-4" role="status">Your password was changed successfully.</div><?php endif; ?>
<?php if ($errorMessage !== ''): ?><div class="alert alert-danger"><?= studentEscape($errorMessage) ?></div><?php endif; ?>
<div class="row">
    <div class="col-lg-7 mb-4">
        <section class="student-card">
            <h2 class="student-card-title">Change password</h2>
            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= studentEscape(studentCsrfToken()) ?>">
                <div class="form-group"><label class="student-form-label" for="current-password">Current password</label><input id="current-password" class="form-control student-form-control" type="password" name="current_password" required autocomplete="current-password"></div>
                <div class="form-group"><label class="student-form-label" for="new-password">New password</label><input id="new-password" class="form-control student-form-control" type="password" name="new_password" minlength="8" required autocomplete="new-password"></div>
                <div class="form-group"><label class="student-form-label" for="confirm-password">Confirm new password</label><input id="confirm-password" class="form-control student-form-control" type="password" name="confirm_password" minlength="8" required autocomplete="new-password"></div>
                <button class="student-action" type="submit"><i class="fas fa-lock"></i> Update password</button>
            </form>
        </section>
    </div>
    <div class="col-lg-5 mb-4">
        <section class="student-card">
            <h2 class="student-card-title">Account</h2>
            <p class="student-muted">Signed in as <strong><?= studentEscape($fullName) ?></strong>.</p>
            <p class="student-muted">Your attendance status can only be recorded by the attendance system or changed by authorized staff after review.</p>
            <a class="student-action secondary" href="student_profile.php"><i class="fas fa-user"></i> Edit profile</a>
        </section>
    </div>
</div>
<?php require __DIR__ . '/Includes/student_page_end.php'; ?>
