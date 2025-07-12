-- Create database
CREATE DATABASE IF NOT EXISTS car_sales;
USE car_sales;

-- Cars table
CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('suv', 'sedan', 'hatchback', 'convertible', 'coupe', 'wagon') NOT NULL,
    fuel_type ENUM('petrol', 'diesel', 'electric', 'hybrid') NOT NULL,
    transmission ENUM('manual', 'automatic') NOT NULL,
    ownership ENUM('first', 'second', 'third', 'fourth+') NOT NULL,
    mileage INT NOT NULL,
    color VARCHAR(30) NOT NULL,
    description TEXT,
    features TEXT,
    image VARCHAR(255),
    additional_images TEXT,
    is_featured BOOLEAN DEFAULT FALSE,
    is_sold BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Enquiries table
CREATE TABLE enquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT,
    status ENUM('new', 'contacted', 'interested', 'not_interested', 'sold') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

-- Contact messages table
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Activity logs table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO admin_users (username, password, email, full_name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@autodeals.com', 'System Administrator');

-- Insert sample cars
INSERT INTO cars (make, model, year, price, category, fuel_type, transmission, ownership, mileage, color, description, features, is_featured) VALUES
('Toyota', 'Camry', 2020, 25000.00, 'sedan', 'petrol', 'automatic', 'first', 15000, 'White', 'Well-maintained Toyota Camry with excellent fuel efficiency and comfort features.', 'Air Conditioning, Power Steering, ABS, Airbags, Bluetooth, Backup Camera', TRUE),
('Honda', 'CR-V', 2019, 28000.00, 'suv', 'petrol', 'automatic', 'first', 22000, 'Black', 'Spacious Honda CR-V perfect for families. Excellent safety ratings and reliability.', 'AWD, Sunroof, Heated Seats, Navigation System, Lane Departure Warning', TRUE),
('BMW', '3 Series', 2021, 35000.00, 'sedan', 'petrol', 'automatic', 'first', 8000, 'Blue', 'Luxury BMW 3 Series with premium features and exceptional performance.', 'Leather Seats, Premium Sound System, Adaptive Cruise Control, Parking Sensors', TRUE),
('Ford', 'Mustang', 2018, 32000.00, 'coupe', 'petrol', 'manual', 'second', 35000, 'Red', 'Classic Ford Mustang with powerful V8 engine. Perfect for sports car enthusiasts.', 'V8 Engine, Sport Mode, Premium Wheels, Racing Stripes, Performance Exhaust', TRUE),
('Volkswagen', 'Golf', 2020, 22000.00, 'hatchback', 'petrol', 'manual', 'first', 18000, 'Silver', 'Compact and efficient Volkswagen Golf. Great for city driving and daily commute.', 'Fuel Efficient, Compact Design, Modern Infotainment, Safety Features', TRUE),
('Audi', 'A4 Convertible', 2019, 42000.00, 'convertible', 'petrol', 'automatic', 'first', 12000, 'White', 'Elegant Audi A4 Convertible with retractable soft top. Luxury meets performance.', 'Convertible Top, Leather Interior, Premium Audio, Climate Control', TRUE),
('Subaru', 'Outback', 2020, 26000.00, 'wagon', 'petrol', 'automatic', 'first', 20000, 'Green', 'Versatile Subaru Outback wagon with all-wheel drive. Perfect for adventures.', 'AWD, Roof Rails, Cargo Space, Ground Clearance, Safety Features', FALSE),
('Nissan', 'Altima', 2019, 21000.00, 'sedan', 'petrol', 'automatic', 'second', 28000, 'Gray', 'Reliable Nissan Altima with comfortable interior and smooth ride quality.', 'Comfortable Seats, Good Fuel Economy, Spacious Interior, Modern Tech', FALSE);

-- Create indexes for better performance
CREATE INDEX idx_cars_category ON cars(category);
CREATE INDEX idx_cars_price ON cars(price);
CREATE INDEX idx_cars_year ON cars(year);
CREATE INDEX idx_cars_featured ON cars(is_featured);
CREATE INDEX idx_cars_sold ON cars(is_sold);
CREATE INDEX idx_enquiries_car_id ON enquiries(car_id);
CREATE INDEX idx_enquiries_status ON enquiries(status);
CREATE INDEX idx_contact_status ON contact_messages(status);