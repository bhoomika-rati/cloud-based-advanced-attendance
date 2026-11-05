# 📚 Attendly | Attendance Management System

A comprehensive web-based attendance management system designed for educational institutions to efficiently track and manage student attendance.

## Landing page

The site root opens the responsive Attendly landing page. Its role-specific sign-in links reuse the existing login flows: administrators and students use the shared login page, and faculty use `classTeacherLogin.php`. Update the institution name and contact placeholders in `landing.php` before deployment.

For the complete Admin sidebar guide, including what each page does and step-by-step instructions for adding data, see [admin.md](./admin.md).

## 🎯 Overview

This web application provides a complete solution for managing student attendance in educational institutions. It offers different interfaces for administrators, teachers, and students, enabling seamless attendance tracking, timetable management, and statistical reporting.

## ✨ Features

### 👨‍💼 Administrator Module
- **Dashboard**: Overview of institutional statistics and information
- **User Management**: 
  - Create, update, and delete classes
  - Manage class groups (arms)
  - Add and manage teachers
  - Register and manage students
- **Academic Management**:
  - Manage courses and modules
  - Configure sessions (class periods)
  - Create and manage timetables
  - Set up holidays and days off
- **Reporting**: View QR attendance session and GPS verification reports

## Admin Dashboard Guide

This guide documents the administrator dashboard and the fields on its current pages. It describes the application as implemented, including places where a label is broader than the actual behavior.

### Dashboard home

The **Attendance Dashboard** home page contains four summary cards:

| Card | Meaning |
|---|---|
| **Total Students** | Number of registered student records. |
| **Present Today** | Attendance records dated today with status `0` (present in the dashboard). |
| **Absent Today** | Attendance records dated today with status `1` (absent in the dashboard). |
| **Attendance Records** | Total attendance entries, not the number of students. |

The **Attendance Log** lists recent records and provides:

- **Student Name** — joined from the student record.
- **Admission No.** — the student's admission number.
- **Class / Group** — class and group IDs recorded on the attendance row.
- **Date** — when the record was taken.
- **Status** — Present or Absent.
- **Search and pagination** — search by student name or admission number and browse the results 10 records per page.

**Export CSV** downloads up to the 100 most recent records with Attendance ID, Admission Number, Student Name, Class ID, Class Group ID, Date, and Status. The bell icon is displayed as a notification affordance; the dashboard currently has no notification feed.

### Navigation and recommended setup order

The sidebar groups admin pages into:

1. **Administration** — Administrator Profile.
2. **Classes** — Manage Classes and Manage Class Groups.
3. **People** — Manage Teachers and Manage Students.
4. **Academics** — Courses and Modules, Manage Sessions, and Manage Timetables.
5. **QR & GPS Attendance** — Session and GPS reports, and college location settings.

For a new setup, create classes and class groups first, then modules/courses, teachers and students, and finally sessions and timetables. Sessions refer to courses and teachers; timetables associate sessions with a class group and date range.

### Administrator Profile

**Update Profile** contains:

- **First Name** and **Last Name** — administrator display name.
- **Email Address** — administrator login email.
- **New Password** and **Confirm Password** — replacement password; the page checks that the two values match before saving.

The top bar displays the signed-in administrator's name and has a **Logout** action. It also displays a search control.

### Manage Classes

**Create Class** has one field:

- **Class Name** — enter a distinct name prefixed with `UG - ` or `PG - `, for example `UG - BSc Computer Science Year 1` or `PG - MSc Computer Science Year 1`. Sample names can be chosen from the input suggestions.

The class list shows undergraduate and postgraduate classes and edit/delete actions. A class is the parent of its class groups, and students and teachers are assigned to those groups. Older school-grade class rows (such as Seven and Eight) are hidden from the admin class list and new class/group assignment selectors, but retained in the database so existing student and attendance links are not lost. Sample cohort names are generic placeholders; replace them with the institution's actual programme, year, or cohort labels as needed.

### Manage Class Groups

**Create Class Groups** has:

