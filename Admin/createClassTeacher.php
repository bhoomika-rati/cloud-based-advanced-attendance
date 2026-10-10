<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

//------------------------SAVE--------------------------------------------------

if (isset($_POST['save'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $emailAddress = $_POST['emailAddress'];
    $phoneNo = $_POST['phoneNo'];
    $password = $_POST['password']; // New password input
    $dateCreated = date("Y-m-d");
    $hashedPassword = md5($password); // Hash the password using MD5

    $classArmList = $_POST['selectionList'];
    $courseId = $_POST['courseId']; // New courseId input
    $moduleId = $_POST['moduleId']; // New moduleId input

    $query = mysqli_query($conn, "SELECT * FROM tblclassteacher WHERE emailAddress ='$emailAddress'");
    $ret = mysqli_fetch_array($query);

    if ($ret > 0) {
        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>This Email Address Already Exists!</div>";
    } else {
        $insertTeacherQuery = "INSERT INTO tblclassteacher (firstName, lastName, emailAddress, password, phoneNo, dateCreated, courseId, moduleId) 
                    VALUES ('$firstName', '$lastName', '$emailAddress', '$hashedPassword', '$phoneNo', '$dateCreated', '$courseId', '$moduleId')";
        $insertTeacherResult = mysqli_query($conn, $insertTeacherQuery);

        if ($insertTeacherResult) {
            $teacherId = mysqli_insert_id($conn);

            foreach ($classArmList as $classArm) {
                $classArmIds = explode("-", $classArm); // Splitting classId and classArmId
                $classId = $classArmIds[0];
                $classArmId = $classArmIds[1];

                $insertRelationshipQuery = "INSERT INTO tblclassteacher_classarms (classteacher_Id, classarm_Id) 
                                  VALUES ('$teacherId', '$classArmId')";
                $insertRelationshipResult = mysqli_query($conn, $insertRelationshipQuery);

                if (!$insertRelationshipResult) {
                    $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error occurred while inserting class teacher and class arm relationship!</div>";
                }
            }
        } else {
            $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>An error occurred while inserting class teacher!</div>";
        }
    }
}

//------------------------UPDATE------------------------------------------------

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['Id'])) {
    $teacherId = $_GET['Id'];

    $fetchTeacherQuery = "SELECT * FROM tblclassteacher WHERE Id = '$teacherId'";
    $fetchTeacherResult = mysqli_query($conn, $fetchTeacherQuery);

    if ($fetchTeacherResult && mysqli_num_rows($fetchTeacherResult) > 0) {
        $teacherData = mysqli_fetch_assoc($fetchTeacherResult);
        $firstName = $teacherData['firstName'];
        $lastName = $teacherData['lastName'];
        $emailAddress = $teacherData['emailAddress'];
        $phoneNo = $teacherData['phoneNo'];
        // Other fields can be fetched similarly
    } else {
        // Teacher not found with the provided ID
        // Redirect or show an error message
    }
}

if (isset($_POST['update'])) {
    $teacherId = $_POST['teacherId'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $emailAddress = $_POST['emailAddress'];
    $phoneNo = $_POST['phoneNo'];
    // Fetch other fields from the form similarly

    // Update query to update the teacher's information
    $updateTeacherQuery = "UPDATE tblclassteacher SET firstName='$firstName', lastName='$lastName', emailAddress='$emailAddress', phoneNo='$phoneNo' WHERE Id='$teacherId'";
    $updateTeacherResult = mysqli_query($conn, $updateTeacherQuery);

    if ($updateTeacherResult) {
        // Update was successful
        // You can optionally redirect the user or display a success message
    } else {
        // Update failed
        // You can show an error message
    }
}

//------------------------DELETE--------------------------------------------------

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['Id'])) {
    $teacherId = $_GET['Id'];

    $deleteTeacherQuery = "DELETE FROM tblclassteacher WHERE Id = '$teacherId'";
    $deleteTeacherResult = mysqli_query($conn, $deleteTeacherQuery);

    $deleteRelationshipQuery = "DELETE FROM tblclassteacher_classarms WHERE classteacher_Id = '$teacherId'";
    $deleteRelationshipResult = mysqli_query($conn, $deleteRelationshipQuery);

    if ($deleteTeacherResult && $deleteRelationshipResult) {
        header("Location: createClassTeacher.php"); // Redirect to the same page after successful deletion
        exit();
    } else {
        $statusMsg = "<div class='alert alert-danger' style='margin-right:700px;'>Failed to delete teacher!</div>";
    }
}
?>


