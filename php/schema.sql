-- Create the application database with a Unicode-safe default collation.
CREATE DATABASE IF NOT EXISTS volt_gym
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE volt_gym;

-- Store prospective member visit requests.
CREATE TABLE IF NOT EXISTS inquiries (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  email VARCHAR(254) NULL,
  phone VARCHAR(32) NULL,
  goal VARCHAR(100) NULL,
  message TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- Store member ratings and written feedback.
CREATE TABLE IF NOT EXISTS reviews (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(60) NOT NULL,
  email VARCHAR(254) NULL,
  phone VARCHAR(32) NULL,
  rating TINYINT UNSIGNED NOT NULL,
  message VARCHAR(600) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT reviews_rating_range CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- Store dashboard login names and password hashes.
CREATE TABLE IF NOT EXISTS admins (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(60) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY admins_username_unique (username)
) ENGINE=InnoDB;

-- Track whether each review or enquiry has been read in the dashboard.
CREATE TABLE IF NOT EXISTS admin_read_status (
  submission_type ENUM('review', 'inquiry') NOT NULL,
  submission_id BIGINT UNSIGNED NOT NULL,
  read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (submission_type, submission_id)
) ENGINE=InnoDB;
