<?php
// ============================================================
// Add these blocks to your existing config files.
// ============================================================

// ─────────────────────────────────────────────────────────────
// FILE: application/config/database.php
//
// Add the 'master' group BELOW your existing 'default' group.
// The default group is overridden per-request by TenantMiddleware.
// The master group stays permanently connected to eduroot_master.
// ─────────────────────────────────────────────────────────────

$db['master'] = [
    'dsn'          => '',
    'hostname'     => 'localhost',
    'port'         => '',
    'username'     => 'YOUR_MYSQL_USER',      // ← change
    'password'     => 'YOUR_MYSQL_PASSWORD',  // ← change
    'database'     => 'eduroot_master',
    'dbdriver'     => 'mysqli',
    'dbprefix'     => '',
    'pconnect'     => false,
    'db_debug'     => (ENVIRONMENT !== 'production'),
    'cache_on'     => false,
    'cachedir'     => '',
    'char_set'     => 'utf8mb4',
    'dbcollat'     => 'utf8mb4_unicode_ci',
    'swap_pre'     => '',
    'encrypt'      => false,
    'compress'     => false,
    'stricton'     => false,
    'failover'     => [],
    'save_queries' => true,
];

// ─────────────────────────────────────────────────────────────
// FILE: application/config/config.php
//
// Add at the bottom of the file.
// ─────────────────────────────────────────────────────────────

// ── SaaS / Multi-tenancy settings ──────────────────────────
$config['saas_mode']             = true;
$config['master_db_name']        = 'eduroot_master';
$config['base_domain']           = 'eduroot.in';          // no leading dot
$config['superadmin_subdomain']  = 'admin';               // admin.eduroot.in

// MySQL superuser — needs CREATE DATABASE / CREATE USER privileges
// Create a dedicated MySQL user for this (not your app user):
//   CREATE USER 'eduroot_admin'@'localhost' IDENTIFIED BY 'strong_password';
//   GRANT ALL PRIVILEGES ON `eduroot_%`.* TO 'eduroot_admin'@'localhost';
$config['db_host']               = 'localhost';
$config['db_port']               = 3306;
$config['db_super_user']         = 'eduroot_admin';       // ← change
$config['db_super_pass']         = 'YOUR_STRONG_PASSWORD'; // ← change

// Trial duration for new schools
$config['trial_days']            = 30;
