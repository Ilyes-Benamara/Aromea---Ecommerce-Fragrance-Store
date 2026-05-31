<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$msg = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_id'])) {
    $did = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM collections WHERE id=?");
    $stmt->bind_param('i', $did);
    $msg = $stmt->execute() ? 'Collection deleted.' : 'Failed.';
}

$collections = [];
$res = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM fragrances WHERE collection_id=c.id) AS frag_count FROM collections c ORDER BY c.created_at DESC");
if ($res) while ($r = $res->fetch_assoc()) $collections[] = $r;

$page_title = 'Collections';
$admin_page = 'collections';
include __DIR__ . '/../header.php';
?>
<div class="admin-wrapper">
  <?php include __DIR__ . '/admin-nav.php'; ?>
  <main class="admin-main">
    <div class="admin-page-header">
      <h1 class="admin-page-title">Collections</h1>
      <a href="<?php echo BASE_URL; ?>/admin/collection-form.php" class="btn-primary">
        <i class="fa-solid fa-plus"></i> Add Collection
      </a>
    </div>
    <?php if ($msg): ?><div class="admin-alert <?php echo $msg_type; ?>"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <div class="admin-table-wrapper">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Name</th><th>Category</th><th>Featured</th><th>On Home</th><th>Fragrances</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($collections)): ?>
          <tr><td colspan="7"><div class="admin-empty"><i class="fa-solid fa-layer-group"></i>No collections yet.</div></td></tr>
        <?php else: ?>
          <?php foreach ($collections as $col): ?>
          <tr>
            <td class="td-muted"><?php echo $col['id']; ?></td>
            <td><?php echo htmlspecialchars($col['name']); ?></td>
            <td class="td-muted"><?php echo htmlspecialchars($col['category'] ?? '—'); ?></td>
            <td><?php echo !empty($col['is_featured']) ? '<span class="status-badge status-confirmed">✓ Yes</span>' : '<span class="td-muted">—</span>'; ?></td>
            <td><?php echo !empty($col['show_on_home']) ? '<span class="status-badge status-confirmed">✓ Yes</span>' : '<span class="td-muted">—</span>'; ?></td>
            <td><?php echo $col['frag_count']; ?></td>
            <td>
              <div class="admin-table-actions">
                <a href="<?php echo BASE_URL; ?>/admin/collection-form.php?id=<?php echo $col['id']; ?>" class="btn-edit">Edit</a>
                <form method="POST" onsubmit="return confirm('Delete this collection?');" style="display:inline;">
                  <input type="hidden" name="delete_id" value="<?php echo $col['id']; ?>">
                  <button type="submit" class="btn-danger">Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
