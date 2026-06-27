-- ============================================
-- StaySmart AI - Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS staysmart_ai
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE staysmart_ai;

-- ============================================
-- Users Table
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100)  NOT NULL,
    email          VARCHAR(150)  NOT NULL UNIQUE,
    password       VARCHAR(255)  NOT NULL,
    phone_number   VARCHAR(20),
    role           ENUM('tenant', 'owner', 'admin') NOT NULL DEFAULT 'tenant',
    gender         ENUM('male', 'female', 'other') DEFAULT NULL,
    lifestyle      VARCHAR(100)  DEFAULT NULL,      -- e.g. quiet, social, night-owl
    institution    VARCHAR(150)  DEFAULT NULL,      -- university / company
    occupation     VARCHAR(100)  DEFAULT NULL,
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Properties Table
-- ============================================
CREATE TABLE IF NOT EXISTS properties (
    property_id    INT AUTO_INCREMENT PRIMARY KEY,
    owner_id       INT NOT NULL,
    property_name  VARCHAR(150) NOT NULL,
    address        VARCHAR(255) NOT NULL,
    city           VARCHAR(100) NOT NULL,
    property_type  ENUM('apartment','house','studio','room','hostel') NOT NULL DEFAULT 'apartment',
    rooms          INT NOT NULL DEFAULT 1,
    rent           DECIMAL(10,2) NOT NULL,
    facilities     VARCHAR(500) DEFAULT NULL,        -- comma separated: wifi,parking,furnished,ac,water,backup
    lifestyle_tag  VARCHAR(100) DEFAULT NULL,        -- quiet, social, family, students
    description    TEXT,
    images         VARCHAR(1000) DEFAULT NULL,       -- comma separated file names / urls
    status         ENUM('active','pending','inactive') NOT NULL DEFAULT 'pending',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Bookings Table
-- ============================================
CREATE TABLE IF NOT EXISTS bookings (
    booking_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    property_id    INT NOT NULL,
    start_date     DATE NOT NULL,
    duration_months INT NOT NULL,                    -- 1, 3, 6, 12 (monthly/quarterly/semester/annual)
    total_amount   DECIMAL(10,2) NOT NULL,
    status         ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Reviews Table
-- ============================================
CREATE TABLE IF NOT EXISTS reviews (
    review_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    property_id    INT NOT NULL,
    rating         TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comments       TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Roommate Requests Table
-- ============================================
CREATE TABLE IF NOT EXISTS roommate_requests (
    request_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    budget         DECIMAL(10,2) NOT NULL,
    preferred_city VARCHAR(100) DEFAULT NULL,
    lifestyle      VARCHAR(100) DEFAULT NULL,
    status         ENUM('open','matched','closed') NOT NULL DEFAULT 'open',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Seed Data
-- ============================================

-- Default password for all seed users: password123
INSERT INTO users (name, email, password, phone_number, role, gender, lifestyle, institution, occupation) VALUES
('Admin User',   'admin@staysmart.ai',  '$2b$12$/iLLZVqGfVeyzVmhQqxRWufkSw3ZW5it01tyUrdISlb2JHJT0VxCK', '03000000000', 'admin', 'other', NULL, NULL, NULL),
('Ali Raza',     'ali.owner@staysmart.ai', '$2b$12$/iLLZVqGfVeyzVmhQqxRWufkSw3ZW5it01tyUrdISlb2JHJT0VxCK', '03001234567', 'owner', 'male', NULL, NULL, 'Property Manager'),
('Sara Khan',    'sara.tenant@staysmart.ai', '$2b$12$/iLLZVqGfVeyzVmhQqxRWufkSw3ZW5it01tyUrdISlb2JHJT0VxCK', '03007654321', 'tenant', 'female', 'quiet', 'NUST', 'Student'),
('Bilal Ahmed',  'bilal.tenant@staysmart.ai', '$2b$12$/iLLZVqGfVeyzVmhQqxRWufkSw3ZW5it01tyUrdISlb2JHJT0VxCK', '03009998888', 'tenant', 'male', 'social', 'Systems Ltd', 'Software Engineer');

INSERT INTO properties (owner_id, property_name, address, city, property_type, rooms, rent, facilities, lifestyle_tag, description, images, status) VALUES
(2, 'Green Valley Apartment',   'Street 12, Bahria Town', 'Islamabad', 'apartment', 2, 45000, 'wifi,parking,furnished,ac', 'quiet',   'A cozy 2-bed apartment ideal for students and professionals seeking a quiet environment.', 'house1.jpg', 'active'),
(2, 'Sunrise Residency',        'F-10 Markaz',            'Islamabad', 'apartment', 3, 60000, 'wifi,furnished,backup,water', 'social',  'Spacious 3-bed apartment close to universities and markets.', 'house2.jpg', 'active'),
(2, 'University Hostel Rooms',  'Near NUST Gate 1',        'Islamabad', 'hostel',    1, 18000, 'wifi,water', 'students', 'Affordable single rooms for students, walking distance to NUST.', 'house3.jpg', 'active'),
(2, 'Model Town Family House',  'Block C, Model Town',     'Lahore',    'house',     4, 85000, 'parking,furnished,ac,backup,water', 'family', 'A large family house with garden, perfect for long-term stay.', 'house4.jpg', 'active'),
(2, 'DHA Studio Flat',          'Phase 5, DHA',            'Lahore',    'studio',    1, 35000, 'wifi,furnished,ac', 'quiet',   'Modern studio apartment for working professionals.', 'house5.jpg', 'active');
