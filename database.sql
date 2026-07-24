
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    role ENUM('user', 'store_owner', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS stores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    phone VARCHAR(20),
    opening_hours TEXT,
    owner_id INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'moderator') DEFAULT 'moderator',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    parent_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(50),
    category_id INT,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS listings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    store_id INT NOT NULL,
    product_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_store_product (store_id, product_id),
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS search_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    query VARCHAR(255) NOT NULL,
    result_count INT DEFAULT 0,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS admin_action_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

CREATE TABLE IF NOT EXISTS approval_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('store', 'listing') NOT NULL,
    item_id INT NOT NULL,
    requested_by INT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by INT NULL,
    review_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES admins(id) ON DELETE SET NULL
);


INSERT INTO categories (id, name, parent_id) VALUES
(1, 'Electronics', NULL),
(2, 'Mobile Phones', 1),
(3, 'Laptops', 1),
(4, 'Grocery', NULL),
(5, 'Daily Essentials', 4),
(6, 'Fashion', NULL),
(7, 'Men''s Clothing', 6),
(8, 'Women''s Clothing', 6);

INSERT INTO users (id, name, email, password, role, status) VALUES 
(1, 'Test Owner', 'owner@test.com', '$2y$10$tq15RaZxEaN30zzDF0vQG.6gwlFle3s1H7inbDmc.44glcIlm4e8y', 'store_owner', 'active'),
(2, 'Normal User', 'user@test.com', '$2y$10$tq15RaZxEaN30zzDF0vQG.6gwlFle3s1H7inbDmc.44glcIlm4e8y', 'user', 'active'),
(3, 'Super Admin', 'admin@dealscout.com', '$2y$10$tq15RaZxEaN30zzDF0vQG.6gwlFle3s1H7inbDmc.44glcIlm4e8y', 'admin', 'active');

INSERT INTO products (id, name, brand, category_id, description, image_url) VALUES
(1, 'iPhone 14', 'Apple', 2, 'Latest Apple iPhone with A15 Bionic chip.', 'https://images.unsplash.com/photo-1678652197831-2d180705cd2c?w=500&q=80'),
(2, 'Samsung Galaxy S23', 'Samsung', 2, 'Android flagship with amazing camera.', 'https://images.unsplash.com/photo-1678911820864-e2c5ce75e81d?w=500&q=80'),
(3, 'Dell XPS 15', 'Dell', 3, 'High performance laptop for creators.', 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500&q=80'),
(4, 'Premium Rice 5kg', 'Local', 5, 'High quality basmati rice.', 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&q=80'),
(5, 'Sunflower Cooking Oil 1L', 'HealthyChoice', 5, 'Refined sunflower oil.', 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=500&q=80'),
(6, 'Nike Air Force 1', 'Nike', 7, 'Classic white sneakers.', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=500&q=80'),
(7, 'Levi''s 501 Original Jeans', 'Levi''s', 7, 'Straight fit iconic blue jeans.', 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=500&q=80');

INSERT INTO stores (id, name, address, latitude, longitude, phone, owner_id, status) VALUES
(1, 'Bhatbhateni Supermarket', 'Naxal, Kathmandu', 27.7172, 85.3240, '01-4412345', NULL, 'approved'),
(2, 'Big Mart', 'Lazimpat, Kathmandu', 27.7165, 85.3190, '01-4423456', NULL, 'approved'),
(3, 'Superstore', 'Baluwatar, Kathmandu', 27.7200, 85.3300, '01-4434567', NULL, 'pending'),
(4, 'Fashion Hub Patan', 'Mangal Bazar, Lalitpur', 27.6766, 85.3182, '01-5001234', 1, 'approved'),
(5, 'City Centre Fashion', 'Kamalpokhari, Kathmandu', 27.7088, 85.3283, '01-4433221', NULL, 'approved');

INSERT INTO listings (store_id, product_id, price, status) VALUES
(1, 1, 140000.00, 'approved'),
(2, 1, 138000.00, 'approved'),
(3, 1, 142000.00, 'pending'),
(1, 2, 120000.00, 'approved'),
(2, 2, 122000.00, 'approved'),
(1, 4, 1200.00, 'approved'),
(2, 4, 1150.00, 'approved'),
(4, 6, 12000.00, 'approved'),
(5, 6, 11500.00, 'approved'),
(4, 7, 6500.00, 'approved'),
(5, 7, 7000.00, 'approved');
