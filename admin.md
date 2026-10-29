# Admin Dashboard Guide

This guide covers every page linked from the Admin sidebar: what it does, how its data is used, and how to add or change records. The instructions describe the application as it currently works.

## Admin navigation

| Sidebar tab | Page(s) | Purpose |
|---|---|---|
| Dashboard | Dashboard | Summary of students and attendance, recent attendance log, CSV export |
| Administrator Profile | Go To My Profile | Edit the administrator account profile |
| Manage Classes | Create Class | Add undergraduate/postgraduate classes |
| Manage Class Groups | Create Class Groups | Add sections/groups to a class |
| Manage Teachers | Create Class Teachers | Add teacher accounts and assign academic information/groups |
| Manage Students | Create Students | Register students and place them in a class and group |
| Courses and Modules | Manage Courses, Manage Modules | Maintain the course catalog, module catalog, and course-module associations |
| Manage Sessions | Create Session | Schedule individual teaching meetings |
| Manage Timetables | Create Timetable, View Timetable, Timetable Days Off | Build schedules, inspect a schedule for a date, and view linked days off |
| Attendance Statistics | View Statistics | Summarize recorded absences |

Sidebar headings such as **Administration**, **Classes**, **People**, **Academics**, and **Reports** are group labels, not separate data-entry pages. Use the expand arrow beside a tab to show its page links.

## Recommended setup order

Set up the records in this order because later forms use earlier records in their dropdowns:

1. Use the existing academic terms (the current database has `First`, `Second`, and `Third`).
2. Create classes.
3. Create class groups for each class.
4. Create modules and courses.
5. Create teacher accounts and assign groups, courses, and modules.
6. Create sessions using a term, teacher, and course.
7. Create timetables, associating a class group, term, and one or more sessions.
8. Register students in their class groups.
9. Record attendance through the teacher/student attendance features.
10. Review the dashboard and Attendance Statistics.

This local application was recently cleared of its demo academic and attendance records. The administrator account and three term records were retained. Consequently, lists may currently be empty. Create classes, groups, courses, modules, and teachers before trying to create sessions or timetables.

There is currently no **Manage Terms** page in the Admin sidebar. The session and timetable forms only let you select terms already stored in the database; adding or renaming terms requires an administrator-supported database change or a new term-management page.

## Dashboard

**What it does:** Shows a quick view of student and attendance activity.

**What each item means:**

- **Total Students** counts student records.
- **Present Today** counts attendance records dated today with status `0`.
- **Absent Today** counts attendance records dated today with status `1`.
- **Attendance Records** counts attendance entries; it is not the student count.
- **Attendance Log** lists up to the 100 most recent records with the student name, admission number, stored class/group IDs, date, and status. If a student record is missing, the log can show “Student not found.”
- The log search filters the displayed rows.
- **Export CSV** downloads the same most-recent attendance records with their IDs and status.
- The bell icon is a visual notification control; the current dashboard does not provide a notification feed.

**How to populate it:** Register students in **Manage Students** and have attendance recorded using the attendance workflows. The dashboard is a read-only summary; it does not create students or attendance.

## Administrator Profile

Open **Administrator Profile → Go To My Profile**.

**What it does:** Updates the administrator name, email, and login password.

**Fields:**

- **First Name** and **Last Name** are displayed in the admin area.
- **Email Address** is the administrator's login email.
- **New Password** and **Confirm Password** must match.

**How to update:** Enter the new name/email and a new password in both password boxes, then select **Update Profile**. The page currently requires a password even when only changing the name or email. The implementation uses a legacy MD5 password hash; use a unique password and do not reuse a sensitive password. The profile page updates the administrator row with ID `1`.

The top bar also provides the signed-in administrator menu and **Logout**. Logging out ends the current admin session.

## Manage Classes — Create Class

**What it does:** Creates the parent academic cohort/programme record. Class groups, students, and timetable groups depend on a class.

**Fields and process:**

