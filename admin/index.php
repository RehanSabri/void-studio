<?php
// ─────────────────────────────────────────────────
//  admin/index.php — Admin dashboard
// ─────────────────────────────────────────────────

session_start();
require_once __DIR__ . '/../config/db.php';

// Auth guard
if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

$pdo = get_db();

// ─── Handle status update ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE contact_submissions SET status=? WHERE id=?");
    $stmt->execute([$_POST['status'], (int)$_POST['id']]);
    header('Location: /admin/index.php');
    exit;
}

// ─── Handle delete ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM contact_submissions WHERE id=?")
        ->execute([(int)$_POST['delete_id']]);
    header('Location: /admin/index.php');
    exit;
}

// ─── Handle logout ───────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /admin/login.php');
    exit;
}

// ─── Fetch data ──────────────────────────────────
$statusFilter = $_GET['status'] ?? 'all';
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

$where  = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[]  = 'status = ?';
    $params[] = $statusFilter;
}
if ($search) {
    $where[]  = '(name LIKE ? OR email LIKE ? OR message LIKE ?)';
    $like     = "%{$search}%";
    $params   = array_merge($params, [$like, $like, $like]);
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_submissions {$whereSQL}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, ceil($total / $perPage));

// Rows
$rowStmt = $pdo->prepare(
    "SELECT * FROM contact_submissions {$whereSQL}
     ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}"
);
$rowStmt->execute($params);
$rows = $rowStmt->fetchAll();

// Stats
$stats = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status='new') AS new_count,
        SUM(status='read') AS read_count,
        SUM(status='replied') AS replied_count
     FROM contact_submissions"
)->fetch();

