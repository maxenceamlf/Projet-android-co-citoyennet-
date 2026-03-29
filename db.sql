-- ============================================================
-- PowerHome - Base de données
-- Résidence participative : gestion des habitats et résidents
-- ============================================================

CREATE DATABASE IF NOT EXISTS powerhome
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE powerhome;

-- ----------------------------------------------------------
-- Table : users (résidents)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    firstname   VARCHAR(100) NOT NULL,
    lastname    VARCHAR(100) NOT NULL,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- hash bcrypt
    phone       VARCHAR(30)  DEFAULT NULL,
    habitat_id  INT          DEFAULT NULL,      -- habitat assigné
    token       VARCHAR(128) DEFAULT NULL,      -- token de session
    eco_coins   INT          DEFAULT 0,         -- monnaie locale
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- Table : habitats (logements dans la résidence)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS habitats (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(100) NOT NULL,   -- ex : "Appartement A101"
    floor   INT NOT NULL DEFAULT 0,
    area    DECIMAL(6,2) NOT NULL    -- surface en m²
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- Table : appliances (équipements électroménagers)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS appliances (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100) NOT NULL,
    wattage   INT NOT NULL,           -- consommation en watts
    icon_name VARCHAR(100) DEFAULT 'ic_default'  -- nom de la ressource drawable Android
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- Table : habitat_appliances (lien habitat ↔ équipements)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS habitat_appliances (
    habitat_id   INT NOT NULL,
    appliance_id INT NOT NULL,
    PRIMARY KEY (habitat_id, appliance_id),
    FOREIGN KEY (habitat_id)   REFERENCES habitats(id)   ON DELETE CASCADE,
    FOREIGN KEY (appliance_id) REFERENCES appliances(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------
-- Données initiales : équipements
-- ----------------------------------------------------------
INSERT INTO appliances (name, wattage, icon_name) VALUES
    ('Lave-linge',       2500, 'ic_laundry'),
    ('Réfrigérateur',    300,  'ic_fridge'),
    ('Robinet électrique',100, 'ic_tap'),
    ('Fer à repasser',   1800, 'ic_iron'),
    ('Télévision',       150,  'ic_default'),
    ('Radiateur',        2000, 'ic_default'),
    ('Micro-ondes',      900,  'ic_default');

-- ----------------------------------------------------------
-- Données initiales : habitats
-- ----------------------------------------------------------
INSERT INTO habitats (name, floor, area) VALUES
    ('Appartement A101', 1, 45.5),
    ('Appartement A102', 1, 32.0),
    ('Appartement B201', 2, 50.0),
    ('Appartement B301', 3, 65.0),
    ('Appartement C101', 0, 28.5);

-- ----------------------------------------------------------
-- Lien habitats ↔ équipements
-- ----------------------------------------------------------
INSERT INTO habitat_appliances (habitat_id, appliance_id) VALUES
    (1, 1), (1, 2), (1, 3), (1, 4),   -- A101 : lave-linge, frigo, robinet, fer
    (2, 1),                             -- A102 : lave-linge
    (3, 4), (3, 3),                     -- B201 : fer, robinet
    (4, 1), (4, 4), (4, 3),            -- B301 : lave-linge, fer, robinet
    (5, 3);                             -- C101 : robinet

-- ----------------------------------------------------------
-- Données initiales : utilisateurs de test
-- Mot de passe "password123" hashé avec bcrypt PHP
-- Pour régénérer : echo password_hash('password123', PASSWORD_BCRYPT);
-- ----------------------------------------------------------
INSERT INTO users (firstname, lastname, email, password, phone, habitat_id) VALUES
    ('Alice',   'Martin',    'alice@powerhome.fr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+33612345678', 1),
    ('Bob',     'Dupont',    'bob@powerhome.fr',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+33698765432', 2),
    ('Camille', 'Lefebvre',  'camille@powerhome.fr','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL,           3);

-- Compte de démo rapide : demo@powerhome.fr / demo1234
-- (hash de "demo1234")
INSERT INTO users (firstname, lastname, email, password, habitat_id) VALUES
    ('Démo', 'Utilisateur', 'demo@powerhome.fr', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LPVd9.47Hm2', 1);
