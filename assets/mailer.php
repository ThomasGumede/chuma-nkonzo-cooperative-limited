<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// reCAPTCHA Secret Key (Replace with your actual key)
define('RECAPTCHA_SECRET_KEY', '6LdvyIQsAAAAACxwNajfPgCjrAn5drImgeS4XQmV');

// Email configuration
define('RECIPIENT_EMAIL', 'info@chumankonzo.co.za');
define('MAIL_FROM', 'noreply@chumankonzo.co.za');

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

// Validate reCAPTCHA
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
    return isset($json->success) && $json->success && $json->score > 0.5;
}

// Get and validate form data
$name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
$subject = isset($_POST['subject']) ? sanitize_input($_POST['subject']) : '';
$message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';
$recaptcha_token = isset($_POST['recaptcha_token']) ? $_POST['recaptcha_token'] : '';

// Validate required fields
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

// Validate reCAPTCHA token
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

// Prepare email content
$email_subject = 'New Contact Form Submission: ' . $subject;

$email_message = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
        .header { background: #007bff; color: white; padding: 15px; border-radius: 5px; }
        .content { background: white; padding: 20px; margin-top: 10px; border-radius: 5px; }
        .field { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .field-label { font-weight: bold; color: #007bff; }
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

// Set headers for HTML email
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
$headers .= "From: " . MAIL_FROM . "\r\n";
$headers .= "Reply-To: " . htmlspecialchars($email) . "\r\n";

// Send email
if (mail(RECIPIENT_EMAIL, $email_subject, $email_message, $headers)) {
    $response['success'] = true;
    $response['message'] = 'Thank you! Your message has been sent successfully. We will get back to you soon.';
    
    // Optional: Send confirmation email to user
    $user_subject = 'We Received Your Message - Chuma Nkonzo';
    $user_message = "
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
        <p>Best regards,<br>Chuma Nkonzo Team</p>
    </body>
    </html>
    ";
    
    $user_headers = "MIME-Version: 1.0" . "\r\n";
    $user_headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $user_headers .= "From: " . MAIL_FROM . "\r\n";
    
    mail($email, $user_subject, $user_message, $user_headers);
} else {
    $response['success'] = false;
    $response['message'] = 'An error occurred while sending your message. Please try again later.';
    error_log('Mail sending failed for: ' . $email);
}

echo json_encode($response);
exit;

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
