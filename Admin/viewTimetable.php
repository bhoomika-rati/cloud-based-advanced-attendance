<?php
// Bridge: Redirect old viewTimetable route to the session-based timetable management
include '../Includes/dbcon.php';
include '../Includes/session.php';

header("Location: createTimetable.php");
exit();
