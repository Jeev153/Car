<?php
require_once 'config/database.php';

// Get car ID
$car_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$car_id) {
    header('Location: cars.php');
    exit();
}

// Handle enquiry form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_enquiry'])) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $message = sanitizeInput($_POST['message']);
    
    $errors = [];
    
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email) || !isValidEmail($email)) $errors[] = "Valid email is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO enquiries (car_id, name, email, phone, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$car_id, $name, $email, $phone, $message]);
            
            // Send email notification
            $car_stmt = $pdo->prepare("SELECT make, model, year FROM cars WHERE id = ?");
            $car_stmt->execute([$car_id]);
            $car_info = $car_stmt->fetch();
            
            $subject = "New Car Enquiry - {$car_info['make']} {$car_info['model']} {$car_info['year']}";
            $email_body = "
                <h2>New Car Enquiry</h2>
                <p><strong>Car:</strong> {$car_info['make']} {$car_info['model']} {$car_info['year']}</p>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Phone:</strong> $phone</p>
                <p><strong>Message:</strong></p>
                <p>$message</p>
                <p><a href='" . SITE_URL . "/car-details.php?id=$car_id'>View Car Details</a></p>
            ";
            
            sendEmail(ADMIN_EMAIL, $subject, $email_body);
            
            $success_message = "Your enquiry has been submitted successfully! We'll contact you soon.";
            
        } catch (Exception $e) {
            $errors[] = "Error submitting enquiry. Please try again.";
        }
    }
}

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND is_sold = 0");
    $stmt->execute([$car_id]);
    $car = $stmt->fetch();
    
    if (!$car) {
        header('Location: 404.html');
        exit();
    }
    
    // Get related cars
    $related_stmt = $pdo->prepare("SELECT * FROM cars WHERE category = ? AND id != ? AND is_sold = 0 LIMIT 4");
    $related_stmt->execute([$car['category'], $car_id]);
    $related_cars = $related_stmt->fetchAll();
    
} catch (Exception $e) {
    header('Location: 404.html');
    exit();
}

// Parse additional images
$additional_images = [];
if ($car['additional_images']) {
    $additional_images = explode(',', $car['additional_images']);
}

