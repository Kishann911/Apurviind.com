<?php
/**
 * Apurvi Industries — Inquiry Dashboard
 *
 * Single-file admin panel that reads from private/inquiries.db.
 *
 * First visit: prompts you to set an admin password (saved as bcrypt hash in private/admin.hash).
 * Subsequent visits: password login. Session-based auth.
 *
 * URL: https://apurviind.com/admin.php
 */

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$PRIVATE_DIR = __DIR__ . '/private';
$DB_FILE     = $PRIVATE_DIR . '/inquiries.db';
$HASH_FILE   = $PRIVATE_DIR . '/admin.hash';

// Ensure private dir & lockdown
if (!is_dir($PRIVATE_DIR)) {
    @mkdir($PRIVATE_DIR, 0700, true);
}
$privHtaccess = $PRIVATE_DIR . '/.htaccess';
if (!file_exists($privHtaccess)) {
    @file_put_contents(
        $privHtaccess,
        "Require all denied\n" .
        "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n"
    );
}

// ---------- Helpers ----------
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check() {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '_')) {
        http_response_code(403);
        exit('CSRF check failed. Reload the page and try again.');
    }
}
function render_page_start($title) {
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?> — Apurvi Inquiries</title>
<style>
:root {
    --bg: #f5f6f8;
    --surface: #ffffff;
    --border: #e3e6ea;
    --border-strong: #c9ced4;
    --text: #1d2125;
    --muted: #6b7480;
    --accent: #c8521f;       /* Apurvi orange */
    --accent-dark: #a3411a;
    --accent-soft: #fbeee6;
    --unread: #c8521f;
    --success: #1f7a4f;
    --danger: #b13434;
    --shadow: 0 1px 2px rgba(20,25,35,.04), 0 4px 12px rgba(20,25,35,.05);
}
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: var(--bg); color: var(--text); font: 15px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
a { color: var(--accent-dark); text-decoration: none; }
a:hover { text-decoration: underline; }

.topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 14px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky; top: 0; z-index: 10;
}
.topbar .brand { font-weight: 700; font-size: 17px; letter-spacing: .2px; }
.topbar .brand span { color: var(--accent); }
.topbar .nav { display: flex; gap: 14px; align-items: center; font-size: 14px; }
.topbar .nav a { color: var(--muted); }
.topbar .nav a:hover { color: var(--text); text-decoration: none; }

.container { max-width: 1180px; margin: 28px auto; padding: 0 24px; }

.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 22px; }
.stat { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 16px 18px; }
.stat .label { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .8px; font-weight: 600; }
.stat .value { font-size: 28px; font-weight: 700; margin-top: 6px; line-height: 1; }
.stat .value.accent { color: var(--accent); }

