<?php
include "config.php";

$success = false;
$error   = '';

if (isset($_POST['save'])) {

    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image   = '';

    if ($title === '') {
        $error = "Give the note a title before saving.";

    } elseif (!empty($_FILES['image']['name'])) {

        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $tmpPath  = $_FILES['image']['tmp_name'];
        $maxBytes = 5 * 1024 * 1024; // 5MB

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $error = "That image couldn't be uploaded. Please try again.";
        } elseif (!in_array($ext, $allowed, true)) {
            $error = "Images must be JPG, PNG, GIF, or WEBP.";
        } elseif ($_FILES['image']['size'] > $maxBytes) {
            $error = "Image is too large (max 5MB).";
        } elseif (@getimagesize($tmpPath) === false) {
            $error = "That file doesn't look like a valid image.";
        } else {
            if (!is_dir('uploads')) {
                mkdir('uploads', 0755, true);
            }
            // random filename — never trust the uploaded name
            $image = bin2hex(random_bytes(8)) . '.' . $ext;
            move_uploaded_file($tmpPath, "uploads/" . $image);
        }
    }

    if ($error === '') {
        $stmt = mysqli_prepare($conn, "INSERT INTO notes (title, content, image) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $title, $content, $image);
        mysqli_stmt_execute($stmt);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Note</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">

<style>
  :root {
    --bg: #E4EAF1;
    --bg-pattern: #D7E0EA;
    --ink: #232A35;
    --ink-soft: #5B6573;
    --surface: #FBF9F4;
    --surface-border: rgba(35, 42, 53, 0.08);
    --accent: #2D4F8E;
    --accent-soft: #DCE6F4;
    --danger: #C0473A;
    --danger-soft: #F6DEDB;
    --go: #4F7A5B;
    --go-soft: #DCEADF;
    --shadow: 0 8px 20px rgba(35, 42, 53, 0.10), 0 2px 6px rgba(35, 42, 53, 0.08);
    --radius: 14px;
  }

  .dark-mode {
    --bg: #161B23;
    --bg-pattern: #1E2530;
    --ink: #E7EBF1;
    --ink-soft: #9AA4B2;
    --surface: #232A35;
    --surface-border: rgba(255, 255, 255, 0.08);
    --accent: #7FA3DE;
    --accent-soft: #2A3850;
    --danger: #E08B7D;
    --danger-soft: #3C2A28;
    --go: #8DC39A;
    --go-soft: #243228;
    --shadow: 0 10px 24px rgba(0, 0, 0, 0.45);
  }

  * { box-sizing: border-box; }

  body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    background-image: radial-gradient(var(--bg-pattern) 1px, transparent 1px);
    background-size: 18px 18px;
    color: var(--ink);
    transition: background 0.3s, color 0.3s;
    min-height: 100vh;
  }

  a { color: inherit; }

  /* ---------- HEADER ---------- */
  .header {
    background: var(--accent);
    color: #fff;
    padding: 28px 20px 22px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }

  .header::before {
    content: "";
    position: absolute;
    top: 10px;
    left: 0;
    right: 0;
    height: 14px;
    background-image: radial-gradient(circle, rgba(255,255,255,0.55) 3px, transparent 3px);
    background-size: 26px 14px;
    background-repeat: repeat-x;
    background-position: center;
  }

  .header h1 {
    font-family: 'Caveat', cursive;
    font-size: 38px;
    font-weight: 700;
    margin: 6px 0 0;
    letter-spacing: 0.5px;
  }

  /* ---------- TOOLBAR ---------- */
  .toolbar {
    max-width: 640px;
    margin: -18px auto 0;
    padding: 0 16px;
    position: relative;
    z-index: 2;
  }

  .toolbar-card {
    background: var(--surface);
    border: 1px solid var(--surface-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 10px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    color: var(--ink);
    font-weight: 600;
    font-size: 14px;
  }

  .back-link svg { width: 16px; height: 16px; }

  .theme-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 13px;
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    cursor: pointer;
    background: transparent;
    color: var(--ink);
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
  }

  .theme-toggle svg { width: 16px; height: 16px; }
  .theme-toggle .icon-dark { display: none; }
  .dark-mode .theme-toggle .icon-light { display: none; }
  .dark-mode .theme-toggle .icon-dark { display: inline-flex; }

  /* ---------- PAGE / CARD ---------- */
  .page {
    max-width: 640px;
    margin: 0 auto;
    padding: 36px 20px 60px;
  }

  .banner {
    display: flex;
    align-items: center;
    gap: 10px;
    border-radius: 10px;
    padding: 13px 16px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 18px;
  }

  .banner svg { width: 18px; height: 18px; flex-shrink: 0; }

  .banner-success { background: var(--go-soft); color: var(--go); }
  .banner-error { background: var(--danger-soft); color: var(--danger); }

  .banner a { text-decoration: underline; }

  .card {
    background: var(--surface);
    border: 1px solid var(--surface-border);
    border-radius: 12px;
    box-shadow: var(--shadow);
    padding: 30px 28px 26px;
    position: relative;
  }

  .tape {
    position: absolute;
    top: -10px;
    left: 50%;
    width: 64px;
    height: 22px;
    margin-left: -32px;
    border-radius: 3px;
    background: #E8C46B;
    opacity: 0.9;
    transform: rotate(-3deg);
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
  }

  .dark-mode .tape { filter: brightness(0.8) saturate(0.75); opacity: 0.75; }

  .card .eyebrow {
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--ink-soft);
    margin: 0 0 16px;
  }

  .field { margin-bottom: 18px; }

  .field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--ink-soft);
    margin-bottom: 6px;
  }

  .field label .optional {
    font-weight: 400;
    color: var(--ink-soft);
    opacity: 0.7;
  }

  .field input[type="text"],
  .field textarea {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    padding: 11px 13px;
    font-family: 'Inter', sans-serif;
    font-size: 15px;
    color: var(--ink);
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
  }

  .field input[type="text"]:focus,
  .field textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-soft);
  }

  .field textarea {
    min-height: 140px;
    resize: vertical;
    line-height: 1.5;
  }

  .file-drop {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--bg);
    border: 1px dashed var(--surface-border);
    border-radius: 8px;
    padding: 13px;
    cursor: pointer;
    font-size: 14px;
    color: var(--ink-soft);
    transition: border-color 0.15s;
  }

  .file-drop:hover { border-color: var(--accent); }
  .file-drop svg { width: 18px; height: 18px; flex-shrink: 0; color: var(--accent); }
  .file-drop span { word-break: break-all; }

  .form-actions {
    display: flex;
    gap: 10px;
    border-top: 1px dashed var(--surface-border);
    padding-top: 20px;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: none;
    border-radius: 8px;
    padding: 11px 18px;
    font-size: 14px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    text-decoration: none;
  }

  .btn svg { width: 15px; height: 15px; }

  .btn-save { background: var(--go); color: #fff; transition: filter 0.15s, transform 0.15s; }
  .btn-save:hover { filter: brightness(1.08); transform: translateY(-1px); }

  .btn-cancel { background: var(--accent-soft); color: var(--accent); }
  .btn-cancel:hover { filter: brightness(1.05); }

  /* ---------- A11Y / MOTION ---------- */
  a:focus-visible, button:focus-visible, input:focus-visible, textarea:focus-visible, .file-drop:focus-within {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .btn-save { transition: none; }
  }
</style>
</head>

<body>

<header class="header">
    <h1>Add a Note</h1>
</header>

<div class="toolbar">
  <div class="toolbar-card">
    <a class="back-link" href="index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        All notes
    </a>

    <button class="theme-toggle" type="button" onclick="toggleDark()" aria-label="Toggle dark mode">
        <svg class="icon-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" stroke-linecap="round"/></svg>
        <svg class="icon-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        Dark mode
    </button>
  </div>
</div>

<div class="page">

    <?php if ($success) { ?>
        <div class="banner banner-success">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Note pinned! <a href="index.php">View all notes</a> or add another below.
        </div>
    <?php } ?>

    <?php if ($error !== '') { ?>
        <div class="banner banner-error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg>
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php } ?>

    <div class="card">
        <span class="tape"></span>

        <p class="eyebrow">New note</p>

        <form method="POST" enctype="multipart/form-data">

            <div class="field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" placeholder="What's this about?" required>
            </div>

            <div class="field">
                <label for="content">Content</label>
                <textarea id="content" name="content" placeholder="Write your note…"></textarea>
            </div>

            <div class="field">
                <label for="image">Photo <span class="optional">(optional)</span></label>
                <label class="file-drop" for="image">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M5 16v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3" stroke-linecap="round"/></svg>
                    <span id="fileName">Click to choose an image (JPG, PNG, GIF, WEBP — max 5MB)</span>
                </label>
                <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp" hidden>
            </div>

            <div class="form-actions">
                <button class="btn btn-save" type="submit" name="save" value="1">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                    Save note
                </button>
                <a class="btn btn-cancel" href="index.php">Cancel</a>
            </div>

        </form>
    </div>

</div>

<script>
function toggleDark() {
    document.body.classList.toggle("dark-mode");
    localStorage.setItem("mode", document.body.classList.contains("dark-mode") ? "dark" : "light");
}

(function () {
    if (localStorage.getItem("mode") === "dark") {
        document.body.classList.add("dark-mode");
    }
})();

document.getElementById('image').addEventListener('change', function () {
    var label = document.getElementById('fileName');
    label.textContent = this.files.length ? this.files[0].name : 'Click to choose an image (JPG, PNG, GIF, WEBP — max 5MB)';
});
</script>

</body>
</html>