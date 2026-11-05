<?php
$topbarName = trim((string)($_SESSION['firstName'] ?? '') . ' ' . (string)($_SESSION['lastName'] ?? ''));
if (basename($_SERVER['PHP_SELF']) === 'student_timetable.php'):
if (isset($conn) && $conn instanceof mysqli) {
    $topbarStudentId = (int)($_SESSION['userId'] ?? 0);
    $topbarStmt = $conn->prepare('SELECT firstName, lastName FROM tblstudents WHERE Id = ? LIMIT 1');
    if ($topbarStmt) {
        $topbarStmt->bind_param('i', $topbarStudentId);
        $topbarStmt->execute();
        $topbarStudent = $topbarStmt->get_result()->fetch_assoc();
        if ($topbarStudent) {
            $topbarName = trim($topbarStudent['firstName'] . ' ' . $topbarStudent['lastName']);
        }
        $topbarStmt->close();
    } else {
        error_log('Student timetable topbar name query could not be prepared: ' . $conn->error);
    }
}
?>
<nav class="navbar navbar-expand navbar-light bg-gradient-primary topbar mb-4 static-top">
    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>
    <div class="text-white big" style="margin-left:100px"></div>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false"><i class="fas fa-search fa-fw"></i></a>
            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                <form class="navbar-search"><div class="input-group">
                    <input type="text" class="form-control bg-light border-1 small" placeholder="What do you want to look for?"
                           aria-label="Search" aria-describedby="basic-addon2" style="border-color:#3f51b5">
                    <div class="input-group-append"><button class="btn btn-primary" type="button"><i class="fas fa-search fa-sm"></i></button></div>
                </div></form>
            </div>
        </li>
        <div class="topbar-divider d-none d-sm-block"></div>
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <img class="img-profile rounded-circle" src="img/user-icn.png" style="max-width:60px" alt="">
                <span class="ml-2 d-none d-lg-inline text-white small"><b>Welcome <?= htmlspecialchars($topbarName, ENT_QUOTES, 'UTF-8') ?></b></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="logout.php"><i class="fas fa-power-off fa-fw mr-2 text-danger"></i>Logout</a>
            </div>
        </li>
    </ul>
</nav>
<?php else: ?>
<nav class="navbar navbar-expand navbar-light student-topbar topbar mb-3">
    <button id="sidebarToggleTop" class="btn btn-link rounded-circle me-3" aria-label="Toggle navigation"><i class="fa fa-bars"></i></button>
    <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="student-profile-avatar me-2" style="width:36px;height:36px;flex-basis:36px;border-radius:12px;font-size:.8rem">
                    <?= htmlspecialchars(strtoupper(substr($_SESSION['firstName'] ?? 'S', 0, 1) . substr($_SESSION['lastName'] ?? '', 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="topbar-name d-none d-sm-inline">Welcome, <?= htmlspecialchars($topbarName, ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="student_profile.php"><i class="fas fa-user me-2 text-secondary"></i>My profile</a>
                <a class="dropdown-item" href="student_settings.php"><i class="fas fa-cog me-2 text-secondary"></i>Settings</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="logout.php"><i class="fas fa-power-off me-2 text-danger"></i>Logout</a>
            </div>
        </li>
    </ul>
</nav>
<?php endif; ?>
