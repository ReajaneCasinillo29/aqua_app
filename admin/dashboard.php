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
// --- Queries ---
$studentCount   = (int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$activeStudents = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn();
$alertCount     = (int)$pdo->query('SELECT COUNT(*) FROM alerts')->fetchColumn();
$unsafeCount    = (int)$pdo->query("SELECT COUNT(*) FROM water_diagnostics WHERE safe_status = 'unsafe'")->fetchColumn();
$safeCount      = (int)$pdo->query("SELECT COUNT(*) FROM water_diagnostics WHERE safe_status = 'safe'")->fetchColumn();
$maintOpen      = (int)$pdo->query("SELECT COUNT(*) FROM maintenance WHERE status != 'done'")->fetchColumn();
$totalReadings  = (int)$pdo->query('SELECT COUNT(*) FROM water_diagnostics')->fetchColumn();
$latestDiag = $pdo->query('SELECT * FROM water_diagnostics ORDER BY id DESC LIMIT 1')->fetch();
$latestSafe = $latestDiag ? ($latestDiag['safe_status'] ?? 'safe') : 'safe';
$avgPh    = (float)$pdo->query('SELECT AVG(ph) FROM water_diagnostics')->fetchColumn();
$avgTurb  = (float)$pdo->query('SELECT AVG(turbidity) FROM water_diagnostics')->fetchColumn();
$avgTds   = (float)$pdo->query('SELECT AVG(tds) FROM water_diagnostics')->fetchColumn();
// Chart data — last 20 readings
$chartRows = $pdo->query('SELECT ph, turbidity, tds, temperature_c, safe_status, created_at FROM water_diagnostics ORDER BY created_at ASC LIMIT 20')->fetchAll();
$chartLabels = $chartPh = $chartTurb = $chartTds = $chartTemp = [];
foreach ($chartRows as $cr) {
    $chartLabels[] = date('M d H:i', strtotime($cr['created_at']));
    $chartPh[]     = (float)$cr['ph'];
    $chartTurb[]   = (float)$cr['turbidity'];
    $chartTds[]    = (float)$cr['tds'];
    $chartTemp[]   = (float)$cr['temperature_c'];
}
$recentDiags  = $pdo->query('SELECT ph, turbidity, tds, temperature_c, fluorescence, safe_status, created_at FROM water_diagnostics ORDER BY id DESC LIMIT 5')->fetchAll();
$recentAlerts = $pdo->query('SELECT title, severity, details, created_at FROM alerts ORDER BY id DESC LIMIT 4')->fetchAll();
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
        :root {
            --primary: #0d6efd;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --orange: #f97316;
            --info: #3b82f6;
            --dark: #1e293b;
            --muted: #64748b;
            --light-bg: #f8fafc;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 10px 15px -3px rgba(0,0,0,0.05);
            --card-shadow-hover: 0 4px 6px rgba(0,0,0,0.06), 0 20px 25px -5px rgba(0,0,0,0.08);
            --radius: 12px;
            --radius-lg: 16px;
        }
        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: var(--dark);
        }
        #pageContent {
            min-height: 100vh;
            background-color: #fff;
        }
        .dash-stat {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: default;
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            background: #fff;
            height: 100%;
        }
        .dash-stat:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-shadow-hover);
        }
        .dash-stat .card-body { padding: 1.25rem; }
        .dash-stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .dash-stat-label {
            font-size: 0.75rem;
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .dash-stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.1;
            letter-spacing: -0.5px;
        }
        .dash-stat-sub {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 2px;
        }
        .card {
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            margin-bottom: 0;
            background: #fff;
            height: 100%;
        }
        .card:hover {
            box-shadow: var(--card-shadow-hover);
        }
        .card-body {
            padding: 1.25rem;
        }
        .chart-card {
            min-height: 340px;
        }
        .chart-card canvas {
            max-height: 280px;
        }
        .activity-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 5px;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
        }
        .activity-line {
            position: relative;
            padding-left: 28px;
        }
        .activity-line::before {
            content: '';
            position: absolute;
            left: 4px;
            top: 16px;
            bottom: -12px;
            width: 2px;
            background: #e2e8f0;
        }
        .activity-line:last-child::before { display: none; }
        .activity-line .activity-dot {
            position: absolute;
            left: 0;
            top: 5px;
        }
        .summary-card {
            border-radius: var(--radius-lg);
            border: none;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
            background: #fff;
        }
        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--card-shadow-hover);
        }
        .summary-card.solar   { background: linear-gradient(135deg, #fffbeb, #fef3c7); }
        .summary-card.battery { background: linear-gradient(135deg, #ecfeee, #d1fae5); }
        .summary-card.perf    { background: linear-gradient(135deg, #eff6ff, #dbeafe); }
        .summary-value {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1;
        }
        .summary-unit {
            font-size: 0.85rem;
            font-weight: 500;
            opacity: 0.7;
            margin-left: 0.25rem;
        }
        .summary-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.8;
        }
        .badge-safe {
            background-color: #dcfce7;
            color: #166534;
            font-weight: 600;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .badge-unsafe {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: 600;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .table { margin-bottom: 0; }
        .table thead th {
            border: none;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
            padding: 0.75rem 1rem;
            background-color: #f8fafc;
        }
        .table tbody tr { transition: background-color 0.2s ease; }
        .table tbody tr:hover { background-color: #f8fafc; }
        .table td {
            border: none;
            border-bottom: 1px solid #f1f5f9;
            padding: 0.85rem 1rem;
            font-size: 0.85rem;
            vertical-align: middle;
        }
        .link-card {
            transition: all 0.2s ease;
            border-radius: 10px;
        }
        .link-card:hover {
            background-color: #f1f5f9 !important;
            transform: translateX(4px);
        }
        .section-title {
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--muted);
            margin: 1.5rem 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .greeting-section {
            background: #fff;
            border-bottom: 1px solid #f1f5f9;
            padding: 1rem 0 1.5rem;
            margin-bottom: 1.5rem;
        }
        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 0.4rem 1rem;
            transition: all 0.2s ease;
        }
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .solar-chart-container,
        .charging-chart-container {
            position: relative;
            height: 280px;
            padding: 0.5rem;
        }
    </style>
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'dashboard'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
    <?php $pageTitle = 'Dashboard'; $pageIcon = 'bi-speedometer2'; include __DIR__ . '/_topbar.php'; ?>
    <div class="p-3 p-md-4">
        <!-- Greeting Section -->
        <div class="greeting-section d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1 fs-5">Good <?php echo date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening'); ?>, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></h4>
                <p class="text-muted mb-0 small">Here's your overview for <?php echo date('l, F j, Y'); ?>.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="water-quality.php" class="btn btn-primary btn-sm"><i class="bi bi-droplet-half me-1"></i>Water Quality</a>
                <a href="alerts.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-bell me-1"></i>Alerts</a>
            </div>
        </div>
        <!-- Water Monitoring Overview -->
        <div class="section-title">
            <i class="bi text-primary"></i> Water Monitoring Overview
        </div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card dash-stat">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="dash-stat-icon" style="background:#dbeafe;color:#1d4ed8;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <div class="dash-stat-label">Water Status</div>
                            <div class="dash-stat-value">
                                <?php if ($latestSafe === 'unsafe'): ?>
                                    <span style="color:#dc2626;">UNSAFE</span>
                                <?php else: ?>
                                    <span style="color:#16a34a;">SAFE</span>
                                <?php endif; ?>
                            </div>
                            <div class="dash-stat-sub">Latest reading</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card dash-stat">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="dash-stat-icon" style="background:#f3e8ff;color:#7c3aed;">
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
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card dash-stat">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="dash-stat-icon" style="background:#dcfce7;color:#16a34a;">
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
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card dash-stat">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="dash-stat-icon" style="background:#fef3c7;color:#d97706;">
                            <i class="bi bi-eye"></i>
                        </div>
                        <div>
                            <div class="dash-stat-label">Turbidity</div>
                            <div class="dash-stat-value"><?php echo $avgTurb > 0 ? number_format($avgTurb, 1) : '--'; ?></div>
                            <div class="dash-stat-sub">NTU average</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Water Quality Chart -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card chart-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h6 class="fw-bold mb-0">Water Quality Trends</h6>
                                <span class="text-muted small">pH, Turbidity, TDS & Temperature over time</span>
                            </div>
                            <a href="water-quality.php" class="btn btn-sm btn-primary">View Details <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                        <canvas id="waterChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Data Row -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="p-3 pb-0">
                        <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-muted"></i>Recent Readings</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>pH</th>
                                        <th>Turbidity</th>
                                        <th>TDS</th>
                                        <th>Temp</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentDiags)): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-4">No readings available yet</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentDiags as $d): ?>
                                            <tr>
                                                <td><?php echo date('M d, H:i', strtotime($d['created_at'])); ?></td>
                                                <td class="fw-semibold"><?php echo htmlspecialchars((string)$d['ph']); ?></td>
                                                <td class="fw-semibold"><?php echo htmlspecialchars((string)$d['turbidity']); ?></td>
                                                <td><?php echo htmlspecialchars((string)$d['tds']); ?></td>
                                                <td><?php echo htmlspecialchars((string)$d['temperature_c']); ?>°C</td>
                                                <td>
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
            <div class="col-lg-4 d-flex flex-column gap-3">
                <!-- System Overview -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-grid-fill me-2 text-muted"></i>System Overview</h6>
                        <div class="d-flex flex-column gap-2">
                            <a href="water-quality.php" class="d-flex align-items-center justify-content-between p-2 rounded text-decoration-none link-card">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="icon-box bg-success-subtle text-success rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </div>
                                    <span class="small fw-semibold">Safe Readings</span>
                                </div>
                                <span class="fw-bold fs-6"><?php echo $safeCount; ?></span>
                            </a>
                            <a href="water-quality.php" class="d-flex align-items-center justify-content-between p-2 rounded text-decoration-none link-card">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="icon-box bg-danger-subtle text-danger rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </div>
                                    <span class="small fw-semibold">Unsafe Readings</span>
                                </div>
                                <span class="fw-bold fs-6"><?php echo $unsafeCount; ?></span>
                            </a>
                            <a href="alerts.php" class="d-flex align-items-center justify-content-between p-2 rounded text-decoration-none link-card">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="icon-box bg-warning-subtle text-warning rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                        <i class="bi bi-bell-fill"></i>
                                    </div>
                                    <span class="small fw-semibold">Active Alerts</span>
                                </div>
                                <span class="fw-bold fs-6"><?php echo $alertCount; ?></span>
                            </a>
                            <a href="maintenance.php" class="d-flex align-items-center justify-content-between p-2 rounded text-decoration-none link-card">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="icon-box bg-primary-subtle text-primary rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                                        <i class="bi bi-tools"></i>
                                    </div>
                                    <span class="small fw-semibold">Open Maintenance</span>
                                </div>
                                <span class="fw-bold fs-6"><?php echo $maintOpen; ?></span>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Recent Alerts -->
                <div class="card flex-grow-1">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-bold mb-0"><i class="bi bi-bell-fill me-2 text-muted"></i>Recent Alerts</h6>
                            <a href="alerts.php" class="small fw-semibold text-decoration-none text-primary">View All <i class="bi bi-arrow-right"></i></a>
                        </div>
                        <?php if (empty($recentAlerts)): ?>
                            <div class="text-center py-4">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width:48px;height:48px;">
                                    <i class="bi bi-bell-slash text-muted"></i>
                                </div>
                                <p class="small text-muted mb-0">No alerts at this time</p>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column">
                                <?php foreach ($recentAlerts as $ra): ?>
                                    <div class="activity-line mb-2">
                                        <div class="activity-dot" style="background:<?php echo ($ra['severity'] ?? 'warning') === 'danger' ? '#ef4444' : '#f59e0b'; ?>;"></div>
                                        <div>
                                            <div class="small fw-semibold"><?php echo htmlspecialchars((string)$ra['title']); ?></div>
                                            <?php if (!empty($ra['details'])): ?>
                                                <div class="text-muted small mt-1"><?php echo htmlspecialchars(mb_strimwidth((string)$ra['details'], 0, 80, '...')); ?></div>
                                            <?php endif; ?>
                                            <div class="text-muted mt-1" style="font-size:0.7rem;"><?php echo date('M d, H:i', strtotime($ra['created_at'])); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Solar & Battery Section -->
        <div class="section-title">
            <i class="bi text-warning"></i> Portable Power Station — Performance & Specs
        </div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-sm-4 col-md-3">
                <div class="card summary-card battery h-100">
                    <div class="card-body p-3">
                        <div class="summary-label text-success">Battery Capacity</div>
                        <div class="summary-value text-success mt-2">144<span class="summary-unit">Wh</span></div>
                        <div class="small text-success opacity-75 mt-2">12.8V · 68,800 mAh<br>Lithium-Ion</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3">
                <div class="card summary-card perf h-100">
                    <div class="card-body p-3">
                        <div class="summary-label text-primary">AC Output</div>
                        <div class="summary-value text-primary mt-2">150<span class="summary-unit">W</span></div>
                        <div class="small text-primary opacity-75 mt-2">Peak 300W<br>Pure Sine Wave</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3">
                <div class="card summary-card solar h-100">
                    <div class="card-body p-3">
                        <div class="summary-label" style="color:#c2410c;">Solar Input</div>
                        <div class="summary-value mt-2" style="color:#c2410c;">20<span class="summary-unit">W Max</span></div>
                        <div class="small opacity-75 mt-2" style="color:#c2410c;">15V–20V<br>Supports 12–30W panel</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-3">
                <div class="card summary-card battery h-100">
                    <div class="card-body p-3">
                        <div class="summary-label text-success">Cycle Life</div>
                        <div class="summary-value text-success mt-2">800+<span class="summary-unit">cycles</span></div>
                        <div class="small text-success opacity-75 mt-2">Long-lasting<br>High-density cells</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-header bg-white border-0 pt-3 pb-0">
                        <h6 class="fw-bold mb-1">Power & Charging Profile</h6>
                        <p class="small text-muted mb-3">Solar vs Adapter charging time & power capacity overview</p>
                    </div>
                    <div class="card-body pt-0">
                        <div class="solar-chart-container">
                            <canvas id="solarBatteryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5 d-flex flex-column gap-3">
                <!-- UPDATED: Charging Comparison as Line Graph -->
                <div class="card flex-grow-1">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h6 class="fw-bold mb-0">Charging Time Comparison</h6>
                                <p class="small text-muted mb-0">Charge progress over time</p>
                            </div>
                            <a href="battery-solar.php" class="btn btn-sm btn-primary">View Details <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                        <div class="charging-chart-container">
                            <canvas id="chargingCompareChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-muted"></i>Technical Specs</h6>
                        <div class="d-flex flex-column gap-2 small">
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="fw-medium">Battery Type</span>
                                <span class="text-muted">Lithium-Ion</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="fw-medium">Selectable Voltage</span>
                                <span class="text-muted">110V / 220V</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="fw-medium">Output Ports</span>
                                <span class="text-muted">1×AC · 3×DC · 2×USB</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="fw-medium">Dimensions</span>
                                <span class="text-muted">225×130×175mm</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="fw-medium">Weight</span>
                                <span class="text-muted">~4.25 kg</span>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span class="fw-medium">Operating Temp</span>
                                <span class="text-muted">-10°C to 40°C</span>
                            </div>
                        </div>
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
    window.onpopstate = () => history.go(1);
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#64748b';
    Chart.defaults.borderColor = '#e2e8f0';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyleWidth = 8;

    // Solar & Battery Bar Chart
    new Chart(document.getElementById('solarBatteryChart'), {
        type: 'bar',
        data: {
            labels: ['Capacity (Wh)', 'Output (W)', 'Solar In (W)', 'Charge Time (h)', 'Cycles'],
            datasets: [{
                label: 'Specifications',
                data: [144, 150, 20, 10, 800],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(249, 115, 22, 0.7)',
                    'rgba(139, 92, 246, 0.7)',
                    'rgba(236, 72, 153, 0.7)'
                ],
                borderColor: ['#10b981','#3b82f6','#f97316','#8b5cf6','#ec4899'],
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { weight: '700', size: 12 },
                    bodyFont: { size: 11 },
                    padding: 12,
                    cornerRadius: 12,
                    displayColors: false,
                    callbacks: {
                        label: ctx => {
                            const units = ['Wh', 'W', 'W', 'Avg hrs', 'lifetime'];
                            return `Value: ${ctx.raw} ${units[ctx.dataIndex]}`;
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { weight: '600' } } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } }
            },
            animation: { duration: 1000, easing: 'easeOutQuart' }
        }
    });

    // Charging Comparison — LINE GRAPH
    new Chart(document.getElementById('chargingCompareChart'), {
        type: 'line',
        data: {
            labels: ['0h', '2h', '4h', '6h', '8h', '10h', '12h', '14h'],
            datasets: [
                {
                    label: 'Solar Charging',
                    data: [0, 15, 30, 45, 60, 75, 90, 100],
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.12)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#f97316',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Adapter Charging',
                    data: [0, 35, 70, 90, 100, 100, 100, 100],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.12)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'right',
                    labels: {
                        boxWidth: 8,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { weight: '600' },
                        padding: 15
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { weight: '700', size: 12 },
                    bodyFont: { size: 11 },
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        label: ctx => `Charge: ${ctx.raw}%`
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 0, font: { size: 10 } }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { size: 10 },
                        callback: val => val + '%'
                    }
                }
            },
            animation: { duration: 1200, easing: 'easeOutQuart' }
        }
    });

    // Water Quality Line Chart
    new Chart(document.getElementById('waterChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels); ?>,
            datasets: [
                {
                    label: 'pH',
                    data: <?php echo json_encode($chartPh); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Turbidity',
                    data: <?php echo json_encode($chartTurb); ?>,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                },
                {
                    label: 'Temp °C',
                    data: <?php echo json_encode($chartTemp); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    fill: false,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#3b82f6',
                    borderDash: [5, 5]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'right',
                    labels: {
                        boxWidth: 8,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: { weight: '600' },
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { weight: '700', size: 12 },
                    bodyFont: { size: 11 },
                    padding: 14,
                    cornerRadius: 12,
                    boxPadding: 6
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 6, font: { size: 10 } } },
                y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } }, beginAtZero: false }
            },
            animation: { duration: 1000, easing: 'easeOutQuart' }
        }
    });
});
</script>
</body>
</html>
