<?php
require_once 'config.php';
$page_title = 'My Account';

/* Redirect to login if not logged in */
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

include 'header.php';

$user = getCurrentUser($conn);
$uid  = intval($_SESSION['user_id']);

/* ── Handle profile update ── */
$update_success = '';
$update_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profile') {
        $fullname = sanitize($_POST['fullname'] ?? '');
        $phone    = sanitize($_POST['phone']    ?? '');
        $age      = intval($_POST['age']        ?? 0);
        $gender   = in_array($_POST['gender'] ?? '', ['male','female','other','prefer_not'])
                    ? $_POST['gender'] : 'prefer_not';

        if (empty($fullname)) {
            $update_error = 'Full name cannot be empty.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname=?, phone=?, age=?, gender=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param("ssssi", $fullname, $phone, $age, $gender, $uid);
            if ($stmt->execute()) {
                $update_success = 'Profile updated successfully.';
                $_SESSION['fullname'] = $fullname;
                $user = getCurrentUser($conn); /* refresh */
            } else {
                $update_error = 'Update failed. Please try again.';
            }
        }
    }

    if ($_POST['action'] === 'change_password') {
        $current  = $_POST['current_password']  ?? '';
        $new_pw   = $_POST['new_password']       ?? '';
        $confirm  = $_POST['confirm_new_password'] ?? '';

        if (empty($current) || empty($new_pw) || empty($confirm)) {
            $update_error = 'Please fill in all password fields.';
        } elseif (strlen($new_pw) < 8) {
            $update_error = 'New password must be at least 8 characters.';
        } elseif ($new_pw !== $confirm) {
            $update_error = 'New passwords do not match.';
        } elseif (!password_verify($current, $user['password_hash'])) {
            $update_error = 'Current password is incorrect.';
        } else {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param("si", $hash, $uid);
            if ($stmt->execute()) {
                $update_success = 'Password changed successfully.';
            } else {
                $update_error = 'Password update failed.';
            }
        }
    }
}

/* ── Fetch orders ── */
$orders = [];
$ores = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 10");
$ores->bind_param("i", $uid);
$ores->execute();
$oresult = $ores->get_result();
while ($r = $oresult->fetch_assoc()) $orders[] = $r;

