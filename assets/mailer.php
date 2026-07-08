<?php
/**
 * FINAL PRODUCTION CONTACT SYSTEM
 * PHPMailer SMTP + reCAPTCHA + HTML email preserved
 * cPanel ready - no Composer, no .env required
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// =========================
// LOAD PHPMailer (manual)
// =========================
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// =========================
// CONFIG
// =========================
define('RECAPTCHA_SECRET_KEY', '6LcdQ7IsAAAAADqJ_iWlKxNzJHiUQ_oQU3rlDCAj');

define('RECIPIENT_EMAIL', 'gumedethomas12@gmail.com');
define('MAIL_FROM', 'noreply@chumankozo.co.za');
define('MAIL_FROM_NAME', 'Chuma Nkozo Cooperative');

// =========================
// SMTP CONFIG (EDIT HERE ONLY)
// =========================
$smtp_host = 'mail.chumankozo.co.za';
$smtp_user = 'noreply@chumankozo.co.za';
$smtp_pass = 'Chuma.Safe@2026';
$smtp_port = 587;
$smtp_secure = 'tls';

// =========================
// LOGGING
// =========================
function log_error($type, $msg) {
    $dir = __DIR__ . '/logs';
    if (!file_exists($dir)) mkdir($dir, 0755, true);

    file_put_contents(
        $dir . '/contact.log',
        date('Y-m-d H:i:s') . " | $type | $msg\n",
        FILE_APPEND
    );
}

// =========================
// SANITIZE
// =========================
function clean($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// =========================
// RECAPTCHA VERIFY
// =========================
function verifyRecaptcha($token) {
    if (empty($token)) return false;

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!$data || !$data['success']) return false;

    if (isset($data['score'])) {
        return $data['score'] >= 0.5;
    }

    return true;
}

// =========================
// ONLY POST
// =========================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// =========================
// STRICT TOKEN CHECK
// =========================
if (empty($_POST['g-recaptcha-response'])) {
    echo json_encode(['success' => false, 'message' => 'Security verification failed']);
    exit;
}

// =========================
// GET DATA
// =========================
$name    = clean($_POST['name'] ?? '');
$email   = clean($_POST['email'] ?? '');
$phone   = clean($_POST['phone'] ?? '');
$subject = clean($_POST['subject'] ?? '');
$message = clean($_POST['message'] ?? '');
$token   = $_POST['g-recaptcha-response'];

$subject = substr($subject, 0, 100);

// =========================
// VALIDATION
// =========================
if (!$name || !$email || !$phone || !$message) {
    echo json_encode(['success' => false, 'message' => 'Please fill all fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

if (strlen($message) < 10) {
    echo json_encode(['success' => false, 'message' => 'Message too short']);
    exit;
}

// =========================
// RECAPTCHA CHECK
// =========================
if (!verifyRecaptcha($token)) {
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA failed']);
    exit;
}

// =========================
// YOUR ORIGINAL HTML EMAIL (UNCHANGED)
// =========================
$email_subject = "New Contact Form Submission: $subject";

$email_message = "
<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: Arial; color: #333; }
.container { max-width:600px;margin:auto;padding:20px; }
.header { background:#AB052D;color:white;padding:15px;border-radius:5px; }
.content { padding:20px;border:1px solid #eee;margin-top:10px; }
.field-label { font-weight:bold;color:#AB052D; }
</style>
</head>
<body>
<div class='container'>
<div class='header'><h2>New Contact Submission</h2></div>
<div class='content'>
<p><b>Name:</b> $name</p>
<p><b>Email:</b> $email</p>
<p><b>Phone:</b> $phone</p>
<p><b>Subject:</b> $subject</p>
<p><b>Message:</b><br>" . nl2br($message) . "</p>
<p><b>Date:</b> " . date('Y-m-d H:i:s') . "</p>
</div>
</div>
</body>
</html>
";

// =========================
// SEND EMAIL (SMTP PHPMailer)
// =========================
$mail = new PHPMailer(true);

try {
    // SMTP SETTINGS
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_user;
    $mail->Password = $smtp_pass;
    $mail->Port = $smtp_port;
    $mail->SMTPSecure = $smtp_secure;

    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);

    // ================= ADMIN EMAIL =================
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress(RECIPIENT_EMAIL);
    $mail->addReplyTo($email, $name);

    $mail->Subject = $email_subject;
    $mail->Body = $email_message;

    $mail->send();

    // ================= USER EMAIL =================
    $mail->clearAddresses();
    $mail->addAddress($email);

    $mail->Subject = "We Received Your Message";

    $mail->Body = "
        <h2>Thank you $name</h2>
        <p>We received your message.</p>
        <p>We will respond soon.</p>
    ";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully'
    ]);

} catch (Exception $e) {
    log_error('SMTP_ERROR', $mail->ErrorInfo);

    echo json_encode([
        'success' => false,
        'message' => 'Email sending failed'
    ]);
}
?>