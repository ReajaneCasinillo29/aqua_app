<?php
declare(strict_types=1);
session_start();
session_regenerate_id(true);
if (!isset($_SESSION['student_id'])) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../connection.php';

$studentId = (int)$_SESSION['student_id'];
$stmt = $pdo->prepare('SELECT * FROM water_diagnostics WHERE student_id = :sid ORDER BY created_at DESC, id DESC LIMIT 1');
$stmt->execute([':sid' => $studentId]);
$diag = $stmt->fetch();

$ph = $turb = $tds = $temp = $fluor = null;
$safeStatus = 'safe';
if ($diag) {
    $ph = isset($diag['ph']) ? (float)$diag['ph'] : null;
    $turb = isset($diag['turbidity']) ? (float)$diag['turbidity'] : null;
    $tds = isset($diag['tds']) ? (float)$diag['tds'] : null;
    $temp = isset($diag['temperature_c']) ? (float)$diag['temperature_c'] : null;
    $fluor = isset($diag['fluorescence']) ? (float)$diag['fluorescence'] : null;
    $safeStatus = (string)($diag['safe_status'] ?? 'safe');
}

function badgeFor($ok): string {
    if ($ok === null) return '<span class="badge bg-secondary">No Data</span>';
    return $ok ? '<span class="badge bg-success">Within Range</span>' : '<span class="badge bg-danger">Out of Range</span>';
}

function sensorStatus($value): string {
    if ($value === null) return '<span class="text-secondary small"><i class="bi bi-x-circle me-1"></i>Not Working</span>';
    return '<span class="text-success small"><i class="bi bi-check-circle me-1"></i>Working</span>';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Water Info</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    .sensor-row { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; }
    .sensor-label { flex: 1; min-width: 90px; }
    .sensor-status { flex-shrink: 0; text-align: center; min-width: 110px; }
    .sensor-badge { flex-shrink: 0; text-align: right; min-width: 110px; }
    .simple-desc { font-size: 0.85rem; line-height: 1.5; color: #555; }
  </style>
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'water-info'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
  <?php $pageTitle = 'Water Info'; $pageIcon = 'bi-droplet-half'; include __DIR__ . '/_topbar.php'; ?>
  <div class="p-4">

    <!-- Back Button -->
    <div class="mb-3">
      <a href="javascript:history.back()" class="d-inline-flex align-items-center text-decoration-none text-secondary fw-medium">
        <i class="bi bi-arrow-left me-1"></i> Go Back
      </a>
    </div>

    <div class="card mb-4">
      <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h5 class="mb-1 fw-bold">Water Quality Overview</h5>
          <div class="text-muted small">Easy explanations for every sensor reading</div>
        </div>
        <?php if ($safeStatus === 'unsafe'): ?>
          <span class="badge bg-danger fs-6 px-3 py-2">UNSAFE</span>
        <?php else: ?>
          <span class="badge bg-success fs-6 px-3 py-2">SAFE</span>
        <?php endif; ?>
      </div>
    </div>
    <?php
      $items = [
        [
          'label'=>'pH Level',
          'value'=>$ph,
          'low'=>6.5,
          'high'=>8.5,
          'desc'=>'Tells if water is too acidic, too basic, or just right. Safe water should not taste sour or feel slippery.',
          'more'=>'Think of it like testing if something is like vinegar (acidic) or soap (basic). Good water is balanced in the middle.'
        ],
        [
          'label'=>'Turbidity (NTU)',
          'value'=>$turb,
          'low'=>0,
          'high'=>5,
          'desc'=>'Tells how clear or cloudy the water is. Clear water is usually safer; cloudy water may have dirt or tiny particles.',
          'more'=>'Imagine looking through the water: if you can see clearly through it, turbidity is low and safe.'
        ],
        [
          'label'=>'TDS (ppm)',
          'value'=>$tds,
          'low'=>0,
          'high'=>500,
          'desc'=>'Counts tiny dissolved minerals and particles in water. Too much means too many extra things mixed in.',
          'more'=>'Like salt or sugar dissolving in water—you cannot see them, but they are there. Safe water has just the right amount.'
        ],
        [
          'label'=>'Temperature (°C)',
          'value'=>$temp,
          'low'=>20,
          'high'=>30,
          'desc'=>'Shows how warm or cold the water is. Water that is too warm or too cold can grow germs faster.',
          'more'=>'Like drinking water—cool or room temperature is best. Very hot or very warm water can spoil quickly.'
        ],
        [
          'label'=>'Fluorescence',
          'value'=>$fluor,
          'low'=>0,
          'high'=>2000,
          'desc'=>'Checks for hidden germs or unwanted stuff that should not be in clean water.',
          'more'=>'Like a special light that finds things your eyes cannot see. Low numbers mean clean water.'
        ],
      ];
    ?>
    <div class="row g-3 mb-4">
      <?php foreach ($items as $it):
        $v = $it['value'];
        $ok = null;
        if ($v !== null) $ok = ($v >= $it['low'] && $v <= $it['high']);
      ?>
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="sensor-row mb-2">
                <div class="sensor-label fw-bold"><?php echo htmlspecialchars((string)$it['label']); ?></div>
                <div class="sensor-status"><?php echo sensorStatus($v); ?></div>
                <div class="sensor-badge"><?php echo badgeFor($ok); ?></div>
              </div>
              <div class="fs-4 fw-bold mb-1">
                <?php echo $v === null ? '<span class="text-muted">—</span>' : htmlspecialchars((string)$v); ?>
              </div>
              <div class="text-muted small mb-2">Safe range: <?php echo $it['low']; ?> – <?php echo $it['high']; ?></div>
              <div class="simple-desc mb-2"><strong>What it means:</strong> <?php echo htmlspecialchars((string)$it['desc']); ?></div>
              <div class="simple-desc"><strong>Easy example:</strong> <?php echo htmlspecialchars((string)$it['more']); ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="alert <?php echo $safeStatus === 'unsafe' ? 'alert-danger' : 'alert-success'; ?> mb-0">
      <?php if ($safeStatus === 'unsafe'): ?>
        <strong>Warning:</strong> This water is not safe for drinking, washing food, or bathing. You can still use it for watering plants or cleaning.
      <?php else: ?>
        <strong>Good news:</strong> This water is safe for regular school use.
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