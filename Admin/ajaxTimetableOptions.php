<?php
include '../Includes/dbcon.php';
include '../Includes/session.php';

header('Content-Type: application/json; charset=utf-8');

$classId = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);
if (!$classId || $classId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'A valid class is required.']);
    exit();
}

$classStmt = $conn->prepare("SELECT Id FROM tblclass WHERE Id = ?");
$classStmt->bind_param("i", $classId);
$classStmt->execute();
$classExists = $classStmt->get_result()->num_rows > 0;
$classStmt->close();

if (!$classExists) {
    http_response_code(404);
    echo json_encode(['error' => 'Class not found.']);
    exit();
}

$arms = [];
$armStmt = $conn->prepare("
    SELECT Id, classArmName
    FROM tblclassarms
    WHERE classId = ?
    ORDER BY classArmName ASC
");
$armStmt->bind_param("i", $classId);
$armStmt->execute();
$armResult = $armStmt->get_result();
while ($row = $armResult->fetch_assoc()) {
    $arms[] = ['id' => (int)$row['Id'], 'name' => $row['classArmName']];
}
$armStmt->close();

$teachers = [];
$teacherStmt = $conn->prepare("
    SELECT DISTINCT ct.Id, ct.firstName, ct.lastName
    FROM tblclassteacher ct
    LEFT JOIN tblclassteacher_classarms rel ON rel.classteacher_Id = ct.Id
    LEFT JOIN tblclassarms ca ON ca.Id = rel.classarm_Id
    WHERE ca.classId = ?
       OR (
           ct.classId = ?
           AND NOT EXISTS (
               SELECT 1
               FROM tblclassteacher_classarms assigned
               WHERE assigned.classteacher_Id = ct.Id
           )
       )
    ORDER BY ct.firstName ASC, ct.lastName ASC
");
$teacherStmt->bind_param("ii", $classId, $classId);
$teacherStmt->execute();
$teacherResult = $teacherStmt->get_result();
while ($row = $teacherResult->fetch_assoc()) {
    $teachers[] = [
        'id' => (int)$row['Id'],
        'name' => trim($row['firstName'] . ' ' . $row['lastName'])
    ];
}
$teacherStmt->close();

$subjects = [];
$subjectStmt = $conn->prepare("
    SELECT DISTINCT c.courseId, c.courseName
    FROM tblclassteacher ct
    INNER JOIN course c ON c.courseId = ct.courseId
    LEFT JOIN tblclassteacher_classarms rel ON rel.classteacher_Id = ct.Id
    LEFT JOIN tblclassarms ca ON ca.Id = rel.classarm_Id
    WHERE ca.classId = ?
       OR (
           ct.classId = ?
           AND NOT EXISTS (
               SELECT 1
               FROM tblclassteacher_classarms assigned
               WHERE assigned.classteacher_Id = ct.Id
           )
       )
    ORDER BY c.courseName ASC
");
$subjectStmt->bind_param("ii", $classId, $classId);
$subjectStmt->execute();
$subjectResult = $subjectStmt->get_result();
while ($row = $subjectResult->fetch_assoc()) {
    $subjects[] = ['id' => (int)$row['courseId'], 'name' => $row['courseName']];
}
$subjectStmt->close();

echo json_encode([
    'arms' => $arms,
    'teachers' => $teachers,
    'subjects' => $subjects
]);
