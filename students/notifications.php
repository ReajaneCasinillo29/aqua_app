<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['student_id'])) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../connection.php';

$studentId = (int)$_SESSION['student_id'];

$rows = $pdo->prepare('SELECT * FROM notifications WHERE student_id = :sid ORDER BY id DESC LIMIT 50');
$rows->execute([':sid' => $studentId]);
$notifs = $rows->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Notifications</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'notifications'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
  <?php $pageTitle = 'Notifications'; $pageIcon = 'bi-bell-fill'; include __DIR__ . '/_topbar.php'; ?>
  <div class="p-4">

    <!-- Back Button -->
    <div class="mb-3">
      <a href="javascript:history.back()" class="d-inline-flex align-items-center text-decoration-none text-secondary fw-medium">
        <i class="bi bi-arrow-left me-1"></i> Go Back
      </a>
    </div>

    <?php if (!$notifs): ?>
      <div class="card">
        <div class="empty-state">
          <div class="empty-state-icon"><i class="bi bi-bell"></i></div>
          <div class="empty-state-title">No notifications</div>
          <div class="empty-state-text">You're all caught up!</div>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($notifs as $n):
        $sev = (string)($n['severity'] ?? 'info');
        $cls = $sev === 'danger' ? 'notif-card--danger' : ($sev === 'warning' ? 'notif-card--warning' : 'notif-card--info');
      ?>
        <div class="notif-card <?php echo $cls; ?>">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="fw-bold"><?php echo htmlspecialchars((string)$n['title']); ?></div>
            <small class="text-muted text-nowrap"><?php echo htmlspecialchars((string)$n['created_at']); ?></small>
          </div>
          <div class="mt-1 small text-muted"><?php echo htmlspecialchars((string)$n['message']); ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>