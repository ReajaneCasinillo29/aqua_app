<?php
// _sidebar.php — shared admin sidebar. Set $activePage before including.
$navItems = [
  ['href'=>'dashboard.php',    'key'=>'dashboard',     'icon'=>'bi-speedometer2', 'label'=>'Dashboard'],
  ['href'=>'water-quality.php','key'=>'water-quality', 'icon'=>'bi-droplet-half', 'label'=>'Water Quality'],
  ['href'=>'students.php',     'key'=>'students',      'icon'=>'bi-people-fill',  'label'=>'Students'],
  ['href'=>'battery-solar.php','key'=>'battery-solar', 'icon'=>'bi-sun-fill',     'label'=>'Solar & Battery'],
  ['href'=>'maintenance.php',  'key'=>'maintenance',   'icon'=>'bi-tools',        'label'=>'Maintenance'],
  ['href'=>'alerts.php',       'key'=>'alerts',        'icon'=>'bi-bell-fill',    'label'=>'Alerts'],
];
$_activeKey = $activePage ?? '';
$_adminUser = (string)($_SESSION['admin_username'] ?? 'admin');
$_adminInitial = strtoupper(substr($_adminUser, 0, 1));
?>
<div id="adminSidebar">
  <div class="sb-brand">
    <a href="dashboard.php">
      <div class="sb-brand-logo">
        <img src="../bsu_logo.png" alt="BSU">
      </div>
      <div>
        <div class="sb-brand-name">AQUALARION</div>
        <div class="sb-brand-sub">Admin Panel</div>
      </div>
    </a>
  </div>
  <nav class="sb-nav">
    <?php foreach ($navItems as $item): ?>
      <a href="<?php echo htmlspecialchars($item['href']); ?>"
         class="sb-link<?php echo $_activeKey === $item['key'] ? ' active' : ''; ?>">
        <span class="sb-icon"><i class="bi <?php echo $item['icon']; ?>"></i></span>
        <span><?php echo htmlspecialchars($item['label']); ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sb-footer">
    <div class="text-white-50" style="font-size:0.65rem;text-align:center;letter-spacing:0.5px;">AQUALARION v1.0</div>
  </div>
</div>
<div id="sidebarOverlay"></div>
