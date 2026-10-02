<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../connection.php';

$latest = $pdo->query('SELECT * FROM water_diagnostics ORDER BY id DESC LIMIT 10')->fetchAll();
$latestReading = !empty($latest) ? $latest[0] : null;

$safeCount = 0;
$unsafeCount = 0;
foreach ($latest as $r) {
    if (($r['safe_status'] ?? 'safe') === 'unsafe') $unsafeCount++;
    else $safeCount++;
}
$totalReadings = count($latest);

function sensorOk($val, $min, $max) {
    if ($val === null || $val === '') return null;
    return ($val >= $min && $val <= $max);
}

$sensors = [
    ['name' => 'pH Level',      'icon' => 'bi-droplet-half',     'val' => $latestReading['ph'] ?? null,         'min' => 6.5,  'max' => 8.5],
    ['name' => 'Turbidity',     'icon' => 'bi-eye',              'val' => $latestReading['turbidity'] ?? null,  'min' => 0,    'max' => 5],
    ['name' => 'TDS',           'icon' => 'bi-moisture',         'val' => $latestReading['tds'] ?? null,        'min' => 0,    'max' => 500],
    ['name' => 'Temperature',   'icon' => 'bi-thermometer-half', 'val' => $latestReading['temperature_c'] ?? null,'min' => 20,  'max' => 30],
    ['name' => 'Fluorescence',  'icon' => 'bi-lightbulb',        'val' => $latestReading['fluorescence'] ?? null,'min' => 0,   'max' => 2000]
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Water Quality - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    .stat-card {
      background: #ffffff;
      border-radius: 12px;
      padding: 24px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .stat-card::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
    }
    .stat-card.total::before    { background: #F97316; }
    .stat-card.safe::before     { background: #10B981; }
    .stat-card.unsafe::before   { background: #EF4444; }

    .stat-icon {
      width: 40px;
      height: 40px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      margin-bottom: 16px;
    }
    .stat-card.total .stat-icon  { background: #FFEDD8; color: #F97316; }
    .stat-card.safe .stat-icon   { background: #DDFCEE; color: #10B981; }
    .stat-card.unsafe .stat-icon { background: #FEE7E7; color: #EF4444; }

    .stat-value {
      font-size: 36px;
      font-weight: 700;
      color: #111827;
      line-height: 1;
      margin-bottom: 6px;
    }
    .stat-label {
      font-size: 15px;
      font-weight: 500;
      color: #6B7280;
    }

    .sensor-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
      border: 1px solid rgba(0,0,0,0.05);
      transition: all 0.3s ease;
      border-left: 4px solid #adb5bd;
    }
    .sensor-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .sensor-card.status-ok   { border-left-color: #198754; }
    .sensor-card.status-fail { border-left-color: #dc3545; }
    .sensor-card.status-none { border-left-color: #adb5bd; }

    .sensor-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      margin-bottom: 12px;
      background: rgba(108,117,125,0.08);
      color: #6c757d;
    }
    .sensor-card.status-ok   .sensor-icon { background: rgba(25,135,84,0.08); color: #198754; }
    .sensor-card.status-fail .sensor-icon { background: rgba(220,53,69,0.08); color: #dc3545; }
    .sensor-card.status-none .sensor-icon { background: rgba(108,117,125,0.08); color: #adb5bd; }

    .sensor-name {
      font-size: 14px;
      font-weight: 600;
      color: #495057;
      margin-bottom: 8px;
    }
    .sensor-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 6px 12px;
      border-radius: 12px;
      font-size: 0.78rem;
      font-weight: 500;
    }
    .status-ok   { background: rgba(25,135,84,0.1); color: #198754; }
    .status-fail { background: rgba(220,53,69,0.1); color: #dc3545; }
    .status-none { background: rgba(108,117,125,0.1); color: #6c757d; }

    .table-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 1.25rem;
      border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .empty-state {
      padding: 3rem 1rem;
      text-align: center;
    }
    .empty-state-icon { font-size: 48px; color: #dee2e6; margin-bottom: 1rem; }
    .empty-state-title { font-weight: 600; color: #212529; margin-bottom: 0.5rem; }
    .empty-state-text { color: #6c757d; font-size: 0.9rem; }
  </style>
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'water-quality'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
  <?php $pageTitle = 'Water Quality'; $pageIcon = 'bi-droplet-half'; include __DIR__ . '/_topbar.php'; ?>
  <div class="p-4">

    <div class="mb-4">
      <a href="javascript:history.back()" class="text-decoration-none d-flex align-items-center gap-2 text-secondary">
        <i class="bi bi-arrow-left"></i> Go Back
      </a>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="stat-card total">
          <div class="stat-icon"><i class="bi bi-bar-chart"></i></div>
          <div class="stat-value"><?php echo $totalReadings; ?></div>
          <div class="stat-label">Total Records</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card safe">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value"><?php echo $safeCount; ?></div>
          <div class="stat-label">Safe Readings</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card unsafe">
          <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
          <div class="stat-value"><?php echo $unsafeCount; ?></div>
          <div class="stat-label">Unsafe Readings</div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <?php foreach ($sensors as $s):
        $ok = sensorOk($s['val'], $s['min'], $s['max']);
        if ($ok === true) {
            $statusClass = 'status-ok';
            $statusText  = '<i class="bi bi-check-circle-fill"></i> Working';
        } elseif ($ok === false) {
            $statusClass = 'status-fail';
            $statusText  = '<i class="bi bi-exclamation-triangle-fill"></i> Out of Range';
        } else {
            $statusClass = 'status-none';
            $statusText  = '<i class="bi bi-dash-circle"></i> No Data';
        }
      ?>
      <div class="col-md-4 col-xl">
        <div class="sensor-card <?php echo $statusClass; ?>">
          <div class="sensor-icon"><i class="bi <?php echo $s['icon']; ?>"></i></div>
          <div class="sensor-name"><?php echo $s['name']; ?></div>
          <div class="sensor-status-badge <?php echo $statusClass; ?>">
            <?php echo $statusText; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="card table-card">
      <div class="table-card-header">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-droplet-half text-muted"></i>
          <span class="fw-semibold">Diagnostics Readings</span>
        </div>
        <span class="badge bg-light text-dark"><?php echo $totalReadings; ?> record<?php echo $totalReadings !== 1 ? 's' : ''; ?></span>
      </div>
      <?php if (!$latest): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><i class="bi bi-droplet"></i></div>
          <div class="empty-state-title">No diagnostics yet</div>
          <div class="empty-state-text">Water quality readings will appear here.</div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Created</th>
                <th>pH</th>
                <th>Turbidity</th>
                <th>TDS</th>
                <th>Temp (°C)</th>
                <th>Fluorescence</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($latest as $row): ?>
                <tr>
                  <td><?php echo (int)$row['id']; ?></td>
                  <td><?php echo htmlspecialchars((string)$row['created_at']); ?></td>
                  <td><?php echo htmlspecialchars((string)$row['ph']); ?></td>
                  <td><?php echo htmlspecialchars((string)$row['turbidity']); ?></td>
                  <td><?php echo htmlspecialchars((string)$row['tds']); ?></td>
                  <td><?php echo htmlspecialchars((string)$row['temperature_c']); ?></td>
                  <td><?php echo htmlspecialchars((string)$row['fluorescence']); ?></td>
                  <td>
                    <?php if (($row['safe_status'] ?? 'safe') === 'unsafe'): ?>
                      <span class="badge-unsafe"><i class="bi bi-x-circle-fill"></i> Unsafe</span>
                    <?php else: ?>
                      <span class="badge-safe"><i class="bi bi-check-circle-fill"></i> Safe</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>