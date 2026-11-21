<?php
require_once __DIR__ . '/../config/config.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Show the form (GET request) — fall through to HTML below
} else {
    // ---- Handle form submission ----
    $fullName        = trim($_POST['fullName'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $terms           = isset($_POST['terms']);

    // Validate
    $error = '';

    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!$terms) {
        $error = 'You must agree to the terms and conditions.';
    }

    if ($error === '') {
        try {
            // Duplicate email check
            $stmt = $pdo->prepare('SELECT id FROM members WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'Email already registered.';
            } else {
                // Hash + insert
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    'INSERT INTO members
                     (full_name, email, phone, password_hash, terms_accepted_at)
                     VALUES (?, ?, ?, ?, NOW())'
                );
                $stmt->execute([$fullName, $email, $phone, $passwordHash]);

                // ✅ Success — redirect to index
                header('Location: index.html');
                exit;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="IRON ELITE FITNESS — Join the circle. Create your member account and start training with purpose.">
  <title>IRON ELITE FITNESS — Create Your Account</title>

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

    .register-wrapper { display: flex; min-height: 100vh; }

    .register-visual {
      position: relative;
      flex: 1 1 42%;
      background-image: url('https://images.unsplash.com/photo-1571902943202-507ec2618e8f?q=80&w=1975&auto=format&fit=crop');
      background-size: cover;
      background-position: center;
      display: none;
      overflow: hidden;
    }
    .register-visual-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(160deg, rgba(13, 10, 11, 0.85) 0%, rgba(13, 10, 11, 0.5) 50%, rgba(13, 10, 11, 0.88) 100%);
      z-index: 1;
    }
    .register-visual-content {
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

    .visual-benefits { max-width: 400px; }
    .visual-benefits h2 {
      font-size: clamp(2rem, 3.5vw, 2.8rem);
      font-weight: 900;
      color: #fff;
      line-height: 1;
      margin-bottom: 1.75rem;
    }
    .visual-benefits h2 span { color: var(--accent); }
    .benefit-item {
      display: flex;
      align-items: flex-start;
      gap: 0.9rem;
      margin-bottom: 1.25rem;
    }
    .benefit-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(155, 27, 58, 0.2);
      border: 1px solid rgba(155, 27, 58, 0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent);
      font-size: 1rem;
      flex-shrink: 0;
    }
    .benefit-text {
      font-size: 0.88rem;
      color: rgba(255, 255, 255, 0.7);
      line-height: 1.5;
      padding-top: 0.25rem;
    }
    .benefit-text strong {
      color: #fff;
      font-weight: 600;
      display: block;
      margin-bottom: 0.15rem;
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

    .register-panel {
      flex: 1 1 58%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: clamp(1.5rem, 4vw, 3rem);
      position: relative;
      background: var(--black);
      transition: background 0.5s ease;
    }

    .register-card {
      width: 100%;
      max-width: 500px;
      animation: cardEntrance 0.9s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
      padding: 2rem 0;
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
      margin-bottom: 2rem;
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
      margin-bottom: 1.75rem;
      display: block;
    }
    .mobile-brand span { color: var(--accent); }

    .register-heading {
      font-size: clamp(2rem, 4vw, 2.8rem);
      font-weight: 900;
      color: var(--white);
      margin-bottom: 0.6rem;
    }
    .register-sub {
      font-size: 0.88rem;
      color: var(--grey);
      line-height: 1.6;
      margin-bottom: 2rem;
    }

    .form-field { margin-bottom: 1.15rem; }
    .form-field .form-label {
      display: block;
      font-size: 0.68rem;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--grey);
      margin-bottom: 0.45rem;
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
      padding: 0.9rem 1rem 0.9rem 2.75rem;
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

    .pw-strength { display: flex; gap: 0.35rem; margin-top: 0.6rem; }
    .pw-strength-bar {
      flex: 1;
      height: 3px;
      border-radius: 10px;
      background: var(--charcoal-3);
      transition: background 0.3s;
    }
    .pw-strength-bar.weak { background: #d63b3b; }
    .pw-strength-bar.medium { background: #d69b3b; }
    .pw-strength-bar.strong { background: #3ba55d; }
    .pw-strength-label {
      font-size: 0.66rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--grey-dark);
      margin-top: 0.35rem;
      transition: color 0.3s;
    }

    .pw-requirements {
      list-style: none;
      padding: 0;
      margin: 0.6rem 0 0;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.3rem 1rem;
    }
    .pw-requirements li {
      font-size: 0.68rem;
      color: var(--grey-dark);
      display: flex;
      align-items: center;
      gap: 0.35rem;
      transition: color 0.3s;
    }
    .pw-requirements li i { font-size: 0.6rem; transition: color 0.3s; }
    .pw-requirements li.met { color: var(--grey); }
    .pw-requirements li.met i { color: #3ba55d; }

    .terms-label {
      display: flex;
      align-items: flex-start;
      gap: 0.6rem;
      font-size: 0.8rem;
      color: var(--grey);
      cursor: pointer;
      user-select: none;
      margin: 1.25rem 0 1.5rem;
      line-height: 1.5;
    }
    .terms-label input[type="checkbox"] {
      appearance: none;
      width: 17px;
      height: 17px;
      border: 1.5px solid var(--charcoal-3);
      border-radius: 4px;
      background: var(--charcoal);
      cursor: pointer;
      position: relative;
      transition: border-color 0.25s, background 0.25s;
      flex-shrink: 0;
      margin-top: 2px;
    }
    .terms-label input[type="checkbox"]:checked {
      background: var(--accent);
      border-color: var(--accent);
    }
    .terms-label input[type="checkbox"]:checked::after {
      content: '';
      position: absolute;
      left: 5px;
      top: 2px;
      width: 4px;
      height: 9px;
      border: solid var(--accent-ink);
      border-width: 0 2px 2px 0;
      transform: rotate(45deg);
    }
    .terms-label a { color: var(--accent); font-weight: 600; }
    .terms-label a:hover { opacity: 0.75; }

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
      margin: 1.5rem 0;
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

    .login-prompt {
      text-align: center;
      margin-top: 2rem;
      font-size: 0.85rem;
      color: var(--grey);
    }
    .login-prompt a { color: var(--accent); font-weight: 600; transition: opacity 0.3s; }
    .login-prompt a:hover { opacity: 0.75; color: var(--accent); }

    .form-error-box {
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
    .form-error-box.show { display: flex; }

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
      .register-visual { display: flex; }
      .mobile-brand { display: none; }
    }
    @media (max-width: 991px) {
      .register-visual { display: none; }
      .register-panel { min-height: 100vh; }
    }
    @media (max-width: 575px) {
      .register-panel { padding: 1.25rem; }
      .theme-toggle-float { top: 1rem; right: 1rem; width: 38px; height: 38px; }
      .social-btn span { display: none; }
      .social-btn i { font-size: 1.15rem; }
      .pw-requirements { grid-template-columns: 1fr; }
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

  <div class="register-wrapper">

    <div class="register-visual">
      <div class="register-visual-overlay"></div>
      <div class="register-visual-content">
        <div class="visual-brand">IRON <span>ELITE</span></div>
        <div class="visual-benefits">
          <h2>Join the<br><span>Circle.</span></h2>
          <div class="benefit-item">
            <div class="benefit-icon"><i class="bi bi-lightning-charge"></i></div>
            <div class="benefit-text"><strong>World-Class Programming</strong>Periodized training designed by elite coaches.</div>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon"><i class="bi bi-people"></i></div>
            <div class="benefit-text"><strong>Community of the Relentless</strong>Train alongside people who refuse to settle.</div>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon"><i class="bi bi-activity"></i></div>
            <div class="benefit-text"><strong>Track Everything</strong>Body composition scans, performance metrics, progress.</div>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon"><i class="bi bi-droplet-half"></i></div>
            <div class="benefit-text"><strong>Recovery Included</strong>Cold plunge, sauna, and compression therapy access.</div>
          </div>
        </div>
        <div class="visual-footer">
          <span>Est. 2016</span><span class="dot"></span><span>London</span><span class="dot"></span><span>Performance Redefined</span>
        </div>
      </div>
    </div>

    <div class="register-panel">
      <div class="register-card">

        <a href="index.html" class="mobile-brand">IRON <span>ELITE</span></a>

        <a href="index.html" class="back-link">
          <i class="bi bi-arrow-left"></i> Back to Home
        </a>

        <div id="formContent">
          <h1 class="register-heading">Create Account</h1>
          <p class="register-sub">Your transformation starts with a single step. Fill in your details below.</p>

          <!-- Server-side error display -->
          <div class="form-error-box <?= !empty($error) ? 'show' : '' ?>" id="formError">
            <i class="bi bi-exclamation-circle"></i>
            <span id="formErrorText"><?= htmlspecialchars($error ?: 'Please check the highlighted fields.') ?></span>
          </div>

          <form id="registerForm" method="POST" action="registration.php">

            <div class="form-field">
              <label for="fullName" class="form-label">Full Name</label>
              <div class="input-wrap">
                <i class="bi bi-person field-icon"></i>
                <input type="text" class="form-control" id="fullName" name="fullName"
                       placeholder="Alex Morgan" autocomplete="name" required
                       value="<?= htmlspecialchars($_POST['fullName'] ?? '') ?>">
              </div>
            </div>

            <div class="form-field">
              <label for="regEmail" class="form-label">Email Address</label>
              <div class="input-wrap">
                <i class="bi bi-envelope field-icon"></i>
                <input type="email" class="form-control" id="regEmail" name="email"
                       placeholder="you@email.com" autocomplete="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
              </div>
            </div>

            <div class="form-field">
              <label for="regPhone" class="form-label">Phone Number</label>
              <div class="input-wrap">
                <i class="bi bi-telephone field-icon"></i>
                <input type="tel" class="form-control" id="regPhone" name="phone"
                       placeholder="+44 20 7123 4567" autocomplete="tel"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
              </div>
            </div>

            <div class="form-field">
              <label for="regPassword" class="form-label">Password</label>
              <div class="input-wrap">
                <i class="bi bi-lock field-icon"></i>
                <input type="password" class="form-control" id="regPassword" name="password"
                       placeholder="Create a strong password" autocomplete="new-password" required minlength="8">
                <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
                  <i class="bi bi-eye" id="pwToggleIcon"></i>
                </button>
              </div>
              <div class="pw-strength" id="pwStrength">
                <div class="pw-strength-bar" data-index="0"></div>
                <div class="pw-strength-bar" data-index="1"></div>
                <div class="pw-strength-bar" data-index="2"></div>
                <div class="pw-strength-bar" data-index="3"></div>
              </div>
              <div class="pw-strength-label" id="pwStrengthLabel">Enter a password</div>
              <ul class="pw-requirements" id="pwRequirements">
                <li data-req="length"><i class="bi bi-circle"></i> 8+ characters</li>
                <li data-req="upper"><i class="bi bi-circle"></i> Uppercase letter</li>
                <li data-req="number"><i class="bi bi-circle"></i> Number</li>
                <li data-req="special"><i class="bi bi-circle"></i> Special character</li>
              </ul>
            </div>

            <div class="form-field">
              <label for="confirmPassword" class="form-label">Confirm Password</label>
              <div class="input-wrap">
                <i class="bi bi-lock field-icon"></i>
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword"
                       placeholder="Re-enter your password" autocomplete="new-password" required minlength="8">
              </div>
            </div>

            <label class="terms-label">
              <input type="checkbox" id="termsCheck" name="terms" required>
              <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>. I understand my membership is subject to a 30-day cancellation notice.</span>
            </label>

            <button type="submit" class="btn-submit" id="registerBtn">
              Create Account <i class="bi bi-arrow-right"></i>
            </button>
          </form>

          <div class="divider">Or sign up with</div>

          <div class="social-row">
            <button type="button" class="social-btn" onclick="alert('Google signup would be integrated here.')">
              <i class="bi bi-google"></i> <span>Google</span>
            </button>
            <button type="button" class="social-btn" onclick="alert('Apple signup would be integrated here.')">
              <i class="bi bi-apple"></i> <span>Apple</span>
            </button>
          </div>

          <p class="login-prompt">
            Already a member? <a href="login.html">Sign in</a>
          </p>
        </div>

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
      const pwInput = document.getElementById('regPassword');
      const pwToggle = document.getElementById('pwToggle');
      const pwIcon = document.getElementById('pwToggleIcon');

      pwToggle.addEventListener('click', () => {
        const isPassword = pwInput.type === 'password';
        pwInput.type = isPassword ? 'text' : 'password';
        pwIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        pwToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      });
    })();

    /* PASSWORD STRENGTH METER */
    (function initPasswordStrength() {
      const pwInput = document.getElementById('regPassword');
      const bars = document.querySelectorAll('.pw-strength-bar');
      const label = document.getElementById('pwStrengthLabel');
      const reqs = document.querySelectorAll('#pwRequirements li');

      const checks = {
        length: (v) => v.length >= 8,
        upper: (v) => /[A-Z]/.test(v),
        number: (v) => /[0-9]/.test(v),
        special: (v) => /[^A-Za-z0-9]/.test(v)
      };

      function evaluate() {
        const val = pwInput.value;
        let score = 0;
        const results = {};

        for (const [key, fn] of Object.entries(checks)) {
          const met = fn(val);
          results[key] = met;
          if (met) score++;
        }

        reqs.forEach(li => {
          const req = li.getAttribute('data-req');
          if (results[req]) {
            li.classList.add('met');
            li.querySelector('i').className = 'bi bi-check-circle-fill';
          } else {
            li.classList.remove('met');
            li.querySelector('i').className = 'bi bi-circle';
          }
        });

        bars.forEach((bar, i) => {
          bar.className = 'pw-strength-bar';
          if (val.length > 0 && i < score) {
            if (score <= 1) bar.classList.add('weak');
            else if (score <= 2) bar.classList.add('medium');
            else bar.classList.add('strong');
          }
        });

        if (val.length === 0) {
          label.textContent = 'Enter a password';
          label.style.color = 'var(--grey-dark)';
        } else if (score <= 1) {
          label.textContent = 'Weak password';
          label.style.color = '#d63b3b';
        } else if (score <= 2) {
          label.textContent = 'Medium strength';
          label.style.color = '#d69b3b';
        } else if (score === 3) {
          label.textContent = 'Good password';
          label.style.color = '#3ba55d';
        } else {
          label.textContent = 'Strong password';
          label.style.color = '#3ba55d';
        }
      }

      pwInput.addEventListener('input', evaluate);
    })();
  </script>
</body>
</html>