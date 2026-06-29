<?php
/**
 * Apurvi Industries — Inquiry Form Handler
 *
 * Receives POST from /contact and / (homepage) forms.
 * Stores every submission in a SQLite database (primary, reliable record),
 * also writes a plaintext log (backup), and attempts to send email (best-effort).
 *
 * The dashboard at /admin.php reads from the SQLite DB.
 */

// ============ CONFIG ============
$RECIPIENT       = 'sales.india@apurviind.com';
$FROM_NAME       = 'Apurvi Industries Website';
$FROM_EMAIL      = 'sales.india@apurviind.com';
$PRIVATE_DIR     = __DIR__ . '/private';
$DB_FILE         = $PRIVATE_DIR . '/inquiries.db';
$LOG_FILE        = $PRIVATE_DIR . '/inquiries.log';
$RATE_LIMIT_SECS = 30;
// ================================

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// --- Honeypot anti-spam ---
if (!empty($_POST['_gotcha'])) {
    echo json_encode(['ok' => true, 'message' => 'Thank you.']);
    exit;
}

// --- Rate limit per IP ---
$ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . '/apurvi_rl_' . md5($ip);
if (file_exists($rateFile) && (time() - filemtime($rateFile)) < $RATE_LIMIT_SECS) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Please wait a moment before submitting again.']);
    exit;
}
@touch($rateFile);

// --- Helpers ---
function clean($key, $max = 500) {
    $v = $_POST[$key] ?? '';
    $v = is_string($v) ? $v : '';
    $v = str_replace(["\r", "\n", "\0"], ' ', $v);
    $v = trim($v);
    if (mb_strlen($v) > $max) $v = mb_substr($v, 0, $max);
    return $v;
}
function cleanMultiline($key, $max = 5000) {
    $v = $_POST[$key] ?? '';
    $v = is_string($v) ? $v : '';
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    $v = trim($v);
    if (mb_strlen($v) > $max) $v = mb_substr($v, 0, $max);
    return $v;
}

// --- Collect fields ---
$contact_person = clean('contact_person', 100);
$designation    = clean('designation', 100);
$company        = clean('company', 150);
$address        = clean('address', 300);
$city           = clean('city', 80);
$state          = clean('state', 80);
$country        = clean('country', 5);
$zip            = clean('zip', 15);
$phone          = clean('phone', 20);
$fax            = clean('fax', 25);
$email          = clean('email', 150);
$website        = clean('website', 200);
$business       = clean('business', 200);
$requirement    = cleanMultiline('requirement', 5000);

// --- Validate required fields ---
$errors = [];
if ($contact_person === '')                                          $errors[] = 'Contact person is required.';
if ($company === '')                                                 $errors[] = 'Company name is required.';
if ($address === '')                                                 $errors[] = 'Mailing address is required.';
if ($phone === '' || !preg_match('/^[0-9]{10}$/', $phone))           $errors[] = 'Valid 10-digit phone is required.';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'Valid email is required.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(' ', $errors)]);
    exit;
}

// --- Country code -> name ---
$countryMap = [
    'IN' => 'India', 'US' => 'United States', 'GB' => 'United Kingdom',
    'AE' => 'United Arab Emirates', 'DE' => 'Germany', 'IT' => 'Italy',
    'TR' => 'Turkey', 'PH' => 'Philippines', 'AU' => 'Australia',
    'CA' => 'Canada', 'MX' => 'Mexico',
];
$countryName = $countryMap[$country] ?? $country;

$submittedAt = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s');
$source      = $_SERVER['HTTP_REFERER'] ?? 'unknown';
$userAgent   = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);

// ============================================================
// Step 1: Ensure private/ exists and is locked down
// ============================================================
if (!is_dir($PRIVATE_DIR)) {
    @mkdir($PRIVATE_DIR, 0700, true);
}
$privHtaccess = $PRIVATE_DIR . '/.htaccess';
if (!file_exists($privHtaccess)) {
    @file_put_contents(
        $privHtaccess,
        "# Block all public access to this directory.\n" .
        "Require all denied\n" .
        "<IfModule !mod_authz_core.c>\n" .
        "    Order deny,allow\n" .
        "    Deny from all\n" .
        "</IfModule>\n"
    );
}

