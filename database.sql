CREATE DATABASE IF NOT EXISTS order_db;
USE order_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    unit_price DECIMAL(10, 2) NOT NULL
);

CREATE TABLE IF NOT EXISTS offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS offer_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    description TEXT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE
);

-- Truncate tables to ensure a clean state
TRUNCATE TABLE offer_items;
TRUNCATE TABLE offers;
TRUNCATE TABLE products;
TRUNCATE TABLE users;

-- Sample Data
INSERT INTO users (id, email, password) VALUES
(1, 'alice@test.com', '$2y$12$dZUHO0rTap3DsRqzJIgieOHg1AK772p3ap6XoTI1eg2tfVElLXcmS'),
(2, 'bob@test.com', '$2y$12$dZUHO0rTap3DsRqzJIgieOHg1AK772p3ap6XoTI1eg2tfVElLXcmS');

INSERT INTO products (name, description, unit_price) VALUES
('Web Sitesi Tasarımı - Temel Paket', '5 sayfalık statik web sitesi tasarımı ve yayına alınması.', 4500.00),
('Web Sitesi Tasarımı - E-Ticaret Paketi', 'Özelleştirilmiş e-ticaret altyapısı, ürün yönetimi ve ödeme entegrasyonu.', 12000.00),
('Logo Tasarımı', '3 farklı konseptte logo tasarımı ve revizyon hakları.', 1500.00),
('Aylık Bakım ve Destek', 'Web sitesi için aylık teknik bakım, güncelleme ve yedekleme hizmeti.', 800.00);

-- Sample Offer 1 (for user Alice)
INSERT INTO offers (id, user_id, customer_name, total_amount, created_at) VALUES
(1, 1, 'ACME Corp', 5900.00, '2023-10-26 10:00:00');

INSERT INTO offer_items (offer_id, description, quantity, unit_price) VALUES
(1, 'Web Sitesi Tasarımı - Temel Paket', 1, 4500.00),
(1, 'Aylık Bakım ve Destek', 1, 800.00);

-- Sample Offer 2 (for user Bob)
INSERT INTO offers (id, user_id, customer_name, total_amount, created_at) VALUES
(2, 2, 'Globex Ltd', 13500.00, '2023-10-27 11:30:00');

INSERT INTO offer_items (offer_id, description, quantity, unit_price) VALUES
(2, 'Web Sitesi Tasarımı - E-Ticaret Paketi', 1, 12000.00),
(2, 'Logo Tasarımı', 1, 1500.00);
