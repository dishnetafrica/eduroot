-- ============================================================
-- EduRoot SaaS — Master Database Schema
-- Database name: eduroot_master
-- Run this ONCE on your server before deploying SaaS mode.
--
-- This is YOUR database — schools register here, billing lives
-- here, subdomains resolve here. Each school then gets their
-- own completely separate database (eduroot_s0001, etc.)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `eduroot_master`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `eduroot_master`;

-- ─────────────────────────────────────────────────────────────
-- schools
-- One row per school. This is the tenant registry.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `schools` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`            VARCHAR(200)    NOT NULL,
    `subdomain`       VARCHAR(100)    NOT NULL  COMMENT 'greenvalley → greenvalley.eduroot.in',
    `email`           VARCHAR(200)    NOT NULL  COMMENT 'Primary admin email',
    `phone`           VARCHAR(20)     NULL,
    `address`         TEXT            NULL,
    `city`            VARCHAR(100)    NULL,
    `state`           VARCHAR(100)    NULL,
    `country`         VARCHAR(100)    NOT NULL DEFAULT 'India',
    `db_name`         VARCHAR(100)    NULL      COMMENT 'eduroot_s0001 — set after provisioning',
    `plan`            ENUM('trial','basic','pro','enterprise') NOT NULL DEFAULT 'trial',
    `status`          ENUM('pending','active','suspended','cancelled') NOT NULL DEFAULT 'pending',
    `trial_ends`      DATE            NULL,
    `plan_expires`    DATE            NULL,
    `max_students`    SMALLINT        NOT NULL DEFAULT 500,
    `admin_name`      VARCHAR(200)    NULL      COMMENT 'Principal/admin full name',
    `admin_password`  VARCHAR(255)    NULL      COMMENT 'bcrypt — for first login',
    `setup_token`     VARCHAR(64)     NULL      COMMENT 'One-time onboarding link token',
    `setup_token_exp` DATETIME        NULL,
    `approved_by`     INT             NULL      COMMENT 'superadmin users.id',
    `approved_at`     DATETIME        NULL,
    `rejected_reason` TEXT            NULL,
    `razorpay_sub_id` VARCHAR(100)    NULL      COMMENT 'Recurring billing subscription ID',
    `notes`           TEXT            NULL      COMMENT 'Internal notes from superadmin',
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_subdomain` (`subdomain`),
    KEY `idx_status`  (`status`),
    KEY `idx_plan`    (`plan`),
    KEY `idx_email`   (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tenant registry — one row per school';

-- ─────────────────────────────────────────────────────────────
-- superadmins
-- Your team — people who can approve, suspend, and manage schools.
-- Separate from the schools' own user tables.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `superadmins` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(200)    NOT NULL,
    `email`       VARCHAR(200)    NOT NULL,
    `password`    VARCHAR(255)    NOT NULL  COMMENT 'bcrypt',
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- billing_events
-- Log every payment, upgrade, suspension for audit.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `billing_events` (
    `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `school_id`    INT UNSIGNED    NOT NULL,
    `event_type`   ENUM('trial_start','plan_upgrade','payment_success',
                        'payment_failed','suspended','reactivated','cancelled') NOT NULL,
    `plan`         VARCHAR(50)     NULL,
    `amount`       DECIMAL(10,2)   NULL,
    `currency`     VARCHAR(5)      NOT NULL DEFAULT 'INR',
    `gateway_ref`  VARCHAR(200)    NULL    COMMENT 'Razorpay payment/subscription ID',
    `notes`        TEXT            NULL,
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_school` (`school_id`),
    KEY `idx_event`  (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- registration_requests
-- Self-registration queue — schools that need your approval.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `registration_requests` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `school_name`     VARCHAR(200)    NOT NULL,
    `subdomain`       VARCHAR(100)    NOT NULL,
    `admin_name`      VARCHAR(200)    NOT NULL,
    `email`           VARCHAR(200)    NOT NULL,
    `phone`           VARCHAR(20)     NULL,
    `city`            VARCHAR(100)    NULL,
    `state`           VARCHAR(100)    NULL,
    `student_count`   SMALLINT        NULL     COMMENT 'Approximate, self-reported',
    `message`         TEXT            NULL     COMMENT 'Why they want EduRoot',
    `status`          ENUM('new','approved','rejected') NOT NULL DEFAULT 'new',
    `school_id`       INT UNSIGNED    NULL     COMMENT 'Set when approved → school provisioned',
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_subdomain` (`subdomain`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- subdomain_cache
-- Fast lookup table: subdomain → db_name
-- Written when school is provisioned, read on every request.
-- Avoids a JOIN to `schools` on every page load.
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subdomain_cache` (
    `subdomain`   VARCHAR(100)    NOT NULL,
    `db_name`     VARCHAR(100)    NOT NULL,
    `school_id`   INT UNSIGNED    NOT NULL,
    `status`      TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '0 = suspended',
    PRIMARY KEY (`subdomain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Hot-path lookup: subdomain → which database to connect';

-- ─────────────────────────────────────────────────────────────
-- Seed: your superadmin account
-- Change email/password before deploying!
-- Password here = 'Admin@123' (bcrypt) — CHANGE IMMEDIATELY
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO `superadmins` (`name`, `email`, `password`) VALUES
('Super Admin', 'superadmin@eduroot.in',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Plans reference (for UI — not enforced in DB)
-- basic:      500 students,  ₹999/month
-- pro:       2000 students,  ₹2499/month
-- enterprise: unlimited,    ₹5999/month
