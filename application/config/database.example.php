<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| Copy this file to database.php and fill in your credentials.
| NEVER commit database.php to version control.
*/

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
    'dsn'          => '',
    'hostname'     => 'localhost',
    'username'     => 'your_db_username',
    'password'     => 'your_db_password',
    'database'     => 'eduroot',
    'dbdriver'     => 'mysqli',
    'dbprefix'     => '',
    'pconnect'     => FALSE,
    'db_debug'     => (ENVIRONMENT !== 'production'),
    'cache_on'     => FALSE,
    'cachedir'     => '',
    'char_set'     => 'utf8mb4',
    'dbcollat'     => 'utf8mb4_unicode_ci',
    'swap_pre'     => '',
    'encrypt'      => FALSE,
    'compress'     => FALSE,
    'stricton'     => FALSE,
    'failover'     => array(),
    'save_queries' => TRUE
);
