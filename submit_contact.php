<?php
// ============================================================
// Contact Form Submit Handler
// POST: name, email, subject, message
// Response: JSON { success: bool, message: string }
// ============================================================

declare(strict_types=1);

// Allow requests from same origin only
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// Parse JSON body (sent from JS fetch)
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    // Fallback to $_POST for regular form submissions
    $data = $_POST;
}

// ── 1. Validate ──────────────────────────────────────────────
$name    = trim($data['name']    ?? '');
$email   = trim($data['email']   ?? '');
$subject = trim($data['subject'] ?? '');
$message = trim($data['message'] ?? '');

$errors = [];
if (empty($name))                         $errors[] = 'Name is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if (empty($subject))                      $errors[] = 'Subject is required.';
if (empty($message))                      $errors[] = 'Message is required.';
if (strlen($message) < 10)               $errors[] = 'Message is too short.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

// Sanitize
$name    = htmlspecialchars($name,    ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
$ip      = $_SERVER['REMOTE_ADDR'] ?? '';
$ua      = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

// ── 2. Save to Database ───────────────────────────────────────
require_once __DIR__ . '/config/db.php';

$pdo = getDB();
$stmt = $pdo->prepare(
    'INSERT INTO contact_messages (name, email, subject, message, ip_address, user_agent)
     VALUES (:name, :email, :subject, :message, :ip, :ua)'
);
$stmt->execute([
    ':name'    => $name,
    ':email'   => $email,
    ':subject' => $subject,
    ':message' => $message,
    ':ip'      => $ip,
    ':ua'      => $ua,
]);

// ── 3. Send Emails ────────────────────────────────────────────
require_once __DIR__ . '/config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mailErrors = [];

// ── 3a. Admin notification email ──────────────────────────────
try {
    $adminMail = createMailer();
    $adminMail->addAddress(ADMIN_EMAIL, 'Gideon Mwiti');
    $adminMail->Subject  = "New Portfolio Inquiry – {$name}";
    $adminMail->isHTML(true);
    $adminMail->Body     = getAdminEmailBody($name, $email, $subject, $message, $ip);
    $adminMail->AltBody  = "New contact form submission\n\nFrom: {$name} <{$email}>\nSubject: {$subject}\n\nMessage:\n{$message}\n\nIP: {$ip}";
    $adminMail->send();
} catch (Exception $e) {
    $mailErrors[] = 'Admin email failed: ' . $e->getMessage();
}

// ── 3b. Confirmation email to sender ────────────────────────────
try {
    $confirmMail = createMailer();
    $confirmMail->addAddress($email, $name);
    $confirmMail->Subject = 'We received your message – Gideon Mwiti | Sericsoft';
    $confirmMail->isHTML(true);
    $confirmMail->Body    = getConfirmationEmailBody($name, $subject);
    $confirmMail->AltBody = "Hi {$name},\n\nThank you for reaching out! I've received your message and will get back to you within 24 hours.\n\nYour message subject: {$subject}\n\nBest regards,\nGideon Mwiti\nCEO, Sericsoft Innovations Ltd\nceo@sericsoft.com";
    $confirmMail->send();
} catch (Exception $e) {
    $mailErrors[] = 'Confirmation email failed: ' . $e->getMessage();
}

// ── 4. Respond ────────────────────────────────────────────────
echo json_encode([
    'success'     => true,
    'message'     => "Thanks {$name}! Your message has been received. We'll get back to you within 24 hours.",
    'mail_errors' => $mailErrors, // Empty in production; helpful during dev
]);


// ═══════════════════════════════════════════════════════════════
// Email Templates
// ═══════════════════════════════════════════════════════════════

function getAdminEmailBody(string $name, string $email, string $subject, string $message, string $ip): string {
    $time = date('D, d M Y H:i:s T');
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #060b18; color: #e2e8f0; margin: 0; padding: 0; }
    .wrap { max-width: 600px; margin: 32px auto; background: #0d1526; border: 1px solid rgba(0,212,255,0.2); border-radius: 16px; overflow: hidden; }
    .header { background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); padding: 32px 36px; }
    .header h1 { margin: 0; font-size: 22px; color: #ffffff; }
    .header p  { margin: 6px 0 0; color: rgba(255,255,255,0.6); font-size: 13px; }
    .badge { display: inline-block; background: rgba(0,212,255,0.15); border: 1px solid rgba(0,212,255,0.4); color: #00d4ff; border-radius: 20px; padding: 3px 12px; font-size: 11px; font-weight: 700; letter-spacing: 0.05em; margin-top: 10px; }
    .body { padding: 28px 36px; }
    .field { margin-bottom: 20px; }
    .field-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 700; margin-bottom: 6px; }
    .field-value { font-size: 15px; color: #e2e8f0; line-height: 1.6; background: rgba(255,255,255,0.04); padding: 12px 16px; border-radius: 8px; border-left: 3px solid #00d4ff; word-break: break-word; }
    .meta { font-size: 11px; color: #64748b; margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.06); }
    .cta { display: inline-block; margin-top: 20px; padding: 11px 24px; background: linear-gradient(135deg, #00d4ff, #6366f1); color: #060b18 !important; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 13px; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>📬 New Portfolio Inquiry</h1>
    <p>Someone sent a message via your contact form</p>
    <span class="badge">ACTION REQUIRED</span>
  </div>
  <div class="body">
    <div class="field">
      <div class="field-label">👤 Full Name</div>
      <div class="field-value">{$name}</div>
    </div>
    <div class="field">
      <div class="field-label">📧 Email Address</div>
      <div class="field-value"><a href="mailto:{$email}" style="color:#00d4ff;">{$email}</a></div>
    </div>
    <div class="field">
      <div class="field-label">📌 Subject</div>
      <div class="field-value">{$subject}</div>
    </div>
    <div class="field">
      <div class="field-label">💬 Message</div>
      <div class="field-value">{$message}</div>
    </div>
    <a href="mailto:{$email}?subject=Re: {$subject}" class="cta">Reply to {$name}</a>
    <div class="meta">
      🕐 Submitted: {$time}<br>
      🌐 IP Address: {$ip}
    </div>
  </div>
</div>
</body>
</html>
HTML;
}


function getConfirmationEmailBody(string $name, string $subject): string {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #060b18; color: #e2e8f0; margin: 0; padding: 0; }
    .wrap { max-width: 600px; margin: 32px auto; background: #0d1526; border: 1px solid rgba(0,212,255,0.2); border-radius: 16px; overflow: hidden; }
    .header { background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); padding: 36px 36px 28px; text-align: center; }
    .header h1 { margin: 0; font-size: 24px; color: #ffffff; }
    .header p  { margin: 8px 0 0; color: rgba(255,255,255,0.65); font-size: 14px; }
    .avatar { width: 72px; height: 72px; border-radius: 50%; border: 3px solid rgba(0,212,255,0.4); margin-bottom: 16px; }
    .body { padding: 32px 36px; }
    .hi { font-size: 20px; font-weight: 700; color: #f1f5f9; margin-bottom: 12px; }
    .text { font-size: 14px; color: #94a3b8; line-height: 1.8; margin-bottom: 20px; }
    .highlight { background: rgba(0,212,255,0.08); border: 1px solid rgba(0,212,255,0.2); border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; }
    .highlight-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 4px; }
    .highlight-val { font-size: 14px; color: #e2e8f0; font-weight: 600; }
    .divider { border: none; border-top: 1px solid rgba(255,255,255,0.06); margin: 24px 0; }
    .contact-row { display: flex; gap: 8px; flex-wrap: wrap; }
    .chip { display: inline-block; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 6px 12px; font-size: 12px; color: #94a3b8; margin: 4px 0; }
    .chip a { color: #00d4ff; text-decoration: none; }
    .footer { background: rgba(0,0,0,0.3); text-align: center; padding: 20px 36px; font-size: 12px; color: #475569; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>✅ Message Received!</h1>
    <p>Thank you for reaching out, {$name}.</p>
  </div>
  <div class="body">
    <div class="hi">Hi {$name} 👋</div>
    <p class="text">
      Your message has been successfully received. I personally review every inquiry and will get back to you
      <strong style="color:#00d4ff;">within 24 hours</strong>.<br><br>
      In the meantime, feel free to explore my portfolio or connect with me directly via the channels below.
    </p>

    <div class="highlight">
      <div class="highlight-label">Your Inquiry Subject</div>
      <div class="highlight-val">📌 {$subject}</div>
    </div>

    <hr class="divider">

    <p class="text" style="margin-bottom:10px;"><strong style="color:#e2e8f0;">Direct Contact</strong></p>
    <div class="chip">📧 <a href="mailto:ceo@sericsoft.com">ceo@sericsoft.com</a></div>
    <div class="chip">📞 <a href="tel:+254798985389">+254 798 985 389</a></div>
    <div class="chip">🌐 <a href="https://www.sericsoft.com">www.sericsoft.com</a></div>

    <hr class="divider">

    <p class="text">
      Best regards,<br>
      <strong style="color:#f1f5f9;">Gideon Mwiti</strong><br>
      <span style="color:#00d4ff;">CEO, Sericsoft Innovations Ltd</span>
    </p>
  </div>
  <div class="footer">
    © 2026 Gideon Mwiti · Sericsoft Innovations Ltd · This is an automated confirmation email.
  </div>
</div>
</body>
</html>
HTML;
}