.toolbar { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; padding: 14px 16px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
.toolbar form { display: flex; gap: 8px; flex: 1; min-width: 240px; }
.toolbar input[type=search] {
    flex: 1; padding: 9px 12px; font-size: 14px;
    border: 1px solid var(--border-strong); border-radius: 6px;
    background: #fff; color: var(--text);
}
.toolbar input[type=search]:focus { outline: 2px solid var(--accent); outline-offset: -1px; border-color: var(--accent); }
.toolbar .filters { display: flex; gap: 6px; }
.toolbar .filter {
    padding: 8px 14px; font-size: 13px; font-weight: 600;
    border: 1px solid var(--border-strong); background: #fff; color: var(--text);
    border-radius: 6px; cursor: pointer; text-decoration: none;
}
.toolbar .filter.active { background: var(--accent); border-color: var(--accent); color: #fff; }
.toolbar .filter:hover:not(.active) { background: #f0f2f5; }
.toolbar .spacer { flex: 1; }

.btn {
    display: inline-block; padding: 9px 16px; font-size: 14px; font-weight: 600;
    border: 1px solid var(--accent); background: var(--accent); color: #fff;
    border-radius: 6px; cursor: pointer; text-decoration: none; line-height: 1;
}
.btn:hover { background: var(--accent-dark); border-color: var(--accent-dark); text-decoration: none; color: #fff; }
.btn.secondary { background: #fff; color: var(--text); border-color: var(--border-strong); }
.btn.secondary:hover { background: #f0f2f5; color: var(--text); }
.btn.danger { background: var(--danger); border-color: var(--danger); }
.btn.danger:hover { background: #8a2828; border-color: #8a2828; }
.btn.small { padding: 6px 10px; font-size: 13px; }

table.inquiries { width: 100%; border-collapse: collapse; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; box-shadow: var(--shadow); }
table.inquiries th { text-align: left; padding: 12px 14px; font-size: 12px; text-transform: uppercase; letter-spacing: .6px; color: var(--muted); border-bottom: 1px solid var(--border); background: #fafbfc; font-weight: 600; }
table.inquiries td { padding: 14px; border-bottom: 1px solid var(--border); vertical-align: top; font-size: 14px; }
table.inquiries tr:last-child td { border-bottom: 0; }
table.inquiries tr.unread td { background: #fffbf6; }
table.inquiries tr:hover td { background: var(--accent-soft); cursor: pointer; }
table.inquiries .pill { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 7px; border-radius: 10px; text-transform: uppercase; letter-spacing: .5px; }
table.inquiries .pill.new { background: var(--accent); color: #fff; }
table.inquiries .pill.read { background: #eef0f3; color: var(--muted); }
table.inquiries .when { color: var(--muted); font-size: 13px; white-space: nowrap; }
table.inquiries .company { font-weight: 600; }
table.inquiries .excerpt { color: var(--muted); font-size: 13px; max-width: 380px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

.empty { background: var(--surface); border: 1px dashed var(--border-strong); border-radius: 8px; padding: 48px 24px; text-align: center; color: var(--muted); }
.empty h3 { margin: 0 0 6px; color: var(--text); font-weight: 600; }

/* Detail view */
.detail { background: var(--surface); border: 1px solid var(--border); border-radius: 8px; box-shadow: var(--shadow); overflow: hidden; }
.detail .head { padding: 22px 26px 18px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; }
.detail .head h1 { margin: 0 0 4px; font-size: 22px; }
.detail .head .meta { color: var(--muted); font-size: 13px; }
.detail .head .actions { display: flex; gap: 8px; flex-wrap: wrap; }
.detail .body { padding: 22px 26px; display: grid; grid-template-columns: 1fr 1fr; gap: 22px 36px; }
.detail .body .full { grid-column: 1 / -1; }
.detail .field { font-size: 14px; }
.detail .field .k { font-size: 11px; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); font-weight: 600; margin-bottom: 4px; }
.detail .field .v { color: var(--text); word-break: break-word; }
.detail .field .v.msg { background: #fafbfc; border: 1px solid var(--border); border-radius: 6px; padding: 14px 16px; white-space: pre-wrap; line-height: 1.6; }
.detail .body a.email-link, .detail .body a.phone-link { color: var(--accent-dark); }
.notes-form { padding: 18px 26px 22px; border-top: 1px solid var(--border); background: #fafbfc; }
.notes-form label { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); font-weight: 600; margin-bottom: 6px; }
.notes-form textarea { width: 100%; min-height: 80px; padding: 10px 12px; border: 1px solid var(--border-strong); border-radius: 6px; font-family: inherit; font-size: 14px; resize: vertical; }
.notes-form textarea:focus { outline: 2px solid var(--accent); outline-offset: -1px; border-color: var(--accent); }
.notes-form .row { display: flex; justify-content: flex-end; margin-top: 10px; }

/* Login / setup */
.auth-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
.auth-card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 36px 32px; width: 100%; max-width: 380px; box-shadow: var(--shadow); }
.auth-card h1 { margin: 0 0 4px; font-size: 22px; }
.auth-card p.sub { margin: 0 0 22px; color: var(--muted); font-size: 14px; }
.auth-card label { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); font-weight: 600; margin-bottom: 6px; }
.auth-card input[type=password] { width: 100%; padding: 10px 12px; font-size: 15px; border: 1px solid var(--border-strong); border-radius: 6px; margin-bottom: 14px; }
.auth-card input[type=password]:focus { outline: 2px solid var(--accent); outline-offset: -1px; border-color: var(--accent); }
.auth-card .btn { width: 100%; padding: 11px; font-size: 15px; }
.auth-card .err { background: #fdecec; color: var(--danger); border: 1px solid #f5c2c2; padding: 10px 12px; border-radius: 6px; font-size: 13px; margin-bottom: 14px; }
.auth-card .hint { font-size: 12px; color: var(--muted); margin-top: 12px; line-height: 1.5; }

.flash { background: #e9f6ef; border: 1px solid #b9e0cb; color: #1d6741; padding: 10px 14px; border-radius: 6px; font-size: 13px; margin-bottom: 14px; }

@media (max-width: 720px) {
    .stats { grid-template-columns: 1fr 1fr; }
    .detail .body { grid-template-columns: 1fr; gap: 16px; }
    table.inquiries thead { display: none; }
    table.inquiries, table.inquiries tbody, table.inquiries tr, table.inquiries td { display: block; width: 100%; }
    table.inquiries tr { border-bottom: 1px solid var(--border); padding: 12px 14px; }
    table.inquiries tr:last-child { border-bottom: 0; }
    table.inquiries td { padding: 3px 0; border: 0; }
    table.inquiries .when { margin-bottom: 4px; }
}
</style>
</head>
<body>
<?php
}
function render_page_end() { ?>
</body>
</html>
<?php }

// ============================================================
// Bootstrap: setup admin password on first run
// ============================================================
if (!file_exists($HASH_FILE)) {
    $setupError = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $p1 = $_POST['new_password']     ?? '';
        $p2 = $_POST['confirm_password'] ?? '';
        if (strlen($p1) < 8) {
            $setupError = 'Password must be at least 8 characters.';
        } elseif ($p1 !== $p2) {
            $setupError = 'Passwords do not match.';
        } else {
            $hash = password_hash($p1, PASSWORD_DEFAULT);
            if (@file_put_contents($HASH_FILE, $hash) === false) {
                $setupError = 'Could not save password file. Check permissions on /private directory.';
            } else {
                @chmod($HASH_FILE, 0600);
                header('Location: admin.php?just_setup=1'); exit;
            }
        }
    }
    render_page_start('Setup');
    ?>
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Set admin password</h1>
            <p class="sub">First-time setup. This password protects the inquiry dashboard.</p>
            <?php if ($setupError): ?><div class="err"><?= h($setupError) ?></div><?php endif; ?>
            <form method="POST" autocomplete="off">
                <label for="np">New password</label>
                <input id="np" type="password" name="new_password" autocomplete="new-password" minlength="8" required autofocus>
                <label for="cp">Confirm password</label>
                <input id="cp" type="password" name="confirm_password" autocomplete="new-password" minlength="8" required>
                <button type="submit" class="btn">Create password</button>
                <p class="hint">Keep this password safe. It is stored as a bcrypt hash in <code>/private/admin.hash</code> — it cannot be recovered, only reset by deleting that file.</p>
            </form>
        </div>
    </div>
    <?php
    render_page_end();
    exit;
}

// ============================================================
// Logout
// ============================================================
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: admin.php?bye=1');
    exit;
}

// ============================================================
// Login
// ============================================================
$loggedIn = !empty($_SESSION['admin_authed']);
if (!$loggedIn) {
    $loginError = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // simple per-IP login rate limit
        $rlKey = sys_get_temp_dir() . '/apurvi_login_rl_' . md5($_SERVER['REMOTE_ADDR'] ?? 'x');
        if (file_exists($rlKey) && (time() - filemtime($rlKey)) < 3) {
            $loginError = 'Too many attempts. Wait a few seconds.';
        } else {
            @touch($rlKey);
            $hash = trim((string)@file_get_contents($HASH_FILE));
            if ($hash && password_verify($_POST['password'] ?? '', $hash)) {
                session_regenerate_id(true);
                $_SESSION['admin_authed'] = true;
                header('Location: admin.php'); exit;
            } else {
                $loginError = 'Incorrect password.';
            }
        }
    }
    render_page_start('Login');
    ?>
    <div class="auth-wrap">
        <div class="auth-card">
            <h1>Apurvi <span style="color:var(--accent)">Inquiries</span></h1>
            <p class="sub">Sign in to view submissions.</p>
            <?php if (!empty($_GET['just_setup'])): ?><div class="flash">Password set. Sign in below.</div><?php endif; ?>
            <?php if (!empty($_GET['bye'])): ?><div class="flash">Signed out.</div><?php endif; ?>
            <?php if ($loginError): ?><div class="err"><?= h($loginError) ?></div><?php endif; ?>
            <form method="POST" autocomplete="off">
                <label for="pw">Password</label>
                <input id="pw" type="password" name="password" autocomplete="current-password" required autofocus>
                <button type="submit" class="btn">Sign in</button>
            </form>
        </div>
    </div>
    <?php
    render_page_end();
    exit;
}

// ============================================================
// Authed beyond this point
// ============================================================
try {
    $pdo = new PDO('sqlite:' . $DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Defensive — create schema if no inquiry has been submitted yet
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inquiries (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at      TEXT NOT NULL,
            contact_person  TEXT, designation TEXT, company TEXT, address TEXT,
            city TEXT, state TEXT, country TEXT, zip TEXT, phone TEXT, fax TEXT,
            email TEXT, website TEXT, business TEXT, requirement TEXT,
            source_page TEXT, ip TEXT, user_agent TEXT,
            is_read INTEGER DEFAULT 0, mail_sent INTEGER DEFAULT 0, notes TEXT
        )
    ");
} catch (Throwable $e) {
    render_page_start('Error');
    echo '<div class="container"><div class="empty"><h3>Database error</h3><p>'. h($e->getMessage()) .'</p></div></div>';
    render_page_end();
    exit;
}

// ---------- Actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $back = $_POST['back'] ?? 'admin.php';
    if ($id > 0) {
        switch ($_POST['action']) {
            case 'mark_read':
                $pdo->prepare("UPDATE inquiries SET is_read = 1 WHERE id = ?")->execute([$id]);
                break;
            case 'mark_unread':
                $pdo->prepare("UPDATE inquiries SET is_read = 0 WHERE id = ?")->execute([$id]);
                break;
            case 'delete':
                $pdo->prepare("DELETE FROM inquiries WHERE id = ?")->execute([$id]);
                $back = 'admin.php';
                break;
            case 'save_notes':
                $notes = substr((string)($_POST['notes'] ?? ''), 0, 2000);
                $pdo->prepare("UPDATE inquiries SET notes = ? WHERE id = ?")->execute([$notes, $id]);
                break;
        }
    }
    header('Location: ' . $back);
    exit;
}

// ---------- CSV Export ----------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="apurvi-inquiries-'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    // BOM for Excel UTF-8
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID','Submitted (IST)','Name','Designation','Company','Email','Phone','Address','City','State','Country','Zip','Website','Business','Requirement','Source Page','IP','Read','Notes']);
    $rows = $pdo->query("SELECT * FROM inquiries ORDER BY id DESC");
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['created_at'], $r['contact_person'], $r['designation'], $r['company'],
            $r['email'], $r['phone'], $r['address'], $r['city'], $r['state'], $r['country'], $r['zip'],
            $r['website'], $r['business'], $r['requirement'], $r['source_page'], $r['ip'],
            $r['is_read'] ? 'yes' : 'no', $r['notes'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// ---------- Detail view ----------
if (!empty($_GET['view'])) {
    $id = (int)$_GET['view'];
    $stmt = $pdo->prepare("SELECT * FROM inquiries WHERE id = ?");
    $stmt->execute([$id]);
    $inq = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$inq) {
        render_page_start('Not found');
        echo '<div class="container"><div class="empty"><h3>Inquiry not found</h3><p>It may have been deleted.</p><p style="margin-top:14px"><a class="btn secondary" href="admin.php">← Back to inbox</a></p></div></div>';
        render_page_end();
        exit;
    }
    // Auto-mark read on view
    if (!$inq['is_read']) {
        $pdo->prepare("UPDATE inquiries SET is_read = 1 WHERE id = ?")->execute([$id]);
        $inq['is_read'] = 1;
    }
    render_page_start('Inquiry #' . $id);
    ?>
    <div class="topbar">
        <div class="brand">Apurvi <span>Inquiries</span></div>
        <div class="nav">
            <a href="admin.php">← All inquiries</a>
            <a href="admin.php?logout=1">Sign out</a>
        </div>
    </div>
    <div class="container">
        <div class="detail">
            <div class="head">
                <div>
                    <h1><?= h($inq['company'] ?: '(no company)') ?></h1>
                    <div class="meta">Inquiry #<?= (int)$inq['id'] ?> · Received <?= h($inq['created_at']) ?> IST</div>
                </div>
                <div class="actions">
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$inq['id'] ?>">
                        <input type="hidden" name="back" value="admin.php?view=<?= (int)$inq['id'] ?>">
                        <input type="hidden" name="action" value="<?= $inq['is_read'] ? 'mark_unread' : 'mark_read' ?>">
                        <button class="btn secondary small" type="submit"><?= $inq['is_read'] ? 'Mark unread' : 'Mark read' ?></button>
                    </form>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Permanently delete this inquiry? This cannot be undone.')">
                        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= (int)$inq['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn danger small" type="submit">Delete</button>
                    </form>
                </div>
            </div>
            <div class="body">
                <div class="field"><div class="k">Contact</div><div class="v"><?= h($inq['contact_person']) ?><?php if ($inq['designation']): ?> <span style="color:var(--muted)">· <?= h($inq['designation']) ?></span><?php endif; ?></div></div>
                <div class="field"><div class="k">Company</div><div class="v"><?= h($inq['company']) ?></div></div>
                <div class="field"><div class="k">Email</div><div class="v"><a class="email-link" href="mailto:<?= h($inq['email']) ?>"><?= h($inq['email']) ?></a></div></div>
                <div class="field"><div class="k">Phone</div><div class="v"><a class="phone-link" href="tel:<?= h($inq['phone']) ?>"><?= h($inq['phone']) ?></a></div></div>
                <?php if ($inq['fax']): ?><div class="field"><div class="k">Fax</div><div class="v"><?= h($inq['fax']) ?></div></div><?php endif; ?>
                <?php if ($inq['website']): ?><div class="field"><div class="k">Website</div><div class="v"><a href="<?= h($inq['website']) ?>" target="_blank" rel="noopener"><?= h($inq['website']) ?></a></div></div><?php endif; ?>
                <div class="field full"><div class="k">Address</div><div class="v"><?= h($inq['address']) ?><?php
                    $loc = array_filter([$inq['city'], $inq['state'], $inq['country'], $inq['zip']]);
                    if ($loc) echo '<br>'. h(implode(', ', $loc));
                ?></div></div>
                <?php if ($inq['business']): ?><div class="field full"><div class="k">Nature of business</div><div class="v"><?= h($inq['business']) ?></div></div><?php endif; ?>
                <div class="field full"><div class="k">Requirement</div><div class="v msg"><?= nl2br(h($inq['requirement'] ?: '(none)')) ?></div></div>
                <div class="field"><div class="k">Source page</div><div class="v" style="color:var(--muted); font-size:13px"><?= h($inq['source_page'] ?: '-') ?></div></div>
                <div class="field"><div class="k">IP address</div><div class="v" style="color:var(--muted); font-size:13px"><?= h($inq['ip']) ?></div></div>
            </div>
            <form method="POST" class="notes-form">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$inq['id'] ?>">
                <input type="hidden" name="back" value="admin.php?view=<?= (int)$inq['id'] ?>">
                <input type="hidden" name="action" value="save_notes">
                <label for="notes">Internal notes (only visible here)</label>
                <textarea id="notes" name="notes" placeholder="e.g. Called on 2026-06-26, sent quote, follow up next week"><?= h($inq['notes'] ?? '') ?></textarea>
                <div class="row"><button class="btn" type="submit">Save notes</button></div>
            </form>
        </div>
    </div>
    <?php
    render_page_end();
    exit;
}

// ---------- List view ----------
$search = trim((string)($_GET['q'] ?? ''));
$filter = $_GET['filter'] ?? 'all';
$where  = [];
$params = [];
if ($search !== '') {
    $where[] = '(company LIKE ? OR contact_person LIKE ? OR email LIKE ? OR phone LIKE ? OR requirement LIKE ?)';
    $like = '%' . $search . '%';
    for ($i = 0; $i < 5; $i++) $params[] = $like;
}
if ($filter === 'unread') $where[] = 'is_read = 0';
if ($filter === 'read')   $where[] = 'is_read = 1';

$sql = 'SELECT * FROM inquiries' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 500';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCount  = (int)$pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE is_read = 0")->fetchColumn();
$today       = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
$todayCount  = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE substr(created_at,1,10) = '". $today ."'")->fetchColumn();

render_page_start('Inbox');
?>
<div class="topbar">
    <div class="brand">Apurvi <span>Inquiries</span></div>
    <div class="nav">
        <a href="admin.php?export=csv">Export CSV</a>
        <a href="admin.php?logout=1">Sign out</a>
    </div>
</div>
<div class="container">
    <div class="stats">
        <div class="stat"><div class="label">Total inquiries</div><div class="value"><?= number_format($totalCount) ?></div></div>
        <div class="stat"><div class="label">Unread</div><div class="value accent"><?= number_format($unreadCount) ?></div></div>
        <div class="stat"><div class="label">Today</div><div class="value"><?= number_format($todayCount) ?></div></div>
    </div>

    <div class="toolbar">
        <form method="GET" action="admin.php">
            <input type="search" name="q" value="<?= h($search) ?>" placeholder="Search company, name, email, phone, message…">
            <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= h($filter) ?>"><?php endif; ?>
            <button class="btn" type="submit">Search</button>
            <?php if ($search !== '' || $filter !== 'all'): ?><a class="btn secondary" href="admin.php">Clear</a><?php endif; ?>
        </form>
        <div class="filters">
            <?php $qs = $search !== '' ? '&q='.urlencode($search) : ''; ?>
            <a class="filter <?= $filter==='all'?'active':'' ?>" href="admin.php?filter=all<?= $qs ?>">All</a>
            <a class="filter <?= $filter==='unread'?'active':'' ?>" href="admin.php?filter=unread<?= $qs ?>">Unread</a>
            <a class="filter <?= $filter==='read'?'active':'' ?>" href="admin.php?filter=read<?= $qs ?>">Read</a>
        </div>
    </div>

    <?php if (!$inquiries): ?>
        <div class="empty">
            <h3><?= $totalCount === 0 ? 'No inquiries yet' : 'No matches' ?></h3>
            <p><?= $totalCount === 0 ? 'New form submissions will appear here.' : 'Try a different search or clear the filters.' ?></p>
        </div>
    <?php else: ?>
        <table class="inquiries">
            <thead><tr>
                <th style="width:32px"></th>
                <th>Received</th>
                <th>Company / Contact</th>
                <th>Phone / Email</th>
                <th>Requirement</th>
            </tr></thead>
            <tbody>
            <?php foreach ($inquiries as $r):
                $url = 'admin.php?view=' . (int)$r['id'];
            ?>
                <tr class="<?= $r['is_read'] ? '' : 'unread' ?>" onclick="window.location='<?= h($url) ?>'">
                    <td><span class="pill <?= $r['is_read'] ? 'read' : 'new' ?>"><?= $r['is_read'] ? '·' : 'New' ?></span></td>
                    <td class="when"><?= h(substr($r['created_at'], 0, 16)) ?></td>
                    <td>
                        <div class="company"><a href="<?= h($url) ?>" onclick="event.stopPropagation()"><?= h($r['company'] ?: '(no company)') ?></a></div>
                        <div style="color:var(--muted); font-size:13px; margin-top:2px"><?= h($r['contact_person']) ?><?php if ($r['designation']): ?> · <?= h($r['designation']) ?><?php endif; ?></div>
                    </td>
                    <td>
                        <div><?= h($r['phone']) ?></div>
                        <div style="color:var(--muted); font-size:13px; margin-top:2px"><?= h($r['email']) ?></div>
                    </td>
                    <td><div class="excerpt"><?= h($r['requirement'] ?: '—') ?></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($totalCount > count($inquiries)): ?>
            <p style="color:var(--muted); font-size:13px; text-align:center; margin-top:14px">Showing latest 500. Use search to find older inquiries.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php
render_page_end();
