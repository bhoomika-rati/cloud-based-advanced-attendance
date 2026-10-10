<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../Includes/dbcon.php';
include '../Includes/session.php';

if (isset($_SESSION['userId'])) {
    $studentId = $_SESSION['userId'];

    // Fetch the student's class and class arm
    $query = "SELECT tblstudents.*, tblclass.className, tblclassarms.classArmName
              FROM tblstudents
              INNER JOIN tblclass ON tblclass.Id = tblstudents.classId
              INNER JOIN tblclassarms ON tblclassarms.Id = tblstudents.classArmId
              WHERE tblstudents.Id = $studentId";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        // If the query failed, output the error
        die("Query failed: " . mysqli_error($conn));
    }

    $row = mysqli_fetch_assoc($result);

    $selectedClassArm = $row['classArmId'];

    // Corrected Query to fetch timetable entries along with teacher information
    $timetableQuery = "SELECT st.startTime, st.endTime, st.sessionName, st.type, CONCAT(ct.firstName, ' ', ct.lastName) AS teacher, st.day_name
                       FROM timetable t
                       INNER JOIN timetable_session ts ON t.id_Timetable = ts.Timetable_id
                       INNER JOIN tblsessionterm st ON ts.Session_id = st.Id
                       INNER JOIN tblclassteacher ct ON st.teacher_id = ct.Id
                       WHERE t.tblclassarms_id = '$selectedClassArm'";

    $timetableResult = mysqli_query($conn, $timetableQuery);

    if (!$timetableResult) {
        // If the query failed, output the error
        die("Query failed: " . mysqli_error($conn));
    }

    $timetableEntries = [];
    while ($timetableRow = mysqli_fetch_assoc($timetableResult)) {
        $timetableEntries[] = $timetableRow;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <title>View Timetable</title>
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include "Includes/sidebar.php"; ?>
        <!-- Sidebar -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- TopBar -->
                <?php include "Includes/topbar.php"; ?>
                <!-- Topbar -->

                <!-- Container Fluid-->
                <div class="container-fluid" id="container-wrapper">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">View Timetable</h1>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">View Timetable</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_SESSION['userId']) && !empty($timetableEntries)) : ?>
                                        <h5>Timetable for <?php echo $row['className'] . '/' . $row['classArmName']; ?></h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" width="100%" cellspacing="0">
                                                <thead>
                                                    <tr>
                                                        <th>Day/Time</th>
                                                        <th>08H30 - 10H30</th>
                                                        <th>10H30 - 12H30</th>
                                                        <th>14H30 - 16H30</th>
                                                        <th>16H30 - 18H30</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    // Initialize an array to store timetable entries for each day and time slot
                                                    $timeSlots = [
                                                        '08:30:00-10:30:00' => '08H30 - 10H30',
                                                        '10:30:00-12:30:00' => '10H30 - 12H30',
                                                        '14:30:00-16:30:00' => '14H30 - 16H30',
                                                        '16:30:00-18:30:00' => '16H30 - 18H30'
                                                    ];

                                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                                    $daysTimetable = array_fill_keys($days, array_fill_keys(array_keys($timeSlots), ''));

                                                    // Populate the days timetable array
                                                    foreach ($timetableEntries as $entry) {
                                                        $startTime = $entry['startTime'];
                                                        $endTime = $entry['endTime'];
                                                        $sessionInfo = $entry['sessionName'] . " (" . $entry['type'] . ") - " . $entry['teacher'];

                                                        foreach ($timeSlots as $slot => $label) {
                                                            list($slotStart, $slotEnd) = explode('-', $slot);
                                                            if ($startTime >= $slotStart && $endTime <= $slotEnd) {
                                                                $daysTimetable[$entry['day_name']][$slot] = $sessionInfo;
                                                            }
                                                        }
                                                    }

                                                    // Output timetable rows
                                                    foreach ($daysTimetable as $day => $entries) {
                                                        echo "<tr>";
                                                        echo "<td>$day</td>";
                                                        foreach ($entries as $entry) {
                                                            echo "<td>";
                                                            if (is_array($entry)) {
                                                                foreach ($entry as $info) {
                                                                    echo "$info<br>";
                                                                }
                                                            } else {
                                                                echo "$entry";
                                                            }
                                                            echo "</td>";
                                                        }
                                                        echo "</tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php elseif (isset($_SESSION['userId']) && empty($timetableEntries)) : ?>
                                        <div class="alert alert-info">No timetable entries found for the selected class/arm.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!---Container Fluid-->
            </div>
            <!-- Footer -->
            <?php include "Includes/footer.php"; ?>
            <!-- Footer -->
        </div>
    </div>

    <!-- Scroll to top -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/ruang-admin.min.js"></script>
</body>

</html>

