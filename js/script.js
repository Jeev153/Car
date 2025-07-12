// Mobile Navigation Toggle
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
});

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-link').forEach(n => n.addEventListener('click', () => {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
}));

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Navbar background change on scroll
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 100) {
        navbar.style.background = 'rgba(255, 255, 255, 0.98)';
    } else {
        navbar.style.background = 'rgba(255, 255, 255, 0.95)';
    }
});

// Category card click handlers
document.querySelectorAll('.category-card').forEach(card => {
    card.addEventListener('click', () => {
        const category = card.dataset.category;
        window.location.href = `cars.php?category=${category}`;
    });
});

// Load featured cars on homepage
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('featured-cars')) {
        loadFeaturedCars();
    }
});

// Function to load featured cars
async function loadFeaturedCars() {
    const container = document.getElementById('featured-cars');
    
    try {
        const response = await fetch('api/get-featured-cars.php');
        const cars = await response.json();
        
        if (cars.length === 0) {
            container.innerHTML = '<p class="text-center">No featured cars available at the moment.</p>';
            return;
        }
        
        container.innerHTML = cars.map(car => `
            <div class="car-card" onclick="viewCarDetails(${car.id})">
                <div class="car-image">
                    <img src="${car.image || '/placeholder.svg?height=200&width=300'}" alt="${car.make} ${car.model}">
                    <div class="car-price">$${parseInt(car.price).toLocaleString()}</div>
                </div>
                <div class="car-info">
                    <h3 class="car-title">${car.make} ${car.model}</h3>
                    <div class="car-details">
                        <div class="car-detail">
                            <i class="fas fa-calendar"></i>
                            <span>${car.year}</span>
                        </div>
                        <div class="car-detail">
                            <i class="fas fa-gas-pump"></i>
                            <span>${car.fuel_type}</span>
                        </div>
                        <div class="car-detail">
                            <i class="fas fa-cogs"></i>
                            <span>${car.transmission}</span>
                        </div>
                        <div class="car-detail">
                            <i class="fas fa-user"></i>
                            <span>${car.ownership}</span>
                        </div>
                    </div>
                    <div class="car-actions">
                        <a href="car-details.php?id=${car.id}" class="btn btn-primary btn-small">View Details</a>
                        <a href="car-details.php?id=${car.id}#enquiry" class="btn btn-outline btn-small">Enquire Now</a>
                    </div>
                </div>
            </div>
        `).join('');
        
    } catch (error) {
        console.error('Error loading featured cars:', error);
        container.innerHTML = '<p class="text-center">Error loading cars. Please try again later.</p>';
    }
}

// Function to view car details
function viewCarDetails(carId) {
    window.location.href = `car-details.php?id=${carId}`;
}

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements for animation
document.addEventListener('DOMContentLoaded', () => {
    const animateElements = document.querySelectorAll('.category-card, .car-card, .feature-card');
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// Form validation helper
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    return isValid;
}

// Email validation
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Phone validation
function validatePhone(phone) {
    const re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/\s/g, ''));
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Loading state helper
function setLoadingState(element, isLoading) {
    if (isLoading) {
        element.disabled = true;
        element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    } else {
        element.disabled = false;
        element.innerHTML = element.dataset.originalText || 'Submit';
    }
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    
    if (searchInput && searchButton) {
        searchButton.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
}

function performSearch() {
    const searchInput = document.getElementById('search-input');
    const query = searchInput.value.trim();
    
    if (query) {
        window.location.href = `cars.php?search=${encodeURIComponent(query)}`;
    }
}

// Initialize search on page load
document.addEventListener('DOMContentLoaded', initializeSearch);

// Back to top button
const backToTopButton = document.createElement('button');
backToTopButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
backToTopButton.className = 'back-to-top';
backToTopButton.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
`;

document.body.appendChild(backToTopButton);

window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
        backToTopButton.style.opacity = '1';
        backToTopButton.style.visibility = 'visible';
    } else {
        backToTopButton.style.opacity = '0';
        backToTopButton.style.visibility = 'hidden';
    }
});

backToTopButton.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Lazy loading for images
function initializeLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Initialize lazy loading on page load
document.addEventListener('DOMContentLoaded', initializeLazyLoading);