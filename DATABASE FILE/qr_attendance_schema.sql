-- Run against attendancemsystem after the existing attendance schema migrations.
-- Back up the database before applying schema changes.
-- For expiry finalization when no teacher dashboard is open, schedule
-- `php.exe <repository>\expireQrAttendance.php` every minute in Windows Task Scheduler.

CREATE TABLE IF NOT EXISTS qr_attendance_sessions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  token_hash CHAR(64) NOT NULL,
  teacher_id INT NOT NULL,
  timetable_id INT NOT NULL,
  academic_session_id INT NOT NULL,
  class_id INT NOT NULL,
  class_arm_id INT NOT NULL,
  subject_id INT NOT NULL,
  attendance_date DATE NOT NULL,
  generated_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  status VARCHAR(16) NOT NULL DEFAULT 'active',
  expired_at DATETIME DEFAULT NULL,
  closed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_qr_session_token_hash (token_hash),
  KEY idx_qr_session_teacher (teacher_id, created_at),
  KEY idx_qr_session_class_status (class_arm_id, status, expires_at),
  KEY idx_qr_session_date (attendance_date, class_arm_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qr_attendance_records (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  qr_session_id BIGINT UNSIGNED NOT NULL,
  student_id INT NOT NULL,
  admission_number VARCHAR(255) NOT NULL,
  student_name VARCHAR(511) NOT NULL,
  qr_token_hash CHAR(64) DEFAULT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'not_marked',
  marked_at DATETIME DEFAULT NULL,
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL,
  location_accuracy_m DECIMAL(10,2) DEFAULT NULL,
  legacy_synced TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_qr_attendance_student_session (qr_session_id, student_id),
  UNIQUE KEY uq_qr_attendance_token_hash (qr_token_hash),
  KEY idx_qr_attendance_roster (qr_session_id, status),
  KEY idx_qr_attendance_student (student_id, marked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qr_attendance_settings (
  id TINYINT UNSIGNED NOT NULL,
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL,
  radius_m SMALLINT UNSIGNED DEFAULT NULL,
  updated_by INT DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO qr_attendance_settings (id, latitude, longitude, radius_m)
VALUES (1, NULL, NULL, NULL);

ALTER TABLE qr_attendance_settings
  MODIFY radius_m SMALLINT UNSIGNED NULL DEFAULT NULL;

-- Use InnoDB so session/roster changes and legacy report records can commit atomically.
ALTER TABLE tblattendance ENGINE=InnoDB;

SET @qr_session_column_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'tblattendance'
      AND column_name = 'qr_session_id'
  ),
  'SELECT 1',
  'ALTER TABLE tblattendance ADD COLUMN qr_session_id BIGINT UNSIGNED NULL'
);
PREPARE qr_session_column_stmt FROM @qr_session_column_sql;
EXECUTE qr_session_column_stmt;
DEALLOCATE PREPARE qr_session_column_stmt;

SET @qr_session_index_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'tblattendance'
      AND index_name = 'uq_attendance_qr_session_student'
  ),
  'SELECT 1',
  'ALTER TABLE tblattendance ADD UNIQUE KEY uq_attendance_qr_session_student (qr_session_id, admissionNo)'
);
PREPARE qr_session_index_stmt FROM @qr_session_index_sql;
EXECUTE qr_session_index_stmt;
DEALLOCATE PREPARE qr_session_index_stmt;

SET @qr_duration_column_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'qr_attendance_sessions'
      AND column_name = 'duration_minutes'
  ),
  'SELECT 1',
  'ALTER TABLE qr_attendance_sessions ADD COLUMN duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10 AFTER expires_at'
);
PREPARE qr_duration_column_stmt FROM @qr_duration_column_sql;
EXECUTE qr_duration_column_stmt;
DEALLOCATE PREPARE qr_duration_column_stmt;

SET @qr_record_token_column_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'qr_attendance_records'
      AND column_name = 'qr_token_hash'
  ),
  'SELECT 1',
  'ALTER TABLE qr_attendance_records ADD COLUMN qr_token_hash CHAR(64) NULL AFTER student_name'
);
PREPARE qr_record_token_column_stmt FROM @qr_record_token_column_sql;
EXECUTE qr_record_token_column_stmt;
DEALLOCATE PREPARE qr_record_token_column_stmt;

