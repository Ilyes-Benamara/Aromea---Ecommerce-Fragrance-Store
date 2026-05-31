<?php
require_once 'config.php';
$page_title = 'Register';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = sanitize($_POST['firstname'] ?? '');
    $lastname  = sanitize($_POST['lastname']  ?? '');
    $fullname  = trim($firstname . ' ' . $lastname);
    $email     = sanitize($_POST['email']     ?? '');
    $password  = $_POST['password']            ?? '';
    $confirm   = $_POST['confirm_password']    ?? '';
    $phone     = sanitize($_POST['phone']      ?? '');
    $age       = intval($_POST['age']          ?? 0);
    $gender    = sanitize($_POST['gender']     ?? '');

    /* Generate a unique username from firstname + lastname + random suffix */
    $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstname . $lastname));
    if (empty($base_username)) $base_username = 'user';
    $username = $base_username . rand(100, 9999);

    if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($age > 0 && ($age < 13 || $age > 120)) {
        $error = 'Please enter a valid age (13+).';
    } else {
        /* Check email not already taken */
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'An account with that email already exists.';
        } else {
            /* Ensure username is unique (loop just in case) */
            do {
                $uchk = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $uchk->bind_param("s", $username);
                $uchk->execute();
                $exists = $uchk->get_result()->num_rows > 0;
                if ($exists) $username = $base_username . rand(1000, 99999);
            } while ($exists);

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db_gender = match($gender) {
                'male'   => 'male',
                'female' => 'female',
                default  => 'prefer_not',
            };

            $age_val = $age > 0 ? $age : null;

            $ins = $conn->prepare("
                INSERT INTO users (username, fullname, email, password_hash, phone, age, gender, user_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'user')
            ");
            $ins->bind_param("sssssss", $username, $fullname, $email, $hash, $phone, $age_val, $db_gender);

            if ($ins->execute()) {
                $success = 'Account created! Redirecting to login…';
                header("refresh:2; url=" . BASE_URL . "/login.php");
            } else {
                $error = 'Registration failed: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — Aromea</title>
  <script src="https://kit.fontawesome.com/0b3d769464.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/style.css">
  <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/images/logo-image.png">
  <style>
    .auth-page { min-height:100vh; display:grid; grid-template-columns:1fr 1fr; }
    @media(max-width:768px){ .auth-page{grid-template-columns:1fr;} .auth-visual{display:none;} }
    .auth-visual {
      background:var(--bg-depth); border-right:1px solid var(--border-light);
      display:flex; flex-direction:column; justify-content:center; align-items:center;
      padding:4rem; gap:2rem; overflow:hidden;
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
      align-items:center; padding:3rem; overflow-y:auto; }
    .auth-form-wrap { width:100%; max-width:420px; }
    .auth-form-wrap h1 { font-size:2rem; margin:0 0 0.4rem;
      text-transform:uppercase; letter-spacing:0.05em; }
    .auth-form-wrap p.lead { font-family:Montserrat; color:var(--text-muted);
      font-size:0.95rem; margin:0 0 2rem; }
    .auth-form { display:flex; flex-direction:column; gap:1.1rem; }
    .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
    @media(max-width:480px){ .form-row{grid-template-columns:1fr;} }
    .form-field { display:flex; flex-direction:column; gap:0.4rem; }
    .form-field label { font-family:Montserrat; font-size:0.72rem; font-weight:700;
      text-transform:uppercase; letter-spacing:0.1em; color:var(--text-muted); }
    .form-field input, .form-field select {
      padding:0.8em 1em; border:1.5px solid var(--border-light); border-radius:10px;
      font-family:Montserrat; font-size:0.95rem; background:var(--bg-depth);
      color:var(--text-main); transition:border-color 0.25s,box-shadow 0.25s;
      outline:none; width:100%; box-sizing:border-box;
    }
    .form-field input:focus, .form-field select:focus {
      border-color:var(--border-focus); box-shadow:0 0 0 3px rgba(62,40,30,0.08);
    }
    .form-field select {
      appearance:none; -webkit-appearance:none; cursor:pointer;
      background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23999' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat:no-repeat; background-position:right 1em center;
    }
    .pw-strength { height:4px; border-radius:2px; background:var(--border-light); overflow:hidden; margin-top:4px; }
    .pw-strength-bar { height:100%; border-radius:2px; transition:width 0.3s,background 0.3s; width:0; }
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
    <img src="<?php echo BASE_URL; ?>/images/Xerjoff Naxos.png"
         alt="Aromea Fragrance" class="auth-visual-img"
         onerror="this.src='<?php echo BASE_URL; ?>/images/liquid brun limited edition.png'">
    <p class="auth-visual-tagline">Join Aromea and discover your signature scent with the help of AI.</p>
    <div class="auth-perks">
      <div class="auth-perk"><i class="fa-brands fa-openai"></i> Personalised AI recommendations</div>
      <div class="auth-perk"><i class="fa-solid fa-bag-shopping"></i> Saved cart across sessions</div>
      <div class="auth-perk"><i class="fa-solid fa-star"></i> Early access to new arrivals</div>
      <div class="auth-perk"><i class="fa-solid fa-tag"></i> Best offers and colections</div>
    </div>
  </div>

  <!-- Right form -->
  <div class="auth-form-side">
    <div class="auth-form-wrap">
      <h1>Create Account</h1>
      <p class="lead">Join thousands of fragrance enthusiasts on Aromea.</p>

      <?php if ($error): ?>
        <div class="auth-alert auth-alert--error">
          <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="auth-alert auth-alert--success">
          <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>

      <form class="auth-form" method="POST">
        <div class="form-row">
          <div class="form-field">
            <label for="firstname">First Name</label>
            <input type="text" id="firstname" name="firstname" placeholder="First name"
                   autocomplete="given-name"
                   value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" required>
          </div>
          <div class="form-field">
            <label for="lastname">Last Name</label>
            <input type="text" id="lastname" name="lastname" placeholder="Last Name"
                   autocomplete="family-name"
                   value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" required>
          </div>
        </div>

        <div class="form-field">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="your@email.com"
                 autocomplete="email"
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>

        <div class="form-field">
          <label for="phone">Phone <span style="font-weight:400;text-transform:none;">(optional)</span></label>
          <input type="tel" id="phone" name="phone" placeholder="+213 6xx xxx xxx"
                 value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        </div>

        <div class="form-field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="At least 8 characters" autocomplete="new-password"
                 oninput="checkPwStrength(this.value)" required>
          <div class="pw-strength"><div class="pw-strength-bar" id="pw-bar"></div></div>
        </div>

        <div class="form-field">
          <label for="confirm_password">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password"
                 placeholder="Repeat password" autocomplete="new-password" required>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="age">Age <span style="font-weight:400;text-transform:none;">(optional)</span></label>
            <input type="number" id="age" name="age" placeholder="e.g. 24" min="13" max="120"
                   value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>">
          </div>
          <div class="form-field">
            <label for="gender">Gender</label>
            <select id="gender" name="gender">
              <option value="">Select…</option>
              <option value="male"   <?php echo (($_POST['gender'] ?? '') === 'male')   ? 'selected' : ''; ?>>Male</option>
              <option value="female" <?php echo (($_POST['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
              <option value="other"  <?php echo (($_POST['gender'] ?? '') === 'other')  ? 'selected' : ''; ?>>Prefer not to say</option>
            </select>
          </div>
        </div>

        <button type="submit" class="auth-submit-btn">Create Account</button>
        <div class="auth-divider">or</div>
        <p class="auth-switch-row">Already have an account?
          <a href="<?php echo BASE_URL; ?>/login.php">Sign in</a>
        </p>
      </form>
    </div>
  </div>

</div>
<script>
function checkPwStrength(val) {
  const bar = document.getElementById('pw-bar');
  let score = 0;
  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const colors = ['#e53e3e','#f59e0b','#3b82f6','#22c55e'];
  bar.style.width = (score * 25) + '%';
  bar.style.background = colors[score - 1] || 'transparent';
}
</script>
</body>
</html>
