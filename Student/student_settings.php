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
<?php if (($_GET['updated'] ?? '') === '1'): ?><div class="student-alert success mb-4" role="status">Password updated successfully. Use your new password the next time you sign in.</div><?php endif; ?>
<?php if ($errorMessage !== ''): ?><div class="alert alert-danger"><?= studentEscape($errorMessage) ?></div><?php endif; ?>
<div class="row">
    <div class="col-lg-7 mb-4">
        <section class="student-card">
            <h2 class="student-card-title">Change password</h2>
            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= studentEscape(studentCsrfToken()) ?>">
                <div class="form-group">
                    <label class="student-form-label" for="current-password">Current password</label>
                    <div class="student-password-field">
                        <input id="current-password" class="form-control student-form-control" type="password" name="current_password" required autocomplete="current-password">
                        <button class="student-password-toggle" type="button" data-password-toggle="current-password" data-password-label="current password" aria-label="Show current password" aria-pressed="false">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="student-form-label" for="new-password">New password</label>
                    <div class="student-password-field">
                        <input id="new-password" class="form-control student-form-control" type="password" name="new_password" minlength="8" required autocomplete="new-password">
                        <button class="student-password-toggle" type="button" data-password-toggle="new-password" data-password-label="new password" aria-label="Show new password" aria-pressed="false">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="student-form-label" for="confirm-password">Confirm new password</label>
                    <div class="student-password-field">
                        <input id="confirm-password" class="form-control student-form-control" type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
                        <button class="student-password-toggle" type="button" data-password-toggle="confirm-password" data-password-label="password confirmation" aria-label="Show password confirmation" aria-pressed="false">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
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
<style>
    .student-password-field { position: relative; }
    .student-password-field .student-form-control { padding-right: 48px; }
    .student-password-toggle {
        position: absolute;
        top: 50%;
        right: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        padding: 0;
        border: 0;
        border-radius: 8px;
        color: #64748b;
        background: transparent;
        transform: translateY(-50%);
        cursor: pointer;
    }
    .student-password-toggle:hover,
    .student-password-toggle:focus-visible { color: #2854cc; background: #edf2ff; }
    .student-password-toggle:focus-visible { outline: 2px solid #2854cc; outline-offset: 1px; }
</style>
<script>
    document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var input = document.getElementById(toggle.getAttribute('data-password-toggle'));
            var icon = toggle.querySelector('i');
            if (!input || !icon) {
                return;
            }

            var isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            toggle.setAttribute('aria-pressed', String(!isVisible));
            toggle.setAttribute(
                'aria-label',
                (isVisible ? 'Show ' : 'Hide ') + toggle.getAttribute('data-password-label')
            );
            icon.classList.toggle('fa-eye', isVisible);
            icon.classList.toggle('fa-eye-slash', !isVisible);
        });
    });
</script>
<?php require __DIR__ . '/Includes/student_page_end.php'; ?>