1. Enter a unique **Class Name**.
2. Use the `UG - ` prefix for undergraduate classes or `PG - ` for postgraduate classes. For example, `UG - BSc Computer Science Year 1` or `PG - MSc Computer Science Year 1`.
3. Select **Save**.
4. Use **Edit** in the class list to change a class name, or **Delete** to remove a class.

The page rejects names that do not start with `UG - ` or `PG - `. Existing class records with other naming patterns are not shown in this page's list or the current class/group selectors. Deleting a class that is already referenced by other records may fail or affect those relationships, so do not delete a class that is in use.

## Manage Class Groups — Create Class Groups

**What it does:** Adds a section/group within a class, such as `Section A`, `Section B`, or `Lab Group 1`. A student is assigned to a class and one of its groups. Teachers and timetables can also be associated with groups.

**Fields and process:**

1. Choose the parent **Select Class**. The dropdown shows classes named with the UG/PG prefixes.
2. Enter a **Class Group Name**.
3. Select **Save**.
4. Use **Edit** or **Delete** in the list to maintain existing groups.

The same group name may be reused in different classes, but duplicate names within one class are rejected. Create at least one group before assigning students or preparing a timetable.

## Manage Teachers — Create Class Teachers

**What it does:** Creates teacher login accounts, assigns a course and module, and associates a teacher with one or more class groups.

**Fields:**

- **Firstname**, **Lastname**, **Email Address**, and **Phone No** identify/contact the teacher.
- **Password** is required when creating an account. When editing, leave it blank to keep the current password.
- **Course** selects a catalog course.
- **Module** selects a catalog module. The module dropdown is independent of the selected course.
- **Select Class** and **Class Group** are used to find the groups to assign.
- **Add to Selection** adds a chosen group to the assignment list; add each required group before saving.

**How to add a teacher:**

1. Create the required classes and groups.
2. Add the required course and module in **Courses and Modules**.
3. Enter teacher details and a unique email address.
4. Choose the course and module.
5. Choose a class, load/select its group, and use **Add to Selection**. Repeat for additional groups.
6. Select **Save**.

Use the list's edit/delete actions to maintain accounts. A session requires an existing teacher. The application stores teacher passwords using legacy MD5 hashing.

## Manage Students — Create Students

**What it does:** Registers a student account and links it to a class and class group. Admission number is used as the unique student identifier/login name.

**Fields:**

- **Firstname** and **Lastname**.
- **Admission Number** — must be unique.
- **Password** — the student's initial login password.
- **Select Class** — choose a UG/PG class.
- **Select Class Arm** — after selecting a class, choose one of its class groups.

**How to add a student:**

1. Create the class and group first.
2. Enter the student's name, unique admission number, and password.
3. Choose the class. Wait for the group options to load, then select a class arm/group.
4. Select **Save**.

The list displays name, admission number, class, group, creation date, and edit/delete actions. A QR image is created from the admission number only when the PHP GD extension is enabled. If GD is unavailable, the student can still be saved, but no QR image is produced. Student passwords are stored using legacy MD5 hashing.

When editing a student, enter the password value that should be used after the update; the current edit form updates the password field as part of saving.

## Courses and Modules

### Manage Modules

**What it does:** Maintains the module catalog. Modules can be associated with courses and selected for teacher assignments.

**How to add one:** Enter a unique **Module Name** and select **Save**. Use **Edit** to rename a module or **Delete** to remove it. Avoid deleting modules currently used by course or teacher records.

### Manage Courses

**What it does:** Maintains the course catalog. Courses are selected when creating sessions and assigning teachers. A course can be associated with one or more modules.

**How to add one:**

1. Add the needed modules in **Manage Modules** first.
2. Enter a unique **Course Name**.
3. Select the applicable modules in **Select Modules**.
4. Select **Save**.

Use **Edit** to update a course and its module selections, or **Delete** to remove the course. Courses are stored separately from modules; their relationship is stored in the course-module association table. If there are no modules yet, add those before creating courses.

## Manage Sessions — Create Session

**What it does:** Creates an individual teaching meeting with a time, weekday, teacher, course, and session type. It is not simply the academic-year/term record.

**Fields:**

