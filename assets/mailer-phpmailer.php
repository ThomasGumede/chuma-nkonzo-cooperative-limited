<?php
/**
 * Contact Form Handler with PHPMailer and Google reCAPTCHA
 * Chuma Nkozo Cooperative Limited
 * 
 * This version uses PHPMailer for better email delivery reliability
 * INSTALLATION: composer require phpmailer/phpmailer
 */

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// ============================================================================
// CONFIGURATION - UPDATE THESE SETTINGS
// ============================================================================

// reCAPTCHA Secret Key (Replace with your actual key)
define('RECAPTCHA_SECRET_KEY', '6LdvyIQsAAAAACxwNajfPgCjrAn5drImgeS4XQmV');

// Email configuration
define('RECIPIENT_EMAIL', 'info@chumankonzo.co.za');
define('MAIL_FROM', 'noreply@chumankonzo.co.za');
define('MAIL_FROM_NAME', 'Chuma Nkozo Cooperative');

/**
 * SMTP Configuration (for PHPMailer)
 * 
 * Option 1: Use Server's Default Mail (sendmail)
 * Set 'enabled' => false
 * 
 * Option 2: Use SMTP (Gmail, Mailtrap, etc.)
 * Set 'enabled' => true and configure details below
 */
$smtp_config = array(
    'enabled' => false,                      // Set to true to use SMTP
    'host' => 'smtp.gmail.com',              // SMTP server
    'port' => 587,                           // Port: 587 for TLS, 465 for SSL
    'username' => 'your-email@gmail.com',    // SMTP username
    'password' => 'your-app-password',       // SMTP password
    'encryption' => 'tls'                    // 'tls', 'ssl', or empty
);

// ============================================================================
// PHPMAILER INITIALIZATION
// ============================================================================

// Check if PHPMailer is installed
$use_phpmailer = false;
try {
    // Try autoloader (Composer install)
    if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        require __DIR__ . '/../../vendor/autoload.php';
        $use_phpmailer = true;
    }
    // Try manual installation in assets folder
    elseif (file_exists(__DIR__ . '/PHPMailer/Exception.php')) {
        require __DIR__ . '/PHPMailer/Exception.php';
        require __DIR__ . '/PHPMailer/PHPMailer.php';
        require __DIR__ . '/PHPMailer/SMTP.php';
        $use_phpmailer = true;
    }
} catch (Exception $e) {
    // PHPMailer not found - will fallback to mail()
    $use_phpmailer = false;
}

// Response array
$response = array(
    'success' => false,
    'message' => ''
);

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

