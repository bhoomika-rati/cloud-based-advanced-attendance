-- Repair migration for the supplied Attendance Management System SQL dump.
-- BACK UP attendancemsystem before running. Run once after importing attendancemsystem.sql.
-- Existing session rows receive safe defaults; review/recreate session assignments in Admin.

ALTER TABLE tblsessionterm
  ADD COLUMN startTime TIME NULL,
  ADD COLUMN endTime TIME NULL,
  ADD COLUMN teacher_id INT NULL,
  ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'Lecture',
  ADD COLUMN day_name VARCHAR(12) NULL;

CREATE TABLE IF NOT EXISTS daysoff (
  id_DaysOff INT NOT NULL AUTO_INCREMENT,
  Name VARCHAR(255) NOT NULL,
  date_DayOff DATE NOT NULL,
  PRIMARY KEY (id_DaysOff),
  UNIQUE KEY uq_daysoff_name_date (Name, date_DayOff),
  KEY idx_daysoff_date (date_DayOff)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS timetable (
  id_Timetable INT NOT NULL AUTO_INCREMENT,
  Name VARCHAR(255) NOT NULL,
  Start_Date DATE NOT NULL,
  End_Date DATE NOT NULL,
  tblclassarms_id INT NOT NULL,
  term_id INT NOT NULL,
  PRIMARY KEY (id_Timetable),
  KEY idx_timetable_classarm (tblclassarms_id),
  KEY idx_timetable_dates (Start_Date, End_Date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS timetable_session (
  Timetable_id INT NOT NULL,
  Session_id INT NOT NULL,
  PRIMARY KEY (Timetable_id, Session_id),
  KEY idx_timetable_session_session (Session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS timetable_daysoff (
  Timetable_id INT NOT NULL,
  DaysOff_id INT NOT NULL,
  PRIMARY KEY (Timetable_id, DaysOff_id),
  KEY idx_timetable_daysoff_day (DaysOff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Existing legacy sessions are intentionally not assigned to a teacher or weekday.
-- In Admin > Create Session/Term, edit/recreate each session and assign teacher,
-- weekday, start/end times and type before testing teacher access.