SET @qr_record_token_index_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'qr_attendance_records'
      AND index_name = 'uq_qr_attendance_token_hash'
  ),
  'SELECT 1',
  'ALTER TABLE qr_attendance_records ADD UNIQUE KEY uq_qr_attendance_token_hash (qr_token_hash)'
);
PREPARE qr_record_token_index_stmt FROM @qr_record_token_index_sql;
EXECUTE qr_record_token_index_stmt;
DEALLOCATE PREPARE qr_record_token_index_stmt;

SET @qr_latitude_column_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'qr_attendance_records'
      AND column_name = 'latitude'
  ),
  'SELECT 1',
  'ALTER TABLE qr_attendance_records ADD COLUMN latitude DECIMAL(10,7) NULL AFTER marked_at'
);
PREPARE qr_latitude_column_stmt FROM @qr_latitude_column_sql;
EXECUTE qr_latitude_column_stmt;
DEALLOCATE PREPARE qr_latitude_column_stmt;

SET @qr_longitude_column_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'qr_attendance_records'
      AND column_name = 'longitude'
  ),
  'SELECT 1',
  'ALTER TABLE qr_attendance_records ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude'
);
PREPARE qr_longitude_column_stmt FROM @qr_longitude_column_sql;
EXECUTE qr_longitude_column_stmt;
DEALLOCATE PREPARE qr_longitude_column_stmt;

SET @qr_location_accuracy_column_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'qr_attendance_records'
      AND column_name = 'location_accuracy_m'
  ),
  'SELECT 1',
  'ALTER TABLE qr_attendance_records ADD COLUMN location_accuracy_m DECIMAL(10,2) NULL AFTER longitude'
);
PREPARE qr_location_accuracy_column_stmt FROM @qr_location_accuracy_column_sql;
EXECUTE qr_location_accuracy_column_stmt;
DEALLOCATE PREPARE qr_location_accuracy_column_stmt;

SET @qr_distance_column_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'qr_attendance_records'
      AND column_name = 'distance_m'
  ),
  'SELECT 1',
  'ALTER TABLE qr_attendance_records ADD COLUMN distance_m DECIMAL(10,2) NULL AFTER location_accuracy_m'
);
PREPARE qr_distance_column_stmt FROM @qr_distance_column_sql;
EXECUTE qr_distance_column_stmt;
DEALLOCATE PREPARE qr_distance_column_stmt;

SET @qr_location_verified_column_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'qr_attendance_records'
      AND column_name = 'location_verified'
  ),
  'SELECT 1',
  'ALTER TABLE qr_attendance_records ADD COLUMN location_verified TINYINT(1) NULL AFTER distance_m'
);
PREPARE qr_location_verified_column_stmt FROM @qr_location_verified_column_sql;
EXECUTE qr_location_verified_column_stmt;
DEALLOCATE PREPARE qr_location_verified_column_stmt;

ALTER TABLE qr_attendance_sessions
  ALTER duration_minutes SET DEFAULT 10;

SET @qr_closed_at_column_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'qr_attendance_sessions'
      AND column_name = 'closed_at'
  ),
  'SELECT 1',
  'ALTER TABLE qr_attendance_sessions ADD COLUMN closed_at DATETIME NULL AFTER expired_at'
);
PREPARE qr_closed_at_column_stmt FROM @qr_closed_at_column_sql;
EXECUTE qr_closed_at_column_stmt;
DEALLOCATE PREPARE qr_closed_at_column_stmt;

SET @course_code_column_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'course'
      AND column_name = 'courseCode'
  ),
  'SELECT 1',
  'ALTER TABLE course ADD COLUMN courseCode VARCHAR(50) NULL AFTER courseName'
);
PREPARE course_code_column_stmt FROM @course_code_column_sql;
EXECUTE course_code_column_stmt;
DEALLOCATE PREPARE course_code_column_stmt;

SET @course_code_index_sql = IF(
  EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'course'
      AND index_name = 'uq_course_code'
  ),
  'SELECT 1',
  'ALTER TABLE course ADD UNIQUE KEY uq_course_code (courseCode)'
);
PREPARE course_code_index_stmt FROM @course_code_index_sql;
EXECUTE course_code_index_stmt;
DEALLOCATE PREPARE course_code_index_stmt;