// ============================================================
// Step 2: Write to SQLite (PRIMARY — must succeed)
// ============================================================
$dbWritten = false;
$dbError   = null;
$inquiryId = null;
try {
    $pdo = new PDO('sqlite:' . $DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA journal_mode = WAL");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inquiries (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at      TEXT NOT NULL,
            contact_person  TEXT,
            designation     TEXT,
            company         TEXT,
            address         TEXT,
            city            TEXT,
            state           TEXT,
            country         TEXT,
            zip             TEXT,
            phone           TEXT,
            fax             TEXT,
            email           TEXT,
            website         TEXT,
            business        TEXT,
            requirement     TEXT,
            source_page     TEXT,
            ip              TEXT,
            user_agent      TEXT,
            is_read         INTEGER DEFAULT 0,
            mail_sent       INTEGER DEFAULT 0,
            notes           TEXT
        )
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_created  ON inquiries(created_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_is_read  ON inquiries(is_read)");

    $stmt = $pdo->prepare("
        INSERT INTO inquiries (
            created_at, contact_person, designation, company, address,
            city, state, country, zip, phone, fax, email, website,
            business, requirement, source_page, ip, user_agent
        ) VALUES (
            :created_at, :contact_person, :designation, :company, :address,
            :city, :state, :country, :zip, :phone, :fax, :email, :website,
            :business, :requirement, :source_page, :ip, :user_agent
        )
    ");
    $stmt->execute([
        ':created_at'     => $submittedAt,
        ':contact_person' => $contact_person,
        ':designation'    => $designation,
        ':company'        => $company,
        ':address'        => $address,
        ':city'           => $city,
        ':state'          => $state,
        ':country'        => $countryName,
        ':zip'            => $zip,
        ':phone'          => $phone,
        ':fax'            => $fax,
        ':email'          => $email,
        ':website'        => $website,
        ':business'       => $business,
        ':requirement'    => $requirement,
        ':source_page'    => $source,
        ':ip'             => $ip,
        ':user_agent'     => $userAgent,
    ]);
    $inquiryId = (int) $pdo->lastInsertId();
    $dbWritten = true;
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

// ============================================================
// Step 3: Plaintext log (BACKUP — runs even if DB failed)
// ============================================================
$logBody  = "[{$submittedAt} IST]";
$logBody .= " id=" . ($inquiryId ?: '-');
$logBody .= " | {$company} | {$contact_person} | {$email} | {$phone}\n";
$logBody .= "Address: {$address}, {$city}, {$state}, {$countryName} {$zip}\n";
$logBody .= "Business: " . ($business ?: '-') . "\n";
$logBody .= "Requirement:\n" . ($requirement ?: '(none)') . "\n";
$logBody .= "Source: {$source} | IP: {$ip}\n";
if ($dbError) $logBody .= "!! DB write failed: {$dbError}\n";
$logBody .= "--- END ---\n\n";
@file_put_contents($LOG_FILE, $logBody, FILE_APPEND | LOCK_EX);

// ============================================================
// Step 4: Respond to the browser.
//
// We intentionally do NOT call mail() from this request. On the
// current cPanel host, the local mailer hangs ~60s before timing
// out, which makes the entire form feel broken. The dashboard at
// /admin.php is the source of truth — every inquiry is captured
// in the SQLite DB above. If/when proper SMTP (PHPMailer + a real
// provider) is wired up, that goes here.
// ============================================================
if ($dbWritten) {
    echo json_encode([
        'ok'      => true,
        'message' => 'Thank you. Your inquiry has been received — our team will get back to you within 24 hours.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Submission temporarily failed. Please email sales.india@apurviind.com or call +91 8128664329.'
    ]);
}
