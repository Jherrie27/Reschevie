-- ============================================================
-- RESCHEVIE LUXURY JEWELRY WEBSITE
-- MySQL Database Schema
-- Web Systems and Technologies 2 — ITS122L
-- Group EBK | Mapúa University
-- ============================================================

CREATE DATABASE IF NOT EXISTS reschevie_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reschevie_db;

-- ============================================================
-- TABLE: users
-- Stores registered customer accounts
-- ============================================================
CREATE TABLE users (
  user_id       INT AUTO_INCREMENT PRIMARY KEY,
  user_username VARCHAR(50)  NOT NULL UNIQUE,
  user_password VARCHAR(255) NOT NULL,  -- store bcrypt hash in production
  user_email    VARCHAR(100) NOT NULL UNIQUE,
  user_fname    VARCHAR(50)  NOT NULL,
  user_lname    VARCHAR(50)  NOT NULL,
  user_contact  VARCHAR(20),
  created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: admins
-- Stores admin accounts (separate from users)
-- ============================================================
CREATE TABLE admins (
  admin_id       INT AUTO_INCREMENT PRIMARY KEY,
  admin_email    VARCHAR(100) NOT NULL UNIQUE,
  admin_password VARCHAR(255) NOT NULL,
  admin_fname    VARCHAR(50)  NOT NULL,
  admin_lname    VARCHAR(50)  NOT NULL,
  created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (password: Admin@2026 — hash this in production!)
INSERT INTO admins (admin_email, admin_password, admin_fname, admin_lname)
VALUES ('admin@reschevie.com', 'reschevieAdmin2026&!', 'Samantha', 'Sayaman');

-- ============================================================
-- TABLE: products
-- Stores all jewelry products
-- ============================================================
CREATE TABLE products (
  product_id          INT AUTO_INCREMENT PRIMARY KEY,
  product_name        VARCHAR(200) NOT NULL,
  product_description TEXT,
  product_type        ENUM('ring','necklace','earring','bracelet','other') NOT NULL,
  product_origin      ENUM('Japan','Italy','Saudi Arabia','Hong Kong','Other') NOT NULL,
  product_materials   VARCHAR(300),
  product_karat       VARCHAR(10),
  product_weight      VARCHAR(20),
  product_price       DECIMAL(12,2),          -- NULL means Price Upon Request
  product_price_poa   TINYINT(1) DEFAULT 0,   -- 1 = Price Upon Request
  product_status      ENUM('available','sold','reserved') DEFAULT 'available',
  product_featured    TINYINT(1) DEFAULT 0,
  product_emoji       VARCHAR(10)  DEFAULT '💍',
  created_at          DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed sample products
INSERT INTO products (product_name, product_description, product_type, product_origin, product_materials, product_karat, product_weight, product_price, product_price_poa, product_status, product_featured, product_emoji) VALUES
('Serpent Bohème Necklace', 'Inspired by Renaissance-era serpent motifs, this sinuous necklace features a hand-engraved snake body set with pavé diamonds along its spine.', 'necklace', 'Italy', '18K Yellow Gold, VS1 Diamond', '18K', '12.4g', NULL, 1, 'available', 1, '🐍'),
('Diamond Pavé Ring', 'A bold statement ring set with 48 pavé diamonds in traditional Arabian filigree setting.', 'ring', 'Saudi Arabia', '22K Yellow Gold, VVS2 Diamonds', '22K', '8.2g', 85000.00, 0, 'available', 1, '💍'),
('Wabi-Sabi Cuff', 'An intentionally imperfect cuff that celebrates the Japanese philosophy of beauty in imperfection.', 'bracelet', 'Japan', '24K Pure Gold', '24K', '22.1g', 120000.00, 0, 'available', 1, '⭕'),
('Dragon Phoenix Earrings', 'Dangle earrings featuring a stylized dragon and phoenix motif — symbols of eternal partnership.', 'earring', 'Hong Kong', '18K Rose Gold, Ruby, Diamond', '18K', '6.8g', 148000.00, 0, 'available', 1, '🐉'),
('Minimalist Bar Necklace', 'Clean geometric lines meet Japanese precision. A brushed gold bar with mirror-polished edges.', 'necklace', 'Japan', '18K Yellow Gold', '18K', '4.1g', 42000.00, 0, 'available', 0, '➖'),
('Florentine Coin Pendant', 'A hand-engraved coin pendant featuring the Florentine lily motif, an ancient symbol of refinement.', 'necklace', 'Italy', '21K Yellow Gold', '21K', '9.3g', 68000.00, 0, 'available', 0, '🔮'),
('Arabian Star Ring', 'An eight-pointed star ring set with a deep blue sapphire at its heart.', 'ring', 'Saudi Arabia', '21K Gold, Sapphire', '21K', '7.6g', 76500.00, 0, 'available', 0, '⭐'),
('Kanji Luck Pendant', 'The Kanji character for fortune masterfully carved into pure 24K gold.', 'necklace', 'Japan', '24K Pure Gold', '24K', '5.2g', 55000.00, 0, 'sold', 0, '🎋');

-- ============================================================
-- TABLE: product_images
-- One product can have many images (One-to-Many)
-- ============================================================
CREATE TABLE product_images (
  p_image_id  INT AUTO_INCREMENT PRIMARY KEY,
  product_id  INT NOT NULL,
  p_image_url VARCHAR(500) NOT NULL,
  is_primary  TINYINT(1) DEFAULT 0,
  sort_order  INT DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: inquiries
-- Stores customer inquiry/quote requests
-- ============================================================
CREATE TABLE inquiries (
  inquiry_id       INT AUTO_INCREMENT PRIMARY KEY,
  user_id          INT,         -- NULL for guest inquiries
  fname            VARCHAR(50)  NOT NULL,
  lname            VARCHAR(50)  NOT NULL,
  email            VARCHAR(100) NOT NULL,
  phone            VARCHAR(20),
  contact_pref     ENUM('email','phone') DEFAULT 'email',
  special_requests TEXT,
  status           ENUM('pending','in-progress','completed','cancelled') DEFAULT 'pending',
  submitted_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE: inquiry_items
-- Products linked to an inquiry (Many-to-Many bridge)
-- ============================================================
CREATE TABLE inquiry_items (
  item_id     INT AUTO_INCREMENT PRIMARY KEY,
  inquiry_id  INT NOT NULL,
  product_id  INT NOT NULL,
  FOREIGN KEY (inquiry_id) REFERENCES inquiries(inquiry_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: client_stories
-- Testimonials and curated narratives
-- ============================================================
CREATE TABLE client_stories (
  story_id          INT AUTO_INCREMENT PRIMARY KEY,
  story_name        VARCHAR(200) NOT NULL,
  story_author      VARCHAR(100) NOT NULL,
  story_description TEXT        NOT NULL,
  story_date_posted DATE        DEFAULT (CURRENT_DATE),
  created_at        DATETIME    DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO client_stories (story_name, story_author, story_description, story_date_posted) VALUES
('A Wedding That Started a Legacy', 'Maria Santos', 'When I commissioned the Diamond Pavé Ring for my wedding, I never imagined it would become a family heirloom. My daughter wore it at her own wedding last year. The quality truly endures.', '2025-11-12'),
('Gifting Beyond Borders', 'James Chen', 'I sent the Dragon Phoenix Earrings to my partner in Manila from Hong Kong. The craftsmanship resonated with our shared heritage. Reschevie understood exactly what we needed.', '2025-12-01'),
('Investing in Beauty', 'Priya Nanwani', 'The Wabi-Sabi Cuff is my most prized possession. It is not just jewelry — it is a philosophy made tangible. I wear it every day and receive compliments from strangers constantly.', '2026-01-08');

-- ============================================================
-- TABLE: newsletters
-- Email subscriptions
-- ============================================================
CREATE TABLE newsletters (
  newsletter_id       INT AUTO_INCREMENT PRIMARY KEY,
  newsletter_email    VARCHAR(100) NOT NULL UNIQUE,
  newsletter_subbed_at DATETIME    DEFAULT CURRENT_TIMESTAMP,
  is_active           TINYINT(1)  DEFAULT 1,
  FOREIGN KEY (newsletter_email) REFERENCES users(user_email) ON DELETE CASCADE
);

-- ============================================================
-- USEFUL QUERIES
-- ============================================================

-- Get all available featured products
-- SELECT * FROM products WHERE product_status = 'available' AND product_featured = 1;

-- Get products by origin
-- SELECT * FROM products WHERE product_origin = 'Japan' AND product_status = 'available';

-- Get full inquiry with items and product names
-- SELECT i.*, ii.product_id, p.product_name, p.product_origin
-- FROM inquiries i
-- JOIN inquiry_items ii ON i.inquiry_id = ii.inquiry_id
-- JOIN products p ON ii.product_id = p.product_id
-- WHERE i.inquiry_id = 1;

-- Count inquiries by status
-- SELECT status, COUNT(*) as count FROM inquiries GROUP BY status;

-- Get all newsletter subscribers
-- SELECT newsletter_email, newsletter_subbed_at FROM newsletters WHERE is_active = 1;
