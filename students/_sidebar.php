<?php
$navItems = [
  ['href'=>'dashboard.php',    'key'=>'dashboard',     'icon'=>'bi-speedometer2',  'label'=>'Dashboard'],
  ['href'=>'water-info.php',   'key'=>'water-info',    'icon'=>'bi-droplet-half',  'label'=>'Water Info'],
  ['href'=>'my-usage.php',     'key'=>'my-usage',      'icon'=>'bi-clock-history', 'label'=>'My Usage'],
  ['href'=>'notifications.php','key'=>'notifications',  'icon'=>'bi-bell-fill',    'label'=>'Notifications'],
];
$_activeKey = $activePage ?? '';
$_studentName = (string)($_SESSION['student_full_name'] ?? 'Student');
$_studentSR = (string)($_SESSION['student_sr_code'] ?? '');
$_studentInitial = strtoupper(substr($_studentName, 0, 1));
?>
<div id="adminSidebar">
  <div class="sb-brand">
    <a href="dashboard.php">
      <div class="sb-brand-logo">
        <img src="../bsu_logo.png" alt="BSU">
      </div>
      <div>
        <div class="sb-brand-name">AQUALARION</div>
        <div class="sb-brand-sub">Student Portal</div>
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
