<?php
require_once __DIR__ . '/Includes/student_context.php';

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!studentVerifyCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        $errorMessage = 'Your form session expired. Refresh the page and try again.';
    } else {
        $email = trim((string)($_POST['email_address'] ?? ''));
        $phone = trim((string)($_POST['phone_number'] ?? ''));
        $department = trim((string)($_POST['department'] ?? ''));
        $semester = trim((string)($_POST['semester'] ?? ''));
        $academicYear = trim((string)($_POST['academic_year'] ?? ''));
        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254)) {
            $errorMessage = 'Enter a valid email address.';
        } elseif (strlen($phone) > 40 || ($phone !== '' && !preg_match('/\A[+0-9().\-\s]{5,40}\z/', $phone))) {
            $errorMessage = 'Enter a valid phone number.';
        } elseif (strlen($department) > 191) {
            $errorMessage = 'Department must be 191 characters or fewer.';
        } elseif (strlen($semester) > 40) {
            $errorMessage = 'Semester must be 40 characters or fewer.';
        } elseif (strlen($academicYear) > 40) {
            $errorMessage = 'Academic year must be 40 characters or fewer.';
        } else {
            $photoPath = (string)($student['photo_path'] ?? '');
            $uploadedPhoto = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK || $_FILES['photo']['size'] > 2 * 1024 * 1024) {
                    $errorMessage = 'Choose a profile image smaller than 2 MB.';
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($_FILES['photo']['tmp_name']);
                    $allowedImages = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (!isset($allowedImages[$mime])) {
                        $errorMessage = 'Upload a JPEG, PNG or WebP profile image.';
                    } else {
                        $relativeDirectory = 'uploads/student_profiles/' . $studentId;
                        $absoluteDirectory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads'
                            . DIRECTORY_SEPARATOR . 'student_profiles' . DIRECTORY_SEPARATOR . $studentId;
                        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0750, true) && !is_dir($absoluteDirectory)) {
                            $errorMessage = 'Unable to prepare profile image storage.';
                        } else {
                            $fileName = bin2hex(random_bytes(16)) . '.' . $allowedImages[$mime];
                            $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $fileName;
                            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $absolutePath)) {
                                $errorMessage = 'Unable to save the profile image.';
                            } else {
                                $uploadedPhoto = $absolutePath;
                                $photoPath = $relativeDirectory . '/' . $fileName;
                            }
                        }
                    }
                }
            }

            if ($errorMessage === '') {
                $profileStmt = $conn->prepare(
                    "INSERT INTO student_profiles
                        (student_id, email_address, phone_number, department, semester, academic_year, photo_path)
                     VALUES (?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE email_address = VALUES(email_address),
                         phone_number = VALUES(phone_number), department = VALUES(department),
                         semester = VALUES(semester), academic_year = VALUES(academic_year),
                         photo_path = VALUES(photo_path)"
                );
                if (!$profileStmt) {
                    if ($uploadedPhoto !== null && is_file($uploadedPhoto)) {
                        unlink($uploadedPhoto);
                    }
                    error_log('Student profile update could not be prepared: ' . $conn->error);
                    http_response_code(500);
                    $errorMessage = 'Unable to update your profile right now.';
                } else {
                    $profileStmt->bind_param(
                        'issssss',
                        $studentId,
                        $email,
                        $phone,
                        $department,
                        $semester,
                        $academicYear,
                        $photoPath
                    );
                    if (!$profileStmt->execute()) {
                        if ($uploadedPhoto !== null && is_file($uploadedPhoto)) {
                            unlink($uploadedPhoto);
                        }
                        error_log('Student profile update failed: ' . $profileStmt->error);
                        http_response_code(500);
                        $errorMessage = 'Unable to update your profile right now.';
                    } else {
                        $profileStmt->close();
                        header('Location: student_profile.php?updated=1');
                        exit;
                    }
                    $profileStmt->close();
                }
            }
        }
    }
}
$pageTitle = 'My Profile';
require __DIR__ . '/Includes/student_page_start.php';
?>
<header class="student-page-heading"><div><h1>My profile</h1><p>Your personal and academic information.</p></div></header>
<?php if (($_GET['updated'] ?? '') === '1'): ?><div class="student-alert success mb-4" role="status">Your contact and academic profile details were saved.</div><?php endif; ?>
<?php if ($errorMessage !== ''): ?><div class="alert alert-danger"><?= studentEscape($errorMessage) ?></div><?php endif; ?>
<div class="row">
    <div class="col-xl-4 mb-4">
        <section class="student-card text-center">
            <?php if (!empty($student['photo_path'])): ?>
                <img class="student-photo mb-3" src="<?= studentEscape($student['photo_path']) ?>" alt="Student profile photograph">
            <?php else: ?>
                <div class="student-profile-avatar mx-auto mb-3" style="width:112px;height:112px;flex-basis:112px;border-radius:28px;font-size:2rem">
                    <?= studentEscape(strtoupper(substr($student['firstName'], 0, 1) . substr($student['lastName'], 0, 1))) ?>
                </div>
            <?php endif; ?>
            <h2 class="h5 fw-bold"><?= studentEscape($fullName) ?></h2>
            <p class="student-muted"><?= studentEscape($admissionNumber) ?></p>
            <div class="text-left">
                <div class="profile-row"><span class="student-muted">Department</span><strong><?= studentEscape($student['department'] ?: 'Not provided') ?></strong></div>
                <div class="profile-row"><span class="student-muted">Semester</span><strong><?= studentEscape($student['semester'] ?: 'Not provided') ?></strong></div>
                <div class="profile-row"><span class="student-muted">Academic year</span><strong><?= studentEscape($student['academic_year'] ?: 'Not provided') ?></strong></div>
                <div class="profile-row"><span class="student-muted">Class / group</span><strong><?= studentEscape(($student['className'] ?: '—') . ' / ' . ($student['classArmName'] ?: '—')) ?></strong></div>
            </div>
        </section>
    </div>
    <div class="col-xl-8 mb-4">
        <section class="student-card">
            <h2 class="student-card-title">Contact and academic details</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= studentEscape(studentCsrfToken()) ?>">
                <div class="form-group">
                    <label class="student-form-label" for="department">Department</label>
                    <input class="form-control student-form-control" id="department" type="text" name="department" maxlength="191" value="<?= studentEscape($_POST['department'] ?? $student['department'] ?? '') ?>" autocomplete="organization-title">
                </div>
                <div class="form-group">
                    <label class="student-form-label" for="semester">Semester</label>
                    <input class="form-control student-form-control" id="semester" type="text" name="semester" maxlength="40" value="<?= studentEscape($_POST['semester'] ?? $student['semester'] ?? '') ?>" placeholder="For example, Semester 1">
                </div>
                <div class="form-group">
                    <label class="student-form-label" for="academic-year">Academic year</label>
                    <input class="form-control student-form-control" id="academic-year" type="text" name="academic_year" maxlength="40" value="<?= studentEscape($_POST['academic_year'] ?? $student['academic_year'] ?? '') ?>" placeholder="For example, 2026–2027">
                </div>
                <div class="form-group">
                    <label class="student-form-label" for="email-address">Email address</label>
                    <input class="form-control student-form-control" id="email-address" type="email" name="email_address" maxlength="254" value="<?= studentEscape($_POST['email_address'] ?? $student['email_address'] ?? '') ?>" autocomplete="email">
                </div>
                <div class="form-group">
                    <label class="student-form-label" for="phone-number">Phone number</label>
                    <input class="form-control student-form-control" id="phone-number" type="tel" name="phone_number" maxlength="40" value="<?= studentEscape($_POST['phone_number'] ?? $student['phone_number'] ?? '') ?>" autocomplete="tel">
                </div>
                <div class="form-group">
                    <label class="student-form-label" for="photo">Profile photograph <span class="student-muted font-weight-normal">(JPEG, PNG or WebP; maximum 2 MB)</span></label>
                    <input class="form-control d-block" id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                </div>
                <button class="student-action" type="submit"><i class="fas fa-save"></i> Save profile</button>
                <a class="student-action secondary ms-2" href="student_settings.php">Account settings</a>
            </form>
        </section>
    </div>
</div>
<?php require __DIR__ . '/Includes/student_page_end.php'; ?>
