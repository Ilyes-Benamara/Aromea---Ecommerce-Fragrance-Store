<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$msg = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $did = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM fragrances WHERE id=?");
    $stmt->bind_param('i', $did);
    $msg = $stmt->execute() ? 'Fragrance deleted.' : 'Deletion failed.';
    if (!$stmt->execute()) $msg_type = 'error';
}

$fragrances = [];
$res = $conn->query("SELECT f.id, f.name, f.brand, f.price, f.stock, f.gender, f.is_featured, c.name AS col_name
    FROM fragrances f LEFT JOIN collections c ON f.collection_id=c.id ORDER BY f.created_at DESC");
if ($res) while ($r = $res->fetch_assoc()) $fragrances[] = $r;

$page_title = 'Fragrances';
$admin_page = 'fragrances';
include __DIR__ . '/../header.php';
?>
<div class="admin-wrapper">
  <?php include __DIR__ . '/admin-nav.php'; ?>
  <main class="admin-main">
    <div class="admin-page-header">
      <h1 class="admin-page-title">Fragrances</h1>
      <a href="<?php echo BASE_URL; ?>/admin/fragrance-form.php" class="btn-primary">
        <i class="fa-solid fa-plus"></i> Add Fragrance
      </a>
    </div>
    <?php if ($msg): ?>
      <div class="admin-alert <?php echo $msg_type; ?>"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <div class="admin-table-wrapper">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Name</th><th>Brand</th><th>Collection</th><th>Price</th><th>Stock</th><th>Gender</th><th>Featured</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($fragrances)): ?>
          <tr><td colspan="9"><div class="admin-empty"><i class="fa-solid fa-spray-can-sparkles"></i>No fragrances yet.</div></td></tr>
        <?php else: foreach ($fragrances as $f): ?>
          <tr>
            <td class="td-muted"><?php echo $f['id']; ?></td>
            <td><?php echo htmlspecialchars($f['name']); ?></td>
            <td class="td-muted"><?php echo htmlspecialchars($f['brand']); ?></td>
            <td class="td-muted"><?php echo htmlspecialchars($f['col_name'] ?? '—'); ?></td>
            <td class="td-price"><?php echo number_format($f['price'],2); ?></td>
            <td class="<?php echo intval($f['stock']) < 5 ? 'stock-low' : 'stock-ok'; ?>"><?php echo intval($f['stock']); ?></td>
            <td class="td-muted"><?php echo htmlspecialchars($f['gender'] ?? '—'); ?></td>
            <td><?php echo $f['is_featured'] ? '<i class="fa-solid fa-star" style="color:#b0763a;"></i>' : '—'; ?></td>
            <td>
              <div class="admin-table-actions">
                <a href="<?php echo BASE_URL; ?>/admin/fragrance-form.php?id=<?php echo $f['id']; ?>" class="btn-edit">Edit</a>
                <form method="POST" onsubmit="return confirm('Delete this fragrance?');" style="display:inline;">
                  <input type="hidden" name="delete_id" value="<?php echo $f['id']; ?>">
                  <button type="submit" class="btn-danger">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
