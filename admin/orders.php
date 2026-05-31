<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['order_id'], $_POST['status'])) {
    $oid    = intval($_POST['order_id']);
    $status = $_POST['status'];
    $valid  = ['pending','confirmed','shipped','delivered','cancelled'];
    if (in_array($status, $valid)) {
        $stmt = $conn->prepare("UPDATE orders SET status=?,updated_at=NOW() WHERE id=?");
        $stmt->bind_param('si',$status,$oid);
        $msg = $stmt->execute() ? 'Order updated.' : 'Failed.';
    }
}

$orders = [];
$res = $conn->query("SELECT o.id, o.total_price, o.status, o.order_date, o.shipping_address, u.fullname, u.email FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.order_date DESC");
if ($res) while ($r = $res->fetch_assoc()) $orders[] = $r;

$page_title = 'Orders';
$admin_page = 'orders';
include __DIR__ . '/../header.php';
?>
<div class="admin-wrapper">
  <?php include __DIR__ . '/admin-nav.php'; ?>
  <main class="admin-main">
    <div class="admin-page-header">
      <h1 class="admin-page-title">Orders</h1>
    </div>
    <?php if ($msg): ?><div class="admin-alert success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <div class="admin-table-wrapper">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Update Status</th></tr></thead>
        <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="6"><div class="admin-empty"><i class="fa-solid fa-receipt"></i>No orders yet.</div></td></tr>
        <?php else: foreach ($orders as $o): ?>
          <tr>
            <td class="td-muted"><?php echo $o['id']; ?></td>
            <td>
              <?php echo htmlspecialchars($o['fullname']); ?>
              <div class="td-muted" style="font-size:.72rem;"><?php echo htmlspecialchars($o['email']); ?></div>
            </td>
            <td class="td-price"><?php echo number_format($o['total_price'],2); ?></td>
            <td><span class="status-badge status-<?php echo $o['status']; ?>"><?php echo $o['status']; ?></span></td>
            <td class="td-muted"><?php echo date('d M Y', strtotime($o['order_date'])); ?></td>
            <td>
              <form method="POST" style="display:flex;gap:.4rem;align-items:center;">
                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                <select name="status" class="form-select" style="padding:.35em .5em;font-size:.78rem;width:auto;">
                  <?php foreach (['pending','confirmed','shipped','delivered','cancelled'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $o['status']==$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-edit" style="padding:.35em .65em;font-size:.72rem;">Save</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
