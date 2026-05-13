CREATE DATABASE IF NOT EXISTS smartevent CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartevent;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS categories;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(20) DEFAULT '✨'
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    favorite_category_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (favorite_category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    location VARCHAR(150),
    event_date DATE NOT NULL,
    price DECIMAL(10,2) DEFAULT 0,
    places INT DEFAULT 0,
    image VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    quantity INT DEFAULT 1,
    status ENUM('en attente', 'confirmee', 'annulee') DEFAULT 'en attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (user_id, event_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

INSERT INTO categories(name, icon) VALUES
('Musique', '🎵'),
('Concert', '🎤'),
('Sport', '🏆'),
('Port & Marina', '⚓'),
('Voyage', '✈️'),
('Formation', '🎓'),
('Festival', '🎪'),
('Business', '💼'),
('Gastronomie', '🍽️'),
('Atelier', '🎨');

INSERT INTO users(name, email, password, role, favorite_category_id) VALUES
('Admin', 'admin@smartevent.com', '$2y$12$ikplvGOytFGA7GOKxPGTie/noYVy80MMqRb951SFpmIHGkN1sK6SW', 'admin', NULL);

INSERT INTO events(category_id, title, description, location, event_date, price, places, image) VALUES
(1, 'Soirée Musique Live', 'DJ, artistes locaux et ambiance lounge dans un espace moderne.', 'Gammarth', '2026-06-10', 35.00, 150, 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=1200&q=80'),
(2, 'Mega Concert Tunis', 'Grand concert avec scène lumineuse, son premium et accès rapide.', 'Cité de la Culture, Tunis', '2026-06-15', 55.00, 300, 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=1200&q=80'),
(4, 'Marina Night Port El Kantaoui', 'Balade port, musique live, restauration et animations au bord de mer.', 'Port El Kantaoui', '2026-06-18', 48.00, 120, 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&w=1200&q=80'),
(6, 'Bootcamp PHP & JavaScript', 'Formation intensive pour construire une application dynamique complète.', 'Lac 1, Tunis', '2026-06-20', 180.00, 35, 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80'),
(7, 'Festival Summer Vibes', 'Festival plein air avec food court, spectacles et show lumineux.', 'Hammamet', '2026-06-28', 70.00, 500, 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1200&q=80'),
(3, 'Run Challenge Carthage', 'Course sportive avec suivi, certificat et espace sponsor.', 'Carthage', '2026-07-02', 25.00, 200, 'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=1200&q=80'),
(5, 'Weekend Djerba Experience', 'Voyage organisé avec activités, plage, transport et découverte culturelle.', 'Djerba', '2026-07-05', 320.00, 50, 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80'),
(8, 'Startup Networking Night', 'Pitchs, rencontres entrepreneurs, opportunités et espace VIP.', 'La Marsa', '2026-07-09', 60.00, 80, 'https://images.unsplash.com/photo-1515187029135-18ee286d815b?auto=format&fit=crop&w=1200&q=80'),
(9, 'Taste of Tunisia', 'Expérience culinaire tunisienne avec chefs, dégustation et animation.', 'Sidi Bou Said', '2026-07-14', 85.00, 90, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=1200&q=80'),
(10, 'Atelier Design UI/UX', 'Atelier pratique pour créer des interfaces propres et professionnelles.', 'Sousse', '2026-07-19', 75.00, 40, 'https://images.unsplash.com/photo-1518005020951-eccb494ad742?auto=format&fit=crop&w=1200&q=80'),
(2, 'Acoustic Night', 'Concert acoustique intimiste avec places limitées et ambiance premium.', 'Marsa Corniche', '2026-07-25', 42.00, 110, 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=1200&q=80'),
(4, 'Harbor Sunset Event', 'Événement coucher de soleil au port avec photos, musique et networking.', 'Bizerte Marina', '2026-08-01', 50.00, 140, 'https://images.unsplash.com/photo-1470115636492-6d2b56f9146d?auto=format&fit=crop&w=1200&q=80'),
(3, 'Beach Volley Day', 'Tournoi sportif au bord de mer avec remise de prix et ambiance estivale.', 'La Goulette', '2026-08-06', 30.00, 160, 'https://images.unsplash.com/photo-1547347298-4074fc3086f0?auto=format&fit=crop&w=1200&q=80'),
(5, 'Sahara Premium Trip', 'Excursion désert, nuit étoilée et expérience culturelle guidée.', 'Douz', '2026-08-12', 410.00, 45, 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?auto=format&fit=crop&w=1200&q=80'),
(1, 'Jazz Lounge Session', 'Session jazz avec espace VIP, boissons soft et ambiance élégante.', 'Tunis Centre', '2026-08-18', 65.00, 130, 'https://images.unsplash.com/photo-1511192336575-5a79af67a629?auto=format&fit=crop&w=1200&q=80');
-- =============================================
--  SmartEvent – Schema Update
--  Add: reviews + full promo system
-- =============================================

-- Reviews: one per user per event
CREATE TABLE IF NOT EXISTS reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    event_id    INT NOT NULL,
    rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (user_id, event_id),
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
);

-- Promo Codes Table
CREATE TABLE IF NOT EXISTS promo_codes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(50)  NOT NULL UNIQUE,
    discount_type   ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value  DECIMAL(10,2) NOT NULL,
    min_amount      DECIMAL(10,2) DEFAULT 0,
    max_uses        INT DEFAULT NULL,
    used_count      INT DEFAULT 0,
    valid_from      DATE DEFAULT NULL,
    valid_until     DATE DEFAULT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Track promo usage per user
CREATE TABLE IF NOT EXISTS promo_uses (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    promo_id        INT NOT NULL,
    user_id         INT NOT NULL,
    reservation_id  INT NOT NULL,
    used_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_promo_use (promo_id, user_id),
    FOREIGN KEY (promo_id)       REFERENCES promo_codes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)        REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add promo columns to reservations table
ALTER TABLE reservations
    ADD COLUMN IF NOT EXISTS promo_code_id   INT          NULL AFTER quantity,
    ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0 AFTER promo_code_id,
    ADD COLUMN IF NOT EXISTS final_price     DECIMAL(10,2) DEFAULT NULL AFTER discount_amount,
    ADD FOREIGN KEY IF NOT EXISTS fk_res_promo (promo_code_id) 
        REFERENCES promo_codes(id) ON DELETE SET NULL;

-- Sample Promo Codes for testing
INSERT IGNORE INTO promo_codes 
    (code, discount_type, discount_value, min_amount, max_uses, valid_from, valid_until) 
VALUES
    ('WELCOME10',  'percent', 10,  0,   NULL, '2026-01-01', '2026-12-31'),
    ('ETE2026',    'percent', 15,  50,  200,  '2026-06-01', '2026-08-31'),
    ('FLAT20DT',   'fixed',   20,  80,  100,  '2026-01-01', '2026-12-31'),
    ('VIP50',      'percent', 50,  200, 50,   '2026-06-01', '2026-09-30');