// Parse features
$features = [];
if ($car['features']) {
    $features = explode(',', $car['features']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($car['make'] . ' ' . $car['model'] . ' ' . $car['year']); ?> - AutoDeals</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($car['description'], 0, 160)); ?>">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/car-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-car"></i>
                <span>AutoDeals</span>
            </div>
            <ul class="nav-menu">
                <li><a href="index.html" class="nav-link">Home</a></li>
                <li><a href="cars.php" class="nav-link">All Cars</a></li>
                <li><a href="about.html" class="nav-link">About</a></li>
                <li><a href="contact.html" class="nav-link">Contact</a></li>
                <li><a href="admin/login.php" class="nav-link admin-link">Admin</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <a href="index.html">Home</a>
            <i class="fas fa-chevron-right"></i>
            <a href="cars.php">Cars</a>
            <i class="fas fa-chevron-right"></i>
            <a href="cars.php?category=<?php echo $car['category']; ?>"><?php echo ucfirst($car['category']); ?>s</a>
            <i class="fas fa-chevron-right"></i>
            <span><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></span>
        </div>
    </div>

    <!-- Car Details Section -->
    <section class="car-details-section">
        <div class="container">
            <div class="car-details-grid">
                <!-- Car Images -->
                <div class="car-images">
                    <div class="main-image">
                        <img id="main-car-image" src="<?php echo $car['image'] ? UPLOAD_PATH . $car['image'] : '/placeholder.svg?height=400&width=600'; ?>" 
                             alt="<?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>">
                        <?php if ($car['is_featured']): ?>
                            <div class="featured-badge">
                                <i class="fas fa-star"></i>
                                Featured
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($additional_images) || $car['image']): ?>
                        <div class="image-thumbnails">
                            <?php if ($car['image']): ?>
                                <img src="<?php echo UPLOAD_PATH . $car['image']; ?>" 
                                     alt="Main image" 
                                     class="thumbnail active"
                                     onclick="changeMainImage(this.src)">
                            <?php endif; ?>
                            
                            <?php foreach ($additional_images as $image): ?>
                                <?php if (trim($image)): ?>
                                    <img src="<?php echo UPLOAD_PATH . trim($image); ?>" 
                                         alt="Additional image" 
                                         class="thumbnail"
                                         onclick="changeMainImage(this.src)">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Car Information -->
                <div class="car-info">
                    <div class="car-header">
                        <h1><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></h1>
                        <div class="car-price"><?php echo formatPrice($car['price']); ?></div>
                    </div>
                    
                    <div class="car-specs">
                        <div class="spec-item">
                            <i class="fas fa-calendar"></i>
                            <span class="spec-label">Year</span>
                            <span class="spec-value"><?php echo $car['year']; ?></span>
                        </div>
                        <div class="spec-item">
                            <i class="fas fa-gas-pump"></i>
                            <span class="spec-label">Fuel Type</span>
                            <span class="spec-value"><?php echo ucfirst($car['fuel_type']); ?></span>
                        </div>
                        <div class="spec-item">
                            <i class="fas fa-cogs"></i>
                            <span class="spec-label">Transmission</span>
                            <span class="spec-value"><?php echo ucfirst($car['transmission']); ?></span>
                        </div>
                        <div class="spec-item">
                            <i class="fas fa-user"></i>
                            <span class="spec-label">Ownership</span>
                            <span class="spec-value"><?php echo ucfirst($car['ownership']); ?> Owner</span>
                        </div>
                        <div class="spec-item">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="spec-label">Mileage</span>
                            <span class="spec-value"><?php echo number_format($car['mileage']); ?> km</span>
                        </div>
                        <div class="spec-item">
                            <i class="fas fa-palette"></i>
                            <span class="spec-label">Color</span>
                            <span class="spec-value"><?php echo ucfirst($car['color']); ?></span>
                        </div>
                        <div class="spec-item">
                            <i class="fas fa-tag"></i>
                            <span class="spec-label">Category</span>
                            <span class="spec-value"><?php echo ucfirst($car['category']); ?></span>
                        </div>
                    </div>
                    
                    <div class="car-actions">
                        <a href="#enquiry" class="btn btn-primary btn-large">
                            <i class="fas fa-envelope"></i>
                            Enquire Now
                        </a>
                        <a href="tel:+15551234567" class="btn btn-secondary btn-large">
                            <i class="fas fa-phone"></i>
                            Call Now
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Car Description and Features -->
            <div class="car-details-content">
                <div class="content-grid">
                    <div class="description-section">
                        <h2>Description</h2>
                        <p><?php echo nl2br(htmlspecialchars($car['description'])); ?></p>
                    </div>
                    
                    <?php if (!empty($features)): ?>
                        <div class="features-section">
                            <h2>Features</h2>
                            <ul class="features-list">
                                <?php foreach ($features as $feature): ?>
                                    <li>
                                        <i class="fas fa-check"></i>
                                        <?php echo htmlspecialchars(trim($feature)); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Enquiry Form -->
    <section id="enquiry" class="enquiry-section">
        <div class="container">
            <div class="enquiry-content">
                <div class="enquiry-info">
                    <h2>Interested in this car?</h2>
                    <p>Fill out the form below and we'll get back to you as soon as possible with more information about this vehicle.</p>
                    
                    <div class="contact-methods">
                        <div class="contact-method">
                            <i class="fas fa-phone"></i>
                            <div>
                                <h4>Call Us</h4>
                                <p>+1 (555) 123-4567</p>
                            </div>
                        </div>
                        <div class="contact-method">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h4>Email Us</h4>
                                <p>info@autodeals.com</p>
                            </div>
                        </div>
                        <div class="contact-method">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h4>Visit Us</h4>
                                <p>123 Car Street, Auto City</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="enquiry-form">
                    <?php if (isset($success_message)): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="form">
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="5" required 
                                      placeholder="I'm interested in this <?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>. Please provide more details."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" name="submit_enquiry" class="btn btn-primary btn-large">
                            <i class="fas fa-paper-plane"></i>
                            Send Enquiry
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Cars -->
    <?php if (!empty($related_cars)): ?>
        <section class="related-cars">
            <div class="container">
                <div class="section-header">
                    <h2>Similar Cars</h2>
                    <p>You might also be interested in these vehicles</p>
                </div>
                
                <div class="cars-grid">
                    <?php foreach ($related_cars as $related_car): ?>
                        <div class="car-card">
                            <div class="car-image">
                                <img src="<?php echo $related_car['image'] ? UPLOAD_PATH . $related_car['image'] : '/placeholder.svg?height=200&width=300'; ?>" 
                                     alt="<?php echo htmlspecialchars($related_car['make'] . ' ' . $related_car['model']); ?>">
                                <div class="car-price"><?php echo formatPrice($related_car['price']); ?></div>
                            </div>
                            <div class="car-info">
                                <h3 class="car-title"><?php echo htmlspecialchars($related_car['make'] . ' ' . $related_car['model']); ?></h3>
                                <div class="car-details">
                                    <div class="car-detail">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo $related_car['year']; ?></span>
                                    </div>
                                    <div class="car-detail">
                                        <i class="fas fa-gas-pump"></i>
                                        <span><?php echo ucfirst($related_car['fuel_type']); ?></span>
                                    </div>
                                    <div class="car-detail">
                                        <i class="fas fa-cogs"></i>
                                        <span><?php echo ucfirst($related_car['transmission']); ?></span>
                                    </div>
                                    <div class="car-detail">
                                        <i class="fas fa-tachometer-alt"></i>
                                        <span><?php echo number_format($related_car['mileage']); ?> km</span>
                                    </div>
                                </div>
                                <div class="car-actions">
                                    <a href="car-details.php?id=<?php echo $related_car['id']; ?>" class="btn btn-primary btn-small">View Details</a>
                                    <a href="car-details.php?id=<?php echo $related_car['id']; ?>#enquiry" class="btn btn-outline btn-small">Enquire</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-car"></i>
                        <span>AutoDeals</span>
                    </div>
                    <p>Your trusted partner in finding the perfect second-hand car.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="cars.php">All Cars</a></li>
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Categories</h3>
                    <ul>
                        <li><a href="cars.php?category=suv">SUVs</a></li>
                        <li><a href="cars.php?category=sedan">Sedans</a></li>
                        <li><a href="cars.php?category=hatchback">Hatchbacks</a></li>
                        <li><a href="cars.php?category=convertible">Convertibles</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <div class="contact-info">
                        <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                        <p><i class="fas fa-envelope"></i> info@autodeals.com</p>
                        <p><i class="fas fa-map-marker-alt"></i> 123 Car Street, Auto City</p>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 AutoDeals. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
    <script src="js/car-details.js"></script>
</body>
</html>