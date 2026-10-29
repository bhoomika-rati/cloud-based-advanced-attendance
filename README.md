# 📚 Attendly | Attendance Management System

A comprehensive web-based attendance management system designed for educational institutions to efficiently track and manage student attendance.

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
- **Reporting**: View attendance statistics by class, group, course, session, and teacher

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
- **Search** — filters visible attendance rows by student/admission details.

**Export CSV** downloads up to the 100 most recent records with Attendance ID, Admission Number, Student Name, Class ID, Class Group ID, Date, and Status. The bell icon is displayed as a notification affordance; the dashboard currently has no notification feed.

### Navigation and recommended setup order

The sidebar groups admin pages into:

1. **Administration** — Administrator Profile.
2. **Classes** — Manage Classes and Manage Class Groups.
3. **People** — Manage Teachers and Manage Students.
4. **Academics** — Courses and Modules, Manage Sessions, and Manage Timetables.
5. **Reports** — Attendance Statistics.

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
- **Admission Number** — unique student identifier and login name.
- **Password** — student login password.
- **Select Class** and **Select Class Arm** — the student's group; group choices load after choosing a class.

Creating a student generates a QR code based on the admission number when the PHP GD extension is enabled. If GD is unavailable, the student is still created and the form displays a notice that the QR image was skipped. The student list shows full name, admission number, class, group, creation date, and edit/delete actions.

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

### Attendance Statistics

**View Statistics** summarizes absences (attendance status `1`) by class, class group, course, session, and teacher, and shows the total and the class group with the most absences. A course may display as unavailable when its catalog record has been removed.

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
  - QR code-based attendance (students scan to mark presence)
  - View class attendance by date
  - View individual student attendance records
- **Reporting**: Generate daily attendance reports in Excel format
- **Session-Based Access**: Login restricted to scheduled teaching hours

### 👨‍🎓 Student Module
- **Timetable Access**: View personal class schedule
- **Attendance Statistics**: Track personal attendance records
- **QR Code Check-in**: Mark attendance by scanning QR code (within first 20 minutes of class)

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
- PHP 7.4 or higher
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

3. **Configure Database Connection**
- Edit the database configuration file with your credentials:
```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'attendancemsystem');
```

4. **Set Permissions**
```bash
chmod -R 755 uploads/
chmod -R 755 logs/
```

5. **Start the Application**
- Place the project in your web server's document root
- Access via browser: `http://localhost/attendance-management-system`

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

### QR Code Attendance
Students can mark their attendance by scanning a session-specific QR code within the first 20 minutes of class. Late arrivals are automatically recorded.

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
