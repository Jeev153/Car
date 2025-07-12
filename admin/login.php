<?php
require_once '../config/database.php';

// Check if already logged in
if (isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$login_attempts = 0;
$lockout_time = 0;

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            $pdo = getConnection();
            
            // Check for existing lockout
            $stmt = $pdo->prepare("SELECT login_attempts, locked_until FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user_data = $stmt->fetch();
            
            if ($user_data && $user_data['locked_until'] && strtotime($user_data['locked_until']) > time()) {
                $lockout_time = strtotime($user_data['locked_until']) - time();
                $error_message = 'Account is locked due to multiple failed login attempts. Please try again in ' . ceil($lockout_time / 60) . ' minutes.';
            } else {
                // Verify credentials
                $stmt = $pdo->prepare("SELECT id, username, password, full_name, email, login_attempts FROM admin_users WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password'])) {
                    // Successful login
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['login_time'] = time();
                    
                    // Reset login attempts
                    $stmt = $pdo->prepare("UPDATE admin_users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
                    $stmt->execute([$admin['id']]);
                    
                    // Log successful login
                    logActivity('Admin Login', "User: {$admin['username']}");
                    
                    // Redirect to dashboard
                    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';
                    header('Location: ' . $redirect);
                    exit();
                } else {
                    // Failed login
                    if ($admin) {
                        $login_attempts = $admin['login_attempts'] + 1;
                        
                        if ($login_attempts >= MAX_LOGIN_ATTEMPTS) {
                            // Lock account
                            $locked_until = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
                            $stmt = $pdo->prepare("UPDATE admin_users SET login_attempts = ?, locked_until = ? WHERE id = ?");
                            $stmt->execute([$login_attempts, $locked_until, $admin['id']]);
                            
                            $error_message = 'Account locked due to multiple failed login attempts. Please try again in 15 minutes.';
                            logActivity('Admin Account Locked', "User: {$admin['username']}");
                        } else {
                            // Increment login attempts
                            $stmt = $pdo->prepare("UPDATE admin_users SET login_attempts = ? WHERE id = ?");
                            $stmt->execute([$login_attempts, $admin['id']]);
                            
                            $remaining_attempts = MAX_LOGIN_ATTEMPTS - $login_attempts;
                            $error_message = "Invalid credentials. $remaining_attempts attempts remaining.";
                        }
                    } else {
                        $error_message = 'Invalid username or password.';
                    }
                    
                    logActivity('Failed Admin Login', "Username: $username");
                }
            }
        } catch (Exception $e) {
            $error_message = 'Login system temporarily unavailable. Please try again later.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AutoDeals</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin-login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-car"></i>
                    <span>AutoDeals</span>
                </div>
                <h1>Admin Login</h1>
                <p>Access the administrative dashboard</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               placeholder="Enter your username">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required 
                               placeholder="Enter your password">
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-large" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            
            <div class="login-footer">
                <p><a href="../index.html"><i class="fas fa-arrow-left"></i> Back to Website</a></p>
            </div>
        </div>
        
        <div class="login-info">
            <div class="info-content">
                <h2>Welcome to AutoDeals Admin</h2>
                <p>Manage your car inventory, customer enquiries, and website content from this secure administrative dashboard.</p>
                
                <div class="features-list">
                    <div class="feature">
                        <i class="fas fa-car"></i>
                        <span>Manage Car Listings</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-users"></i>
                        <span>Handle Customer Enquiries</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-chart-bar"></i>
                        <span>View Analytics & Reports</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-cog"></i>
                        <span>System Configuration</span>
                    </div>
                </div>
                
                <div class="security-notice">
                    <i class="fas fa-shield-alt"></i>
                    <p>This is a secure area. All activities are logged and monitored.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Demo Credentials Notice (Remove in production) -->
    <div class="demo-notice">
        <div class="demo-content">
            <h3><i class="fas fa-info-circle"></i> Demo Credentials</h3>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> admin123</p>
            <small>Remove this notice in production</small>
        </div>
    </div>

    <script>
        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                showError('Please enter both username and password.');
                return;
            }
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        });
        
        // Show error message
        function showError(message) {
            const existingError = document.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
            
            const form = document.querySelector('.login-form');
            form.parentNode.insertBefore(errorDiv, form);
        }
        
        // Auto-hide demo notice after 10 seconds
        setTimeout(function() {
            const demoNotice = document.querySelector('.demo-notice');
            if (demoNotice) {
                demoNotice.style.opacity = '0';
                setTimeout(() => demoNotice.remove(), 500);
            }
        }, 10000);
        
        // Focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>