- **Select Class** — the parent class.
- **Class Group Name** — a section/arm within that class, such as `A` or `N1`.

The list shows the class and group names. Students and teachers are assigned to class groups.

### Manage Teachers

**Create Class Teachers** has:

- **Firstname** and **Lastname** — teacher identity.
- **Email Address** — teacher login name.
- **Phone No** — contact number.
- **Password** — initial password when creating a teacher; on edit, leave blank to keep the current password.
- **Course** — a course selected from Manage Courses.
- **Module** — a module selected from Manage Modules.
- **Select Class** and **Class Group** — choose a class, then one of its groups.
- **Selected Class Group** — use **Add to Selection** to add the chosen group. Multiple groups can be selected.

The teacher list shows name, email, phone, assigned groups, course, and module, with edit/delete actions. Course and module are separate selections on this form; the current form does not filter modules based on the selected course.

### Manage Students

**Create Students** has:

- **Firstname** and **Lastname**.
- **Admission Number** — unique student identifier and login name. Students can also sign in with their full registered name; use the admission number if names are shared.
- **Password** — student login password.
- **Select Class** and **Select Class Arm** — the student's group; group choices load after choosing a class.

Attendance QR codes are not generated when a student account is created. The class teacher generates a unique, short-lived QR code for each enrolled student when starting an attendance session.

### Courses and Modules

**Manage Modules** has **Module Name**. Its list shows module names and edit/delete actions.

**Manage Courses** has:

- **Course Name**.
- **Select Modules** — checkboxes associating existing modules with the course.

The course list shows course name and its associated modules, with edit/delete actions. A course and a module are separate catalog records; their association is stored in `module_course`.

### Manage Sessions

A **session** here is one scheduled class meeting, not merely an academic year. The form contains:

- **Session Name** — academic year, such as `2026/2027`.
- **Term** — First, Second, or Third.
- **Start Time** and **End Time** — scheduled class time.
- **Teacher** — teacher assigned to the meeting.
- **Day** — weekday, Monday through Saturday.
- **Session Type** — Course, TD (tutorial/discussion), or TP (practical).
- **Subject** — the course taught in this meeting.

Saving records the session as inactive. The list shows year, term, time, teacher, course, type, day, and status; actions activate, edit, or delete a session. The current activation action deactivates every other session before activating the selected one, so only one session is active system-wide.

Teacher login checks for an active session assigned to that teacher, on the current weekday, during its time window. It does not check the timetable date range or term when deciding whether to allow login. The current duplicate check uses academic year, term, and course; it can prevent a second session for the same course in the same year and term even if the day or time differs. When editing, verify every select field before saving because the current form does not reliably preselect all existing values.

### Manage Timetables

**Create Timetable** has:

- **Timetable Name** — descriptive schedule label.
- **Start Date** and **End Date** — dates when the timetable applies.
- **Sessions** — checkboxes for sessions to include.
- **Class/Arm** — the class group that follows the timetable.
- **Term** — the associated term.

The timetable list shows name, class/group, and term, with edit/delete actions. In the database, `timetable_session` connects timetable records to session records.

### Timetable Days Off

**Timetable Days Off** is currently a read-only listing. It shows:

- **Timetable Name**.
- **Day Off Name**.
- **Day Off Date**.

This admin page has no create/edit form; it displays day-off records already associated with a timetable.

### How the main records connect

```text
Class
└── Class Group
    ├── Students
    ├── Teacher assignments
    └── Timetable
        └── Scheduled Sessions
            ├── Academic year and term
            ├── Course
            ├── Teacher
            ├── Weekday and start/end time
            └── Type (Course / TD / TP)
                └── Attendance records
```

In database terms, classes and groups are stored in `tblclass` and `tblclassarms`; people are stored in `tblstudents` and `tblclassteacher`; courses/modules are stored in `course` and `module`, with `module_course` connecting them; scheduled sessions are in `tblsessionterm`; timetables and their session links are in `timetable` and `timetable_session`; attendance is stored in `tblattendance` and refers to a session by `sessionTermId`.

