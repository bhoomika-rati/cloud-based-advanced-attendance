<?php
// Bridge: Redirect old timetableDaysOf route to the session-based timetable management
include '../Includes/dbcon.php';
include '../Includes/session.php';

header("Location: createTimetable.php");
exit();
