<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * TenantMiddleware
 *
 * Runs as a CI pre_controller hook on EVERY request.
 * Reads the subdomain → looks up the school's database name
 * → switches $CI->db to that school's database.
 *
 * After this hook runs, every model query automatically hits
 * the correct school's database. Zero changes needed to
 * existing models, controllers, or views.
 *
 * Register in application/config/hooks.php:
 *
 *   $hook['pre_controller'][] = [
 *       'class'    => 'TenantMiddleware',
 *       'function' => 'boot',
 *       'filename' => 'TenantMiddleware.php',
 *       'filepath' => 'libraries',
 *   ];
 *
 * Required constants in application/config/config.php:
 *   $config['saas_mode']        = true;
 *   $config['master_db_name']   = 'eduroot_master';
 *   $config['base_domain']      = 'eduroot.in';  // no leading dot
 *   $config['superadmin_subdomain'] = 'admin';   // admin.eduroot.in → your panel
 */
class TenantMiddleware
{
    /** @var CI_Controller */
    private $CI;

    /** Subdomains that belong to your infrastructure — not school tenants */
    private $reserved = ['www', 'admin', 'api', 'static', 'cdn', 'mail', 'smtp'];

    public function boot(): void
    {
        $this->CI =& get_instance();

        // Only run in SaaS mode
        if (!$this->CI->config->item('saas_mode')) {
            return;
        }

        // Extract subdomain from HTTP_HOST
        // greenvalley.eduroot.in → 'greenvalley'
        $host      = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $subdomain = $this->_extractSubdomain($host);

        // No subdomain = bare domain (eduroot.in) → show marketing/registration page
        if (!$subdomain) {
            return;
        }

        // Reserved subdomain = your own panel — don't switch DB
        if (in_array($subdomain, $this->reserved, true)) {
            $this->_loadMasterDb();
            define('IS_SUPERADMIN_PANEL', true);
            return;
        }

        // ── Fast path: check subdomain_cache first ──────────────────────────
        // The cache table avoids a JOIN to the schools table on every request.
        $this->_loadMasterDb();

        $cached = $this->CI->db->get_where('subdomain_cache', ['subdomain' => $subdomain])->row();

        if (!$cached) {
            // Not in cache — school doesn't exist or was never provisioned
            $this->_showSchoolNotFound($subdomain);
            return;
        }

        if ($cached->status == 0) {
            // School suspended
            $this->_showSuspended($subdomain);
            return;
        }

        // ── Switch to school database ────────────────────────────────────────
        $switched = $this->_switchToSchoolDb(
            $cached->db_name,
            $cached->school_id,
            $subdomain
        );

        if (!$switched) {
            log_message('error', "TenantMiddleware: Failed to connect to DB [{$cached->db_name}] for subdomain [{$subdomain}]");
            show_error('School database unavailable. Please contact support.', 503);
        }
    }

    // -------------------------------------------------------------------------
    // Private methods
    // -------------------------------------------------------------------------

    /**
     * Connect $CI->db to the master database.
     * Uses a second DB group 'master' defined in database.php.
     */
    private function _loadMasterDb(): void
    {
        // If already on master DB, skip
        if ($this->CI->db->database === $this->CI->config->item('master_db_name')) {
            return;
        }

        // Load the 'master' connection group from database.php
        // database.php must have: $db['master'] = [..., 'database' => 'eduroot_master']
        $this->CI->load->database('master', false, true);
    }

    /**
     * Switch $CI->db to the given school database.
     * Returns true on success, false on failure.
     *
     * @param string $db_name    e.g. 'eduroot_s0001'
     * @param int    $school_id
     * @param string $subdomain
     */
    private function _switchToSchoolDb(string $db_name, int $school_id, string $subdomain): bool
    {
        try {
            // Close the master connection and open the school's DB
            // CI3: we reload with a custom DSN
            $master_config = $this->CI->db->conn_id
                ? ['hostname' => $this->CI->db->hostname,
                   'username' => $this->CI->db->username,
                   'password' => $this->CI->db->password,
                   'database' => $db_name,
                   'dbdriver' => 'mysqli',
                   'char_set' => 'utf8mb4',
                   'dbcollat' => 'utf8mb4_unicode_ci',
                   'db_debug' => (ENVIRONMENT !== 'production')]
                : null;

            if ($master_config) {
                // Re-use same MySQL server credentials, just switch database
                $this->CI->db->close();
                $this->CI->db->database = $db_name;
                $this->CI->db->initialize();

                // Verify connection
                if (!$this->CI->db->conn_id) {
                    return false;
                }
            }

            // Inject school context constants for use in hooks/views
            if (!defined('CURRENT_SCHOOL_ID'))   { define('CURRENT_SCHOOL_ID',   $school_id); }
            if (!defined('CURRENT_SUBDOMAIN'))    { define('CURRENT_SUBDOMAIN',   $subdomain); }
            if (!defined('CURRENT_DB_NAME'))      { define('CURRENT_DB_NAME',     $db_name); }
            if (!defined('IS_SUPERADMIN_PANEL'))  { define('IS_SUPERADMIN_PANEL', false); }

            return true;

        } catch (\Exception $e) {
            log_message('error', 'TenantMiddleware::_switchToSchoolDb — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract subdomain from hostname.
     *
     * greenvalley.eduroot.in  → 'greenvalley'
     * eduroot.in              → null
     * localhost               → null
     * 127.0.0.1               → null
     *
     * @param string $host  Full HTTP_HOST value
     * @return string|null
     */
    private function _extractSubdomain(string $host): ?string
    {
        $base_domain = $this->CI->config->item('base_domain') ?? 'eduroot.in';

        // Strip port if present
        $host = preg_replace('/:\d+$/', '', $host);

        // Local/IP development — no subdomain concept
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        // Must end with base domain
        if (!str_ends_with($host, '.' . $base_domain) && $host !== $base_domain) {
            return null;
        }

        // Bare domain — no subdomain
        if ($host === $base_domain) {
            return null;
        }

        // Extract everything before .base_domain
        $prefix = substr($host, 0, strlen($host) - strlen('.' . $base_domain));

        // Reject if it contains dots (deep subdomains like a.b.eduroot.in)
        if (strpos($prefix, '.') !== false) {
            return null;
        }

        return $prefix;
    }

    private function _showSchoolNotFound(string $subdomain): void
    {
        http_response_code(404);
        // In production replace with a nice HTML page
        echo "<!DOCTYPE html><html><head><title>School Not Found</title></head><body style='font-family:sans-serif;max-width:500px;margin:100px auto;text-align:center'>";
        echo "<h2>School not found</h2>";
        echo "<p><strong>{$subdomain}.eduroot.in</strong> does not exist.</p>";
        echo "<p><a href='https://eduroot.in/register'>Register your school &rarr;</a></p>";
        echo "</body></html>";
        exit;
    }

    private function _showSuspended(string $subdomain): void
    {
        http_response_code(402);
        echo "<!DOCTYPE html><html><head><title>Account Suspended</title></head><body style='font-family:sans-serif;max-width:500px;margin:100px auto;text-align:center'>";
        echo "<h2>Account Suspended</h2>";
        echo "<p>Your EduRoot account has been suspended.</p>";
        echo "<p>Please contact <a href='mailto:support@eduroot.in'>support@eduroot.in</a></p>";
        echo "</body></html>";
        exit;
    }
}
