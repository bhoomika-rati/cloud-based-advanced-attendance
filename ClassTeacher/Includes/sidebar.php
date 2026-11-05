 <ul class="navbar-nav sidebar sidebar-light accordion" id="accordionSidebar">
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
            <a class="collapse-item" href="takeAttendance.php">Take Attendance</a>
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