$statusColors = [
    'new'      => '#c9a96e',
    'read'     => '#6a8ac9',
    'replied'  => '#6ac98a',
    'archived' => '#4a4540',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>VØID Admin — Submissions</title>
<style>
  *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
  :root {
    --bg:#0a0906; --bg2:#111009; --cream:#e8e0d0; --cream2:#c9bfa8;
    --gold:#c9a96e; --muted:#4a4540; --border:rgba(201,169,110,0.15);
  }
  body { background:var(--bg); color:var(--cream); font-family:Georgia,serif;
         font-size:14px; min-height:100vh; }

  /* Layout */
  .sidebar { position:fixed; left:0; top:0; bottom:0; width:220px;
             background:var(--bg2); border-right:1px solid var(--border);
             padding:2rem 1.5rem; display:flex; flex-direction:column; gap:2rem; }
  .main { margin-left:220px; padding:2rem; }

  /* Sidebar */
  .logo { font-family:sans-serif; font-weight:800; font-size:1.2rem;
          letter-spacing:.08em; color:var(--cream); }
  .logo span { color:var(--gold); }
  .sidebar-nav { display:flex; flex-direction:column; gap:.5rem; }
  .sidebar-nav a { display:flex; justify-content:space-between; align-items:center;
                   padding:.5rem .8rem; color:var(--cream2); text-decoration:none;
                   font-family:monospace; font-size:.68rem; letter-spacing:.08em;
                   text-transform:uppercase; border-radius:2px; transition:background .2s; }
  .sidebar-nav a:hover, .sidebar-nav a.active { background:rgba(201,169,110,.08); color:var(--gold); }
  .badge { background:var(--gold); color:var(--bg); padding:.1rem .4rem;
           border-radius:2px; font-size:.6rem; }
  .logout { margin-top:auto; font-family:monospace; font-size:.65rem;
            letter-spacing:.1em; text-transform:uppercase; color:var(--muted);
            text-decoration:none; transition:color .3s; }
  .logout:hover { color:var(--gold); }

  /* Stats */
  .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:2rem; }
  .stat { border:1px solid var(--border); padding:1.2rem; }
  .stat-n { font-size:2rem; font-weight:300; letter-spacing:-.04em; color:var(--cream); }
  .stat-l { font-family:monospace; font-size:.6rem; letter-spacing:.1em;
            text-transform:uppercase; color:var(--muted); margin-top:.3rem; }

  /* Toolbar */
  .toolbar { display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; align-items:center; }
  .toolbar input { background:transparent; border:1px solid var(--border);
                   padding:.5rem 1rem; color:var(--cream); font-family:Georgia,serif;
                   outline:none; width:260px; }
  .toolbar input:focus { border-color:var(--gold); }
  .toolbar input::placeholder { color:var(--muted); }

  /* Table */
  .table-wrap { overflow-x:auto; }
  table { width:100%; border-collapse:collapse; }
  thead th { font-family:monospace; font-size:.6rem; letter-spacing:.12em;
             text-transform:uppercase; color:var(--muted); border-bottom:1px solid var(--border);
             padding:.8rem; text-align:left; white-space:nowrap; }
  tbody tr { border-bottom:1px solid var(--border); transition:background .2s; }
  tbody tr:hover { background:rgba(201,169,110,.03); }
  tbody td { padding:.9rem .8rem; vertical-align:top; }
  .td-name { font-size:.95rem; color:var(--cream); }
  .td-email { font-family:monospace; font-size:.7rem; color:var(--muted); }
  .td-project { font-family:monospace; font-size:.65rem; color:var(--gold); }
  .td-msg { color:var(--cream2); font-size:.85rem; max-width:300px; }
  .td-date { font-family:monospace; font-size:.65rem; color:var(--muted); white-space:nowrap; }

  .status-badge {
    display:inline-block; padding:.2rem .6rem; font-family:monospace;
    font-size:.6rem; letter-spacing:.08em; text-transform:uppercase; border-radius:2px;
  }

  /* Forms inside table */
  .actions { display:flex; gap:.5rem; flex-wrap:wrap; }
  select { background:var(--bg2); border:1px solid var(--border); color:var(--cream);
           font-family:monospace; font-size:.62rem; padding:.3rem .5rem; cursor:pointer; }
  .btn-sm { background:var(--gold); border:none; color:var(--bg); font-family:monospace;
            font-size:.6rem; letter-spacing:.08em; text-transform:uppercase;
            padding:.3rem .7rem; cursor:pointer; transition:background .2s; }
  .btn-sm:hover { background:var(--cream); }
  .btn-del { background:rgba(200,70,70,.15); border:1px solid rgba(200,70,70,.3);
             color:#e57373; font-family:monospace; font-size:.6rem;
             padding:.3rem .7rem; cursor:pointer; transition:background .2s; }
  .btn-del:hover { background:rgba(200,70,70,.3); }

  /* Pagination */
  .pagination { margin-top:2rem; display:flex; gap:.5rem; }
  .pagination a { font-family:monospace; font-size:.65rem; letter-spacing:.08em;
                  padding:.4rem .8rem; border:1px solid var(--border); color:var(--cream2);
                  text-decoration:none; transition:border-color .2s, color .2s; }
  .pagination a:hover, .pagination a.active { border-color:var(--gold); color:var(--gold); }

  /* Empty state */
  .empty { padding:4rem; text-align:center; color:var(--muted);
           font-family:monospace; font-size:.7rem; letter-spacing:.1em; }
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo">VØ<span>I</span>D</div>
  <nav class="sidebar-nav">
    <a href="?status=all" class="<?= $statusFilter==='all'?'active':'' ?>">
      All <?= $stats['total'] ? "<span class='badge'>{$stats['total']}</span>" : '' ?>
    </a>
    <a href="?status=new" class="<?= $statusFilter==='new'?'active':'' ?>">
      New <?= $stats['new_count'] ? "<span class='badge'>{$stats['new_count']}</span>" : '' ?>
    </a>
    <a href="?status=read"    class="<?= $statusFilter==='read'?'active':'' ?>">Read</a>
    <a href="?status=replied" class="<?= $statusFilter==='replied'?'active':'' ?>">Replied</a>
    <a href="?status=archived"class="<?= $statusFilter==='archived'?'active':'' ?>">Archived</a>
  </nav>
  <a href="?logout=1" class="logout">← Sign out</a>
