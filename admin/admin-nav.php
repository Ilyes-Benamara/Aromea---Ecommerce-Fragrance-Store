<?php
// admin-nav.php — shared sidebar nav
$admin_page = $admin_page ?? '';
?>
<div class="admin-sidebar-overlay" id="admin-sidebar-overlay" onclick="closeAdminSidebar()"></div>
<aside class="admin-sidebar" id="admin-sidebar">
  <button class="admin-sidebar-close-btn" onclick="closeAdminSidebar()" aria-label="Close menu"
    style="display:none;position:absolute;top:0.75rem;right:0.75rem;background:none;border:none;font-size:1.2rem;color:var(--text-muted);cursor:pointer;padding:0.4rem;">
    <i class="fa-solid fa-xmark"></i>
  </button>
  <p class="admin-sidebar-label">Navigation</p>
  <a href="<?php echo BASE_URL; ?>/admin/dashboard.php"
     class="admin-nav-link <?php echo $admin_page==='dashboard'?'active':''; ?>">
    <i class="fa-solid fa-gauge"></i> Dashboard
  </a>
  <a href="<?php echo BASE_URL; ?>/admin/fragrances.php"
     class="admin-nav-link <?php echo $admin_page==='fragrances'?'active':''; ?>">
    <i class="fa-solid fa-spray-can-sparkles"></i> Fragrances
  </a>
  <a href="<?php echo BASE_URL; ?>/admin/collections.php"
     class="admin-nav-link <?php echo $admin_page==='collections'?'active':''; ?>">
    <i class="fa-solid fa-layer-group"></i> Collections
  </a>
  <a href="<?php echo BASE_URL; ?>/admin/packs.php"
     class="admin-nav-link <?php echo $admin_page==='packs'?'active':''; ?>">
    <i class="fa-solid fa-box-open"></i> Packs
  </a>
  <a href="<?php echo BASE_URL; ?>/admin/accords.php"
     class="admin-nav-link <?php echo $admin_page==='accords'?'active':''; ?>">
    <i class="fa-solid fa-palette"></i> Accords
  </a>
  <a href="<?php echo BASE_URL; ?>/admin/notes.php"
     class="admin-nav-link <?php echo $admin_page==='notes'?'active':''; ?>">
    <i class="fa-solid fa-leaf"></i> Notes
  </a>
  <div class="admin-sidebar-divider"></div>
  <a href="<?php echo BASE_URL; ?>/admin/orders.php"
     class="admin-nav-link <?php echo $admin_page==='orders'?'active':''; ?>">
    <i class="fa-solid fa-receipt"></i> Orders
  </a>
  <a href="<?php echo BASE_URL; ?>/admin/users.php"
     class="admin-nav-link <?php echo $admin_page==='users'?'active':''; ?>">
    <i class="fa-solid fa-users"></i> Users
  </a>
  <div class="admin-sidebar-divider"></div>
  <a href="<?php echo BASE_URL; ?>/index.php" class="admin-nav-link">
    <i class="fa-solid fa-arrow-left"></i> Back to Site
  </a>
</aside>
<script>
function openAdminSidebar() {
  document.getElementById('admin-sidebar').classList.add('admin-sidebar-open');
  document.getElementById('admin-sidebar-overlay').classList.add('open');
  // show close btn only on mobile
  const closeBtn = document.querySelector('.admin-sidebar-close-btn');
  if (closeBtn && window.innerWidth < 700) closeBtn.style.display = 'flex';
}
function closeAdminSidebar() {
  document.getElementById('admin-sidebar').classList.remove('admin-sidebar-open');
  document.getElementById('admin-sidebar-overlay').classList.remove('open');
}
</script>
