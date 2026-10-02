<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../connection.php';

$rows = $pdo->query('SELECT a.*, w.safe_status FROM alerts a LEFT JOIN water_diagnostics w ON w.id = a.water_diagnostic_id ORDER BY a.id DESC LIMIT 100')->fetchAll();
$dangerCount = 0; $warningCount = 0;
foreach ($rows as $r) {
    if (($r['severity'] ?? 'warning') === 'danger') $dangerCount++; else $warningCount++;
}
$totalAlerts = count($rows);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Alerts - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    .kpi-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
      border: 1px solid rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }
    .kpi-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    .kpi-card.danger {
      border-left: 4px solid #dc3545;
    }
    .kpi-card.warning {
      border-left: 4px solid #ffc107;
    }
    .kpi-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      margin-bottom: 16px;
    }
    .kpi-icon.danger {
      background: rgba(220, 53, 69, 0.1);
      color: #dc3545;
    }
    .kpi-icon.warning {
      background: rgba(255, 193, 7, 0.1);
      color: #ffc107;
    }
    .kpi-value {
      font-size: 32px;
      font-weight: 700;
      color: #212529;
      line-height: 1;
      margin-bottom: 4px;
    }
    .kpi-label {
      font-size: 14px;
      font-weight: 500;
      color: #6c757d;
    }
    .badge-danger {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      background: rgba(220, 53, 69, 0.1);
      color: #dc3545;
      border: 1px solid rgba(220, 53, 69, 0.2);
    }
    .badge-warning {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      background: rgba(255, 193, 7, 0.1);
      color: #d39e00;
      border: 1px solid rgba(255, 193, 7, 0.2);
    }
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
    .empty-state-icon {
      font-size: 48px;
      color: #dee2e6;
      margin-bottom: 1rem;
    }
    .empty-state-title {
      font-weight: 600;
      color: #212529;
      margin-bottom: 0.5rem;
    }
    .empty-state-text {
      color: #6c757d;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'alerts'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
  <?php $pageTitle = 'Alerts'; $pageIcon = 'bi-bell-fill'; include __DIR__ . '/_topbar.php'; ?>
  <div class="p-4">

    <!-- Plain Go Back Link -->
    <div class="mb-4">
      <a href="javascript:history.back()" class="text-decoration-none d-flex align-items-center gap-2 text-secondary">
        <i class="bi bi-arrow-left"></i> Go Back
      </a>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="kpi-card danger">
          <div class="kpi-icon danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <div class="kpi-value"><?php echo $dangerCount; ?></div>
          <div class="kpi-label">Danger Alerts</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="kpi-card warning">
          <div class="kpi-icon warning">
            <i class="bi bi-exclamation-circle-fill"></i>
          </div>
          <div class="kpi-value"><?php echo $warningCount; ?></div>
          <div class="kpi-label">Warning Alerts</div>
        </div>
      </div>
    </div>

    <div class="card table-card">
      <div class="table-card-header">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-bell-fill text-muted"></i>
          <span class="fw-semibold">System Alerts</span>
        </div>
        <span class="badge bg-light text-dark"><?php echo $totalAlerts; ?> record<?php echo $totalAlerts !== 1 ? 's' : ''; ?></span>
      </div>
      <?php if (!$rows): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><i class="bi bi-bell"></i></div>
          <div class="empty-state-title">No alerts yet</div>
          <div class="empty-state-text">System alerts will appear here.</div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th style="width:48px;"></th>
                <th>Title</th>
                <th>Severity</th>
                <th>Created</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $a): ?>
                <?php $sev = (string)($a['severity'] ?? 'warning'); ?>
                <tr>
                  <td>
                    <div class="notif-dropdown-icon notif-dropdown-icon--<?php echo $sev === 'danger' ? 'danger' : 'warning'; ?>">
                      <i class="bi bi-<?php echo $sev === 'danger' ? 'exclamation-triangle-fill' : 'exclamation-circle-fill'; ?>"></i>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars((string)$a['title']); ?></td>
                  <td>
                    <?php if ($sev === 'danger'): ?>
                      <span class="badge-danger"><i class="bi bi-exclamation-triangle-fill"></i> Danger</span>
                    <?php else: ?>
                      <span class="badge-warning"><i class="bi bi-exclamation-circle-fill"></i> Warning</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars((string)$a['created_at']); ?></td>
                  <td><?php echo htmlspecialchars((string)($a['details'] ?? '')); ?></td>
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