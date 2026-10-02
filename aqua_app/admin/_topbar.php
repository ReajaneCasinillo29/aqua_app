<?php
$_tbUser = (string)($_SESSION['admin_username'] ?? 'admin');
$_tbInitial = strtoupper(substr($_tbUser, 0, 1));
$_tbTitle = $pageTitle ?? 'Dashboard';
$_tbIcon = $pageIcon ?? 'bi-speedometer2';

$_tbAlerts = [];
$_tbAlertCount = 0;
if (isset($pdo)) {
    $_tbAlerts = $pdo->query("SELECT id, title, severity, details, created_at FROM alerts ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $_tbAlertCount = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
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
      <button class="topbar-btn" onclick="toggleNotifDropdown(event)" title="Alerts">
        <i class="bi bi-bell"></i>
        <?php if ($_tbAlertCount > 0): ?>
          <span class="notif-dot"></span>
        <?php endif; ?>
      </button>
      <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-dropdown-header">
          <span class="fw-semibold" style="font-size:0.85rem;">Alerts</span>
          <?php if ($_tbAlertCount > 0): ?>
            <span class="badge-danger" style="font-size:0.7rem;"><?php echo $_tbAlertCount; ?> recent</span>
          <?php endif; ?>
        </div>
        <div class="notif-dropdown-body">
          <?php if (empty($_tbAlerts)): ?>
            <div class="notif-dropdown-empty">
              <i class="bi bi-bell-slash"></i>
              <div>No alerts yet</div>
            </div>
          <?php else: ?>
            <?php foreach ($_tbAlerts as $_a): ?>
              <div class="notif-dropdown-item">
                <div class="notif-dropdown-icon notif-dropdown-icon--<?php echo ($_a['severity'] ?? 'warning') === 'danger' ? 'danger' : 'warning'; ?>">
                  <i class="bi bi-<?php echo ($_a['severity'] ?? 'warning') === 'danger' ? 'exclamation-triangle-fill' : 'exclamation-circle-fill'; ?>"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                  <div class="notif-dropdown-title"><?php echo htmlspecialchars((string)$_a['title']); ?></div>
                  <?php if (!empty($_a['details'])): ?>
                    <div class="notif-dropdown-text"><?php echo htmlspecialchars(mb_strimwidth((string)$_a['details'], 0, 60, '...')); ?></div>
                  <?php endif; ?>
                  <div class="notif-dropdown-time"><?php echo htmlspecialchars((string)$_a['created_at']); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <a href="alerts.php" class="notif-dropdown-footer">
          View all alerts <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    </div>

    <a href="maintenance.php" class="topbar-btn d-none d-sm-flex" title="Maintenance">
      <i class="bi bi-tools"></i>
    </a>

    <div class="topbar-divider"></div>

    <div class="topbar-profile">
      <button class="topbar-profile-btn" onclick="toggleProfileDropdown(event)">
        <div class="topbar-profile-avatar"><?php echo htmlspecialchars($_tbInitial); ?></div>
        <div class="topbar-profile-info d-none d-lg-block">
          <div class="topbar-profile-name"><?php echo htmlspecialchars($_tbUser); ?></div>
          <div class="topbar-profile-role">Administrator</div>
        </div>
        <i class="bi bi-chevron-down d-none d-lg-block" style="font-size:0.65rem;color:#9ca3af;"></i>
      </button>
      <div class="topbar-dropdown" id="profileDropdown">
        <div class="topbar-dropdown-header">
          <div class="fw-semibold" style="font-size:0.85rem;"><?php echo htmlspecialchars($_tbUser); ?></div>
          <div class="text-muted" style="font-size:0.72rem;">Administrator</div>
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