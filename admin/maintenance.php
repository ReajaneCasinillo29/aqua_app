<?php
declare(strict_types=1);
session_start();

// Auth check
if (!isset($_SESSION['admin_id'])) { 
    header('Location: ../login.php'); 
    exit; 
}

// Database connection with error handling
$connPath = __DIR__ . '/../connection.php';
if (!file_exists($connPath)) {
    $_SESSION['error'] = "Database configuration file missing!";
    header('Location: ../login.php');
    exit;
}
require_once $connPath;

if (!isset($pdo) || !$pdo instanceof PDO) {
    $_SESSION['error'] = "Database connection failed!";
    header('Location: ../login.php');
    exit;
}

// ==========================================
// UPLOAD PROOF PHOTO + MARK AS DONE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_proof') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        $_SESSION['error'] = "Invalid task ID!";
        header('Location: maintenance.php');
        exit;
    }

    // Check existing status
    try {
        $checkStmt = $pdo->prepare("SELECT status FROM maintenance WHERE id = ?");
        $checkStmt->execute([$id]);
        $currentStatus = $checkStmt->fetchColumn();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header('Location: maintenance.php');
        exit;
    }

    if ($currentStatus === 'done') {
        $_SESSION['error'] = "Task is already completed!";
        header('Location: maintenance.php');
        exit;
    }

    // File validation
    if (!isset($_FILES['proof_photo']) || $_FILES['proof_photo']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => "File too large (server limit)",
            UPLOAD_ERR_FORM_SIZE => "File too large (form limit)",
            UPLOAD_ERR_PARTIAL => "Partial upload, try again",
            UPLOAD_ERR_NO_FILE => "Please select a photo to upload!"
        ];
        $errCode = $_FILES['proof_photo']['error'] ?? UPLOAD_ERR_NO_FILE;
        $_SESSION['error'] = $errors[$errCode] ?? "Upload error (code: $errCode)";
        header('Location: maintenance.php');
        exit;
    }

    // Size limit: 5MB
    if ($_FILES['proof_photo']['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "File too large! Maximum 5MB.";
        header('Location: maintenance.php');
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/maintenance/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $_SESSION['error'] = "Failed to create upload folder. Check permissions.";
            header('Location: maintenance.php');
            exit;
        }
    }

    $extension = strtolower(pathinfo($_FILES['proof_photo']['name'], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $allowedTypes, true)) {
        $_SESSION['error'] = "Only JPG, PNG, GIF & WebP files are allowed!";
        header('Location: maintenance.php');
        exit;
    }

    // Sanitize filename
    $safeFilename = preg_replace('/[^A-Za-z0-9_\-]/', '_', "proof_{$id}_" . time()) . ".$extension";
    $filepath = $uploadDir . $safeFilename;
    $dbPath = 'uploads/maintenance/' . $safeFilename;

    if (move_uploaded_file($_FILES['proof_photo']['tmp_name'], $filepath)) {
        try {
            $stmt = $pdo->prepare("UPDATE maintenance SET proof_photo = ?, status = 'done' WHERE id = ?");
            $stmt->execute([$dbPath, $id]);
            $_SESSION['success'] = "Proof uploaded! Task marked as Done.";
        } catch (PDOException $e) {
            @unlink($filepath);
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Failed to save file. Check folder permissions.";
    }
    
    header('Location: maintenance.php');
    exit;
}

// ==========================================
// CREATE TASK
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_task') {
    $title = trim((string)($_POST['title'] ?? ''));
    $scheduled_for = trim((string)($_POST['scheduled_for'] ?? ''));
    $details = trim((string)($_POST['details'] ?? ''));
    $status = 'in_progress';
    
    if (empty($title)) {
        $_SESSION['error'] = "Task title is required!";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO maintenance (title, scheduled_for, details, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $title, 
                $scheduled_for ?: null, 
                $details, 
                $status
            ]);
            $_SESSION['success'] = "New task created successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
    
    header('Location: maintenance.php');
    exit;
}

