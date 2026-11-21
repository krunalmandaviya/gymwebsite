<?php
session_start();

/* =========================================================
   PATH FIX — Windows/XAMPP safe
   ========================================================= */
define('ROOT_PATH', dirname(__DIR__));

require_once __DIR__ . '/../config/config.php';

/* =========================================================
   POST HANDLERS
   ========================================================= */
$success = '';
$error   = '';

/* ---------- HERO ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'hero') {
    try {
        $hero = $pdo->query("SELECT * FROM hero_section WHERE is_active = 1 ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$hero) throw new Exception('Hero record not found.');

        $bgPath = $hero['background_image'];

        if (!empty($_FILES['background_image']['name']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','webp','avif'];
            $ext = strtolower(pathinfo($_FILES['background_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) throw new Exception('Invalid image type. Allowed: jpg, png, webp, avif.');
            if ($_FILES['background_image']['size'] > 8 * 1024 * 1024) throw new Exception('Image too large (max 8MB).');

            $uploadDir = ROOT_PATH . '/uploads/hero/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'hero-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['background_image']['tmp_name'], $uploadDir . $filename)) {
                throw new Exception('Failed to move uploaded file.');
            }
            if ($hero['background_image'] && file_exists(ROOT_PATH . '/' . $hero['background_image'])) {
                @unlink(ROOT_PATH . '/' . $hero['background_image']);
            }
            $bgPath = 'uploads/hero/' . $filename;
        }

        $sql = "UPDATE hero_section SET
            eyebrow_text = ?,
            title_line_1 = ?, title_line_2 = ?, title_line_3 = ?, title_line_4 = ?,
            title_line_2_accent = ?,
            description = ?,
            cta_primary_text = ?, cta_primary_link = ?, cta_primary_icon = ?,
            cta_secondary_text = ?, cta_secondary_link = ?,
            scroll_text = ?, background_image = ?, is_active = ?,
            updated_by = ?
            WHERE id = ?";
        $pdo->prepare($sql)->execute([
            trim($_POST['eyebrow_text'] ?? ''),
            trim($_POST['title_line_1'] ?? ''), trim($_POST['title_line_2'] ?? ''),
            trim($_POST['title_line_3'] ?? ''), trim($_POST['title_line_4'] ?? ''),
            isset($_POST['title_line_2_accent']) ? 1 : 0,
            trim($_POST['description'] ?? ''),
            trim($_POST['cta_primary_text'] ?? ''), trim($_POST['cta_primary_link'] ?? ''),
            trim($_POST['cta_primary_icon'] ?? ''),
            trim($_POST['cta_secondary_text'] ?? ''), trim($_POST['cta_secondary_link'] ?? ''),
            trim($_POST['scroll_text'] ?? ''), $bgPath,
            isset($_POST['is_active']) ? 1 : 0,
            $_SESSION['admin_id'] ?? null,
            $hero['id']
        ]);

        $success = 'Hero section updated successfully.';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

/* ---------- MARQUEE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'marquee') {
    try {
        $marquee = $pdo->query("SELECT * FROM marquee_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$marquee) {
            $pdo->exec("INSERT INTO marquee_section (id, is_active) VALUES (1, 1)");
            $marquee = ['id' => 1];
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE marquee_section SET is_active = ?, updated_by = ? WHERE id = ?")
            ->execute([
                isset($_POST['is_active']) ? 1 : 0,
                $_SESSION['admin_id'] ?? null,
                $marquee['id']
            ]);

        $pdo->prepare("DELETE FROM marquee_items WHERE marquee_id = ?")->execute([$marquee['id']]);

        $labels = $_POST['labels'] ?? [];
        $insertStmt = $pdo->prepare("INSERT INTO marquee_items (marquee_id, label, sort_order) VALUES (?, ?, ?)");
        $order = 1;
        foreach ($labels as $label) {
            $label = trim($label);
            if ($label === '') continue;
            if (mb_strlen($label) > 80) continue;
            $insertStmt->execute([$marquee['id'], $label, $order++]);
        }

        $pdo->commit();
        $success = 'Marquee section updated successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'Marquee update failed: ' . $e->getMessage();
    }
}

/* ---------- ABOUT ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'about') {
    try {
        $about = $pdo->query("SELECT * FROM about_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$about) {
            $pdo->exec("INSERT INTO about_section (id, is_active) VALUES (1, 1)");
            $about = ['id' => 1, 'image' => null];
        }

        $imgPath = $about['image'];

        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','webp','avif'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) throw new Exception('Invalid image type.');
            if ($_FILES['image']['size'] > 8 * 1024 * 1024) throw new Exception('Image too large (max 8MB).');

            $uploadDir = ROOT_PATH . '/uploads/about/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'about-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                throw new Exception('Failed to move uploaded file.');
            }
            if ($about['image'] && file_exists(ROOT_PATH . '/' . $about['image'])) {
                @unlink(ROOT_PATH . '/' . $about['image']);
            }
            $imgPath = 'uploads/about/' . $filename;
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE about_section SET
            label = ?, title_line_1 = ?, title_line_2 = ?,
            para_1 = ?, para_2 = ?, image = ?, is_active = ?, updated_by = ?
            WHERE id = ?")
            ->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['title_line_1'] ?? ''),
                trim($_POST['title_line_2'] ?? ''),
                trim($_POST['para_1'] ?? ''),
                trim($_POST['para_2'] ?? ''),
                $imgPath,
                isset($_POST['is_active']) ? 1 : 0,
                $_SESSION['admin_id'] ?? null,
                $about['id']
            ]);

        $pdo->prepare("DELETE FROM about_stats WHERE about_id = ?")->execute([$about['id']]);

        $numbers = $_POST['stat_number'] ?? [];
        $suffixes = $_POST['stat_suffix'] ?? [];
        $statLabels = $_POST['stat_label'] ?? [];
        $insertStmt = $pdo->prepare("INSERT INTO about_stats (about_id, number, suffix, label, sort_order) VALUES (?, ?, ?, ?, ?)");
        $order = 1;
        foreach ($statLabels as $i => $lbl) {
            $lbl = trim($lbl);
            if ($lbl === '') continue;
            $num = (int)($numbers[$i] ?? 0);
            $suf = trim($suffixes[$i] ?? '');
            if (mb_strlen($suf) > 10) $suf = mb_substr($suf, 0, 10);
            $insertStmt->execute([$about['id'], $num, $suf, $lbl, $order++]);
        }

        $pdo->commit();
        $success = 'About section updated successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'About update failed: ' . $e->getMessage();
    }
}

/* ---------- PRICING ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'pricing') {
    try {
        $pricing = $pdo->query("SELECT * FROM pricing_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$pricing) {
            $pdo->exec("INSERT INTO pricing_section (id, is_active) VALUES (1, 1)");
            $pricing = ['id' => 1];
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE pricing_section SET label = ?, title = ?, subtitle = ?, is_active = ?, updated_by = ? WHERE id = ?")
            ->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['title'] ?? ''),
                trim($_POST['subtitle'] ?? ''),
                isset($_POST['is_active']) ? 1 : 0,
                $_SESSION['admin_id'] ?? null,
                $pricing['id']
            ]);

        $existingPlanIds = $pdo->query("SELECT id FROM pricing_plans WHERE pricing_id = " . (int)$pricing['id'])->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($existingPlanIds)) {
            $in = implode(',', array_map('intval', $existingPlanIds));
            $pdo->exec("DELETE FROM pricing_features WHERE plan_id IN ($in)");
        }
        $pdo->prepare("DELETE FROM pricing_plans WHERE pricing_id = ?")->execute([$pricing['id']]);

        $planNames     = $_POST['plan_name'] ?? [];
        $planPrices    = $_POST['plan_price'] ?? [];
        $planCurrency  = $_POST['plan_currency'] ?? [];
        $planPeriod    = $_POST['plan_period'] ?? [];
        $planBtnText   = $_POST['plan_button_text'] ?? [];
        $planBtnStyle  = $_POST['plan_button_style'] ?? [];
        $planFeatured  = $_POST['plan_featured'] ?? [];
        $planBadge     = $_POST['plan_badge'] ?? [];
        $planFeatures  = $_POST['plan_features'] ?? [];
        $planFeatureOn = $_POST['plan_features_on'] ?? [];

        $insertPlan = $pdo->prepare("INSERT INTO pricing_plans
            (pricing_id, name, price, currency, period, button_text, button_style, is_featured, badge_text, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertFeature = $pdo->prepare("INSERT INTO pricing_features
            (plan_id, feature_text, is_included, sort_order) VALUES (?, ?, ?, ?)");

        $planOrder = 1;
        foreach ($planNames as $i => $name) {
            $name = trim($name);
            if ($name === '') continue;

            $isFeatured = in_array((string)$i, array_map('strval', $planFeatured), true) ? 1 : 0;
            $insertPlan->execute([
                $pricing['id'],
                $name,
                trim($planPrices[$i] ?? ''),
                trim($planCurrency[$i] ?? '$'),
                trim($planPeriod[$i] ?? '/mo'),
                trim($planBtnText[$i] ?? 'Select'),
                in_array(($planBtnStyle[$i] ?? 'ghost'), ['accent','ghost']) ? $planBtnStyle[$i] : 'ghost',
                $isFeatured,
                trim($planBadge[$i] ?? '') ?: null,
                $planOrder++
            ]);
            $newPlanId = (int)$pdo->lastInsertId();

            $featTexts = $planFeatures[$i] ?? [];
            $featOn    = $planFeatureOn[$i] ?? [];
            $featOrder = 1;
            foreach ($featTexts as $fi => $ftext) {
                $ftext = trim($ftext);
                if ($ftext === '') continue;
                $included = isset($featOn[$fi]) ? 1 : 0;
                $insertFeature->execute([$newPlanId, $ftext, $included, $featOrder++]);
            }
        }

        $pdo->commit();
        $success = 'Pricing section updated successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'Pricing update failed: ' . $e->getMessage();
    }
}

/* ---------- PROGRAMS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'programs') {
    try {
        $programs = $pdo->query("SELECT * FROM programs_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$programs) {
            $pdo->exec("INSERT INTO programs_section (id, is_active) VALUES (1, 1)");
            $programs = ['id' => 1];
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE programs_section SET label = ?, title = ?, subtitle = ?, is_active = ?, updated_by = ? WHERE id = ?")
            ->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['title'] ?? ''),
                trim($_POST['subtitle'] ?? ''),
                isset($_POST['is_active']) ? 1 : 0,
                $_SESSION['admin_id'] ?? null,
                $programs['id']
            ]);

        $existing = [];
        $stmt = $pdo->prepare("SELECT id, image FROM programs_items WHERE programs_id = ?");
        $stmt->execute([$programs['id']]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing[(int)$r['id']] = $r;
        }

        $itemIds    = $_POST['item_id'] ?? [];
        $names      = $_POST['item_name'] ?? [];
        $descs      = $_POST['item_description'] ?? [];
        $keepImages = $_POST['item_keep_image'] ?? [];

        $keptIds = [];

        $insertItem = $pdo->prepare("INSERT INTO programs_items
            (programs_id, name, description, image, sort_order) VALUES (?, ?, ?, ?, ?)");
        $updateItem = $pdo->prepare("UPDATE programs_items SET
            name = ?, description = ?, image = ?, sort_order = ? WHERE id = ?");

        $order = 1;
        foreach ($names as $i => $name) {
            $name = trim($name);
            if ($name === '') continue;

            $id = (int)($itemIds[$i] ?? 0);
            $desc = trim($descs[$i] ?? '');
            $imagePath = null;

            if ($id > 0 && isset($existing[$id])) {
                $imagePath = $existing[$id]['image'];
            }

            $urlInput = trim($_POST['item_image_url'][$i] ?? '');
            if ($urlInput !== '') $imagePath = $urlInput;

            if (!empty($_FILES['item_image']['name'][$i]) && $_FILES['item_image']['error'][$i] === UPLOAD_ERR_OK) {
                $allowed = ['jpg','jpeg','png','webp','avif'];
                $ext = strtolower(pathinfo($_FILES['item_image']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) throw new Exception("Invalid image type on row " . ($i+1));
                if ($_FILES['item_image']['size'][$i] > 8 * 1024 * 1024) throw new Exception("Image too large on row " . ($i+1));

                $uploadDir = ROOT_PATH . '/uploads/programs/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'program-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (!move_uploaded_file($_FILES['item_image']['tmp_name'][$i], $uploadDir . $filename)) {
                    throw new Exception('Failed to move uploaded file.');
                }
                if ($imagePath && strpos($imagePath, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $imagePath)) {
                    @unlink(ROOT_PATH . '/' . $imagePath);
                }
                $imagePath = 'uploads/programs/' . $filename;
            }

            if ($id > 0 && isset($existing[$id]) && empty($keepImages[$i]) && empty($_FILES['item_image']['name'][$i]) && $urlInput === '') {
                if ($imagePath && strpos($imagePath, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $imagePath)) {
                    @unlink(ROOT_PATH . '/' . $imagePath);
                }
                $imagePath = null;
            }

            if ($id > 0 && isset($existing[$id])) {
                $updateItem->execute([$name, $desc, $imagePath, $order++, $id]);
                $keptIds[] = $id;
            } else {
                $insertItem->execute([$programs['id'], $name, $desc, $imagePath, $order++]);
                $keptIds[] = (int)$pdo->lastInsertId();
            }
        }

        $toDelete = array_diff(array_keys($existing), $keptIds);
        if (!empty($toDelete)) {
            foreach ($toDelete as $delId) {
                $img = $existing[$delId]['image'] ?? '';
                if ($img && strpos($img, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $img)) {
                    @unlink(ROOT_PATH . '/' . $img);
                }
            }
            $in = implode(',', array_map('intval', $toDelete));
            $pdo->exec("DELETE FROM programs_items WHERE id IN ($in)");
        }

        $pdo->commit();
        $success = 'Programs section updated successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'Programs update failed: ' . $e->getMessage();
    }
}

/* ---------- TRAINERS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'trainers') {
    try {
        $trainers = $pdo->query("SELECT * FROM trainers_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$trainers) {
            $pdo->exec("INSERT INTO trainers_section (id, is_active) VALUES (1, 1)");
            $trainers = ['id' => 1];
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE trainers_section SET label = ?, title = ?, subtitle = ?, is_active = ?, updated_by = ? WHERE id = ?")
            ->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['title'] ?? ''),
                trim($_POST['subtitle'] ?? ''),
                isset($_POST['is_active']) ? 1 : 0,
                $_SESSION['admin_id'] ?? null,
                $trainers['id']
            ]);

        $existing = [];
        $stmt = $pdo->prepare("SELECT id, image FROM trainers_items WHERE trainers_id = ?");
        $stmt->execute([$trainers['id']]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing[(int)$r['id']] = $r;
        }

        $itemIds    = $_POST['item_id'] ?? [];
        $names      = $_POST['item_name'] ?? [];
        $specialties= $_POST['item_specialty'] ?? [];
        $instas     = $_POST['item_instagram'] ?? [];
        $twitters   = $_POST['item_twitter'] ?? [];
        $keepImages = $_POST['item_keep_image'] ?? [];

        $keptIds = [];

        $insertItem = $pdo->prepare("INSERT INTO trainers_items
            (trainers_id, name, specialty, image, instagram_url, twitter_url, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $updateItem = $pdo->prepare("UPDATE trainers_items SET
            name = ?, specialty = ?, image = ?, instagram_url = ?, twitter_url = ?, sort_order = ? WHERE id = ?");

        $order = 1;
        foreach ($names as $i => $name) {
            $name = trim($name);
            if ($name === '') continue;

            $id = (int)($itemIds[$i] ?? 0);
            $spec = trim($specialties[$i] ?? '');
            $insta = trim($instas[$i] ?? '');
            $twit = trim($twitters[$i] ?? '');

            $imagePath = null;
            if ($id > 0 && isset($existing[$id])) {
                $imagePath = $existing[$id]['image'];
            }

            $urlInput = trim($_POST['item_image_url'][$i] ?? '');
            if ($urlInput !== '') $imagePath = $urlInput;

            if (!empty($_FILES['item_image']['name'][$i]) && $_FILES['item_image']['error'][$i] === UPLOAD_ERR_OK) {
                $allowed = ['jpg','jpeg','png','webp','avif'];
                $ext = strtolower(pathinfo($_FILES['item_image']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) throw new Exception("Invalid image type on row " . ($i+1));
                if ($_FILES['item_image']['size'][$i] > 8 * 1024 * 1024) throw new Exception("Image too large on row " . ($i+1));

                $uploadDir = ROOT_PATH . '/uploads/trainers/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'trainer-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (!move_uploaded_file($_FILES['item_image']['tmp_name'][$i], $uploadDir . $filename)) {
                    throw new Exception('Failed to move uploaded file.');
                }
                if ($imagePath && strpos($imagePath, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $imagePath)) {
                    @unlink(ROOT_PATH . '/' . $imagePath);
                }
                $imagePath = 'uploads/trainers/' . $filename;
            }

            if ($id > 0 && isset($existing[$id]) && empty($keepImages[$i]) && empty($_FILES['item_image']['name'][$i]) && $urlInput === '') {
                if ($imagePath && strpos($imagePath, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $imagePath)) {
                    @unlink(ROOT_PATH . '/' . $imagePath);
                }
                $imagePath = null;
            }

            if ($id > 0 && isset($existing[$id])) {
                $updateItem->execute([$name, $spec ?: null, $imagePath, $insta ?: null, $twit ?: null, $order++, $id]);
                $keptIds[] = $id;
            } else {
                $insertItem->execute([$trainers['id'], $name, $spec ?: null, $imagePath, $insta ?: null, $twit ?: null, $order++]);
                $keptIds[] = (int)$pdo->lastInsertId();
            }
        }

        $toDelete = array_diff(array_keys($existing), $keptIds);
        if (!empty($toDelete)) {
            foreach ($toDelete as $delId) {
                $img = $existing[$delId]['image'] ?? '';
                if ($img && strpos($img, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $img)) {
                    @unlink(ROOT_PATH . '/' . $img);
                }
            }
            $in = implode(',', array_map('intval', $toDelete));
            $pdo->exec("DELETE FROM trainers_items WHERE id IN ($in)");
        }

        $pdo->commit();
        $success = 'Trainers section updated successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'Trainers update failed: ' . $e->getMessage();
    }
}

/* ---------- FACILITIES ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'facilities') {
    try {
        $fac = $pdo->query("SELECT * FROM facilities_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$fac) {
            $pdo->exec("INSERT INTO facilities_section (id, is_active) VALUES (1, 1)");
            $fac = ['id' => 1, 'image_main' => null, 'image_small_1' => null, 'image_small_2' => null];
        }

        $imgKeys = ['image_main', 'image_small_1', 'image_small_2'];
        $imgPaths = [];
        foreach ($imgKeys as $k) $imgPaths[$k] = $fac[$k] ?? null;

        foreach ($imgKeys as $key) {
            if (!empty($_FILES[$key]['name']) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg','jpeg','png','webp','avif'];
                $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) throw new Exception("Invalid image type for $key.");
                if ($_FILES[$key]['size'] > 8 * 1024 * 1024) throw new Exception("Image too large for $key (max 8MB).");

                $uploadDir = ROOT_PATH . '/uploads/facilities/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'fac-' . $key . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (!move_uploaded_file($_FILES[$key]['tmp_name'], $uploadDir . $filename)) {
                    throw new Exception("Failed to move uploaded file for $key.");
                }
                if ($imgPaths[$key] && strpos($imgPaths[$key], 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $imgPaths[$key])) {
                    @unlink(ROOT_PATH . '/' . $imgPaths[$key]);
                }
                $imgPaths[$key] = 'uploads/facilities/' . $filename;
            }
            $urlKey = $key . '_url';
            if (!empty($_POST[$urlKey])) {
                $imgPaths[$key] = trim($_POST[$urlKey]);
            }
            if (!empty($_POST[$key . '_clear']) && empty($_FILES[$key]['name']) && empty($_POST[$urlKey])) {
                if ($imgPaths[$key] && strpos($imgPaths[$key], 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $imgPaths[$key])) {
                    @unlink(ROOT_PATH . '/' . $imgPaths[$key]);
                }
                $imgPaths[$key] = null;
            }
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE facilities_section SET
            label = ?, title_line_1 = ?, title_line_2 = ?, subtitle = ?,
            image_main = ?, image_small_1 = ?, image_small_2 = ?,
            is_active = ?, updated_by = ?
            WHERE id = ?")
            ->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['title_line_1'] ?? ''),
                trim($_POST['title_line_2'] ?? ''),
                trim($_POST['subtitle'] ?? ''),
                $imgPaths['image_main'],
                $imgPaths['image_small_1'],
                $imgPaths['image_small_2'],
                isset($_POST['is_active']) ? 1 : 0,
                $_SESSION['admin_id'] ?? null,
                $fac['id']
            ]);

        $pdo->prepare("DELETE FROM facilities_items WHERE facilities_id = ?")->execute([$fac['id']]);

        $icons  = $_POST['item_icon'] ?? [];
        $titles = $_POST['item_title'] ?? [];
        $descs  = $_POST['item_description'] ?? [];
        $insertStmt = $pdo->prepare("INSERT INTO facilities_items
            (facilities_id, icon, title, description, sort_order) VALUES (?, ?, ?, ?, ?)");
        $order = 1;
        foreach ($titles as $i => $title) {
            $title = trim($title);
            if ($title === '') continue;
            $icon = trim($icons[$i] ?? 'bi bi-check');
            $desc = trim($descs[$i] ?? '');
            $insertStmt->execute([$fac['id'], $icon, $title, $desc, $order++]);
        }

        $pdo->commit();
        $success = 'Facilities section updated successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'Facilities update failed: ' . $e->getMessage();
    }
}

/* ---------- TRANSFORMATIONS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'transformations') {
    try {
        $tr = $pdo->query("SELECT * FROM transformations_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$tr) {
            $pdo->exec("INSERT INTO transformations_section (id, is_active) VALUES (1, 1)");
            $tr = ['id' => 1];
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE transformations_section SET label = ?, title = ?, subtitle = ?, is_active = ?, updated_by = ? WHERE id = ?")
            ->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['title'] ?? ''),
                trim($_POST['subtitle'] ?? ''),
                isset($_POST['is_active']) ? 1 : 0,
                $_SESSION['admin_id'] ?? null,
                $tr['id']
            ]);

        $existing = [];
        $stmt = $pdo->prepare("SELECT id, image FROM transformations_items WHERE transformations_id = ?");
        $stmt->execute([$tr['id']]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing[(int)$r['id']] = $r;
        }

        $itemIds    = $_POST['item_id'] ?? [];
        $names      = $_POST['item_name'] ?? [];
        $durations  = $_POST['item_duration'] ?? [];
        $results    = $_POST['item_result'] ?? [];
        $quotes     = $_POST['item_quote'] ?? [];
        $keepImages = $_POST['item_keep_image'] ?? [];

        $keptIds = [];

        $insertItem = $pdo->prepare("INSERT INTO transformations_items
            (transformations_id, name, duration_label, result_line, quote, image, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $updateItem = $pdo->prepare("UPDATE transformations_items SET
            name = ?, duration_label = ?, result_line = ?, quote = ?, image = ?, sort_order = ? WHERE id = ?");

        $order = 1;
        foreach ($names as $i => $name) {
            $name = trim($name);
            if ($name === '') continue;

            $id = (int)($itemIds[$i] ?? 0);
            $dur = trim($durations[$i] ?? '');
            $res = trim($results[$i] ?? '');
            $q   = trim($quotes[$i] ?? '');

            $imagePath = null;
            if ($id > 0 && isset($existing[$id])) {
                $imagePath = $existing[$id]['image'];
            }

            $urlInput = trim($_POST['item_image_url'][$i] ?? '');
            if ($urlInput !== '') $imagePath = $urlInput;

            if (!empty($_FILES['item_image']['name'][$i]) && $_FILES['item_image']['error'][$i] === UPLOAD_ERR_OK) {
                $allowed = ['jpg','jpeg','png','webp','avif'];
                $ext = strtolower(pathinfo($_FILES['item_image']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) throw new Exception("Invalid image type on row " . ($i+1));
                if ($_FILES['item_image']['size'][$i] > 8 * 1024 * 1024) throw new Exception("Image too large on row " . ($i+1));

                $uploadDir = ROOT_PATH . '/uploads/transformations/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'trans-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (!move_uploaded_file($_FILES['item_image']['tmp_name'][$i], $uploadDir . $filename)) {
                    throw new Exception('Failed to move uploaded file.');
                }
                if ($imagePath && strpos($imagePath, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $imagePath)) {
                    @unlink(ROOT_PATH . '/' . $imagePath);
                }
                $imagePath = 'uploads/transformations/' . $filename;
            }

            if ($id > 0 && isset($existing[$id]) && empty($keepImages[$i]) && empty($_FILES['item_image']['name'][$i]) && $urlInput === '') {
                if ($imagePath && strpos($imagePath, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $imagePath)) {
                    @unlink(ROOT_PATH . '/' . $imagePath);
                }
                $imagePath = null;
            }

            if ($id > 0 && isset($existing[$id])) {
                $updateItem->execute([$name, $dur ?: null, $res ?: null, $q ?: null, $imagePath, $order++, $id]);
                $keptIds[] = $id;
            } else {
                $insertItem->execute([$tr['id'], $name, $dur ?: null, $res ?: null, $q ?: null, $imagePath, $order++]);
                $keptIds[] = (int)$pdo->lastInsertId();
            }
        }

        $toDelete = array_diff(array_keys($existing), $keptIds);
        if (!empty($toDelete)) {
            foreach ($toDelete as $delId) {
                $img = $existing[$delId]['image'] ?? '';
                if ($img && strpos($img, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $img)) {
                    @unlink(ROOT_PATH . '/' . $img);
                }
            }
            $in = implode(',', array_map('intval', $toDelete));
            $pdo->exec("DELETE FROM transformations_items WHERE id IN ($in)");
        }

        $pdo->commit();
        $success = 'Transformations section updated successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'Transformations update failed: ' . $e->getMessage();
    }
}

/* ---------- TESTIMONIALS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'testimonials') {
    try {
        $tm = $pdo->query("SELECT * FROM testimonials_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$tm) {
            $pdo->exec("INSERT INTO testimonials_section (id, is_active) VALUES (1, 1)");
            $tm = ['id' => 1];
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE testimonials_section SET label = ?, title = ?, subtitle = ?, is_active = ?, updated_by = ? WHERE id = ?")
            ->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['title'] ?? ''),
                trim($_POST['subtitle'] ?? ''),
                isset($_POST['is_active']) ? 1 : 0,
                $_SESSION['admin_id'] ?? null,
                $tm['id']
            ]);

        $existing = [];
        $stmt = $pdo->prepare("SELECT id, avatar FROM testimonials_items WHERE testimonials_id = ?");
        $stmt->execute([$tm['id']]);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing[(int)$r['id']] = $r;
        }

        $itemIds    = $_POST['item_id'] ?? [];
        $names      = $_POST['item_name'] ?? [];
        $roles      = $_POST['item_role'] ?? [];
        $quotesIn   = $_POST['item_quote'] ?? [];
        $starsIn    = $_POST['item_stars'] ?? [];
        $keepImages = $_POST['item_keep_image'] ?? [];

        $keptIds = [];

        $insertItem = $pdo->prepare("INSERT INTO testimonials_items
            (testimonials_id, name, role, quote, stars, avatar, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $updateItem = $pdo->prepare("UPDATE testimonials_items SET
            name = ?, role = ?, quote = ?, stars = ?, avatar = ?, sort_order = ? WHERE id = ?");

        $order = 1;
        foreach ($names as $i => $name) {
            $name = trim($name);
            if ($name === '') continue;

            $id    = (int)($itemIds[$i] ?? 0);
            $role  = trim($roles[$i] ?? '');
            $quote = trim($quotesIn[$i] ?? '');
            $stars = (int)($starsIn[$i] ?? 5);
            if ($stars < 1) $stars = 1;
            if ($stars > 5) $stars = 5;

            $avatarPath = null;
            if ($id > 0 && isset($existing[$id])) {
                $avatarPath = $existing[$id]['avatar'];
            }

            $urlInput = trim($_POST['item_avatar_url'][$i] ?? '');
            if ($urlInput !== '') $avatarPath = $urlInput;

            if (!empty($_FILES['item_avatar']['name'][$i]) && $_FILES['item_avatar']['error'][$i] === UPLOAD_ERR_OK) {
                $allowed = ['jpg','jpeg','png','webp','avif'];
                $ext = strtolower(pathinfo($_FILES['item_avatar']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) throw new Exception("Invalid avatar type on row " . ($i+1));
                if ($_FILES['item_avatar']['size'][$i] > 4 * 1024 * 1024) throw new Exception("Avatar too large on row " . ($i+1) . " (max 4MB).");

                $uploadDir = ROOT_PATH . '/uploads/testimonials/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'tm-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (!move_uploaded_file($_FILES['item_avatar']['tmp_name'][$i], $uploadDir . $filename)) {
                    throw new Exception('Failed to move uploaded file.');
                }
                if ($avatarPath && strpos($avatarPath, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $avatarPath)) {
                    @unlink(ROOT_PATH . '/' . $avatarPath);
                }
                $avatarPath = 'uploads/testimonials/' . $filename;
            }

            if ($id > 0 && isset($existing[$id]) && empty($keepImages[$i]) && empty($_FILES['item_avatar']['name'][$i]) && $urlInput === '') {
                if ($avatarPath && strpos($avatarPath, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $avatarPath)) {
                    @unlink(ROOT_PATH . '/' . $avatarPath);
                }
                $avatarPath = null;
            }

            if ($id > 0 && isset($existing[$id])) {
                $updateItem->execute([$name, $role ?: null, $quote ?: null, $stars, $avatarPath, $order++, $id]);
                $keptIds[] = $id;
            } else {
                $insertItem->execute([$tm['id'], $name, $role ?: null, $quote ?: null, $stars, $avatarPath, $order++]);
                $keptIds[] = (int)$pdo->lastInsertId();
            }
        }

        $toDelete = array_diff(array_keys($existing), $keptIds);
        if (!empty($toDelete)) {
            foreach ($toDelete as $delId) {
                $img = $existing[$delId]['avatar'] ?? '';
                if ($img && strpos($img, 'uploads/') === 0 && file_exists(ROOT_PATH . '/' . $img)) {
                    @unlink(ROOT_PATH . '/' . $img);
                }
            }
            $in = implode(',', array_map('intval', $toDelete));
            $pdo->exec("DELETE FROM testimonials_items WHERE id IN ($in)");
        }

        $pdo->commit();
        $success = 'Testimonials section updated successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'Testimonials update failed: ' . $e->getMessage();
    }
}

/* ---------- SCHEDULE ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'schedule') {
    try {
        $sch = $pdo->query("SELECT * FROM schedule_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$sch) {
            $pdo->exec("INSERT INTO schedule_section (id, is_active) VALUES (1, 1)");
            $sch = ['id' => 1];
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE schedule_section SET label = ?, title = ?, subtitle = ?, is_active = ?, updated_by = ? WHERE id = ?")
            ->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['title'] ?? ''),
                trim($_POST['subtitle'] ?? ''),
                isset($_POST['is_active']) ? 1 : 0,
                $_SESSION['admin_id'] ?? null,
                $sch['id']
            ]);

        $pdo->prepare("DELETE FROM schedule_times WHERE schedule_id = ?")->execute([$sch['id']]);

        $timeLabels = $_POST['row_time'] ?? [];
        $cellMatrix = $_POST['cell'] ?? [];

        $insertTime = $pdo->prepare("INSERT INTO schedule_times (schedule_id, time_label, sort_order) VALUES (?, ?, ?)");
        $insertCell = $pdo->prepare("INSERT INTO schedule_cells (time_id, day_index, class_name) VALUES (?, ?, ?)");

        $order = 1;
        foreach ($timeLabels as $ri => $timeLabel) {
            $timeLabel = trim($timeLabel);
            if ($timeLabel === '') continue;
            $timeLabel = mb_substr($timeLabel, 0, 20);
            $insertTime->execute([$sch['id'], $timeLabel, $order++]);
            $newTimeId = (int)$pdo->lastInsertId();

            for ($day = 1; $day <= 6; $day++) {
                $cls = trim($cellMatrix[$ri][$day] ?? '');
                if ($cls === '') $cls = null;
                else $cls = mb_substr($cls, 0, 100);
                $insertCell->execute([$newTimeId, $day, $cls]);
            }
        }

        $pdo->commit();
        $success = 'Schedule section updated successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'Schedule update failed: ' . $e->getMessage();
    }
}

/* ---------- JOURNEY ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'journey') {
    try {
        $jr = $pdo->query("SELECT * FROM journey_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$jr) {
            $pdo->exec("INSERT INTO journey_section (id, is_active) VALUES (1, 1)");
            $jr = ['id' => 1];
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE journey_section SET
            label = ?, title = ?, subtitle = ?,
            cta_text = ?, cta_link = ?, cta_icon = ?,
            is_active = ?, updated_by = ?
            WHERE id = ?")
            ->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['title'] ?? ''),
                trim($_POST['subtitle'] ?? ''),
                trim($_POST['cta_text'] ?? ''),
                trim($_POST['cta_link'] ?? ''),
                trim($_POST['cta_icon'] ?? ''),
                isset($_POST['is_active']) ? 1 : 0,
                $_SESSION['admin_id'] ?? null,
                $jr['id']
            ]);

        $pdo->prepare("DELETE FROM journey_steps WHERE journey_id = ?")->execute([$jr['id']]);

        $numbers = $_POST['step_number'] ?? [];
        $titles  = $_POST['step_title'] ?? [];
        $descs   = $_POST['step_description'] ?? [];

        $insertStmt = $pdo->prepare("INSERT INTO journey_steps
            (journey_id, number, title, description, sort_order) VALUES (?, ?, ?, ?, ?)");
        $order = 1;
        foreach ($titles as $i => $title) {
            $title = trim($title);
            if ($title === '') continue;
            $num = trim($numbers[$i] ?? '');
            if ($num === '') $num = str_pad((string)$order, 2, '0', STR_PAD_LEFT);
            $num = mb_substr($num, 0, 10);
            $desc = trim($descs[$i] ?? '');
            $insertStmt->execute([$jr['id'], $num, $title, $desc, $order++]);
        }

        $pdo->commit();
        $success = 'Journey section updated successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'Journey update failed: ' . $e->getMessage();
    }
}

/* ---------- FAQ ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['section'] ?? '') === 'faq') {
    try {
        $faq = $pdo->query("SELECT * FROM faq_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$faq) {
            $pdo->exec("INSERT INTO faq_section (id, is_active) VALUES (1, 1)");
            $faq = ['id' => 1];
        }

        $pdo->beginTransaction();

        $pdo->prepare("UPDATE faq_section SET label = ?, title = ?, subtitle = ?, is_active = ?, updated_by = ? WHERE id = ?")
            ->execute([
                trim($_POST['label'] ?? ''),
                trim($_POST['title'] ?? ''),
                trim($_POST['subtitle'] ?? ''),
                isset($_POST['is_active']) ? 1 : 0,
                $_SESSION['admin_id'] ?? null,
                $faq['id']
            ]);

        $pdo->prepare("DELETE FROM faq_items WHERE faq_id = ?")->execute([$faq['id']]);

        $questions = $_POST['item_question'] ?? [];
        $answers   = $_POST['item_answer'] ?? [];

        $insertStmt = $pdo->prepare("INSERT INTO faq_items
            (faq_id, question, answer, sort_order) VALUES (?, ?, ?, ?)");
        $order = 1;
        foreach ($questions as $i => $q) {
            $q = trim($q);
            if ($q === '') continue;
            $q = mb_substr($q, 0, 255);
            $a = trim($answers[$i] ?? '');
            $insertStmt->execute([$faq['id'], $q, $a ?: null, $order++]);
        }

        $pdo->commit();
        $success = 'FAQ section updated successfully.';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = 'FAQ update failed: ' . $e->getMessage();
    }
}

/* =========================================================
   LOAD DATA
   ========================================================= */
