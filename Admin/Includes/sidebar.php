
<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

$activeGroups = [
    'administrator_profile.php' => 'collapseAdminProfile',
    'createClass.php' => 'collapseBootstrap',
    'createClassArms.php' => 'collapseBootstrapusers',
    'createClassTeacher.php' => 'collapseBootstrapassests',
    'createStudents.php' => 'collapseBootstrap2',
    'createCourse.php' => 'collapseCourseModel',
    'createModel.php' => 'collapseCourseModel',
    'createSessionTerm.php' => 'collapseBootstrapcon',
    'createTimetable.php' => 'collapseTimetable',
    'viewTimetable.php' => 'collapseTimetable',
    'timetableDaysOf.php' => 'collapseTimetable',
    'Attendance_Statistics.php' => 'collapseAttendance',
];

$activeGroup = $activeGroups[$currentPage] ?? '';
?>

<style>
/* Advanced Attendance sidebar */
#accordionSidebar.aa-sidebar {
    background: #192b49 !important;
    background-image: none !important;
    width: 250px;
    min-width: 250px;
    min-height: 100vh;
    padding: 18px 14px;
    box-shadow: 5px 0 24px rgba(15, 23, 42, .08);
}

#accordionSidebar.aa-sidebar .sidebar-brand {
    background: transparent !important;
    height: auto;
    min-height: 74px;
    margin-bottom: 24px;
    justify-content: flex-start !important;
    padding: 8px 12px;
    white-space: normal;
}

#accordionSidebar.aa-sidebar .sidebar-brand-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: #2858d8;
    color: #fff;
    font-size: 21px;
}

#accordionSidebar.aa-sidebar .sidebar-brand-icon img {
    width: 30px;
    height: 30px;
    object-fit: contain;
    border-radius: 6px;
}

#accordionSidebar.aa-sidebar .sidebar-brand-text {
    color: #fff;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -.3px;
    margin-left: 10px !important;
}

#accordionSidebar.aa-sidebar .sidebar-divider {
    border-top: 1px solid rgba(255,255,255,.12);
    margin: 12px 5px;
}

#accordionSidebar.aa-sidebar .sidebar-heading {
    color: #9eacc5;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.5px;
    padding: 10px 12px 7px;
}

#accordionSidebar.aa-sidebar .nav-item {
    margin: 3px 0;
}

#accordionSidebar.aa-sidebar .nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 14px;
    min-height: 46px;
    border-radius: 9px;
    color: #d5deed !important;
    font-size: 13px;
    font-weight: 600;
    transition: background .2s ease, color .2s ease;
}

#accordionSidebar.aa-sidebar .nav-link i {
    width: 20px;
    text-align: center;
    color: #b9c8e2;
    font-size: 16px;
}

#accordionSidebar.aa-sidebar .nav-link:hover {
    background: rgba(255,255,255,.09);
    color: #fff !important;
}

#accordionSidebar.aa-sidebar .nav-item.active > .nav-link,
#accordionSidebar.aa-sidebar .nav-item > .nav-link[aria-expanded="true"] {
    background: #2854cc !important;
    color: #fff !important;
    box-shadow: 0 5px 14px rgba(0,0,0,.12);
}

#accordionSidebar.aa-sidebar .nav-item.active > .nav-link i,
#accordionSidebar.aa-sidebar .nav-link[aria-expanded="true"] i {
    color: #fff;
}

#accordionSidebar.aa-sidebar .collapse-inner {
    background: #213655 !important;
    border-radius: 8px !important;
    margin: 5px 0 8px;
    padding: 8px !important;
}

#accordionSidebar.aa-sidebar .collapse-header {
    color: #9eacc5 !important;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

#accordionSidebar.aa-sidebar .collapse-item {
    color: #d5deed !important;
    border-radius: 6px;
    padding: 9px 10px;
    font-size: 12px;
}

#accordionSidebar.aa-sidebar .collapse-item:hover,
#accordionSidebar.aa-sidebar .collapse-item.active {
    background: #2854cc !important;
    color: #fff !important;
}

@media (max-width: 768px) {
    #accordionSidebar.aa-sidebar {
        width: 100%;
        min-width: 0;
        min-height: auto;
    }
}
</style>

