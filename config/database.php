<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'car_sales');

// Create connection
function getConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'your-email@gmail.com');
define('FROM_NAME', 'AutoDeals');
define('ADMIN_EMAIL', 'admin@autodeals.com');

// Site configuration
define('SITE_URL', 'http://localhost/car-sales');
define('UPLOAD_PATH', 'uploads/cars/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Pagination
define('CARS_PER_PAGE', 12);
define('FEATURED_CARS_LIMIT', 6);

// Security
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function formatPrice($price) {
    return '$' . number_format($price);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function uploadImage($file, $directory = UPLOAD_PATH) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $filename = uniqid() . '.' . $extension;
    $filepath = $directory . $filename;
    
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

function sendEmail($to, $subject, $body, $isHTML = true) {
    // Simple mail function - in production, use PHPMailer or similar
    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    
    if ($isHTML) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $body, $headers);
}

function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: login.php');
        exit();
    }
}

function logActivity($action, $details = '') {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (action, details, ip_address, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$action, $details, $_SERVER['REMOTE_ADDR']]);
    } catch (Exception $e) {
        // Log error but don't break the application
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
?>