### 👨‍🏫 Teacher Module
- **Current Session View**: See students enrolled in the current teaching session
- **Attendance Management**:
  - Manual attendance marking via checkboxes
  - QR code-based attendance (individual student codes for each active session)
  - View class attendance by date
  - View individual student attendance records
- **Reporting**: Generate daily attendance reports in Excel format
- **Session-Based Access**: Login restricted to scheduled teaching hours

### 👨‍🎓 Student Module
- **Timetable Access**: View personal class schedule
- **Attendance Statistics**: Track personal attendance records
- **QR Code Check-in**: Mark attendance by scanning the assigned student QR during the 10-minute session

## 🛠️ Technologies Used

### Backend
- **PHP**: Server-side scripting and application logic
- **MySQL**: Database management system

### Frontend
- **HTML5**: Structure and content
- **CSS3**: Styling and layout
- **Bootstrap**: Responsive design framework
- **JavaScript**: Client-side interactivity
- **jQuery**: DOM manipulation and AJAX requests
- **AJAX**: Asynchronous data exchange

## 📊 Database Structure

The system uses 14 main tables:

1. **tbladmin**: Administrator accounts
2. **tblclassteacher**: Teacher information
3. **tblstudents**: Student records
4. **tblclass**: Class information
5. **tblclassarms**: Class groups/divisions
6. **course**: Course catalog
7. **module**: Module information
8. **module_course**: Course-module relationships
9. **tblsessionterm**: Academic session details
10. **timetable**: Schedule information
11. **tblattendance**: Attendance records
12. **daysoff**: Holiday calendar
13. **timetable_daysoff**: Timetable-holiday associations
14. **tblterm**: Academic terms/semesters

## 🚀 Installation

### Prerequisites
- PHP 8.x
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser

### Setup Instructions

1. **Clone the repository**
```bash
git clone https://github.com/yourusername/attendance-management-system.git
cd attendance-management-system
```

2. **Database Configuration**
- Create a new MySQL database named `attendancemsystem`
- Import the base schema, then apply both repair scripts in order:
```bash
mysql -u username -p attendancemsystem < "DATABASE FILE/attendancemsystem.sql"
mysql -u username -p attendancemsystem < "DATABASE FILE/repair_schema.sql"
mysql -u username -p attendancemsystem < "DATABASE FILE/repair_academic_schema.sql"
```
- To populate the admin catalog with 50 sample modules and 100 sample courses, optionally import `DATABASE FILE/sample_catalog_seed.sql` after the repair scripts. It can be re-run safely and does not replace existing entries.
- To create the sample undergraduate/postgraduate cohorts, run `DATABASE FILE/sample_academic_classes.sql` after the repair scripts. It renames existing `UG`/`PG` rows in place and safely creates 15 generic cohort names: eight UG and seven PG.

3. **Configure the environment**
- Copy `.env.example` to `.env` and set your database credentials and `DEVELOPMENT_WEBSITE_BASE_URL` to the URL used to open the local project. The local `.env` file is ignored by Git and denied by the included Apache rule; server-provided environment variables take precedence.
- Generate a QR signing key for each environment and keep each key private and unchanged for that environment:
```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```
- Put the local generated value in `DEVELOPMENT_QR_ATTENDANCE_TOKEN_KEY`. Use `QR_ATTENDANCE_TOKEN_KEY` for production; never use the example placeholder as a real key.

4. **Set Permissions**
```bash
chmod -R 755 uploads/
chmod -R 755 logs/
```

5. **Start the Application**
- Place the project in your web server's document root and start Apache and MySQL.
- Install the locked PHP dependencies with `composer install` if `vendor/` is not present.
- Open the exact `DEVELOPMENT_WEBSITE_BASE_URL` configured in `.env`. For a project folder named `Attendance-Management-System-master` under XAMPP `htdocs`, the default is `http://localhost/Attendance-Management-System-master`.

## 👥 Default Login Credentials

### Administrator
- Email: admin@epg.ma
- Password: admin123

### Teacher
- Email: teacher@epg.ma
- Password: teacher123

