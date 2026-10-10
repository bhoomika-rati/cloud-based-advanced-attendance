<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

//------------------------SAVE--------------------------------------------------

if (isset($_POST['save'])) {
  $timetableName = $_POST['timetableName'];
  $startDate = $_POST['startDate'];
  $endDate = $_POST['endDate'];
  $classArmId = $_POST['classArmId'];
  $termId = $_POST['termId'];

  $timetableQuery = "INSERT INTO timetable (Name, Start_Date, End_Date, tblclassarms_id, term_id) VALUES ('$timetableName', '$startDate', '$endDate', '$classArmId', '$termId')";
  $timetableResult = mysqli_query($conn, $timetableQuery);

  if ($timetableResult) {
    $timetableId = mysqli_insert_id($conn); 

    foreach ($_POST['sessions'] as $sessionId) {
      $insertSessionQuery = "INSERT INTO timetable_session (Timetable_id, Session_id) VALUES ('$timetableId', '$sessionId')";
      $insertSessionResult = mysqli_query($conn, $insertSessionQuery);
      if (!$insertSessionResult) {
        $statusMsg = "<div class='alert alert-danger' id='alert'>An error occurred while inserting sessions: " . mysqli_error($conn) . "</div>";
        // Rollback the transaction and exit loop if insertion fails
        mysqli_query($conn, "ROLLBACK");
        break;
      }
    }

    if (!isset($statusMsg)) {
      $statusMsg = "<div class='alert alert-success' id='alert'>Timetable Created Successfully!</div>";
    }
  } else {
    $statusMsg = "<div class='alert alert-danger' id='alert'>An error occurred while creating timetable: " . mysqli_error($conn) . "</div>";
  }
}

//------------------------UPDATE--------------------------------------------------
//------------------------UPDATE--------------------------------------------------

if (isset($_POST['update'])) {
  $timetableId = $_POST['timetableId'];
  $newTimetableName = $_POST['timetableName'];
  $newStartDate = $_POST['startDate'];
  $newEndDate = $_POST['endDate'];
  $newClassArmId = $_POST['classArmId'];
  $newTermId = $_POST['termId'];
  $selectedSessions = isset($_POST['sessions']) ? $_POST['sessions'] : [];

  // Update timetable details
  $updateQuery = "UPDATE timetable SET Name = '$newTimetableName', Start_Date = '$newStartDate', End_Date = '$newEndDate', tblclassarms_id = '$newClassArmId', term_id = '$newTermId' WHERE id_Timetable = '$timetableId'";

  if (mysqli_query($conn, $updateQuery)) {
    // Retrieve existing sessions associated with the timetable
    $existingSessionsQuery = mysqli_query($conn, "SELECT Session_id FROM timetable_session WHERE Timetable_id = '$timetableId'");
    $existingSessions = [];
    while ($row = mysqli_fetch_assoc($existingSessionsQuery)) {
      $existingSessions[] = $row['Session_id'];
    }

    // Compare selected sessions with existing sessions
    $toDelete = array_diff($existingSessions, $selectedSessions);
    $toInsert = array_diff($selectedSessions, $existingSessions);

    // Delete sessions that are no longer selected
    foreach ($toDelete as $sessionId) {
      mysqli_query($conn, "DELETE FROM timetable_session WHERE Timetable_id = '$timetableId' AND Session_id = '$sessionId'");
    }

    // Insert new sessions
    foreach ($toInsert as $sessionId) {
      mysqli_query($conn, "INSERT INTO timetable_session (Timetable_id, Session_id) VALUES ('$timetableId', '$sessionId')");
    }

    $statusMsg = "<div class='alert alert-success' id='alert'>Timetable Updated Successfully!</div>";
    echo "<script>
                setTimeout(function(){
                    window.location.href = 'createTimetable.php';
                }, 2000); 
              </script>";
  } else {
    $statusMsg = "<div class='alert alert-danger' id='alert'>An error occurred while updating timetable: " . mysqli_error($conn) . "</div>";
  }
}