try {
    // Get and validate form data
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
    $subject = isset($_POST['subject']) ? sanitize_input($_POST['subject']) : '';
    $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';
    $recaptcha_token = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';

    // ========================================================================
    // RECAPTCHA VERIFICATION
    // ========================================================================

    if (empty($recaptcha_token)) {
        $response['message'] = 'reCAPTCHA verification failed. Please try again.';
        echo json_encode($response);
        exit;
    }

    if (!verifyRecaptcha($recaptcha_token)) {
        $response['message'] = 'reCAPTCHA verification failed. Please try again.';
        echo json_encode($response);
        exit;
    }

    // ========================================================================
    // VALIDATE REQUIRED FIELDS
    // ========================================================================

    if (empty($name) || empty($email) || empty($phone) || empty($message)) {
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        echo json_encode($response);
        exit;
    }

    // Validate phone format
    if (!preg_match('/^[0-9\s\-\+\(\)]+$/', $phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 7) {
        $response['message'] = 'Please enter a valid phone number.';
        echo json_encode($response);
        exit;
    }

    // Validate message length
    if (strlen($message) < 10 || strlen($message) > 2000) {
        $response['message'] = 'Message must be between 10 and 2000 characters.';
        echo json_encode($response);
        exit;
    }

    // ========================================================================
    // PREPARE EMAIL CONTENT
    // ========================================================================

    $email_subject = 'New Contact Form Submission: ' . $subject;

    $email_message = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #AB052D; color: white; padding: 15px; border-radius: 5px; }
        .content { background: white; padding: 20px; margin-top: 10px; border-radius: 5px; border: 1px solid #eee; }
        .field { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .field-label { font-weight: bold; color: #AB052D; }
        .field-value { margin-top: 5px; color: #555; }
        .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Contact Form Submission</h2>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='field-label'>Name:</div>
                <div class='field-value'>" . htmlspecialchars($name) . "</div>
            </div>
            <div class='field'>
                <div class='field-label'>Email:</div>
                <div class='field-value'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></div>
            </div>
            <div class='field'>
                <div class='field-label'>Phone:</div>
                <div class='field-value'>" . htmlspecialchars($phone) . "</div>
            </div>
            <div class='field'>
                <div class='field-label'>Subject:</div>
                <div class='field-value'>" . htmlspecialchars($subject) . "</div>
            </div>
            <div class='field'>
                <div class='field-label'>Message:</div>
                <div class='field-value'>" . nl2br(htmlspecialchars($message)) . "</div>
            </div>
        </div>
        <div class='footer'>
            <p>This is an automated message from your website contact form.</p>
            <p>Timestamp: " . date('Y-m-d H:i:s') . "</p>
        </div>
    </div>
</body>
</html>
    ";

    // ========================================================================
    // SEND EMAILS
    // ========================================================================

    $admin_sent = false;
    $user_confirmation_sent = false;

    if ($use_phpmailer) {
        // Use PHPMailer for better reliability
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            if ($smtp_config['enabled']) {
                $mail->isSMTP();
                $mail->Host = $smtp_config['host'];
                $mail->Port = $smtp_config['port'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_config['username'];
                $mail->Password = $smtp_config['password'];
                if (!empty($smtp_config['encryption'])) {
                    $mail->SMTPSecure = $smtp_config['encryption'];
                }
                $mail->SMTPDebug = 0; // Set to 2 or 3 for debugging
            } else {
                // Use system mail
                $mail->isMail();
            }

            // Set encoding
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);

            // ================================================================
            // SEND ADMIN EMAIL
            // ================================================================
            $mail->clearAddresses();
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress(RECIPIENT_EMAIL);
            $mail->addReplyTo($email, $name);
            $mail->Subject = $email_subject;
            $mail->Body = $email_message;
            $mail->AltBody = strip_tags($email_message);

            $admin_sent = $mail->send();

            // ================================================================
            // SEND USER CONFIRMATION EMAIL
            // ================================================================
            $user_subject = 'We Received Your Message - Chuma Nkozo';
            $user_message = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #AB052D; color: white; padding: 15px; border-radius: 5px; }
        .content { background: white; padding: 20px; margin-top: 10px; border-radius: 5px; border: 1px solid #eee; }
        .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Thank you for contacting us!</h2>
        </div>
        <div class='content'>
            <p>Dear " . htmlspecialchars($name) . ",</p>
            <p>We have received your inquiry and will respond as soon as possible.</p>
            <p><strong>Your message details:</strong></p>
            <ul>
                <li><strong>Subject:</strong> " . htmlspecialchars($subject) . "</li>
                <li><strong>Submitted on:</strong> " . date('Y-m-d H:i:s') . "</li>
            </ul>
            <p>If you have any questions, contact us at:</p>
            <ul>
                <li><strong>Phone:</strong> +27 78 819 3379</li>
                <li><strong>Email:</strong> info@chumankonzo.co.za</li>
            </ul>
            <p>Best regards,<br>Chuma Nkozo Team</p>
        </div>
        <div class='footer'>
            <p>&copy; 2026 Chuma Nkozo Cooperative Limited. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
            ";

            $mail->clearAddresses();
            $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
            $mail->addAddress($email);
            $mail->Subject = $user_subject;
            $mail->Body = $user_message;
            $mail->AltBody = strip_tags($user_message);

            $user_confirmation_sent = $mail->send();

        } catch (Exception $e) {
            // Log the error
            error_log('PHPMailer Error: ' . $mail->ErrorInfo);
            
            // Fallback to mail() function
            $admin_sent = sendEmailFallback(RECIPIENT_EMAIL, $email_subject, $email_message, $email, $name);
            $user_confirmation_sent = sendEmailFallback($email, $user_subject, $user_message, MAIL_FROM, MAIL_FROM_NAME);
        }
    } else {
        // Use PHP mail() function
        $admin_sent = sendEmailFallback(RECIPIENT_EMAIL, $email_subject, $email_message, $email, $name);
        
        $user_subject = 'We Received Your Message - Chuma Nkozo';
        $user_message = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
    </style>
</head>
<body>
    <h2>Thank you for contacting us!</h2>
    <p>Dear " . htmlspecialchars($name) . ",</p>
    <p>We have received your inquiry and will respond as soon as possible.</p>
    <p><strong>Your message details:</strong></p>
    <ul>
        <li><strong>Subject:</strong> " . htmlspecialchars($subject) . "</li>
        <li><strong>Submitted on:</strong> " . date('Y-m-d H:i:s') . "</li>
    </ul>
    <p>Best regards,<br>Chuma Nkozo Team</p>
</body>
</html>
        ";
        
        $user_confirmation_sent = sendEmailFallback($email, $user_subject, $user_message, MAIL_FROM, MAIL_FROM_NAME);
    }

    // ========================================================================
    // RESPONSE
    // ========================================================================

    if ($admin_sent) {
        $response['success'] = true;
        $response['message'] = 'Thank you! Your message has been sent successfully. We will get back to you soon.';
    } else {
        $response['success'] = false;
        $response['message'] = 'An error occurred while sending your message. Please try again later.';
        error_log('Mail sending failed for contact from: ' . $email);
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    error_log('Contact Form Error: ' . $e->getMessage());
}

echo json_encode($response);
exit;

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Verify reCAPTCHA token
 */
function verifyRecaptcha($token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    if ($result === false) {
        return false;
    }
    
    $json = json_decode($result);
    
    // For reCAPTCHA v2 checkbox
    if (isset($json->success)) {
        return $json->success === true;
    }
    
    // For reCAPTCHA v3
    if (isset($json->success) && isset($json->score)) {
        return $json->success === true && $json->score > 0.5;
    }
    
    return false;
}

/**
 * Fallback mail function using PHP mail()
 */
function sendEmailFallback($to, $subject, $body, $reply_email, $reply_name) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">" . "\r\n";
    $headers .= "Reply-To: " . htmlspecialchars($reply_email) . "\r\n";
    
    return @mail($to, $subject, $body, $headers);
}

/**
 * Sanitize user input
 */
function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

?>
