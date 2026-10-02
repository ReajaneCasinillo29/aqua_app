<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: ../login.php'); exit; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Solar & Battery Specifications - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    :root {
      --solar-primary: #f97316;
      --battery-primary: #10b981;
      --text-dark: #0f172a;
      --text-muted: #64748b;
      --border-light: #e2e8f0;
    }
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
      min-height: 100vh;
    }
    .spec-card {
      background: #ffffff;
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 1px 2px rgba(0,0,0,0.04);
      border: 1px solid var(--border-light);
      transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
      height: 100%;
      position: relative;
      overflow: hidden;
    }
    .spec-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
    }
    .spec-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08), 0 4px 10px rgba(0,0,0,0.04);
      border-color: transparent;
    }
    .spec-card.solar {
      background: linear-gradient(to bottom, #fffbf7, #ffffff);
    }
    .spec-card.solar::before {
      background: linear-gradient(90deg, #f97316, #fb923c);
    }
    .spec-card.solar:hover {
      background: linear-gradient(to bottom, #fff7ed, #ffffff);
    }
    .spec-card.battery {
      background: linear-gradient(to bottom, #f7fffb, #ffffff);
    }
    .spec-card.battery::before {
      background: linear-gradient(90deg, #10b981, #34d399);
    }
    .spec-card.battery:hover {
      background: linear-gradient(to bottom, #ecfdf5, #ffffff);
    }
    .spec-icon-wrapper {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
    }
    .spec-icon {
      width: 64px;
      height: 64px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      flex-shrink: 0;
    }
    .spec-icon.orange {
      background: linear-gradient(135deg, #fed7aa, #fdba74);
      color: #c2410c;
      box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);
    }
    .spec-icon.green {
      background: linear-gradient(135deg, #a7f3d0, #6ee7b7);
      color: #047857;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }
    .spec-title-group h5 {
      font-size: 1.25rem;
      font-weight: 700;
      margin: 0;
    }
    .solar .spec-title-group h5 { color: #c2410c; }
    .battery .spec-title-group h5 { color: #047857; }
    .spec-subtitle {
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-top: 0.25rem;
    }
    .spec-list {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    .spec-item {
      padding: 1rem 1.25rem;
      border-radius: 12px;
      transition: background 0.2s ease;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 1rem;
    }
    .solar .spec-item:hover { background: #fff7ed; }
    .battery .spec-item:hover { background: #ecfdf5; }
    .spec-label {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text-dark);
      flex: 0 0 42%;
    }
    .spec-value {
      font-size: 0.9rem;
      color: var(--text-muted);
      text-align: right;
      flex: 1;
      line-height: 1.5;
    }
    .page-header {
      margin-bottom: 2rem;
    }
    .page-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-dark);
    }
    .page-subtitle {
      color: var(--text-muted);
      font-size: 0.95rem;
    }
    .verified-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: 50px;
      font-size: 0.85rem;
      font-weight: 600;
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      color: #065f46;
      border: 1px solid #6ee7b7;
    }
    @media (max-width: 767.98px) {
      .spec-item {
        flex-direction: column;
        gap: 0.25rem;
        align-items: flex-start;
      }
      .spec-value {
        text-align: left;
      }
    }
  </style>
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'battery-solar'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
  <?php $pageTitle = 'Solar & Battery Specifications'; $pageIcon = 'bi-sun-fill'; include __DIR__ . '/_topbar.php'; ?>
  <div class="p-4">
    
    <!-- Plain Go Back Link -->
    <div class="mb-4">
      <a href="javascript:history.back()" class="text-decoration-none d-flex align-items-center gap-2 text-secondary">
        <i class="bi bi-arrow-left"></i> Go Back
      </a>
    </div>

    <!-- Page Header with Verified Badge -->
    <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <div>
        <h5 class="page-title mb-1"><i class="bi bi-sun-fill text-warning me-2"></i>Portable Power Station — Specifications</h5>
      </div>
      <span class="verified-badge">
        <i class="bi bi-patch-check-fill"></i> Verified from Trusted Shop
      </span>
    </div>

    <!-- Cards Grid -->
    <div class="row g-4">
      
      <!-- Solar & Charging Input -->
      <div class="col-md-6">
        <div class="spec-card solar">
          <div class="spec-icon-wrapper">
            <div class="spec-icon orange">
              <i class="bi bi-sun-fill"></i>
            </div>
            <div class="spec-title-group">
              <h5>Solar & Charging Input</h5>
              <p class="spec-subtitle">Power sources & charging details</p>
            </div>
          </div>
          
          <div class="spec-list">
            <div class="spec-item">
              <span class="spec-label">Solar Panel Input</span>
              <span class="spec-value">DC 15V – 20V<br>Max 20W</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Compatible Solar Range</span>
              <span class="spec-value">12V – 30W</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">AC Adapter Input</span>
              <span class="spec-value">DC 12V / 3A</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Charging Time — Solar</span>
              <span class="spec-value">6 – 14.5 hours</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Charging Time — Adapter</span>
              <span class="spec-value">5 – 6 hours</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Power Source</span>
              <span class="spec-value">Solar or electric outlet</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Recommended Use</span>
              <span class="spec-value">Outdoor · Emergency · Brownout</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Operating Temperature</span>
              <span class="spec-value">-10°C to 40°C</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Battery & Power Output -->
      <div class="col-md-6">
        <div class="spec-card battery">
          <div class="spec-icon-wrapper">
            <div class="spec-icon green">
              <i class="bi bi-battery-charging"></i>
            </div>
            <div class="spec-title-group">
              <h5>Battery & Power Output</h5>
              <p class="spec-subtitle">Capacity, ports & performance</p>
            </div>
          </div>
          
          <div class="spec-list">
            <div class="spec-item">
              <span class="spec-label">Battery Type</span>
              <span class="spec-value">Maintenance-Free<br>Lithium-Ion</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Battery Capacity</span>
              <span class="spec-value">144Wh<br>12.8V / 68,800 mAh</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">AC Output Waveform</span>
              <span class="spec-value">Pure Sine Wave</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">AC Continuous / Peak</span>
              <span class="spec-value">150W / 300W</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">AC Voltage</span>
              <span class="spec-value">110V / 220V<br>Selectable</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Output Ports</span>
              <span class="spec-value">1× AC · 3× DC 12V ·<br>2× USB 5V/2.4A</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Display & Light</span>
              <span class="spec-value">LCD (V/Charge/Discharge)<br>LED Light (4 modes)</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Cycle Life</span>
              <span class="spec-value">800+ cycles</span>
            </div>
            <div class="spec-item">
              <span class="spec-label">Dimensions / Weight</span>
              <span class="spec-value">225 × 130 × 175 mm<br>~4.25 kg</span>
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
<script src="../assets/js/app.js"></script>
</body>
</html>