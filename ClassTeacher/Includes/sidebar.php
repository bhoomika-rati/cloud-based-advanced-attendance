<style>
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
    text-transform: uppercase;
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
      <a class="sidebar-brand d-flex align-items-center bg-gradient-primary justify-content-center" href="index.php">
        <div class="sidebar-brand-icon" >
          <img src="../img/logo/attendly-mark.svg" alt="">
        </div>
        <div class="sidebar-brand-text mx-3">Attendly</div>
      </a>
      <hr class="sidebar-divider my-0">
      <li class="nav-item active">
        <a class="nav-link" href="index.php">
          <i class="fas fa-fw fa-tachometer-alt"></i>
          <span>Dashboard</span></a>
      </li> 
      <hr class="sidebar-divider">
      <div class="sidebar-heading">
        Students
      </div>
      </li>
       <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBootstrap2"
          aria-expanded="true" aria-controls="collapseBootstrap2">
          <i class="fas fa-user-graduate"></i>
          <span>My Students</span>
        </a>
        <div id="collapseBootstrap2" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">My Students</h6>
            <a class="collapse-item" href="viewStudents.php">View Students</a>
            <!-- <a class="collapse-item" href="#">Assets Type</a> -->
          </div>
        </div>
      </li>
      <hr class="sidebar-divider">
      <div class="sidebar-heading">
      Attendance
      </div>
      </li>
       <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBootstrapcon"
          aria-expanded="true" aria-controls="collapseBootstrapcon">
          <i class="fa fa-calendar-alt"></i>
          <span>Manage Attendance</span>
        </a>
        <div id="collapseBootstrapcon" class="collapse" aria-labelledby="headingBootstrap" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Manage Attendance</h6>
            <a class="collapse-item" href="qrCodeAttendance.php">QR_Code Attendance</a>
            <a class="collapse-item" href="viewStudentAttendance.php">View Student Attendance</a>
            <a class="collapse-item" href="downloadRecord.php">Today's Report (xls)</a>
            <!-- <a class="collapse-item" href="addMemberToContLevel.php ">Add Member to Level</a> -->
          </div>
        </div>
      </li>

     
      <!-- <li class="nav-item">
        <a class="nav-link" href="forms.html">
          <i class="fab fa-fw fa-wpforms"></i>
          <span>Forms</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTable" aria-expanded="true"
          aria-controls="collapseTable">
          <i class="fas fa-fw fa-table"></i>
          <span>Tables</span>
        </a>
        <div id="collapseTable" class="collapse" aria-labelledby="headingTable" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Tables</h6>
            <a class="collapse-item" href="simple-tables.html">Simple Tables</a>
            <a class="collapse-item" href="datatables.html">DataTables</a>
          </div>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="ui-colors.html">
          <i class="fas fa-fw fa-palette"></i>
          <span>UI Colors</span>
        </a>
      </li>
      <hr class="sidebar-divider">
      <div class="sidebar-heading">
        Examples
      </div>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePage" aria-expanded="true"
          aria-controls="collapsePage">
          <i class="fas fa-fw fa-columns"></i>
          <span>Pages</span>
        </a>
        <div id="collapsePage" class="collapse" aria-labelledby="headingPage" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Example Pages</h6>
            <a class="collapse-item" href="login.html">Login</a>
            <a class="collapse-item" href="register.html">Register</a>
            <a class="collapse-item" href="404.html">404 Page</a>
            <a class="collapse-item" href="blank.html">Blank Page</a>
          </div>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="charts.html">
          <i class="fas fa-fw fa-chart-area"></i>
          <span>Charts</span>
        </a>
      </li> -->
      <hr class="sidebar-divider">
     
    </ul>
    <script>
      (function () {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        const sidebarLinks = document.querySelectorAll('.sidebar .nav-link, .sidebar .collapse-item');
        sidebarLinks.forEach(function (link) {
          const href = link.getAttribute('href');
          if (href && href.split('/').pop() === currentPage) {
            const navItem = link.closest('.nav-item');
            if (navItem) navItem.classList.add('active');
            const collapseTarget = link.closest('.collapse');
            if (collapseTarget) collapseTarget.classList.add('show');
            const toggle = collapseTarget && collapseTarget.previousElementSibling;
            if (toggle) {
              toggle.classList.remove('collapsed');
              toggle.setAttribute('aria-expanded', 'true');
            }
          }
        });

        const sidebarToggle = document.getElementById('sidebarToggleTop');
        const sidebar = document.querySelector('.sidebar');
        if (sidebarToggle && sidebar) {
          sidebarToggle.addEventListener('click', function (event) {
            event.preventDefault();
            document.body.classList.toggle('sidebar-toggled');
            sidebar.classList.toggle('toggled');
            if (sidebar.classList.contains('toggled')) {
              document.querySelectorAll('.sidebar .collapse').forEach(function (item) {
                item.classList.remove('show');
              });
            }
          });
        }

        document.addEventListener('click', function (event) {
          const mobileOpen = window.innerWidth < 768 && document.body.classList.contains('sidebar-toggled');
          if (!mobileOpen) return;
          const sidebarEl = document.querySelector('.sidebar');
          const toggleButton = document.getElementById('sidebarToggleTop');
          if (!sidebarEl || !toggleButton) return;
          if (!sidebarEl.contains(event.target) && !toggleButton.contains(event.target)) {
            document.body.classList.remove('sidebar-toggled');
            sidebarEl.classList.remove('toggled');
          }
        });
      })();
    </script>