-- Additive tables for the student panel. Back up attendancemsystem before applying.
-- Existing attendance, timetable, QR session, and student tables are unchanged.

ALTER TABLE tblstudents MODIFY password VARCHAR(255) NOT NULL;

CREATE TABLE IF NOT EXISTS student_profiles (
  student_id INT NOT NULL,
  email_address VARCHAR(254) DEFAULT NULL,
  phone_number VARCHAR(40) DEFAULT NULL,
  department VARCHAR(191) DEFAULT NULL,
  semester VARCHAR(40) DEFAULT NULL,
  academic_year VARCHAR(40) DEFAULT NULL,
  photo_path VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (student_id),
  KEY idx_student_profile_email (email_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_announcements (
  id INT NOT NULL AUTO_INCREMENT,
  title VARCHAR(191) NOT NULL,
  body TEXT NOT NULL,
  class_id INT DEFAULT NULL,
  class_arm_id INT DEFAULT NULL,
  published_by INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_student_announcement_audience (class_id, class_arm_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_attendance_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id INT NOT NULL,
  attendance_id INT DEFAULT NULL,
  request_type VARCHAR(32) NOT NULL,
  details TEXT NOT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'Pending',
  reviewed_by INT DEFAULT NULL,
  reviewed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_student_request_owner (student_id, created_at),
  KEY idx_student_request_review (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
