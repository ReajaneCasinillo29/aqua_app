<?php
$_tbName = (string)($_SESSION['student_full_name'] ?? 'Student');
$_tbSR = (string)($_SESSION['student_sr_code'] ?? '');
$_tbInitial = strtoupper(substr($_tbName, 0, 1));
$_tbTitle = $pageTitle ?? 'Dashboard';
$_tbIcon = $pageIcon ?? 'bi-speedometer2';

$_tbNotifs = [];
$_tbNotifCount = 0;
if (isset($pdo) && isset($_SESSION['student_id'])) {
    $_sid = (int)$_SESSION['student_id'];
    $__stmt = $pdo->prepare("SELECT id, title, message, severity, is_read, created_at FROM notifications WHERE student_id = :sid ORDER BY created_at DESC LIMIT 5");
    $__stmt->execute([':sid' => $_sid]);
    $_tbNotifs = $__stmt->fetchAll();
    $__stmt2 = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE student_id = :sid AND is_read = 0");
    $__stmt2->execute([':sid' => $_sid]);
    $_tbNotifCount = (int)$__stmt2->fetchColumn();
}
?>
<div class="topbar">
  <button class="btn btn-light btn-sm d-lg-none" id="sidebarToggle" onclick="toggleSidebar()">
    <i class="bi bi-list fs-5"></i>
  </button>

  <div class="d-flex align-items-center gap-2">
    <div class="page-header-icon"><i class="bi <?php echo $_tbIcon; ?>"></i></div>
    <span class="fw-semibold"><?php echo htmlspecialchars($_tbTitle); ?></span>
  </div>

  <div class="ms-auto topbar-actions">
    <div class="topbar-notif">
      <button class="topbar-btn" onclick="toggleNotifDropdown(event)" title="Notifications">
        <i class="bi bi-bell"></i>
        <?php if ($_tbNotifCount > 0): ?>
          <span class="notif-dot"></span>
        <?php endif; ?>
      </button>
      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-dropdown-header">
          <span class="fw-semibold" style="font-size:0.85rem;">Notifications</span>
          <?php if ($_tbNotifCount > 0): ?>
            <span class="badge-info" style="font-size:0.7rem;"><?php echo $_tbNotifCount; ?> unread</span>
          <?php endif; ?>
        </div>
        <div class="notif-dropdown-body">
          <?php if (empty($_tbNotifs)): ?>
            <div class="notif-dropdown-empty">
              <i class="bi bi-bell-slash"></i>
              <div>No notifications yet</div>
            </div>
          <?php else: ?>
            <?php foreach ($_tbNotifs as $_n):
              $__sev = (string)($_n['severity'] ?? 'info');
              $__iconClass = $__sev === 'danger' ? 'danger' : ($__sev === 'warning' ? 'warning' : 'info');
              $__icon = $__sev === 'danger' ? 'exclamation-triangle-fill' : ($__sev === 'warning' ? 'exclamation-circle-fill' : 'info-circle-fill');
            ?>
              <div class="notif-dropdown-item<?php echo (int)($_n['is_read'] ?? 0) === 0 ? ' unread' : ''; ?>">
                <div class="notif-dropdown-icon notif-dropdown-icon--<?php echo $__iconClass; ?>">
                  <i class="bi bi-<?php echo $__icon; ?>"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                  <div class="notif-dropdown-title"><?php echo htmlspecialchars((string)$_n['title']); ?></div>
                  <div class="notif-dropdown-text"><?php echo htmlspecialchars(mb_strimwidth((string)$_n['message'], 0, 60, '...')); ?></div>
                  <div class="notif-dropdown-time"><?php echo htmlspecialchars((string)$_n['created_at']); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <a href="notifications.php" class="notif-dropdown-footer">
          View all notifications <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    </div>

    <div class="topbar-divider"></div>

    <div class="topbar-profile">
      <button class="topbar-profile-btn" onclick="toggleProfileDropdown(event)">
        <div class="topbar-profile-avatar"><?php echo htmlspecialchars($_tbInitial); ?></div>
        <div class="topbar-profile-info d-none d-lg-block">
          <div class="topbar-profile-name"><?php echo htmlspecialchars($_tbName); ?></div>
          <div class="topbar-profile-role"><?php echo htmlspecialchars($_tbSR); ?></div>
        </div>
        <i class="bi bi-chevron-down d-none d-lg-block" style="font-size:0.65rem;color:#9ca3af;"></i>
      </button>
      <div class="topbar-dropdown" id="profileDropdown">
        <div class="topbar-dropdown-header">
          <div class="fw-semibold" style="font-size:0.85rem;"><?php echo htmlspecialchars($_tbName); ?></div>
          <div class="text-muted" style="font-size:0.72rem;"><?php echo htmlspecialchars($_tbSR); ?></div>
        </div>
        <a href="profile.php" class="topbar-dropdown-item">
          <i class="bi bi-person"></i> My Profile
        </a>
        <div class="topbar-dropdown-divider"></div>
        <a href="../logout.php" class="topbar-dropdown-item text-danger btn-logout">
          <i class="bi bi-box-arrow-left"></i> Logout
        </a>
      </div>
    </div>
  </div>
</div>