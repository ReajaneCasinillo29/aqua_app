<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['student_id'])) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../connection.php';

$studentId = (int)$_SESSION['student_id'];

$stmt = $pdo->prepare('SELECT * FROM water_diagnostics WHERE student_id = :sid OR student_id IS NULL ORDER BY id DESC LIMIT 20');
$stmt->execute([':sid' => $studentId]);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Usage</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'my-usage'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
  <?php $pageTitle = 'My Usage'; $pageIcon = 'bi-clock-history'; include __DIR__ . '/_topbar.php'; ?>
  <div class="p-4">

    <!-- Back Button -->
    <div class="mb-3">
      <a href="javascript:history.back()" class="d-inline-flex align-items-center text-decoration-none text-secondary fw-medium">
        <i class="bi bi-arrow-left me-1"></i> Go Back
      </a>
    </div>

    <div class="card table-card">
      <div class="table-card-header">
        <span class="fw-semibold">Water Diagnostics History</span>
        <span class="text-muted small">Last 20 records</span>
      </div>
      <?php if (!$rows): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><i class="bi bi-clock-history"></i></div>
          <div class="empty-state-title">No usage records yet</div>
          <div class="empty-state-text">Your water diagnostics history will appear here.</div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>pH</th>
                <th>Turbidity <span class="text-muted fw-normal">(NTU)</span></th>
                <th>TDS <span class="text-muted fw-normal">(ppm)</span></th>
                <th>Temp <span class="text-muted fw-normal">(&deg;C)</span></th>
                <th>Fluorescence</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td class="small"><?php echo htmlspecialchars((string)$r['created_at']); ?></td>
                  <td><?php echo $r['ph'] !== null ? htmlspecialchars((string)$r['ph']) : '<span class="text-muted">--</span>'; ?></td>
                  <td><?php echo $r['turbidity'] !== null ? htmlspecialchars((string)$r['turbidity']) : '<span class="text-muted">--</span>'; ?></td>
                  <td><?php echo $r['tds'] !== null ? htmlspecialchars((string)$r['tds']) : '<span class="text-muted">--</span>'; ?></td>
                  <td><?php echo $r['temperature_c'] !== null ? htmlspecialchars((string)$r['temperature_c']) : '<span class="text-muted">--</span>'; ?></td>
                  <td><?php echo $r['fluorescence'] !== null ? htmlspecialchars((string)$r['fluorescence']) : '<span class="text-muted">--</span>'; ?></td>
                  <td>
                    <?php if (($r['safe_status'] ?? 'safe') === 'unsafe'): ?>
                      <span class="badge-unsafe">UNSAFE</span>
                    <?php else: ?>
                      <span class="badge-safe">SAFE</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="mt-3 text-muted small">
      Safe ranges: pH 6.5&ndash;8.5, Turbidity 0&ndash;5 NTU, TDS 0&ndash;500 ppm, Temp 20&ndash;30&deg;C, Fluorescence 0&ndash;2000.
    </div>

  </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/app.js"></script>
</body>
</html>