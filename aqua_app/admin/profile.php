<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../connection.php';

$adminId = (int)$_SESSION['admin_id'];
$adminUsername = (string)($_SESSION['admin_username'] ?? 'admin');

$msg = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPass = (string)($_POST['current_password'] ?? '');
    $newPass     = (string)($_POST['new_password'] ?? '');
    $confirmPass = (string)($_POST['confirm_password'] ?? '');

    $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $adminId]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($currentPass, $row['password_hash'])) {
        $msg = 'Current password is incorrect.';
        $msgType = 'error';
    } elseif (strlen($newPass) < 8) {
        $msg = 'New password must be at least 8 characters.';
        $msgType = 'warning';
    } elseif ($newPass !== $confirmPass) {
        $msg = 'New passwords do not match.';
        $msgType = 'warning';
    } else {
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $upd = $pdo->prepare('UPDATE admins SET password_hash = :h WHERE id = :id');
        $upd->execute([':h' => $hash, ':id' => $adminId]);
        $msg = 'Password updated successfully.';
        $msgType = 'success';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Profile Settings - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'profile'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
  <?php $pageTitle = 'Profile'; $pageIcon = 'bi-gear-fill'; include __DIR__ . '/_topbar.php'; ?>
  <div class="p-4">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8">

        <!-- Profile Header Card -->
        <div class="card mb-4">
          <div class="card-body">
            <div class="d-flex align-items-center gap-4 flex-wrap">
              <div class="profile-avatar" style="width:72px;height:72px;font-size:1.5rem;">
                <?php echo strtoupper(substr($adminUsername, 0, 1)); ?>
              </div>
              <div>
                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($adminUsername); ?></h4>
                <div class="text-muted"><i class="bi bi-shield-check me-1"></i>Administrator</div>
                <div class="text-muted small mt-1"><i class="bi bi-clock me-1"></i>Session active</div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <!-- Account Info -->
          <div class="col-md-5">
            <div class="card h-100">
              <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-muted"></i>Account Info</h6>
                <div class="d-flex flex-column gap-3">
                  <div>
                    <div class="text-muted small">Username</div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($adminUsername); ?></div>
                  </div>
                  <div>
                    <div class="text-muted small">Role</div>
                    <div class="fw-semibold">Administrator</div>
                  </div>
                  <div>
                    <div class="text-muted small">Status</div>
                    <div><span class="badge-active">ACTIVE</span></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Change Password -->
          <div class="col-md-7">
            <div class="card h-100">
              <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-lock me-2 text-muted"></i>Change Password</h6>
                <form method="POST" action="profile.php" id="profileForm">
                  <div class="mb-3">
                    <label for="current_password" class="form-label small fw-semibold">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required />
                  </div>
                  <div class="mb-3">
                    <label for="new_password" class="form-label small fw-semibold">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8" />
                    <div class="form-text">At least 8 characters.</div>
                  </div>
                  <div class="mb-3">
                    <label for="confirm_password" class="form-label small fw-semibold">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required />
                  </div>
                  <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Update Password</button>
                </form>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
</div>

<?php if ($msg !== ''): ?>
<script>
Swal.fire({
  icon: <?php echo json_encode($msgType); ?>,
  title: <?php echo $msgType === 'success' ? json_encode('Updated!') : json_encode('Error'); ?>,
  text: <?php echo json_encode($msg); ?>
});
</script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>
