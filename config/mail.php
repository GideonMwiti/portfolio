<?php
// ============================================================
// PHPMailer / SMTP Configuration  
// ============================================================
// cPanel SMTP settings — fill in your cPanel hosting details
//
// SMTP_HOST : Your hosting mail server, e.g. mail.sericsoft.com
// SMTP_USER : Full email address, e.g. ceo@sericsoft.com
// SMTP_PASS : The email account password from cPanel
// SMTP_PORT : 465 for SSL, 587 for TLS (465 recommended for cPanel)
// SMTP_FROM : Sender address (same as SMTP_USER usually)
// SMTP_NAME : Display name shown in "From" field
// ADMIN_EMAIL: Where admin notifications are sent
// ============================================================

define('SMTP_HOST',   'mail.sericsoft.com');   // ← Your cPanel mail host
define('SMTP_USER',   'ceo@sericsoft.com');    // ← cPanel email address
define('SMTP_PASS',   'YOUR_EMAIL_PASSWORD');  // ← Your email password
define('SMTP_PORT',   465);                    // 465 = SSL, 587 = TLS
define('SMTP_SECURE', 'ssl');                  // 'ssl' or 'tls'
define('SMTP_FROM',   'ceo@sericsoft.com');
define('SMTP_NAME',   'Gideon Mwiti · Sericsoft');
define('ADMIN_EMAIL', 'ceo@sericsoft.com');

require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';
require_once __DIR__ . '/../phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(SMTP_FROM, SMTP_NAME);
    return $mail;
}
