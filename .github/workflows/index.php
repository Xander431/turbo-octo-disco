<?php
include "config.php";

/* SEARCH — parameterized to prevent SQL injection */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== "") {
    $sql = "SELECT * FROM notes 
            WHERE title LIKE CONCAT('%', ?, '%') 
            OR content LIKE CONCAT('%', ?, '%') 
            ORDER BY id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $search, $search);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql = "SELECT * FROM notes ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
}

$noteCount = $result ? mysqli_num_rows($result) : 0;

// sticky-note color + tilt, derived from the note id so it stays put between reloads
$tapeColors = ['#E8C46B', '#9FBF9B', '#8FB6D9', '#E0A4A1', '#C9A6E0'];
$tilts      = [-2, 1.5, -1, 2.5, -1.5, 1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Notes</title>
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
    padding: 34px 20px 26px;
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
    font-size: 46px;
    font-weight: 700;
    margin: 6px 0 2px;
    letter-spacing: 0.5px;
  }

  .header p {
    margin: 0;
    font-size: 14px;
    opacity: 0.85;
    font-style: italic;
  }

  /* ---------- TOOLBAR ---------- */
  .toolbar {
    max-width: 980px;
    margin: -22px auto 0;
    padding: 0 16px;
    position: relative;
    z-index: 2;
  }

  .toolbar-card {
    background: var(--surface);
    border: 1px solid var(--surface-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 14px 18px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 12px;
  }

  .add-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    background: var(--go);
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    white-space: nowrap;
    transition: transform 0.15s, filter 0.15s;
  }

  .add-btn:hover { filter: brightness(1.08); transform: translateY(-1px); }

  .theme-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    cursor: pointer;
    background: transparent;
    color: var(--ink);
    font-size: 14px;
    font-weight: 600;
    font-family: inherit;
  }

  .theme-toggle svg { width: 16px; height: 16px; }
  .theme-toggle .icon-dark { display: none; }
  .dark-mode .theme-toggle .icon-light { display: none; }
  .dark-mode .theme-toggle .icon-dark { display: inline-flex; }

  .search-form {
    flex: 1 1 260px;
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--bg);
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    padding: 6px 10px;
    min-width: 220px;
  }

  .search-form svg { width: 16px; height: 16px; color: var(--ink-soft); flex-shrink: 0; }

  .search-form input {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    color: var(--ink);
    font-family: inherit;
    font-size: 14px;
    min-width: 0;
  }

  .search-form button {
    border: none;
    background: var(--accent);
    color: #fff;
    padding: 7px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
  }

  .clear-search {
    color: var(--ink-soft);
    text-decoration: none;
    font-size: 16px;
    padding: 0 2px;
  }

  /* ---------- GRID ---------- */
  .container {
    max-width: 1100px;
    margin: 0 auto;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 26px;
    padding: 40px 20px 60px;
  }

  .card {
    --rot: 0deg;
    background: var(--surface);
    width: 290px;
    padding: 18px 18px 16px;
    border-radius: 10px;
    box-shadow: var(--shadow);
    border: 1px solid var(--surface-border);
    position: relative;
    transform: rotate(var(--rot));
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .card:hover {
    transform: rotate(0deg) translateY(-3px);
    box-shadow: 0 14px 28px rgba(35, 42, 53, 0.16);
  }

  .tape {
    position: absolute;
    top: -10px;
    left: 50%;
    width: 56px;
    height: 20px;
    margin-left: -28px;
    border-radius: 3px;
    opacity: 0.9;
    transform: rotate(-3deg);
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
  }

  .dark-mode .tape { filter: brightness(0.8) saturate(0.75); opacity: 0.75; }

  .card h3 {
    font-family: 'Caveat', cursive;
    font-size: 26px;
    font-weight: 700;
    margin: 4px 0 8px;
    line-height: 1.15;
    word-break: break-word;
  }

  .card .content {
    font-size: 14px;
    line-height: 1.55;
    color: var(--ink);
    margin: 0 0 10px;
    word-break: break-word;
  }

  .card img {
    width: 100%;
    border-radius: 8px;
    margin-top: 4px;
    margin-bottom: 10px;
    display: block;
  }

  .meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    color: var(--ink-soft);
    margin-bottom: 12px;
  }

  .meta svg { width: 12px; height: 12px; }

  .actions {
    display: flex;
    gap: 8px;
    border-top: 1px dashed var(--surface-border);
    padding-top: 12px;
  }

  .actions a {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    text-decoration: none;
    padding: 6px 11px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    transition: filter 0.15s;
  }

  .actions a svg { width: 12px; height: 12px; }
  .actions a:hover { filter: brightness(1.1); }

  .edit { background: var(--accent-soft); color: var(--accent); }
  .delete { background: var(--danger-soft); color: var(--danger); }

  /* ---------- EMPTY STATE ---------- */
  .empty-state {
    width: 100%;
    text-align: center;
    padding: 70px 20px;
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
  a:focus-visible, button:focus-visible, input:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .card, .add-btn { transition: none; }
  }

  @media (max-width: 480px) {
    .header h1 { font-size: 36px; }
    .card { width: 100%; max-width: 320px; }
  }
</style>
</head>

<body>

<header class="header">
    <h1>My Notes</h1>
    <p>Pin it before it slips your mind.</p>
</header>

<div class="toolbar">
  <div class="toolbar-card">

    <a class="add-btn" href="add.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
        Add note
    </a>

    <form method="GET" class="search-form" role="search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3" stroke-linecap="round"/></svg>
        <input type="text" name="search" placeholder="Search your notes…" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($search !== '') { ?>
            <a class="clear-search" href="index.php" title="Clear search">✕</a>
        <?php } ?>
        <button type="submit">Search</button>
    </form>

    <button class="theme-toggle" type="button" onclick="toggleDark()" aria-label="Toggle dark mode">
        <svg class="icon-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" stroke-linecap="round"/></svg>
        <svg class="icon-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        Dark mode
    </button>

  </div>
</div>

<div class="container">

<?php if ($noteCount === 0) { ?>

    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.5 6.5L21 11l-6.5 2.5L12 20l-2.5-6.5L3 11l6.5-2.5L12 2z"/></svg>
        <?php if ($search !== '') { ?>
            <h2>No notes match "<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"</h2>
            <p>Try a different search, or clear it to see every note.</p>
            <a class="add-btn" href="index.php">Clear search</a>
        <?php } else { ?>
            <h2>Nothing pinned yet</h2>
            <p>Add your first note to see it show up here.</p>
            <a class="add-btn" href="add.php">+ Add a note</a>
        <?php } ?>
    </div>

<?php
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        $id   = (int) $row['id'];
        $tape = $tapeColors[$id % count($tapeColors)];
        $tilt = $tilts[$id % count($tilts)];
?>

<div class="card" style="--rot: <?php echo $tilt; ?>deg;">

    <span class="tape" style="background: <?php echo $tape; ?>;"></span>

    <h3><?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></h3>

    <p class="content"><?php echo nl2br(htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8')); ?></p>

    <?php if (!empty($row['image'])) { ?>
        <img src="uploads/<?php echo htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="">
    <?php } ?>

    <div class="meta">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3" stroke-linecap="round"/></svg>
        <?php echo date("M d, Y - h:i A", strtotime($row['created_at'])); ?>
    </div>

    <div class="actions">
        <a class="edit" href="edit.php?id=<?php echo $id; ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
            Edit
        </a>
        <a class="delete" href="delete.php?id=<?php echo $id; ?>" onclick="return confirm('Delete this note?')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
            Delete
        </a>
    </div>

</div>

<?php
    }
}
?>

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