### Student
- Admission Number: STU001
- Password: student123

**⚠️ Important**: Change these default credentials immediately after first login.

## 📱 Key Functionalities

### Student Panel upgrade

The upgraded Student panel uses local Bootstrap 5.3.8 assets from `Student/vendor/bootstrap5/`; Admin, Faculty, and the existing `Student/student_timetable.php` page continue using their existing frontend assets. After the regular attendance and academic schema setup, back up `attendancemsystem` and run `DATABASE FILE/student_panel_schema.sql` to add student contact/profile fields, announcement storage, and attendance correction requests. Existing attendance and QR-session tables are reused.

Announcements appear for the student's class when published records are present in `student_announcements`. Student correction requests are stored as Pending and are read-only to students; review decisions must be made by authorized staff.

### QR Code Attendance
Teachers create a subject-specific attendance session from **QR_Code Attendance**. The teacher's dashboard generates one shared QR code for each session, and students assigned to that class can view every active session, its code, details, and countdown on their dashboards. Each class and subject can have one active session at a time; different subjects may run concurrently. The QR is valid for exactly 10 minutes from session creation. Scanning the faculty QR opens this website's home page in the phone browser—no mobile app is needed. If the student is not signed in, the existing Student sign-in flow preserves the scanned session and records attendance automatically after successful authentication and GPS verification; a signed-in student is checked in automatically. Students do not need to scan a second time. The server verifies the active session, current class assignment, roster membership, and configured college geofence; database uniqueness prevents a second attendance entry. The server records attendance date and time, while the QR session remains linked to its subject and faculty. The faculty dashboard refreshes the roster and total/present/absent/not-marked/location-verified counts automatically, and the attendance also appears in the existing Admin reports and Student history. A teacher may close a session early; remaining students are then marked absent.

The QR URL is generated from the server-side `WEBSITE_BASE_URL`, not the browser host header. The PHP pages and attendance endpoints use the same origin; there is no separate frontend or API server to configure.

#### Local XAMPP test

1. In PowerShell, copy the template with `Copy-Item .env.example .env`. Keep `APP_ENV=development`, set `DEVELOPMENT_WEBSITE_BASE_URL` to the exact URL opened in your browser, and configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` for local MySQL. The local `.env` is ignored by Git and blocked by the included Apache rule; server environment variables take precedence.
2. Generate `DEVELOPMENT_QR_ATTENDANCE_TOKEN_KEY` with `C:\xampp\php\php.exe -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`. Put the generated value in `.env`; do not use the example placeholder or commit your key. Development-specific URLs and signing keys keep local QR codes independent from any pre-existing Apache production/tunnel settings.
3. Start Apache and MySQL in XAMPP. Import `attendancemsystem.sql`, then run `repair_schema.sql`, `repair_academic_schema.sql`, `migration_v2.sql`, and `qr_attendance_schema.sql` from `DATABASE FILE` in that order. Run `composer install` from the project directory if `vendor/` is missing.
4. Open the configured `DEVELOPMENT_WEBSITE_BASE_URL` in Chrome or another current browser. `http://localhost/...` is a secure browser context for camera/location APIs on the same computer. A QR link using localhost only opens on the computer running XAMPP; on a phone, localhost means the phone itself.
5. In the local database, create a class/group, subject, teacher, student, academic session, and timetable. In Admin, open **QR & GPS Attendance → College Location Settings** and set the local database coordinates to the test device's actual location with a suitable radius. Do not disable the server-side location check or use these settings in production.
6. Sign in as a teacher, create a QR attendance session, and display its QR code. In another browser profile on the same computer, open the Student **Scan attendance QR** page and scan the displayed QR, or scan with a phone camera only if the generated link is reachable on the phone. Sign in as a student enrolled in that session and allow precise location. Confirm the success page and verify the class roster/Student attendance history.
7. Test a malformed token by changing the token in a test URL; it must be rejected. Scan/submit again after successful check-in; the duplicate must be rejected. Expired and closed sessions are rejected too.

