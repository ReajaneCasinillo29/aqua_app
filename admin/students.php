<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: ../login.php'); exit; }
require_once __DIR__ . '/../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student_id'])) {
    $id = (int)$_POST['delete_student_id'];
    $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: students.php?deleted=1');
    exit;
}

$students = $pdo->query('SELECT id, sr_code, portal_code, full_name, school_name, is_active, created_at FROM students ORDER BY id DESC')->fetchAll();
$activeCount = 0; $inactiveCount = 0;
foreach ($students as $s) {
    if ((int)$s['is_active'] === 1) $activeCount++; else $inactiveCount++;
}
$totalCount = count($students);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Students - Admin</title>
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
    .kpi-card.total {
      border-left: 4px solid #fd7e14;
    }
    .kpi-card.active {
      border-left: 4px solid #198754;
    }
    .kpi-card.inactive {
      border-left: 4px solid #dc3545;
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
    .kpi-icon.total {
      background: rgba(253, 126, 20, 0.1);
      color: #fd7e14;
    }
    .kpi-icon.active {
      background: rgba(25, 135, 84, 0.1);
      color: #198754;
    }
    .kpi-icon.inactive {
      background: rgba(220, 53, 69, 0.1);
      color: #dc3545;
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
    .badge-active {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
      background: rgba(25, 135, 84, 0.1);
      color: #198754;
      border: 1px solid rgba(25, 135, 84, 0.2);
    }
    .badge-inactive {
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
    .delete-btn {
      color: #dc3545;
      text-decoration: none;
      padding: 4px 8px;
      border-radius: 6px;
      transition: all 0.2s ease;
    }
    .delete-btn:hover {
      background: rgba(220, 53, 69, 0.1);
      color: #bb2d3b;
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
<?php $activePage = 'students'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
  <?php $pageTitle = 'Students'; $pageIcon = 'bi-people-fill'; include __DIR__ . '/_topbar.php'; ?>
  <div class="p-4">

    <div class="mb-4">
      <a href="javascript:history.back()" class="text-decoration-none d-flex align-items-center gap-2 text-secondary">
        <i class="bi bi-arrow-left"></i> Go Back
      </a>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="kpi-card total">
          <div class="kpi-icon total">
            <i class="bi bi-people-fill"></i>
          </div>
          <div class="kpi-value"><?php echo $totalCount; ?></div>
          <div class="kpi-label">Total Students</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="kpi-card active">
          <div class="kpi-icon active">
            <i class="bi bi-person-check"></i>
          </div>
          <div class="kpi-value"><?php echo $activeCount; ?></div>
          <div class="kpi-label">Active Students</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="kpi-card inactive">
          <div class="kpi-icon inactive">
            <i class="bi bi-person-x"></i>
          </div>
          <div class="kpi-value"><?php echo $inactiveCount; ?></div>
          <div class="kpi-label">Inactive Students</div>
        </div>
      </div>
    </div>

    <div class="card table-card">
      <div class="table-card-header">
        <span class="fw-semibold">All Students</span>
        <span class="badge bg-light text-dark"><?php echo $totalCount; ?> record<?php echo $totalCount !== 1 ? 's' : ''; ?></span>
      </div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th style="width:48px;"></th>
              <th>Name</th>
              <th>SR Code</th>
              <th>Portal Code</th>
              <th>School</th>
              <th>Status</th>
              <th>Created</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$students): ?>
              <tr>
                <td colspan="8">
                  <div class="empty-state">
                    <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                    <div class="empty-state-title">No students yet</div>
                    <div class="empty-state-text">Student accounts will appear here.</div>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($students as $s): ?>
                <tr>
                  <td>
                    <div style="width:32px;height:32px;border-radius:50%;background:#dc3545;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:0.75rem;">
                      <?php echo strtoupper(substr((string)($s['full_name'] ?? 'S'), 0, 1)); ?>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars((string)($s['full_name'] ?? '')); ?></td>
                  <td><code class="bg-light px-2 py-1 rounded text-danger border-0"><?php echo htmlspecialchars((string)$s['sr_code']); ?></code></td>
                  <td><code class="bg-light px-2 py-1 rounded text-danger border-0"><?php echo htmlspecialchars((string)$s['portal_code']); ?></code></td>
                  <td><?php echo htmlspecialchars((string)($s['school_name'] ?? '')); ?></td>
                  <td>
                    <?php if ((int)$s['is_active'] === 1): ?>
                      <span class="badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                    <?php else: ?>
                      <span class="badge-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars((string)$s['created_at']); ?></td>
                  <td>
                    <a href="#" class="delete-btn" data-id="<?php echo $s['id']; ?>" data-name="<?php echo htmlspecialchars((string)$s['full_name']); ?>">
                      <i class="bi bi-trash3"></i>
                    </a>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.location.search.includes('deleted=1')) {
    Swal.fire({
      icon: 'success',
      title: 'Deleted Successfully',
      text: 'The student has been removed.',
      confirmButtonColor: '#198754',
      timer: 2000,
      showConfirmButton: false
    });
    history.replaceState({}, '', window.location.pathname);
  }

  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const id = btn.dataset.id;
      const name = btn.dataset.name;

      Swal.fire({
        title: 'Delete Student?',
        text: `You are about to delete "${name}". This cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
      }).then(result => {
        if (result.isConfirmed) {
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = '';
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'delete_student_id';
          input.value = id;
          form.appendChild(input);
          document.body.appendChild(form);
          form.submit();
        }
      });
    });
  });
});
</script>
<script src="../assets/js/app.js"></script>
</body>
</html>