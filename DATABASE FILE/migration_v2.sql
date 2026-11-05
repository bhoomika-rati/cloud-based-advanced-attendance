-- ============================================================================
-- Attendly AMS - Schema Upgrade & Session Timetable Migration
-- ============================================================================

-- 1. Ensure password fields have sufficient length for modern password_hash() (bcrypt/argon2)
ALTER TABLE tblstudents MODIFY password VARCHAR(255) NOT NULL;
ALTER TABLE tbladmin MODIFY password VARCHAR(255) NOT NULL;
ALTER TABLE tblclassteacher MODIFY password VARCHAR(255) NOT NULL;

-- 2. Create the clean, session-based timetable table
CREATE TABLE IF NOT EXISTS session_timetable (
    id INT NOT NULL AUTO_INCREMENT,
    session_id INT NOT NULL,
    class_id INT NOT NULL,
    class_arm_id INT NOT NULL,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    day_name VARCHAR(15) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_type VARCHAR(50) NOT NULL DEFAULT 'Lecture',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_st_session (session_id),
    KEY idx_st_class (class_id),
    KEY idx_st_class_arm (class_arm_id),
    KEY idx_st_teacher (teacher_id),
    KEY idx_st_subject (subject_id),
    KEY idx_st_day_time (day_name, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Safely migrate any existing legacy timetable entries into session_timetable
INSERT IGNORE INTO session_timetable (session_id, class_id, class_arm_id, teacher_id, subject_id, day_name, start_time, end_time, slot_type)
SELECT 
    ts.Session_id,
    ca.classId,
    t.tblclassarms_id,
    st.teacher_id,
    st.course_id,
    st.day_name,
    st.startTime,
    st.endTime,
    COALESCE(st.type, 'Lecture')
FROM timetable t
INNER JOIN timetable_session ts ON ts.Timetable_id = t.id_Timetable
INNER JOIN tblsessionterm st ON st.Id = ts.Session_id
INNER JOIN tblclassarms ca ON ca.Id = t.tblclassarms_id
WHERE st.startTime IS NOT NULL 
  AND st.endTime IS NOT NULL 
  AND st.teacher_id IS NOT NULL 
  AND st.course_id IS NOT NULL 
  AND st.day_name IS NOT NULL;

