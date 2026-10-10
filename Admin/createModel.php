<?php
error_reporting(0);
include '../Includes/dbcon.php';
include '../Includes/session.php';

//------------------------SAVE--------------------------------------------------

if (isset($_POST['save'])) {
  $moduleName = $_POST['moduleName'];
  $query = mysqli_query($conn, "SELECT * FROM module WHERE moduleName ='$moduleName'");
  $ret = mysqli_fetch_array($query);

  if ($ret > 0) {
    $statusMsg = "<div class='alert alert-danger' id='alert'>This Module Name Already Exists!</div>";
  } else {
    $query = mysqli_query($conn, "INSERT INTO module(moduleName) VALUES('$moduleName')");
    if ($query) {
      $statusMsg = "<div class='alert alert-success' id='alert'>Module Created Successfully!</div>";
    } else {
      $statusMsg = "<div class='alert alert-danger' id='alert'>An error Occurred!</div>";
    }
  }
}

//------------------------UPDATE--------------------------------------------------

if (isset($_POST['update'])) {
  $moduleId = $_POST['moduleId'];
  $newModuleName = $_POST['moduleName'];
  $updateQuery = mysqli_query($conn, "UPDATE module SET moduleName = '$newModuleName' WHERE moduleId = $moduleId");

  if ($updateQuery) {
    $statusMsg = "<div class='alert alert-success' id='alert'>Module Updated Successfully!</div>";
    echo "<script>
                setTimeout(function(){
                    window.location.href = 'createModel.php';
                }, 2000); 
              </script>";
  } else {
    $statusMsg = "<div class='alert alert-danger' id='alert'>An error Occurred!</div>";
  }
}

//------------------------DELETE--------------------------------------------------

if (isset($_GET['delete'])) {
  $deleteId = $_GET['delete'];
  $deleteQuery = mysqli_query($conn, "DELETE FROM module WHERE moduleId = $deleteId");

  if ($deleteQuery) {
    $statusMsg = "<div class='alert alert-success' id='alert'>Module Deleted Successfully!</div>";
  } else {
    $statusMsg = "<div class='alert alert-danger' id='alert'>An error Occurred!</div>";
  }
}

//------------------------EDIT--------------------------------------------------

if (isset($_GET['edit'])) {
  $editId = $_GET['edit'];
  $editQuery = mysqli_query($conn, "SELECT * FROM module WHERE moduleId = $editId");

  if ($editQuery && mysqli_num_rows($editQuery) > 0) {
    $editRow = mysqli_fetch_assoc($editQuery);
  } else {
    $statusMsg = "<div class='alert alert-danger' id='alert'>Module Not Found!</div>";
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
  <link href="../vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">

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
            <h1 class="h3 mb-0 text-gray-800">Manage Modules</h1>
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="./">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Manage Modules</li>
            </ol>
          </div>

          <div class="row">
            <div class="col-lg-12">
              <!-- Form Basic -->
              <div class="card mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                  <h6 class="m-0 font-weight-bold text-primary"><?php echo isset($editRow) ? 'Edit Model' : 'Create Module'; ?></h6>
                  <?php echo $statusMsg; ?>
                </div>
                <div class="card-body">
                  <form method="post">
                    <div class="form-group row mb-3">
                      <div class="col-xl-12">
                        <label class="form-control-label">Module Name<span class="text-danger ml-2">*</span></label>
                        <input type="text" class="form-control" name="moduleName" id="exampleInputModelName" value="<?php echo isset($editRow) ? $editRow['moduleName'] : ''; ?>" required>
                      </div>
                    </div>
                    <?php if (isset($editRow)) { ?>
                      <input type="hidden" name="moduleId" value="<?php echo $editRow['moduleId']; ?>">
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
                  <h6 class="m-0 font-weight-bold text-primary">All Modules</h6>
                </div>
                <div class="table-responsive p-3">
                  <table id="dataTableHover" class="table align-items-center table-flush table-hover">
                    <thead class="thead-light">
                      <tr>
                        <th>Module Name</th>
                        <th>Edit</th>
                        <th>Delete</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $query = "SELECT * FROM module";
                      $rs = $conn->query($query);
                      $num = $rs->num_rows;
                      if ($num > 0) {
                        while ($row = $rs->fetch_assoc()) {
                          echo "
                                  <tr>
                                      <td>" . $row['moduleName'] . "</td>
                                      <td><a href='createModel.php?edit=" . $row['moduleId'] . "'><i class='fas fa-fw fa-edit'></i>Edit</a></td>
                                      <td><a href='createModel.php?delete=" . $row['moduleId'] . "' onclick=\"return confirm('Are you sure you want to delete this module?');\"><i class='fas fa-fw fa-trash'></i>Delete</a></td>
                                  </tr>";
                        }
                      } else {
                        echo "<tr><td colspan='3' class='text-center'>No Modules Found!</td></tr>";
                      }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          <!-- End Display Modules Table -->
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