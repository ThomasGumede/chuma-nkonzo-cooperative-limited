<?php
/**
 * Appointment Form Handler with Google reCAPTCHA and PHPMailer
 * Chuma Nkozo Cooperative Limited
 * 
 * This version uses PHPMailer for better email delivery reliability
 * INSTALLATION: composer require phpmailer/phpmailer
 */

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// CORS headers (optional, adjust as needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// ============================================================================
// CONFIGURATION - UPDATE THESE SETTINGS
// ============================================================================

// Your Google reCAPTCHA Secret Key
$recaptcha_secret = '6LdvyIQsAAAAACxwNajfPgCjrAn5drImgeS4XQmV'; // Replace with your actual secret key

// Email configuration
$to_email = 'info@chumankonzo.co.za';           // Admin email (receives appointments)
$from_email = 'noreply@chumankonzo.co.za';      // From email address
$from_name = 'Chuma Nkozo Cooperative';         // From name

/**
 * SMTP Configuration (for PHPMailer)
 * 
 * Option 1: Use Server's Default Mail (sendmail)
 * Set 'enabled' => false
 * 
 * Option 2: Use SMTP (Gmail, Mailtrap, etc.)
 * Set 'enabled' => true and configure details below
 */
$smtp_config = [
    'enabled' => false,                         // Set to true to use SMTP
    'host' => 'smtp.chumankonzo.co.za',                 // SMTP server
    'port' => 587,                              // Port: 587 for TLS, 465 for SSL
    'username' => 'your-email@chumankonzo.co.za',       // SMTP username
    'password' => 'your-app-password',          // SMTP password
    'encryption' => 'tls'                       // 'tls', 'ssl', or empty
];

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
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

