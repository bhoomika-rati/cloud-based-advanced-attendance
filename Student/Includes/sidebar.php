<?php
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage === 'student_timetable.php'):
?>
<style>
  #accordionSidebar { background: #192b49 !important; background-image: none !important; }
  #accordionSidebar .sidebar-brand { background: transparent !important; }
  #accordionSidebar .sidebar-divider { border-top-color: rgba(255,255,255,.12); }
  #accordionSidebar .sidebar-heading { color: #9eacc5 !important; }
  #accordionSidebar .nav-link { color: #d5deed !important; }
  #accordionSidebar .nav-link i { color: #b9c8e2 !important; }
  #accordionSidebar .nav-item.active > .nav-link, #accordionSidebar .nav-link:hover { background: #2854cc !important; color: #fff !important; }
  #accordionSidebar .nav-item.active > .nav-link i, #accordionSidebar .nav-link:hover i { color: #fff !important; }
</style>
<?php endif; ?>
<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon">
            <img src="../img/logo/attendly-mark.svg" alt="Attendly">
        </div>
        <div class="sidebar-brand-text mx-3">Attendly</div>
    </a>
    <hr class="sidebar-divider my-0">
    <li class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
        <a class="nav-link" href="index.php" aria-label="Dashboard" title="Dashboard"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a>
    </li>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Attendance</div>
    <li class="nav-item <?= $currentPage === 'student_attendance.php' ? 'active' : '' ?>">
        <a class="nav-link" href="student_attendance.php" aria-label="My Attendance" title="My Attendance"><i class="fas fa-clipboard-list"></i><span>My Attendance</span></a>
    </li>
    <li class="nav-item <?= $currentPage === 'attendance_reports.php' ? 'active' : '' ?>">
        <a class="nav-link" href="attendance_reports.php" aria-label="Attendance Reports" title="Attendance Reports"><i class="fas fa-chart-pie"></i><span>Attendance Reports</span></a>
    </li>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Academics &amp; account</div>
    <li class="nav-item <?= $currentPage === 'student_timetable.php' ? 'active' : '' ?>">
        <a class="nav-link" href="student_timetable.php" aria-label="View My Timetable" title="View My Timetable">
            <i class="fa fa-calendar-alt"></i><span>View My Timetable</span>
        </a>
    </li>
    <li class="nav-item <?= $currentPage === 'student_notifications.php' ? 'active' : '' ?>">
        <a class="nav-link" href="student_notifications.php" aria-label="Notifications" title="Notifications"><i class="fas fa-bell"></i><span>Notifications</span></a>
    </li>
    <li class="nav-item <?= $currentPage === 'student_profile.php' ? 'active' : '' ?>">
        <a class="nav-link" href="student_profile.php" aria-label="My Profile" title="My Profile"><i class="fas fa-user-circle"></i><span>My Profile</span></a>
    </li>
    <li class="nav-item <?= $currentPage === 'student_settings.php' ? 'active' : '' ?>">
        <a class="nav-link" href="student_settings.php" aria-label="Settings" title="Settings"><i class="fas fa-sliders-h"></i><span>Settings</span></a>
    </li>
    <li class="nav-item <?= $currentPage === 'student_support.php' ? 'active' : '' ?>">
        <a class="nav-link" href="student_support.php" aria-label="Help and Support" title="Help &amp; Support"><i class="fas fa-life-ring"></i><span>Help &amp; Support</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="logout.php" aria-label="Logout" title="Logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </li>
</ul>
