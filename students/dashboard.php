<?php
declare(strict_types=1);

session_start();
session_regenerate_id(true);

if (!isset($_SESSION['student_id'])) {
  header('Location: ../login.php');
  exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

require_once __DIR__ . '/../connection.php';

$studentId = (int)$_SESSION['student_id'];

$latest = $pdo->prepare("
  SELECT * FROM water_diagnostics 
  WHERE student_id = :sid 
  ORDER BY created_at DESC, id DESC 
  LIMIT 1
");
$latest->execute([':sid' => $studentId]);
$diag = $latest->fetch();

$ph = $turb = $tds = $temp = $fluor = null;
$safeStatus = 'safe';
$updatedAt = '';

if ($diag) {
  $ph = isset($diag['ph']) ? (float)$diag['ph'] : null;
  $turb = isset($diag['turbidity']) ? (float)$diag['turbidity'] : null;
  $tds = isset($diag['tds']) ? (float)$diag['tds'] : null;
  $temp = isset($diag['temperature_c']) ? (float)$diag['temperature_c'] : null;
  $fluor = isset($diag['fluorescence']) ? (float)$diag['fluorescence'] : null;
  $safeStatus = (string)($diag['safe_status'] ?? 'safe');
  $updatedAt = (string)$diag['created_at'];
}

// Unread notifications count
$notifStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE student_id = :sid AND is_read = 0');
$notifStmt->execute([':sid' => $studentId]);
$unreadCount = (int)$notifStmt->fetchColumn();

// Sensor status helper
function sensorStatus($value, $low, $high): string {
  if ($value === null) return 'none';
  return ($value >= $low && $value <= $high) ? 'safe' : 'unsafe';
}

$sensors = [
  ['label' => 'pH Level',      'value' => $ph,    'low' => 6.5,  'high' => 8.5,   'unit' => '',    'icon' => 'bi-droplet-half',     'desc' => 'Acidity / Alkalinity',  'range' => '6.5 – 8.5'],
  ['label' => 'Turbidity',     'value' => $turb,  'low' => 0,    'high' => 5,     'unit' => 'NTU', 'icon' => 'bi-eye',              'desc' => 'Water Clarity',         'range' => '0 – 5 NTU'],
  ['label' => 'TDS',           'value' => $tds,   'low' => 0,    'high' => 500,   'unit' => 'ppm', 'icon' => 'bi-moisture',         'desc' => 'Dissolved Solids',      'range' => '0 – 500 ppm'],
  ['label' => 'Temperature',   'value' => $temp,  'low' => 20,   'high' => 30,    'unit' => '°C',  'icon' => 'bi-thermometer-half', 'desc' => 'Water Temperature',     'range' => '20 – 30°C'],
  ['label' => 'Fluorescence',  'value' => $fluor, 'low' => 0,    'high' => 2000,  'unit' => '',    'icon' => 'bi-lightbulb',        'desc' => 'Contaminant Detection', 'range' => '0 – 2,000'],
];

// Count safe/unsafe/no-data sensors
$safeCount = 0; $unsafeCount = 0; $noData = 0;
foreach ($sensors as $s) {
  $st = sensorStatus($s['value'], $s['low'], $s['high']);
  if ($st === 'safe') $safeCount++;
  elseif ($st === 'unsafe') $unsafeCount++;
  else $noData++;
}

if (isset($_GET['new']) && $_GET['new'] === '1') {
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            icon: "success",
            title: "Account Created!",
            text: "Your SR-Code has been registered successfully. Welcome to AquaLaRion!",
            confirmButtonColor: "#C8102E"
        });
    });
    </script>';
}