try {
    // Get form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';
    $time = isset($_POST['time']) ? trim($_POST['time']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $recaptcha_token = isset($_POST['g-recaptcha-response']) ? trim($_POST['g-recaptcha-response']) : '';

    // ========================================================================
    // RECAPTCHA VERIFICATION
    // ========================================================================

    if (empty($recaptcha_token)) {
        $response['errors']['recaptcha'] = 'reCAPTCHA verification failed. Please try again.';
        $response['message'] = 'reCAPTCHA verification required';
        echo json_encode($response);
        exit;
    }

    // Verify reCAPTCHA token with Google
    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded\r\n',
            'content' => http_build_query([
                'secret' => $recaptcha_secret,
                'response' => $recaptcha_token
            ])
        ]
    ];

    $context = stream_context_create($recaptcha_options);
    $recaptcha_response = @file_get_contents($recaptcha_url, false, $context);
    
    if ($recaptcha_response === false) {
        $response['errors']['recaptcha'] = 'Unable to verify reCAPTCHA. Please try again.';
        $response['message'] = 'reCAPTCHA verification failed';
        echo json_encode($response);
        exit;
    }

    $recaptcha_result = json_decode($recaptcha_response, true);
    
    // Check if reCAPTCHA verification was successful
    if (!isset($recaptcha_result['success']) || $recaptcha_result['success'] !== true) {
        $response['errors']['recaptcha'] = 'reCAPTCHA verification failed. Please try again.';
        $response['message'] = 'Security verification failed';
        echo json_encode($response);
        exit;
    }

    // Check score (for reCAPTCHA v3)
    if (isset($recaptcha_result['score']) && $recaptcha_result['score'] < 0.5) {
        $response['errors']['recaptcha'] = 'Suspicious activity detected. Please try again.';
        $response['message'] = 'Security verification failed';
        echo json_encode($response);
        exit;
    }

    // ========================================================================
    // FORM VALIDATION
    // ========================================================================

    if (empty($name)) {
        $response['errors']['name'] = 'Full name is required';
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $response['errors']['name'] = 'Name must be between 2 and 100 characters';
    }

    if (empty($email)) {
        $response['errors']['email'] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors']['email'] = 'Please enter a valid email address';
    }

    if (empty($phone)) {
        $response['errors']['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9\s\-\+\(\)]+$/', $phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 7) {
        $response['errors']['phone'] = 'Please enter a valid phone number';
    }

    if (empty($subject)) {
        $response['errors']['subject'] = 'Please select a service';
    }

    if (empty($date)) {
        $response['errors']['date'] = 'Please select a date';
    } else {
        // Validate date format (assumes format from flatpickr)
        $date_obj = DateTime::createFromFormat('Y-m-d', $date);
        if ($date_obj === false) {
            $response['errors']['date'] = 'Invalid date format';
        } else {
            // Check if date is in the future
            if ($date_obj < new DateTime('today')) {
                $response['errors']['date'] = 'Please select a future date';
            }
        }
    }

    if (empty($time)) {
        $response['errors']['time'] = 'Please select a time';
    } else {
        // Validate time format (assumes HH:MM format)
        if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            $response['errors']['time'] = 'Invalid time format';
        }
    }

    if (empty($message)) {
        $response['errors']['message'] = 'Please enter a message';
    } elseif (strlen($message) < 10) {
        $response['errors']['message'] = 'Message must be at least 10 characters';
    } elseif (strlen($message) > 1000) {
        $response['errors']['message'] = 'Message must not exceed 1000 characters';
    }

    // If validation failed, return errors
    if (!empty($response['errors'])) {
        $response['message'] = 'Please correct the errors below';
        echo json_encode($response);
        exit;
    }

    // ========================================================================
    // MAP SUBJECT VALUE TO DISPLAY NAME
    // ========================================================================

    $subject_map = [
        'one' => 'Business Strategy',
        'two' => 'Market Analysis',
        'three' => 'Financial Planning',
        'four' => 'Risk Management',
        'five' => 'Digital Transformation',
        'Your Business' => 'General Inquiry'
    ];
    $subject_display = $subject_map[$subject] ?? $subject;

    // ========================================================================
    // PREPARE EMAIL CONTENT
    // ========================================================================

    $appointment_date = DateTime::createFromFormat('Y-m-d', $date)->format('F j, Y');
    
    $admin_email_body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #AB052D; color: white; padding: 20px; text-align: center; border-radius: 5px; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; border-radius: 5px; }
        .field { margin-bottom: 15px; }
        .field-label { font-weight: bold; color: #AB052D; }
        .field-value { margin-top: 5px; padding: 10px; background-color: #fff; border-left: 3px solid #AB052D; }
        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h2>New Appointment Request</h2>
        </div>
        
        <div class=\"content\">
            <div class=\"field\">
                <div class=\"field-label\">Client Name:</div>
                <div class=\"field-value\">" . htmlspecialchars($name) . "</div>
            </div>
            
            <div class=\"field\">
                <div class=\"field-label\">Email:</div>
                <div class=\"field-value\"><a href=\"mailto:" . htmlspecialchars($email) . "\">" . htmlspecialchars($email) . "</a></div>
            </div>
            
            <div class=\"field\">
                <div class=\"field-label\">Phone:</div>
                <div class=\"field-value\"><a href=\"tel:" . htmlspecialchars($phone) . "\">" . htmlspecialchars($phone) . "</a></div>
            </div>
            
            <div class=\"field\">
                <div class=\"field-label\">Service Requested:</div>
                <div class=\"field-value\">" . htmlspecialchars($subject_display) . "</div>
            </div>
            
            <div class=\"field\">
                <div class=\"field-label\">Preferred Date & Time:</div>
                <div class=\"field-value\">" . htmlspecialchars($appointment_date) . " at " . htmlspecialchars($time) . "</div>
            </div>
            
            <div class=\"field\">
                <div class=\"field-label\">Message:</div>
                <div class=\"field-value\">" . nl2br(htmlspecialchars($message)) . "</div>
            </div>
        </div>
        
        <div class=\"footer\">
            <p>This is an automated message from your website appointment system.</p>
            <p>Please contact the client to confirm the appointment.</p>
        </div>
    </div>
</body>
</html>
    ";

    $client_email_body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #AB052D; color: white; padding: 20px; text-align: center; border-radius: 5px; }
        .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; border-radius: 5px; }
        .footer { text-align: center; margin-top: 20px; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h2>Appointment Confirmation</h2>
        </div>
        
        <div class=\"content\">
            <p>Dear " . htmlspecialchars($name) . ",</p>
            
            <p>Thank you for booking an appointment with Chuma Nkozo Cooperative Limited. We have received your request and will contact you shortly to confirm your appointment.</p>
            
            <p><strong>Appointment Details:</strong></p>
            <ul>
                <li><strong>Service:</strong> " . htmlspecialchars($subject_display) . "</li>
                <li><strong>Preferred Date:</strong> " . htmlspecialchars($appointment_date) . "</li>
                <li><strong>Preferred Time:</strong> " . htmlspecialchars($time) . "</li>
            </ul>
            
            <p>If you have any questions, please contact us at:</p>
            <ul>
                <li><strong>Phone:</strong> +27 78 819 3379</li>
                <li><strong>Email:</strong> info@chumankonzo.co.za</li>
            </ul>
        </div>
        
        <div class=\"footer\">
            <p>This is an automated confirmation message.</p>
            <p>&copy; 2026 Chuma Nkozo Cooperative Limited. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
    ";

    // ========================================================================
    // SEND EMAILS
    // ========================================================================

    $admin_sent = false;
    $client_sent = false;

    if ($use_phpmailer) {
        // Use PHPMailer for better reliability
        $mail = new PHPMailer(true);

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
            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($to_email);
            $mail->addReplyTo($email, $name);
            $mail->Subject = 'New Appointment Request from ' . htmlspecialchars($name);
            $mail->Body = $admin_email_body;
            $mail->AltBody = strip_tags($admin_email_body);

            $admin_sent = $mail->send();

            // ================================================================
            // SEND CLIENT CONFIRMATION EMAIL
            // ================================================================
            $mail->clearAddresses();
            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($email);
            $mail->Subject = 'Appointment Confirmation - Chuma Nkozo';
            $mail->Body = $client_email_body;
            $mail->AltBody = strip_tags($client_email_body);

            $client_sent = $mail->send();

        } catch (Exception $e) {
            // Log the error
            error_log('PHPMailer Error: ' . $mail->ErrorInfo);
            
            // Fallback to mail() function
            $admin_sent = sendEmailFallback($to_email, 'New Appointment Request from ' . htmlspecialchars($name), $admin_email_body);
            $client_sent = sendEmailFallback($email, 'Appointment Confirmation - Chuma Nkozo', $client_email_body);
        }
    } else {
        // Use PHP mail() function
        $admin_sent = sendEmailFallback($to_email, 'New Appointment Request from ' . htmlspecialchars($name), $admin_email_body);
        $client_sent = sendEmailFallback($email, 'Appointment Confirmation - Chuma Nkozo', $client_email_body);
    }

    // ========================================================================
    // RESPONSE
    // ========================================================================

    $response['success'] = true;
    $response['message'] = 'Thank you! Your appointment request has been received. We will contact you shortly to confirm.';

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    error_log('Appointment Form Error: ' . $e->getMessage());
    echo json_encode($response);
    exit;
}

// ============================================================================
// FALLBACK MAIL FUNCTION
// ============================================================================

function sendEmailFallback($to, $subject, $body) {
    global $from_email, $from_name;
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $from_name . " <" . $from_email . ">\r\n";
    
    return @mail($to, $subject, $body, $headers);
}

?>
