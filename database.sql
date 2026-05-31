-- Aromea's database

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    fullname VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    age TINYINT UNSIGNED,
    gender ENUM('male','female','other','prefer_not'),
    user_type ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE collections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) UNIQUE NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    category ENUM('designer','niche','arabic','other') DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE accords (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(80) NOT NULL UNIQUE,
    color VARCHAR(20) DEFAULT '#c4a882'
);

CREATE TABLE notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(80) NOT NULL UNIQUE,
    image_url VARCHAR(255)
);

CREATE TABLE fragrances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(80),
    collection_id INT,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    video_url VARCHAR(255),
    fragrance_type VARCHAR(60),
    concentration VARCHAR(60),
    gender ENUM('him','her','unisex') DEFAULT 'unisex',
    longevity VARCHAR(60),
    sillage VARCHAR(60),
    occasion VARCHAR(120),
    season_summer TINYINT(1) DEFAULT 0,
    season_spring TINYINT(1) DEFAULT 0,
    season_fall   TINYINT(1) DEFAULT 0,
    season_winter TINYINT(1) DEFAULT 0,
    time_day      TINYINT(1) DEFAULT 0,
    time_night    TINYINT(1) DEFAULT 0,
    bottle_size VARCHAR(40),
    rating DECIMAL(3,2) DEFAULT 5.00,
    stock INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE SET NULL
);

CREATE TABLE fragrance_accords (
    fragrance_id INT NOT NULL,
    accord_id INT NOT NULL,
    strength TINYINT UNSIGNED DEFAULT 50,
    PRIMARY KEY (fragrance_id, accord_id),
    FOREIGN KEY (fragrance_id) REFERENCES fragrances(id) ON DELETE CASCADE,
    FOREIGN KEY (accord_id)    REFERENCES accords(id)    ON DELETE CASCADE
);

CREATE TABLE fragrance_notes (
    fragrance_id INT NOT NULL,
    note_id INT NOT NULL,
    tier ENUM('top','heart','base') NOT NULL,
    PRIMARY KEY (fragrance_id, note_id, tier),
    FOREIGN KEY (fragrance_id) REFERENCES fragrances(id) ON DELETE CASCADE,
    FOREIGN KEY (note_id)      REFERENCES notes(id)      ON DELETE CASCADE
);

CREATE TABLE packs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) UNIQUE NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    discount_pct DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE pack_fragrances (
    pack_id INT NOT NULL,
    fragrance_id INT NOT NULL,
    sort_order TINYINT UNSIGNED DEFAULT 0,
    PRIMARY KEY (pack_id, fragrance_id),
    FOREIGN KEY (pack_id)      REFERENCES packs(id)      ON DELETE CASCADE,
    FOREIGN KEY (fragrance_id) REFERENCES fragrances(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    payment_method VARCHAR(50),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    fragrance_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)     REFERENCES orders(id)     ON DELETE CASCADE,
    FOREIGN KEY (fragrance_id) REFERENCES fragrances(id) ON DELETE RESTRICT
);

CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    fragrance_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_fragrance (user_id, fragrance_id),
    FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (fragrance_id) REFERENCES fragrances(id) ON DELETE CASCADE
);

CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    fragrance_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wish (user_id, fragrance_id),
    FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
    FOREIGN KEY (fragrance_id) REFERENCES fragrances(id) ON DELETE CASCADE
);

CREATE INDEX idx_frag_collection ON fragrances(collection_id);
CREATE INDEX idx_frag_brand      ON fragrances(brand);
CREATE INDEX idx_order_user      ON orders(user_id);
CREATE INDEX idx_cart_user       ON cart(user_id);

-- ── Migration: add classification column to fragrances ──
ALTER TABLE fragrances ADD COLUMN IF NOT EXISTS classification VARCHAR(40) DEFAULT '' AFTER is_featured;

-- ── Create uploads folder (handled automatically by upload.php, but document here) ──
-- mkdir aromea/images/uploads  (auto-created on first upload)

-- ── Migration: add is_featured to collections ──
ALTER TABLE collections ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) DEFAULT 0 AFTER category;

-- ── Migration: add show_on_home, home_order, sale_price to collections ──
ALTER TABLE collections ADD COLUMN IF NOT EXISTS show_on_home TINYINT(1) DEFAULT 0 AFTER category;
ALTER TABLE collections ADD COLUMN IF NOT EXISTS home_order   INT DEFAULT 0 AFTER show_on_home;
ALTER TABLE collections ADD COLUMN IF NOT EXISTS sale_price   DECIMAL(10,2) DEFAULT NULL AFTER home_order;

-- ── Migration: add show_on_home to fragrances ──
ALTER TABLE fragrances ADD COLUMN IF NOT EXISTS show_on_home TINYINT(1) DEFAULT 0 AFTER classification;
