<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$msg = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['delete_id'])) {
        $did = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM accords WHERE id=?");
        $stmt->bind_param('i', $did);
        $msg = $stmt->execute() ? 'Accord deleted.' : 'Delete failed.';
        if (!$stmt->execute()) $msg_type='error';
    } elseif (isset($_POST['name'])) {
        $id    = intval($_POST['id'] ?? 0);
        $name  = sanitize($_POST['name']);
        $color = sanitize($_POST['color'] ?? '#c4a882');
        if (!$name) { $msg = 'Name required.'; $msg_type = 'error'; }
        elseif ($id > 0) {
            $stmt = $conn->prepare("UPDATE accords SET name=?,color=? WHERE id=?");
            $stmt->bind_param('ssi',$name,$color,$id);
            $msg = $stmt->execute() ? 'Updated.' : 'Failed.';
        } else {
            $stmt = $conn->prepare("INSERT IGNORE INTO accords (name,color) VALUES (?,?)");
            $stmt->bind_param('ss',$name,$color);
            $msg = $stmt->execute() ? 'Accord added.' : 'Failed (duplicate?).';
        }
    }
}

$accords = $conn->query("SELECT a.*, (SELECT COUNT(*) FROM fragrance_accords WHERE accord_id=a.id) AS used FROM accords a ORDER BY a.name")->fetch_all(MYSQLI_ASSOC);

// edit mode
$edit = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    foreach ($accords as $a) if ($a['id']==$eid) { $edit=$a; break; }
}

$page_title = 'Accords';
$admin_page = 'accords';
include __DIR__ . '/../header.php';
?>
<div class="admin-wrapper">
  <?php include __DIR__ . '/admin-nav.php'; ?>
  <main class="admin-main">
    <div class="admin-page-header">
      <h1 class="admin-page-title">Accords</h1>
    </div>
    <?php if ($msg): ?><div class="admin-alert <?php echo $msg_type; ?>"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
      <!-- Form -->
      <div class="admin-form-card">
        <p class="form-label" style="margin-bottom:.85rem;"><?php echo $edit ? 'Edit Accord' : 'Add New Accord'; ?></p>
        <form method="POST">
          <input type="hidden" name="id" value="<?php echo $edit ? intval($edit['id']) : 0; ?>">
          <div class="form-group" style="margin-bottom:.75rem;">
            <label class="form-label">Name *</label>
            <input class="form-input" name="name" required value="<?php echo $edit ? htmlspecialchars($edit['name']) : ''; ?>" placeholder="e.g. Woody, Spicy…">
          </div>
          <div class="form-group" style="margin-bottom:1rem;">
            <label class="form-label">Color</label>
            <div style="display:flex;gap:.6rem;align-items:center;">
              <input type="color" name="color" value="<?php echo $edit ? htmlspecialchars($edit['color']) : '#c4a882'; ?>" style="width:44px;height:36px;border:1.5px solid var(--border-light);border-radius:6px;cursor:pointer;">
              <span class="form-hint" style="margin:0;">Used for accord bars</span>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary"><i class="fa-solid fa-check"></i> <?php echo $edit ? 'Update' : 'Add'; ?></button>
            <?php if ($edit): ?>
              <a href="<?php echo BASE_URL; ?>/admin/accords.php" class="btn-secondary">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- List -->
      <div class="admin-table-wrapper">
        <table class="admin-table">
          <thead><tr><th>Name</th><th>Color</th><th>Used in</th><th>Actions</th></tr></thead>
          <tbody>
          <?php if (empty($accords)): ?>
            <tr><td colspan="4"><div class="admin-empty"><i class="fa-solid fa-palette"></i>None yet.</div></td></tr>
          <?php else: foreach ($accords as $a): ?>
            <tr>
              <td><?php echo htmlspecialchars($a['name']); ?></td>
              <td><span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:<?php echo htmlspecialchars($a['color']); ?>;vertical-align:middle;"></span></td>
              <td class="td-muted"><?php echo $a['used']; ?> fragrances</td>
              <td>
                <div class="admin-table-actions">
                  <a href="?edit=<?php echo $a['id']; ?>" class="btn-edit">Edit</a>
                  <form method="POST" onsubmit="return confirm('Delete?');" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?php echo $a['id']; ?>">
                    <button type="submit" class="btn-danger">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