The in-page scanner requires a browser supporting `BarcodeDetector` and camera permission; use the phone's built-in camera if that API is unavailable. Browsers generally allow camera/location on localhost, but not on an ordinary HTTP LAN address. To test from a phone on the same Wi-Fi, set `DEVELOPMENT_WEBSITE_BASE_URL` to the computer's private LAN IP and serve it over HTTPS with a certificate trusted by that phone, or set it to a trusted HTTPS development tunnel. Generate a new QR after changing it. Mobile data cannot reach XAMPP directly without a reachable HTTPS tunnel.

#### Test QR attendance from mobile data (temporary Cloudflare Quick Tunnel)

To let students reach the local XAMPP app over their own mobile data, first start Apache and MySQL in XAMPP. Install `cloudflared` or provide its executable path, then run this from PowerShell in the project directory:

```powershell
.\scripts\startAttendanceQuickTunnel.ps1 -CloudflaredPath "C:\path\to\cloudflared.exe"
```

The script starts a temporary HTTPS Quick Tunnel to `http://localhost:80`, updates only `DEVELOPMENT_WEBSITE_BASE_URL` in the ignored local `.env`, and checks that the project responds through the public URL. Keep its PowerShell window open and refresh the Faculty QR Attendance page to generate a fresh code. QR generation verifies the configured HTTPS host is public and reachable; if the tunnel has stopped or the URL is stale, it refuses to generate the QR and asks you to restart the tunnel. Scan with Android Chrome while Wi-Fi is off to test mobile-data access.

Quick Tunnels have no uptime guarantee and their hostname changes when restarted. The script updates `.env` each time, so refresh the faculty QR after every restart. They are for temporary development only. Since the tunnel forwards to Apache on port 80, any site/path served by that Apache instance may be reachable through the tunnel; avoid exposing unrelated projects or real data during the test. Do not open the Apache port directly on your router or firewall. A stable hostname requires a Cloudflare account, a domain added to that account, and a named tunnel configured with that hostname; those credentials/domain details are not present in this project.

#### Staging and production