//------------------------DELETE--------------------------------------------------

if (isset($_GET['delete'])) {
  $deleteId = $_GET['delete'];
  $deleteQuery = mysqli_query($conn, "DELETE FROM timetable WHERE id_Timetable = $deleteId");

  if ($deleteQuery) {
    $statusMsg = "<div class='alert alert-success' id='alert'>Timetable Deleted Successfully!</div>";
  } else {
    $statusMsg = "<div class='alert alert-danger' id='alert'>An error occurred while deleting timetable: " . mysqli_error($conn) . "</div>";
  }
}

//------------------------EDIT--------------------------------------------------

if (isset($_GET['edit'])) {
  $editId = $_GET['edit'];
  $editQuery = mysqli_query($conn, "SELECT * FROM timetable WHERE id_Timetable = $editId");

  if ($editQuery && mysqli_num_rows($editQuery) > 0) {
    $editRow = mysqli_fetch_assoc($editQuery);
  } else {
    $statusMsg = "<div class='alert alert-danger' id='alert'>Timetable Not Found!</div>";
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
  <?php include 'includes/title.php'; ?>
  <link href="../vendore/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="../vendore/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="css/ruang-admin.min.css" rel="stylesheet">
  <link href="../vendore/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

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
            <h1 class="h3 mb-0 text-gray-800">Manage Timetables</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Manage Timetables</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary"><?php echo isset($editRow) ? 'Edit Timetable' : 'Create Timetable'; ?></h6>
                  <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                      <div class="col-xl-4">
                        <label class="form-control-label">Timetable Name<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="timetableName" id="timetableName" value="<?php echo isset($editRow) ? $editRow['Name'] : ''; ?>" required>
                      </div>
                      <div class="col-xl-4">
                        <label class="form-control-label">Start Date<span class="text-danger ml-2">*</span></label>
                        <input type="date" class="form-control" name="startDate" id="startDate" value="<?php echo isset(                  $editRow) ? $editRow['Start_Date'] : ''; ?>" required>
                      </div>
                      <div class="col-xl-4">
                        <label class="form-control-label">End Date<span class="text-danger ml-2">*</span></label>
                        <input type="date" class="form-control" name="endDate" id="endDate" value="<?php echo isset($editRow) ? $editRow['End_Date'] : ''; ?>" required>
                      </div>
                    </div>

                    <div class="form-group row mb-3">
                      <div class="col-xl-4">
                        <label class="form-control-label">Sessions<span class="text-danger ml-2">*</span></label>
                        <?php
                        $sessionQuery = mysqli_query($conn, "SELECT * FROM tblsessionterm");
                        while ($row = mysqli_fetch_assoc($sessionQuery)) {
                          $checked = '';
                          if (isset($editRow)) {
                            $sessionId = $row['Id'];
                            $checkSessionQuery = mysqli_query($conn, "SELECT * FROM timetable_session WHERE Timetable_id = '" . $editRow['id_Timetable'] . "' AND Session_id = '$sessionId'");
                            if (mysqli_num_rows($checkSessionQuery) > 0) {
                              $checked = 'checked';
                            }
                          }
                          echo "<div class='form-check'>";
                          echo "<input class='form-check-input' type='checkbox' name='sessions[]' value='" . $row['Id'] . "' $checked>";
                          echo "<label class='form-check-label'>" . $row['sessionName'] . "</label>";
                          echo "</div>";
                        }
                        ?>
                      </div>
                      <div class="col-xl-4">
                        <label class="form-control-label">Class/Arm<span class="text-danger ml-2">*</span></label>
                        <select required name="classArmId" class="form-control">
                          <option value="">--Select Class/Arm--</option>
                          <?php
                          $classQuery = mysqli_query($conn, "SELECT * FROM tblclass");
                          while ($classRow = mysqli_fetch_assoc($classQuery)) {
                            echo "<optgroup label='" . $classRow['className'] . "'>";
                            $armQuery = mysqli_query($conn, "SELECT * FROM tblclassarms WHERE classId = '" . $classRow['Id'] . "'");
                            while ($armRow = mysqli_fetch_assoc($armQuery)) {
                              $selected = isset($editRow) && $editRow['tblclassarms_id'] == $armRow['Id'] ? 'selected' : '';
                              echo "<option value='" . $armRow['Id'] . "' $selected>" . $classRow['className'] . "/" . $armRow['classArmName'] . "</option>";
                            }
                            echo "</optgroup>";
                          }
                          ?>
                        </select>
                      </div>
                      <div class="col-xl-4">
                        <label class="form-control-label">Term<span class="text-danger ml-2">*</span></label>
                        <select required name="termId" class="form-control">
                          <option value="">--Select Term--</option>
                          <?php
                          $termQuery = mysqli_query($conn, "SELECT * FROM tblterm");
                          while ($termRow = mysqli_fetch_assoc($termQuery)) {
                            $selected = isset($editRow) && $editRow['term_id'] == $termRow['Id'] ? 'selected' : '';
                            echo "<option value='" . $termRow['Id'] . "' $selected>" . $termRow['termName'] . "</option>";
                          }
                          ?>
                        </select>
                      </div>
                    </div>

                    <?php if (isset($editRow)) { ?>
                      <input type="hidden" name="timetableId" value="<?php echo $editRow['id_Timetable']; ?>">
                      <button type="submit" name="update" class="btn btn-warning">Update</button>
                    <?php } else { ?>
                      <button type="submit" name="save" class="btn btn-primary">Save</button>
                    <?php } ?>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!--Row-->
          <div class="row">
            <div class="col-lg-12">
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary">All Timetables</h6>
                </div>
                <div class="table-responsive p-3">
                  <table id="dataTableHover" class="table align-items-center table-flush table-hover">
                    <thead class="thead-light">
                      <tr>
                        <th>Timetable Name</th>
                        <th>Class/Arm</th>
                        <th>Term</th>
                        <th>Edit</th>
                        <th>Delete</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $query = "SELECT tt.*, c.className, ca.classArmName, t.termName 
                              FROM timetable tt 
                              INNER JOIN tblclassarms ca ON tt.tblclassarms_id = ca.Id
                              INNER JOIN tblclass c ON ca.classId = c.Id
                              INNER JOIN tblterm t ON tt.term_id = t.Id";
                      $rs = $conn->query($query);
                      $num = $rs->num_rows;
                      if ($num > 0) {
                        while ($row = $rs->fetch_assoc()) {
                          echo "
                                <tr>
                                    <td>" . $row['Name'] . "</td>
                                    <td>" . $row['className'] . "/" . $row['classArmName'] . "</td>
                                    <td>" . $row['termName'] . "</td>
                                    <td><a href='createTimetable.php?edit=" . $row['id_Timetable'] . "'><i class='fas fa-fw fa-edit'></i>Edit</a></td>
                                    <td><a href='createTimetable.php?delete=" . $row['id_Timetable'] . "' onclick=\"return confirm('Are you sure you want to delete this timetable?');\"><i class='fas fa-fw fa-trash'></i>Delete</a></td>
                                </tr>";
                        }
                      } else {
                        echo "<tr><td colspan='5' class='text-center'>No Timetables Found!</td></tr>";
                      }
                      ?>

                    </tbody>
                  </table>
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
  <script src="../vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="../vendor/datatables/dataTables.bootstrap4.min.js"></script>
  <script src="js/ruang-admin.min.js"></script>
  <!-- Page level custom scripts -->
  <script>
    $(document).ready(function() {
      $('#dataTableHover').DataTable();
    });

    // Hide alerts after 5 seconds
    setTimeout(function() {
      var alert = document.getElementById('alert');
      if (alert) {
        alert.style.display = 'none';
      }
    }, 5000);
  </script>
</body>
</html>