</html>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="img/logo/attnlg.jpg" rel="icon">
    <?php include 'includes/title.php'; ?>
    <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
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
                        <h1 class="h3 mb-0 text-gray-800">Create Class Teachers</h1>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="./">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Create Class Teachers</li>
                        </ol>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Form Basic -->
                            <div class="card mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Create Class Teachers</h6>
                                    <?php echo $statusMsg; ?>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="teacherId" value="<?php echo isset($teacherId) ? $teacherId : ''; ?>">
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Firstname<span class="text-danger ml-2">*</span></label>
                                                <input type="text" class="form-control" required name="firstName" id="exampleInputFirstName" value="<?php echo isset($firstName) ? $firstName : ''; ?>">
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Lastname<span class="text-danger ml-2">*</span></label>
                                                <input type="text" class="form-control" required name="lastName" id="exampleInputLastName" value="<?php echo isset($lastName) ? $lastName : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Email Address<span class="text-danger ml-2">*</span></label>
                                                <input type="email" class="form-control" required name="emailAddress" id="exampleInputEmail" value="<?php echo isset($emailAddress) ? $emailAddress : '';?>">
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Phone No<span class="text-danger ml-2">*</span></label>
                                                <input type="text" class="form-control" name="phoneNo" id="exampleInputPhone" value="<?php echo isset($phoneNo) ? $phoneNo : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Password<span class="text-danger ml-2">*</span></label>
                                                <input type="password" class="form-control" required name="password" id="exampleInputPassword">
                                            </div>
                                        </div>
                                        <div class="form-group row mb-3">
                                            <div class="col-xl-6">
                                                <label class="form-control-label">Course<span class="text-danger ml-2">*</span></label>
                                                <select class="form-control" required name="courseId" id="exampleInputCourseId">
                                                    <option value="">Select Course</option>
                                                    <?php
                                                    $course_query = mysqli_query($conn, "SELECT * FROM course");
                                                    while ($course_row = mysqli_fetch_assoc($course_query))                                                    {
                                                      $selected = ($course_row['courseId'] == $courseId) ? 'selected' : '';
                                                      echo '<option value="' . $course_row['courseId'] . '" ' . $selected . '>' . $course_row['courseName'] . '</option>';
                                                  }
                                                  ?>
                                              </select>
                                          </div>
                                          <div class="col-xl-6">
                                              <label class="form-control-label">Module<span class="text-danger ml-2">*</span></label>
                                              <select class="form-control" required name="moduleId" id="exampleInputModuleId">
                                                  <option value="">Select Module</option>
                                                  <?php
                                                  $module_query = mysqli_query($conn, "SELECT * FROM module");
                                                  while ($module_row = mysqli_fetch_assoc($module_query)) {
                                                      $selected = ($module_row['moduleId'] == $moduleId) ? 'selected' : '';
                                                      echo '<option value="' . $module_row['moduleId'] . '" ' . $selected . '>' . $module_row['moduleName'] . '</option>';
                                                    }
                                                    ?>
                                                    </select>
                                                    </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                    <div class="col-xl-6">
                                                    <label class="form-control-label">Select Class<span class="text-danger ml-2"></span></label>
                                                    <?php
                                                                                                 $qry = "SELECT * FROM tblclass ORDER BY className ASC";
                                                                                                 $result = $conn->query($qry);
                                                                                                 $num = $result->num_rows;
                                                                                                 if ($num > 0) {
                                                                                                     echo '<select required name="classId" id="classId" onchange="classArmDropdown(this.value)" class="form-control mb-3">';
                                                                                                     echo '<option value="">--Select Class--</option>';
                                                                                                     while ($rows = $result->fetch_assoc()) {
                                                                                                         echo '<option value="' . $rows['Id'] . '" >' . $rows['className'] . '</option>';
                                                                                                     }
                                                                                                     echo '</select>';
                                                                                                 }
                                                                                                 ?>
                                                    </div>
                                                    <div class="col-xl-6">
                                                    <label class="form-control-label">Class Group<span class="text-danger ml-2"></span></label>
                                                    <select required name="classArmId" id="classArmId" class="form-control mb-3">
                                                    <option value="">--Select Class Arm--</option>
                                                    </select>
                                                    <button type="button" class="btn btn-primary" onclick="addToSelection()">Add to Selection</button>
                                                    </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                    <div class="col-xl-12">
                                                    <label class="form-control-label">Selected Class Group<span class="text-danger ml-2">*</span></label>
                                                    <select multiple name="selectionList[]" id="selectionList" class="form-control mb-3">
                                                    </select>
                                                    </div>
                                                    </div>
                                                    <div class="form-group row mb-3">
                                                    <div class="col-xl-12">
                                                    <button type="submit" name="save" class="btn btn-primary">Save</button>
                                                    <?php if (isset($teacherId)) : ?>
                                                    <button type="submit" name="update" class="btn btn-success">Update</button>
                                                    <?php endif; ?>
                                                    </div>
                                                    </div>
                                                    </form>
                                                    </div>
                                                    </div>
                                                                            <!-- Input Group -->
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card mb-4">
                                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                        <h6 class="m-0 font-weight-bold text-primary">All Class Teachers</h6>
                                    </div>
                                    <div class="table-responsive p-3">
                                        <table class="table align-items-center table-flush table-hover" id="dataTableHover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>First Name</th>
                                                    <th>Last Name</th>
                                                    <th>Email Address</th>
                                                    <th>Phone No</th>
                                                    <th>Classes Taught</th>
                                                    <th>Course</th> <!-- Added Course Column -->
                                                    <th>Module</th> <!-- Added Module Column -->
                                                    <th>Edit</th>
                                                    <th>Delete</th>
                                                </tr>
                                            </thead>

                                            <tbody>

                                                <?php
                                                $query = "SELECT tblclassteacher.Id, tblclassteacher.firstName, tblclassteacher.lastName, tblclassteacher.emailAddress, tblclassteacher.phoneNo,
                                                      GROUP_CONCAT(CONCAT(tblclass.className, ' - ', tblclassarms.classArmName) SEPARATOR '<br>') AS classes_taught,
                                                      course.courseName AS course,
                                                      module.moduleName AS module
                                                      FROM tblclassteacher
                                                      LEFT JOIN tblclassteacher_classarms ON tblclassteacher.Id = tblclassteacher_classarms.classteacher_Id
                                                      LEFT JOIN tblclassarms ON tblclassteacher_classarms.classarm_Id = tblclassarms.Id
                                                      LEFT JOIN tblclass ON tblclassarms.classId = tblclass.Id
                                                      LEFT JOIN course ON tblclassteacher.courseId = course.courseId
                                                      LEFT JOIN module ON tblclassteacher.moduleId = module.moduleId
                                                      GROUP BY tblclassteacher.Id";
                                                $rs = $conn->query($query);
                                                $num = $rs->num_rows;
                                                $sn = 0;
                                                $status = "";
                                                if ($num > 0) {
                                                    while ($rows = $rs->fetch_assoc()) {
                                                        $sn = $sn + 1;
                                                        echo "
                                                        <tr>
                                                            <td>" . $sn . "</td>
                                                            <td>" . $rows['firstName'] . "</td>
                                                            <td>" . $rows['lastName'] . "</td>
                                                            <td>" . $rows['emailAddress'] . "</td>
                                                            <td>" . $rows['phoneNo'] . "</td>
                                                            <td>" . $rows['classes_taught'] . "</td>
                                                            <td>" . $rows['course'] . "</td>
                                                            <td>" . $rows['module'] . "</td>
                                                            <td><a href='?action=edit&Id=" . $rows['Id'] . "'><i class='fas fa-fw fa-edit'></i></a></td> <!-- Edit Button -->
                                                            <td><a href='?action=delete&Id=" . $rows['Id'] . "' onclick='return confirmDelete();'><i class='fas fa-fw fa-trash'></i></a></td>
                                                        </tr>";
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='10' class='text-center'>No Record Found!</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--Row-->

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

    <!-- Page level plugins -->
    <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable(); // ID From dataTable 
            $('#dataTableHover').DataTable(); // ID From dataTable with Hover
        });

        function confirmDelete() {
            return confirm('Are you sure you want to delete this teacher?');
        }

        function addToSelection() {
            var classId = document.getElementById("classId").value;
            var classArmId = document.getElementById("classArmId").value;
                var className = $("#classId option:selected").text();
                var classArmName = $("#classArmId option:selected").text();

                if (classId != "" && classArmId != "") {
                    var optionText = className + " - " + classArmName;
                    var optionValue = classId + "-" + classArmId;
                    $("#selectionList").append('<option value="' + optionValue + '">' + optionText + '</option>');
                } else {
                    alert("Please select both class and class arm.");
                }
            }

            function classArmDropdown(classId) {
                if (classId == "") {
                    document.getElementById("classArmId").innerHTML = '<option value="">--Select Class Arm--</option>';
                    return;
                } else {
                    if (window.XMLHttpRequest) {
                        // code for IE7+, Firefox, Chrome, Opera, Safari
                        xmlhttp = new XMLHttpRequest();
                    } else {
                        // code for IE6, IE5
                        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                    }
                    xmlhttp.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                            document.getElementById("classArmId").innerHTML = this.responseText;
                        }
                    };
                    xmlhttp.open("GET", "ajaxClassArms.php?cid=" + classId, true);
                    xmlhttp.send();
                }
            }


        </script>
</body>

</html>