<ul class="navbar-nav sidebar sidebar-dark accordion aa-sidebar" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center" href="index.php">
        <div class="sidebar-brand-icon">
            <img src="../img/logo/attendly-mark.svg" alt="">
        </div>
        <div class="sidebar-brand-text">Attendly</div>
    </a>

    <li class="nav-item <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
        <a class="nav-link" href="index.php">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">ADMINISTRATION</div>

    <li class="nav-item <?php echo $activeGroup === 'collapseAdminProfile' ? 'active' : ''; ?>">
        <a class="nav-link <?php echo $activeGroup === 'collapseAdminProfile' ? '' : 'collapsed'; ?>"
           href="#" data-toggle="collapse" data-target="#collapseAdminProfile"
           aria-expanded="<?php echo $activeGroup === 'collapseAdminProfile' ? 'true' : 'false'; ?>"
           aria-controls="collapseAdminProfile">
            <i class="fas fa-user-cog"></i><span>Administrator Profile</span>
        </a>
        <div id="collapseAdminProfile" class="collapse <?php echo $activeGroup === 'collapseAdminProfile' ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="collapse-inner rounded">
                <h6 class="collapse-header">Administrator Profile</h6>
                <a class="collapse-item <?php echo $currentPage === 'administrator_profile.php' ? 'active' : ''; ?>" href="administrator_profile.php">Go To My Profile</a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">CLASSES</div>

    <li class="nav-item <?php echo $activeGroup === 'collapseBootstrap' ? 'active' : ''; ?>">
        <a class="nav-link <?php echo $activeGroup === 'collapseBootstrap' ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseBootstrap" aria-expanded="<?php echo $activeGroup === 'collapseBootstrap' ? 'true' : 'false'; ?>" aria-controls="collapseBootstrap">
            <i class="fas fa-chalkboard"></i><span>Manage Classes</span>
        </a>
        <div id="collapseBootstrap" class="collapse <?php echo $activeGroup === 'collapseBootstrap' ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="collapse-inner rounded">
                <h6 class="collapse-header">Manage Classes</h6>
                <a class="collapse-item <?php echo $currentPage === 'createClass.php' ? 'active' : ''; ?>" href="createClass.php">Create Class</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?php echo $activeGroup === 'collapseBootstrapusers' ? 'active' : ''; ?>">
        <a class="nav-link <?php echo $activeGroup === 'collapseBootstrapusers' ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseBootstrapusers" aria-expanded="<?php echo $activeGroup === 'collapseBootstrapusers' ? 'true' : 'false'; ?>" aria-controls="collapseBootstrapusers">
            <i class="fas fa-code-branch"></i><span>Manage Class Groups</span>
        </a>
        <div id="collapseBootstrapusers" class="collapse <?php echo $activeGroup === 'collapseBootstrapusers' ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="collapse-inner rounded">
                <h6 class="collapse-header">Class Groups</h6>
                <a class="collapse-item <?php echo $currentPage === 'createClassArms.php' ? 'active' : ''; ?>" href="createClassArms.php">Create Class Groups</a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">PEOPLE</div>

    <li class="nav-item <?php echo $activeGroup === 'collapseBootstrapassests' ? 'active' : ''; ?>">
        <a class="nav-link <?php echo $activeGroup === 'collapseBootstrapassests' ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseBootstrapassests" aria-expanded="<?php echo $activeGroup === 'collapseBootstrapassests' ? 'true' : 'false'; ?>" aria-controls="collapseBootstrapassests">
            <i class="fas fa-chalkboard-teacher"></i><span>Manage Teachers</span>
        </a>
        <div id="collapseBootstrapassests" class="collapse <?php echo $activeGroup === 'collapseBootstrapassests' ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="collapse-inner rounded">
                <h6 class="collapse-header">Teachers</h6>
                <a class="collapse-item <?php echo $currentPage === 'createClassTeacher.php' ? 'active' : ''; ?>" href="createClassTeacher.php">Create Class Teachers</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?php echo $activeGroup === 'collapseBootstrap2' ? 'active' : ''; ?>">
        <a class="nav-link <?php echo $activeGroup === 'collapseBootstrap2' ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseBootstrap2" aria-expanded="<?php echo $activeGroup === 'collapseBootstrap2' ? 'true' : 'false'; ?>" aria-controls="collapseBootstrap2">
            <i class="fas fa-user-graduate"></i><span>Manage Students</span>
        </a>
        <div id="collapseBootstrap2" class="collapse <?php echo $activeGroup === 'collapseBootstrap2' ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="collapse-inner rounded">
                <h6 class="collapse-header">Students</h6>
                <a class="collapse-item <?php echo $currentPage === 'createStudents.php' ? 'active' : ''; ?>" href="createStudents.php">Create Students</a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">ACADEMICS</div>

    <li class="nav-item <?php echo $activeGroup === 'collapseCourseModel' ? 'active' : ''; ?>">
        <a class="nav-link <?php echo $activeGroup === 'collapseCourseModel' ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseCourseModel" aria-expanded="<?php echo $activeGroup === 'collapseCourseModel' ? 'true' : 'false'; ?>" aria-controls="collapseCourseModel">
            <i class="fas fa-book"></i><span>Courses and Modules</span>
        </a>
        <div id="collapseCourseModel" class="collapse <?php echo $activeGroup === 'collapseCourseModel' ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="collapse-inner rounded">
                <h6 class="collapse-header">Courses and Modules</h6>
                <a class="collapse-item <?php echo $currentPage === 'createCourse.php' ? 'active' : ''; ?>" href="createCourse.php">Manage Courses</a>
                <a class="collapse-item <?php echo $currentPage === 'createModel.php' ? 'active' : ''; ?>" href="createModel.php">Manage Modules</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?php echo $activeGroup === 'collapseBootstrapcon' ? 'active' : ''; ?>">
        <a class="nav-link <?php echo $activeGroup === 'collapseBootstrapcon' ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseBootstrapcon" aria-expanded="<?php echo $activeGroup === 'collapseBootstrapcon' ? 'true' : 'false'; ?>" aria-controls="collapseBootstrapcon">
            <i class="fas fa-calendar-alt"></i><span>Manage Sessions</span>
        </a>
        <div id="collapseBootstrapcon" class="collapse <?php echo $activeGroup === 'collapseBootstrapcon' ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="collapse-inner rounded">
                <h6 class="collapse-header">Sessions</h6>
                <a class="collapse-item <?php echo $currentPage === 'createSessionTerm.php' ? 'active' : ''; ?>" href="createSessionTerm.php">Create Session</a>
            </div>
        </div>
    </li>

    <li class="nav-item <?php echo $activeGroup === 'collapseTimetable' ? 'active' : ''; ?>">
        <a class="nav-link <?php echo $activeGroup === 'collapseTimetable' ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseTimetable" aria-expanded="<?php echo $activeGroup === 'collapseTimetable' ? 'true' : 'false'; ?>" aria-controls="collapseTimetable">
            <i class="fas fa-clock"></i><span>Manage Timetables</span>
        </a>
        <div id="collapseTimetable" class="collapse <?php echo $activeGroup === 'collapseTimetable' ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="collapse-inner rounded">
                <h6 class="collapse-header">Timetables</h6>
                <a class="collapse-item <?php echo $currentPage === 'createTimetable.php' ? 'active' : ''; ?>" href="createTimetable.php">Create Timetable</a>
                <a class="collapse-item <?php echo $currentPage === 'viewTimetable.php' ? 'active' : ''; ?>" href="viewTimetable.php">View Timetable</a>
                <a class="collapse-item <?php echo $currentPage === 'timetableDaysOf.php' ? 'active' : ''; ?>" href="timetableDaysOf.php">Timetable Days Off</a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">REPORTS</div>

    <li class="nav-item <?php echo $activeGroup === 'collapseAttendance' ? 'active' : ''; ?>">
        <a class="nav-link <?php echo $activeGroup === 'collapseAttendance' ? '' : 'collapsed'; ?>" href="#" data-toggle="collapse" data-target="#collapseAttendance" aria-expanded="<?php echo $activeGroup === 'collapseAttendance' ? 'true' : 'false'; ?>" aria-controls="collapseAttendance">
            <i class="fas fa-chart-bar"></i><span>Attendance Statistics</span>
        </a>
        <div id="collapseAttendance" class="collapse <?php echo $activeGroup === 'collapseAttendance' ? 'show' : ''; ?>" data-parent="#accordionSidebar">
            <div class="collapse-inner rounded">
                <h6 class="collapse-header">Attendance Statistics</h6>
                <a class="collapse-item <?php echo $currentPage === 'Attendance_Statistics.php' ? 'active' : ''; ?>" href="Attendance_Statistics.php">View Statistics</a>
            </div>
        </div>
    </li>
</ul>
