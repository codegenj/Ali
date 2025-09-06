-- Domain Checker Veritabanı Yapısı
-- Bu dosyayı MySQL'de çalıştırarak veritabanını oluşturun

CREATE DATABASE IF NOT EXISTS domain_checker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE domain_checker;

-- Domain kontrol sonuçları tablosu
CREATE TABLE IF NOT EXISTS domain_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('available', 'registered', 'error') NOT NULL DEFAULT 'error',
    expiry_date DATETIME NULL,
    registrar VARCHAR(255) NULL,
    created_date DATETIME NULL,
    last_checked DATETIME NOT NULL,
    is_premium BOOLEAN DEFAULT FALSE,
    drop_date DATETIME NULL,
    whois_data TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_domain (domain),
    INDEX idx_status (status),
    INDEX idx_is_premium (is_premium),
    INDEX idx_drop_date (drop_date),
    INDEX idx_last_checked (last_checked)
);

-- Domain geçmişi tablosu (değişiklikleri takip etmek için)
CREATE TABLE IF NOT EXISTS domain_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    old_status ENUM('available', 'registered', 'error') NULL,
    new_status ENUM('available', 'registered', 'error') NOT NULL,
    old_expiry_date DATETIME NULL,
    new_expiry_date DATETIME NULL,
    old_registrar VARCHAR(255) NULL,
    new_registrar VARCHAR(255) NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_domain (domain),
    INDEX idx_changed_at (changed_at),
    FOREIGN KEY (domain) REFERENCES domain_checks(domain) ON DELETE CASCADE
);

-- Premium domain listesi tablosu
CREATE TABLE IF NOT EXISTS premium_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL UNIQUE,
    reason VARCHAR(255) NOT NULL, -- 'short', 'keyword', 'numeric', 'manual'
    keyword VARCHAR(255) NULL,
    value_estimate DECIMAL(10,2) NULL,
    notes TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_domain (domain),
    INDEX idx_reason (reason),
    INDEX idx_is_active (is_active)
);

-- Domain takip listesi (kullanıcıların takip etmek istediği domainler)
CREATE TABLE IF NOT EXISTS watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    user_email VARCHAR(255) NULL,
    notification_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_domain_user (domain, user_email),
    INDEX idx_domain (domain),
    INDEX idx_user_email (user_email)
);

-- Bildirim geçmişi tablosu
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    notification_type ENUM('dropped', 'expiring', 'status_change') NOT NULL,
    message TEXT NOT NULL,
    sent_to VARCHAR(255) NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    
    INDEX idx_domain (domain),
    INDEX idx_type (notification_type),
    INDEX idx_sent_at (sent_at),
    INDEX idx_status (status)
);

-- Cron job logları tablosu
CREATE TABLE IF NOT EXISTS cron_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(100) NOT NULL,
    status ENUM('started', 'completed', 'failed') NOT NULL,
    domains_checked INT DEFAULT 0,
    errors_count INT DEFAULT 0,
    execution_time DECIMAL(10,3) NULL, -- saniye cinsinden
    error_message TEXT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_job_name (job_name),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
);

-- API kullanım istatistikleri tablosu
CREATE TABLE IF NOT EXISTS api_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_name VARCHAR(100) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    request_count INT DEFAULT 1,
    success_count INT DEFAULT 0,
    error_count INT DEFAULT 0,
    total_response_time DECIMAL(10,3) DEFAULT 0,
    date DATE NOT NULL,
    
    UNIQUE KEY unique_api_date (api_name, endpoint, date),
    INDEX idx_api_name (api_name),
    INDEX idx_date (date)
);

-- Örnek veriler ekleme
INSERT INTO premium_domains (domain, reason, keyword, value_estimate, notes) VALUES
('business.com', 'keyword', 'business', 50000.00, 'Yüksek değerli iş kelimesi'),
('money.net', 'keyword', 'money', 25000.00, 'Finans kelimesi'),
('tech.org', 'keyword', 'tech', 30000.00, 'Teknoloji kelimesi'),
('abc.com', 'short', NULL, 100000.00, '3 harfli kısa domain'),
('123.com', 'numeric', NULL, 75000.00, 'Sayısal domain'),
('crypto.io', 'keyword', 'crypto', 40000.00, 'Kripto para kelimesi');

