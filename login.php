<?php
require_once 'config.php';
$page_title = 'Login';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT id, email, password_hash, fullname, user_type FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['fullname']  = $user['fullname'];
                $_SESSION['user_type'] = $user['user_type'];
                $success = 'Login successful! Redirecting…';
                $redirect = $user['user_type'] === 'admin'
                    ? BASE_URL . '/admin/dashboard.php'
                    : BASE_URL . '/index.php';
                header("refresh:1; url=$redirect");
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'No account found with that email.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Aromea</title>
  <script src="https://kit.fontawesome.com/0b3d769464.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/style.css">
  <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/images/logo-image.png">
  <style>
    .auth-page { min-height:100vh; display:grid; grid-template-columns:1fr 1fr; }
    @media(max-width:768px){ .auth-page{grid-template-columns:1fr;} .auth-visual{display:none;} }

    .auth-visual {
      background:var(--bg-depth); border-right:1px solid var(--border-light);
      display:flex; flex-direction:column; justify-content:center; align-items:center;
      padding:4rem; gap:2rem; position:relative; overflow:hidden;
    }
    .auth-visual-logo { font-family:Montserrat; font-size:3.5rem; font-weight:900;
      text-transform:uppercase; letter-spacing:0.1em; color:var(--text-main); }
    .auth-visual-tagline { font-family:Montserrat; font-size:1.1rem; color:var(--text-muted);
      text-align:center; line-height:1.7; max-width:340px; }
    .auth-visual-img { width:260px; opacity:0.85; border-radius:16px; }
    .auth-perks { display:flex; flex-direction:column; gap:0.75rem; width:100%; max-width:300px; }
    .auth-perk { display:flex; align-items:center; gap:0.75rem;
      font-family:Montserrat; font-size:0.9rem; color:var(--text-main); }
    .auth-perk i { color:var(--text-muted); width:18px; text-align:center; }

    .auth-form-side { display:flex; flex-direction:column; justify-content:center;
      align-items:center; padding:4rem 3rem; }
    .auth-form-wrap { width:100%; max-width:400px; }
    .auth-form-wrap h1 { font-size:2rem; margin:0 0 0.4rem;
      text-transform:uppercase; letter-spacing:0.05em; }
    .auth-form-wrap p.lead { font-family:Montserrat; color:var(--text-muted);
      font-size:0.95rem; margin:0 0 2rem; }

    .auth-form { display:flex; flex-direction:column; gap:1.1rem; }
    .form-field { display:flex; flex-direction:column; gap:0.4rem; }
    .form-field label { font-family:Montserrat; font-size:0.72rem; font-weight:700;
      text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted); }
    .form-field input {
      padding:0.8em 1em; border:1.5px solid var(--border-light); border-radius:10px;
      font-family:Montserrat; font-size:0.95rem; background:var(--bg-depth);
      color:var(--text-main); transition:border-color 0.25s,box-shadow 0.25s;
      outline:none; width:100%; box-sizing:border-box;
    }
    .form-field input:focus { border-color:var(--border-focus); box-shadow:0 0 0 3px rgba(62,40,30,0.08); }

    .auth-submit-btn {
      width:100%; padding:0.9em; background:var(--cta-bg); color:var(--cta-text);
      border:none; border-radius:10px; font-family:Montserrat; font-size:1rem;
      font-weight:700; text-transform:uppercase; letter-spacing:0.08em;
      cursor:pointer; transition:all 0.25s; margin-top:0.4rem;
    }
    .auth-submit-btn:hover { background:var(--cta-hover); transform:translateY(-1px); }

    .auth-divider { display:flex; align-items:center; gap:1rem;
      font-family:Montserrat; font-size:0.8rem; color:var(--text-muted); }
    .auth-divider::before,.auth-divider::after { content:''; flex:1; height:1px; background:var(--border-light); }

    .auth-switch-row { font-family:Montserrat; font-size:0.88rem; color:var(--text-muted); text-align:center; }
    .auth-switch-row a { color:var(--text-main); font-weight:700; text-decoration:none;
      border-bottom:1px solid var(--border-focus); padding-bottom:1px; transition:opacity 0.2s; }
    .auth-switch-row a:hover { opacity:0.7; }

    .auth-alert { font-family:Montserrat; font-size:0.88rem; padding:0.85em 1.1em;
      border-radius:10px; margin-bottom:0.5rem; }
    .auth-alert--error   { background:#fff0f0; color:#c0392b; border:1px solid #f5c6c6; }
    .auth-alert--success { background:#f0fff4; color:#276749; border:1px solid #c6f2d9; }
  </style>
</head>
<body>
<div class="auth-page">

  <!-- Left visual -->
  <div class="auth-visual">
    <div class="auth-visual-logo">Aromea</div>
    <img src="<?php echo BASE_URL; ?>/images/Le Beau Le Parfum.png"
         alt="Aromea Fragrance" class="auth-visual-img"
         onerror="this.src='<?php echo BASE_URL; ?>/images/logo-image.png'">
    <p class="auth-visual-tagline">Welcome back. Your signature scent is waiting.</p>
    <div class="auth-perks">
      <div class="auth-perk"><i class="fa-brands fa-openai"></i> AI fragrance recommendations</div>
      <div class="auth-perk"><i class="fa-solid fa-bag-shopping"></i> Saved cart &amp; wishlist</div>
      <div class="auth-perk"><i class="fa-solid fa-star"></i> Early access to new arrivals</div>
      <div class="auth-perk"><i class="fa-solid fa-tag"></i> Member-only offers</div>
    </div>
  </div>

  <!-- Right form -->
  <div class="auth-form-side">
    <div class="auth-form-wrap">
      <h1>Sign In</h1>
      <p class="lead">Access your Aromea account.</p>

      <?php if ($error): ?>
        <div class="auth-alert auth-alert--error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="auth-alert auth-alert--success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <form class="auth-form" method="POST">
        <div class="form-field">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 placeholder="your@email.com" autocomplete="email"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="form-field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="Your password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="auth-submit-btn">Sign In</button>
        <div class="auth-divider">or</div>
        <p class="auth-switch-row">
          Don't have an account? <a href="<?php echo BASE_URL; ?>/register.php">Create one</a>
        </p>
      </form>
    </div>
  </div>

</div>
</body>
</html>
