-- ─────────────────────────────────────────────────
--  VØID Studio — Database Schema
--  Run this file once to set up your database
-- ─────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS void_studio
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE void_studio;

-- Contact form submissions
CREATE TABLE IF NOT EXISTS contact_submissions (
  id           INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(150)    NOT NULL,
  email        VARCHAR(255)    NOT NULL,
  project_type VARCHAR(200)    DEFAULT NULL,
  message      TEXT            NOT NULL,
  ip_address   VARCHAR(45)     DEFAULT NULL,
  user_agent   VARCHAR(500)    DEFAULT NULL,
  status       ENUM('new','read','replied','archived') DEFAULT 'new',
  created_at   DATETIME        DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_email      (email),
  INDEX idx_status     (status),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin users (for the simple admin panel)
CREATE TABLE IF NOT EXISTS admin_users (
  id           INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(80)     NOT NULL UNIQUE,
  password     VARCHAR(255)    NOT NULL,    -- bcrypt hash
  email        VARCHAR(255)    NOT NULL,
  last_login   DATETIME        DEFAULT NULL,
  created_at   DATETIME        DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Default admin user ─────────────────────────
-- Username: admin | Password: Admin@1234
-- CHANGE THIS PASSWORD IMMEDIATELY AFTER FIRST LOGIN
INSERT INTO admin_users (username, password, email) VALUES
(
  'admin',
  '$2y$12$KIXQv7T6C3Vz0w5V3D2GcOH8FZq9mJ6N1R5sYpXnMvWeLdTuA8QoK',
  'admin@void.studio'
);