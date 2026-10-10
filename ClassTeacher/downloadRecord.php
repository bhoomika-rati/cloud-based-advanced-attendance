<?php 
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

?>
<table border="1">
<thead>
    <tr>
        <th>#</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Admission No</th>
        <th>Class</th>
        <th>Class Arm</th>
        <th>Session</th>
        <th>Term</th>
        <th>Status</th>
        <th>Date</th>
        <th>Late Arrival</th>
        <th>Justifier</th>
        <th>Lateness Duration</th>
        <th>Description</th>
    </tr>
</thead>

<?php 
$filename = "Attendance list";
$dateTaken = date("Y-m-d");
$cnt = 1;

$ret = mysqli_query($conn, "SELECT tblattendance.Id, tblattendance.status, tblattendance.dateTimeTaken, tblattendance.lateArrival, 
        tblattendance.justification, tblattendance.latenessDuration, tblattendance.description, tblclass.className,
        tblclassarms.classArmName, tblsessionterm.sessionName, tblsessionterm.termId, tblterm.termName,
        tblstudents.firstName, tblstudents.lastName, tblstudents.admissionNumber
        FROM tblattendance
        INNER JOIN tblclass ON tblclass.Id = tblattendance.classId
        INNER JOIN tblclassarms ON tblclassarms.Id = tblattendance.classArmId
        INNER JOIN tblsessionterm ON tblsessionterm.Id = tblattendance.sessionTermId
        INNER JOIN tblterm ON tblterm.Id = tblsessionterm.termId
        INNER JOIN tblstudents ON tblstudents.admissionNumber = tblattendance.admissionNo
        WHERE tblattendance.dateTimeTaken = '$dateTaken' 
        AND tblattendance.classId = '$_SESSION[classId]' 
        AND tblattendance.classArmId = '$_SESSION[classArmId]'");

// Error handling
if (!$ret) {
    die('Error in query: ' . mysqli_error($conn));
}

if (mysqli_num_rows($ret) > 0 ) {
    while ($row = mysqli_fetch_array($ret)) { 
        $status = ($row['status'] == '1') ? "Present" : "Absent";
        $lateArrival = ($row['lateArrival'] == '1') ? "Yes" : "No";
        $justifier = ($row['justification'] == '1') ? "Yes" : "No";

        echo '<tr>  
                <td>'.$cnt.'</td> 
                <td>'.htmlspecialchars($row['firstName']).'</td> 
                <td>'.htmlspecialchars($row['lastName']).'</td> 
                <td>'.htmlspecialchars($row['admissionNumber']).'</td> 
                <td>'.htmlspecialchars($row['className']).'</td> 
                <td>'.htmlspecialchars($row['classArmName']).'</td>	
                <td>'.htmlspecialchars($row['sessionName']).'</td>	 
                <td>'.htmlspecialchars($row['termName']).'</td>	
                <td>'.htmlspecialchars($status).'</td>	 	
                <td>'.htmlspecialchars($row['dateTimeTaken']).'</td>	 
                <td>'.htmlspecialchars($lateArrival).'</td>	 
                <td>'.htmlspecialchars($justifier).'</td>	 
                <td>'.htmlspecialchars($row['latenessDuration']).'</td>	 
                <td>'.htmlspecialchars($row['description']).'</td>					
            </tr>';
        $cnt++;
    }
} else {
    echo "<tr><td colspan='14'>No rows returned from the query.</td></tr>";
}
?>
</table>

<?php
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=".$filename."-report.xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
