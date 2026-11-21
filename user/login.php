<?php
session_start();

require_once __DIR__ . '/../config/config.php';

$error = '';
$savedEmail = ''; // 1. Initialize the variable to prevent the warning

// 2. Check if the user has a saved email cookie from a previous "Remember Me"
if (isset($_COOKIE['remember_email'])) {
    $savedEmail = $_COOKIE['remember_email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']); // Check if remember me was ticked

    if ($email == '' || $password == '') {
        $error = "Please enter email and password.";
    } else {
        $sql = "SELECT id, full_name, email, password_hash
                FROM members
                WHERE email = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);

        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($member && password_verify($password, $member['password_hash'])) {

            session_regenerate_id(true);

            $_SESSION['member_id'] = $member['id'];
            $_SESSION['member_name'] = $member['full_name'];
            $_SESSION['member_email'] = $member['email'];

            // 3. Handle the "Remember Me" cookie
            if ($remember) {
                // Save email for 30 days
                setcookie('remember_email', $email, time() + (86400 * 30), "/"); 
            } else {
                // Clear the cookie if they uncheck the box
                setcookie('remember_email', '', time() - 3600, "/"); 
            }

            header("Location: ../index.html");
            exit;

        } else {
            $error = "Invalid email or password.";
            // Keep the email filled in if login fails so they don't have to retype it
            $savedEmail = htmlspecialchars($email); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="IRON ELITE FITNESS — Member Login. Access your training dashboard, bookings, and performance metrics.">
  <title>IRON ELITE FITNESS — Member Login</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --black: #f7f4f2;
      --charcoal: #ffffff;
      --charcoal-2: #fcf9f8;
      --charcoal-3: #ece3e0;
      --white: #1a1416;
      --grey: #6b5e62;
      --grey-dark: #9b8b8f;
      --accent: #9b1b3a;
      --accent-dim: #7d1430;
      --accent-glow: rgba(155, 27, 58, 0.16);
      --accent-ink: #ffffff;
      --font-head: 'Barlow Condensed', sans-serif;
      --font-body: 'Inter', sans-serif;
      --radius: 16px;
      --radius-sm: 10px;
      --transition: 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      --shadow-soft: 0 10px 40px rgba(26, 20, 22, 0.07);
      --shadow-card: 0 4px 24px rgba(26, 20, 22, 0.05);
    }

    body.dark-mode {
      --black: #0d0a0b;
      --charcoal: #161114;
      --charcoal-2: #1f181c;
      --charcoal-3: #2b2125;
      --white: #f5f0f1;
      --grey: #b0a0a5;
      --grey-dark: #6b5a60;
      --accent: #e84a6f;
      --accent-dim: #c73858;
      --accent-glow: rgba(232, 74, 111, 0.22);
      --accent-ink: #0d0a0b;
      --shadow-soft: 0 10px 40px rgba(0, 0, 0, 0.45);
      --shadow-card: 0 4px 24px rgba(0, 0, 0, 0.35);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    body {
      font-family: var(--font-body);
      background: var(--black);
      color: var(--white);
      overflow-x: hidden;
      transition: background 0.5s ease, color 0.5s ease;
      -webkit-font-smoothing: antialiased;
      min-height: 100vh;
    }

    ::selection { background: var(--accent); color: var(--accent-ink); }

    h1, h2, h3, h4, h5, h6 {
      font-family: var(--font-head);
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.02em;
      line-height: 0.95;
    }

    a { text-decoration: none; color: inherit; }
    a:hover { color: var(--accent); }
    img { max-width: 100%; display: block; }

    .login-wrapper { display: flex; min-height: 100vh; }

    .login-visual {
      position: relative;
      flex: 1 1 48%;
      background-image: url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format&fit=crop');
      background-size: cover;
      background-position: center;
      display: none;
      overflow: hidden;
    }
    .login-visual-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(160deg, rgba(13, 10, 11, 0.82) 0%, rgba(13, 10, 11, 0.55) 50%, rgba(13, 10, 11, 0.85) 100%);
      z-index: 1;
    }
    .login-visual-content {
      position: relative;
      z-index: 2;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: clamp(2rem, 4vw, 3.5rem);
    }
    .visual-brand {
      font-family: var(--font-head);
      font-weight: 900;
      font-size: 1.5rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #fff;
    }
    .visual-brand span { color: var(--accent); }
    .visual-quote { max-width: 440px; }
    .visual-quote .quote-mark {
      font-family: var(--font-head);
      font-size: 5rem;
      line-height: 0.6;
      color: var(--accent);
      opacity: 0.5;
      margin-bottom: 1rem;
    }
    .visual-quote h2 {
      font-size: clamp(2rem, 3.5vw, 3rem);
      font-weight: 900;
      color: #fff;
      line-height: 1;
      margin-bottom: 1.25rem;
    }
    .visual-quote h2 span { color: var(--accent); }
    .visual-quote p {
      font-size: 0.95rem;
      color: rgba(255, 255, 255, 0.65);
      line-height: 1.7;
      max-width: 380px;
    }
    .visual-footer {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      font-size: 0.68rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.4);
    }
    .visual-footer .dot { width: 4px; height: 4px; border-radius: 50%; background: var(--accent); }

    .login-panel {
      flex: 1 1 52%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: clamp(1.5rem, 4vw, 3rem);
      position: relative;
      background: var(--black);
      transition: background 0.5s ease;
    }

    .login-card {
      width: 100%;
      max-width: 440px;
      animation: cardEntrance 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }
    @keyframes cardEntrance {
      from { opacity: 0; transform: translateY(24px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--grey);
      margin-bottom: 2.5rem;
      transition: color 0.3s, gap 0.3s;
    }
    .back-link:hover { color: var(--accent); gap: 0.75rem; }
    .back-link i { font-size: 0.85rem; }

    .mobile-brand {
      font-family: var(--font-head);
      font-weight: 900;
      font-size: 1.3rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--white);
      margin-bottom: 2rem;
      display: block;
    }
    .mobile-brand span { color: var(--accent); }

    .login-heading {
      font-size: clamp(2rem, 4vw, 2.8rem);
      font-weight: 900;
      color: var(--white);
      margin-bottom: 0.6rem;
    }
    .login-sub {
      font-size: 0.88rem;
      color: var(--grey);
      line-height: 1.6;
      margin-bottom: 2.5rem;
    }

    .form-field { margin-bottom: 1.4rem; }
    .form-field .form-label {
      display: block;
      font-size: 0.68rem;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--grey);
      margin-bottom: 0.5rem;
    }

    .input-wrap { position: relative; }
    .input-wrap .field-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--grey-dark);
      font-size: 0.95rem;
      pointer-events: none;
      transition: color 0.3s;
      z-index: 2;
    }
    .input-wrap .form-control {
      width: 100%;
      background: var(--charcoal);
      border: 1px solid var(--charcoal-3);
      border-radius: var(--radius-sm);
      color: var(--white);
      font-size: 0.9rem;
      padding: 0.95rem 1rem 0.95rem 2.75rem;
      transition: border-color 0.3s, box-shadow 0.3s, background 0.3s;
    }
    .input-wrap .form-control:focus {
      background: var(--charcoal);
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-glow);
      color: var(--white);
      outline: none;
    }
    .input-wrap .form-control::placeholder { color: var(--grey-dark); }
    .input-wrap:focus-within .field-icon { color: var(--accent); }

    .pw-toggle {
      position: absolute;
      right: 0.75rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--grey-dark);
      cursor: pointer;
      font-size: 1rem;
      padding: 0.35rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.3s;
      z-index: 2;
    }
    .pw-toggle:hover { color: var(--accent); }

    .form-options {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.75rem;
      margin-top: 0.25rem;
    }
    .remember-label {
      display: flex;
      align-items: center;
      gap: 0.55rem;
      font-size: 0.8rem;
      color: var(--grey);
      cursor: pointer;
      user-select: none;
    }
    .remember-label input[type="checkbox"] {
      appearance: none;
      width: 16px;
      height: 16px;
      border: 1.5px solid var(--charcoal-3);
      border-radius: 4px;
      background: var(--charcoal);
      cursor: pointer;
      position: relative;
      transition: border-color 0.25s, background 0.25s;
      flex-shrink: 0;
    }
    .remember-label input[type="checkbox"]:checked {
      background: var(--accent);
      border-color: var(--accent);
    }
    .remember-label input[type="checkbox"]:checked::after {
      content: '';
      position: absolute;
      left: 4.5px;
      top: 1.5px;
      width: 4px;
      height: 8px;
      border: solid var(--accent-ink);
      border-width: 0 2px 2px 0;
      transform: rotate(45deg);
    }
    .forgot-link {
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--accent);
      letter-spacing: 0.02em;
      transition: opacity 0.3s;
    }
    .forgot-link:hover { opacity: 0.75; color: var(--accent); }

    .btn-submit {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.65rem;
      width: 100%;
      background: var(--accent);
      color: var(--accent-ink);
      font-family: var(--font-body);
      font-weight: 700;
      font-size: 0.82rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 1.05rem 2rem;
      border-radius: 100px;
      border: none;
      cursor: pointer;
      transition: background 0.3s, box-shadow 0.3s, transform 0.2s;
    }
    .btn-submit:hover {
      background: var(--accent-dim);
      box-shadow: 0 0 40px var(--accent-glow);
    }
    .btn-submit:active { transform: scale(0.985); }
    .btn-submit i { font-size: 0.9rem; }

    .divider {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 1.75rem 0;
      color: var(--grey-dark);
      font-size: 0.68rem;
      letter-spacing: 0.15em;
      text-transform: uppercase;
    }
    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--charcoal-3);
    }

    .social-row { display: flex; gap: 0.75rem; }
    .social-btn {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.55rem;
      background: transparent;
      border: 1px solid var(--charcoal-3);
      border-radius: var(--radius-sm);
      color: var(--grey);
      font-size: 0.78rem;
      font-weight: 600;
      padding: 0.8rem 1rem;
      cursor: pointer;
      transition: border-color 0.3s, color 0.3s, background 0.3s;
      font-family: var(--font-body);
    }
    .social-btn:hover {
      border-color: var(--accent);
      color: var(--accent);
      background: var(--accent-glow);
    }
    .social-btn i { font-size: 1rem; }

    .signup-prompt {
      text-align: center;
      margin-top: 2.25rem;
      font-size: 0.85rem;
      color: var(--grey);
    }
    .signup-prompt a {
      color: var(--accent);
      font-weight: 600;
      transition: opacity 0.3s;
    }
    .signup-prompt a:hover { opacity: 0.75; color: var(--accent); }

    .login-error {
      display: none;
      margin-bottom: 1.25rem;
      padding: 0.85rem 1rem;
      background: rgba(214, 59, 59, 0.08);
      border: 1px solid rgba(214, 59, 59, 0.3);
      border-radius: var(--radius-sm);
      color: #d63b3b;
      font-size: 0.8rem;
      align-items: center;
      gap: 0.5rem;
    }
    .login-error.show { display: flex; }

    .theme-toggle-float {
      position: fixed;
      top: 1.5rem;
      right: 1.5rem;
      width: 42px;
      height: 42px;
      border-radius: 50%;
      border: 1px solid var(--charcoal-3);
      background: var(--charcoal);
      color: var(--grey);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: var(--transition);
      font-size: 1rem;
      z-index: 100;
    }
    .theme-toggle-float:hover { border-color: var(--accent); color: var(--accent); }

    @media (min-width: 992px) {
      .login-visual { display: flex; }
      .mobile-brand { display: none; }
    }
    @media (max-width: 991px) {
      .login-visual { display: none; }
      .login-panel { min-height: 100vh; }
    }
    @media (max-width: 575px) {
      .login-panel { padding: 1.25rem; }
      .theme-toggle-float { top: 1rem; right: 1rem; width: 38px; height: 38px; }
      .social-btn span { display: none; }
      .social-btn i { font-size: 1.15rem; }
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
      }
    }
  </style>
