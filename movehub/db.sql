-- MoveHub database schema (MySQL)
CREATE DATABASE IF NOT EXISTS movehub CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE movehub;

-- Users: roles = visitor(implicit), client, mover, admin
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('client','mover','admin') NOT NULL DEFAULT 'client',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Annonces (moving requests)
CREATE TABLE IF NOT EXISTS annonces (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  start_datetime DATETIME NOT NULL,
  city_from VARCHAR(120) NOT NULL,
  city_to VARCHAR(120) NOT NULL,
  from_type ENUM('house','apartment') NOT NULL,
  from_floor INT NULL,
  from_elevator TINYINT(1) NOT NULL DEFAULT 0,
  to_type ENUM('house','apartment') NOT NULL,
  to_floor INT NULL,
  to_elevator TINYINT(1) NOT NULL DEFAULT 0,
  total_volume_m3 DECIMAL(8,2) NULL,
  total_weight_kg INT NULL,
  movers_needed INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Images for annonces
CREATE TABLE IF NOT EXISTS annonce_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annonce_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE CASCADE
);

-- Offers from movers
CREATE TABLE IF NOT EXISTS offers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annonce_id INT UNSIGNED NOT NULL,
  mover_id INT UNSIGNED NOT NULL,
  price_cents INT UNSIGNED NOT NULL,
  message VARCHAR(500) NULL,
  status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_offer (annonce_id, mover_id),
  FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE CASCADE,
  FOREIGN KEY (mover_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Optional Q&A between mover and client (bonus)
CREATE TABLE IF NOT EXISTS questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annonce_id INT UNSIGNED NOT NULL,
  mover_id INT UNSIGNED NOT NULL,
  question TEXT NOT NULL,
  answer TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  answered_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE CASCADE,
  FOREIGN KEY (mover_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Optional ratings after move (bonus)
CREATE TABLE IF NOT EXISTS ratings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annonce_id INT UNSIGNED NOT NULL,
  client_id INT UNSIGNED NOT NULL,
  mover_id INT UNSIGNED NOT NULL,
  score TINYINT UNSIGNED NOT NULL CHECK (score BETWEEN 1 AND 5),
  comment VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_rating (annonce_id, client_id, mover_id),
  FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE CASCADE,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (mover_id) REFERENCES users(id) ON DELETE CASCADE
);


