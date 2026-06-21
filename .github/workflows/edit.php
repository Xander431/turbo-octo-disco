<?php
include "config.php";

/* Load the note — parameterized to prevent SQL injection */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = mysqli_prepare($conn, "SELECT * FROM notes WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = $result ? mysqli_fetch_assoc($result) : null;

$saved = false;

if (isset($_POST['update']) && $row) {

    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    $update = mysqli_prepare($conn, "UPDATE notes SET title = ?, content = ? WHERE id = ?");
    mysqli_stmt_bind_param($update, "ssi", $title, $content, $id);
    mysqli_stmt_execute($update);

    header("Location: index.php");
    exit;
}

// sticky-note color, derived from the note id so it matches its card on the home page
$tapeColors = ['#E8C46B', '#9FBF9B', '#8FB6D9', '#E0A4A1', '#C9A6E0'];
$tape = $tapeColors[$id % count($tapeColors)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Note</title>
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

  /* ---------- FORM CARD ---------- */
  .page {
    max-width: 640px;
    margin: 0 auto;
    padding: 36px 20px 60px;
  }

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
    background: <?php echo $tape; ?>;
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
    margin: 0 0 4px;
  }

  .field { margin-bottom: 18px; }

  .field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--ink-soft);
    margin-bottom: 6px;
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
    min-height: 160px;
    resize: vertical;
    line-height: 1.5;
  }

  .meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    color: var(--ink-soft);
    margin: -4px 0 20px;
  }

  .meta svg { width: 12px; height: 12px; }

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

  /* ---------- NOT FOUND ---------- */
  .empty-state {
    text-align: center;
    padding: 50px 20px;
    color: var(--ink-soft);
  }

  .empty-state svg { width: 40px; height: 40px; margin-bottom: 14px; opacity: 0.6; }

  .empty-state h2 {
    font-family: 'Caveat', cursive;
    font-size: 28px;
    color: var(--ink);
    margin: 0 0 6px;
  }

  .empty-state p { margin: 0 0 18px; font-size: 14px; }

  /* ---------- A11Y / MOTION ---------- */
  a:focus-visible, button:focus-visible, input:focus-visible, textarea:focus-visible {
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
    <h1>Edit Note</h1>
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

<?php if (!$row) { ?>

    <div class="card">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg>
            <h2>Note not found</h2>
            <p>This note may have been deleted, or the link is no longer valid.</p>
            <a class="btn btn-save" href="index.php">Back to all notes</a>
        </div>
    </div>

<?php } else { ?>

    <div class="card">
        <span class="tape"></span>

        <p class="eyebrow">Editing note #<?php echo $id; ?></p>

        <div class="meta">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3" stroke-linecap="round"/></svg>
            Pinned <?php echo date("M d, Y - h:i A", strtotime($row['created_at'])); ?>
        </div>

        <form method="POST">

            <div class="field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="field">
                <label for="content">Content</label>
                <textarea id="content" name="content"><?php echo htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="form-actions">
                <button class="btn btn-save" type="submit" name="update" value="1">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Save changes
                </button>
                <a class="btn btn-cancel" href="index.php">Cancel</a>
            </div>

        </form>
    </div>

<?php } ?>

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
</script>

</body>
</html>