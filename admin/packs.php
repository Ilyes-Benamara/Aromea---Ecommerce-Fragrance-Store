<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$msg = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $did = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM packs WHERE id=?");
    $stmt->bind_param('i', $did);
    $msg = $stmt->execute() ? 'Pack deleted.' : 'Failed.';
}

$packs = [];
$res = $conn->query("SELECT p.*, (SELECT COUNT(*) FROM pack_fragrances WHERE pack_id=p.id) AS frag_count FROM packs p ORDER BY p.created_at DESC");
if ($res) while ($r = $res->fetch_assoc()) $packs[] = $r;

$page_title = 'Packs';
$admin_page = 'packs';
include __DIR__ . '/../header.php';
?>
<div class="admin-wrapper">
  <?php include __DIR__ . '/admin-nav.php'; ?>
  <main class="admin-main">
    <div class="admin-page-header">
      <h1 class="admin-page-title">Packs</h1>
      <a href="<?php echo BASE_URL; ?>/admin/pack-form.php" class="btn-primary">
        <i class="fa-solid fa-plus"></i> Add Pack
      </a>
    </div>
    <?php if ($msg): ?><div class="admin-alert <?php echo $msg_type; ?>"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <div class="admin-table-wrapper">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Name</th><th>Fragrances</th><th>Discount %</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($packs)): ?>
          <tr><td colspan="5"><div class="admin-empty"><i class="fa-solid fa-box-open"></i>No packs yet.</div></td></tr>
        <?php else: foreach ($packs as $pk): ?>
          <tr>
            <td class="td-muted"><?php echo $pk['id']; ?></td>
            <td><?php echo htmlspecialchars($pk['name']); ?></td>
            <td><?php echo $pk['frag_count']; ?></td>
            <td class="td-muted"><?php echo number_format($pk['discount_pct'],1); ?>%</td>
            <td>
              <div class="admin-table-actions">
                <a href="<?php echo BASE_URL; ?>/admin/pack-form.php?id=<?php echo $pk['id']; ?>" class="btn-edit">Edit</a>
                <form method="POST" onsubmit="return confirm('Delete this pack?');" style="display:inline;">
                  <input type="hidden" name="delete_id" value="<?php echo $pk['id']; ?>">
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
