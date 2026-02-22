-- ============================================================
-- Portfolio Contact Form Database
-- Run this once in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS portfolio_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE portfolio_db;

CREATE TABLE IF NOT EXISTS contact_messages (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(120)  NOT NULL,
    email        VARCHAR(180)  NOT NULL,
    subject      VARCHAR(255)  NOT NULL,
    message      TEXT          NOT NULL,
    ip_address   VARCHAR(45)   DEFAULT NULL,
    user_agent   VARCHAR(255)  DEFAULT NULL,
    submitted_at DATETIME      DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_submitted (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
