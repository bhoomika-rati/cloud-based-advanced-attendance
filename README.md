# 📚 Attendance Management System

A comprehensive web-based attendance management system designed for educational institutions to efficiently track and manage student attendance.

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
- **Reporting**: View comprehensive attendance statistics

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
- Import the SQL file:
```bash
mysql -u username -p attendancemsystem < database/attendancemsystem.sql
```

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