- **Session Name** — academic year/label, such as `2026/2027`.
- **Term** — choose one of the existing terms.
- **Start Time** and **End Time**.
- **Teacher** — choose an existing teacher.
- **Day** — Monday through Saturday.
- **Session Type** — `Course`, `TD` (tutorial/discussion), or `TP` (practical).
- **Subject** — choose an existing course.

**How to add one:** Confirm the desired term is available, then create the course and teacher. Fill all session fields and select **Save**. New sessions are inactive. The list shows the schedule, teacher, course, type, day, and active status.

Use **Edit** to change a session, **Delete** to remove it, or the check/activate action to activate it. Activating one session deactivates all other sessions system-wide. Teacher access checks an active teacher session for the current weekday and time window. The current login check does not evaluate the timetable date range or term. The duplicate check is based on academic session name, term, and course, so it may prevent a second session for the same course in the same term.

## Manage Timetables

### Create Timetable

**What it does:** Creates a timetable for a particular class group and term over a date range, then associates one or more teaching sessions with it.

**Fields:**

- **Timetable Name** — a recognizable schedule label.
- **Start Date** and **End Date** — dates the timetable covers.
- **Sessions** — tick the sessions to include.
- **Class/Arm** — the group following this timetable.
- **Term** — the associated academic term.

**How to add one:**

1. Create the class, group, term, teacher, course, and sessions first.
2. Enter a name and a valid start/end date range.
3. Select one or more sessions.
4. Choose the class group and term.
5. Select **Save**.

The page lists timetables with their class/group and term. Use **Edit** to update the fields/session selection or **Delete** to remove a timetable. When editing, review session checkboxes carefully; the selected sessions replace the previous set.

### View Timetable

**What it does:** Displays schedule entries for a selected date and class group. It is a viewer, not a timetable creation form.

**How to use it:**

1. Select the **Date** to inspect.
2. Select the **Class/Arm**.
3. Select **View Timetable**.

The page uses timetable date ranges and the timetable-to-session links to find entries, then displays the weekday/time and teacher/session details. It only has fixed display columns for four example time ranges (08:30–10:30, 10:30–12:30, 14:30–16:30, and 16:30–18:30); schedules outside those slots may not display as expected.

### Timetable Days Off

**What it does:** Shows the timetable name, day-off name, and date for holiday records linked to timetables.

**How to add data:** This page is **read-only**. There is no admin form in the current application to create a holiday or link it to a timetable. An empty table means no linked day-off records exist; this page cannot add them. A supported entry workflow would need to be added to the application or the records managed by an authorized database administrator. A day-off record must be associated with a timetable before it appears here.

## Attendance Statistics — View Statistics

**What it does:** Read-only report of recorded absences grouped by class, class group, course, session, and teacher. It also shows the total absences and class group with the highest count.

The report treats attendance status `1` as absent. It depends on attendance being linked to valid students, class groups, sessions, and teachers. Course rows can display as **Course unavailable** if the referenced course was removed. When there are no matching records, the tables are empty and the total/highest group show zero or `N/A`.

**How to populate it:** Create students and sessions, then record attendance through the teacher/student attendance workflow. There is no attendance-entry form on the Statistics page itself.

## Data relationships at a glance

```text
Class
└── Class Group
    ├── Students
    ├── Teacher assignments
    └── Timetable (date range + term)
        └── Sessions (weekday + time + teacher + course + type)
            └── Attendance records

Course ── Course/Module association ── Module
Day Off ── Timetable/Day-Off association ── Timetable
```

## Important notes

- Empty dropdowns usually mean a prerequisite record has not been created yet. For example, the session form needs terms, teachers, and courses; the timetable form needs class groups, sessions, and terms.
- Do not delete records that are still used by schedules, attendance, or assignments unless you intend to remove or repair those relationships as well.
- Administrator and teacher/student password handling currently uses legacy MD5 hashing. This guide describes current behavior, not a recommendation for password security.
- This document covers the pages currently linked in the Admin sidebar. Internal helper pages such as class-group AJAX endpoints are not standalone admin tabs.
