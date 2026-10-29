-- Create sample undergraduate and postgraduate cohorts.
-- Existing class rows named UG and PG are renamed in place to preserve their IDs.
-- Legacy school-grade class records are retained to preserve linked student data;
-- Admin class lists and new assignment selectors only show UG/PG-prefixed classes.

UPDATE tblclass SET className = 'UG - Cohort 01' WHERE className = 'UG';
UPDATE tblclass SET className = 'PG - Cohort 01' WHERE className = 'PG';

INSERT INTO tblclass (className)
SELECT 'UG - Cohort 01' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'UG - Cohort 01');
INSERT INTO tblclass (className)
SELECT 'UG - Cohort 02' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'UG - Cohort 02');
INSERT INTO tblclass (className)
SELECT 'UG - Cohort 03' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'UG - Cohort 03');
INSERT INTO tblclass (className)
SELECT 'UG - Cohort 04' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'UG - Cohort 04');
INSERT INTO tblclass (className)
SELECT 'UG - Cohort 05' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'UG - Cohort 05');
INSERT INTO tblclass (className)
SELECT 'UG - Cohort 06' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'UG - Cohort 06');
INSERT INTO tblclass (className)
SELECT 'UG - Cohort 07' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'UG - Cohort 07');
INSERT INTO tblclass (className)
SELECT 'UG - Cohort 08' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'UG - Cohort 08');

INSERT INTO tblclass (className)
SELECT 'PG - Cohort 01' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'PG - Cohort 01');
INSERT INTO tblclass (className)
SELECT 'PG - Cohort 02' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'PG - Cohort 02');
INSERT INTO tblclass (className)
SELECT 'PG - Cohort 03' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'PG - Cohort 03');
INSERT INTO tblclass (className)
SELECT 'PG - Cohort 04' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'PG - Cohort 04');
INSERT INTO tblclass (className)
SELECT 'PG - Cohort 05' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'PG - Cohort 05');
INSERT INTO tblclass (className)
SELECT 'PG - Cohort 06' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'PG - Cohort 06');
INSERT INTO tblclass (className)
SELECT 'PG - Cohort 07' WHERE NOT EXISTS (SELECT 1 FROM tblclass WHERE className = 'PG - Cohort 07');
