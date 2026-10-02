<?php
declare(strict_types=1);
session_start();

if (isset($_SESSION['admin_id'])) {
  header('Location: admin/dashboard.php');
  exit;
}
if (isset($_SESSION['student_id'])) {
  header('Location: students/dashboard.php');
  exit;
}

$schoolName = 'Batangas State University - Nasugbu Campus';
$systemName = 'AquaLaRion';
$tagline = 'Solar-Powered Water Quality Monitoring System';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo htmlspecialchars($systemName); ?> - Admin Sign In</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root {
      --red-primary: #C8102E;
      --red-dark: #9B0A20;
      --red-light: #E8344E;
      --gold-light: #F0D78C;
      --dark: #1a1a2e;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      min-height: 100vh;
      font-family: 'Inter', 'Segoe UI', sans-serif;
      background: var(--dark);
      overflow: hidden;
    }

    .login-wrapper {
      display: flex;
      min-height: 100vh;
      width: 100%;
    }

    /* ── Left panel ── */
    .hero-panel {
      flex: 1;
      position: relative;
      display: flex;
      align-items: flex-end;
      justify-content: flex-start;
      overflow: hidden;
    }

    .hero-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background: url('CAPSTONE_PROTOTYPE.png') center center / cover no-repeat;
      z-index: 0;
    }

    .hero-panel::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(26,26,46,0.3) 0%, rgba(26,26,46,0.5) 50%, rgba(26,26,46,0.85) 100%);
      z-index: 1;
    }

    .hero-content {
      position: relative;
      z-index: 2;
      padding: 3rem;
      color: #fff;
      max-width: 520px;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,255,255,0.12);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 50px;
      padding: 6px 16px;
      font-size: 0.75rem;
      font-weight: 500;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      color: #ff4d4d;
      margin-bottom: 1.25rem;
    }

    .hero-badge i { font-size: 0.65rem; }

    .hero-title {
      font-size: 2rem;
      font-weight: 800;
      line-height: 1.2;
      margin-bottom: 0.75rem;
      letter-spacing: -0.5px;
    }

    .hero-title span { color: var(--gold-light); }

    .hero-desc {
      font-size: 0.9rem;
      color: rgba(255,255,255,0.7);
      line-height: 1.6;
      margin-bottom: 1.5rem;
    }

    .hero-features { display: flex; gap: 1.5rem; }

    .hero-feature {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.8rem;
      color: rgba(255,255,255,0.8);
    }

    .hero-feature-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: rgba(255,255,255,0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.85rem;
      color: var(--gold-light);
      flex-shrink: 0;
    }

    /* ── Right panel ── */
    .form-panel {
      width: 480px;
      min-width: 480px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 3rem;
      background: #fff;
      position: relative;
    }

    .form-inner {
      width: 100%;
      max-width: 360px;
    }

    .logo-area {
      text-align: center;
      margin-bottom: 2rem;
    }

    .logo-ring {
      width: 88px;
      height: 88px;
      border-radius: 50%;
      border: 3px solid var(--red-primary);
      padding: 4px;
      margin: 0 auto 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
      box-shadow: 0 4px 20px rgba(200,16,46,0.15);
    }

    .logo-ring img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: contain;
    }

    .logo-school {
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: var(--red-primary);
      font-weight: 600;
      margin-bottom: 4px;
    }

    .logo-system {
      font-size: 1.75rem;
      font-weight: 800;
      color: var(--dark);
      letter-spacing: -0.5px;
    }

    .logo-system span { color: var(--red-primary); }

    .logo-tagline {
      font-size: 0.78rem;
      color: #aaa;
      margin-top: 2px;
    }

    .form-divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 1.75rem;
    }

    .form-divider::before,
    .form-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #eee;
    }

    .form-divider span {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #bbb;
      font-weight: 600;
    }

    .input-group-custom { margin-bottom: 1rem; }

    /* ✅ FIXED: Icon on TOP layer with z-index */
    .admin-link {
      position: absolute;
      top: 24px;
      right: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      color: var(--red-primary);
      font-size: 1.2rem;
      transition: all 0.25s ease;
      text-decoration: none;
      background: rgba(200,16,46,0.08);
      z-index: 9999; /* Always on top */
    }

    .admin-link:hover {
      background: var(--red-primary);
      color: #fff;
      transform: scale(1.1);
    }

    .input-group-custom label {
      display: block;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #555;
      margin-bottom: 6px;
    }

    .input-wrap { position: relative; }

    .input-wrap > i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #c0c0c0;
      font-size: 1rem;
      transition: color 0.2s;
      pointer-events: none;
    }

    .input-wrap input {
      width: 100%;
      padding: 14px 48px 14px 48px;
      border: 2px solid #eef0f2;
      border-radius: 12px;
      font-size: 0.95rem;
      font-family: 'Inter', sans-serif;
      color: var(--dark);
      background: #fafbfc;
      transition: all 0.25s ease;
      outline: none;
    }

    .input-wrap input::placeholder { color: #c0c0c0; }

    .input-wrap input:focus {
      border-color: var(--red-primary);
      background: #fff;
      box-shadow: 0 0 0 4px rgba(200,16,46,0.08);
    }

    .input-wrap input:focus ~ i { color: var(--red-primary); }

    .toggle-password {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #c0c0c0;
      cursor: pointer;
      font-size: 1rem;
      padding: 4px;
      transition: color 0.2s;
    }

    .toggle-password:hover { color: var(--red-primary); }

    .btn-sign-in {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 12px;
      font-size: 0.95rem;
      font-weight: 700;
      font-family: 'Inter', sans-serif;
      letter-spacing: 0.3px;
      color: #fff;
      background: linear-gradient(135deg, var(--red-primary), var(--red-light));
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(200,16,46,0.3);
      margin-top: 0.5rem;
      position: relative;
      overflow: hidden;
    }

    .btn-sign-in::before {
      content: '';
      position: absolute;
      top: 0; left: -100%;
      width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
      transition: left 0.5s;
    }

    .btn-sign-in:hover {
      background: linear-gradient(135deg, var(--red-dark), var(--red-primary));
      box-shadow: 0 6px 25px rgba(200,16,46,0.4);
      transform: translateY(-1px);
    }

    .btn-sign-in:hover::before { left: 100%; }
    .btn-sign-in:active { transform: translateY(0) scale(0.99); }
    .btn-sign-in i { margin-right: 6px; }

    .form-footer {
      text-align: center;
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid #f0f0f0;
    }

    .form-footer p {
      font-size: 0.75rem;
      color: #bbb;
      margin: 0;
      line-height: 1.5;
    }

    .form-footer .secured {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      color: #198754;
      font-weight: 600;
      font-size: 0.72rem;
    }

    @media (max-width: 991px) {
      .hero-panel { display: none; }
      .form-panel {
        width: 100%;
        min-width: unset;
        min-height: 100vh;
        background:
          linear-gradient(180deg, rgba(255,255,255,0.97) 0%, rgba(255,255,255,0.93) 100%),
          url('CAPSTONE_PROTOTYPE.png') center center / cover no-repeat;
      }
    }

    @media (max-width: 480px) {
      .form-panel { padding: 2rem 1.5rem; }
      .logo-ring { width: 74px; height: 74px; }
      .logo-system { font-size: 1.5rem; }
    }

    .btn-sign-in.loading { pointer-events: none; opacity: 0.85; }
    .btn-sign-in.loading .btn-text { visibility: hidden; }
    .btn-sign-in.loading::after {
      content: '';
      position: absolute;
      width: 20px; height: 20px;
      border: 2.5px solid rgba(255,255,255,0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
      top: 50%; left: 50%;
      margin: -10px 0 0 -10px;
    }

    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>

  <div class="login-wrapper">
    <div class="hero-panel">
      <div class="hero-content">
        <div class="hero-badge">
          <i class="bi bi-circle-fill"></i>
          Batangas State University - Nasugbu Campus
        </div>
        <h1 class="hero-title">Clean Water,<br><span>Smart Monitoring.</span></h1>
        <p class="hero-desc">
          Real-time water quality diagnostics powered by solar energy.
          Ensuring safe and clean water for the university community.
        </p>
        <div class="hero-features">
          <div class="hero-feature">
            <div class="hero-feature-icon"><i class="bi bi-droplet-half"></i></div>
            <div>Water<br>Quality</div>
          </div>
          <div class="hero-feature">
            <div class="hero-feature-icon"><i class="bi bi-sun"></i></div>
            <div>Solar<br>Powered</div>
          </div>
          <div class="hero-feature">
            <div class="hero-feature-icon"><i class="bi bi-graph-up"></i></div>
            <div>Real-time<br>Analytics</div>
          </div>
        </div>
      </div>
    </div>

    <div class="form-panel">
      <!-- ✅ Icon now on TOP layer -->
      <a href="login.php" class="admin-link" title="User Sign In">
        <i class="bi bi-person-fill"></i>
      </a>

      <div class="form-inner">
        <div class="logo-area">
          <div class="logo-ring">
            <img src="bsu_logo.png" alt="BSU Logo"
              onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\'bi bi-droplet-fill\' style=\'font-size:2.2rem;color:var(--red-primary);\'></i>';">
          </div>
          <div class="logo-school"><?php echo htmlspecialchars($schoolName); ?></div>
          <div class="logo-system">AQUA<span>LARION</span></div>
          <div class="logo-tagline"><?php echo htmlspecialchars($tagline); ?></div>
        </div>

        <div class="form-divider">
          <span>Admin Sign In</span>
        </div>

        <form method="POST" action="authenticate.php" id="loginForm" novalidate>
          <input type="hidden" name="role" value="admin">

          <div class="input-group-custom">
            <label for="adminPassword">Password</label>
            <div class="input-wrap">
              <input
                type="password"
                name="password"
                id="adminPassword"
                placeholder="Enter admin password"
                autocomplete="current-password"
              />
              <i class="bi bi-lock-fill"></i>
              <button type="button" class="toggle-password" tabindex="-1" aria-label="Toggle password visibility">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <button class="btn-sign-in" type="submit" id="btnSubmit">
            <span class="btn-text"><i class="bi bi-arrow-right-circle-fill"></i> Sign In</span>
          </button>
        </form>

        <div class="form-footer">
          <p>
            <span class="secured"><i class="bi bi-shield-lock-fill"></i> Secured Access</span><br>
            Administrator access only.
          </p>
        </div>

      </div>
    </div>
  </div>

  <script>
    document.querySelector('.toggle-password').addEventListener('click', function() {
      const input = document.getElementById('adminPassword');
      const icon = this.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
      }
    });

    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const pass = document.getElementById('adminPassword').value.trim();
      if (!pass) {
        e.preventDefault();
        Swal.fire({
          icon: 'warning',
          title: 'Missing Field',
          text: 'Please enter the admin password.',
          confirmButtonColor: '#C8102E'
        });
        return;
      }
      document.getElementById('btnSubmit').classList.add('loading');
    });

    (function() {
      var params = new URLSearchParams(window.location.search);
      if (params.has('error')) {
        Swal.fire({ icon: 'error', title: 'Login Failed', text: params.get('error'), confirmButtonColor: '#C8102E' });
        history.replaceState(null, '', window.location.pathname);
      } else if (params.has('success')) {
        Swal.fire({ icon: 'success', title: 'Success', text: params.get('success'), confirmButtonColor: '#C8102E', timer: 2500, showConfirmButton: false });
        history.replaceState(null, '', window.location.pathname);
      }
    })();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>