</head>
<body>

  <button class="theme-toggle-float" id="themeToggle" aria-label="Toggle theme">
    <i class="bi bi-moon-stars" id="themeIcon"></i>
  </button>

  <div class="login-wrapper">

    <div class="login-visual">
      <div class="login-visual-overlay"></div>
      <div class="login-visual-content">
        <div class="visual-brand">IRON <span>ELITE</span></div>
        <div class="visual-quote">
          <div class="quote-mark">"</div>
          <h2>Discipline<br>Is the <span>Difference.</span></h2>
          <p>Welcome back to the circle. Your training dashboard, class bookings, and performance metrics are waiting.</p>
        </div>
        <div class="visual-footer">
          <span>Est. 2016</span><span class="dot"></span><span>London</span><span class="dot"></span><span>Performance Redefined</span>
        </div>
      </div>
    </div>

    <div class="login-panel">
      <div class="login-card">

        <a href="index.html" class="mobile-brand">IRON <span>ELITE</span></a>

        <a href="index.html" class="back-link">
          <i class="bi bi-arrow-left"></i> Back to Home
        </a>

        <h1 class="login-heading">Member Login</h1>
        <p class="login-sub">Access your training dashboard and keep pushing forward.</p>

        <!-- Server-side error -->
        <div class="login-error <?= !empty($error) ? 'show' : '' ?>" id="loginError">
          <i class="bi bi-exclamation-circle"></i>
          <span id="loginErrorText"><?= htmlspecialchars($error ?: 'Invalid email or password. Please try again.') ?></span>
        </div>

        <form id="loginForm" method="POST" action="login.php">

          <div class="form-field">
            <label for="loginEmail" class="form-label">Email Address</label>
            <div class="input-wrap">
              <i class="bi bi-envelope field-icon"></i>
              <input type="email" class="form-control" id="loginEmail" name="email"
                     placeholder="you@email.com" autocomplete="email" required
                     value="<?= htmlspecialchars($_POST['email'] ?? $savedEmail) ?>">
            </div>
          </div>

          <div class="form-field">
            <label for="loginPassword" class="form-label">Password</label>
            <div class="input-wrap">
              <i class="bi bi-lock field-icon"></i>
              <input type="password" class="form-control" id="loginPassword" name="password"
                     placeholder="Enter your password" autocomplete="current-password" required>
              <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
                <i class="bi bi-eye" id="pwToggleIcon"></i>
              </button>
            </div>
          </div>

          <div class="form-options">
            <label class="remember-label">
              <input type="checkbox" id="rememberMe" name="remember" <?= !empty($savedEmail) ? 'checked' : '' ?>>
              Remember me
            </label>
            <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
          </div>

          <button type="submit" class="btn-submit" id="loginBtn">
            Sign In <i class="bi bi-arrow-right"></i>
          </button>
        </form>

        <div class="divider">Or continue with</div>

        <div class="social-row">
          <button type="button" class="social-btn" onclick="alert('Google login would be integrated here.')">
            <i class="bi bi-google"></i> <span>Google</span>
          </button>
          <button type="button" class="social-btn" onclick="alert('Apple login would be integrated here.')">
            <i class="bi bi-apple"></i> <span>Apple</span>
          </button>
        </div>

        <p class="signup-prompt">
          Not a member yet? <a href="registration.php">Join IRON ELITE</a>
        </p>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    /* THEME TOGGLE */
    (function initTheme() {
      const toggle = document.getElementById('themeToggle');
      const icon = document.getElementById('themeIcon');
      const body = document.body;

      if (localStorage.getItem('ironelite-theme') === 'dark') {
        body.classList.add('dark-mode');
        icon.className = 'bi bi-sun';
      } else {
        icon.className = 'bi bi-moon-stars';
      }

      toggle.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        const isDark = body.classList.contains('dark-mode');
        icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
        localStorage.setItem('ironelite-theme', isDark ? 'dark' : 'light');
      });
    })();

    /* PASSWORD VISIBILITY TOGGLE */
    (function initPasswordToggle() {
      const pwInput = document.getElementById('loginPassword');
      const pwToggle = document.getElementById('pwToggle');
      const pwIcon = document.getElementById('pwToggleIcon');

      pwToggle.addEventListener('click', () => {
        const isPassword = pwInput.type === 'password';
        pwInput.type = isPassword ? 'text' : 'password';
        pwIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        pwToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      });
    })();
  </script>
</body>
</html>