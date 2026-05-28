<?php
/**
 * Apurvi Industries — Inquiry Form Handler
 * Receives POST from /contact and / (homepage) forms, sends email to sales team.
 * No customer auto-reply (per requirements).
 */

// ============ CONFIG ============
$RECIPIENT       = 'sales.india@apurviind.com';
$FROM_NAME       = 'Apurvi Industries Website';
$FROM_EMAIL      = 'no-reply@apurviind.com';   // must exist as a cPanel email account OR be a valid sender on this domain
$LOG_FILE        = __DIR__ . '/inquiries.log'; // also saved here as backup
$RATE_LIMIT_SECS = 30;                          // one submission per IP per N seconds
// ================================

header('Content-Type: application/json; charset=utf-8');

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// --- Honeypot anti-spam: bots fill this hidden field, humans don't ---
if (!empty($_POST['_gotcha'])) {
    // Silently pretend success so the bot moves on
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

// --- Helper: clean text input (strip control chars, trim, length cap) ---
function clean($key, $max = 500) {
    $v = $_POST[$key] ?? '';
    $v = is_string($v) ? $v : '';
    $v = str_replace(["\r", "\n", "\0"], ' ', $v); // header-injection safety
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

// --- Country code -> readable name ---
$countryMap = [
    'IN' => 'India', 'US' => 'United States', 'GB' => 'United Kingdom',
    'AE' => 'United Arab Emirates', 'DE' => 'Germany', 'IT' => 'Italy',
    'TR' => 'Turkey', 'PH' => 'Philippines', 'AU' => 'Australia',
    'CA' => 'Canada', 'MX' => 'Mexico',
];
$countryName = $countryMap[$country] ?? $country;

// --- Build email body (plain text — most reliable across mail clients) ---
$submittedAt = (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s') . ' IST';
$source      = $_SERVER['HTTP_REFERER'] ?? 'unknown';

$body  = "New inquiry received via apurviind.com\n";
$body .= "===========================================\n\n";
$body .= "--- CONTACT INFO ---\n";
$body .= "Name        : {$contact_person}\n";
$body .= "Designation : " . ($designation ?: '-') . "\n";
$body .= "Company     : {$company}\n";
$body .= "Phone       : {$phone}\n";
$body .= "Email       : {$email}\n";
$body .= "Fax         : " . ($fax ?: '-') . "\n";
$body .= "Website     : " . ($website ?: '-') . "\n\n";
$body .= "--- ADDRESS ---\n";
$body .= "Address     : {$address}\n";
$body .= "City        : " . ($city ?: '-') . "\n";
$body .= "State       : " . ($state ?: '-') . "\n";
$body .= "Country     : {$countryName}\n";
$body .= "Zip         : " . ($zip ?: '-') . "\n\n";
$body .= "--- BUSINESS ---\n";
$body .= "Nature      : " . ($business ?: '-') . "\n\n";
$body .= "--- ENQUIRY ---\n";
$body .= ($requirement ?: '(no message provided)') . "\n\n";
$body .= "===========================================\n";
$body .= "Submitted   : {$submittedAt}\n";
$body .= "Source page : {$source}\n";
$body .= "IP          : {$ip}\n";

// --- Headers (mb_encode to support special chars in subject) ---
$subject = '=?UTF-8?B?' . base64_encode("New Inquiry from {$company} — Apurvi Industries") . '?=';
$headers = [
    'From: ' . $FROM_NAME . ' <' . $FROM_EMAIL . '>',
    'Reply-To: ' . $contact_person . ' <' . $email . '>',
    'X-Mailer: PHP/' . phpversion(),
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
];

// --- Backup log (so submissions are never lost even if mail() fails) ---
@file_put_contents(
    $LOG_FILE,
    "[{$submittedAt}] {$company} | {$contact_person} | {$email} | {$phone}\n{$body}\n--- END ---\n\n",
    FILE_APPEND | LOCK_EX
);

// --- Send mail ---
$sent = @mail($RECIPIENT, $subject, $body, implode("\r\n", $headers), "-f {$FROM_EMAIL}");

if (!$sent) {
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Mail server temporarily unavailable. We have logged your inquiry — please also reach us at sales.india@apurviind.com or +91 8128664329.'
    ]);
    exit;
}

echo json_encode([
    'ok'      => true,
    'message' => 'Thank you. Your inquiry has been received — our team will get back to you within 24 hours.'
]);
