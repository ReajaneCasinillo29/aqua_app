<?php
declare(strict_types=1);


session_start();
session_regenerate_id(true);

if (!isset($_SESSION['admin_id'])) {
  header('Location: ../login.php');
  exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

require_once __DIR__ . '/../connection.php';

$studentCount = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();

$studentCount = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$activeStudents = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn();
$alertCount = (int)$pdo->query('SELECT COUNT(*) FROM alerts')->fetchColumn();
$unsafeCount = (int)$pdo->query("SELECT COUNT(*) FROM water_diagnostics WHERE safe_status = 'unsafe'")->fetchColumn();
$safeCount = (int)$pdo->query("SELECT COUNT(*) FROM water_diagnostics WHERE safe_status = 'safe'")->fetchColumn();
$maintOpen = (int)$pdo->query("SELECT COUNT(*) FROM maintenance WHERE status != 'done'")->fetchColumn();
$totalReadings = (int)$pdo->query('SELECT COUNT(*) FROM water_diagnostics')->fetchColumn();

$latestDiag = $pdo->query('SELECT * FROM water_diagnostics ORDER BY id DESC LIMIT 1')->fetch();
$latestSafe = $latestDiag ? ($latestDiag['safe_status'] ?? 'safe') : 'safe';
$avgPh = (float)$pdo->query('SELECT AVG(ph) FROM water_diagnostics')->fetchColumn();
$avgTurb = (float)$pdo->query('SELECT AVG(turbidity) FROM water_diagnostics')->fetchColumn();
$avgTds = (float)$pdo->query('SELECT AVG(tds) FROM water_diagnostics')->fetchColumn();

$latestSolar = $pdo->query('SELECT * FROM solar_battery ORDER BY id DESC LIMIT 1')->fetch();
$solarPower = $latestSolar ? $latestSolar['solar_power_w'] : null;
$battLevel = $latestSolar ? $latestSolar['battery_level'] : null;
$battStatus = $latestSolar ? (string)($latestSolar['battery_status'] ?? 'normal') : 'normal';
$solarVolt = $latestSolar ? $latestSolar['solar_voltage'] : null;

// Chart data — last 7 readings
$chartRows = $pdo->query('SELECT ph, turbidity, tds, temperature_c, safe_status, created_at FROM water_diagnostics ORDER BY created_at ASC LIMIT 20')->fetchAll();
$chartLabels = []; $chartPh = []; $chartTurb = []; $chartTds = []; $chartTemp = [];
foreach ($chartRows as $cr) {
    $chartLabels[] = date('M d H:i', strtotime($cr['created_at']));
    $chartPh[] = (float)$cr['ph'];
    $chartTurb[] = (float)$cr['turbidity'];
    $chartTds[] = (float)$cr['tds'];
    $chartTemp[] = (float)$cr['temperature_c'];
}

// Solar chart data
$solarRows = $pdo->query('SELECT solar_power_w, battery_level, created_at FROM solar_battery ORDER BY created_at ASC LIMIT 20')->fetchAll();
$solarLabels = []; $solarPowerData = []; $solarBattData = [];
foreach ($solarRows as $sr) {
    $solarLabels[] = date('M d H:i', strtotime($sr['created_at']));
    $solarPowerData[] = (float)$sr['solar_power_w'];
    $solarBattData[] = (int)$sr['battery_level'];
}

$recentDiags = $pdo->query('SELECT ph, turbidity, tds, temperature_c, fluorescence, safe_status, created_at FROM water_diagnostics ORDER BY id DESC LIMIT 5')->fetchAll();
$recentAlerts = $pdo->query('SELECT title, severity, details, created_at FROM alerts ORDER BY id DESC LIMIT 4')->fetchAll();

function battLevelClass(?int $lvl): string {
    if ($lvl === null) return 'bg-secondary';
    if ($lvl < 20) return 'bg-danger';
    if ($lvl < 50) return 'bg-warning';
    return 'bg-success';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - AQUALARION</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    .dash-stat { transition: transform 0.2s, box-shadow 0.2s; cursor: default; }
    .dash-stat:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
    .dash-stat .card-body { padding: 1.1rem 1.25rem; }
    .dash-stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .dash-stat-label { font-size: 0.72rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }
    .dash-stat-value { font-size: 1.4rem; font-weight: 800; color: var(--dark); line-height: 1.2; letter-spacing: -0.5px; }
    .dash-stat-sub { font-size: 0.68rem; color: #9ca3af; margin-top: 2px; }

    .chart-card { min-height: 320px; }
    .chart-card canvas { max-height: 260px; }

    .activity-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
    .activity-line { position: relative; padding-left: 28px; }
    .activity-line::before { content: ''; position: absolute; left: 4px; top: 16px; bottom: -12px; width: 2px; background: #eef0f2; }
    .activity-line:last-child::before { display: none; }
    .activity-line .activity-dot { position: absolute; left: 0; top: 5px; }

    .gauge-ring { width: 90px; height: 90px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  </style>
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'dashboard'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
  <?php $pageTitle = 'Dashboard'; $pageIcon = 'bi-speedometer2'; include __DIR__ . '/_topbar.php'; ?>
  <div class="p-3 p-md-4">

    <!-- ═══ Welcome ═══ -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
      <div>
        <h4 class="fw-bold mb-1" style="letter-spacing:-0.5px;">Good <?php echo date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening'); ?>, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></h4>
        <p class="text-muted mb-0 small">Here's your water monitoring overview for <?php echo date('l, F j, Y'); ?>.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="water-quality.php" class="btn btn-primary btn-sm"><i class="bi bi-droplet-half me-1"></i>Water Quality</a>
        <a href="alerts.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-bell me-1"></i>Alerts</a>
      </div>
    </div>

    <!-- ═══ Stat Cards ═══ -->
    <div class="row g-3 mb-4">
      <!-- Water Safety -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card dash-stat">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="dash-stat-icon" style="background:#EBF5FB;color:#2563EB;">
              <i class="bi bi-shield-check"></i>
            </div>
            <div>
              <div class="dash-stat-label">Water</div>
              <div class="dash-stat-value">
                <?php if ($latestSafe === 'unsafe'): ?>
                  <span style="color:#DC2626;">UNSAFE</span>
                <?php else: ?>
                  <span style="color:#059669;">SAFE</span>
                <?php endif; ?>
              </div>
              <div class="dash-stat-sub">Latest reading</div>
            </div>
          </div>
        </div>
      </div>
      <!-- Students -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card dash-stat">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="dash-stat-icon" style="background:#F3E8FF;color:#7C3AED;">
              <i class="bi bi-people-fill"></i>
            </div>
            <div>
              <div class="dash-stat-label">Students</div>
              <div class="dash-stat-value"><?php echo $studentCount; ?></div>
              <div class="dash-stat-sub"><?php echo $activeStudents; ?> active</div>
            </div>
          </div>
        </div>
      </div>
      <!-- pH -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card dash-stat">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="dash-stat-icon" style="background:#E8F8F0;color:#059669;">
              <i class="bi bi-droplet-half"></i>
            </div>
            <div>
              <div class="dash-stat-label">Avg pH</div>
              <div class="dash-stat-value"><?php echo $avgPh > 0 ? number_format($avgPh, 1) : '--'; ?></div>
              <div class="dash-stat-sub">Safe: 6.5–8.5</div>
            </div>
          </div>
        </div>
      </div>
      <!-- Turbidity -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card dash-stat">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="dash-stat-icon" style="background:#FFF8E1;color:#D97706;">
              <i class="bi bi-eye"></i>
            </div>
            <div>
              <div class="dash-stat-label">Turbidity</div>
              <div class="dash-stat-value"><?php echo $avgTurb > 0 ? number_format($avgTurb, 1) : '--'; ?></div>
              <div class="dash-stat-sub">NTU avg</div>
            </div>
          </div>
        </div>
      </div>
      <!-- Solar -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card dash-stat">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="dash-stat-icon" style="background:#FFF3E0;color:#EA580C;">
              <i class="bi bi-sun-fill"></i>
            </div>
            <div>
              <div class="dash-stat-label">Solar</div>
              <div class="dash-stat-value"><?php echo $solarPower !== null ? number_format((float)$solarPower, 0) . 'W' : '--'; ?></div>
              <div class="dash-stat-sub"><?php echo $solarVolt !== null ? number_format((float)$solarVolt, 1) . 'V' : 'No data'; ?></div>
            </div>
          </div>
        </div>
      </div>
      <!-- Battery -->
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card dash-stat">
          <div class="card-body d-flex align-items-center gap-3">
            <div class="dash-stat-icon" style="background:#E0F7FA;color:#0891B2;">
              <i class="bi bi-battery-half"></i>
            </div>
            <div>
              <div class="dash-stat-label">Battery</div>
              <div class="dash-stat-value"><?php echo $battLevel !== null ? (int)$battLevel . '%' : '--'; ?></div>
              <?php if ($battLevel !== null): ?>
                <div class="progress mt-1" style="height:4px;width:60px;">
                  <div class="progress-bar <?php echo battLevelClass((int)$battLevel); ?>" style="width:<?php echo (int)$battLevel; ?>%"></div>
                </div>
              <?php else: ?>
                <div class="dash-stat-sub">No data</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ Charts Row ═══ -->
    <div class="row g-3 mb-4">
      <!-- Water Quality Chart -->
      <div class="col-lg-8">
        <div class="card chart-card">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div>
                <h6 class="fw-bold mb-0">Water Quality Trends</h6>
                <div class="text-muted" style="font-size:0.72rem;">pH, Turbidity, TDS readings over time</div>
              </div>
              <a href="water-quality.php" class="btn btn-sm btn-outline-primary">View Details</a>
            </div>
            <canvas id="waterChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Power & Battery Chart -->
      <div class="col-lg-4">
        <div class="card chart-card">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div>
                <h6 class="fw-bold mb-0">Power System</h6>
                <div class="text-muted" style="font-size:0.72rem;">Solar & battery status</div>
              </div>
              <a href="battery-solar.php" class="btn btn-sm btn-outline-secondary">Details</a>
            </div>
            <canvas id="powerChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ Data Row ═══ -->
    <div class="row g-3 mb-4">

      <!-- Recent Readings Table -->
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body p-0">
            <!-- ✅ Removed "View All" link here -->
            <div class="p-3 pb-0">
              <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-muted"></i>Recent Readings</h6>
            </div>
            <div class="table-responsive">
              <table class="table align-middle mb-0" style="font-size:0.85rem;">
                <thead>
                  <tr>
                    <th style="padding:0.65rem 1rem;background:#f8f9fb;color:#6b7280;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;border-bottom:2px solid #eef0f2;">Date</th>
                    <th style="padding:0.65rem 1rem;background:#f8f9fb;color:#6b7280;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;border-bottom:2px solid #eef0f2;">pH</th>
                    <th style="padding:0.65rem 1rem;background:#f8f9fb;color:#6b7280;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;border-bottom:2px solid #eef0f2;">Turbidity</th>
                    <th style="padding:0.65rem 1rem;background:#f8f9fb;color:#6b7280;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;border-bottom:2px solid #eef0f2;">TDS</th>
                    <th style="padding:0.65rem 1rem;background:#f8f9fb;color:#6b7280;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;border-bottom:2px solid #eef0f2;">Temp</th>
                    <th style="padding:0.65rem 1rem;background:#f8f9fb;color:#6b7280;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;border-bottom:2px solid #eef0f2;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($recentDiags)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No readings yet</td></tr>
                  <?php else: ?>
                    <?php foreach ($recentDiags as $d): ?>
                      <tr>
                        <td style="padding:0.6rem 1rem;border-bottom:1px solid #f1f3f5;color:#374151;"><?php echo date('M d, H:i', strtotime($d['created_at'])); ?></td>
                        <!-- ✅ Changed all colored numbers to black -->
                        <td style="padding:0.6rem 1rem;border-bottom:1px solid #f1f3f5;font-weight:600;color:#000000;"><?php echo htmlspecialchars((string)$d['ph']); ?></td>
                        <td style="padding:0.6rem 1rem;border-bottom:1px solid #f1f3f5;font-weight:600;color:#000000;"><?php echo htmlspecialchars((string)$d['turbidity']); ?></td>
                        <td style="padding:0.6rem 1rem;border-bottom:1px solid #f1f3f5;color:#000000;"><?php echo htmlspecialchars((string)$d['tds']); ?></td>
                        <td style="padding:0.6rem 1rem;border-bottom:1px solid #f1f3f5;color:#000000;"><?php echo htmlspecialchars((string)$d['temperature_c']); ?>°</td>
                        <td style="padding:0.6rem 1rem;border-bottom:1px solid #f1f3f5;">
                          <?php echo ($d['safe_status'] ?? 'safe') === 'unsafe'
                            ? '<span class="badge-unsafe">UNSAFE</span>'
                            : '<span class="badge-safe">SAFE</span>'; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column -->
      <div class="col-lg-4 d-flex flex-column gap-3">

        <!-- System Overview -->
        <div class="card">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-grid-fill me-2 text-muted"></i>System Overview</h6>
            <div class="d-flex flex-column gap-2">
              <a href="water-quality.php" class="d-flex align-items-center justify-content-between p-2 rounded text-decoration-none" style="background:var(--bg-body);transition:background 0.15s;" onmouseenter="this.style.background='#eef0f2'" onmouseleave="this.style.background='var(--bg-body)'">
                <div class="d-flex align-items-center gap-2">
                  <div style="width:32px;height:32px;border-radius:8px;background:#E8F8F0;color:#059669;display:flex;align-items:center;justify-content:center;font-size:0.85rem;"><i class="bi bi-check-circle-fill"></i></div>
                  <span class="small fw-semibold" style="color:var(--dark);">Safe Readings</span>
                </div>
                <span class="fw-bold" style="color:var(--dark);"><?php echo $safeCount; ?></span>
              </a>
              <a href="water-quality.php" class="d-flex align-items-center justify-content-between p-2 rounded text-decoration-none" style="background:var(--bg-body);transition:background 0.15s;" onmouseenter="this.style.background='#eef0f2'" onmouseleave="this.style.background='var(--bg-body)'">
                <div class="d-flex align-items-center gap-2">
                  <div style="width:32px;height:32px;border-radius:8px;background:#FEE2E2;color:#DC2626;display:flex;align-items:center;justify-content:center;font-size:0.85rem;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                  <span class="small fw-semibold" style="color:var(--dark);">Unsafe Readings</span>
                </div>
                <span class="fw-bold" style="color:var(--dark);"><?php echo $unsafeCount; ?></span>
              </a>
              <a href="alerts.php" class="d-flex align-items-center justify-content-between p-2 rounded text-decoration-none" style="background:var(--bg-body);transition:background 0.15s;" onmouseenter="this.style.background='#eef0f2'" onmouseleave="this.style.background='var(--bg-body)'">
                <div class="d-flex align-items-center gap-2">
                  <div style="width:32px;height:32px;border-radius:8px;background:#FFF8E1;color:#D97706;display:flex;align-items:center;justify-content:center;font-size:0.85rem;"><i class="bi bi-bell-fill"></i></div>
                  <span class="small fw-semibold" style="color:var(--dark);">Active Alerts</span>
                </div>
                <span class="fw-bold" style="color:var(--dark);"><?php echo $alertCount; ?></span>
              </a>
              <a href="maintenance.php" class="d-flex align-items-center justify-content-between p-2 rounded text-decoration-none" style="background:var(--bg-body);transition:background 0.15s;" onmouseenter="this.style.background='#eef0f2'" onmouseleave="this.style.background='var(--bg-body)'">
                <div class="d-flex align-items-center gap-2">
                  <div style="width:32px;height:32px;border-radius:8px;background:#EBF5FB;color:#0EA5E9;display:flex;align-items:center;justify-content:center;font-size:0.85rem;"><i class="bi bi-tools"></i></div>
                  <span class="small fw-semibold" style="color:var(--dark);">Open Maintenance</span>
                </div>
                <span class="fw-bold" style="color:var(--dark);"><?php echo $maintOpen; ?></span>
              </a>
            </div>
          </div>
        </div>

        <!-- Recent Alerts Feed -->
        <div class="card flex-grow-1">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h6 class="fw-bold mb-0"><i class="bi bi-bell-fill me-2 text-muted"></i>Recent Alerts</h6>
              <a href="alerts.php" class="small fw-semibold text-decoration-none" style="color:var(--red-primary);">All <i class="bi bi-arrow-right"></i></a>
            </div>
            <?php if (empty($recentAlerts)): ?>
              <div class="text-center py-4">
                <div style="width:48px;height:48px;border-radius:50%;background:#f3f4f6;display:inline-flex;align-items:center;justify-content:center;margin-bottom:0.5rem;"><i class="bi bi-bell-slash" style="font-size:1.2rem;color:#9ca3af;"></i></div>
                <div class="small text-muted">No alerts to show</div>
              </div>
            <?php else: ?>
              <div class="d-flex flex-column">
                <?php foreach ($recentAlerts as $i => $ra): ?>
                  <div class="activity-line">
                    <div class="activity-dot" style="background:<?php echo ($ra['severity'] ?? 'warning') === 'danger' ? '#DC2626' : '#D97706'; ?>;"></div>
                    <div class="mb-3">
                      <div class="small fw-semibold" style="color:var(--dark);"><?php echo htmlspecialchars((string)$ra['title']); ?></div>
                      <?php if (!empty($ra['details'])): ?>
                        <div style="font-size:0.75rem;color:#6b7280;margin-top:1px;"><?php echo htmlspecialchars(mb_strimwidth((string)$ra['details'], 0, 80, '...')); ?></div>
                      <?php endif; ?>
                      <div style="font-size:0.68rem;color:#9ca3af;margin-top:2px;"><?php echo date('M d, H:i', strtotime($ra['created_at'])); ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="../assets/js/app.js"></script>

<script>
  history.pushState(null, null, location.href);
  window.onpopstate = function () {
    history.go(1);
  };
</script>
<script>
// Chart defaults
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyleWidth = 8;

// Water Quality Chart
var wCtx = document.getElementById('waterChart').getContext('2d');
new Chart(wCtx, {
  type: 'line',
  data: {
    labels: <?php echo json_encode($chartLabels); ?>,
    datasets: [
      {
        label: 'pH',
        data: <?php echo json_encode($chartPh); ?>,
        borderColor: '#059669',
        backgroundColor: 'rgba(5,150,105,0.08)',
        fill: true,
        tension: 0.4,
        borderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 6,
        pointBackgroundColor: '#059669'
      },
      {
        label: 'Turbidity',
        data: <?php echo json_encode($chartTurb); ?>,
        borderColor: '#D97706',
        backgroundColor: 'rgba(217,119,6,0.08)',
        fill: true,
        tension: 0.4,
        borderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 6,
        pointBackgroundColor: '#D97706'
      },
      {
        label: 'Temp °C',
        data: <?php echo json_encode($chartTemp); ?>,
        borderColor: '#0EA5E9',
        backgroundColor: 'rgba(14,165,233,0.05)',
        fill: false,
        tension: 0.4,
        borderWidth: 2,
        pointRadius: 2,
        pointHoverRadius: 5,
        pointBackgroundColor: '#0EA5E9',
        borderDash: [4, 4]
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: {
      legend: { position: 'top', align: 'end' },
      tooltip: {
        backgroundColor: '#1a1a2e',
        titleFont: { weight: '600' },
        padding: 10,
        cornerRadius: 8,
        displayColors: true,
        boxPadding: 4
      }
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 6, color: '#9ca3af', font: { size: 10 } }
      },
      y: {
        grid: { color: '#f1f3f5' },
        ticks: { color: '#9ca3af', font: { size: 10 } },
        beginAtZero: false
      }
    }
  }
});

// Power Chart
var pCtx = document.getElementById('powerChart').getContext('2d');
new Chart(pCtx, {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($solarLabels); ?>,
    datasets: [
      {
        label: 'Solar W',
        data: <?php echo json_encode($solarPowerData); ?>,
        backgroundColor: 'rgba(234,88,12,0.7)',
        borderRadius: 4,
        barPercentage: 0.6,
        categoryPercentage: 0.7
      },
      {
        label: 'Battery %',
        data: <?php echo json_encode($solarBattData); ?>,
        type: 'line',
        borderColor: '#0891B2',
        backgroundColor: 'rgba(8,145,178,0.1)',
        fill: true,
        tension: 0.4,
        borderWidth: 2,
        pointRadius: 3,
        pointBackgroundColor: '#0891B2',
        yAxisID: 'y1'
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: {
      legend: { position: 'top', align: 'end' },
      tooltip: { backgroundColor: '#1a1a2e', padding: 10, cornerRadius: 8 }
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 5, color: '#9ca3af', font: { size: 10 } }
      },
      y: {
        position: 'left',
        grid: { color: '#f1f3f5' },
        ticks: { color: '#9ca3af', font: { size: 10 } },
        title: { display: true, text: 'Watts', font: { size: 10 }, color: '#9ca3af' }
      },
      y1: {
        position: 'right',
        grid: { drawOnChartArea: false },
        ticks: { color: '#9ca3af', font: { size: 10 } },
        min: 0, max: 100,
        title: { display: true, text: 'Battery %', font: { size: 10 }, color: '#9ca3af' }
      }
    }
  }
});
</script>
</body>
</html>