// Percentage for gauge
function sensorPercent($value, $low, $high): int {
  if ($value === null) return 0;
  $range = $high - $low;
  if ($range <= 0) return 50;
  $pos = (($value - $low) / $range) * 100;
  return max(0, min(100, (int)round($pos)));
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    /* ── Loading Effect Settings ── */
    :root {
      --glass-bg: linear-gradient(135deg, #d4f8e8 0%, #e0f7ff 100%);
      --glass-overlay: linear-gradient(135deg, rgba(212,248,232,0.85) 0%, rgba(224,247,255,0.85) 100%);
      --blur-strength: 4px;
      --reveal-delay: 280ms;
    }

    /* ── Hero Banner ── */
    .dash-hero {
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
      border-radius: 16px;
      padding: 2rem 2rem 1.75rem;
      color: #fff;
      position: relative;
      overflow: hidden;
    }
    .dash-hero::before {
      content: '';
      position: absolute;
      top: -40%;
      right: -10%;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(200,16,46,0.15) 0%, transparent 70%);
      border-radius: 50%;
    }
    .dash-hero::after {
      content: '';
      position: absolute;
      bottom: -30%;
      left: 20%;
      width: 200px;
      height: 200px;
      background: radial-gradient(circle, rgba(13,110,253,0.08) 0%, transparent 70%);
      border-radius: 50%;
    }
    .dash-hero-inner {
      position: relative;
      z-index: 1;
    }
    .dash-hero-avatar {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--red-primary), var(--red-dark));
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.15rem;
      color: #fff;
      flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(200,16,46,0.35);
    }
    .dash-hero-greeting {
      font-size: 0.78rem;
      color: rgba(255,255,255,0.5);
      text-transform: uppercase;
      letter-spacing: 1.5px;
      font-weight: 600;
    }
    .dash-hero-name {
      font-size: 1.35rem;
      font-weight: 800;
      letter-spacing: -0.3px;
      margin-top: 2px;
    }
    .dash-hero-school {
      font-size: 0.8rem;
      color: rgba(255,255,255,0.45);
      margin-top: 2px;
    }
    .dash-hero-status {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
    }
    .dash-hero-status.safe {
      background: rgba(5,150,105,0.15);
      color: #34D399;
      border: 1px solid rgba(5,150,105,0.25);
    }
    .dash-hero-status.unsafe {
      background: rgba(220,38,38,0.15);
      color: #F87171;
      border: 1px solid rgba(220,38,38,0.25);
      animation: pulse-red 2s ease-in-out infinite;
    }
    @keyframes pulse-red {
      0%, 100% { box-shadow: 0 0 0 0 rgba(220,38,38,0.2); }
      50% { box-shadow: 0 0 0 8px rgba(220,38,38,0); }
    }
    .dash-hero-status i { font-size: 1.1rem; }
    .dash-hero-meta {
      display: flex;
      gap: 1.5rem;
      margin-top: 1.25rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(255,255,255,0.06);
    }
    .dash-meta-item {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.75rem;
      color: rgba(255,255,255,0.4);
    }
    .dash-meta-item i { font-size: 0.85rem; color: rgba(255,255,255,0.25); }
    .dash-meta-item strong { color: rgba(255,255,255,0.7); font-weight: 600; }

    /* ── Sensor Section ── */
    .sensor-section {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
      overflow: hidden;
    }
    .sensor-section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid #f1f3f5;
    }
    .sensor-section-title {
      font-weight: 700;
      font-size: 1rem;
      margin: 0;
      letter-spacing: -0.3px;
      color: var(--dark);
    }
    .sensor-section-sub {
      font-size: 0.75rem;
      color: #9CA3AF;
      margin-top: 2px;
    }

    /* CSS Grid for sensor cards */
    .sensor-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0;
    }
    .sensor-grid .sensor-card {
      border-radius: 0;
      border-right: 1px solid #f1f3f5;
      border-bottom: 1px solid #f1f3f5;
      background: var(--glass-bg);
      transition: all 0.25s ease;
      position: relative;
      filter: blur(var(--blur-strength));
      opacity: 0.3;
      transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sensor-grid .sensor-card.revealed {
      filter: blur(0);
      opacity: 1;
      background: #fff;
    }
    .sensor-grid .sensor-card:nth-child(3n) { border-right: none; }
    .sensor-grid .sensor-card:nth-last-child(-n+3) { border-bottom: none; }
    .sensor-grid .sensor-card:nth-last-child(-n+2):nth-child(n+4) { border-bottom: none; }

    .sensor-grid .sensor-card:hover {
      background: #fafbfc;
      z-index: 1;
    }

    @media (max-width: 991px) {
      .sensor-grid { grid-template-columns: repeat(2, 1fr); }
      .sensor-grid .sensor-card { border-right: 1px solid #f1f3f5; border-bottom: 1px solid #f1f3f5; }
      .sensor-grid .sensor-card:nth-child(2n) { border-right: none; }
      .sensor-grid .sensor-card:last-child { border-bottom: none; }
      .sensor-grid .sensor-card:nth-last-child(2):nth-child(odd) { border-bottom: none; }
    }
    @media (max-width: 575px) {
      .sensor-grid { grid-template-columns: 1fr; }
      .sensor-grid .sensor-card { border-right: none; }
      .sensor-grid .sensor-card:last-child { border-bottom: none; }
    }

    /* ── Glass Shimmer Overlay ── */
    .glass-shimmer {
      position: absolute;
      inset: 0;
      background: var(--glass-overlay);
      backdrop-filter: blur(6px);
      z-index: 2;
      overflow: hidden;
      transition: opacity 0.6s ease, visibility 0.6s ease;
    }
    .glass-shimmer::after {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
      animation: glassSweep 2s infinite linear;
    }
    @keyframes glassSweep {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
    }
    .sensor-card.revealed .glass-shimmer {
      opacity: 0;
      visibility: hidden;
    }

    .sensor-card-body {
      padding: 1.25rem 1.5rem;
      position: relative;
      z-index: 1;
    }
    .sensor-card-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 0.85rem;
    }
    .sensor-icon-wrap {
      width: 42px;
      height: 42px;
      border-radius: 11px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.05rem;
    }
    .sensor-icon-wrap.safe   { background: #E8F8F0; color: #059669; }
    .sensor-icon-wrap.unsafe { background: #FEE2E2; color: #DC2626; }
    .sensor-icon-wrap.none   { background: #F3F4F6; color: #9CA3AF; }

    .sensor-status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      margin-top: 6px;
    }
    .sensor-status-dot.safe   { background: #10B981; box-shadow: 0 0 6px rgba(16,185,129,0.4); }
    .sensor-status-dot.unsafe { background: #EF4444; box-shadow: 0 0 6px rgba(239,68,68,0.4); }
    .sensor-status-dot.none   { background: #D1D5DB; }

    .sensor-label {
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: #9CA3AF;
      font-weight: 600;
    }
    .sensor-value {
      font-size: 1.65rem;
      font-weight: 800;
      color: var(--dark);
      line-height: 1.1;
      margin: 4px 0 2px;
      letter-spacing: -0.5px;
    }
    .sensor-value .sensor-unit {
      font-size: 0.8rem;
      font-weight: 500;
      color: #9CA3AF;
      margin-left: 2px;
    }
    .sensor-desc {
      font-size: 0.73rem;
      color: #B0B7C3;
    }

    .sensor-gauge {
      margin-top: 0.75rem;
    }
    .sensor-gauge-bar {
      height: 4px;
      border-radius: 4px;
      background: #F0F1F3;
      overflow: hidden;
    }
    .sensor-gauge-fill {
      height: 100%;
      border-radius: 4px;
      transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .sensor-gauge-fill.safe   { background: linear-gradient(90deg, #10B981, #34D399); }
    .sensor-gauge-fill.unsafe { background: linear-gradient(90deg, #EF4444, #F87171); }
    .sensor-gauge-fill.none   { background: #D1D5DB; }
    .sensor-gauge-range {
      display: flex;
      justify-content: space-between;
      margin-top: 4px;
      font-size: 0.65rem;
      color: #C4C9D4;
      font-weight: 500;
    }

    .usage-guide-card {
      background: #FFF8E1;
      border: 1px solid #FFE082;
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }
    .usage-guide-header {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 1rem;
    }
    .usage-guide-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: #F59E0B;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      flex-shrink: 0;
    }
    .usage-guide-title {
      font-weight: 700;
      font-size: 1rem;
      color: #92400E;
      margin: 0;
    }
    .usage-guide-subtitle {
      font-size: 0.8rem;
      color: #B45309;
      margin-top: 2px;
    }
    .usage-guide-list {
      padding-left: 1.25rem;
      margin-bottom: 0;
    }
    .usage-guide-list li {
      font-size: 0.85rem;
      color: #78350F;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .usage-guide-list li i {
      color: #F59E0B;
      font-size: 0.9rem;
    }
    .usage-guide-note {
      font-size: 0.75rem;
      color: #92400E;
      margin-top: 1rem;
      padding-top: 0.75rem;
      border-top: 1px solid rgba(245, 158, 11, 0.2);
    }

    .quick-action {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0.85rem 1rem;
      border-radius: 12px;
      background: #fff;
      border: 1px solid #F0F1F3;
      text-decoration: none;
      color: var(--dark);
      transition: all 0.2s;
    }
    .quick-action:hover {
      border-color: var(--red-primary);
      background: rgba(200,16,46,0.02);
      color: var(--dark);
      box-shadow: 0 4px 12px rgba(200,16,46,0.08);
      transform: translateY(-1px);
    }
    .quick-action-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      flex-shrink: 0;
    }
    .quick-action-label {
      font-weight: 600;
      font-size: 0.85rem;
    }
    .quick-action-sub {
      font-size: 0.7rem;
      color: #9CA3AF;
    }
    .quick-action .bi-chevron-right {
      margin-left: auto;
      color: #D1D5DB;
      font-size: 0.8rem;
    }

    .summary-ring {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .summary-ring.safe {
      background: conic-gradient(#10B981 0deg, #10B981 calc(var(--pct) * 3.6deg), #F0F1F3 calc(var(--pct) * 3.6deg));
    }
    .summary-ring.unsafe {
      background: conic-gradient(#EF4444 0deg, #EF4444 calc(var(--pct) * 3.6deg), #F0F1F3 calc(var(--pct) * 3.6deg));
    }
    .summary-ring-inner {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      background: #fff;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .summary-ring-value {
      font-size: 1rem;
      font-weight: 800;
      color: var(--dark);
      line-height: 1;
    }
    .summary-ring-label {
      font-size: 0.55rem;
      color: #9CA3AF;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
      margin-top: 1px;
    }
  </style>
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'dashboard'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
  <?php $pageTitle = 'Dashboard'; $pageIcon = 'bi-speedometer2'; include __DIR__ . '/_topbar.php'; ?>
  <div class="p-4">

    <!-- ── Hero Banner ── -->
    <div class="dash-hero mb-4">
      <div class="dash-hero-inner">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="dash-hero-avatar"><?php echo strtoupper(substr((string)($_SESSION['student_full_name'] ?? 'S'), 0, 1)); ?></div>
            <div>
              <div class="dash-hero-greeting">Welcome, </div>
              <div class="dash-hero-name"><?php echo htmlspecialchars((string)($_SESSION['student_full_name'] ?? 'Student')); ?></div>
              <div class="dash-hero-school"><?php echo htmlspecialchars((string)($_SESSION['student_school_name'] ?? '')); ?></div>
            </div>
          </div>
          <div class="dash-hero-status <?php echo $safeStatus; ?>">
            <i class="bi <?php echo $safeStatus === 'unsafe' ? 'bi-exclamation-triangle-fill' : 'bi-shield-check'; ?>"></i>
            Water is <?php echo strtoupper($safeStatus); ?>
          </div>
        </div>
        <div class="dash-hero-meta">
          <div class="dash-meta-item">
            <i class="bi bi-clock"></i>
            <?php if ($updatedAt): ?>
              Last updated: <strong><?php echo htmlspecialchars($updatedAt); ?></strong>
            <?php else: ?>
              No readings yet
            <?php endif; ?>
          </div>
          <div class="dash-meta-item">
            <i class="bi bi-check2-circle"></i>
            <strong><?php echo $safeCount; ?></strong>/<?php echo count($sensors); ?> sensors OK
          </div>
          <?php if ($unreadCount > 0): ?>
          <div class="dash-meta-item">
            <i class="bi bi-bell"></i>
            <strong><?php echo $unreadCount; ?></strong> unread notification<?php echo $unreadCount > 1 ? 's' : ''; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
            
    <!-- ── Unsafe Water Usage Guide ── -->
    <?php if ($safeStatus === 'unsafe'): ?>
    <div class="usage-guide-card">
      <div class="usage-guide-header">
        <div class="usage-guide-icon">
          <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div>
          <h5 class="usage-guide-title">This water is NOT safe for drinking or personal use</h5>
          <p class="usage-guide-subtitle">It can still be safely used for these purposes around school:</p>
        </div>
      </div>
      <ul class="usage-guide-list">
        <li><i class="bi bi-check-circle-fill"></i> Watering school gardens and plants</li>
        <li><i class="bi bi-check-circle-fill"></i> Cleaning classrooms, hallways, and facilities</li>
        <li><i class="bi bi-check-circle-fill"></i> Flushing toilets and restroom use</li>
        <li><i class="bi bi-check-circle-fill"></i> Washing school equipment and tools</li>
        <li><i class="bi bi-check-circle-fill"></i> Filling science experiment tanks (non-consumable)</li>
      </ul>
      <p class="usage-guide-note">
        <strong>Important:</strong> Do not drink, wash food, brush teeth, or bathe with this water. Always use properly treated water for personal needs.
      </p>
    </div>
    <?php endif; ?>

    <!-- ── Sensor Readings ── -->
    <?php
      $totalActive = $safeCount + $unsafeCount;
      $qualityPct = $totalActive > 0 ? round(($safeCount / $totalActive) * 100) : 0;
    ?>
    <div class="sensor-section mb-4">
      <div class="sensor-section-header">
        <div>
          <h6 class="sensor-section-title">Sensor Readings</h6>
          <div class="sensor-section-sub">Real-time water quality parameters</div>
        </div>
        <div class="summary-ring <?php echo $unsafeCount > 0 ? 'unsafe' : 'safe'; ?>" style="--pct:<?php echo $qualityPct; ?>;">
          <div class="summary-ring-inner">
            <div class="summary-ring-value"><?php echo $qualityPct; ?>%</div>
            <div class="summary-ring-label">Quality</div>
          </div>
        </div>
      </div>
      <div class="sensor-grid">
        <?php foreach ($sensors as $s):
          $st = sensorStatus($s['value'], $s['low'], $s['high']);
          $pct = sensorPercent($s['value'], $s['low'], $s['high']);
        ?>
        <div class="sensor-card">
          <div class="glass-shimmer"></div>
          <div class="sensor-card-body">
            <div class="sensor-card-top">
              <div class="sensor-icon-wrap <?php echo $st; ?>">
                <i class="bi <?php echo $s['icon']; ?>"></i>
              </div>
              <div class="sensor-status-dot <?php echo $st; ?>"></div>
            </div>
            <div class="sensor-label"><?php echo htmlspecialchars($s['label']); ?></div>
            <div class="sensor-value">
              <?php echo $s['value'] !== null ? htmlspecialchars((string)$s['value']) : '—'; ?>
              <?php if ($s['value'] !== null && $s['unit']): ?>
                <span class="sensor-unit"><?php echo $s['unit']; ?></span>
              <?php endif; ?>
            </div>
            <div class="sensor-desc"><?php echo htmlspecialchars($s['desc']); ?></div>
            <div class="sensor-gauge">
              <div class="sensor-gauge-bar">
                <div class="sensor-gauge-fill <?php echo $st; ?>" style="width:<?php echo $s['value'] !== null ? $pct : 0; ?>%;"></div>
              </div>
              <div class="sensor-gauge-range">
                <span>Safe: <?php echo $s['range']; ?></span>
                <?php if ($st === 'safe'): ?>
                  <span style="color:#10B981;">In Range</span>
                <?php elseif ($st === 'unsafe'): ?>
                  <span style="color:#EF4444;">Out of Range</span>
                <?php else: ?>
                  <span>No Data</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ── Quick Actions ── -->
    <div class="row g-3">
      <div class="col-12">
        <h6 class="fw-bold mb-3" style="letter-spacing:-0.3px;">Quick Actions</h6>
      </div>
      <div class="col-md-6 col-xl-3">
        <a href="water-info.php" class="quick-action">
          <div class="quick-action-icon" style="background:#EBF5FB;color:#2563EB;">
            <i class="bi bi-droplet-half"></i>
          </div>
          <div>
            <div class="quick-action-label">Water Info</div>
            <div class="quick-action-sub">Detailed sensor data</div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
      <div class="col-md-6 col-xl-3">
        <a href="my-usage.php" class="quick-action">
          <div class="quick-action-icon" style="background:#F3E8FF;color:#7C3AED;">
            <i class="bi bi-clock-history"></i>
          </div>
          <div>
            <div class="quick-action-label">My Usage</div>
            <div class="quick-action-sub">Reading history</div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
      <div class="col-md-6 col-xl-3">
        <a href="notifications.php" class="quick-action">
          <div class="quick-action-icon" style="background:#FFF8E1;color:#D97706;">
            <i class="bi bi-bell-fill"></i>
          </div>
          <div>
            <div class="quick-action-label">Notifications</div>
            <div class="quick-action-sub"><?php echo $unreadCount > 0 ? $unreadCount . ' unread' : 'All caught up'; ?></div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
      <div class="col-md-6 col-xl-3">
        <a href="profile.php" class="quick-action">
          <div class="quick-action-icon" style="background:#E8F8F0;color:#059669;">
            <i class="bi bi-person-circle"></i>
          </div>
          <div>
            <div class="quick-action-label">My Profile</div>
            <div class="quick-action-sub">Account settings</div>
          </div>
          <i class="bi bi-chevron-right"></i>
        </a>
      </div>
    </div>

  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
<script>
  history.pushState(null, null, location.href);
  window.onpopstate = function () {
    history.go(1);
  };
  document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.sensor-card');
    cards.forEach((card, i) => {
      setTimeout(() => card.classList.add('revealed'), i * 280);
    });
  });
</script>
</body>
</html>