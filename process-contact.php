<?php
require_once 'config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email) || !isValidEmail($email)) $errors[] = "Valid email address is required";
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        try {
            $pdo = getConnection();
            
            // Insert contact message
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $full_name = $first_name . ' ' . $last_name;
            $stmt->execute([$full_name, $email, $subject, $message]);
            
            // Send email notification to admin
            $email_subject = "New Contact Form Submission - " . $subject;
            $email_body = "
                <h2>New Contact Form Submission</h2>
                <p><strong>Name:</strong> $full_name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Phone:</strong> " . ($phone ?: 'Not provided') . "</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong></p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
                <p><strong>Newsletter Subscription:</strong> " . ($newsletter ? 'Yes' : 'No') . "</p>
                <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>
            ";
            
            sendEmail(ADMIN_EMAIL, $email_subject, $email_body);
            
            // Send confirmation email to user
            $user_subject = "Thank you for contacting AutoDeals";
            $user_body = "
                <h2>Thank you for your message!</h2>
                <p>Dear $first_name,</p>
                <p>We have received your message and will get back to you as soon as possible, typically within 24 hours.</p>
                <p><strong>Your message details:</strong></p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong> " . nl2br(htmlspecialchars($message)) . "</p>
                <p>If you have any urgent questions, please don't hesitate to call us at +1 (555) 123-4567.</p>
                <p>Best regards,<br>The AutoDeals Team</p>
            ";
            
            sendEmail($email, $user_subject, $user_body);
            
            $response['success'] = true;
            $response['message'] = 'Thank you for your message! We will get back to you soon.';
            
        } catch (Exception $e) {
            $response['message'] = 'Sorry, there was an error sending your message. Please try again.';
            error_log("Contact form error: " . $e->getMessage());
        }
    } else {
        $response['message'] = implode('<br>', $errors);
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Redirect back to contact page with message
$_SESSION['contact_message'] = $response['message'];
$_SESSION['contact_success'] = $response['success'];
header('Location: contact.html');
exit();
?>