$hero = $pdo->query("SELECT * FROM hero_section WHERE is_active = 1 ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$marquee = $pdo->query("SELECT * FROM marquee_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$marqueeItems = [];
if ($marquee) {
    $marqueeItems = $pdo->query(
        "SELECT * FROM marquee_items WHERE marquee_id = " . (int)$marquee['id'] . "
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

$about = $pdo->query("SELECT * FROM about_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$aboutStats = [];
if ($about) {
    $aboutStats = $pdo->query(
        "SELECT * FROM about_stats WHERE about_id = " . (int)$about['id'] . "
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

$pricing = $pdo->query("SELECT * FROM pricing_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$pricingPlans = [];
if ($pricing) {
    $pricingPlans = $pdo->query(
        "SELECT * FROM pricing_plans WHERE pricing_id = " . (int)$pricing['id'] . "
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($pricingPlans as &$plan) {
        $plan['features'] = $pdo->query(
            "SELECT * FROM pricing_features WHERE plan_id = " . (int)$plan['id'] . "
             ORDER BY sort_order ASC, id ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($plan);
}

$programs = $pdo->query("SELECT * FROM programs_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$programsItems = [];
if ($programs) {
    $programsItems = $pdo->query(
        "SELECT * FROM programs_items WHERE programs_id = " . (int)$programs['id'] . "
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

$trainers = $pdo->query("SELECT * FROM trainers_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$trainersItems = [];
if ($trainers) {
    $trainersItems = $pdo->query(
        "SELECT * FROM trainers_items WHERE trainers_id = " . (int)$trainers['id'] . "
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

$facilities = $pdo->query("SELECT * FROM facilities_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$facilitiesItems = [];
if ($facilities) {
    $facilitiesItems = $pdo->query(
        "SELECT * FROM facilities_items WHERE facilities_id = " . (int)$facilities['id'] . "
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

$transformations = $pdo->query("SELECT * FROM transformations_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$transformationsItems = [];
if ($transformations) {
    $transformationsItems = $pdo->query(
        "SELECT * FROM transformations_items WHERE transformations_id = " . (int)$transformations['id'] . "
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

$testimonials = $pdo->query("SELECT * FROM testimonials_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$testimonialsItems = [];
if ($testimonials) {
    $testimonialsItems = $pdo->query(
        "SELECT * FROM testimonials_items WHERE testimonials_id = " . (int)$testimonials['id'] . "
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

$schedule = $pdo->query("SELECT * FROM schedule_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$scheduleTimes = [];
$scheduleCellsByTime = [];
if ($schedule) {
    $scheduleTimes = $pdo->query(
        "SELECT * FROM schedule_times WHERE schedule_id = " . (int)$schedule['id'] . "
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($scheduleTimes)) {
        $timeIds = array_map(fn($t) => (int)$t['id'], $scheduleTimes);
        $in = implode(',', $timeIds);
        $cellRows = $pdo->query("SELECT * FROM schedule_cells WHERE time_id IN ($in)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cellRows as $c) {
            $scheduleCellsByTime[(int)$c['time_id']][(int)$c['day_index']] = $c['class_name'] ?? '';
        }
    }
}

$journey = $pdo->query("SELECT * FROM journey_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$journeySteps = [];
if ($journey) {
    $journeySteps = $pdo->query(
        "SELECT * FROM journey_steps WHERE journey_id = " . (int)$journey['id'] . "
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

$faq = $pdo->query("SELECT * FROM faq_section ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$faqItems = [];
if ($faq) {
    $faqItems = $pdo->query(
        "SELECT * FROM faq_items WHERE faq_id = " . (int)$faq['id'] . "
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================================================
   TABS CONFIG
   ========================================================= */
$tabs = [
    'hero'            => ['label' => 'Hero',            'icon' => 'bi-house-heart',     'ready' => true],
    'marquee'         => ['label' => 'Marquee',         'icon' => 'bi-arrow-left-right','ready' => true],
    'about'           => ['label' => 'About',           'icon' => 'bi-info-circle',     'ready' => true],
    'pricing'         => ['label' => 'Pricing',         'icon' => 'bi-tags',            'ready' => true],
    'programs'        => ['label' => 'Programs',        'icon' => 'bi-lightning',       'ready' => true],
    'trainers'        => ['label' => 'Trainers',        'icon' => 'bi-people',          'ready' => true],
    'facilities'      => ['label' => 'Facilities',      'icon' => 'bi-building',        'ready' => true],
    'transformations' => ['label' => 'Transformations', 'icon' => 'bi-stars',           'ready' => true],
    'testimonials'    => ['label' => 'Testimonials',    'icon' => 'bi-chat-quote',      'ready' => true],
    'schedule'        => ['label' => 'Schedule',        'icon' => 'bi-calendar-week',   'ready' => true],
    'journey'         => ['label' => 'Journey',         'icon' => 'bi-signpost-split',  'ready' => true],
    'faq'             => ['label' => 'FAQ',             'icon' => 'bi-question-circle', 'ready' => true],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root { --accent:#c9a24b; --bg:#0e0e10; --panel:#17171b; --border:#2a2a30; }
  * { box-sizing: border-box; }
  body { background:var(--bg); color:#eaeaea; font-family:system-ui,-apple-system,sans-serif; margin:0; }
  .layout { display:flex; min-height:100vh; }

  .sidebar {
    width:250px; flex-shrink:0;
    background:var(--panel); border-right:1px solid var(--border);
    padding:22px 14px; position:sticky; top:0; height:100vh; overflow-y:auto;
  }
  .brand { font-weight:800; letter-spacing:.06em; color:var(--accent); font-size:1.15rem; padding:0 10px 18px; }
  .nav-tab-btn {
    display:flex; align-items:center; gap:10px; width:100%; text-align:left;
    padding:10px 14px; border-radius:9px; border:1px solid transparent;
    background:transparent; color:#b9b9c1; font-size:.9rem; font-weight:600;
    transition:.15s; margin-bottom:2px; cursor:pointer;
  }
  .nav-tab-btn:hover:not(:disabled) { background:#1e1e24; color:#fff; }
  .nav-tab-btn.active { background:var(--accent); color:#0e0e10; }
  .nav-tab-btn.active i { color:#0e0e10; }
  .nav-tab-btn.soon { opacity:.45; cursor:not-allowed; }
  .nav-tab-btn .badge-soon { font-size:.6rem; background:#333; color:#888; margin-left:auto; padding:2px 6px; border-radius:4px; }
  .sidebar hr { border-color:#2a2a30; margin:18px 0; }

  .main { flex:1; padding:28px; min-width:0; }
  .panel { background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:28px; }
  .form-label { color:#b9b9c1; font-weight:600; font-size:.8rem; letter-spacing:.04em; text-transform:uppercase; }
  .form-control, .form-select { background:var(--bg); border:1px solid var(--border); color:#eaeaea; }
  .form-control:focus, .form-select:focus { background:var(--bg); color:#eaeaea; border-color:var(--accent); box-shadow:0 0 0 .2rem rgba(201,162,75,.15); }
  .form-control::placeholder { color:#555; }
  .form-check-input:checked { background-color:var(--accent); border-color:var(--accent); }
  .section-head { color:var(--accent); font-size:.72rem; letter-spacing:.22em; text-transform:uppercase; margin:28px 0 12px; font-weight:700; }
  .section-head:first-child { margin-top:0; }
  .btn-accent { background:var(--accent); color:#0e0e10; font-weight:700; border:none; }
  .btn-accent:hover { background:#dcb45e; color:#0e0e10; }
  .hint { color:#7a7a84; font-size:.78rem; }
  .preview-img { max-height:190px; width:100%; border-radius:10px; border:1px solid var(--border); object-fit:cover; }
  .sticky-save { position:sticky; bottom:0; background:linear-gradient(0deg, var(--panel) 65%, transparent); padding:18px 0 4px; margin-top:24px; }
  .soon-box { text-align:center; padding:80px 20px; }
  .soon-box i { font-size:3rem; color:var(--accent); opacity:.4; }

  .marquee-row { align-items:center; }
  .stat-row { align-items:center; }
  .plan-block { background:#131317; border:1px solid var(--border); border-radius:12px; padding:18px; margin-bottom:16px; }
  .plan-block .plan-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
  .plan-block .plan-title { font-weight:700; color:var(--accent); font-size:.95rem; }
  .feature-row { align-items:center; }
  .feature-row .form-check { display:flex; align-items:center; gap:6px; }
  .feature-row .form-check-input { margin-top:0; }

  .program-block, .trainer-block, .facility-block, .transformation-block, .testimonial-block, .journey-block, .faq-block { background:#131317; border:1px solid var(--border); border-radius:12px; padding:18px; margin-bottom:16px; }
  .item-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
  .item-title { font-weight:700; color:var(--accent); font-size:.95rem; }

  .program-thumb, .program-thumb-empty,
  .trainer-thumb, .trainer-thumb-empty,
  .transformation-thumb, .transformation-thumb-empty,
  .testimonial-thumb, .testimonial-thumb-empty {
    width:80px; height:80px; border-radius:8px; object-fit:cover;
    border:1px solid var(--border); background:#0e0e10;
  }
  .trainer-thumb, .trainer-thumb-empty,
  .testimonial-thumb, .testimonial-thumb-empty { border-radius:50%; }
  .program-thumb-empty, .trainer-thumb-empty, .transformation-thumb-empty, .testimonial-thumb-empty {
    display:flex; align-items:center; justify-content:center; color:#555; font-size:1.5rem;
  }

  .facility-block .facility-icon-preview {
    display:inline-flex; align-items:center; justify-content:center;
    width:42px; height:42px; border-radius:10px;
    background:rgba(201,162,75,.12); color:var(--accent); font-size:1.2rem;
  }

  .facilities-img-grid { display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
  .facilities-img-card { background:#131317; border:1px solid var(--border); border-radius:12px; padding:14px; }
  .facilities-img-card.main { grid-row: span 2; }
  .facilities-img-card .preview {
    width:100%; aspect-ratio: 1; object-fit:cover; border-radius:8px;
    border:1px solid var(--border); background:#0e0e10; margin-bottom:10px;
  }
  .facilities-img-card.main .preview { aspect-ratio: 3/4; }

  .star-select { max-width:110px; }

  /* Schedule matrix */
  .schedule-matrix-wrap { overflow-x:auto; border:1px solid var(--border); border-radius:12px; }
  table.schedule-matrix { width:100%; min-width:820px; border-collapse:separate; border-spacing:0; }
  table.schedule-matrix th, table.schedule-matrix td {
    border-bottom:1px solid var(--border); border-right:1px solid var(--border);
    padding:8px; vertical-align:middle; background:#131317;
  }
  table.schedule-matrix th { color:var(--accent); font-size:.72rem; letter-spacing:.12em; text-transform:uppercase; font-weight:700; background:#17171b; }
  table.schedule-matrix td:last-child, table.schedule-matrix th:last-child { border-right:none; }
  table.schedule-matrix tr:last-child td { border-bottom:none; }
  table.schedule-matrix .row-time-input { width:110px; }
  table.schedule-matrix .cell-input { min-width:120px; }
  table.schedule-matrix .row-remove-btn { white-space:nowrap; }
  .schedule-day-col { width:calc((100% - 140px - 60px) / 6); }

  /* Journey step number chip */
  .journey-num-preview {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:44px; height:44px; padding:0 10px; border-radius:10px;
    background:rgba(201,162,75,.12); color:var(--accent);
    font-weight:800; font-size:1rem; letter-spacing:.04em;
  }

  /* FAQ */
  .faq-block .faq-index-chip {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:38px; height:38px; padding:0 8px; border-radius:10px;
    background:rgba(201,162,75,.12); color:var(--accent);
    font-weight:800; font-size:.95rem;
  }

  @media(max-width:900px){
    .layout { flex-direction:column; }
    .sidebar { width:100%; height:auto; position:relative; }
    .main { padding:18px; }
    .panel { padding:18px; }
    .facilities-img-grid { grid-template-columns: 1fr; }
    .facilities-img-card.main { grid-row: auto; }
  }
</style>
</head>
<body>
<div class="layout">

  <aside class="sidebar">
    <div class="brand"><i class="bi bi-shield-lock"></i> Admin</div>

    <?php foreach ($tabs as $key => $t): ?>
      <button type="button"
              class="nav-tab-btn <?= $key==='hero' ? 'active' : ($t['ready'] ? '' : 'soon') ?>"
              data-tab="<?= $key ?>"
              <?= $t['ready'] ? '' : 'disabled' ?>>
        <i class="bi <?= $t['icon'] ?>"></i>
        <?= $t['label'] ?>
        <?php if (!$t['ready']): ?><span class="badge-soon">Soon</span><?php endif; ?>
      </button>
    <?php endforeach; ?>

    <hr>
    <a href="<?= '../index.php' ?>" target="_blank" class="nav-tab-btn">
      <i class="bi bi-box-arrow-up-right"></i> View Site
    </a>
    <a href="logout.php" class="nav-tab-btn">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </aside>

  <main class="main">

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- HERO TAB -->
    <div class="tab-pane-content" data-pane="hero">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">Hero Section</h2>
          <div class="hint">Homepage hero — headline, description, CTAs, background</div>
        </div>
      </div>

      <?php if (!$hero): ?>
        <div class="alert alert-warning">No hero record found. Insert one in <code>hero_section</code> table.</div>
      <?php else: ?>
      <form method="POST" enctype="multipart/form-data" class="panel">
        <input type="hidden" name="section" value="hero">

        <div class="section-head">Eyebrow</div>
        <input type="text" name="eyebrow_text" class="form-control"
               value="<?= htmlspecialchars($hero['eyebrow_text']) ?>">

        <div class="section-head">Headline (4 Lines)</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Line 1</label>
            <input type="text" name="title_line_1" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($hero['title_line_1']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Line 2 (accentable)</label>
            <input type="text" name="title_line_2" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($hero['title_line_2']) ?>">
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="title_line_2_accent"
                     id="accentCheck" <?= $hero['title_line_2_accent'] ? 'checked' : '' ?>>
              <label class="form-check-label hint" for="accentCheck">Highlight this line in accent color</label>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Line 3</label>
            <input type="text" name="title_line_3" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($hero['title_line_3']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Line 4</label>
            <input type="text" name="title_line_4" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($hero['title_line_4']) ?>">
          </div>
        </div>

        <div class="section-head">Description</div>
        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($hero['description']) ?></textarea>

        <div class="section-head">Call-to-Actions</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Primary — Label</label>
            <input type="text" name="cta_primary_text" class="form-control"
                   value="<?= htmlspecialchars($hero['cta_primary_text']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Primary — Link</label>
            <input type="text" name="cta_primary_link" class="form-control"
                   value="<?= htmlspecialchars($hero['cta_primary_link']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Primary — Icon</label>
            <input type="text" name="cta_primary_icon" class="form-control"
                   value="<?= htmlspecialchars($hero['cta_primary_icon']) ?>">
            <div class="hint">e.g. <code>bi bi-arrow-up-right</code></div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Secondary — Label</label>
            <input type="text" name="cta_secondary_text" class="form-control"
                   value="<?= htmlspecialchars($hero['cta_secondary_text']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Secondary — Link</label>
            <input type="text" name="cta_secondary_link" class="form-control"
                   value="<?= htmlspecialchars($hero['cta_secondary_link']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Scroll Text</label>
            <input type="text" name="scroll_text" class="form-control"
                   value="<?= htmlspecialchars($hero['scroll_text']) ?>">
          </div>
        </div>

        <div class="section-head">Background Image</div>
        <div class="row g-3 align-items-center">
          <div class="col-md-7">
            <input type="file" name="background_image" class="form-control" accept="image/*">
            <div class="hint mt-1">JPG / PNG / WEBP / AVIF — max 8MB. Recommended 1920×1080+</div>
          </div>
          <div class="col-md-5">
            <?php if ($hero['background_image']): ?>
              <img src="../<?= htmlspecialchars($hero['background_image']) ?>" class="preview-img" alt="Current">
            <?php else: ?>
              <div class="hint">No image uploaded yet.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="activeSwitch"
                 <?= $hero['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="activeSwitch">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save Hero
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <!-- MARQUEE TAB -->
    <div class="tab-pane-content d-none" data-pane="marquee">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">Marquee Section</h2>
          <div class="hint">Scrolling keywords between sections. Frontend auto-duplicates for the loop.</div>
        </div>
      </div>

      <form method="POST" class="panel" id="marqueeForm">
        <input type="hidden" name="section" value="marquee">

        <div class="section-head">Items</div>
        <div class="hint mb-3">Order yahan jaisa dikhega waisa hi frontend me jaayega.</div>

        <div id="marqueeItemsWrap" class="d-flex flex-column gap-2">
          <?php
            $rows = $marqueeItems ?: [['label' => '']];
            foreach ($rows as $item):
          ?>
          <div class="d-flex gap-2 marquee-row">
            <span class="d-flex align-items-center px-2 text-muted" style="min-width:32px; cursor:grab;">
              <i class="bi bi-grip-vertical"></i>
            </span>
            <input type="text" name="labels[]" class="form-control"
                   maxlength="80" placeholder="e.g. Strength"
                   value="<?= htmlspecialchars($item['label']) ?>">
            <button type="button" class="btn btn-outline-danger btn-sm marquee-remove" title="Remove">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" id="marqueeAddBtn" class="btn btn-outline-light btn-sm mt-3">
          <i class="bi bi-plus-lg"></i> Add Item
        </button>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="marqueeActive"
                 <?= ($marquee['is_active'] ?? 1) ? 'checked' : '' ?>>
          <label class="form-check-label" for="marqueeActive">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save Marquee
          </button>
        </div>
      </form>
    </div>

    <!-- ABOUT TAB -->
    <div class="tab-pane-content d-none" data-pane="about">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">About Section</h2>
          <div class="hint">Story text, image and the stat counters</div>
        </div>
      </div>

      <?php if (!$about): ?>
        <div class="alert alert-warning">No about record found. Insert one in <code>about_section</code> table.</div>
      <?php else: ?>
      <form method="POST" enctype="multipart/form-data" class="panel">
        <input type="hidden" name="section" value="about">

        <div class="section-head">Label</div>
        <input type="text" name="label" class="form-control" maxlength="100"
               value="<?= htmlspecialchars($about['label']) ?>">

        <div class="section-head">Title (2 Lines)</div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Line 1</label>
            <input type="text" name="title_line_1" class="form-control" maxlength="150"
                   value="<?= htmlspecialchars($about['title_line_1']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Line 2</label>
            <input type="text" name="title_line_2" class="form-control" maxlength="150"
                   value="<?= htmlspecialchars($about['title_line_2']) ?>">
          </div>
        </div>

        <div class="section-head">Story Paragraphs</div>
        <label class="form-label">Paragraph 1 (main)</label>
        <textarea name="para_1" class="form-control mb-3" rows="4"><?= htmlspecialchars($about['para_1']) ?></textarea>

        <label class="form-label">Paragraph 2 (secondary, small grey)</label>
        <textarea name="para_2" class="form-control" rows="3"><?= htmlspecialchars($about['para_2']) ?></textarea>

        <div class="section-head">Image</div>
        <div class="row g-3 align-items-center">
          <div class="col-md-7">
            <input type="file" name="image" class="form-control" accept="image/*">
            <div class="hint mt-1">JPG / PNG / WEBP / AVIF — max 8MB. Recommended square-ish, 1200px+</div>
          </div>
          <div class="col-md-5">
            <?php if ($about['image']): ?>
              <img src="../<?= htmlspecialchars($about['image']) ?>" class="preview-img" alt="Current">
            <?php else: ?>
              <div class="hint">No image uploaded yet.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="section-head">Stats (3 shown on frontend)</div>
        <div class="hint mb-3">Number + Suffix + Label. Example: 2400 / + / Members.</div>

        <div id="aboutStatsWrap" class="d-flex flex-column gap-2">
          <?php
            $statRows = $aboutStats ?: [
                ['number' => '', 'suffix' => '+', 'label' => ''],
                ['number' => '', 'suffix' => '+', 'label' => ''],
                ['number' => '', 'suffix' => '',  'label' => ''],
            ];
            foreach ($statRows as $s):
          ?>
          <div class="d-flex gap-2 stat-row">
            <input type="number" name="stat_number[]" class="form-control" style="max-width:120px;"
                   placeholder="2400" value="<?= htmlspecialchars($s['number']) ?>">
            <input type="text" name="stat_suffix[]" class="form-control" style="max-width:90px;"
                   placeholder="+" maxlength="10" value="<?= htmlspecialchars($s['suffix']) ?>">
            <input type="text" name="stat_label[]" class="form-control"
                   placeholder="Members" maxlength="80" value="<?= htmlspecialchars($s['label']) ?>">
            <button type="button" class="btn btn-outline-danger btn-sm stat-remove" title="Remove">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" id="aboutStatAddBtn" class="btn btn-outline-light btn-sm mt-3">
          <i class="bi bi-plus-lg"></i> Add Stat
        </button>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="aboutActive"
                 <?= $about['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="aboutActive">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save About
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <!-- PRICING TAB -->
    <div class="tab-pane-content d-none" data-pane="pricing">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">Pricing Section</h2>
          <div class="hint">Header text + plans with features. Featured plan gets accent button.</div>
        </div>
      </div>

      <?php if (!$pricing): ?>
        <div class="alert alert-warning">No pricing record found. Insert one in <code>pricing_section</code> table.</div>
      <?php else: ?>
      <form method="POST" class="panel" id="pricingForm">
        <input type="hidden" name="section" value="pricing">

        <div class="section-head">Header</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Label</label>
            <input type="text" name="label" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($pricing['label']) ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($pricing['title']) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Subtitle</label>
            <textarea name="subtitle" class="form-control" rows="2"><?= htmlspecialchars($pricing['subtitle']) ?></textarea>
          </div>
        </div>

        <div class="section-head">Plans</div>
        <div class="hint mb-3">Har plan ke andar features list hai.</div>

        <div id="pricingPlansWrap">
          <?php
            $planRows = $pricingPlans ?: [[
                'name'=>'', 'price'=>'', 'currency'=>'$', 'period'=>'/mo',
                'button_text'=>'Select', 'button_style'=>'ghost',
                'is_featured'=>0, 'badge_text'=>'', 'features'=>[],
            ]];
            foreach ($planRows as $pi => $plan):
              $feats = !empty($plan['features']) ? $plan['features'] : [['feature_text'=>'','is_included'=>1]];
          ?>
          <div class="plan-block" data-plan-index="<?= $pi ?>">
            <div class="plan-header">
              <span class="plan-title"><i class="bi bi-tag"></i> Plan <?= $pi+1 ?></span>
              <button type="button" class="btn btn-outline-danger btn-sm pricing-plan-remove">
                <i class="bi bi-trash"></i> Remove Plan
              </button>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" name="plan_name[]" class="form-control" maxlength="80"
                       value="<?= htmlspecialchars($plan['name']) ?>" placeholder="Basic">
              </div>
              <div class="col-md-2">
                <label class="form-label">Currency</label>
                <input type="text" name="plan_currency[]" class="form-control" maxlength="10"
                       value="<?= htmlspecialchars($plan['currency']) ?>" placeholder="$">
              </div>
              <div class="col-md-3">
                <label class="form-label">Price</label>
                <input type="text" name="plan_price[]" class="form-control" maxlength="30"
                       value="<?= htmlspecialchars($plan['price']) ?>" placeholder="49">
              </div>
              <div class="col-md-3">
                <label class="form-label">Period</label>
                <input type="text" name="plan_period[]" class="form-control" maxlength="20"
                       value="<?= htmlspecialchars($plan['period']) ?>" placeholder="/mo">
              </div>
              <div class="col-md-4">
                <label class="form-label">Button Text</label>
                <input type="text" name="plan_button_text[]" class="form-control" maxlength="80"
                       value="<?= htmlspecialchars($plan['button_text']) ?>" placeholder="Select Basic">
              </div>
              <div class="col-md-3">
                <label class="form-label">Button Style</label>
                <select name="plan_button_style[]" class="form-select">
                  <option value="ghost"  <?= $plan['button_style']==='ghost'  ? 'selected':'' ?>>Ghost (outline)</option>
                  <option value="accent" <?= $plan['button_style']==='accent' ? 'selected':'' ?>>Accent (solid gold)</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Badge (optional)</label>
                <input type="text" name="plan_badge[]" class="form-control" maxlength="50"
                       value="<?= htmlspecialchars($plan['badge_text'] ?? '') ?>" placeholder="Recommended">
              </div>
              <div class="col-md-2 d-flex align-items-end">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="plan_featured[]"
                         value="<?= $pi ?>" id="feat<?= $pi ?>"
                         <?= $plan['is_featured'] ? 'checked' : '' ?>>
                  <label class="form-check-label hint" for="feat<?= $pi ?>">Featured</label>
                </div>
              </div>
            </div>

            <div class="section-head" style="margin-top:18px; font-size:.68rem;">Features</div>
            <div class="pricing-features-wrap d-flex flex-column gap-2" data-plan="<?= $pi ?>">
              <?php foreach ($feats as $fi => $f): ?>
              <div class="d-flex gap-2 feature-row">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox"
                         name="plan_features_on[<?= $pi ?>][<?= $fi ?>]" value="1"
                         id="f<?= $pi ?>_<?= $fi ?>" <?= $f['is_included'] ? 'checked' : '' ?>>
                  <label class="form-check-label hint" for="f<?= $pi ?>_<?= $fi ?>">Included</label>
                </div>
                <input type="text" name="plan_features[<?= $pi ?>][<?= $fi ?>]"
                       class="form-control" maxlength="200"
                       placeholder="Feature text"
                       value="<?= htmlspecialchars($f['feature_text']) ?>">
                <button type="button" class="btn btn-outline-danger btn-sm feature-remove">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-outline-light btn-sm mt-2 feature-add" data-plan="<?= $pi ?>">
              <i class="bi bi-plus-lg"></i> Add Feature
            </button>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" id="pricingPlanAddBtn" class="btn btn-outline-light btn-sm mt-2">
          <i class="bi bi-plus-lg"></i> Add Plan
        </button>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="pricingActive"
                 <?= $pricing['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="pricingActive">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save Pricing
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <!-- PROGRAMS TAB -->
    <div class="tab-pane-content d-none" data-pane="programs">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">Programs Section</h2>
          <div class="hint">Program cards with image, name, description. Drag to reorder.</div>
        </div>
      </div>

      <?php if (!$programs): ?>
        <div class="alert alert-warning">No programs record found. Insert one in <code>programs_section</code> table.</div>
      <?php else: ?>
      <form method="POST" enctype="multipart/form-data" class="panel" id="programsForm">
        <input type="hidden" name="section" value="programs">

        <div class="section-head">Header</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Label</label>
            <input type="text" name="label" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($programs['label']) ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($programs['title']) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Subtitle</label>
            <textarea name="subtitle" class="form-control" rows="2"><?= htmlspecialchars($programs['subtitle']) ?></textarea>
          </div>
        </div>

        <div class="section-head">Program Cards</div>
        <div class="hint mb-3">Har card me image (upload ya URL), name, description. Drag handle se reorder.</div>

        <div id="programsItemsWrap" class="d-flex flex-column gap-2">
          <?php
            $itemRows = $programsItems ?: [[
                'id' => 0, 'name' => '', 'description' => '', 'image' => null
            ]];
            foreach ($itemRows as $item):
              $isUrl = $item['image'] && (strpos($item['image'], 'http') === 0);
          ?>
          <div class="program-block" data-item-id="<?= (int)$item['id'] ?>">
            <input type="hidden" name="item_id[]" value="<?= (int)$item['id'] ?>">
            <div class="item-header">
              <span class="item-title">
                <i class="bi bi-grip-vertical" style="cursor:grab;"></i>
                Program <?= $item['id'] ? '#'.(int)$item['id'] : '(new)' ?>
              </span>
              <button type="button" class="btn btn-outline-danger btn-sm program-remove">
                <i class="bi bi-trash"></i> Remove
              </button>
            </div>

            <div class="d-flex gap-3 mb-3 align-items-center">
              <?php if ($item['image']): ?>
                <img src="<?= htmlspecialchars($isUrl ? $item['image'] : '../'.$item['image']) ?>"
                     class="program-thumb" alt="">
              <?php else: ?>
                <div class="program-thumb-empty"><i class="bi bi-image"></i></div>
              <?php endif; ?>
              <div class="flex-grow-1">
                <label class="form-label">Image</label>
                <input type="file" name="item_image[]" class="form-control mb-1" accept="image/*">
                <input type="text" name="item_image_url[]" class="form-control"
                       placeholder="…or paste image URL"
                       value="<?= $isUrl ? htmlspecialchars($item['image']) : '' ?>">
                <?php if ($item['image']): ?>
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox"
                           name="item_keep_image[<?= (int)$item['id'] ?>]" value="1"
                           id="keep<?= (int)$item['id'] ?>" checked>
                    <label class="form-check-label hint" for="keep<?= (int)$item['id'] ?>">
                      Keep current image (uncheck + save to remove)
                    </label>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" name="item_name[]" class="form-control" maxlength="120"
                       placeholder="Strength Training"
                       value="<?= htmlspecialchars($item['name']) ?>">
              </div>
              <div class="col-md-8">
                <label class="form-label">Description</label>
                <textarea name="item_description[]" class="form-control" rows="2"
                          placeholder="Short punchy sentence about the program."><?= htmlspecialchars($item['description']) ?></textarea>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" id="programsAddBtn" class="btn btn-outline-light btn-sm mt-2">
          <i class="bi bi-plus-lg"></i> Add Program
        </button>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="programsActive"
                 <?= $programs['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="programsActive">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save Programs
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <!-- TRAINERS TAB -->
    <div class="tab-pane-content d-none" data-pane="trainers">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">Trainers Section</h2>
          <div class="hint">Coach cards with image, name, specialty and social links. Drag to reorder.</div>
        </div>
      </div>

      <?php if (!$trainers): ?>
        <div class="alert alert-warning">No trainers record found. Insert one in <code>trainers_section</code> table.</div>
      <?php else: ?>
      <form method="POST" enctype="multipart/form-data" class="panel" id="trainersForm">
        <input type="hidden" name="section" value="trainers">

        <div class="section-head">Header</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Label</label>
            <input type="text" name="label" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($trainers['label']) ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($trainers['title']) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Subtitle</label>
            <textarea name="subtitle" class="form-control" rows="2"><?= htmlspecialchars($trainers['subtitle']) ?></textarea>
          </div>
        </div>

        <div class="section-head">Trainer Cards</div>
        <div class="hint mb-3">Image (upload ya URL), name, specialty, social links. Khali socials hide ho jaate hain.</div>

        <div id="trainersItemsWrap" class="d-flex flex-column gap-2">
          <?php
            $tRows = $trainersItems ?: [[
                'id' => 0, 'name' => '', 'specialty' => '', 'image' => null,
                'instagram_url' => '', 'twitter_url' => ''
            ]];
            foreach ($tRows as $item):
              $isUrl = $item['image'] && (strpos($item['image'], 'http') === 0);
          ?>
          <div class="trainer-block" data-item-id="<?= (int)$item['id'] ?>">
            <input type="hidden" name="item_id[]" value="<?= (int)$item['id'] ?>">
            <div class="item-header">
              <span class="item-title">
                <i class="bi bi-grip-vertical" style="cursor:grab;"></i>
                Trainer <?= $item['id'] ? '#'.(int)$item['id'] : '(new)' ?>
              </span>
              <button type="button" class="btn btn-outline-danger btn-sm trainer-remove">
                <i class="bi bi-trash"></i> Remove
              </button>
            </div>

            <div class="d-flex gap-3 mb-3 align-items-center">
              <?php if ($item['image']): ?>
                <img src="<?= htmlspecialchars($isUrl ? $item['image'] : '../'.$item['image']) ?>"
                     class="trainer-thumb" alt="">
              <?php else: ?>
                <div class="trainer-thumb-empty"><i class="bi bi-person"></i></div>
              <?php endif; ?>
              <div class="flex-grow-1">
                <label class="form-label">Image</label>
                <input type="file" name="item_image[]" class="form-control mb-1" accept="image/*">
                <input type="text" name="item_image_url[]" class="form-control"
                       placeholder="…or paste image URL"
                       value="<?= $isUrl ? htmlspecialchars($item['image']) : '' ?>">
                <?php if ($item['image']): ?>
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox"
                           name="item_keep_image[<?= (int)$item['id'] ?>]" value="1"
                           id="tkeep<?= (int)$item['id'] ?>" checked>
                    <label class="form-check-label hint" for="tkeep<?= (int)$item['id'] ?>">
                      Keep current image (uncheck + save to remove)
                    </label>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" name="item_name[]" class="form-control" maxlength="120"
                       placeholder="Marcus Cole"
                       value="<?= htmlspecialchars($item['name']) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Specialty</label>
                <input type="text" name="item_specialty[]" class="form-control" maxlength="120"
                       placeholder="Strength & Conditioning"
                       value="<?= htmlspecialchars($item['specialty'] ?? '') ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label">Instagram URL</label>
                <input type="text" name="item_instagram[]" class="form-control"
                       placeholder="https://instagram.com/…"
                       value="<?= htmlspecialchars($item['instagram_url'] ?? '') ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label">Twitter / X URL</label>
                <input type="text" name="item_twitter[]" class="form-control"
                       placeholder="https://x.com/…"
                       value="<?= htmlspecialchars($item['twitter_url'] ?? '') ?>">
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" id="trainersAddBtn" class="btn btn-outline-light btn-sm mt-2">
          <i class="bi bi-plus-lg"></i> Add Trainer
        </button>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="trainersActive"
                 <?= $trainers['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="trainersActive">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save Trainers
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <!-- FACILITIES TAB -->
    <div class="tab-pane-content d-none" data-pane="facilities">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">Facilities Section</h2>
          <div class="hint">Left: header + bullet list. Right: 1 large image + 2 small images grid.</div>
        </div>
      </div>

      <?php if (!$facilities): ?>
        <div class="alert alert-warning">No facilities record found. Insert one in <code>facilities_section</code> table.</div>
      <?php else: ?>
      <form method="POST" enctype="multipart/form-data" class="panel" id="facilitiesForm">
        <input type="hidden" name="section" value="facilities">

        <div class="section-head">Header</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Label</label>
            <input type="text" name="label" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($facilities['label']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Title Line 1</label>
            <input type="text" name="title_line_1" class="form-control" maxlength="150"
                   value="<?= htmlspecialchars($facilities['title_line_1']) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Title Line 2</label>
            <input type="text" name="title_line_2" class="form-control" maxlength="150"
                   value="<?= htmlspecialchars($facilities['title_line_2']) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Subtitle</label>
            <textarea name="subtitle" class="form-control" rows="2"><?= htmlspecialchars($facilities['subtitle']) ?></textarea>
          </div>
        </div>

        <div class="section-head">Facility Items</div>
        <div class="hint mb-3">Icon (Bootstrap Icons), title, description. Drag handle se reorder.</div>

        <div id="facilitiesItemsWrap" class="d-flex flex-column gap-2">
          <?php
            $fRows = $facilitiesItems ?: [[
                'icon' => 'bi bi-check', 'title' => '', 'description' => ''
            ]];
            foreach ($fRows as $idx => $item):
          ?>
          <div class="facility-block">
            <div class="item-header">
              <span class="item-title">
                <i class="bi bi-grip-vertical" style="cursor:grab;"></i>
                Facility <?= $idx + 1 ?>
              </span>
              <button type="button" class="btn btn-outline-danger btn-sm facility-remove">
                <i class="bi bi-trash"></i> Remove
              </button>
            </div>

            <div class="d-flex gap-3 align-items-start">
              <span class="facility-icon-preview" data-icon-preview>
                <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
              </span>
              <div class="flex-grow-1">
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label">Icon class</label>
                    <input type="text" name="item_icon[]" class="form-control facility-icon-input"
                           maxlength="100" placeholder="bi bi-lightning-charge"
                           value="<?= htmlspecialchars($item['icon']) ?>">
                    <div class="hint mt-1">Reference: <a href="https://icons.getbootstrap.com/" target="_blank" class="text-decoration-none" style="color:var(--accent);">icons.getbootstrap.com</a></div>
                  </div>
                  <div class="col-md-8">
                    <label class="form-label">Title</label>
                    <input type="text" name="item_title[]" class="form-control" maxlength="150"
                           placeholder="Premium Equipment"
                           value="<?= htmlspecialchars($item['title']) ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="item_description[]" class="form-control" rows="2"
                              placeholder="Rogue, Hammer Strength, and Eleiko platforms and free weights."><?= htmlspecialchars($item['description']) ?></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" id="facilitiesAddBtn" class="btn btn-outline-light btn-sm mt-2">
          <i class="bi bi-plus-lg"></i> Add Facility Item
        </button>

        <div class="section-head">Images</div>
        <div class="hint mb-3">1 large (left, col-7) + 2 small (right column). Upload ya URL paste.</div>

        <div class="facilities-img-grid">
          <?php foreach (['image_main' => 'Main (Large)', 'image_small_1' => 'Small Top', 'image_small_2' => 'Small Bottom'] as $key => $labelText):
            $curImg = $facilities[$key] ?? null;
            $isUrl = $curImg && strpos($curImg, 'http') === 0;
            $isLarge = $key === 'image_main';
          ?>
          <div class="facilities-img-card <?= $isLarge ? 'main' : '' ?>">
            <div class="hint mb-2"><strong style="color:var(--accent);"><?= $labelText ?></strong></div>
            <?php if ($curImg): ?>
              <img src="<?= htmlspecialchars($isUrl ? $curImg : '../'.$curImg) ?>" class="preview" alt="">
            <?php else: ?>
              <div class="preview" style="display:flex;align-items:center;justify-content:center;color:#555;font-size:2rem;">
                <i class="bi bi-image"></i>
              </div>
            <?php endif; ?>

            <input type="file" name="<?= $key ?>" class="form-control mb-2" accept="image/*">
            <input type="text" name="<?= $key ?>_url" class="form-control mb-2"
                   placeholder="…or paste URL"
                   value="<?= $isUrl ? htmlspecialchars($curImg) : '' ?>">
            <?php if ($curImg): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="<?= $key ?>_clear" value="1" id="clear_<?= $key ?>">
                <label class="form-check-label hint" for="clear_<?= $key ?>">Clear this image</label>
              </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="facilitiesActive"
                 <?= $facilities['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="facilitiesActive">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save Facilities
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <!-- TRANSFORMATIONS TAB -->
    <div class="tab-pane-content d-none" data-pane="transformations">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">Transformations Section</h2>
          <div class="hint">Result cards with image, name, duration label, result line and quote. Drag to reorder.</div>
        </div>
      </div>

      <?php if (!$transformations): ?>
        <div class="alert alert-warning">No transformations record found. Insert one in <code>transformations_section</code> table.</div>
      <?php else: ?>
      <form method="POST" enctype="multipart/form-data" class="panel" id="transformationsForm">
        <input type="hidden" name="section" value="transformations">

        <div class="section-head">Header</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Label</label>
            <input type="text" name="label" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($transformations['label']) ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($transformations['title']) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Subtitle</label>
            <textarea name="subtitle" class="form-control" rows="2"><?= htmlspecialchars($transformations['subtitle']) ?></textarea>
          </div>
        </div>

        <div class="section-head">Transformation Cards</div>
        <div class="hint mb-3">
          Image (upload ya URL), name, duration label (corner badge), result line, quote.
          Khali duration/result/quote hide ho jaate hain.
        </div>

        <div id="transformationsItemsWrap" class="d-flex flex-column gap-2">
          <?php
            $trRows = $transformationsItems ?: [[
                'id' => 0, 'name' => '', 'duration_label' => '', 'result_line' => '',
                'quote' => '', 'image' => null
            ]];
            foreach ($trRows as $item):
              $isUrl = $item['image'] && (strpos($item['image'], 'http') === 0);
          ?>
          <div class="transformation-block" data-item-id="<?= (int)$item['id'] ?>">
            <input type="hidden" name="item_id[]" value="<?= (int)$item['id'] ?>">
            <div class="item-header">
              <span class="item-title">
                <i class="bi bi-grip-vertical" style="cursor:grab;"></i>
                Transformation <?= $item['id'] ? '#'.(int)$item['id'] : '(new)' ?>
              </span>
              <button type="button" class="btn btn-outline-danger btn-sm transformation-remove">
                <i class="bi bi-trash"></i> Remove
              </button>
            </div>

            <div class="d-flex gap-3 mb-3 align-items-center">
              <?php if ($item['image']): ?>
                <img src="<?= htmlspecialchars($isUrl ? $item['image'] : '../'.$item['image']) ?>"
                     class="transformation-thumb" alt="">
              <?php else: ?>
                <div class="transformation-thumb-empty"><i class="bi bi-image"></i></div>
              <?php endif; ?>
              <div class="flex-grow-1">
                <label class="form-label">Image</label>
                <input type="file" name="item_image[]" class="form-control mb-1" accept="image/*">
                <input type="text" name="item_image_url[]" class="form-control"
                       placeholder="…or paste image URL"
                       value="<?= $isUrl ? htmlspecialchars($item['image']) : '' ?>">
                <?php if ($item['image']): ?>
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox"
                           name="item_keep_image[<?= (int)$item['id'] ?>]" value="1"
                           id="trkeep<?= (int)$item['id'] ?>" checked>
                    <label class="form-check-label hint" for="trkeep<?= (int)$item['id'] ?>">
                      Keep current image (uncheck + save to remove)
                    </label>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" name="item_name[]" class="form-control" maxlength="120"
                       placeholder="Sarah K."
                       value="<?= htmlspecialchars($item['name']) ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Duration Label</label>
                <input type="text" name="item_duration[]" class="form-control" maxlength="60"
                       placeholder="12 Weeks"
                       value="<?= htmlspecialchars($item['duration_label'] ?? '') ?>">
              </div>
              <div class="col-md-5">
                <label class="form-label">Result Line</label>
                <input type="text" name="item_result[]" class="form-control" maxlength="200"
                       placeholder="Lost 18 kg · Gained confidence"
                       value="<?= htmlspecialchars($item['result_line'] ?? '') ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Quote</label>
                <textarea name="item_quote[]" class="form-control" rows="2"
                          placeholder="“The coaches didn't just train my body. They rewired my mindset.”"><?= htmlspecialchars($item['quote'] ?? '') ?></textarea>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" id="transformationsAddBtn" class="btn btn-outline-light btn-sm mt-2">
          <i class="bi bi-plus-lg"></i> Add Transformation
        </button>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="transformationsActive"
                 <?= $transformations['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="transformationsActive">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save Transformations
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <!-- TESTIMONIALS TAB -->
    <div class="tab-pane-content d-none" data-pane="testimonials">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">Testimonials Section</h2>
          <div class="hint">Member review cards with avatar, name, role, quote and star rating. Drag to reorder.</div>
        </div>
      </div>

      <?php if (!$testimonials): ?>
        <div class="alert alert-warning">No testimonials record found. Insert one in <code>testimonials_section</code> table.</div>
      <?php else: ?>
      <form method="POST" enctype="multipart/form-data" class="panel" id="testimonialsForm">
        <input type="hidden" name="section" value="testimonials">

        <div class="section-head">Header</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Label</label>
            <input type="text" name="label" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($testimonials['label'] ?? '') ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($testimonials['title'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Subtitle</label>
            <textarea name="subtitle" class="form-control" rows="2"><?= htmlspecialchars($testimonials['subtitle'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="section-head">Testimonial Cards</div>
        <div class="hint mb-3">
          Avatar (upload ya URL), name, role (e.g. "Member since 2021"), quote, and star rating (1–5).
          Khali role/quote hide ho jaate hain.
        </div>

        <div id="testimonialsItemsWrap" class="d-flex flex-column gap-2">
          <?php
            $tmRows = $testimonialsItems ?: [[
                'id' => 0, 'name' => '', 'role' => '', 'quote' => '',
                'stars' => 5, 'avatar' => null
            ]];
            foreach ($tmRows as $item):
              $isUrl = $item['avatar'] && (strpos($item['avatar'], 'http') === 0);
              $curStars = (int)($item['stars'] ?? 5);
          ?>
          <div class="testimonial-block" data-item-id="<?= (int)$item['id'] ?>">
            <input type="hidden" name="item_id[]" value="<?= (int)$item['id'] ?>">
            <div class="item-header">
              <span class="item-title">
                <i class="bi bi-grip-vertical" style="cursor:grab;"></i>
                Testimonial <?= $item['id'] ? '#'.(int)$item['id'] : '(new)' ?>
              </span>
              <button type="button" class="btn btn-outline-danger btn-sm testimonial-remove">
                <i class="bi bi-trash"></i> Remove
              </button>
            </div>

            <div class="d-flex gap-3 mb-3 align-items-center">
              <?php if ($item['avatar']): ?>
                <img src="<?= htmlspecialchars($isUrl ? $item['avatar'] : '../'.$item['avatar']) ?>"
                     class="testimonial-thumb" alt="">
              <?php else: ?>
                <div class="testimonial-thumb-empty"><i class="bi bi-person"></i></div>
              <?php endif; ?>
              <div class="flex-grow-1">
                <label class="form-label">Avatar</label>
                <input type="file" name="item_avatar[]" class="form-control mb-1" accept="image/*">
                <input type="text" name="item_avatar_url[]" class="form-control"
                       placeholder="…or paste image URL"
                       value="<?= $isUrl ? htmlspecialchars($item['avatar']) : '' ?>">
                <?php if ($item['avatar']): ?>
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox"
                           name="item_keep_image[<?= (int)$item['id'] ?>]" value="1"
                           id="tmkeep<?= (int)$item['id'] ?>" checked>
                    <label class="form-check-label hint" for="tmkeep<?= (int)$item['id'] ?>">
                      Keep current avatar (uncheck + save to remove)
                    </label>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" name="item_name[]" class="form-control" maxlength="120"
                       placeholder="James Thornton"
                       value="<?= htmlspecialchars($item['name']) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Role</label>
                <input type="text" name="item_role[]" class="form-control" maxlength="120"
                       placeholder="Member since 2021"
                       value="<?= htmlspecialchars($item['role'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Stars (1–5)</label>
                <select name="item_stars[]" class="form-select star-select">
                  <?php for ($s = 5; $s >= 1; $s--): ?>
                    <option value="<?= $s ?>" <?= $curStars === $s ? 'selected' : '' ?>>
                      <?= str_repeat('★', $s) . str_repeat('☆', 5 - $s) ?> (<?= $s ?>)
                    </option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Quote</label>
                <textarea name="item_quote[]" class="form-control" rows="3"
                          placeholder="“I've trained at gyms on three continents…”"><?= htmlspecialchars($item['quote'] ?? '') ?></textarea>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" id="testimonialsAddBtn" class="btn btn-outline-light btn-sm mt-2">
          <i class="bi bi-plus-lg"></i> Add Testimonial
        </button>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="testimonialsActive"
                 <?= !empty($testimonials['is_active']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="testimonialsActive">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save Testimonials
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <!-- SCHEDULE TAB -->
    <div class="tab-pane-content d-none" data-pane="schedule">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">Schedule Section</h2>
          <div class="hint">Weekly timetable. Rows = time slots (editable), columns = Monday → Saturday. Leave a cell empty to render a dash (—).</div>
        </div>
      </div>

      <?php if (!$schedule): ?>
        <div class="alert alert-warning">No schedule record found. Insert one in <code>schedule_section</code> table.</div>
      <?php else: ?>
      <form method="POST" class="panel" id="scheduleForm">
        <input type="hidden" name="section" value="schedule">

        <div class="section-head">Header</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Label</label>
            <input type="text" name="label" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($schedule['label'] ?? '') ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($schedule['title'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Subtitle</label>
            <textarea name="subtitle" class="form-control" rows="2"><?= htmlspecialchars($schedule['subtitle'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="section-head">Timetable Grid</div>
        <div class="hint mb-3">
          Each row is a time slot. Columns are Monday through Saturday (Sunday is hidden on the frontend).
          Leave a class cell empty to render a dash (—).
        </div>

        <div class="schedule-matrix-wrap">
          <table class="schedule-matrix" id="scheduleMatrix">
            <thead>
              <tr>
                <th style="width:140px;">Time</th>
                <th class="schedule-day-col">Monday</th>
                <th class="schedule-day-col">Tuesday</th>
                <th class="schedule-day-col">Wednesday</th>
                <th class="schedule-day-col">Thursday</th>
                <th class="schedule-day-col">Friday</th>
                <th class="schedule-day-col">Saturday</th>
                <th style="width:70px;"></th>
              </tr>
            </thead>
            <tbody id="scheduleRowsBody">
              <?php
                $sRows = $scheduleTimes ?: [['id' => 0, 'time_label' => '']];
                foreach ($sRows as $ri => $t):
                  $tid = (int)$t['id'];
              ?>
              <tr class="schedule-row">
                <td>
                  <input type="text" name="row_time[]" class="form-control row-time-input"
                         maxlength="20" placeholder="06:00"
                         value="<?= htmlspecialchars($t['time_label']) ?>">
                </td>
                <?php for ($day = 1; $day <= 6; $day++):
                  $val = $tid && isset($scheduleCellsByTime[$tid][$day]) ? $scheduleCellsByTime[$tid][$day] : '';
                ?>
                <td>
                  <input type="text"
                         name="cell[<?= $ri ?>][<?= $day ?>]"
                         class="form-control cell-input"
                         maxlength="100" placeholder="—"
                         value="<?= htmlspecialchars($val) ?>">
                </td>
                <?php endfor; ?>
                <td class="text-center">
                  <button type="button" class="btn btn-outline-danger btn-sm row-remove-btn" title="Remove row">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <button type="button" id="scheduleAddRowBtn" class="btn btn-outline-light btn-sm mt-2">
          <i class="bi bi-plus-lg"></i> Add Time Slot
        </button>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="scheduleActive"
                 <?= !empty($schedule['is_active']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="scheduleActive">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save Schedule
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <!-- JOURNEY TAB -->
    <div class="tab-pane-content d-none" data-pane="journey">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">Journey Section</h2>
          <div class="hint">"Your Journey Starts Here" — numbered steps + bottom CTA button.</div>
        </div>
      </div>

      <?php if (!$journey): ?>
        <div class="alert alert-warning">No journey record found. Insert one in <code>journey_section</code> table.</div>
      <?php else: ?>
      <form method="POST" class="panel" id="journeyForm">
        <input type="hidden" name="section" value="journey">

        <div class="section-head">Header</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Label</label>
            <input type="text" name="label" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($journey['label'] ?? '') ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($journey['title'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Subtitle</label>
            <textarea name="subtitle" class="form-control" rows="2"><?= htmlspecialchars($journey['subtitle'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="section-head">Bottom CTA</div>
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label">Button Text</label>
            <input type="text" name="cta_text" class="form-control" maxlength="120"
                   value="<?= htmlspecialchars($journey['cta_text'] ?? '') ?>"
                   placeholder="Begin Your Transformation">
          </div>
          <div class="col-md-4">
            <label class="form-label">Button Link</label>
            <input type="text" name="cta_link" class="form-control" maxlength="255"
                   value="<?= htmlspecialchars($journey['cta_link'] ?? '') ?>"
                   placeholder="#pricing">
          </div>
          <div class="col-md-3">
            <label class="form-label">Icon class</label>
            <input type="text" name="cta_icon" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($journey['cta_icon'] ?? '') ?>"
                   placeholder="bi bi-arrow-right">
            <div class="hint mt-1">
              Reference: <a href="https://icons.getbootstrap.com/" target="_blank" class="text-decoration-none" style="color:var(--accent);">icons.getbootstrap.com</a>
            </div>
          </div>
        </div>

        <div class="section-head">Steps</div>
        <div class="hint mb-3">
          Number badge (e.g. 01), title and description. Leave number empty to auto-number. Drag the grip to reorder.
        </div>

        <div id="journeyStepsWrap" class="d-flex flex-column gap-2">
          <?php
            $jRows = $journeySteps ?: [[
                'number' => '', 'title' => '', 'description' => ''
            ]];
            foreach ($jRows as $idx => $step):
          ?>
          <div class="journey-block">
            <div class="item-header">
              <span class="item-title">
                <i class="bi bi-grip-vertical" style="cursor:grab;"></i>
                Step <?= $idx + 1 ?>
              </span>
              <button type="button" class="btn btn-outline-danger btn-sm journey-step-remove">
                <i class="bi bi-trash"></i> Remove
              </button>
            </div>

            <div class="d-flex gap-3 align-items-start">
              <span class="journey-num-preview" data-num-preview>
                <?= htmlspecialchars($step['number'] ?: str_pad((string)($idx+1), 2, '0', STR_PAD_LEFT)) ?>
              </span>
              <div class="flex-grow-1">
                <div class="row g-3">
                  <div class="col-md-2">
                    <label class="form-label">Number</label>
                    <input type="text" name="step_number[]" class="form-control journey-number-input"
                           maxlength="10" placeholder="01"
                           value="<?= htmlspecialchars($step['number']) ?>">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Title</label>
                    <input type="text" name="step_title[]" class="form-control" maxlength="150"
                           placeholder="Choose Your Plan"
                           value="<?= htmlspecialchars($step['title']) ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Description</label>
                    <textarea name="step_description[]" class="form-control" rows="2"
                              placeholder="Short description of this step."><?= htmlspecialchars($step['description']) ?></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" id="journeyStepAddBtn" class="btn btn-outline-light btn-sm mt-2">
          <i class="bi bi-plus-lg"></i> Add Step
        </button>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="journeyActive"
                 <?= !empty($journey['is_active']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="journeyActive">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save Journey
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <!-- FAQ TAB -->
    <div class="tab-pane-content d-none" data-pane="faq">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">FAQ Section</h2>
          <div class="hint">Left column = section label + title + subtitle. Right column = accordion of Q&A pairs.</div>
        </div>
      </div>

      <?php if (!$faq): ?>
        <div class="alert alert-warning">No FAQ record found. Insert one in <code>faq_section</code> table.</div>
      <?php else: ?>
      <form method="POST" class="panel" id="faqForm">
        <input type="hidden" name="section" value="faq">

        <div class="section-head">Header</div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Label</label>
            <input type="text" name="label" class="form-control" maxlength="100"
                   value="<?= htmlspecialchars($faq['label'] ?? '') ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($faq['title'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Subtitle</label>
            <textarea name="subtitle" class="form-control" rows="2"><?= htmlspecialchars($faq['subtitle'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="section-head">Questions & Answers</div>
        <div class="hint mb-3">
          Drag the grip to reorder. First item is expanded by default on the frontend; the rest are collapsed.
        </div>

        <div id="faqItemsWrap" class="d-flex flex-column gap-2">
          <?php
            $faqRows = $faqItems ?: [['question' => '', 'answer' => '']];
            foreach ($faqRows as $idx => $item):
          ?>
          <div class="faq-block">
            <div class="item-header">
              <span class="item-title">
                <i class="bi bi-grip-vertical" style="cursor:grab;"></i>
                Question <?= $idx + 1 ?>
              </span>
              <button type="button" class="btn btn-outline-danger btn-sm faq-remove">
                <i class="bi bi-trash"></i> Remove
              </button>
            </div>

            <div class="d-flex gap-3 align-items-start">
              <span class="faq-index-chip" data-index-chip>
                <?= str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT) ?>
              </span>
              <div class="flex-grow-1">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label">Question</label>
                    <input type="text" name="item_question[]" class="form-control" maxlength="255"
                           placeholder="Do I need experience to join?"
                           value="<?= htmlspecialchars($item['question']) ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Answer</label>
                    <textarea name="item_answer[]" class="form-control" rows="3"
                              placeholder="Write a clear, helpful answer here."><?= htmlspecialchars($item['answer'] ?? '') ?></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <button type="button" id="faqAddBtn" class="btn btn-outline-light btn-sm mt-2">
          <i class="bi bi-plus-lg"></i> Add Question
        </button>

        <div class="section-head">Status</div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="faqActive"
                 <?= !empty($faq['is_active']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="faqActive">Section visible on site</label>
        </div>

        <div class="sticky-save d-flex justify-content-end gap-2">
          <a href="index.php" class="btn btn-outline-light">Reset</a>
          <button type="submit" class="btn btn-accent px-4">
            <i class="bi bi-check2"></i> Save FAQ
          </button>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <!-- PLACEHOLDER PANES -->
    <?php foreach ($tabs as $key => $t): if ($t['ready']) continue; ?>
      <div class="tab-pane-content d-none" data-pane="<?= $key ?>">
        <div class="panel soon-box">
          <i class="bi <?= $t['icon'] ?>"></i>
          <h4 class="mt-3"><?= $t['label'] ?> Section</h4>
          <p class="hint">Coming soon.</p>
        </div>
      </div>
    <?php endforeach; ?>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
/* Tab switching */
document.querySelectorAll('.nav-tab-btn[data-tab]').forEach(btn => {
  btn.addEventListener('click', () => {
    if (btn.disabled) return;
    document.querySelectorAll('.nav-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const target = btn.dataset.tab;
    document.querySelectorAll('.tab-pane-content').forEach(p => {
      p.classList.toggle('d-none', p.dataset.pane !== target);
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
});

/* Marquee rows */
(function () {
  const wrap = document.getElementById('marqueeItemsWrap');
  const addBtn = document.getElementById('marqueeAddBtn');
  if (!wrap || !addBtn) return;
  function rowTemplate() {
    return `
      <div class="d-flex gap-2 marquee-row">
        <span class="d-flex align-items-center px-2 text-muted" style="min-width:32px; cursor:grab;">
          <i class="bi bi-grip-vertical"></i>
        </span>
        <input type="text" name="labels[]" class="form-control" maxlength="80" placeholder="e.g. Strength" value="">
        <button type="button" class="btn btn-outline-danger btn-sm marquee-remove" title="Remove">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>`;
  }
  addBtn.addEventListener('click', () => {
    wrap.insertAdjacentHTML('beforeend', rowTemplate());
    wrap.lastElementChild.querySelector('input').focus();
  });
  wrap.addEventListener('click', (e) => {
    const btn = e.target.closest('.marquee-remove');
    if (!btn) return;
    const rows = wrap.querySelectorAll('.marquee-row');
    if (rows.length <= 1) { btn.previousElementSibling.value = ''; return; }
    btn.closest('.marquee-row').remove();
  });
  if (window.Sortable) {
    Sortable.create(wrap, { animation: 150, handle: '.bi-grip-vertical', ghostClass: 'opacity-50' });
  }
})();

/* About stats */
(function () {
  const wrap = document.getElementById('aboutStatsWrap');
  const addBtn = document.getElementById('aboutStatAddBtn');
  if (!wrap || !addBtn) return;
  function rowTemplate() {
    return `
      <div class="d-flex gap-2 stat-row">
        <input type="number" name="stat_number[]" class="form-control" style="max-width:120px;" placeholder="0" value="">
        <input type="text" name="stat_suffix[]" class="form-control" style="max-width:90px;" placeholder="+" maxlength="10" value="+">
        <input type="text" name="stat_label[]" class="form-control" placeholder="Label" maxlength="80" value="">
        <button type="button" class="btn btn-outline-danger btn-sm stat-remove" title="Remove">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>`;
  }
  addBtn.addEventListener('click', () => {
    wrap.insertAdjacentHTML('beforeend', rowTemplate());
    wrap.lastElementChild.querySelector('input').focus();
  });
  wrap.addEventListener('click', (e) => {
    const btn = e.target.closest('.stat-remove');
    if (!btn) return;
    const rows = wrap.querySelectorAll('.stat-row');
    if (rows.length <= 1) { rows[0].querySelectorAll('input').forEach(i => i.value = ''); return; }
    btn.closest('.stat-row').remove();
  });
})();

/* Pricing plans + features */
(function () {
  const wrap = document.getElementById('pricingPlansWrap');
  const addPlanBtn = document.getElementById('pricingPlanAddBtn');
  if (!wrap || !addPlanBtn) return;

  function nextPlanIndex() {
    const blocks = wrap.querySelectorAll('.plan-block');
    if (!blocks.length) return 0;
    return Math.max(...Array.from(blocks).map(b => parseInt(b.dataset.planIndex, 10) || 0)) + 1;
  }
  function featureTemplate(pi, fi) {
    return `
      <div class="d-flex gap-2 feature-row">
        <div class="form-check">
          <input class="form-check-input" type="checkbox"
                 name="plan_features_on[${pi}][${fi}]" value="1" id="f${pi}_${fi}" checked>
          <label class="form-check-label hint" for="f${pi}_${fi}">Included</label>
        </div>
        <input type="text" name="plan_features[${pi}][${fi}]"
               class="form-control" maxlength="200" placeholder="Feature text" value="">
        <button type="button" class="btn btn-outline-danger btn-sm feature-remove">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>`;
  }
  function planTemplate(pi) {
    return `
      <div class="plan-block" data-plan-index="${pi}">
        <div class="plan-header">
          <span class="plan-title"><i class="bi bi-tag"></i> Plan ${pi+1}</span>
          <button type="button" class="btn btn-outline-danger btn-sm pricing-plan-remove">
            <i class="bi bi-trash"></i> Remove Plan
          </button>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="plan_name[]" class="form-control" maxlength="80" placeholder="Basic" value="">
          </div>
          <div class="col-md-2">
            <label class="form-label">Currency</label>
            <input type="text" name="plan_currency[]" class="form-control" maxlength="10" placeholder="$" value="$">
          </div>
          <div class="col-md-3">
            <label class="form-label">Price</label>
            <input type="text" name="plan_price[]" class="form-control" maxlength="30" placeholder="49" value="">
          </div>
          <div class="col-md-3">
            <label class="form-label">Period</label>
            <input type="text" name="plan_period[]" class="form-control" maxlength="20" placeholder="/mo" value="/mo">
          </div>
          <div class="col-md-4">
            <label class="form-label">Button Text</label>
            <input type="text" name="plan_button_text[]" class="form-control" maxlength="80" placeholder="Select" value="Select">
          </div>
          <div class="col-md-3">
            <label class="form-label">Button Style</label>
            <select name="plan_button_style[]" class="form-select">
              <option value="ghost">Ghost (outline)</option>
              <option value="accent">Accent (solid gold)</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Badge (optional)</label>
            <input type="text" name="plan_badge[]" class="form-control" maxlength="50" placeholder="Recommended" value="">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="plan_featured[]" value="${pi}" id="feat${pi}">
              <label class="form-check-label hint" for="feat${pi}">Featured</label>
            </div>
          </div>
        </div>
        <div class="section-head" style="margin-top:18px; font-size:.68rem;">Features</div>
        <div class="pricing-features-wrap d-flex flex-column gap-2" data-plan="${pi}">
          ${featureTemplate(pi, 0)}
        </div>
        <button type="button" class="btn btn-outline-light btn-sm mt-2 feature-add" data-plan="${pi}">
          <i class="bi bi-plus-lg"></i> Add Feature
        </button>
      </div>`;
  }

  addPlanBtn.addEventListener('click', () => {
    const pi = nextPlanIndex();
    wrap.insertAdjacentHTML('beforeend', planTemplate(pi));
    wrap.lastElementChild.querySelector('input').focus();
  });

  wrap.addEventListener('click', (e) => {
    const planRm = e.target.closest('.pricing-plan-remove');
    if (planRm) {
      const blocks = wrap.querySelectorAll('.plan-block');
      if (blocks.length <= 1) { alert('At least one plan required.'); return; }
      planRm.closest('.plan-block').remove();
      return;
    }
    const featAdd = e.target.closest('.feature-add');
    if (featAdd) {
      const block = featAdd.closest('.plan-block');
      const pi = block.dataset.planIndex;
      const fwrap = block.querySelector('.pricing-features-wrap');
      const fi = fwrap.querySelectorAll('.feature-row').length;
      fwrap.insertAdjacentHTML('beforeend', featureTemplate(pi, fi));
      fwrap.lastElementChild.querySelector('input[type="text"]').focus();
      return;
    }
    const featRm = e.target.closest('.feature-remove');
    if (featRm) {
      const fwrap = featRm.closest('.pricing-features-wrap');
      if (fwrap.querySelectorAll('.feature-row').length <= 1) {
        featRm.previousElementSibling.value = '';
        return;
      }
      featRm.closest('.feature-row').remove();
    }
  });
})();

/* Programs */
(function () {
  const wrap = document.getElementById('programsItemsWrap');
  const addBtn = document.getElementById('programsAddBtn');
  if (!wrap || !addBtn) return;
  function template() {
    return `
      <div class="program-block" data-item-id="0">
        <input type="hidden" name="item_id[]" value="0">
        <div class="item-header">
          <span class="item-title"><i class="bi bi-grip-vertical" style="cursor:grab;"></i> Program (new)</span>
          <button type="button" class="btn btn-outline-danger btn-sm program-remove"><i class="bi bi-trash"></i> Remove</button>
        </div>
        <div class="d-flex gap-3 mb-3 align-items-center">
          <div class="program-thumb-empty"><i class="bi bi-image"></i></div>
          <div class="flex-grow-1">
            <label class="form-label">Image</label>
            <input type="file" name="item_image[]" class="form-control mb-1" accept="image/*">
            <input type="text" name="item_image_url[]" class="form-control" placeholder="…or paste image URL" value="">
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="item_name[]" class="form-control" maxlength="120" placeholder="Strength Training" value="">
          </div>
          <div class="col-md-8">
            <label class="form-label">Description</label>
            <textarea name="item_description[]" class="form-control" rows="2" placeholder="Short description."></textarea>
          </div>
        </div>
      </div>`;
  }
  addBtn.addEventListener('click', () => {
    wrap.insertAdjacentHTML('beforeend', template());
    wrap.lastElementChild.querySelector('input[name="item_name[]"]').focus();
  });
  wrap.addEventListener('click', (e) => {
    const rm = e.target.closest('.program-remove');
    if (!rm) return;
    const rows = wrap.querySelectorAll('.program-block');
    if (rows.length <= 1) { alert('At least one program required.'); return; }
    rm.closest('.program-block').remove();
  });
  if (window.Sortable) {
    Sortable.create(wrap, { animation: 150, handle: '.bi-grip-vertical', ghostClass: 'opacity-50' });
  }
})();

/* Trainers */
(function () {
  const wrap = document.getElementById('trainersItemsWrap');
  const addBtn = document.getElementById('trainersAddBtn');
  if (!wrap || !addBtn) return;
  function template() {
    return `
      <div class="trainer-block" data-item-id="0">
        <input type="hidden" name="item_id[]" value="0">
        <div class="item-header">
          <span class="item-title"><i class="bi bi-grip-vertical" style="cursor:grab;"></i> Trainer (new)</span>
          <button type="button" class="btn btn-outline-danger btn-sm trainer-remove"><i class="bi bi-trash"></i> Remove</button>
        </div>
        <div class="d-flex gap-3 mb-3 align-items-center">
          <div class="trainer-thumb-empty"><i class="bi bi-person"></i></div>
          <div class="flex-grow-1">
            <label class="form-label">Image</label>
            <input type="file" name="item_image[]" class="form-control mb-1" accept="image/*">
            <input type="text" name="item_image_url[]" class="form-control" placeholder="…or paste image URL" value="">
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="item_name[]" class="form-control" maxlength="120" placeholder="Marcus Cole" value="">
          </div>
          <div class="col-md-4">
            <label class="form-label">Specialty</label>
            <input type="text" name="item_specialty[]" class="form-control" maxlength="120" placeholder="Strength & Conditioning" value="">
          </div>
          <div class="col-md-2">
            <label class="form-label">Instagram URL</label>
            <input type="text" name="item_instagram[]" class="form-control" placeholder="https://instagram.com/…" value="">
          </div>
          <div class="col-md-2">
            <label class="form-label">Twitter / X URL</label>
            <input type="text" name="item_twitter[]" class="form-control" placeholder="https://x.com/…" value="">
          </div>
        </div>
      </div>`;
  }
  addBtn.addEventListener('click', () => {
    wrap.insertAdjacentHTML('beforeend', template());
    wrap.lastElementChild.querySelector('input[name="item_name[]"]').focus();
  });
  wrap.addEventListener('click', (e) => {
    const rm = e.target.closest('.trainer-remove');
    if (!rm) return;
    const rows = wrap.querySelectorAll('.trainer-block');
    if (rows.length <= 1) { alert('At least one trainer required.'); return; }
    rm.closest('.trainer-block').remove();
  });
  if (window.Sortable) {
    Sortable.create(wrap, { animation: 150, handle: '.bi-grip-vertical', ghostClass: 'opacity-50' });
  }
})();

/* Facilities items + icon preview */
(function () {
  const wrap = document.getElementById('facilitiesItemsWrap');
  const addBtn = document.getElementById('facilitiesAddBtn');
  if (!wrap || !addBtn) return;
  function template(index) {
    return `
      <div class="facility-block">
        <div class="item-header">
          <span class="item-title"><i class="bi bi-grip-vertical" style="cursor:grab;"></i> Facility ${index+1}</span>
          <button type="button" class="btn btn-outline-danger btn-sm facility-remove"><i class="bi bi-trash"></i> Remove</button>
        </div>
        <div class="d-flex gap-3 align-items-start">
          <span class="facility-icon-preview" data-icon-preview>
            <i class="bi bi-check"></i>
          </span>
          <div class="flex-grow-1">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Icon class</label>
                <input type="text" name="item_icon[]" class="form-control facility-icon-input" maxlength="100" placeholder="bi bi-lightning-charge" value="bi bi-check">
              </div>
              <div class="col-md-8">
                <label class="form-label">Title</label>
                <input type="text" name="item_title[]" class="form-control" maxlength="150" placeholder="Premium Equipment" value="">
              </div>
              <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="item_description[]" class="form-control" rows="2" placeholder="Short description."></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>`;
  }
  function refreshBlockNumbers() {
    wrap.querySelectorAll('.facility-block').forEach((b, i) => {
      const t = b.querySelector('.item-title');
      if (t) t.innerHTML = `<i class="bi bi-grip-vertical" style="cursor:grab;"></i> Facility ${i + 1}`;
    });
  }
  addBtn.addEventListener('click', () => {
    const count = wrap.querySelectorAll('.facility-block').length;
    wrap.insertAdjacentHTML('beforeend', template(count));
    wrap.lastElementChild.querySelector('input[name="item_title[]"]').focus();
  });
  wrap.addEventListener('click', (e) => {
    const rm = e.target.closest('.facility-remove');
    if (!rm) return;
    const rows = wrap.querySelectorAll('.facility-block');
    if (rows.length <= 1) { alert('At least one facility item required.'); return; }
    rm.closest('.facility-block').remove();
    refreshBlockNumbers();
  });
  wrap.addEventListener('input', (e) => {
    const inp = e.target.closest('.facility-icon-input');
    if (!inp) return;
    const block = inp.closest('.facility-block');
    const preview = block.querySelector('[data-icon-preview] i');
    if (preview) preview.className = inp.value.trim() || 'bi bi-check';
  });
  if (window.Sortable) {
    Sortable.create(wrap, {
      animation: 150,
      handle: '.bi-grip-vertical',
      ghostClass: 'opacity-50',
      onEnd: refreshBlockNumbers
    });
  }
})();

/* Transformations */
(function () {
  const wrap = document.getElementById('transformationsItemsWrap');
  const addBtn = document.getElementById('transformationsAddBtn');
  if (!wrap || !addBtn) return;
  function template() {
    return `
      <div class="transformation-block" data-item-id="0">
        <input type="hidden" name="item_id[]" value="0">
        <div class="item-header">
          <span class="item-title"><i class="bi bi-grip-vertical" style="cursor:grab;"></i> Transformation (new)</span>
          <button type="button" class="btn btn-outline-danger btn-sm transformation-remove"><i class="bi bi-trash"></i> Remove</button>
        </div>
        <div class="d-flex gap-3 mb-3 align-items-center">
          <div class="transformation-thumb-empty"><i class="bi bi-image"></i></div>
          <div class="flex-grow-1">
            <label class="form-label">Image</label>
            <input type="file" name="item_image[]" class="form-control mb-1" accept="image/*">
            <input type="text" name="item_image_url[]" class="form-control" placeholder="…or paste image URL" value="">
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="item_name[]" class="form-control" maxlength="120" placeholder="Sarah K." value="">
          </div>
          <div class="col-md-3">
            <label class="form-label">Duration Label</label>
            <input type="text" name="item_duration[]" class="form-control" maxlength="60" placeholder="12 Weeks" value="">
          </div>
          <div class="col-md-5">
            <label class="form-label">Result Line</label>
            <input type="text" name="item_result[]" class="form-control" maxlength="200" placeholder="Lost 18 kg · Gained confidence" value="">
          </div>
          <div class="col-12">
            <label class="form-label">Quote</label>
            <textarea name="item_quote[]" class="form-control" rows="2" placeholder="Short quote."></textarea>
          </div>
        </div>
      </div>`;
  }
  addBtn.addEventListener('click', () => {
    wrap.insertAdjacentHTML('beforeend', template());
    wrap.lastElementChild.querySelector('input[name="item_name[]"]').focus();
  });
  wrap.addEventListener('click', (e) => {
    const rm = e.target.closest('.transformation-remove');
    if (!rm) return;
    const rows = wrap.querySelectorAll('.transformation-block');
    if (rows.length <= 1) { alert('At least one transformation required.'); return; }
    rm.closest('.transformation-block').remove();
  });
  if (window.Sortable) {
    Sortable.create(wrap, { animation: 150, handle: '.bi-grip-vertical', ghostClass: 'opacity-50' });
  }
})();

/* Testimonials */
(function () {
  const wrap = document.getElementById('testimonialsItemsWrap');
  const addBtn = document.getElementById('testimonialsAddBtn');
  if (!wrap || !addBtn) return;
  function template() {
    return `
      <div class="testimonial-block" data-item-id="0">
        <input type="hidden" name="item_id[]" value="0">
        <div class="item-header">
          <span class="item-title"><i class="bi bi-grip-vertical" style="cursor:grab;"></i> Testimonial (new)</span>
          <button type="button" class="btn btn-outline-danger btn-sm testimonial-remove"><i class="bi bi-trash"></i> Remove</button>
        </div>
        <div class="d-flex gap-3 mb-3 align-items-center">
          <div class="testimonial-thumb-empty"><i class="bi bi-person"></i></div>
          <div class="flex-grow-1">
            <label class="form-label">Avatar</label>
            <input type="file" name="item_avatar[]" class="form-control mb-1" accept="image/*">
            <input type="text" name="item_avatar_url[]" class="form-control" placeholder="…or paste image URL" value="">
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input type="text" name="item_name[]" class="form-control" maxlength="120" placeholder="James Thornton" value="">
          </div>
          <div class="col-md-4">
            <label class="form-label">Role</label>
            <input type="text" name="item_role[]" class="form-control" maxlength="120" placeholder="Member since 2021" value="">
          </div>
          <div class="col-md-4">
            <label class="form-label">Stars (1–5)</label>
            <select name="item_stars[]" class="form-select star-select">
              <option value="5" selected>★★★★★ (5)</option>
              <option value="4">★★★★☆ (4)</option>
              <option value="3">★★★☆☆ (3)</option>
              <option value="2">★★☆☆☆ (2)</option>
              <option value="1">★☆☆☆☆ (1)</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Quote</label>
            <textarea name="item_quote[]" class="form-control" rows="3" placeholder="Quote…"></textarea>
          </div>
        </div>
      </div>`;
  }
  addBtn.addEventListener('click', () => {
    wrap.insertAdjacentHTML('beforeend', template());
    wrap.lastElementChild.querySelector('input[name="item_name[]"]').focus();
  });
  wrap.addEventListener('click', (e) => {
    const rm = e.target.closest('.testimonial-remove');
    if (!rm) return;
    const rows = wrap.querySelectorAll('.testimonial-block');
    if (rows.length <= 1) { alert('At least one testimonial required.'); return; }
    rm.closest('.testimonial-block').remove();
  });
  if (window.Sortable) {
    Sortable.create(wrap, { animation: 150, handle: '.bi-grip-vertical', ghostClass: 'opacity-50' });
  }
})();

/* Schedule grid */
(function () {
  const body = document.getElementById('scheduleRowsBody');
  const addBtn = document.getElementById('scheduleAddRowBtn');
  if (!body || !addBtn) return;

  function reindexRows() {
    body.querySelectorAll('.schedule-row').forEach((row, ri) => {
      row.querySelectorAll('input[name^="cell["]').forEach(inp => {
        const day = inp.name.match(/\]\[(\d+)\]$/)[1];
        inp.name = `cell[${ri}][${day}]`;
      });
    });
  }

  function rowTemplate(ri) {
    let cells = '';
    for (let day = 1; day <= 6; day++) {
      cells += `
        <td>
          <input type="text" name="cell[${ri}][${day}]" class="form-control cell-input"
                 maxlength="100" placeholder="—" value="">
        </td>`;
    }
    return `
      <tr class="schedule-row">
        <td>
          <input type="text" name="row_time[]" class="form-control row-time-input"
                 maxlength="20" placeholder="06:00" value="">
        </td>
        ${cells}
        <td class="text-center">
          <button type="button" class="btn btn-outline-danger btn-sm row-remove-btn" title="Remove row">
            <i class="bi bi-x-lg"></i>
          </button>
        </td>
      </tr>`;
  }

  addBtn.addEventListener('click', () => {
    const ri = body.querySelectorAll('.schedule-row').length;
    body.insertAdjacentHTML('beforeend', rowTemplate(ri));
    body.lastElementChild.querySelector('input[name="row_time[]"]').focus();
  });

  body.addEventListener('click', (e) => {
    const rm = e.target.closest('.row-remove-btn');
    if (!rm) return;
    const rows = body.querySelectorAll('.schedule-row');
    if (rows.length <= 1) { alert('At least one time slot required.'); return; }
    rm.closest('.schedule-row').remove();
    reindexRows();
  });

  if (window.Sortable) {
    Sortable.create(body, {
      animation: 150,
      handle: '.row-time-input',
      ghostClass: 'opacity-50',
      filter: '.row-remove-btn',
      preventOnFilter: false,
      onEnd: reindexRows
    });
  }
})();

/* Journey steps */
(function () {
  const wrap = document.getElementById('journeyStepsWrap');
  const addBtn = document.getElementById('journeyStepAddBtn');
  if (!wrap || !addBtn) return;

  function refreshBlockNumbers() {
    wrap.querySelectorAll('.journey-block').forEach((b, i) => {
      const t = b.querySelector('.item-title');
      if (t) t.innerHTML = `<i class="bi bi-grip-vertical" style="cursor:grab;"></i> Step ${i + 1}`;
      const num = b.querySelector('[data-num-preview]');
      const inp = b.querySelector('.journey-number-input');
      if (num && inp && !inp.value.trim()) {
        num.textContent = String(i + 1).padStart(2, '0');
      }
    });
  }

  function template(index) {
    const auto = String(index + 1).padStart(2, '0');
    return `
      <div class="journey-block">
        <div class="item-header">
          <span class="item-title"><i class="bi bi-grip-vertical" style="cursor:grab;"></i> Step ${index+1}</span>
          <button type="button" class="btn btn-outline-danger btn-sm journey-step-remove"><i class="bi bi-trash"></i> Remove</button>
        </div>
        <div class="d-flex gap-3 align-items-start">
          <span class="journey-num-preview" data-num-preview>${auto}</span>
          <div class="flex-grow-1">
            <div class="row g-3">
              <div class="col-md-2">
                <label class="form-label">Number</label>
                <input type="text" name="step_number[]" class="form-control journey-number-input"
                       maxlength="10" placeholder="${auto}" value="">
              </div>
              <div class="col-md-4">
                <label class="form-label">Title</label>
                <input type="text" name="step_title[]" class="form-control" maxlength="150" placeholder="Step title" value="">
              </div>
              <div class="col-md-6">
                <label class="form-label">Description</label>
                <textarea name="step_description[]" class="form-control" rows="2" placeholder="Short description."></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>`;
  }

  addBtn.addEventListener('click', () => {
    const count = wrap.querySelectorAll('.journey-block').length;
    wrap.insertAdjacentHTML('beforeend', template(count));
    wrap.lastElementChild.querySelector('input[name="step_title[]"]').focus();
  });

  wrap.addEventListener('click', (e) => {
    const rm = e.target.closest('.journey-step-remove');
    if (!rm) return;
    const rows = wrap.querySelectorAll('.journey-block');
    if (rows.length <= 1) { alert('At least one step required.'); return; }
    rm.closest('.journey-block').remove();
    refreshBlockNumbers();
  });

  wrap.addEventListener('input', (e) => {
    const inp = e.target.closest('.journey-number-input');
    if (!inp) return;
    const block = inp.closest('.journey-block');
    const preview = block.querySelector('[data-num-preview]');
    if (preview) preview.textContent = inp.value.trim() || '—';
  });

  if (window.Sortable) {
    Sortable.create(wrap, {
      animation: 150,
      handle: '.bi-grip-vertical',
      ghostClass: 'opacity-50',
      onEnd: refreshBlockNumbers
    });
  }
})();

/* FAQ items */
(function () {
  const wrap = document.getElementById('faqItemsWrap');
  const addBtn = document.getElementById('faqAddBtn');
  if (!wrap || !addBtn) return;

  function refreshBlockNumbers() {
    wrap.querySelectorAll('.faq-block').forEach((b, i) => {
      const t = b.querySelector('.item-title');
      if (t) t.innerHTML = `<i class="bi bi-grip-vertical" style="cursor:grab;"></i> Question ${i + 1}`;
      const chip = b.querySelector('[data-index-chip]');
      if (chip) chip.textContent = String(i + 1).padStart(2, '0');
    });
  }

  function template(index) {
    const chip = String(index + 1).padStart(2, '0');
    return `
      <div class="faq-block">
        <div class="item-header">
          <span class="item-title"><i class="bi bi-grip-vertical" style="cursor:grab;"></i> Question ${index+1}</span>
          <button type="button" class="btn btn-outline-danger btn-sm faq-remove"><i class="bi bi-trash"></i> Remove</button>
        </div>
        <div class="d-flex gap-3 align-items-start">
          <span class="faq-index-chip" data-index-chip>${chip}</span>
          <div class="flex-grow-1">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Question</label>
                <input type="text" name="item_question[]" class="form-control" maxlength="255"
                       placeholder="Question text" value="">
              </div>
              <div class="col-12">
                <label class="form-label">Answer</label>
                <textarea name="item_answer[]" class="form-control" rows="3" placeholder="Answer text."></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>`;
  }

  addBtn.addEventListener('click', () => {
    const count = wrap.querySelectorAll('.faq-block').length;
    wrap.insertAdjacentHTML('beforeend', template(count));
    wrap.lastElementChild.querySelector('input[name="item_question[]"]').focus();
  });

  wrap.addEventListener('click', (e) => {
    const rm = e.target.closest('.faq-remove');
    if (!rm) return;
    const rows = wrap.querySelectorAll('.faq-block');
    if (rows.length <= 1) { alert('At least one FAQ required.'); return; }
    rm.closest('.faq-block').remove();
    refreshBlockNumbers();
  });

  if (window.Sortable) {
    Sortable.create(wrap, {
      animation: 150,
      handle: '.bi-grip-vertical',
      ghostClass: 'opacity-50',
      onEnd: refreshBlockNumbers
    });
  }
})();
</script>
</body>
</html>