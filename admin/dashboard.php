<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$stats = [];
$stats['fragrances']  = $conn->query("SELECT COUNT(*) c FROM fragrances")->fetch_assoc()['c'] ?? 0;
$stats['collections'] = $conn->query("SELECT COUNT(*) c FROM collections")->fetch_assoc()['c'] ?? 0;
$stats['packs']       = $conn->query("SELECT COUNT(*) c FROM packs")->fetch_assoc()['c'] ?? 0;
$stats['users']       = $conn->query("SELECT COUNT(*) c FROM users WHERE user_type='user'")->fetch_assoc()['c'] ?? 0;
$stats['orders']      = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'] ?? 0;
$stats['revenue']     = $conn->query("SELECT COALESCE(SUM(total_price),0) r FROM orders WHERE status!='cancelled'")->fetch_assoc()['r'] ?? 0;
$stats['low_stock']   = $conn->query("SELECT COUNT(*) c FROM fragrances WHERE stock < 5")->fetch_assoc()['c'] ?? 0;
$stats['pending']     = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")->fetch_assoc()['c'] ?? 0;

$recent_orders = [];
$res = $conn->query("SELECT o.id, o.total_price, o.status, o.order_date, u.fullname FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.order_date DESC LIMIT 6");
if ($res) while ($r = $res->fetch_assoc()) $recent_orders[] = $r;

$page_title = 'Admin Dashboard';
$admin_page = 'dashboard';
include __DIR__ . '/../header.php';
?>
<div class="admin-wrapper">
  <?php include __DIR__ . '/admin-nav.php'; ?>
  <main class="admin-main">
    <div class="admin-page-header">
      <h1 class="admin-page-title">Dashboard</h1>
      <a href="<?php echo BASE_URL; ?>/admin/fragrance-form.php" class="btn-primary">
        <i class="fa-solid fa-plus"></i> Add Fragrance
      </a>
    </div>

    <div class="admin-stats-grid">
      <div class="admin-stat-card">
        <span class="admin-stat-label">Fragrances</span>
        <span class="admin-stat-value"><?php echo $stats['fragrances']; ?></span>
        <?php if ($stats['low_stock'] > 0): ?>
          <span class="admin-stat-sub" style="color:#c0392b;"><?php echo $stats['low_stock']; ?> low stock</span>
        <?php else: ?>
          <span class="admin-stat-sub">All stocked</span>
        <?php endif; ?>
      </div>
      <div class="admin-stat-card">
        <span class="admin-stat-label">Collections</span>
        <span class="admin-stat-value"><?php echo $stats['collections']; ?></span>
        <span class="admin-stat-sub"><?php echo $stats['packs']; ?> packs</span>
      </div>
      <div class="admin-stat-card">
        <span class="admin-stat-label">Customers</span>
        <span class="admin-stat-value"><?php echo $stats['users']; ?></span>
        <span class="admin-stat-sub">Registered</span>
      </div>
      <div class="admin-stat-card">
        <span class="admin-stat-label">Orders</span>
        <span class="admin-stat-value"><?php echo $stats['orders']; ?></span>
        <?php if ($stats['pending'] > 0): ?>
          <span class="admin-stat-sub" style="color:#b45309;"><?php echo $stats['pending']; ?> pending</span>
        <?php else: ?>
          <span class="admin-stat-sub">All handled</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="admin-table-wrapper" style="margin-bottom:1.5rem;padding:1.25rem 1.5rem;">
      <p class="admin-stat-label">Total Revenue</p>
      <p style="font-family:Montserrat;font-size:2rem;font-weight:900;color:var(--green);margin-top:.25rem;">
        <?php echo number_format($stats['revenue'], 2); ?>
        <span style="font-size:1rem;color:var(--text-muted);">DZD</span>
      </p>
    </div>

    <h2 style="font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:.75rem;">Recent Orders</h2>
    <div class="admin-table-wrapper">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          <?php if (empty($recent_orders)): ?>
            <tr><td colspan="5"><div class="admin-empty"><i class="fa-solid fa-receipt"></i>No orders yet.</div></td></tr>
          <?php else: foreach ($recent_orders as $o): ?>
            <tr>
              <td class="td-muted"><?php echo $o['id']; ?></td>
              <td><?php echo htmlspecialchars($o['fullname']); ?></td>
              <td class="td-price"><?php echo number_format($o['total_price'],2); ?></td>
              <td><span class="status-badge status-<?php echo $o['status']; ?>"><?php echo $o['status']; ?></span></td>
              <td class="td-muted"><?php echo date('d M Y', strtotime($o['order_date'])); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
