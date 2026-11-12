<?php 
require_once('../config.php');

$db_status_ok = ($conn && $conn instanceof mysqli);
$db_error_msg = (!$db_status_ok) ? ($db->error ?? 'Unable to connect to database') : '';

$login_error = '';
// Handle direct/traditional POST fallback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!$db_status_ok) {
        $login_error = 'Database is not connected! ' . $db_error_msg;
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        if ($stmt) {
            $pw = md5($password);
            $stmt->bind_param('ss', $username, $pw);
            $stmt->execute();
            $qry = $stmt->get_result();
            if ($qry && $qry->num_rows > 0) {
                $res = $qry->fetch_array();
                if ($res['status'] != 1) {
                    $login_error = 'Your Account is not yet verified.';
                } else {
                    foreach ($res as $k => $v) {
                        if (!is_numeric($k) && $k != 'password') {
                            $_settings->set_userdata($k, $v);
                        }
                    }
                    $_settings->set_userdata('login_type', 1);
                    header("Location: " . base_url . "admin/index.php");
                    exit;
                }
            } else {
                $login_error = 'Incorrect username or password.';
            }
        } else {
            $login_error = 'Query failed: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
 <?php require_once('inc/header.php') ?>
<body class="hold-transition ">
  <script>
    start_loader()
  </script>
  <style>
    html, body{
      height:calc(100%) !important;
      width:calc(100%) !important;
    }
    body{
      background-image: url("<?php echo validate_image($_settings->info('cover')) ?>");
      background-size:cover;
      background-repeat:no-repeat;
    }
    .login-title{
      text-shadow: 2px 2px black
    }
    #login{
      flex-direction:column !important
    }
    #logo-img{
        height:150px;
        width:150px;
        object-fit:scale-down;
        object-position:center center;
        border-radius:100%;
    }
    #login .col-7,#login .col-5{
      width: 100% !important;
      max-width:unset !important
    }
  </style>
  <div class="h-100 d-flex align-items-center w-100" id="login">
    <div class="col-7 h-100 d-flex align-items-center justify-content-center">
      <div class="w-100">
        <center><img src="<?= validate_image($_settings->info('logo')) ?>" alt="" id="logo-img"></center>
        <h1 class="text-center py-5 login-title"><b><?php echo $_settings->info('name') ?> - Admin</b></h1>
      </div>
    </div>
    <div class="col-5 h-100 bg-gradient">
      <div class="d-flex w-100 h-100 justify-content-center align-items-center">
        <div class="card col-sm-12 col-md-6 col-lg-4 card-outline card-primary rounded-0 shadow">
          <div class="card-header rounded-0">
            <h4 class="text-purple text-center"><b>Admin Login</b></h4>
          </div>
          <div class="card-body rounded-0">
            <?php if (!$db_status_ok): ?>
              <div class="alert alert-warning text-dark font-weight-bold" style="font-size: 0.85rem;">
                <i class="fa fa-exclamation-triangle"></i> <strong>Database Not Connected!</strong><br>
                Render me <code>DB_SERVER</code> (Aiven host) set karein aur <a href="<?= base_url ?>db_setup.php" class="text-primary font-weight-bold" style="text-decoration: underline;">db_setup.php</a> se database import karein.
              </div>
            <?php endif; ?>

            <?php if (!empty($login_error)): ?>
              <div class="alert alert-danger text-white err_msg">
                <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($login_error); ?>
              </div>
            <?php endif; ?>

            <form id="login-frm" action="" method="post">
              <div class="input-group mb-3">
                <input type="text" class="form-control" autofocus name="username" placeholder="Username (Default: admin)" required>
                <div class="input-group-append">
                  <div class="input-group-text">
                    <span class="fas fa-user"></span>
                  </div>
                </div>
              </div>
              <div class="input-group mb-3">
                <input type="password" class="form-control" name="password" placeholder="Password (Default: admin123)" required>
                <div class="input-group-append">
                  <div class="input-group-text">
                    <span class="fas fa-lock"></span>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-7">
                  <a href="<?php echo base_url ?>">Go to Website</a>
                </div>
                <div class="col-5 text-right">
                  <button type="submit" class="btn btn-primary btn-block btn-flat">Sign In</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

<!-- jQuery -->
<script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="<?php echo base_url ?>dist/js/adminlte.min.js"></script>
<script src="<?php echo base_url ?>dist/js/script.js"></script>

<script>
  $(document).ready(function(){
    end_loader();
  })
</script>
</body>
</html>
