<?php
require_once __DIR__ . '/../config.php';
if (!isAdmin()) { header('Location: ' . BASE_URL . '/login.php'); exit; }

$users = $conn->query("SELECT id, fullname, email, user_type, created_at FROM users ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Users';
$admin_page = 'users';
include __DIR__ . '/../header.php';
?>
<div class="admin-wrapper">
  <?php include __DIR__ . '/admin-nav.php'; ?>
  <main class="admin-main">
    <div class="admin-page-header">
      <h1 class="admin-page-title">Users</h1>
    </div>
    <div class="admin-table-wrapper">
      <table class="admin-table">
        <thead><tr><th>#</th><th>Full Name</th><th>Email</th><th>Type</th><th>Joined</th></tr></thead>
        <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="5"><div class="admin-empty"><i class="fa-solid fa-users"></i>No users yet.</div></td></tr>
        <?php else: foreach ($users as $u): ?>
          <tr>
            <td class="td-muted"><?php echo $u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['fullname']); ?></td>
            <td class="td-muted"><?php echo htmlspecialchars($u['email']); ?></td>
            <td>
              <?php if ($u['user_type']==='admin'): ?>
                <span class="status-badge status-shipped">Admin</span>
              <?php else: ?>
                <span class="td-muted">User</span>
              <?php endif; ?>
            </td>
            <td class="td-muted"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../footer.php'; ?>
