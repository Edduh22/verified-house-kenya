CREATE DATABASE IF NOT EXISTS verified_house
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE verified_house;

-- =====================================================
-- USERS
-- =====================================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30),
    password VARCHAR(255) NOT NULL,
    role ENUM('tenant', 'landlord', 'admin') NOT NULL DEFAULT 'tenant',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- PROPERTIES
-- =====================================================

CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    landlord_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    property_type ENUM(
        'Apartment',
        'Bedsitter',
        'Studio',
        'House',
        'Maisonette',
        'Villa',
        'Hostel',
        'Single Room',
        'Commercial'
    ) NOT NULL,
    county VARCHAR(100) NOT NULL,
    town VARCHAR(100),
    area VARCHAR(150),
    address VARCHAR(255),
    rent DECIMAL(12,2) NOT NULL,
    bedrooms INT DEFAULT 0,
    bathrooms INT DEFAULT 0,
    amenities TEXT,
    status ENUM(
        'available',
        'occupied',
        'inactive'
    ) DEFAULT 'available',
    verification_status ENUM(
        'pending',
        'verified',
        'rejected'
    ) DEFAULT 'pending',
    admin_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_properties_landlord
        FOREIGN KEY (landlord_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

-- =====================================================
-- PROPERTY IMAGES
-- =====================================================

CREATE TABLE property_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_property_images_property
        FOREIGN KEY (property_id)
        REFERENCES properties(id)
        ON DELETE CASCADE
);

-- =====================================================
-- FAVOURITES
-- =====================================================

CREATE TABLE favourites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_favourite (tenant_id, property_id),

    CONSTRAINT fk_favourites_tenant
        FOREIGN KEY (tenant_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_favourites_property
        FOREIGN KEY (property_id)
        REFERENCES properties(id)
        ON DELETE CASCADE
);

-- =====================================================
-- VIEWING REQUESTS
-- =====================================================

CREATE TABLE viewing_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    property_id INT NOT NULL,
    landlord_id INT NOT NULL,
    requested_date DATE,
    requested_time TIME,
    message TEXT,
    status ENUM(
        'pending',
        'approved',
        'rejected',
        'completed',
        'cancelled'
    ) DEFAULT 'pending',
    landlord_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_viewing_tenant
        FOREIGN KEY (tenant_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_viewing_property
        FOREIGN KEY (property_id)
        REFERENCES properties(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_viewing_landlord
        FOREIGN KEY (landlord_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

-- =====================================================
-- BOOKINGS
-- =====================================================

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    property_id INT NOT NULL,
    landlord_id INT NOT NULL,
    booking_date DATE,
    status ENUM(
        'pending',
        'confirmed',
        'cancelled',
        'completed'
    ) DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_bookings_tenant
        FOREIGN KEY (tenant_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_bookings_property
        FOREIGN KEY (property_id)
        REFERENCES properties(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_bookings_landlord
        FOREIGN KEY (landlord_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

-- =====================================================
-- PROPERTY VERIFICATION
-- =====================================================

CREATE TABLE property_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    admin_id INT,
    verification_type VARCHAR(100),
    document_path VARCHAR(255),
    remarks TEXT,
    status ENUM(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending',
    verified_at TIMESTAMP NULL,

    CONSTRAINT fk_verification_property
        FOREIGN KEY (property_id)
        REFERENCES properties(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_verification_admin
        FOREIGN KEY (admin_id)
        REFERENCES users(id)
        ON DELETE SET NULL
);

-- =====================================================
-- NOTIFICATIONS
-- =====================================================

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);