-- Trigger: Domain değişikliklerini history tablosuna kaydet
DELIMITER $$

CREATE TRIGGER domain_changes_trigger
AFTER UPDATE ON domain_checks
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status OR 
       OLD.expiry_date != NEW.expiry_date OR 
       OLD.registrar != NEW.registrar THEN
        
        INSERT INTO domain_history (
            domain, 
            old_status, 
            new_status, 
            old_expiry_date, 
            new_expiry_date, 
            old_registrar, 
            new_registrar
        ) VALUES (
            NEW.domain,
            OLD.status,
            NEW.status,
            OLD.expiry_date,
            NEW.expiry_date,
            OLD.registrar,
            NEW.registrar
        );
    END IF;
END$$

DELIMITER ;

-- View: Yakında düşecek premium domainler
CREATE VIEW expiring_premium_domains AS
SELECT 
    dc.domain,
    dc.status,
    dc.expiry_date,
    dc.drop_date,
    dc.registrar,
    dc.last_checked,
    pd.reason,
    pd.value_estimate,
    DATEDIFF(dc.drop_date, NOW()) as days_until_drop
FROM domain_checks dc
JOIN premium_domains pd ON dc.domain = pd.domain
WHERE dc.status = 'registered' 
AND dc.drop_date IS NOT NULL 
AND dc.drop_date > NOW()
AND pd.is_active = TRUE
ORDER BY dc.drop_date ASC;

-- View: Düşen premium domainler
CREATE VIEW dropped_premium_domains AS
SELECT 
    dc.domain,
    dc.status,
    dc.last_checked,
    pd.reason,
    pd.value_estimate,
    DATEDIFF(NOW(), dc.last_checked) as hours_since_drop
FROM domain_checks dc
JOIN premium_domains pd ON dc.domain = pd.domain
WHERE dc.status = 'available' 
AND pd.is_active = TRUE
ORDER BY dc.last_checked DESC;

-- Stored Procedure: Domain istatistikleri
DELIMITER $$

CREATE PROCEDURE GetDomainStats()
BEGIN
    SELECT 
        COUNT(*) as total_domains,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_domains,
        SUM(CASE WHEN status = 'registered' THEN 1 ELSE 0 END) as registered_domains,
        SUM(CASE WHEN is_premium = 1 THEN 1 ELSE 0 END) as premium_domains,
        SUM(CASE WHEN status = 'available' AND is_premium = 1 THEN 1 ELSE 0 END) as available_premium_domains,
        SUM(CASE WHEN drop_date IS NOT NULL AND drop_date > NOW() THEN 1 ELSE 0 END) as expiring_domains
    FROM domain_checks;
END$$

DELIMITER ;

-- Stored Procedure: Domain arama
DELIMITER $$

CREATE PROCEDURE SearchDomains(IN search_term VARCHAR(255))
BEGIN
    SELECT 
        domain,
        status,
        expiry_date,
        drop_date,
        registrar,
        is_premium,
        last_checked
    FROM domain_checks 
    WHERE domain LIKE CONCAT('%', search_term, '%')
    ORDER BY is_premium DESC, last_checked DESC
    LIMIT 100;
END$$

DELIMITER ;

-- Index optimizasyonları
CREATE INDEX idx_domain_status_premium ON domain_checks(domain, status, is_premium);
CREATE INDEX idx_drop_date_status ON domain_checks(drop_date, status);
CREATE INDEX idx_last_checked_status ON domain_checks(last_checked, status);

-- Veritabanı kullanıcısı oluştur (güvenlik için)
-- CREATE USER 'domain_checker'@'localhost' IDENTIFIED BY 'strong_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON domain_checker.* TO 'domain_checker'@'localhost';
-- FLUSH PRIVILEGES;