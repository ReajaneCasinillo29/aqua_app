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

if (isset($_GET['new']) && $_GET['new'] === '1') {
    $_SESSION['info'] = "👋 Welcome! Please set your Full Name to complete your profile.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim((string)($_POST['full_name'] ?? ''));
    if ($full_name === '') {
        $_SESSION['error'] = "Full Name cannot be empty.";
        header("Location: profile.php");
        exit;
    }

    $update = $pdo->prepare("UPDATE students SET full_name = :full_name WHERE id = :id");
    $update->execute([
        ':full_name' => $full_name,
        ':id'        => $studentId
    ]);

    $_SESSION['success'] = "Profile updated successfully.";
    header("Location: profile.php");
    exit;
}

$stmt = $pdo->prepare("SELECT sr_code, portal_code, full_name, school_name, is_active, created_at FROM students WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile & Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .profile-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #dc3545;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }
        .btn-outline-danger:hover {
            background: #dc3545;
            color: white;
        }
        .form-control:read-only {
            background: #f8f9fa;
            opacity: 0.8;
        }
    </style>
</head>
<body>
<div class="d-flex" id="appWrapper">
<?php $activePage = 'profile'; include __DIR__ . '/_sidebar.php'; ?>
<div id="pageContent">
<?php $pageTitle = 'Profile & Settings'; $pageIcon = 'bi-gear'; include __DIR__ . '/_topbar.php'; ?>
<div class="p-4">

<?php if(isset($_SESSION['info'])): ?>
<script>document.addEventListener("DOMContentLoaded",()=>{Swal.fire({icon:"info",title:"Almost done!",text:"<?=addslashes($_SESSION['info'])?>"});});</script>
<?php unset($_SESSION['info']); endif; ?>
<?php if(isset($_SESSION['success'])): ?>
<script>document.addEventListener("DOMContentLoaded",()=>{Swal.fire({icon:"success",title:"Success",text:"<?=addslashes($_SESSION['success'])?>",timer:2000,showConfirmButton:false});});</script>
<?php unset($_SESSION['success']); endif; ?>
<?php if(isset($_SESSION['error'])): ?>
<script>document.addEventListener("DOMContentLoaded",()=>{Swal.fire({icon:"error",title:"Error",text:"<?=addslashes($_SESSION['error'])?>"});});</script>
<?php unset($_SESSION['error']); endif; ?>

<?php if($student): ?>
<div class="row justify-content-center">
<div class="col-lg-8 col-xl-6">
<div class="card shadow-sm border-0">
<div class="card-body p-4">
<div class="d-flex align-items-center gap-3 mb-4">
<div class="profile-avatar">
<?= strtoupper(substr(trim((string)($student['full_name'] ?? '')) ?: 'U', 0, 1)) ?>
</div>
<div class="flex-grow-1">
<h5 class="mb-1 fw-bold">
<?= htmlspecialchars(trim((string)($student['full_name'] ?? '')) ?: 'Set your full name') ?>
</h5>
<p class="mb-0 text-muted small">
<?= htmlspecialchars((string)($student['school_name'] ?? '')) ?>
</p>
</div>
<button type="button" id="editBtn" class="btn btn-outline-danger">
<i class="bi bi-pencil-square"></i> Edit
</button>
</div>

<form method="POST" id="profileForm">
<div class="mb-3">
<label class="form-label small text-uppercase fw-semibold text-secondary">Full Name</label>
<input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars((string)($student['full_name'] ?? '')) ?>" readonly required>
</div>
<div class="mb-3">
<label class="form-label small text-uppercase fw-semibold text-secondary">School Name</label>
<input type="text" class="form-control" value="<?= htmlspecialchars((string)($student['school_name'] ?? '')) ?>" readonly>
</div>
<div class="mb-3">
<label class="form-label small text-uppercase fw-semibold text-secondary">SR Code</label>
<input type="text" class="form-control" value="<?= htmlspecialchars((string)($student['sr_code'] ?? '')) ?>" readonly>
</div>
<div class="mb-3">
<label class="form-label small text-uppercase fw-semibold text-secondary">Portal Code</label>
<input type="text" class="form-control" value="<?= htmlspecialchars((string)($student['portal_code'] ?? '')) ?>" readonly>
</div>
<div class="mb-3">
<label class="form-label small text-uppercase fw-semibold text-secondary">Account Status</label>
<br>
<span class="badge <?= (int)($student['is_active'] ?? 0) === 1 ? 'bg-success' : 'bg-danger' ?> px-3 py-1">
<?= (int)($student['is_active'] ?? 0) === 1 ? 'ACTIVE' : 'INACTIVE' ?>
</span>
</div>
<div class="mb-4">
<label class="form-label small text-uppercase fw-semibold text-secondary">Member Since</label>
<input type="text" class="form-control" value="<?= htmlspecialchars((string)($student['created_at'] ?? '')) ?>" readonly>
</div>

<div class="d-grid gap-2 d-none" id="actionBtns">
<button type="button" id="saveBtn" class="btn btn-danger btn-lg"><i class="bi bi-save"></i> Save Changes</button>
<button type="button" id="cancelBtn" class="btn btn-secondary">Cancel</button>
</div>
</form>

</div>
</div>
</div>
</div>
<?php else: ?>
<div class="alert alert-warning">Profile not found.</div>
<?php endif; ?>

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
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editBtn = document.getElementById('editBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const saveBtn = document.getElementById('saveBtn');
    const actionBtns = document.getElementById('actionBtns');
    const fullName = document.querySelector('input[name="full_name"]');
    const origName = fullName.value;

    editBtn.addEventListener('click', () => {
        fullName.removeAttribute('readonly');
        fullName.focus();
        editBtn.classList.add('d-none');
        actionBtns.classList.remove('d-none');
    });

    cancelBtn.addEventListener('click', () => {
        fullName.value = origName;
        fullName.setAttribute('readonly', 'readonly');
        editBtn.classList.remove('d-none');
        actionBtns.classList.add('d-none');
    });

    saveBtn.addEventListener('click', () => {
        Swal.fire({
            title: "Save Full Name?",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d"
        }).then(res => {
            if (res.isConfirmed) document.getElementById('profileForm').submit();
        });
    });
});
</script>
</body>
</html>