</aside>

<!-- Main -->
<main class="main">

  <!-- Stats -->
  <div class="stats">
    <div class="stat">
      <div class="stat-n"><?= (int)$stats['total'] ?></div>
      <div class="stat-l">Total</div>
    </div>
    <div class="stat">
      <div class="stat-n" style="color:var(--gold)"><?= (int)$stats['new_count'] ?></div>
      <div class="stat-l">New</div>
    </div>
    <div class="stat">
      <div class="stat-n" style="color:#6a8ac9"><?= (int)$stats['read_count'] ?></div>
      <div class="stat-l">Read</div>
    </div>
    <div class="stat">
      <div class="stat-n" style="color:#6ac98a"><?= (int)$stats['replied_count'] ?></div>
      <div class="stat-l">Replied</div>
    </div>
  </div>

  <!-- Search -->
  <div class="toolbar">
    <form method="GET" style="display:contents">
      <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
      <input type="search" name="q" placeholder="Search name, email, message…"
             value="<?= htmlspecialchars($search) ?>">
      <button class="btn-sm" type="submit">Search</button>
      <?php if ($search): ?>
        <a class="btn-sm" href="?status=<?= urlencode($statusFilter) ?>" style="text-decoration:none">Clear</a>
      <?php endif; ?>
    </form>
    <span style="margin-left:auto;font-family:monospace;font-size:.65rem;color:var(--muted)">
      <?= $total ?> result<?= $total !== 1 ? 's' : '' ?>
    </span>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name / Email</th>
          <th>Project</th>
          <th>Message</th>
          <th>Status</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7"><div class="empty">No submissions found.</div></td></tr>
        <?php else: foreach ($rows as $row): ?>
        <tr>
          <td style="color:var(--muted);font-family:monospace;font-size:.65rem"><?= (int)$row['id'] ?></td>
          <td>
            <div class="td-name"><?= htmlspecialchars($row['name']) ?></div>
            <div class="td-email"><?= htmlspecialchars($row['email']) ?></div>
          </td>
          <td><div class="td-project"><?= htmlspecialchars($row['project_type'] ?: '—') ?></div></td>
          <td><div class="td-msg"><?= htmlspecialchars(mb_substr($row['message'], 0, 120)) ?><?= mb_strlen($row['message']) > 120 ? '…' : '' ?></div></td>
          <td>
            <span class="status-badge" style="background:<?= $statusColors[$row['status']] ?>22;color:<?= $statusColors[$row['status']] ?>;border:1px solid <?= $statusColors[$row['status']] ?>44">
              <?= $row['status'] ?>
            </span>
          </td>
          <td class="td-date"><?= date('M j, Y', strtotime($row['created_at'])) ?><br><?= date('H:i', strtotime($row['created_at'])) ?></td>
          <td>
            <div class="actions">
              <!-- Update status -->
              <form method="POST" style="display:flex;gap:.3rem;align-items:center">
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <select name="status">
                  <?php foreach (['new','read','replied','archived'] as $s): ?>
                    <option value="<?= $s ?>" <?= $row['status']===$s?'selected':'' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn-sm" name="update_status" value="1">Save</button>
              </form>
              <!-- Reply link -->
              <a class="btn-sm" href="mailto:<?= htmlspecialchars($row['email']) ?>?subject=Re: Your enquiry" style="text-decoration:none">Reply</a>
              <!-- Delete -->
              <form method="POST" onsubmit="return confirm('Delete this submission?')">
                <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                <button class="btn-del" type="submit">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="?status=<?= urlencode($statusFilter) ?>&q=<?= urlencode($search) ?>&page=<?= $i ?>"
         class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

</main>
</body>
</html>