// ==========================================
// DELETE TASK
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_task') {
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        $_SESSION['error'] = "Invalid task ID!";
    } else {
        try {
            // Get photo path first
            $getPhoto = $pdo->prepare("SELECT proof_photo FROM maintenance WHERE id = ?");
            $getPhoto->execute([$id]);
            $photo = $getPhoto->fetchColumn();
            
            // Delete file if exists
            if ($photo && is_string($photo)) {
                $fullPath = __DIR__ . '/../' . ltrim($photo, '/');
                if (file_exists($fullPath) && is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }
            
            // Delete record
            $stmt = $pdo->prepare("DELETE FROM maintenance WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "Task deleted successfully!";
            } else {
                $_SESSION['error'] = "Task not found!";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
    
    header('Location: maintenance.php');
    exit;
}

// ==========================================
// FETCH DATA
// ==========================================
try {
    $rows = $pdo->query('SELECT * FROM maintenance ORDER BY id DESC LIMIT 50')->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to load tasks: " . $e->getMessage();
    $rows = [];
}

$progressCount = $doneCount = 0;
foreach ($rows as $r) {
    if (isset($r['status']) && (string)$r['status'] === 'done') {
        $doneCount++;
    } else {
        $progressCount++;
    }
}

function formatDate(?string $datetime): string {
    if (empty($datetime)) return '<span class="text-muted fst-italic">—</span>';
    try {
        $dt = new DateTime($datetime);
        return htmlspecialchars($dt->format('M j, Y g:i A'));
    } catch (Exception $e) {
        return htmlspecialchars($datetime);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Maintenance Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    :root {
      --red-primary: #dc2626;
      --red-dark: #b91c1c;
      --red-light: #fef2f2;
      --red-border: #fecaca;
      --success: #16a34a;
      --info: #0284c7;
      --gray-50: #f9fafb;
      --gray-100: #f3f4f6;
      --gray-200: #e5e7eb;
      --gray-700: #374151;
      --gray-800: #1f2937;
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    * { box-sizing: border-box; }
    body { 
      font-family: 'Inter', sans-serif; 
      background-color: #f8fafc;
      color: var(--gray-800);
      line-height: 1.5;
    }
    .page-header {
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--gray-200);
    }
    .page-title {
      font-weight: 700;
      font-size: 1.75rem;
      color: var(--gray-800);
      margin: 0;
    }
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 0.375rem;
      color: var(--gray-700);
      text-decoration: none;
      font-size: 0.875rem;
      margin-bottom: 1rem;
      transition: color 0.2s ease;
    }
    .back-link:hover { color: var(--red-primary); }
    .kpi-card {
      background: #ffffff;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: var(--shadow-md);
      border: 1px solid var(--gray-200);
      transition: all 0.3s ease;
      height: 100%;
    }
    .kpi-card:hover { 
      transform: translateY(-4px); 
      box-shadow: var(--shadow-lg);
    }
    .kpi-card.in-progress { border-left: 4px solid var(--info); }
    .kpi-card.done { border-left: 4px solid var(--success); }
    .kpi-icon {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      margin-bottom: 1rem;
    }
    .kpi-icon.in-progress { background: rgba(2, 132, 199, 0.08); color: var(--info); }
    .kpi-icon.done { background: rgba(22, 163, 74, 0.08); color: var(--success); }
    .kpi-value { font-size: 2rem; font-weight: 800; line-height: 1; margin-bottom: 0.25rem; }
    .kpi-label { font-size: 0.875rem; color: #6b7280; font-weight: 500; }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.375rem;
      padding: 0.375rem 0.875rem;
      border-radius: 50px;
      font-size: 0.8125rem;
      font-weight: 600;
      letter-spacing: 0.01em;
    }
    .status-badge.in-progress { background: rgba(2, 132, 199, 0.1); color: var(--info); }
    .status-badge.done { background: rgba(22, 163, 74, 0.1); color: var(--success); }
    .status-display {
      display: inline-flex;
      align-items: center;
      gap: 0.375rem;
      padding: 0.375rem 0.875rem;
      border-radius: 50px;
      font-size: 0.8125rem;
      font-weight: 600;
      background: rgba(2, 132, 199, 0.1);
      color: var(--info);
      cursor: default;
      user-select: none;
    }
   .btn-primary-red {
  background: linear-gradient(135deg, var(--success), #15803d);
  border: none;
  color: white;
  font-weight: 600;
  padding: 0.5rem 1.25rem;
  border-radius: 8px;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);
}
.btn-primary-red:hover {
  background: linear-gradient(135deg, #15803d, #166534);
  color: white;
  box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
  transform: translateY(-1px);
}
   .btn-upload {
  background: linear-gradient(135deg, #1e40af, #1e3a8a);
  border: none;
  color: white;
  font-weight: 600;
  padding: 0.4rem 1rem;
  border-radius: 8px;
  font-size: 0.8125rem;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(30, 64, 175, 0.2);
}
.btn-upload:hover:not(:disabled) {
  background: linear-gradient(135deg, #1e3a8a, #172554);
  color: white;
  box-shadow: 0 4px 10px rgba(30, 64, 175, 0.3);
  transform: translateY(-1px);
}
.btn-upload:disabled {
  background: #93c5fd;
  cursor: not-allowed;
  opacity: 0.7;
  transform: none;
  box-shadow: none;
}
.btn-loading {
  pointer-events: none;
  opacity: 0.85;
}
.btn-loading .spinner-border {
  width: 1rem;
  height: 1rem;
  border-width: 0.15em;
  margin-right: 0.5rem;
  vertical-align: middle;
}
    .table-card {
      background: white;
      border-radius: 12px;
      box-shadow: var(--shadow-md);
      border: 1px solid var(--gray-200);
      overflow: hidden;
    }
    .table-card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 1.5rem;
      border-bottom: 1px solid var(--gray-100);
      background: var(--gray-50);
    }
    .table thead { background: var(--gray-50); }
    .table th {
      font-weight: 600;
      font-size: 0.8125rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #6b7280;
      border-bottom: 1px solid var(--gray-200);
      padding: 0.875rem 1rem;
    }
    .table td {
      padding: 1rem;
      vertical-align: middle;
      border-bottom: 1px solid var(--gray-100);
    }
    .table tbody tr { transition: background-color 0.15s ease; }
    .table tbody tr:hover { background-color: var(--gray-50); }
    .proof-photo-preview {
      width: 64px;
      height: 44px;
      border-radius: 6px;
      object-fit: cover;
      cursor: pointer;
      border: 1px solid var(--gray-200);
      transition: all 0.2s ease;
    }
    .proof-photo-preview:hover { 
      transform: scale(1.15); 
      box-shadow: var(--shadow-md);
    }
    .upload-form-cell { min-width: 260px; }
    .file-input-wrapper { width: 100%; }
    .form-control-file-custom {
      border: 1px solid var(--red-border);
      border-radius: 8px;
      padding: 0.5rem;
      font-size: 0.8125rem;
      background: var(--red-light);
    }
    .selected-preview-img {
      max-width: 100%;
      max-height: 260px;
      object-fit: contain;
      border-radius: 10px;
      border: 3px solid var(--red-border);
      margin: 0.75rem 0;
      background: #fff;
      padding: 0.5rem;
      box-shadow: var(--shadow-sm);
    }
    .file-name-text { 
      font-size: 0.8125rem; 
      color: #6b7280; 
      margin-top: 0.5rem; 
    }
    .delete-btn {
      color: var(--red-primary);
      background: transparent;
      border: none;
      padding: 0.375rem 0.5rem;
      border-radius: 6px;
      transition: all 0.2s ease;
    }
    .delete-btn:hover { 
      background: var(--red-light); 
      color: var(--red-dark);
    }
    .empty-state { 
      padding: 3rem 1rem; 
      text-align: center; 
    }
    .empty-state-icon { 
      font-size: 3rem; 
      color: var(--gray-200); 
      margin-bottom: 1rem; 
    }
    .empty-state-title { 
      font-weight: 700; 
      color: var(--gray-800); 
      margin-bottom: 0.5rem; 
    }
    .empty-state-text { 
      color: #6b7280; 
      font-size: 0.875rem; 
    }
    .alert {
      border: none;
      border-radius: 8px;
      box-shadow: var(--shadow-sm);
    }
    .alert-success {
      background: #f0fdf4;
      color: #166534;
      border-left: 4px solid var(--success);
    }
    .alert-danger {
      background: var(--red-light);
      color: var(--red-dark);
      border-left: 4px solid var(--red-primary);
    }
    .modal-header {
      border-bottom: 1px solid var(--gray-100);
      padding: 1rem 1.25rem;
    }
    .modal-title {
      font-weight: 700;
      font-size: 1.125rem;
    }
    .modal-footer {
      border-top: 1px solid var(--gray-100);
      padding: 1rem 1.25rem;
    }
    .btn-close:focus { box-shadow: none; }
  </style>
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'maintenance'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
  <?php $pageTitle = 'Maintenance Management'; $pageIcon = 'bi-tools'; include __DIR__ . '/_topbar.php'; ?>
  <div class="p-4">
    <a href="javascript:history.back()" class="back-link">
      <i class="bi bi-arrow-left"></i> Go Back
    </a>

    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="kpi-card in-progress">
          <div class="kpi-icon in-progress"><i class="bi bi-clock-history"></i></div>
          <div class="kpi-value"><?php echo $progressCount; ?></div>
          <div class="kpi-label">Tasks In Progress</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="kpi-card done">
          <div class="kpi-icon done"><i class="bi bi-check-circle"></i></div>
          <div class="kpi-value"><?php echo $doneCount; ?></div>
          <div class="kpi-label">Tasks Done</div>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
      <h5 class="fw-semibold mb-0">Maintenance Records Overview</h5>
      <button class="btn btn-primary-red" data-bs-toggle="modal" data-bs-target="#addTaskModal">
        <i class="bi bi-plus-lg me-1"></i> Create New Task
      </button>
    </div>

    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="POST">
            <div class="modal-header">
              <h5 class="modal-title" id="addTaskModalLabel">
                <i class="bi bi-tools text-danger me-2"></i>New Maintenance Task
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="action" value="create_task">
              <div class="mb-3">
                <label class="form-label fw-semibold">Task Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required placeholder="Enter maintenance task title" autofocus>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Scheduled Date & Time</label>
                <input type="datetime-local" name="scheduled_for" class="form-control">
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Details & Description</label>
                <textarea name="details" class="form-control" rows="4" placeholder="Enter task details, location, and notes..."></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn-primary-red">Create Task</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="table-card">
      <div class="table-card-header">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-list-check text-danger"></i>
          <span class="fw-semibold">Task List</span>
        </div>
        <span class="badge bg-light text-dark border px-3 py-1"><?php echo count($rows); ?> Total Records</span>
      </div>
      
      <?php if (empty($rows)): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
          <div class="empty-state-title">No Maintenance Records Yet</div>
          <div class="empty-state-text">Click "Create New Task" to add your first maintenance record.</div>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table mb-0">
            <thead>
              <tr>
                <th style="width:48px;"></th>
                <th>Task Title</th>
                <th>Current Status</th>
                <th>Proof of Completion</th>
                <th>Scheduled Date</th>
                <th>Details</th>
                <th class="text-center" style="width:70px;">Remove</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $m): 
                $st = (string)($m['status'] ?? 'in_progress');
                $hasProof = !empty($m['proof_photo']);
              ?>
                <tr>
                  <td>
                    <?php if ($st === 'done'): ?>
                      <span class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width:32px;height:32px;">
                        <i class="bi bi-check-lg"></i>
                      </span>
                    <?php else: ?>
                      <span class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-circle" style="width:32px;height:32px;">
                        <i class="bi bi-clock"></i>
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="fw-semibold text-dark"><?php echo htmlspecialchars((string)($m['title'] ?? '')); ?></td>
                  <td>
                    <?php if ($st === 'done'): ?>
                      <span class="status-badge done">
                        <i class="bi bi-check-circle-fill"></i> Done
                      </span>
                    <?php else: ?>
                      <span class="status-display">
                        <i class="bi bi-clock-history"></i> In Progress
                      </span>
                    <?php endif; ?>
                  </td>
                  
                  <td class="upload-form-cell">
                    <?php if ($hasProof): ?>
                      <a href="../<?php echo htmlspecialchars(ltrim((string)$m['proof_photo'], '/')); ?>" target="_blank" title="Click to view full-size proof">
                        <img src="../<?php echo htmlspecialchars(ltrim((string)$m['proof_photo'], '/')); ?>" 
                             class="proof-photo-preview" alt="Proof of completion">
                      </a>
                    <?php elseif ($st === 'in_progress'): ?>
                      <form method="POST" enctype="multipart/form-data" class="file-input-wrapper" id="uploadForm-<?php echo (int)$m['id']; ?>">
                        <input type="hidden" name="action" value="upload_proof">
                        <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                        <input type="file" name="proof_photo" accept="image/*" required 
                               class="form-control form-control-file-custom mb-2" id="file-<?php echo (int)$m['id']; ?>"
                               onchange="previewPhoto(<?php echo (int)$m['id']; ?>, this)">
                        <button type="button" class="btn-upload w-100" 
                                id="uploadBtn-<?php echo (int)$m['id']; ?>"
                                onclick="confirmUpload(<?php echo (int)$m['id']; ?>)" disabled>
                          <i class="bi bi-cloud-upload me-1"></i> Upload & Mark Done
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="text-muted fst-italic">—</span>
                    <?php endif; ?>
                  </td>
                  
                  <td><?php echo formatDate((string)($m['scheduled_for'] ?? '')); ?></td>
                  <td class="text-wrap text-muted small">
                    <?php 
                    $details = (string)($m['details'] ?? '');
                    echo !empty($details) ? nl2br(htmlspecialchars($details)) : '<span class="fst-italic">No details provided</span>'; 
                    ?>
                  </td>
                  <td class="text-center">
                    <button type="button" class="delete-btn" 
                            data-id="<?php echo (int)$m['id']; ?>" title="Delete this task">
                      <i class="bi bi-trash3"></i>
                    </button>
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
<script>
let photoPreviewData = {};

function previewPhoto(taskId, input) {
  if (!input.files || !input.files[0]) {
    photoPreviewData[taskId] = null;
    document.getElementById(`uploadBtn-${taskId}`).disabled = true;
    return;
  }
  const file = input.files[0];
  photoPreviewData[taskId] = {
    file: file,
    name: file.name,
    size: (file.size / 1024 / 1024).toFixed(2) + ' MB'
  };
  const reader = new FileReader();
  reader.onload = function(e) {
    photoPreviewData[taskId].dataUrl = e.target.result;
    document.getElementById(`uploadBtn-${taskId}`).disabled = false;
  };
  reader.readAsDataURL(file);
}

function confirmUpload(taskId) {
  const preview = photoPreviewData[taskId];
  if (!preview || !preview.dataUrl) {
    Swal.fire({
      icon: 'warning',
      title: 'Please Select a Photo',
      text: 'Choose an image file first before submitting proof.',
      confirmButtonColor: '#26d9dc',
      timer: 2500
    });
    return;
  }
  
  Swal.fire({
    title: '<span style="color:#dc2626;font-weight:700;">Review Proof Photo</span>',
    html: `
      <div style="text-align:center; padding: 0.5rem 0;">
        <p style="color:#6b7280; font-size:0.875rem; margin-bottom:0.5rem;">
          Please verify that this is the correct photo before submitting:
        </p>
        <img src="${preview.dataUrl}" class="selected-preview-img" alt="Preview of selected photo">
        <div class="file-name-text">
          <strong style="color:#1f2937;">${preview.name}</strong><br>
          File size: ${preview.size}
        </div>
        <p style="margin-top:1rem; font-size:0.8125rem; color:#dc2626; font-weight:500;">
          <i class="bi bi-exclamation-circle"></i> 
          Once submitted, the task will be marked as <strong>Done</strong> and cannot be modified.
        </p>
      </div>
    `,
    showCancelButton: true,
    confirmButtonColor: '#101575',
    cancelButtonColor: '#6b7280',
    confirmButtonText: '<i class="bi bi-check-circle-fill me-1"></i> Yes, Submit & Mark Done',
    cancelButtonText: 'Cancel',
    focusCancel: true,
    showLoaderOnConfirm: true,
    preConfirm: () => {
      return new Promise(resolve => {
        const btn = document.getElementById(`uploadBtn-${taskId}`);
        btn.classList.add('btn-loading');
        btn.innerHTML = '<span class="spinner-border"></span> Uploading...';
        btn.disabled = true;
        document.getElementById(`uploadForm-${taskId}`).submit();
        resolve();
      });
    },
    allowOutsideClick: () => !Swal.isLoading()
  });
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const id = btn.dataset.id;
      Swal.fire({
        title: '<span style="color:#dc2626;">Delete Task?</span>',
        text: 'This action cannot be undone — proceed with caution.',
        icon: 'warning',
        iconColor: '#dc2626',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        focusCancel: true
      }).then((result) => {
        if (result.isConfirmed) {
          const form = document.createElement('form');
          form.method = 'POST';
          form.style.display = 'none';
          form.innerHTML = `
            <input type="hidden" name="action" value="delete_task">
            <input type="hidden" name="id" value="${id}">
          `;
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