The same PHP application and database schema are used in each environment; no source or frontend build changes are needed. Configure these server-side environment variables in the hosting platform (or Apache's PHP environment). Do not put secrets in frontend code:

```text
APP_ENV=production
WEBSITE_BASE_URL=https://attendance.example.edu/Attendance-Management-System-master
DB_HOST=<production database host>
DB_PORT=3306
DB_NAME=<production database name>
DB_USER=<production database user>
DB_PASSWORD=<production database password>
QR_ATTENDANCE_TOKEN_KEY=<base64-encoded random 32-byte secret>
```

Set `WEBSITE_BASE_URL` to the deployed app's reachable public HTTPS URL, including its subdirectory. `APP_ENV=production` rejects HTTP, localhost, and private IP URLs. Staging can use `STAGING_WEBSITE_BASE_URL` and `STAGING_QR_ATTENDANCE_TOKEN_KEY` with `APP_ENV=staging`. Preserve each environment's signing key across all PHP instances in that environment, and use different keys for development, staging, and production. Deploy the project and `vendor/`, run the database migrations above, and provide environment settings through the hosting platform's secret manager; never upload the local `.env` or use the example key.

QR scans open the home page and preserve the scanned session through Student sign-in. On first setup, run `DATABASE FILE/qr_attendance_schema.sql`, then sign in as Admin and open **QR & GPS Attendance → College Location Settings**. Enter the institution's real coordinates and permitted radius; attendance remains disabled until all values are configured. Students must allow precise location on the HTTPS site. The server validates the QR, authenticated student, class roster, request token, session status/expiry, location coordinates/accuracy, and geofence before writing attendance. A unique database key prevents duplicate records. The same-origin PHP backend writes to the configured environment's database; client requests cannot select the backend/database.

After deployment, activate an academic session and add the teacher's subject/class timetable for the current weekday. Test with an enrolled student from a phone on mobile data: generate a 10-minute session, scan the teacher's QR, sign in if prompted, and verify the success confirmation, Student history, and live faculty roster. Scan again to confirm duplicate rejection, then test early closure and expiry. To finalize expired absences when no dashboard remains open, schedule `php.exe <project-path>\expireQrAttendance.php` to run every minute using the hosting scheduler or Windows Task Scheduler.

Run `C:\xampp\php\php.exe tests\attendance_config_test.php` for URL/security/token/geofence checks, and `C:\xampp\php\php.exe tests\database_smoke_test.php` to verify local MySQL connectivity and required QR attendance tables. End-to-end attendance recording additionally requires valid student, class, academic session, and timetable records, so follow the manual scan steps above without using test requests that write to a live database.

### Smart Login System
- Teachers can only log in during their scheduled teaching sessions
- System prevents login on holidays and non-teaching days
- Automatic session validation

### Attendance Methods
1. **Manual Marking**: Teachers use checkboxes to mark attendance
2. **QR Code Scanning**: Students scan codes to self-register attendance
3. **Late Arrival Tracking**: System records tardiness with duration and justification options

### Reports and Statistics
- Daily attendance reports (Excel export)
- Class-wise attendance statistics
- Individual student attendance history
- Graphical representation of attendance trends

## 🔒 Security Features

- Password hashing for all user accounts
- Session-based authentication
- Role-based access control (RBAC)
- SQL injection prevention
- XSS protection
- CSRF token validation

## 📈 Future Enhancements

- [ ] Mobile application for iOS and Android
- [ ] Email/SMS notifications for absences
- [ ] Parent portal for attendance monitoring
- [ ] Biometric attendance integration
- [ ] Advanced analytics and predictive reporting
- [ ] Multi-language support
- [ ] API for third-party integrations

## 🐛 Known Issues

- Password recovery feature temporarily disabled due to Gmail security policy updates
- QR code generation requires stable internet connection
- Timetable conflicts may occur if sessions overlap

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Mohamed Mounim RHOULI**
- Institution: École Supérieure de Technologie de Fès
- Program: DUT Génie Informatique
- Project Supervisor: Mr. LAZRAK Alae Eddine
- Academic Supervisor: Pr. Adil HACHMOUD

## 🙏 Acknowledgments

- École Polytechnique des Génies for hosting the internship
- Supervisors and jury members for their guidance
- Open-source community for various libraries and frameworks

## 📞 Support

For support and queries:
- Email:mohamedmounim.rhouli@usmba.ac.ma

## 🔗 Useful Links

- [PHP Documentation](https://www.php.net/downloads)
- [jQuery API](https://api.jquery.com/)
- [Bootstrap Documentation](https://getbootstrap.com/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

---

**Note**: This project was developed as part of a final year internship project at École Polytechnique des Génies, Fès, Morocco (2024).


## Local repair notes (Windows / XAMPP)

The original SQL dump does not include all timetable/holiday tables required by the PHP files. After importing `DATABASE FILE/attendancemsystem.sql`, back up the database and run `DATABASE FILE/repair_schema.sql` once in phpMyAdmin. Then use Admin > Create Session/Term to configure valid sessions, weekdays, teachers, and start/end times; legacy rows cannot be reliably auto-assigned.

The Calendarific holiday integration now reads `CALENDARIFIC_API_KEY` from the server environment. Set that variable only if you use the integration. Rotate any previously exposed key and do not commit secrets.

**Security note:** This legacy project stores/checks MD5 passwords in its imported data. The login queries for administrator and student have been changed to prepared statements, but password storage still needs a planned migration to `password_hash()` / `password_verify()` before any public deployment. Use only for local/demo use until that migration and authorization review are complete.

## Submission smoke test

1. Confirm Apache and MySQL are running in XAMPP.
2. Confirm `Includes/dbcon.php` matches your local database host/user/password; never commit production credentials.
3. Import the original SQL dump, then run `repair_schema.sql` once.
4. Configure at least one class, class arm, teacher, student, term, session and timetable using the Admin interface.
5. Test administrator login, teacher login during a configured session, manual attendance, student attendance view, and report export.
6. Verify holiday integration only after setting a valid API key; the core app should not require that external service for basic login.
