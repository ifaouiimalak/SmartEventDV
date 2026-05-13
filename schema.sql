-- ─────────────────────────────────────────────
--  SmartEvent – Schema Update
--  Add: reviews, promo_codes
-- ─────────────────────────────────────────────

-- Reviews: one per user per event, only after the event date
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

-- Promo codes
CREATE TABLE IF NOT EXISTS promo_codes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(50)  NOT NULL UNIQUE,
    discount_type   ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value  DECIMAL(10,2) NOT NULL,           -- % or DT amount
    min_amount      DECIMAL(10,2) DEFAULT 0,          -- min order to apply
    max_uses        INT DEFAULT NULL,                 -- NULL = unlimited
    used_count      INT DEFAULT 0,
    valid_from      DATE DEFAULT NULL,
    valid_until     DATE DEFAULT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Track which user used which promo (prevent reuse)
CREATE TABLE IF NOT EXISTS promo_uses (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    promo_id     INT NOT NULL,
    user_id      INT NOT NULL,
    reservation_id INT NOT NULL,
    used_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_promo_use (promo_id, user_id),
    FOREIGN KEY (promo_id)       REFERENCES promo_codes(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);

-- Add promo_code_id + discount_amount + final_price to reservations
ALTER TABLE reservations
    ADD COLUMN IF NOT EXISTS promo_code_id   INT          NULL AFTER quantity,
    ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0 AFTER promo_code_id,
    ADD COLUMN IF NOT EXISTS final_price     DECIMAL(10,2) DEFAULT NULL AFTER discount_amount,
    ADD FOREIGN KEY IF NOT EXISTS fk_res_promo (promo_code_id) REFERENCES promo_codes(id) ON DELETE SET NULL;

-- Sample promo codes for testing
INSERT IGNORE INTO promo_codes (code, discount_type, discount_value, min_amount, max_uses, valid_from, valid_until) VALUES
('WELCOME10',  'percent', 10,  0,      NULL, '2026-01-01', '2026-12-31'),
('ETE2026',    'percent', 15,  50,     200,  '2026-06-01', '2026-08-31'),
('FLAT20DT',   'fixed',   20,  80,     100,  '2026-01-01', '2026-12-31'),
('VIP50',      'percent', 50,  200,    50,   '2026-06-01', '2026-09-30');
-- =============================================
-- Add Promo Code Support to reservations table
-- =============================================

ALTER TABLE reservations
    ADD COLUMN IF NOT EXISTS promo_code_id   INT          NULL AFTER quantity,
    ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0 AFTER promo_code_id,
    ADD COLUMN IF NOT EXISTS final_price     DECIMAL(10,2) DEFAULT NULL AFTER discount_amount,
    ADD FOREIGN KEY IF NOT EXISTS fk_res_promo (promo_code_id) 
        REFERENCES promo_codes(id) ON DELETE SET NULL;
