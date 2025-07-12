<?php
require_once 'config/database.php';

// Get filter parameters
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;
$year_from = isset($_GET['year_from']) ? (int)$_GET['year_from'] : 0;
$year_to = isset($_GET['year_to']) ? (int)$_GET['year_to'] : 0;
$fuel_type = isset($_GET['fuel_type']) ? sanitizeInput($_GET['fuel_type']) : '';
$transmission = isset($_GET['transmission']) ? sanitizeInput($_GET['transmission']) : '';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'created_at DESC';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * CARS_PER_PAGE;

// Build query
$where_conditions = ["is_sold = 0"];
$params = [];

if ($category) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
}

if ($search) {
    $where_conditions[] = "(make LIKE ? OR model LIKE ? OR description LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($min_price > 0) {
    $where_conditions[] = "price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $where_conditions[] = "price <= ?";
    $params[] = $max_price;
}

if ($year_from > 0) {
    $where_conditions[] = "year >= ?";
    $params[] = $year_from;
}

if ($year_to > 0) {
    $where_conditions[] = "year <= ?";
    $params[] = $year_to;
}

if ($fuel_type) {
    $where_conditions[] = "fuel_type = ?";
    $params[] = $fuel_type;
}

if ($transmission) {
    $where_conditions[] = "transmission = ?";
    $params[] = $transmission;
}

$where_clause = implode(' AND ', $where_conditions);

try {
    $pdo = getConnection();
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) FROM cars WHERE $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_cars = $count_stmt->fetchColumn();
    $total_pages = ceil($total_cars / CARS_PER_PAGE);
    
    // Get cars
    $sql = "SELECT * FROM cars WHERE $where_clause ORDER BY $sort LIMIT ? OFFSET ?";
    $params[] = CARS_PER_PAGE;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cars = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Error loading cars: " . $e->getMessage();
    $cars = [];
    $total_cars = 0;
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cars - AutoDeals</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cars.css">
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
                <li><a href="cars.php" class="nav-link active">All Cars</a></li>
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

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Find Your Perfect Car</h1>
            <p>Browse through our extensive collection of quality second-hand cars</p>
        </div>
    </section>

    <!-- Search and Filters -->
    <section class="filters-section">
        <div class="container">
            <form method="GET" class="filters-form">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search by make, model..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                
                <div class="filters-grid">
                    <select name="category">
                        <option value="">All Categories</option>
                        <option value="suv" <?php echo $category === 'suv' ? 'selected' : ''; ?>>SUVs</option>
                        <option value="sedan" <?php echo $category === 'sedan' ? 'selected' : ''; ?>>Sedans</option>
                        <option value="hatchback" <?php echo $category === 'hatchback' ? 'selected' : ''; ?>>Hatchbacks</option>
                        <option value="convertible" <?php echo $category === 'convertible' ? 'selected' : ''; ?>>Convertibles</option>
                        <option value="coupe" <?php echo $category === 'coupe' ? 'selected' : ''; ?>>Coupes</option>
                        <option value="wagon" <?php echo $category === 'wagon' ? 'selected' : ''; ?>>Wagons</option>
                    </select>
                    
                    <select name="fuel_type">
                        <option value="">Fuel Type</option>
                        <option value="petrol" <?php echo $fuel_type === 'petrol' ? 'selected' : ''; ?>>Petrol</option>
                        <option value="diesel" <?php echo $fuel_type === 'diesel' ? 'selected' : ''; ?>>Diesel</option>
                        <option value="electric" <?php echo $fuel_type === 'electric' ? 'selected' : ''; ?>>Electric</option>
                        <option value="hybrid" <?php echo ($fuel == 'hybrid') ? 'selected' : ''; ?>>Electric</option>
                        <option value="hybrid" <?php echo $fuel_type === 'hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                    </select>
                    
                    <select name="transmission">
                        <option value="">Transmission</option>
                        <option value="manual" <?php echo $transmission === 'manual' ? 'selected' : ''; ?>>Manual</option>
                        <option value="automatic" <?php echo $transmission === 'automatic' ? 'selected' : ''; ?>>Automatic</option>
                    </select>
                    
                    <input type="number" name="min_price" placeholder="Min Price" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                    <input type="number" name="max_price" placeholder="Max Price" value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
                    
                    <input type="number" name="year_from" placeholder="Year From" value="<?php echo $year_from > 0 ? $year_from : ''; ?>">
                    <input type="number" name="year_to" placeholder="Year To" value="<?php echo $year_to > 0 ? $year_to : ''; ?>">
                    
                    <select name="sort">
                        <option value="created_at DESC" <?php echo $sort === 'created_at DESC' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price ASC" <?php echo $sort === 'price ASC' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price DESC" <?php echo $sort === 'price DESC' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="year DESC" <?php echo $sort === 'year DESC' ? 'selected' : ''; ?>>Year: Newest</option>
                        <option value="year ASC" <?php echo $sort === 'year ASC' ? 'selected' : ''; ?>>Year: Oldest</option>
                        <option value="mileage ASC" <?php echo $sort === 'mileage ASC' ? 'selected' : ''; ?>>Mileage: Low to High</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="cars.php" class="btn btn-secondary">Clear All</a>
                </div>
            </form>
        </div>
    </section>

    <!-- Results Section -->
    <section class="results-section">
        <div class="container">
            <div class="results-header">
                <h2>Available Cars</h2>
                <p><?php echo $total_cars; ?> cars found</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php elseif (empty($cars)): ?>
                <div class="no-results">
                    <i class="fas fa-car"></i>
                    <h3>No cars found</h3>
                    <p>Try adjusting your search criteria or browse all available cars.</p>
                    <a href="cars.php" class="btn btn-primary">View All Cars</a>
                </div>
            <?php else: ?>
                <div class="cars-grid">
                    <?php foreach ($cars as $car): ?>
                        <div class="car-card">
                            <div class="car-image">
                                <img src="<?php echo $car['image'] ? UPLOAD_PATH . $car['image'] : '/placeholder.svg?height=200&width=300'; ?>" 
                                     alt="<?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>">
                                <div class="car-price"><?php echo formatPrice($car['price']); ?></div>
                                <?php if ($car['is_featured']): ?>
                                    <div class="featured-badge">
                                        <i class="fas fa-star"></i>
                                        Featured
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="car-info">
                                <h3 class="car-title"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></h3>
                                <div class="car-details">
                                    <div class="car-detail">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo $car['year']; ?></span>
                                    </div>
                                    <div class="car-detail">
                                        <i class="fas fa-gas-pump"></i>
                                        <span><?php echo ucfirst($car['fuel_type']); ?></span>
                                    </div>
                                    <div class="car-detail">
                                        <i class="fas fa-cogs"></i>
                                        <span><?php echo ucfirst($car['transmission']); ?></span>
                                    </div>
                                    <div class="car-detail">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo ucfirst($car['ownership']); ?> Owner</span>
                                    </div>
                                    <div class="car-detail">
                                        <i class="fas fa-tachometer-alt"></i>
                                        <span><?php echo number_format($car['mileage']); ?> km</span>
                                    </div>
                                    <div class="car-detail">
                                        <i class="fas fa-palette"></i>
                                        <span><?php echo ucfirst($car['color']); ?></span>
                                    </div>
                                </div>
                                <div class="car-actions">
                                    <a href="car-details.php?id=<?php echo $car['id']; ?>" class="btn btn-primary btn-small">View Details</a>
                                    <a href="car-details.php?id=<?php echo $car['id']; ?>#enquiry" class="btn btn-outline btn-small">Enquire Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-btn">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

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
</body>
</html>