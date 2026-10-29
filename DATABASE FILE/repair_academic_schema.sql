-- Add the academic catalog and teacher/session links used by the Admin pages.
-- Back up attendancemsystem before running. Run after attendancemsystem.sql
-- and repair_schema.sql.

CREATE TABLE IF NOT EXISTS course (
  courseId INT NOT NULL AUTO_INCREMENT,
  courseName VARCHAR(191) NOT NULL,
  PRIMARY KEY (courseId),
  UNIQUE KEY uq_course_name (courseName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS module (
  moduleId INT NOT NULL AUTO_INCREMENT,
  moduleName VARCHAR(191) NOT NULL,
  PRIMARY KEY (moduleId),
  UNIQUE KEY uq_module_name (moduleName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS module_course (
  moduleId INT NOT NULL,
  courseId INT NOT NULL,
  PRIMARY KEY (moduleId, courseId),
  KEY idx_module_course_course (courseId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tblclassteacher_classarms (
  classteacher_Id INT NOT NULL,
  classarm_Id INT NOT NULL,
  PRIMARY KEY (classteacher_Id, classarm_Id),
  KEY idx_teacher_classarms_arm (classarm_Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO tblclassteacher_classarms (classteacher_Id, classarm_Id)
  SELECT Id, classArmId
  FROM tblclassteacher;

ALTER TABLE tblclassteacher
  ADD COLUMN courseId INT NULL,
  ADD COLUMN moduleId INT NULL;

ALTER TABLE tblsessionterm
  ADD COLUMN course_id INT NULL;

SET @student_qrcode_column_sql = IF(
  EXISTS (
    SELECT 1
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'tblstudents'
      AND column_name = 'qrCode_path'
  ),
  'SELECT 1',
  'ALTER TABLE tblstudents ADD COLUMN qrCode_path VARCHAR(255) NULL'
);
PREPARE student_qrcode_column_stmt FROM @student_qrcode_column_sql;
EXECUTE student_qrcode_column_stmt;
DEALLOCATE PREPARE student_qrcode_column_stmt;
