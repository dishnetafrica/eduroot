<?php
// ============================================================
// FILE: application/config/hooks.php
//
// Add the TenantMiddleware hook. Keep any existing hooks.
// This runs before every controller — reads the subdomain
// and switches $CI->db to the correct school database.
// ============================================================

// Keep your existing maintenance hook:
$hook['pre_system'][] = [
    'class'    => 'maintenance_hook',
    'function' => 'offline_check',
    'filename' => 'maintenance_hook.php',
    'filepath' => 'hooks',
];

// ADD THIS — the multi-tenancy hook:
$hook['pre_controller'][] = [
    'class'    => 'TenantMiddleware',
    'function' => 'boot',
    'filename' => 'TenantMiddleware.php',
    'filepath' => 'libraries',
];


// ============================================================
// FILE: application/config/routes.php
//
// Add these lines ABOVE the existing routes.
// ============================================================

// ── Public registration (lives on bare domain: eduroot.in) ──
$route['register']              = 'register/index';
$route['register/submit']       = 'register/submit';
$route['register/check']        = 'register/checkSubdomain';
$route['register/thanks']       = 'register/thanks';

// ── Superadmin panel (lives on admin.eduroot.in) ────────────
$route['superadmin']                            = 'superadmin/dashboard';
$route['superadmin/login']                      = 'superadmin/login';
$route['superadmin/logout']                     = 'superadmin/logout';
$route['superadmin/registrations']              = 'superadmin/registrations';
$route['superadmin/approve/(:num)']             = 'superadmin/approve/$1';
$route['superadmin/reject/(:num)']              = 'superadmin/reject/$1';
$route['superadmin/schools']                    = 'superadmin/schools';
$route['superadmin/schools/create']             = 'superadmin/createSchool';
$route['superadmin/schools/suspend/(:num)']     = 'superadmin/suspend/$1';
$route['superadmin/schools/reactivate/(:num)']  = 'superadmin/reactivate/$1';

// ── API — also works per-school (TenantMiddleware switches DB first) ──
$route['api/v1/(:any)'] = 'api_v1/route/$1';
