<?php
require_once __DIR__ . '/config/config.php'; // loads $pdo

try {
    $stmt = $pdo->query("SELECT * FROM hero_section WHERE is_active = 1 LIMIT 1");
    $hero = $stmt->fetch();

    if (!$hero) {
        $hero = [
            'eyebrow_text'        => 'Est. 2016 — Performance Redefined',
            'title_line_1'        => 'Forged in',
            'title_line_2'        => 'Iron.',
            'title_line_3'        => 'Built for',
            'title_line_4'        => 'Elite.',
            'title_line_2_accent' => 1,
            'description'         => 'More than a gym. A performance laboratory where discipline meets design, and every rep is engineered for progress. Step inside the circle.',
            'cta_primary_text'    => 'Join Now',
            'cta_primary_link'    => '#pricing',
            'cta_primary_icon'    => 'bi bi-arrow-up-right',
            'cta_secondary_text'  => 'Explore Programs',
            'cta_secondary_link'  => '#programs',
            'scroll_text'         => 'Scroll',
            'background_image'    => '',
            'background_video'    => '',
        ];
    }
} catch (PDOException $e) {
    error_log('Hero fetch failed: ' . $e->getMessage());
    $hero = null;
}

// ---- Marquee items ----
try {
    $stmt = $pdo->query("
        SELECT label
        FROM marquee_items
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $marquee_items = $stmt->fetchAll(PDO::FETCH_COLUMN); // array of strings
} catch (PDOException $e) {
    error_log('Marquee fetch failed: ' . $e->getMessage());
    $marquee_items = [];
}


// ---- About section ----
try {
    $stmt = $pdo->query("SELECT * FROM about_section WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
    $about = $stmt->fetch();

    if (!$about) {
        $about = [
            'label'          => 'The Story',
            'title_line_1'   => 'Where Ambition',
            'title_line_2'   => 'Meets Architecture',
            'para_1'         => '',
            'para_2'         => '',
            'image'          => '',
        ];
    }
} catch (PDOException $e) {
    error_log('About fetch failed: ' . $e->getMessage());
    $about = null;
}


// ---- Pricing section header ----
try {
    $stmt = $pdo->query("SELECT * FROM pricing_section WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
    $pricing_section = $stmt->fetch() ?: [
        'label'    => 'Membership',
        'title'    => 'Invest in Your Edge',
        'subtitle' => '',
    ];
} catch (PDOException $e) {
    error_log('Pricing section fetch failed: ' . $e->getMessage());
    $pricing_section = null;
}

// ---- Pricing plans + their features ----
$pricing_plans = [];
try {
    $stmt = $pdo->query("
        SELECT *
        FROM pricing_plans
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $plans = $stmt->fetchAll();

    if ($plans) {
        $planIds = array_column($plans, 'id');
        // PDO-safe IN clause
        $in = implode(',', array_fill(0, count($planIds), '?'));
        $fstmt = $pdo->prepare("
            SELECT plan_id, feature_text, is_included
            FROM pricing_features
            WHERE plan_id IN ($in)
            ORDER BY sort_order ASC, id ASC
        ");
        $fstmt->execute($planIds);

        // Group features by plan_id
        $featuresByPlan = [];
        foreach ($fstmt->fetchAll() as $f) {
            $featuresByPlan[$f['plan_id']][] = $f;
        }

        foreach ($plans as $p) {
            $p['features'] = $featuresByPlan[$p['id']] ?? [];
            $pricing_plans[] = $p;
        }
    }
} catch (PDOException $e) {
    error_log('Pricing plans fetch failed: ' . $e->getMessage());
    $pricing_plans = [];
}

// ---- Programs section header ----
try {
    $stmt = $pdo->query("SELECT * FROM programs_section WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
    $programs_section = $stmt->fetch() ?: [
        'label'    => 'Disciplines',
        'title'    => 'Train With Purpose',
        'subtitle' => '',
    ];
} catch (PDOException $e) {
    error_log('Programs section fetch failed: ' . $e->getMessage());
    $programs_section = null;
}

// ---- Program items ----
$programs_items = [];
try {
    $stmt = $pdo->query("
        SELECT *
        FROM programs_items
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $programs_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Programs items fetch failed: ' . $e->getMessage());
    $programs_items = [];
}

// ---- Trainers section header ----
try {
    $stmt = $pdo->query("SELECT * FROM trainers_section WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
    $trainers_section = $stmt->fetch() ?: [
        'label'    => 'The Coaches',
        'title'    => 'Masters of Their Craft',
        'subtitle' => '',
    ];
} catch (PDOException $e) {
    error_log('Trainers section fetch failed: ' . $e->getMessage());
    $trainers_section = null;
}

// ---- Trainer items ----
$trainers_items = [];
try {
    $stmt = $pdo->query("
        SELECT *
        FROM trainers_items
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $trainers_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Trainers items fetch failed: ' . $e->getMessage());
    $trainers_items = [];
}


// ---- Facilities section header ----
try {
    $stmt = $pdo->query("SELECT * FROM facilities_section WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
    $facilities_section = $stmt->fetch() ?: [
        'label'         => 'The Space',
        'title_line_1'  => 'Built for',
        'title_line_2'  => 'Performance',
        'subtitle'      => '',
        'image_main'    => '',
        'image_small_1' => '',
        'image_small_2' => '',
    ];
} catch (PDOException $e) {
    error_log('Facilities section fetch failed: ' . $e->getMessage());
    $facilities_section = null;
}

// ---- Facility items ----
$facilities_items = [];
try {
    $stmt = $pdo->query("
        SELECT *
        FROM facilities_items
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $facilities_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Facilities items fetch failed: ' . $e->getMessage());
    $facilities_items = [];
}

// ---- Testimonials section header ----
try {
    $stmt = $pdo->query("SELECT * FROM testimonials_section WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
    $testimonials_section = $stmt->fetch() ?: [
        'label'    => 'Community',
        'title'    => 'What Members Say',
        'subtitle' => '',
    ];
} catch (PDOException $e) {
    error_log('Testimonials section fetch failed: ' . $e->getMessage());
    $testimonials_section = null;
}

// ---- Testimonial items ----
$testimonials_items = [];
try {
    $stmt = $pdo->query("
        SELECT *
        FROM testimonials_items
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $testimonials_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Testimonials items fetch failed: ' . $e->getMessage());
    $testimonials_items = [];
}

// Fallback if empty
if (empty($marquee_items)) {
    $marquee_items = ['Strength','Discipline','Performance','Community','Results','Mindset'];
}

// ---- Schedule section header ----
try {
    $stmt = $pdo->query("SELECT * FROM schedule_section WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
    $schedule_section = $stmt->fetch() ?: [
        'label'    => 'Timetable',
        'title'    => 'Weekly Schedule',
        'subtitle' => '',
    ];
} catch (PDOException $e) {
    error_log('Schedule section fetch failed: ' . $e->getMessage());
    $schedule_section = null;
}

// ---- Schedule times (row headers) + cells ----
$schedule_times = [];       // [ ['id'=>1,'time_label'=>'06:00',...], ... ]
$schedule_grid  = [];       // [ time_id => [ day_index => class_name ], ... ]

try {
    // Row headers — assumes schedule_times table; adjust name/column if different
    $stmt = $pdo->query("
        SELECT id, time_label
        FROM schedule_times
        ORDER BY sort_order ASC, id ASC
    ");
    $schedule_times = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Schedule times fetch failed: ' . $e->getMessage());
    $schedule_times = [];
}

try {
    // Cells: build a grid keyed [time_id][day_index]
    $stmt = $pdo->query("SELECT time_id, day_index, class_name FROM schedule_cells");
    foreach ($stmt->fetchAll() as $c) {
        $schedule_grid[$c['time_id']][$c['day_index']] = $c['class_name'];
    }
} catch (PDOException $e) {
    error_log('Schedule cells fetch failed: ' . $e->getMessage());
    $schedule_grid = [];
}


// ---- Journey section header ----
try {
    $stmt = $pdo->query("SELECT * FROM journey_section WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
    $journey_section = $stmt->fetch() ?: [
        'label'    => 'Get Started',
        'title'    => 'Your Journey Starts Here',
        'subtitle' => '',
        'cta_text' => 'Begin Your Transformation',
        'cta_link' => '#pricing',
        'cta_icon' => 'bi bi-arrow-right',
    ];
} catch (PDOException $e) {
    error_log('Journey section fetch failed: ' . $e->getMessage());
    $journey_section = null;
}

// ---- Journey steps ----
$journey_steps = [];
try {
    $stmt = $pdo->query("
        SELECT *
        FROM journey_steps
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $journey_steps = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Journey steps fetch failed: ' . $e->getMessage());
    $journey_steps = [];
}

// ---- FAQ section header ----
try {
    $stmt = $pdo->query("SELECT * FROM faq_section WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
    $faq_section = $stmt->fetch() ?: [
        'label'    => 'Questions',
        'title'    => 'Everything You Need to Know',
        'subtitle' => '',
    ];
} catch (PDOException $e) {
    error_log('FAQ section fetch failed: ' . $e->getMessage());
    $faq_section = null;
}

// ---- FAQ items ----
$faq_items = [];
try {
    $stmt = $pdo->query("
        SELECT *
        FROM faq_items
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $faq_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('FAQ items fetch failed: ' . $e->getMessage());
    $faq_items = [];
}

// Helper: format FAQ title so admins can control the line break with "|" or a newline
function faq_title_html($title) {
    if (strpos($title, '|') !== false) {
        $parts = array_map('trim', explode('|', $title, 2));
        return htmlspecialchars($parts[0], ENT_QUOTES, 'UTF-8')
             . '<br>'
             . htmlspecialchars($parts[1], ENT_QUOTES, 'UTF-8');
    }
    if (strpos($title, "\n") !== false) {
        return nl2br(htmlspecialchars($title, ENT_QUOTES, 'UTF-8'));
    }
    return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
}


function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="IRON ELITE FITNESS — Luxury training, elite coaches, and a community built on strength. Join the movement.">
  <title>IRON ELITE FITNESS — Luxury Gym & Performance Studio</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Google Fonts: Barlow Condensed (headlines) + Inter (body) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* ============================================================
       ROOT & VARIABLES — LIGHT LUXURY THEME (default) — WINE/BURGUNDY ACCENT
       ============================================================ */
    :root {
      --black: #f7f4f2;          /* soft warm canvas with a hint of rose */
      --charcoal: #ffffff;       /* pure surface */
      --charcoal-2: #fcf9f8;     /* elevated surface */
      --charcoal-3: #ece3e0;     /* borders / muted */
      --white: #1a1416;          /* primary text (near-black with warmth) */
      --grey: #6b5e62;           /* secondary text */
      --grey-dark: #9b8b8f;      /* tertiary text */
      --accent: #9b1b3a;         /* deep wine / burgundy */
      --accent-dim: #7d1430;
      --accent-glow: rgba(155, 27, 58, 0.16);
      --accent-ink: #ffffff;     /* text on accent */
      --font-head: 'Barlow Condensed', sans-serif;
      --font-body: 'Inter', sans-serif;
      --radius: 16px;
      --radius-sm: 10px;
      --transition: 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      --nav-h: 80px;
      --shadow-soft: 0 10px 40px rgba(26, 20, 22, 0.07);
      --shadow-card: 0 4px 24px rgba(26, 20, 22, 0.05);
    }

    /* Dark mode overrides (toggle) */
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

    /* ============================================================
       BASE
       ============================================================ */
    * { margin: 0; padding: 0; box-sizing: border-box; }

    html { scroll-behavior: smooth; }

    body {
      font-family: var(--font-body);
      background: var(--black);
      color: var(--white);
      overflow-x: hidden;
      transition: background 0.5s ease, color 0.5s ease;
      -webkit-font-smoothing: antialiased;
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

    /* ============================================================
       CUSTOM CURSOR (desktop only)
       ============================================================ */
    .cursor-dot, .cursor-ring {
      position: fixed;
      top: 0; left: 0;
      pointer-events: none;
      z-index: 99999;
      border-radius: 50%;
      transform: translate(-50%, -50%);
      transition: opacity 0.3s ease;
    }
    .cursor-dot {
      width: 8px; height: 8px;
      background: var(--accent);
    }
    .cursor-ring {
      width: 40px; height: 40px;
      border: 2px solid rgba(155, 27, 58, 0.45);
      transition: width 0.2s, height 0.2s, border-color 0.2s;
    }
    body.dark-mode .cursor-ring { border-color: rgba(232, 74, 111, 0.45); }
    .cursor-ring.hover-link { width: 60px; height: 60px; border-color: var(--accent); }
    .cursor-ring.hover-cta { width: 80px; height: 80px; border-color: var(--accent); background: var(--accent-glow); }

    @media (max-width: 991px) {
      .cursor-dot, .cursor-ring { display: none !important; }
    }

    /* ============================================================
       LOADING SCREEN
       ============================================================ */
    #loader {
      position: fixed;
      inset: 0;
      background: var(--black);
      z-index: 100000;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      transition: opacity 0.8s ease, visibility 0.8s ease;
    }
    #loader.hidden { opacity: 0; visibility: hidden; pointer-events: none; }

    .loader-logo {
      font-family: var(--font-head);
      font-weight: 900;
      font-size: clamp(2.5rem, 6vw, 5rem);
      letter-spacing: 0.08em;
      color: var(--white);
      text-transform: uppercase;
      margin-bottom: 2rem;
      opacity: 0;
      transform: translateY(20px);
    }
    .loader-logo span { color: var(--accent); }

    .loader-bar-track {
      width: min(320px, 70vw);
      height: 3px;
      background: var(--charcoal-3);
      border-radius: 10px;
      overflow: hidden;
      opacity: 0;
    }
    .loader-bar-fill {
      height: 100%;
      width: 0%;
      background: var(--accent);
      border-radius: 10px;
      box-shadow: 0 0 20px var(--accent-glow);
    }
    .loader-percent {
      font-family: var(--font-body);
      font-size: 0.75rem;
      letter-spacing: 0.3em;
      color: var(--grey);
      margin-top: 1rem;
      opacity: 0;
    }

    /* ============================================================
       NAVIGATION
       ============================================================ */
    .navbar-elite {
      position: fixed;
      top: 0; left: 0; right: 0;
      height: var(--nav-h);
      z-index: 10000;
      display: flex;
      align-items: center;
      padding: 0 clamp(1.2rem, 4vw, 3rem);
      transition: background 0.4s ease, backdrop-filter 0.4s ease, border-color 0.4s ease;
      border-bottom: 1px solid transparent;
    }
    .navbar-elite.scrolled {
      background: rgba(255, 255, 255, 0.82);
      backdrop-filter: blur(18px) saturate(1.4);
      border-bottom-color: rgba(26, 20, 22, 0.07);
    }
    body.dark-mode .navbar-elite.scrolled {
      background: rgba(13, 10, 11, 0.82);
      border-bottom-color: rgba(255, 255, 255, 0.06);
    }

    .nav-brand {
      font-family: var(--font-head);
      font-weight: 900;
      font-size: 1.35rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--white);
      white-space: nowrap;
    }
    .nav-brand span { color: var(--accent); }

    .nav-links-desktop {
      display: none;
      list-style: none;
      gap: clamp(1rem, 2vw, 2rem);
      margin: 0 auto;
      padding: 0;
    }
    .nav-links-desktop a {
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--grey);
      transition: color 0.3s;
      position: relative;
    }
    .nav-links-desktop a:hover, .nav-links-desktop a.active { color: var(--white); }
    .nav-links-desktop a::after {
      content: '';
      position: absolute;
      bottom: -6px; left: 0;
      width: 0; height: 2px;
      background: var(--accent);
      transition: width 0.3s;
    }
    .nav-links-desktop a:hover::after, .nav-links-desktop a.active::after { width: 100%; }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-left: auto;
    }

    .btn-theme-toggle {
      width: 40px; height: 40px;
      border-radius: 50%;
      border: 1px solid var(--charcoal-3);
      background: transparent;
      color: var(--grey);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: var(--transition);
      font-size: 1rem;
    }
    .btn-theme-toggle:hover { border-color: var(--accent); color: var(--accent); }

    .btn-hamburger {
      width: 40px; height: 40px;
      border-radius: 50%;
      border: 1px solid var(--charcoal-3);
      background: transparent;
      color: var(--white);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 1.2rem;
      transition: var(--transition);
    }
    .btn-hamburger:hover { border-color: var(--accent); color: var(--accent); }

    /* Mobile menu */
    .mobile-menu {
      position: fixed;
      inset: 0;
      background: var(--black);
      z-index: 10001;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 1.2rem;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.4s ease, visibility 0.4s ease;
    }
    .mobile-menu.open { opacity: 1; visibility: visible; }
    .mobile-menu a {
      font-family: var(--font-head);
      font-size: clamp(1.6rem, 5vw, 2.4rem);
      font-weight: 700;
      text-transform: uppercase;
      color: var(--grey);
      transition: color 0.3s, transform 0.3s;
      letter-spacing: 0.04em;
    }
    .mobile-menu a:hover { color: var(--accent); transform: translateX(8px); }
    .mobile-menu .close-btn {
      position: absolute;
      top: 1.5rem; right: 1.5rem;
      font-size: 2rem;
      color: var(--grey);
      background: none;
      border: none;
      cursor: pointer;
    }

    @media (min-width: 992px) {
      .nav-links-desktop { display: flex; }
      .btn-hamburger { display: none; }
    }

    /* ============================================================
       BUTTONS & MAGNETIC
       ============================================================ */
    .btn-accent {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      background: var(--accent);
      color: var(--accent-ink);
      font-family: var(--font-body);
      font-weight: 700;
      font-size: 0.78rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 1rem 2.2rem;
      border-radius: 100px;
      border: none;
      cursor: pointer;
      transition: background 0.3s, box-shadow 0.3s, transform 0.2s;
      white-space: nowrap;
    }
    .btn-accent:hover {
      background: var(--accent-dim);
      color: var(--accent-ink);
      box-shadow: 0 0 40px var(--accent-glow);
    }

    .btn-ghost {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      background: transparent;
      color: var(--white);
      font-family: var(--font-body);
      font-weight: 600;
      font-size: 0.78rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 1rem 2.2rem;
      border-radius: 100px;
      border: 1px solid rgba(26, 20, 22, 0.2);
      cursor: pointer;
      transition: border-color 0.3s, background 0.3s, color 0.3s;
      white-space: nowrap;
    }
    body.dark-mode .btn-ghost { border-color: rgba(255, 255, 255, 0.2); }
    .btn-ghost:hover {
      border-color: var(--accent);
      color: var(--accent);
      background: var(--accent-glow);
    }

    .magnetic { display: inline-block; }

    /* ============================================================
       HERO
       ============================================================ */
    .hero-section {
      position: relative;
      min-height: 100vh;
      display: flex;
      align-items: center;
      overflow: hidden;
      background: var(--black);
    }
    .hero-bg {
      position: absolute;
      inset: -10%;
      background-image: url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=2070&auto=format&fit=crop');
      background-size: cover;
      background-position: center;
      will-change: transform;
      filter: saturate(0.85) brightness(1.1);
    }
    body.dark-mode .hero-bg { filter: saturate(1) brightness(1); }
    .hero-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(105deg, rgba(247, 244, 242, 0.94) 0%, rgba(247, 244, 242, 0.7) 45%, rgba(247, 244, 242, 0.35) 100%);
      z-index: 1;
    }
    body.dark-mode .hero-overlay {
      background: linear-gradient(105deg, rgba(13, 10, 11, 0.92) 0%, rgba(13, 10, 11, 0.55) 45%, rgba(13, 10, 11, 0.25) 100%);
    }
    .hero-content {
      position: relative;
      z-index: 2;
      padding: calc(var(--nav-h) + 2rem) clamp(1.2rem, 4vw, 3rem) 4rem;
      max-width: 900px;
    }
    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 0.7rem;
      font-weight: 600;
      letter-spacing: 0.25em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 1.5rem;
    }
    .hero-eyebrow::before {
      content: '';
      width: 40px; height: 2px;
      background: var(--accent);
    }
    .hero-title {
      font-size: clamp(3rem, 9vw, 7.5rem);
      font-weight: 900;
      color: var(--white);
      margin-bottom: 1.5rem;
      line-height: 0.9;
    }
    .hero-title .line { display: block; overflow: hidden; }
    .hero-title .line span { display: inline-block; }
    .hero-title .accent { color: var(--accent); }
    .hero-desc {
      font-size: clamp(0.95rem, 1.4vw, 1.1rem);
      color: var(--grey);
      max-width: 520px;
      line-height: 1.7;
      margin-bottom: 2.5rem;
      font-weight: 400;
    }
    .hero-ctas { display: flex; flex-wrap: wrap; gap: 1rem; }

    .hero-scroll-indicator {
      position: absolute;
      bottom: 2rem; left: 50%;
      transform: translateX(-50%);
      z-index: 2;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
      color: var(--grey);
      font-size: 0.6rem;
      letter-spacing: 0.25em;
      text-transform: uppercase;
    }
    .scroll-line {
      width: 1px; height: 40px;
      background: linear-gradient(to bottom, var(--accent), transparent);
      animation: scrollPulse 2s ease-in-out infinite;
    }
    @keyframes scrollPulse {
      0%, 100% { opacity: 0.3; transform: scaleY(0.6); }
      50% { opacity: 1; transform: scaleY(1); }
    }

    /* ============================================================
       SECTION SHARED
       ============================================================ */
    .section-pad { padding: clamp(4rem, 10vw, 8rem) clamp(1.2rem, 4vw, 3rem); }
    .section-label {
      font-size: 0.68rem;
      font-weight: 600;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .section-label::before {
      content: '';
      width: 24px; height: 2px;
      background: var(--accent);
    }
    .section-title {
      font-size: clamp(2rem, 4.5vw, 3.8rem);
      color: var(--white);
      margin-bottom: 1rem;
    }
    .section-sub {
      color: var(--grey);
      font-size: 0.95rem;
      line-height: 1.7;
      max-width: 560px;
      font-weight: 400;
    }

    /* ============================================================
       ABOUT
       ============================================================ */
    .about-section { background: var(--charcoal); }
    .about-img-wrap {
      position: relative;
      border-radius: var(--radius);
      overflow: hidden;
      aspect-ratio: 4/5;
      box-shadow: var(--shadow-soft);
    }
    .about-img-wrap img {
      width: 100%; height: 100%;
      object-fit: cover;
      transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    .about-img-wrap:hover img { transform: scale(1.06); }
    .about-img-accent {
      position: absolute;
      bottom: -20px; right: -20px;
      width: 120px; height: 120px;
      border: 2px solid var(--accent);
      border-radius: var(--radius);
      z-index: -1;
    }

    .stat-item { text-align: center; }
    .stat-number {
      font-family: var(--font-head);
      font-size: clamp(2.2rem, 4vw, 3.2rem);
      font-weight: 900;
      color: var(--accent);
      line-height: 1;
    }
    .stat-suffix { font-size: 0.6em; }
    .stat-label {
      font-size: 0.7rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--grey);
      margin-top: 0.4rem;
    }

    /* ============================================================
       PRICING
       ============================================================ */
    .pricing-card {
      background: var(--charcoal);
      border: 1px solid var(--charcoal-3);
      border-radius: var(--radius);
      padding: clamp(1.8rem, 3vw, 2.5rem);
      height: 100%;
      display: flex;
      flex-direction: column;
      transition: border-color 0.4s, transform 0.4s, box-shadow 0.4s;
      position: relative;
      box-shadow: var(--shadow-card);
    }
    .pricing-card:hover {
      border-color: rgba(155, 27, 58, 0.4);
      transform: translateY(-6px);
      box-shadow: var(--shadow-soft);
    }
    .pricing-card.featured {
      border-color: var(--accent);
      background: linear-gradient(180deg, rgba(155, 27, 58, 0.06) 0%, var(--charcoal) 60%);
      box-shadow: 0 0 60px rgba(155, 27, 58, 0.08), var(--shadow-card);
    }
    body.dark-mode .pricing-card.featured {
      background: linear-gradient(180deg, rgba(232, 74, 111, 0.06) 0%, var(--charcoal) 60%);
    }
    .pricing-card.featured:hover { transform: translateY(-6px) scale(1.01); }
    .pricing-badge {
      position: absolute;
      top: -1px; right: 1.5rem;
      background: var(--accent);
      color: var(--accent-ink);
      font-size: 0.6rem;
      font-weight: 700;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      padding: 0.35rem 1rem;
      border-radius: 0 0 8px 8px;
    }
    .pricing-name {
      font-family: var(--font-head);
      font-size: 1.3rem;
      color: var(--grey);
      letter-spacing: 0.1em;
      margin-bottom: 0.5rem;
    }
    .pricing-price {
      font-family: var(--font-head);
      font-size: clamp(2.5rem, 4vw, 3.5rem);
      font-weight: 900;
      color: var(--white);
      line-height: 1;
    }
    .pricing-price span { font-size: 0.35em; color: var(--grey); font-weight: 400; }
    .pricing-features {
      list-style: none;
      padding: 0;
      margin: 1.5rem 0;
      flex: 1;
    }
    .pricing-features li {
      font-size: 0.85rem;
      color: var(--grey);
      padding: 0.55rem 0;
      border-bottom: 1px solid var(--charcoal-3);
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }
    .pricing-features li i { color: var(--accent); font-size: 0.75rem; }
    .pricing-features li.disabled { opacity: 0.45; }
    .pricing-features li.disabled i { color: var(--grey-dark); }

    /* ============================================================
       PROGRAMS
       ============================================================ */
    .program-card {
      position: relative;
      border-radius: var(--radius);
      overflow: hidden;
      aspect-ratio: 3/4;
      cursor: pointer;
      box-shadow: var(--shadow-card);
    }
    .program-card img {
      width: 100%; height: 100%;
      object-fit: cover;
      transition: transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    .program-card:hover img { transform: scale(1.1); }
    .program-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(13, 10, 11, 0.92) 0%, rgba(13, 10, 11, 0.2) 50%, transparent 100%);
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 1.5rem;
      transition: background 0.4s;
    }
    .program-card:hover .program-overlay { background: linear-gradient(to top, rgba(13, 10, 11, 0.95) 0%, rgba(13, 10, 11, 0.5) 50%, rgba(13, 10, 11, 0.15) 100%); }
    .program-name {
      font-family: var(--font-head);
      font-size: clamp(1.2rem, 2vw, 1.6rem);
      color: #fff;
      font-weight: 800;
      letter-spacing: 0.04em;
    }
    .program-desc {
      font-size: 0.75rem;
      color: rgba(255, 255, 255, 0.7);
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.5s ease, margin 0.4s ease;
      line-height: 1.5;
    }
    .program-card:hover .program-desc { max-height: 80px; margin-top: 0.5rem; }
    .program-arrow {
      position: absolute;
      top: 1rem; right: 1rem;
      width: 36px; height: 36px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 0.9rem;
      opacity: 0;
      transform: translateY(-4px);
      transition: opacity 0.3s, transform 0.3s;
    }
    .program-card:hover .program-arrow { opacity: 1; transform: translateY(0); }

    /* ============================================================
       TRAINERS
       ============================================================ */
    .trainer-card {
      position: relative;
      border-radius: var(--radius);
      overflow: hidden;
      aspect-ratio: 3/4;
      box-shadow: var(--shadow-card);
    }
    .trainer-card img {
      width: 100%; height: 100%;
      object-fit: cover;
      transition: transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    .trainer-card:hover img { transform: scale(1.06); }
    .trainer-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(13, 10, 11, 0.92) 0%, transparent 55%);
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 1.5rem;
    }
    .trainer-name {
      font-family: var(--font-head);
      font-size: 1.3rem;
      font-weight: 800;
      color: #fff;
      letter-spacing: 0.03em;
    }
    .trainer-specialty {
      font-size: 0.7rem;
      color: var(--accent);
      letter-spacing: 0.12em;
      text-transform: uppercase;
      font-weight: 600;
      margin-bottom: 0.4rem;
    }
    body.dark-mode .trainer-specialty { color: var(--accent); }
    .trainer-socials {
      display: flex;
      gap: 0.5rem;
      margin-top: 0.75rem;
      opacity: 0;
      transform: translateY(8px);
      transition: opacity 0.4s, transform 0.4s;
    }
    .trainer-card:hover .trainer-socials { opacity: 1; transform: translateY(0); }
    .trainer-socials a {
      width: 32px; height: 32px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(6px);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 0.75rem;
      transition: background 0.3s, color 0.3s;
    }
    .trainer-socials a:hover { background: var(--accent); color: var(--accent-ink); }

    /* ============================================================
       FACILITIES
       ============================================================ */
    .facility-item {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      padding: 1.25rem;
      background: var(--charcoal-2);
      border: 1px solid var(--charcoal-3);
      border-radius: var(--radius-sm);
      transition: border-color 0.3s, background 0.3s;
      box-shadow: var(--shadow-card);
    }
    .facility-item:hover { border-color: rgba(155, 27, 58, 0.35); }
    .facility-icon {
      width: 44px; height: 44px;
      border-radius: 50%;
      background: var(--accent-glow);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-dim);
      font-size: 1.1rem;
      flex-shrink: 0;
    }
    body.dark-mode .facility-icon { color: var(--accent); }
    .facility-title {
      font-family: var(--font-head);
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--white);
      letter-spacing: 0.04em;
    }
    .facility-desc {
      font-size: 0.8rem;
      color: var(--grey);
      line-height: 1.5;
      margin-top: 0.2rem;
    }

    /* ============================================================
       TRANSFORMATION GALLERY
       ============================================================ */
    .transformation-card {
      border-radius: var(--radius);
      overflow: hidden;
      background: var(--charcoal);
      border: 1px solid var(--charcoal-3);
      transition: border-color 0.3s, box-shadow 0.3s;
      box-shadow: var(--shadow-card);
    }
    .transformation-card:hover { border-color: rgba(155, 27, 58, 0.35); box-shadow: var(--shadow-soft); }
    .transformation-img-wrap {
      position: relative;
      aspect-ratio: 4/5;
      overflow: hidden;
    }
    .transformation-img-wrap img {
      width: 100%; height: 100%;
      object-fit: cover;
      transition: transform 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    .transformation-card:hover .transformation-img-wrap img { transform: scale(1.05); }
    .transformation-label {
      position: absolute;
      top: 1rem; left: 1rem;
      background: var(--accent);
      color: var(--accent-ink);
      font-size: 0.6rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding: 0.3rem 0.8rem;
      border-radius: 100px;
    }
    .transformation-info { padding: 1.2rem; }
    .transformation-name { font-family: var(--font-head); font-size: 1.1rem; font-weight: 700; color: var(--white); }
    .transformation-result { font-size: 0.75rem; color: var(--grey); margin-top: 0.2rem; }
    .transformation-stat { font-size: 0.7rem; color: var(--accent-dim); font-weight: 600; margin-top: 0.3rem; }
    body.dark-mode .transformation-stat { color: var(--accent); }

    /* ============================================================
       TESTIMONIALS
       ============================================================ */
    .testimonial-card {
      background: var(--charcoal);
      border: 1px solid var(--charcoal-3);
      border-radius: var(--radius);
      padding: 2rem;
      height: 100%;
      transition: border-color 0.3s, box-shadow 0.3s;
      box-shadow: var(--shadow-card);
    }
    .testimonial-card:hover { border-color: rgba(155, 27, 58, 0.35); box-shadow: var(--shadow-soft); }
    .testimonial-stars { color: var(--accent-dim); font-size: 0.8rem; margin-bottom: 1rem; letter-spacing: 2px; }
    body.dark-mode .testimonial-stars { color: var(--accent); }
    .testimonial-text {
      font-size: 0.9rem;
      color: var(--grey);
      line-height: 1.7;
      font-style: italic;
      margin-bottom: 1.25rem;
    }
    .testimonial-author { display: flex; align-items: center; gap: 0.75rem; }
    .testimonial-avatar {
      width: 44px; height: 44px;
      border-radius: 50%;
      object-fit: cover;
    }
    .testimonial-name { font-weight: 600; font-size: 0.85rem; color: var(--white); }
    .testimonial-role { font-size: 0.7rem; color: var(--grey-dark); }

    /* ============================================================
       SCHEDULE
       ============================================================ */
    .schedule-table-wrap {
      background: var(--charcoal);
      border: 1px solid var(--charcoal-3);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow-card);
    }
    .schedule-table { width: 100%; border-collapse: collapse; }
    .schedule-table th {
      font-family: var(--font-head);
      font-size: 0.75rem;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--accent-dim);
      background: var(--accent-glow);
      padding: 1rem 0.75rem;
      text-align: left;
      font-weight: 700;
      border-bottom: 1px solid var(--charcoal-3);
    }
    body.dark-mode .schedule-table th { color: var(--accent); }
    .schedule-table td {
      padding: 0.85rem 0.75rem;
      font-size: 0.82rem;
      color: var(--grey);
      border-bottom: 1px solid var(--charcoal-3);
    }
    .schedule-table tr:last-child td { border-bottom: none; }
    .schedule-table tr:hover td { background: var(--accent-glow); color: var(--white); }
    .schedule-class { font-weight: 600; color: var(--white); }
    .schedule-time { font-size: 0.75rem; color: var(--accent-dim); font-weight: 600; }
    body.dark-mode .schedule-time { color: var(--accent); }

    /* ============================================================
       JOURNEY / HOW TO JOIN
       ============================================================ */
    .journey-step {
      text-align: center;
      padding: 2rem 1.5rem;
      position: relative;
    }
    .journey-number {
      font-family: var(--font-head);
      font-size: 4rem;
      font-weight: 900;
      color: var(--accent-glow);
      line-height: 1;
      margin-bottom: 0.5rem;
    }
    .journey-title {
      font-family: var(--font-head);
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--white);
      margin-bottom: 0.5rem;
      letter-spacing: 0.04em;
    }
    .journey-desc { font-size: 0.82rem; color: var(--grey); line-height: 1.6; max-width: 240px; margin: 0 auto; }

    /* ============================================================
       FAQ
       ============================================================ */
    .accordion-elite .accordion-item {
      background: var(--charcoal);
      border: 1px solid var(--charcoal-3);
      border-radius: var(--radius-sm) !important;
      margin-bottom: 0.75rem;
      overflow: hidden;
      box-shadow: var(--shadow-card);
    }
    .accordion-elite .accordion-button {
      background: transparent;
      color: var(--white);
      font-family: var(--font-body);
      font-weight: 600;
      font-size: 0.9rem;
      padding: 1.25rem 1.5rem;
      border: none;
      box-shadow: none;
    }
    .accordion-elite .accordion-button:not(.collapsed) { color: var(--accent-dim); background: var(--accent-glow); }
    body.dark-mode .accordion-elite .accordion-button:not(.collapsed) { color: var(--accent); }
    .accordion-elite .accordion-button::after { filter: brightness(0) saturate(100%) invert(18%) sepia(60%) saturate(3000%) hue-rotate(330deg); }
    .accordion-elite .accordion-body {
      color: var(--grey);
      font-size: 0.85rem;
      line-height: 1.7;
      padding: 0 1.5rem 1.25rem;
    }

    /* ============================================================
       CONTACT
       ============================================================ */
    .contact-section { background: var(--charcoal-2); }
    .contact-form .form-control {
      background: var(--charcoal);
      border: 1px solid var(--charcoal-3);
      border-radius: var(--radius-sm);
      color: var(--white);
      font-size: 0.85rem;
      padding: 0.85rem 1rem;
      transition: border-color 0.3s, box-shadow 0.3s;
    }
    .contact-form .form-control:focus {
      background: var(--charcoal);
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-glow);
      color: var(--white);
    }
    .contact-form .form-control::placeholder { color: var(--grey-dark); }
    .contact-form select.form-control { color: var(--grey); }
    .contact-form select.form-control option { background: var(--charcoal); color: var(--white); }
    .form-label { font-size: 0.7rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--grey); margin-bottom: 0.4rem; }
    .form-error { font-size: 0.7rem; color: #d63b3b; margin-top: 0.25rem; display: none; }
    .form-control.is-invalid { border-color: #d63b3b; }

    .contact-info-item { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
    .contact-info-icon {
      width: 44px; height: 44px;
      border-radius: 50%;
      background: var(--accent-glow);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--accent-dim);
      font-size: 1rem;
      flex-shrink: 0;
    }
    body.dark-mode .contact-info-icon { color: var(--accent); }
    .contact-info-label { font-size: 0.65rem; letter-spacing: 0.15em; text-transform: uppercase; color: var(--grey); font-weight: 600; }
    .contact-info-value { font-size: 0.9rem; color: var(--white); margin-top: 0.15rem; }
    .contact-info-value a { color: var(--white); }
    .contact-info-value a:hover { color: var(--accent); }

    .map-embed {
      border-radius: var(--radius);
      overflow: hidden;
      border: 1px solid var(--charcoal-3);
      height: 220px;
      box-shadow: var(--shadow-card);
    }
    .map-embed iframe { width: 100%; height: 100%; border: 0; filter: grayscale(0.2) contrast(1.02); }

    .whatsapp-float {
      position: fixed;
      bottom: 1.5rem; right: 1.5rem;
      width: 56px; height: 56px;
      border-radius: 50%;
      background: #25D366;
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      z-index: 9998;
      box-shadow: 0 4px 24px rgba(37, 211, 102, 0.35);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .whatsapp-float:hover { transform: scale(1.08); box-shadow: 0 6px 32px rgba(37, 211, 102, 0.5); color: #fff; }

    /* ============================================================
       MARQUEE
       ============================================================ */
    .marquee-section {
      overflow: hidden;
      padding: 2rem 0;
      background: var(--charcoal);
      border-top: 1px solid var(--charcoal-3);
      border-bottom: 1px solid var(--charcoal-3);
    }
    .marquee-track {
      display: flex;
      gap: 3rem;
      animation: marqueeScroll 30s linear infinite;
      width: max-content;
    }
    .marquee-item {
      font-family: var(--font-head);
      font-size: clamp(1.5rem, 3vw, 2.5rem);
      font-weight: 900;
      color: var(--charcoal-3);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      white-space: nowrap;
      display: flex;
      align-items: center;
      gap: 3rem;
      transition: color 0.3s;
    }
    .marquee-item:hover { color: var(--accent-glow); }
    .marquee-item::after {
      content: '✦';
      font-size: 0.5em;
      color: var(--accent);
    }
    @keyframes marqueeScroll {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }

    /* ============================================================
       FOOTER
       ============================================================ */
    .footer-elite {
      background: var(--charcoal);
      border-top: 1px solid var(--charcoal-3);
      padding: clamp(3rem, 6vw, 5rem) clamp(1.2rem, 4vw, 3rem) 2rem;
    }
    .footer-brand {
      font-family: var(--font-head);
      font-size: 1.5rem;
      font-weight: 900;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--white);
    }
    .footer-brand span { color: var(--accent); }
    .footer-desc { font-size: 0.82rem; color: var(--grey); line-height: 1.7; max-width: 300px; margin-top: 1rem; }
    .footer-heading {
      font-family: var(--font-head);
      font-size: 0.85rem;
      font-weight: 700;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--white);
      margin-bottom: 1rem;
    }
    .footer-links { list-style: none; padding: 0; }
    .footer-links li { margin-bottom: 0.5rem; }
    .footer-links a { font-size: 0.82rem; color: var(--grey); transition: color 0.3s; }
    .footer-links a:hover { color: var(--accent); }
    .footer-social {
      display: flex;
      gap: 0.6rem;
      margin-top: 1.25rem;
    }
    .footer-social a {
      width: 38px; height: 38px;
      border-radius: 50%;
      border: 1px solid var(--charcoal-3);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--grey);
      font-size: 0.85rem;
      transition: border-color 0.3s, color 0.3s, background 0.3s;
    }
    .footer-social a:hover { border-color: var(--accent); color: var(--accent-dim); background: var(--accent-glow); }
    .footer-bottom {
      border-top: 1px solid var(--charcoal-3);
      margin-top: 3rem;
      padding-top: 1.5rem;
      font-size: 0.72rem;
      color: var(--grey-dark);
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 0.5rem;
    }

    /* ============================================================
       RESPONSIVE TWEAKS
       ============================================================ */
    @media (max-width: 575px) {
      .hero-ctas { flex-direction: column; }
      .hero-ctas .btn-accent, .hero-ctas .btn-ghost { width: 100%; }
      .schedule-table th, .schedule-table td { padding: 0.6rem 0.4rem; font-size: 0.7rem; }
      .schedule-table .schedule-class { font-size: 0.72rem; }
      .whatsapp-float { bottom: 1rem; right: 1rem; width: 48px; height: 48px; font-size: 1.3rem; }
    }

    /* Reduced motion */
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
      }
      html { scroll-behavior: auto; }
    }
  </style>
</head>
<body>

  <!-- ============================================================
       CUSTOM CURSOR
       ============================================================ -->
  <div class="cursor-dot" id="cursorDot"></div>
  <div class="cursor-ring" id="cursorRing"></div>

  

  <!-- ============================================================
       NAVIGATION
       ============================================================ -->
  <nav class="navbar-elite" id="mainNav">
    <a href="#home" class="nav-brand">IRON <span>ELITE</span></a>

    <ul class="nav-links-desktop">
      <li><a href="#about">About</a></li>
      <li><a href="#pricing">Membership</a></li>
      <li><a href="#programs">Programs</a></li>
      <li><a href="#trainers">Trainers</a></li>
      <li><a href="#facilities">Facilities</a></li>
      <li><a href="#schedule">Schedule</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>

    <div class="nav-actions">
      <button class="btn-theme-toggle" id="themeToggle" aria-label="Toggle theme">
        <i class="bi bi-moon-stars" id="themeIcon"></i>
      </button>
      <button class="btn-hamburger" id="hamburgerBtn" aria-label="Open menu">
        <i class="bi bi-list"></i>
      </button>
    </div>
  </nav>

  <!-- Mobile Menu -->
  <div class="mobile-menu" id="mobileMenu">
    <button class="close-btn" id="closeMenuBtn" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
    <a href="#home" class="mobile-link">Home</a>
    <a href="#about" class="mobile-link">About</a>
    <a href="#pricing" class="mobile-link">Membership</a>
    <a href="#programs" class="mobile-link">Programs</a>
    <a href="#trainers" class="mobile-link">Trainers</a>
    <a href="#facilities" class="mobile-link">Facilities</a>
    <a href="#schedule" class="mobile-link">Schedule</a>
    <a href="#contact" class="mobile-link">Contact</a>
  </div>

    <!-- ============================================================
       HERO — powered by hero_section table
       ============================================================ -->
  <?php if (!empty($hero)): ?>
  <section class="hero-section" id="home">
    <?php if (!empty($hero['background_video'])): ?>
      <video class="hero-bg" id="heroBg" autoplay muted loop playsinline
             <?php if (!empty($hero['background_image'])): ?>
               poster="<?= e($hero['background_image']) ?>"
             <?php endif; ?>
             style="object-fit: cover; width:100%; height:100%;">
        <source src="<?= e($hero['background_video']) ?>" type="video/mp4">
      </video>
    <?php elseif (!empty($hero['background_image'])): ?>
      <div class="hero-bg" id="heroBg"
           style="background-image: url('<?= e($hero['background_image']) ?>');"></div>
    <?php else: ?>
      <div class="hero-bg" id="heroBg"></div>
    <?php endif; ?>

    <div class="hero-overlay"></div>

    <div class="hero-content">
      <?php if (!empty($hero['eyebrow_text'])): ?>
        <div class="hero-eyebrow"><?= e($hero['eyebrow_text']) ?></div>
      <?php endif; ?>

      <h1 class="hero-title" id="heroTitle">
        <?php if (!empty($hero['title_line_1'])): ?>
          <span class="line"><span><?= e($hero['title_line_1']) ?></span></span>
        <?php endif; ?>
        <?php if (!empty($hero['title_line_2'])): ?>
          <span class="line">
            <span class="<?= $hero['title_line_2_accent'] ? 'accent' : '' ?>"><?= e($hero['title_line_2']) ?></span>
          </span>
        <?php endif; ?>
        <?php if (!empty($hero['title_line_3'])): ?>
          <span class="line"><span><?= e($hero['title_line_3']) ?></span></span>
        <?php endif; ?>
        <?php if (!empty($hero['title_line_4'])): ?>
          <span class="line"><span><?= e($hero['title_line_4']) ?></span></span>
        <?php endif; ?>
      </h1>

      <?php if (!empty($hero['description'])): ?>
        <p class="hero-desc"><?= e($hero['description']) ?></p>
      <?php endif; ?>

      <div class="hero-ctas">
        <?php if (!empty($hero['cta_primary_text'])): ?>
          <a href="<?= e($hero['cta_primary_link']) ?>" class="btn-accent magnetic" data-cursor="cta">
            <?= e($hero['cta_primary_text']) ?>
            <?php if (!empty($hero['cta_primary_icon'])): ?>
              <i class="<?= e($hero['cta_primary_icon']) ?>"></i>
            <?php endif; ?>
          </a>
        <?php endif; ?>
        <?php if (!empty($hero['cta_secondary_text'])): ?>
          <a href="<?= e($hero['cta_secondary_link']) ?>" class="btn-ghost magnetic" data-cursor="link">
            <?= e($hero['cta_secondary_text']) ?>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="hero-scroll-indicator">
      <span><?= e($hero['scroll_text'] ?? 'Scroll') ?></span>
      <div class="scroll-line"></div>
    </div>
  </section>
  <?php endif; ?>

    <!-- ============================================================
       MARQUEE — powered by marquee_items table
       ============================================================ -->
  <?php if (!empty($marquee_items)): ?>
  <div class="marquee-section">
    <div class="marquee-track" id="marqueeTrack">
      <?php
        // Duplicate the list so the CSS -50% loop is seamless
        for ($pass = 0; $pass < 2; $pass++):
          foreach ($marquee_items as $item):
      ?>
        <span class="marquee-item"><?= e($item) ?></span>
      <?php
          endforeach;
        endfor;
      ?>
    </div>
  </div>
  <?php endif; ?>

    <!-- ============================================================
       ABOUT — powered by about_section table
       ============================================================ -->
  <?php if (!empty($about)): ?>
  <section class="section-pad about-section" id="about">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <div class="about-img-wrap" data-reveal>
            <?php if (!empty($about['image'])): ?>
              <img src="<?= e($about['image']) ?>"
                   alt="<?= e($about['title_line_1'] . ' ' . $about['title_line_2']) ?>"
                   loading="lazy">
            <?php else: ?>
              <img src="https://images.unsplash.com/photo-1571902943202-507ec2618e8f?q=80&w=1975&auto=format&fit=crop"
                   alt="Athlete training with battle ropes in luxury gym" loading="lazy">
            <?php endif; ?>
            <div class="about-img-accent"></div>
          </div>
        </div>

        <div class="col-lg-6">
          <?php if (!empty($about['label'])): ?>
            <div class="section-label" data-reveal><?= e($about['label']) ?></div>
          <?php endif; ?>

          <h2 class="section-title" data-reveal>
            <?= e($about['title_line_1']) ?><?php if (!empty($about['title_line_2'])): ?><br><?= e($about['title_line_2']) ?><?php endif; ?>
          </h2>

          <?php if (!empty($about['para_1'])): ?>
            <p class="section-sub mb-4" data-reveal><?= e($about['para_1']) ?></p>
          <?php endif; ?>

          <?php if (!empty($about['para_2'])): ?>
            <p style="color: var(--grey); font-size: 0.9rem; line-height: 1.7;" data-reveal>
              <?= e($about['para_2']) ?>
            </p>
          <?php endif; ?>

          <div class="row g-4 mt-4" data-reveal>
            <div class="col-4">
              <div class="stat-item">
                <div class="stat-number"><span class="counter" data-target="2400">0</span><span class="stat-suffix">+</span></div>
                <div class="stat-label">Members</div>
              </div>
            </div>
            <div class="col-4">
              <div class="stat-item">
                <div class="stat-number"><span class="counter" data-target="35">0</span><span class="stat-suffix">+</span></div>
                <div class="stat-label">Coaches</div>
              </div>
            </div>
            <div class="col-4">
              <div class="stat-item">
                <div class="stat-number"><span class="counter" data-target="9">0</span></div>
                <div class="stat-label">Years</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

    <!-- ============================================================
       PRICING — powered by pricing_section, pricing_plans, pricing_features
       ============================================================ -->
  <?php if (!empty($pricing_section) && !empty($pricing_plans)): ?>
  <section class="section-pad" id="pricing">
    <div class="container">
      <div class="text-center mb-5">
        <?php if (!empty($pricing_section['label'])): ?>
          <div class="section-label justify-content-center" data-reveal><?= e($pricing_section['label']) ?></div>
        <?php endif; ?>
        <?php if (!empty($pricing_section['title'])): ?>
          <h2 class="section-title" data-reveal><?= e($pricing_section['title']) ?></h2>
        <?php endif; ?>
        <?php if (!empty($pricing_section['subtitle'])): ?>
          <p class="section-sub mx-auto" data-reveal><?= e($pricing_section['subtitle']) ?></p>
        <?php endif; ?>
      </div>

      <div class="row g-4 align-items-stretch">
        <?php foreach ($pricing_plans as $plan): ?>
          <?php
            $isFeatured  = !empty($plan['is_featured']);
            $btnStyle    = $plan['button_style'] === 'accent' ? 'btn-accent' : 'btn-ghost';
            $btnExtra    = $plan['button_style'] === 'accent' ? 'magnetic' : '';
            $planNameEsc = e($plan['name']);
            // Center the last card if row has an odd count on md+
            $colClass = ($isFeatured || count($pricing_plans) === 3) ? '' : 'mx-md-auto';
          ?>
          <div class="col-lg-4 col-md-6 <?= $colClass ?>" data-reveal>
            <div class="pricing-card <?= $isFeatured ? 'featured' : '' ?>"
                 data-plan="<?= $planNameEsc ?>">

              <?php if ($isFeatured && !empty($plan['badge_text'])): ?>
                <div class="pricing-badge"><?= e($plan['badge_text']) ?></div>
              <?php endif; ?>

              <div class="pricing-name"><?= $planNameEsc ?></div>

              <div class="pricing-price">
                <?= e($plan['currency'] ?? '$') ?><?= e($plan['price']) ?><span><?= e($plan['period'] ?? '/mo') ?></span>
              </div>

              <ul class="pricing-features">
                <?php foreach ($plan['features'] as $f): ?>
                  <?php $included = (int)$f['is_included'] === 1; ?>
                  <li class="<?= $included ? '' : 'disabled' ?>">
                    <i class="bi <?= $included ? 'bi-check-lg' : 'bi-dash' ?>"></i>
                    <?= e($f['feature_text']) ?>
                  </li>
                <?php endforeach; ?>
              </ul>

              <button class="<?= $btnStyle ?> <?= $btnExtra ?> plan-select w-100"
                      data-plan="<?= $planNameEsc ?>"
                      <?php if ($btnStyle === 'btn-accent'): ?>data-cursor="cta"<?php endif; ?>>
                <?= e($plan['button_text'] ?: 'Select ' . $plan['name']) ?>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

    <!-- ============================================================
       PROGRAMS — powered by programs_section, programs_items
       ============================================================ -->
  <?php if (!empty($programs_section) && !empty($programs_items)): ?>
  <section class="section-pad" id="programs" style="background: var(--charcoal-2);">
    <div class="container">
      <div class="mb-5">
        <?php if (!empty($programs_section['label'])): ?>
          <div class="section-label" data-reveal><?= e($programs_section['label']) ?></div>
        <?php endif; ?>
        <?php if (!empty($programs_section['title'])): ?>
          <h2 class="section-title" data-reveal><?= e($programs_section['title']) ?></h2>
        <?php endif; ?>
        <?php if (!empty($programs_section['subtitle'])): ?>
          <p class="section-sub" data-reveal><?= e($programs_section['subtitle']) ?></p>
        <?php endif; ?>
      </div>

      <div class="row g-4">
        <?php foreach ($programs_items as $prog): ?>
          <div class="col-lg-3 col-md-6 col-6" data-reveal>
            <div class="program-card">
              <?php if (!empty($prog['image'])): ?>
                <img src="<?= e($prog['image']) ?>"
                     alt="<?= e($prog['name']) ?>"
                     loading="lazy">
              <?php endif; ?>
              <div class="program-arrow"><i class="bi bi-arrow-up-right"></i></div>
              <div class="program-overlay">
                <div class="program-name"><?= e($prog['name']) ?></div>
                <?php if (!empty($prog['description'])): ?>
                  <div class="program-desc"><?= e($prog['description']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

    <!-- ============================================================
       TRAINERS — powered by trainers_section, trainers_items
       ============================================================ -->
  <?php if (!empty($trainers_section) && !empty($trainers_items)): ?>
  <section class="section-pad" id="trainers">
    <div class="container">
      <div class="mb-5">
        <?php if (!empty($trainers_section['label'])): ?>
          <div class="section-label" data-reveal><?= e($trainers_section['label']) ?></div>
        <?php endif; ?>
        <?php if (!empty($trainers_section['title'])): ?>
          <h2 class="section-title" data-reveal><?= e($trainers_section['title']) ?></h2>
        <?php endif; ?>
        <?php if (!empty($trainers_section['subtitle'])): ?>
          <p class="section-sub" data-reveal><?= e($trainers_section['subtitle']) ?></p>
        <?php endif; ?>
      </div>

      <div class="row g-4">
        <?php foreach ($trainers_items as $tr): ?>
          <div class="col-lg-3 col-md-6 col-6" data-reveal>
            <div class="trainer-card">
              <?php if (!empty($tr['image'])): ?>
                <img src="<?= e($tr['image']) ?>"
                     alt="Trainer <?= e($tr['name']) ?>"
                     loading="lazy">
              <?php endif; ?>
              <div class="trainer-overlay">
                <?php if (!empty($tr['specialty'])): ?>
                  <div class="trainer-specialty"><?= e($tr['specialty']) ?></div>
                <?php endif; ?>
                <div class="trainer-name"><?= e($tr['name']) ?></div>
                <?php if (!empty($tr['instagram_url']) || !empty($tr['twitter_url'])): ?>
                  <div class="trainer-socials">
                    <?php if (!empty($tr['instagram_url'])): ?>
                      <a href="<?= e($tr['instagram_url']) ?>" aria-label="Instagram" target="_blank" rel="noopener">
                        <i class="bi bi-instagram"></i>
                      </a>
                    <?php endif; ?>
                    <?php if (!empty($tr['twitter_url'])): ?>
                      <a href="<?= e($tr['twitter_url']) ?>" aria-label="Twitter" target="_blank" rel="noopener">
                        <i class="bi bi-twitter-x"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

    <!-- ============================================================
       FACILITIES — powered by facilities_section, facilities_items
       ============================================================ -->
  <?php if (!empty($facilities_section)): ?>
  <section class="section-pad" id="facilities" style="background: var(--charcoal-2);">
    <div class="container">
      <div class="row g-5 align-items-center">
        <div class="col-lg-5">
          <?php if (!empty($facilities_section['label'])): ?>
            <div class="section-label" data-reveal><?= e($facilities_section['label']) ?></div>
          <?php endif; ?>

          <h2 class="section-title" data-reveal>
            <?= e($facilities_section['title_line_1']) ?><?php if (!empty($facilities_section['title_line_2'])): ?><br><?= e($facilities_section['title_line_2']) ?><?php endif; ?>
          </h2>

          <?php if (!empty($facilities_section['subtitle'])): ?>
            <p class="section-sub mb-4" data-reveal><?= e($facilities_section['subtitle']) ?></p>
          <?php endif; ?>

          <?php if (!empty($facilities_items)): ?>
          <div class="row g-3" data-reveal>
            <?php foreach ($facilities_items as $f): ?>
              <div class="col-12">
                <div class="facility-item">
                  <?php if (!empty($f['icon'])): ?>
                    <div class="facility-icon"><i class="<?= e($f['icon']) ?>"></i></div>
                  <?php endif; ?>
                  <div>
                    <div class="facility-title"><?= e($f['title']) ?></div>
                    <?php if (!empty($f['description'])): ?>
                      <div class="facility-desc"><?= e($f['description']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <div class="col-lg-7">
          <div class="row g-3" data-reveal>
            <div class="col-7">
              <div style="border-radius: var(--radius); overflow: hidden; aspect-ratio: 3/4; box-shadow: var(--shadow-soft);">
                <?php if (!empty($facilities_section['image_main'])): ?>
                  <img src="<?= e($facilities_section['image_main']) ?>"
                       alt="Gym interior with premium equipment"
                       style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                <?php endif; ?>
              </div>
            </div>
            <div class="col-5 d-flex flex-column gap-3">
              <div style="border-radius: var(--radius); overflow: hidden; aspect-ratio: 1; flex:1; box-shadow: var(--shadow-card);">
                <?php if (!empty($facilities_section['image_small_1'])): ?>
                  <img src="<?= e($facilities_section['image_small_1']) ?>"
                       alt="Gym equipment detail"
                       style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                <?php endif; ?>
              </div>
              <div style="border-radius: var(--radius); overflow: hidden; aspect-ratio: 1; flex:1; box-shadow: var(--shadow-card);">
                <?php if (!empty($facilities_section['image_small_2'])): ?>
                  <img src="<?= e($facilities_section['image_small_2']) ?>"
                       alt="Recovery lounge"
                       style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

    <!-- ============================================================
       TESTIMONIALS — powered by testimonials_section, testimonials_items
       ============================================================ -->
  <?php if (!empty($testimonials_section) && !empty($testimonials_items)): ?>
  <section class="section-pad" id="testimonials" style="background: var(--charcoal-2);">
    <div class="container">
      <div class="text-center mb-5">
        <?php if (!empty($testimonials_section['label'])): ?>
          <div class="section-label justify-content-center" data-reveal><?= e($testimonials_section['label']) ?></div>
        <?php endif; ?>
        <?php if (!empty($testimonials_section['title'])): ?>
          <h2 class="section-title" data-reveal><?= e($testimonials_section['title']) ?></h2>
        <?php endif; ?>
        <?php if (!empty($testimonials_section['subtitle'])): ?>
          <p class="section-sub mx-auto" data-reveal><?= e($testimonials_section['subtitle']) ?></p>
        <?php endif; ?>
      </div>

      <div class="row g-4">
        <?php
          $tCount = count($testimonials_items);
          foreach ($testimonials_items as $i => $t):
            // Center the last single card on md+ if it's the trailing odd one
            $isLastOdd = ($i === $tCount - 1) && ($tCount % 3 === 1);
            $colClass  = $isLastOdd ? 'mx-md-auto' : '';
            $stars     = max(0, min(5, (int)($t['stars'] ?? 5)));
        ?>
          <div class="col-lg-4 col-md-6 <?= $colClass ?>" data-reveal>
            <div class="testimonial-card">
              <div class="testimonial-stars"><?= str_repeat('★', $stars) ?></div>
              <?php if (!empty($t['quote'])): ?>
                <p class="testimonial-text">“<?= e($t['quote']) ?>”</p>
              <?php endif; ?>
              <div class="testimonial-author">
                <?php if (!empty($t['avatar'])): ?>
                  <img src="<?= e($t['avatar']) ?>"
                       alt="<?= e($t['name']) ?>"
                       class="testimonial-avatar" loading="lazy">
                <?php endif; ?>
                <div>
                  <div class="testimonial-name"><?= e($t['name']) ?></div>
                  <?php if (!empty($t['role'])): ?>
                    <div class="testimonial-role"><?= e($t['role']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  
    <!-- ============================================================
       SCHEDULE — powered by schedule_section, schedule_times, schedule_cells
       ============================================================ -->
  <?php if (!empty($schedule_section) && !empty($schedule_times)): ?>
  <section class="section-pad" id="schedule">
    <div class="container">
      <div class="mb-5">
        <?php if (!empty($schedule_section['label'])): ?>
          <div class="section-label" data-reveal><?= e($schedule_section['label']) ?></div>
        <?php endif; ?>
        <?php if (!empty($schedule_section['title'])): ?>
          <h2 class="section-title" data-reveal><?= e($schedule_section['title']) ?></h2>
        <?php endif; ?>
        <?php if (!empty($schedule_section['subtitle'])): ?>
          <p class="section-sub" data-reveal><?= e($schedule_section['subtitle']) ?></p>
        <?php endif; ?>
      </div>

      <div class="schedule-table-wrap" data-reveal>
        <div class="table-responsive">
          <table class="schedule-table">
            <thead>
              <tr>
                <th>Time</th>
                <th>Monday</th>
                <th>Tuesday</th>
                <th>Wednesday</th>
                <th>Thursday</th>
                <th>Friday</th>
                <th>Saturday</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($schedule_times as $row): ?>
                <tr>
                  <td><span class="schedule-time"><?= e($row['time_label']) ?></span></td>
                  <?php for ($d = 1; $d <= 6; $d++): ?>
                    <?php
                      $className = $schedule_grid[$row['id']][$d] ?? '';
                      $label     = ($className !== '' && $className !== null) ? $className : '—';
                    ?>
                    <td><span class="schedule-class"><?= e($label) ?></span></td>
                  <?php endfor; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

    <!-- ============================================================
       JOURNEY — powered by journey_section, journey_steps
       ============================================================ -->
  <?php if (!empty($journey_section) && !empty($journey_steps)): ?>
  <section class="section-pad" id="journey" style="background: var(--charcoal-2);">
    <div class="container">
      <div class="text-center mb-5">
        <?php if (!empty($journey_section['label'])): ?>
          <div class="section-label justify-content-center" data-reveal><?= e($journey_section['label']) ?></div>
        <?php endif; ?>
        <?php if (!empty($journey_section['title'])): ?>
          <h2 class="section-title" data-reveal><?= e($journey_section['title']) ?></h2>
        <?php endif; ?>
        <?php if (!empty($journey_section['subtitle'])): ?>
          <p class="section-sub mx-auto" data-reveal><?= e($journey_section['subtitle']) ?></p>
        <?php endif; ?>
      </div>

      <div class="row g-4">
        <?php foreach ($journey_steps as $step): ?>
          <div class="col-lg-3 col-md-6" data-reveal>
            <div class="journey-step">
              <?php if (!empty($step['number'])): ?>
                <div class="journey-number"><?= e($step['number']) ?></div>
              <?php endif; ?>
              <div class="journey-title"><?= e($step['title']) ?></div>
              <?php if (!empty($step['description'])): ?>
                <div class="journey-desc"><?= e($step['description']) ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($journey_section['cta_text']) && !empty($journey_section['cta_link'])): ?>
        <div class="text-center mt-5" data-reveal>
          <a href="<?= e($journey_section['cta_link']) ?>" class="btn-accent magnetic" data-cursor="cta">
            <?= e($journey_section['cta_text']) ?>
            <?php if (!empty($journey_section['cta_icon'])): ?>
              <i class="<?= e($journey_section['cta_icon']) ?>"></i>
            <?php endif; ?>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

    <!-- ============================================================
       FAQ — powered by faq_section, faq_items
       ============================================================ -->
  <?php if (!empty($faq_section) && !empty($faq_items)): ?>
  <section class="section-pad" id="faq">
    <div class="container">
      <div class="row g-5">
        <div class="col-lg-4">
          <?php if (!empty($faq_section['label'])): ?>
            <div class="section-label" data-reveal><?= e($faq_section['label']) ?></div>
          <?php endif; ?>
          <?php if (!empty($faq_section['title'])): ?>
            <h2 class="section-title" data-reveal><?= faq_title_html($faq_section['title']) ?></h2>
          <?php endif; ?>
          <?php if (!empty($faq_section['subtitle'])): ?>
            <p class="section-sub" data-reveal><?= e($faq_section['subtitle']) ?></p>
          <?php endif; ?>
        </div>

        <div class="col-lg-8">
          <div class="accordion accordion-elite" id="faqAccordion" data-reveal>
            <?php foreach ($faq_items as $i => $item): ?>
              <?php
                $idx      = $i + 1;
                $target   = 'faq' . $idx;
                $isOpen   = ($i === 0); // first one open by default
                $btnClass = 'accordion-button' . ($isOpen ? '' : ' collapsed');
                $colClass = 'accordion-collapse collapse' . ($isOpen ? ' show' : '');
              ?>
              <div class="accordion-item">
                <h3 class="accordion-header">
                  <button class="<?= $btnClass ?>" type="button"
                          data-bs-toggle="collapse"
                          data-bs-target="#<?= $target ?>"
                          aria-expanded="<?= $isOpen ? 'true' : 'false' ?>">
                    <?= e($item['question']) ?>
                  </button>
                </h3>
                <div id="<?= $target ?>" class="<?= $colClass ?>" data-bs-parent="#faqAccordion">
                  <div class="accordion-body">
                    <?= e($item['answer'] ?? '') ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ============================================================
       CONTACT
       ============================================================ -->
  <section class="section-pad contact-section" id="contact">
    <div class="container">
      <div class="row g-5">
        <div class="col-lg-6">
          <div class="section-label" data-reveal>Get in Touch</div>
          <h2 class="section-title" data-reveal>Start the Conversation</h2>
          <p class="section-sub mb-4" data-reveal>Fill in the form and a membership advisor will reach out within 24 hours.</p>

          <form class="contact-form" id="contactForm" novalidate data-reveal>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="nameInput" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="nameInput" placeholder="Alex Morgan" required>
                <div class="form-error" id="nameError">Please enter your name.</div>
              </div>
              <div class="col-md-6">
                <label for="emailInput" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="emailInput" placeholder="alex@email.com" required>
                <div class="form-error" id="emailError">Please enter a valid email.</div>
              </div>
              <div class="col-md-6">
                <label for="phoneInput" class="form-label">Phone (optional)</label>
                <input type="tel" class="form-control" id="phoneInput" placeholder="+1 (555) 000-0000">
              </div>
              <div class="col-md-6">
                <label for="planSelect" class="form-label">Interested Plan</label>
                <select class="form-control" id="planSelect">
                  <option value="Basic">Basic — $49/mo</option>
                  <option value="Pro" selected>Pro — $89/mo</option>
                  <option value="Elite">Elite — $159/mo</option>
                  <option value="Not sure yet">Not sure yet</option>
                </select>
              </div>
              <div class="col-12">
                <label for="messageInput" class="form-label">Message</label>
                <textarea class="form-control" id="messageInput" rows="4" placeholder="Tell us about your goals..." required></textarea>
                <div class="form-error" id="messageError">Please tell us a little about your goals.</div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn-accent w-100 magnetic" data-cursor="cta">Send Enquiry <i class="bi bi-send"></i></button>
              </div>
            </div>
          </form>
          <div id="formSuccess" style="display:none; margin-top:1rem; padding:1rem; background:var(--accent-glow); border:1px solid var(--accent); border-radius:var(--radius-sm); color:var(--accent-dim); font-size:0.85rem;">
            <i class="bi bi-check-circle"></i> Thank you. Your enquiry has been sent. We'll be in touch within 24 hours.
          </div>
        </div>

        <div class="col-lg-6">
          <div class="contact-info-item" data-reveal>
            <div class="contact-info-icon"><i class="bi bi-geo-alt"></i></div>
            <div>
              <div class="contact-info-label">Address</div>
              <div class="contact-info-value">88 Ironworks Lane, District 9<br>London, EC2A 4NE</div>
            </div>
          </div>
          <div class="contact-info-item" data-reveal>
            <div class="contact-info-icon"><i class="bi bi-clock"></i></div>
            <div>
              <div class="contact-info-label">Opening Hours</div>
              <div class="contact-info-value">Mon–Fri: 5am – 11pm<br>Sat: 6am – 9pm · Sun: 7am – 8pm</div>
            </div>
          </div>
          <div class="contact-info-item" data-reveal>
            <div class="contact-info-icon"><i class="bi bi-telephone"></i></div>
            <div>
              <div class="contact-info-label">Phone & Email</div>
              <div class="contact-info-value">
                <a href="tel:+442071234567">+44 20 7123 4567</a><br>
                <a href="mailto:hello@ironelite.fit">hello@ironelite.fit</a>
              </div>
            </div>
          </div>

          <div class="map-embed" data-reveal>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2482.466266801642!2d-0.0811!3d51.5250!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x48761cb4f5e3e3e3%3A0x0!2sShoreditch%2C%20London!5e0!3m2!1sen!2suk!4v1600000000000!5m2!1sen!2suk" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Gym location map"></iframe>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================================
       FOOTER
       ============================================================ -->
  <footer class="footer-elite">
    <div class="container-fluid px-0">
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="footer-brand">IRON <span>ELITE</span></div>
          <p class="footer-desc">A performance sanctuary for those who refuse to settle. Est. 2016. London.</p>
          <div class="footer-social">
            <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
            <a href="#" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
            <a href="#" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
            <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
          </div>
        </div>
        <div class="col-lg-2 col-6">
          <div class="footer-heading">Explore</div>
          <ul class="footer-links">
            <li><a href="#about">About</a></li>
            <li><a href="#pricing">Membership</a></li>
            <li><a href="#programs">Programs</a></li>
            <li><a href="#trainers">Trainers</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-6">
          <div class="footer-heading">Support</div>
          <ul class="footer-links">
            <li><a href="#faq">FAQ</a></li>
            <li><a href="#contact">Contact</a></li>
            <li><a href="#schedule">Schedule</a></li>
            <li><a href="#">Privacy Policy</a></li>
          </ul>
        </div>
        <div class="col-lg-4">
          <div class="footer-heading">Stay in the Loop</div>
          <p class="footer-desc" style="margin-top:0; font-size:0.8rem;">Drop your email for training tips and early access to events.</p>
          <div class="d-flex gap-2 mt-3" style="max-width:360px;">
            <input type="email" class="form-control" placeholder="your@email.com" id="footerEmail" style="background:var(--charcoal-2); border:1px solid var(--charcoal-3); color:var(--white); font-size:0.8rem; border-radius:8px; padding:0.65rem 0.9rem;">
            <button class="btn-accent" style="padding:0.65rem 1.2rem; font-size:0.7rem;" onclick="document.getElementById('footerEmail').value=''; this.textContent='Subscribed ✓'; setTimeout(()=>this.textContent='Subscribe',2000)">Subscribe</button>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© 2025 IRON ELITE FITNESS. All rights reserved.</span>
        <span>Designed for the relentless.</span>
      </div>
    </div>
  </footer>

  <!-- WhatsApp Float -->
  <a href="https://wa.me/447123456789?text=Hi%20IRON%20ELITE%2C%20I%27d%20like%20to%20know%20more%20about%20membership." class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <i class="bi bi-whatsapp"></i>
  </a>

  <!-- ============================================================
       SCRIPTS
       ============================================================ -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

  <script>
    /* ============================================================
       GLOBAL STATE
       ============================================================ */
    const WHATSAPP_NUMBER = '447123456789'; // Configurable WhatsApp number

    /* ============================================================
       LOADING SCREEN
       ============================================================ */
    (function initLoader() {
      const loader = document.getElementById('loader');
      const barFill = document.getElementById('loaderBarFill');
      const percentEl = document.getElementById('loaderPercent');
      const logo = document.getElementById('loaderLogo');
      const track = document.getElementById('loaderBarTrack');

      gsap.to(logo, { opacity: 1, y: 0, duration: 0.8, delay: 0.2, ease: 'power3.out' });
      gsap.to(track, { opacity: 1, duration: 0.6, delay: 0.5 });
      gsap.to(percentEl, { opacity: 1, duration: 0.6, delay: 0.5 });

      let progress = 0;
      const interval = setInterval(() => {
        progress += Math.random() * 12 + 4;
        if (progress > 100) progress = 100;
        barFill.style.width = progress + '%';
        percentEl.textContent = Math.floor(progress) + '%';

        if (progress >= 100) {
          clearInterval(interval);
          setTimeout(() => {
            loader.classList.add('hidden');
            document.body.style.overflow = 'auto';
            startHeroAnimations();
          }, 400);
        }
      }, 120);
    })();

    /* ============================================================
       HERO ENTRANCE ANIMATIONS
       ============================================================ */
    function startHeroAnimations() {
      const tl = gsap.timeline({ defaults: { ease: 'power4.out' } });

      tl.from('.hero-eyebrow', { opacity: 0, y: 30, duration: 0.8 })
        .from('#heroTitle .line span', {
          y: '110%',
          duration: 1.1,
          stagger: 0.12
        }, '-=0.4')
        .from('.hero-desc', { opacity: 0, y: 30, duration: 0.8 }, '-=0.5')
        .from('.hero-ctas .btn-accent', { opacity: 0, y: 20, duration: 0.6 }, '-=0.4')
        .from('.hero-ctas .btn-ghost', { opacity: 0, y: 20, duration: 0.6 }, '-=0.5')
        .from('.hero-scroll-indicator', { opacity: 0, duration: 0.6 }, '-=0.3');
    }

    /* ============================================================
       CUSTOM CURSOR
       ============================================================ */
    (function initCursor() {
      if (window.innerWidth < 992) return;

      const dot = document.getElementById('cursorDot');
      const ring = document.getElementById('cursorRing');
      let mouseX = 0, mouseY = 0;
      let ringX = 0, ringY = 0;

      document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
        dot.style.left = mouseX + 'px';
        dot.style.top = mouseY + 'px';
      });

      function animateRing() {
        ringX += (mouseX - ringX) * 0.12;
        ringY += (mouseY - ringY) * 0.12;
        ring.style.left = ringX + 'px';
        ring.style.top = ringY + 'px';
        requestAnimationFrame(animateRing);
      }
      animateRing();

      document.querySelectorAll('a, button, .program-card, .trainer-card, .pricing-card').forEach(el => {
        el.addEventListener('mouseenter', () => {
          const isCta = el.classList.contains('btn-accent') || el.dataset.cursor === 'cta';
          ring.classList.add(isCta ? 'hover-cta' : 'hover-link');
        });
        el.addEventListener('mouseleave', () => {
          ring.classList.remove('hover-cta', 'hover-link');
        });
      });
    })();

    /* ============================================================
       NAVIGATION
       ============================================================ */
    (function initNav() {
      const nav = document.getElementById('mainNav');
      const hamburger = document.getElementById('hamburgerBtn');
      const mobileMenu = document.getElementById('mobileMenu');
      const closeBtn = document.getElementById('closeMenuBtn');
      const mobileLinks = document.querySelectorAll('.mobile-link');

      window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 50);
      });

      hamburger.addEventListener('click', () => {
        mobileMenu.classList.add('open');
        document.body.style.overflow = 'hidden';
      });

      function closeMenu() {
        mobileMenu.classList.remove('open');
        document.body.style.overflow = 'auto';
      }

      closeBtn.addEventListener('click', closeMenu);
      mobileLinks.forEach(link => link.addEventListener('click', closeMenu));

      const sections = document.querySelectorAll('section[id]');
      window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
          const top = section.offsetTop - 120;
          if (window.scrollY >= top) current = section.getAttribute('id');
        });
        document.querySelectorAll('.nav-links-desktop a').forEach(a => {
          a.classList.remove('active');
          if (a.getAttribute('href') === '#' + current) a.classList.add('active');
        });
      });
    })();

    /* ============================================================
       THEME TOGGLE (Light default → Dark toggle)
       ============================================================ */
    (function initTheme() {
      const toggle = document.getElementById('themeToggle');
      const icon = document.getElementById('themeIcon');
      const body = document.body;

      // Light is default. If user previously chose dark, restore it.
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

    /* ============================================================
       GSAP SCROLLTRIGGER
       ============================================================ */
    (function initScrollAnimations() {
      gsap.registerPlugin(ScrollTrigger);

      document.querySelectorAll('[data-reveal]').forEach(el => {
        gsap.from(el, {
          scrollTrigger: {
            trigger: el,
            start: 'top 88%',
            toggleActions: 'play none none none'
          },
          opacity: 0,
          y: 40,
          duration: 0.9,
          ease: 'power3.out'
        });
      });

      gsap.to('#heroBg', {
        scrollTrigger: {
          trigger: '#home',
          start: 'top top',
          end: 'bottom top',
          scrub: 1
        },
        y: 120,
        ease: 'none'
      });

      document.querySelectorAll('.counter').forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        ScrollTrigger.create({
          trigger: counter,
          start: 'top 90%',
          once: true,
          onEnter: () => {
            gsap.to(counter, {
              innerText: target,
              duration: 1.8,
              ease: 'power2.out',
              snap: { innerText: 1 },
              onUpdate: function() {
                counter.textContent = Math.floor(this.targets()[0].innerText);
              }
            });
          }
        });
      });

      gsap.from('.program-card', {
        scrollTrigger: { trigger: '#programs', start: 'top 75%' },
        opacity: 0,
        y: 40,
        duration: 0.8,
        stagger: 0.08,
        ease: 'power3.out'
      });
    })();

    /* ============================================================
       MAGNETIC BUTTONS
       ============================================================ */
    (function initMagnetic() {
      if (window.innerWidth < 992) return;

      document.querySelectorAll('.magnetic').forEach(btn => {
        btn.addEventListener('mousemove', (e) => {
          const rect = btn.getBoundingClientRect();
          const x = e.clientX - rect.left - rect.width / 2;
          const y = e.clientY - rect.top - rect.height / 2;
          gsap.to(btn, { x: x * 0.25, y: y * 0.25, duration: 0.4, ease: 'power2.out' });
        });

        btn.addEventListener('mouseleave', () => {
          gsap.to(btn, { x: 0, y: 0, duration: 0.5, ease: 'elastic.out(1, 0.4)' });
        });
      });
    })();

    /* ============================================================
       PRICING: PLAN SELECTION
       ============================================================ */
    (function initPricing() {
      document.querySelectorAll('.plan-select').forEach(btn => {
        btn.addEventListener('click', () => {
          const plan = btn.getAttribute('data-plan');
          const planSelect = document.getElementById('planSelect');
          if (planSelect) {
            planSelect.value = plan;
          }
          document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
          setTimeout(() => {
            planSelect.style.borderColor = 'var(--accent)';
            setTimeout(() => planSelect.style.borderColor = '', 2000);
          }, 800);
        });
      });
    })();

    /* ============================================================
       CONTACT FORM VALIDATION
       ============================================================ */
    (function initForm() {
      const form = document.getElementById('contactForm');
      const nameInput = document.getElementById('nameInput');
      const emailInput = document.getElementById('emailInput');
      const messageInput = document.getElementById('messageInput');
      const successMsg = document.getElementById('formSuccess');

      form.addEventListener('submit', (e) => {
        e.preventDefault();
        let valid = true;

        [nameInput, emailInput, messageInput].forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.form-error').forEach(el => el.style.display = 'none');

        if (nameInput.value.trim().length < 2) {
          nameInput.classList.add('is-invalid');
          document.getElementById('nameError').style.display = 'block';
          valid = false;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value.trim())) {
          emailInput.classList.add('is-invalid');
          document.getElementById('emailError').style.display = 'block';
          valid = false;
        }

        if (messageInput.value.trim().length < 10) {
          messageInput.classList.add('is-invalid');
          document.getElementById('messageError').style.display = 'block';
          valid = false;
        }

        if (valid) {
          form.style.display = 'none';
          successMsg.style.display = 'block';
        }
      });

      [nameInput, emailInput, messageInput].forEach(input => {
        input.addEventListener('input', () => {
          input.classList.remove('is-invalid');
          const errorId = input.id.replace('Input', 'Error');
          const err = document.getElementById(errorId);
          if (err) err.style.display = 'none';
        });
      });
    })();

    /* ============================================================
       SMOOTH SCROLL
       ============================================================ */
    (function initSmoothScroll() {
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
          const targetId = this.getAttribute('href');
          if (targetId === '#') return;
          const target = document.querySelector(targetId);
          if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        });
      });
    })();

    /* ============================================================
       WHATSAPP CONFIG
       ============================================================ */
    (function initWhatsApp() {
      const wa = document.querySelector('.whatsapp-float');
      if (wa) {
        wa.href = `https://wa.me/${WHATSAPP_NUMBER}?text=Hi%20IRON%20ELITE%2C%20I%27d%20like%20to%20know%20more%20about%20membership.`;
      }
    })();
  </script>
</body>
</html>