/* ── Fetch wishlist ── */
$wishlist = [];
$wres = $conn->prepare("
    SELECT f.id, f.name, f.brand, f.price, f.image_url
    FROM wishlist w JOIN fragrances f ON w.fragrance_id = f.id
    WHERE w.user_id = ? ORDER BY w.added_at DESC
");
$wres->bind_param("i", $uid);
$wres->execute();
$wresult = $wres->get_result();
while ($r = $wresult->fetch_assoc()) $wishlist[] = $r;

/* Initials for avatar */
$initials = '';
$parts = explode(' ', trim($user['fullname'] ?? ''));
foreach ($parts as $p) if ($p) $initials .= strtoupper($p[0]);
$initials = substr($initials, 0, 2) ?: 'AR';
?>

<main class="details-main" style="max-width:900px;">

  <!-- Account header -->
  <div style="display:flex;align-items:center;gap:1.25rem;padding:1.5rem 0 2rem;border-bottom:1px solid var(--border-light);flex-wrap:wrap;">
    <div style="width:64px;height:64px;border-radius:50%;background:var(--cta-bg);display:flex;align-items:center;
                justify-content:center;font-family:Montserrat;font-weight:800;font-size:1.4rem;color:#fff;flex-shrink:0;">
      <?php echo htmlspecialchars($initials); ?>
    </div>
    <div>
      <h1 style="font-size:1.4rem;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.15rem;">
        <?php echo htmlspecialchars($user['fullname'] ?? 'My Account'); ?>
      </h1>
      <p style="font-family:Montserrat;font-size:0.85rem;color:var(--text-muted);margin:0;">
        <?php echo htmlspecialchars($user['email']); ?>
        <?php if (!empty($user['user_type']) && $user['user_type'] === 'admin'): ?>
          &nbsp;<span style="background:var(--cta-bg);color:#fff;border-radius:30px;padding:0.15em 0.7em;font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">Admin</span>
        <?php endif; ?>
      </p>
    </div>
    <a href="<?php echo BASE_URL; ?>/logout.php"
       style="margin-left:auto;font-family:Montserrat;font-size:0.82rem;font-weight:700;text-transform:uppercase;
              letter-spacing:0.06em;display:flex;align-items:center;gap:0.5em;color:var(--text-muted);
              border:1.5px solid var(--border-light);border-radius:8px;padding:0.6em 1em;transition:all 0.2s;"
       onmouseover="this.style.background='var(--cta-bg)';this.style.color='#fff';this.style.borderColor='var(--cta-bg)'"
       onmouseout="this.style.background='';this.style.color='var(--text-muted)';this.style.borderColor='var(--border-light)'">
      <i class="fa-solid fa-right-from-bracket"></i> Sign Out
    </a>
  </div>

  <?php if ($update_success): ?>
    <div style="background:#f0fff4;color:#276749;border:1px solid #c6f2d9;border-radius:10px;
                padding:0.85em 1.1em;font-family:Montserrat;font-size:0.88rem;margin-top:1.25rem;">
      <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($update_success); ?>
    </div>
  <?php endif; ?>
  <?php if ($update_error): ?>
    <div style="background:#fff0f0;color:#c0392b;border:1px solid #f5c6c6;border-radius:10px;
                padding:0.85em 1.1em;font-family:Montserrat;font-size:0.88rem;margin-top:1.25rem;">
      <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($update_error); ?>
    </div>
  <?php endif; ?>

  <!-- Tab nav -->
  <div id="acct-tabs" style="display:flex;gap:0;border-bottom:1px solid var(--border-light);margin-top:2rem;overflow-x:auto;">
    <?php
    $tabs = [
      ['profile',  'fa-user',           'Profile'],
      ['password', 'fa-lock',           'Password'],
      ['orders',   'fa-box',            'Orders (' . count($orders) . ')'],
      ['wishlist', 'fa-heart',          'Wishlist (' . count($wishlist) . ')'],
    ];
    foreach ($tabs as [$tid, $icon, $label]):
    ?>
    <button onclick="switchTab('<?php echo $tid; ?>')" id="tab-btn-<?php echo $tid; ?>"
            style="font-family:Montserrat;font-size:0.8rem;font-weight:700;text-transform:uppercase;
                   letter-spacing:0.07em;padding:0.9em 1.1em;border:none;background:none;
                   cursor:pointer;border-bottom:2.5px solid transparent;white-space:nowrap;
                   color:var(--text-muted);transition:all 0.2s;display:flex;align-items:center;gap:0.45em;">
      <i class="fa-solid <?php echo $icon; ?>"></i> <?php echo $label; ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Tab: Profile -->
  <div id="tab-profile" class="acct-tab" style="padding:2rem 0;">
    <h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1.5rem;">Edit Profile</h2>
    <form method="POST" style="display:flex;flex-direction:column;gap:1.1rem;max-width:480px;">
      <input type="hidden" name="action" value="update_profile">

      <div style="display:flex;flex-direction:column;gap:0.4rem;">
        <label style="font-family:Montserrat;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);">Full Name</label>
        <input type="text" name="fullname" required
               value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>"
               style="padding:0.8em 1em;border:1.5px solid var(--border-light);border-radius:10px;
                      font-family:Montserrat;font-size:0.95rem;background:var(--bg-depth);
                      color:var(--text-main);outline:none;width:100%;box-sizing:border-box;">
      </div>

      <div style="display:flex;flex-direction:column;gap:0.4rem;">
        <label style="font-family:Montserrat;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);">Email <span style="font-weight:400;text-transform:none;">(cannot change)</span></label>
        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
               style="padding:0.8em 1em;border:1.5px solid var(--border-light);border-radius:10px;
                      font-family:Montserrat;font-size:0.95rem;background:var(--bg-alt);
                      color:var(--text-muted);width:100%;box-sizing:border-box;">
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <div style="display:flex;flex-direction:column;gap:0.4rem;">
          <label style="font-family:Montserrat;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);">Phone</label>
          <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                 placeholder="+213 6xx xxx xxx"
                 style="padding:0.8em 1em;border:1.5px solid var(--border-light);border-radius:10px;
                        font-family:Montserrat;font-size:0.95rem;background:var(--bg-depth);
                        color:var(--text-main);outline:none;width:100%;box-sizing:border-box;">
        </div>
        <div style="display:flex;flex-direction:column;gap:0.4rem;">
          <label style="font-family:Montserrat;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);">Age</label>
          <input type="number" name="age" min="13" max="120"
                 value="<?php echo htmlspecialchars($user['age'] ?? ''); ?>"
                 style="padding:0.8em 1em;border:1.5px solid var(--border-light);border-radius:10px;
                        font-family:Montserrat;font-size:0.95rem;background:var(--bg-depth);
                        color:var(--text-main);outline:none;width:100%;box-sizing:border-box;">
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:0.4rem;">
        <label style="font-family:Montserrat;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);">Gender</label>
        <select name="gender"
                style="padding:0.8em 1em;border:1.5px solid var(--border-light);border-radius:10px;
                       font-family:Montserrat;font-size:0.95rem;background:var(--bg-depth);
                       color:var(--text-main);outline:none;width:100%;box-sizing:border-box;
                       appearance:none;cursor:pointer;">
          <?php $g = $user['gender'] ?? ''; ?>
          <option value="male"       <?php echo $g === 'male'       ? 'selected' : ''; ?>>Male</option>
          <option value="female"     <?php echo $g === 'female'     ? 'selected' : ''; ?>>Female</option>
          <option value="other"      <?php echo $g === 'other'      ? 'selected' : ''; ?>>Other</option>
          <option value="prefer_not" <?php echo $g === 'prefer_not' ? 'selected' : ''; ?>>Prefer not to say</option>
        </select>
      </div>

      <button type="submit"
              style="padding:0.85em 2em;background:var(--cta-bg);color:var(--cta-text);border:none;
                     border-radius:10px;font-family:Montserrat;font-size:0.95rem;font-weight:700;
                     text-transform:uppercase;letter-spacing:0.07em;cursor:pointer;
                     transition:all 0.2s;align-self:flex-start;">
        Save Changes
      </button>
    </form>
  </div>

  <!-- Tab: Password -->
  <div id="tab-password" class="acct-tab" style="padding:2rem 0;display:none;">
    <h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1.5rem;">Change Password</h2>
    <form method="POST" style="display:flex;flex-direction:column;gap:1.1rem;max-width:420px;">
      <input type="hidden" name="action" value="change_password">
      <?php foreach ([
        ['current_password',       'Current Password',     'current-password'],
        ['new_password',           'New Password',         'new-password'],
        ['confirm_new_password',   'Confirm New Password', 'new-password'],
      ] as [$fname, $flabel, $fauto]): ?>
      <div style="display:flex;flex-direction:column;gap:0.4rem;">
        <label style="font-family:Montserrat;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);"><?php echo $flabel; ?></label>
        <input type="password" name="<?php echo $fname; ?>" autocomplete="<?php echo $fauto; ?>" required
               style="padding:0.8em 1em;border:1.5px solid var(--border-light);border-radius:10px;
                      font-family:Montserrat;font-size:0.95rem;background:var(--bg-depth);
                      color:var(--text-main);outline:none;width:100%;box-sizing:border-box;">
      </div>
      <?php endforeach; ?>
      <button type="submit"
              style="padding:0.85em 2em;background:var(--cta-bg);color:var(--cta-text);border:none;
                     border-radius:10px;font-family:Montserrat;font-size:0.95rem;font-weight:700;
                     text-transform:uppercase;letter-spacing:0.07em;cursor:pointer;
                     transition:all 0.2s;align-self:flex-start;">
        Change Password
      </button>
    </form>
  </div>

  <!-- Tab: Orders -->
  <div id="tab-orders" class="acct-tab" style="padding:2rem 0;display:none;">
    <h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1.5rem;">My Orders</h2>
    <?php if (empty($orders)): ?>
      <div style="text-align:center;padding:3rem 1rem;color:var(--text-muted);font-family:Montserrat;">
        <i class="fa-solid fa-box-open" style="font-size:2.5rem;margin-bottom:1rem;display:block;opacity:0.4;"></i>
        <p>No orders yet.</p>
        <a href="<?php echo BASE_URL; ?>/explore.php"
           style="display:inline-block;margin-top:1rem;font-weight:700;color:var(--text-main);
                  border-bottom:1px solid var(--border-focus);padding-bottom:2px;">
          Start exploring →
        </a>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:0.75rem;">
        <?php foreach ($orders as $ord):
          $status_colors = [
            'pending'   => '#b07a00', 'confirmed' => '#276749',
            'shipped'   => '#1a5fa8', 'delivered' => '#276749', 'cancelled' => '#c0392b',
          ];
          $sc = $status_colors[$ord['status']] ?? '#555';
        ?>
        <div style="background:var(--bg-depth);border:1px solid var(--border-light);border-radius:12px;padding:1rem 1.25rem;display:flex;align-items:center;flex-wrap:wrap;gap:1rem;">
          <div style="flex:1;min-width:160px;">
            <p style="font-family:Montserrat;font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;margin:0 0 0.2rem;">Order #<?php echo $ord['id']; ?></p>
            <p style="font-family:Montserrat;font-size:0.82rem;color:var(--text-main);margin:0;">
              <?php echo date('d M Y', strtotime($ord['order_date'])); ?>
            </p>
          </div>
          <div style="text-align:center;">
            <span style="font-family:Montserrat;font-size:0.72rem;font-weight:700;text-transform:uppercase;
                         letter-spacing:0.08em;border-radius:30px;padding:0.25em 0.85em;
                         background:<?php echo $sc; ?>18;color:<?php echo $sc; ?>;">
              <?php echo ucfirst($ord['status']); ?>
            </span>
          </div>
          <div style="font-family:Montserrat;font-size:1rem;font-weight:800;color:var(--green);margin-left:auto;">
            <?php echo number_format($ord['total_price'], 2); ?> DZD
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Tab: Wishlist -->
  <div id="tab-wishlist" class="acct-tab" style="padding:2rem 0;display:none;">
    <h2 style="font-size:1rem;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:1.5rem;">My Wishlist</h2>
    <?php if (empty($wishlist)): ?>
      <div style="text-align:center;padding:3rem 1rem;color:var(--text-muted);font-family:Montserrat;">
        <i class="fa-solid fa-heart" style="font-size:2.5rem;margin-bottom:1rem;display:block;opacity:0.3;"></i>
        <p>Your wishlist is empty.</p>
        <a href="<?php echo BASE_URL; ?>/explore.php"
           style="display:inline-block;margin-top:1rem;font-weight:700;color:var(--text-main);
                  border-bottom:1px solid var(--border-focus);padding-bottom:2px;">
          Discover fragrances →
        </a>
      </div>
    <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:0.75rem;">
        <?php foreach ($wishlist as $wf): ?>
        <div style="background:var(--bg-depth);border:1px solid var(--border-light);border-radius:12px;
                    padding:0.85rem;display:flex;flex-direction:column;align-items:center;gap:0.5rem;text-align:center;">
          <img src="<?php echo BASE_URL . '/' . htmlspecialchars($wf['image_url'] ?? ''); ?>"
               alt="<?php echo htmlspecialchars($wf['name']); ?>"
               style="width:80px;height:100px;object-fit:contain;"
               onerror="this.style.display='none'">
          <p style="font-family:Montserrat;font-size:0.68rem;color:var(--text-muted);margin:0;text-transform:uppercase;letter-spacing:0.08em;">
            <?php echo htmlspecialchars($wf['brand'] ?? ''); ?>
          </p>
          <p style="font-family:Montserrat;font-size:0.75rem;font-weight:700;color:var(--text-main);margin:0;line-height:1.3;">
            <?php echo htmlspecialchars($wf['name']); ?>
          </p>
          <p style="font-family:Montserrat;font-size:0.82rem;font-weight:800;color:var(--green);margin:0;">
            <?php echo number_format($wf['price'], 2); ?> DZD
          </p>
          <a href="<?php echo BASE_URL; ?>/fragrance-details.php?id=<?php echo $wf['id']; ?>"
             style="font-family:Montserrat;font-size:0.72rem;font-weight:700;text-transform:uppercase;
                    letter-spacing:0.06em;color:var(--cta-text);background:var(--cta-bg);
                    border-radius:6px;padding:0.45em 1em;transition:background 0.2s;width:100%;
                    text-align:center;box-sizing:border-box;">
            View
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</main>

<script>
const TABS = ['profile','password','orders','wishlist'];
function switchTab(id) {
  TABS.forEach(t => {
    document.getElementById('tab-' + t).style.display     = t === id ? '' : 'none';
    const btn = document.getElementById('tab-btn-' + t);
    btn.style.color       = t === id ? 'var(--text-main)' : 'var(--text-muted)';
    btn.style.borderBottom = t === id ? '2.5px solid var(--text-main)' : '2.5px solid transparent';
  });
}
/* Activate profile tab by default */
switchTab('profile');

/* If there's a success/error message from password change, switch to that tab */
<?php if ($update_success || $update_error): ?>
<?php if (isset($_POST['action']) && $_POST['action'] === 'change_password'): ?>
switchTab('password');
<?php endif; ?>
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
