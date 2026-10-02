// AQUALARION — Shared JS

// ── Sidebar toggle ──
function toggleSidebar() {
  var s = document.getElementById('adminSidebar');
  var o = document.getElementById('sidebarOverlay');
  var open = s.classList.toggle('show');
  o.style.display = open ? 'block' : 'none';
}

// ── Profile dropdown toggle ──
function toggleProfileDropdown(e) {
  e.stopPropagation();
  var dd = document.getElementById('profileDropdown');
  var nd = document.getElementById('notifDropdown');
  if (nd) nd.classList.remove('show');
  if (dd) dd.classList.toggle('show');
}

// ── Notification dropdown toggle ──
function toggleNotifDropdown(e) {
  e.stopPropagation();
  var nd = document.getElementById('notifDropdown');
  var dd = document.getElementById('profileDropdown');
  if (dd) dd.classList.remove('show');
  if (nd) nd.classList.toggle('show');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
  var dd = document.getElementById('profileDropdown');
  var nd = document.getElementById('notifDropdown');
  if (dd && !e.target.closest('.topbar-profile')) {
    dd.classList.remove('show');
  }
  if (nd && !e.target.closest('.topbar-notif')) {
    nd.classList.remove('show');
  }
});

document.addEventListener('DOMContentLoaded', function() {
  var overlay = document.getElementById('sidebarOverlay');
  if (overlay) overlay.addEventListener('click', toggleSidebar);

  // ── Logout confirmation ──
  document.querySelectorAll('.btn-logout').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var logoutUrl = this.getAttribute('href');
      Swal.fire({
        title: 'Logout',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#C8102E',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        reverseButtons: true
      }).then(function(result) {
        if (result.isConfirmed) {
          window.location.href = logoutUrl;
        }
      });
    });
  });

  // ── Delete confirmation ──
  document.querySelectorAll('.btn-delete').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var deleteUrl = this.getAttribute('href');
      var itemName = this.getAttribute('data-name') || 'this item';
      Swal.fire({
        title: 'Delete',
        text: 'Are you sure you want to delete ' + itemName + '? This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then(function(result) {
        if (result.isConfirmed) {
          window.location.href = deleteUrl;
        }
      });
    });
  });

  // ── Success/error alerts from URL params ──
  var params = new URLSearchParams(window.location.search);
  if (params.has('success')) {
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: params.get('success'),
      confirmButtonColor: '#C8102E',
      timer: 2500,
      showConfirmButton: false
    });
    history.replaceState(null, '', window.location.pathname);
  }
  if (params.has('error')) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: params.get('error'),
      confirmButtonColor: '#C8102E'
    });
    history.replaceState(null, '', window.location.pathname);
  }
  if (params.has('warning')) {
    Swal.fire({
      icon: 'warning',
      title: 'Warning',
      text: params.get('warning'),
      confirmButtonColor: '#C8102E'
    });
    history.replaceState(null, '', window.location.pathname);
  }
});
