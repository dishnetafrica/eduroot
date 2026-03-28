<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SchoolProvisioner
 *
 * Creates a new school database, runs the schema, seeds the
 * first admin account, and registers the school in the master DB.
 *
 * Called from:
 *   - SuperAdmin_Controller::approveSchool()  (you approve manually)
 *   - Superadmin_Controller::createSchool()   (you create directly)
 *
 * The entire provisioning sequence takes < 5 seconds.
 *
 * Usage:
 *   $this->load->library('SchoolProvisioner');
 *   $result = $this->schoolprovisioner->provision([
 *       'name'        => 'Green Valley School',
 *       'subdomain'   => 'greenvalley',
 *       'email'       => 'principal@greenvalley.edu',
 *       'admin_name'  => 'Ramesh Patel',
 *       'city'        => 'Ahmedabad',
 *       'state'       => 'Gujarat',
 *       'plan'        => 'trial',
 *   ]);
 *   // Returns: ['success' => true, 'school_id' => 3, 'url' => '...', 'login_email' => '...', 'temp_password' => '...']
 */
class SchoolProvisioner
{
    /** @var CI_Controller */
    private $CI;

    /** Master DB connection (kept separate from the school DB being created) */
    private $masterDb;

    /** MySQL host, port, superuser credentials for CREATE DATABASE */
    private $dbHost;
    private $dbPort;
    private $dbSuperUser;
    private $dbSuperPass;

    /** Path to the school schema SQL template */
    private $schemaPath;

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->dbHost      = $this->CI->config->item('db_host')       ?? 'localhost';
        $this->dbPort      = $this->CI->config->item('db_port')       ?? 3306;
        $this->dbSuperUser = $this->CI->config->item('db_super_user') ?? 'root';
        $this->dbSuperPass = $this->CI->config->item('db_super_pass') ?? '';
        $this->schemaPath  = APPPATH . 'sql/school_schema.sql';

        // Keep a dedicated connection to the master DB
        // so we don't lose it when we switch to the school DB
        $this->masterDb = $this->CI->load->database('master', true);
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Provision a complete new school in under 5 seconds.
     *
     * @param array $data  See parameter list above
     * @return array  ['success', 'school_id', 'url', 'login_email', 'temp_password', 'error']
     */
    public function provision(array $data): array
    {
        try {
            // 1. Validate subdomain
            $subdomain = $this->_sanitiseSubdomain($data['subdomain'] ?? '');
            if (!$subdomain) {
                return ['success' => false, 'error' => 'Invalid subdomain'];
            }
            if ($this->_subdomainExists($subdomain)) {
                return ['success' => false, 'error' => "Subdomain '{$subdomain}' is already taken"];
            }

            // 2. Generate DB name + temp password
            $db_name   = $this->_generateDbName();
            $temp_pass = $this->_generateTempPassword();
            $hash      = password_hash($temp_pass, PASSWORD_BCRYPT);

            // 3. Create the MySQL database
            $this->_createDatabase($db_name);

            // 4. Run schema
            $this->_runSchema($db_name);

            // 5. Seed: sch_settings, first session, first admin user
            $this->_seedSchool($db_name, $data, $hash);

            // 6. Register in master DB
            $school_id = $this->_registerInMaster($data, $subdomain, $db_name, $hash);

            // 7. Update subdomain_cache for fast middleware lookup
            $this->_updateCache($subdomain, $db_name, $school_id);

            $base_domain = $this->CI->config->item('base_domain') ?? 'eduroot.in';

            return [
                'success'       => true,
                'school_id'     => $school_id,
                'url'           => "https://{$subdomain}.{$base_domain}",
                'login_email'   => $data['email'],
                'temp_password' => $temp_pass,
                'db_name'       => $db_name,
            ];

        } catch (\Exception $e) {
            log_message('error', 'SchoolProvisioner::provision — ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Suspend a school (blocks login, keeps data intact).
     */
    public function suspend(int $school_id, string $reason = ''): bool
    {
        $this->masterDb->where('id', $school_id);
        $this->masterDb->update('schools', ['status' => 'suspended']);

        $this->masterDb->where('school_id', $school_id);
        $this->masterDb->update('subdomain_cache', ['status' => 0]);

        $this->masterDb->insert('billing_events', [
            'school_id'  => $school_id,
            'event_type' => 'suspended',
            'notes'      => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Reactivate a suspended school.
     */
    public function reactivate(int $school_id): bool
    {
        $this->masterDb->where('id', $school_id);
        $this->masterDb->update('schools', ['status' => 'active']);

        $this->masterDb->where('school_id', $school_id);
        $this->masterDb->update('subdomain_cache', ['status' => 1]);

        $this->masterDb->insert('billing_events', [
            'school_id'  => $school_id,
            'event_type' => 'reactivated',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Extend trial by N days.
     */
    public function extendTrial(int $school_id, int $days = 30): bool
    {
        $school = $this->masterDb->get_where('schools', ['id' => $school_id])->row();
        if (!$school) { return false; }

        $new_end = date('Y-m-d', strtotime(($school->trial_ends ?? 'today') . " +{$days} days"));
        $this->masterDb->where('id', $school_id);
        return $this->masterDb->update('schools', ['trial_ends' => $new_end, 'status' => 'active']);
    }

    // -------------------------------------------------------------------------
    // Step-by-step provisioning internals
    // -------------------------------------------------------------------------

    /**
     * Step 3: CREATE DATABASE on MySQL.
     * Uses a raw MySQLi connection with the super-user credentials.
     */
    private function _createDatabase(string $db_name): void
    {
        $mysqli = new \mysqli($this->dbHost, $this->dbSuperUser, $this->dbSuperPass, '', $this->dbPort);

        if ($mysqli->connect_error) {
            throw new \RuntimeException("MySQL connect failed: " . $mysqli->connect_error);
        }

        $safe = $mysqli->real_escape_string($db_name);
        $mysqli->query("CREATE DATABASE `{$safe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        if ($mysqli->error) {
            throw new \RuntimeException("CREATE DATABASE failed: " . $mysqli->error);
        }

        $mysqli->close();
        log_message('info', "SchoolProvisioner: Created database [{$db_name}]");
    }

    /**
     * Step 4: Run the school schema SQL against the new database.
     *
     * The schema file is at: application/sql/school_schema.sql
     * Generate it with:  mysqldump --no-data eduroot_template > school_schema.sql
     */
    private function _runSchema(string $db_name): void
    {
        if (!file_exists($this->schemaPath)) {
            throw new \RuntimeException("Schema file not found: {$this->schemaPath}");
        }

        $sql = file_get_contents($this->schemaPath);
        if (!$sql) {
            throw new \RuntimeException("Schema file is empty");
        }

        $mysqli = new \mysqli($this->dbHost, $this->dbSuperUser, $this->dbSuperPass, $db_name, $this->dbPort);
        if ($mysqli->connect_error) {
            throw new \RuntimeException("MySQL connect to [{$db_name}] failed: " . $mysqli->connect_error);
        }

        $mysqli->multi_query($sql);

        // Drain all result sets — multi_query requires this
        do {
            if ($result = $mysqli->store_result()) { $result->free(); }
        } while ($mysqli->next_result());

        if ($mysqli->error) {
            throw new \RuntimeException("Schema import failed: " . $mysqli->error);
        }

        $mysqli->close();
        log_message('info', "SchoolProvisioner: Schema applied to [{$db_name}]");
    }

    /**
     * Step 5: Seed the new school database with:
     *   - sch_settings row (school profile)
     *   - sessions row    (current academic year)
     *   - users row       (first admin account)
     *   - staff row       (linked to admin user)
     *
     * After this, the school admin can log in immediately.
     */
    private function _seedSchool(string $db_name, array $data, string $password_hash): void
    {
        $mysqli = new \mysqli($this->dbHost, $this->dbSuperUser, $this->dbSuperPass, $db_name, $this->dbPort);
        if ($mysqli->connect_error) {
            throw new \RuntimeException("Seed connect failed: " . $mysqli->connect_error);
        }

        $name       = $mysqli->real_escape_string($data['name']       ?? 'New School');
        $email      = $mysqli->real_escape_string($data['email']      ?? '');
        $phone      = $mysqli->real_escape_string($data['phone']      ?? '');
        $city       = $mysqli->real_escape_string($data['city']       ?? '');
        $state      = $mysqli->real_escape_string($data['state']      ?? '');
        $admin_name = $mysqli->real_escape_string($data['admin_name'] ?? 'School Admin');
        $hash       = $mysqli->real_escape_string($password_hash);

        // Current academic year: e.g. "2025-26"
        $year        = (int) date('Y');
        $month       = (int) date('n');
        $session_str = $month >= 4
            ? "{$year}-" . ($year + 1 - 2000)
            : ($year - 1) . "-" . ($year - 2000);

        $seed_sql = "
            -- 1. First academic session
            INSERT INTO `sessions` (`session`, `is_active`) VALUES ('{$session_str}', 1);
            SET @session_id = LAST_INSERT_ID();

            -- 2. School settings (sch_settings)
            INSERT INTO `sch_settings`
                (`name`, `email`, `phone`, `address`, `session_id`, `date_format`,
                 `currency`, `lang_id`, `theme`, `adm_prefix`, `adm_start_from`)
            VALUES
                ('{$name}', '{$email}', '{$phone}', '{$city}, {$state}',
                 @session_id, 'd/m/Y', 1, 1, 'default', 'ADM', 1);

            -- 3. Admin user
            INSERT INTO `users` (`username`, `password`, `role`, `is_active`)
            VALUES ('{$email}', '{$hash}', 'admin', 1);
            SET @user_id = LAST_INSERT_ID();

            -- 4. Staff record linked to the admin user
            INSERT INTO `staff` (`name`, `email`, `is_active`)
            VALUES ('{$admin_name}', '{$email}', 'yes');
            SET @staff_id = LAST_INSERT_ID();

            -- 5. Link user to staff
            UPDATE `users` SET `user_id` = @staff_id WHERE `id` = @user_id;

            -- 6. Assign admin role
            INSERT INTO `staff_roles` (`staff_id`, `role_id`) VALUES (@staff_id, 1);

            -- 7. Default roles
            INSERT IGNORE INTO `roles` (`id`, `name`, `is_active`) VALUES
                (1, 'Admin', 1), (2, 'Teacher', 1), (3, 'Accountant', 1),
                (4, 'Librarian', 1), (5, 'Receptionist', 1);
        ";

        $mysqli->multi_query($seed_sql);
        do {
            if ($result = $mysqli->store_result()) { $result->free(); }
        } while ($mysqli->next_result());

        $mysqli->close();
        log_message('info', "SchoolProvisioner: Seeded school [{$name}] in [{$db_name}]");
    }

    /**
     * Step 6: Write the school record to eduroot_master.schools
     */
    private function _registerInMaster(array $data, string $subdomain, string $db_name, string $hash): int
    {
        $trial_days = $this->CI->config->item('trial_days') ?? 30;

        $this->masterDb->insert('schools', [
            'name'          => $data['name']       ?? 'New School',
            'subdomain'     => $subdomain,
            'email'         => $data['email']      ?? '',
            'phone'         => $data['phone']      ?? '',
            'city'          => $data['city']       ?? '',
            'state'         => $data['state']      ?? '',
            'admin_name'    => $data['admin_name'] ?? 'Admin',
            'admin_password'=> $hash,
            'db_name'       => $db_name,
            'plan'          => $data['plan']       ?? 'trial',
            'status'        => 'active',
            'trial_ends'    => date('Y-m-d', strtotime("+{$trial_days} days")),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $school_id = (int) $this->masterDb->insert_id();

        $this->masterDb->insert('billing_events', [
            'school_id'  => $school_id,
            'event_type' => 'trial_start',
            'notes'      => "Trial started. Expires in {$trial_days} days.",
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $school_id;
    }

    /**
     * Step 7: Warm the subdomain_cache so the middleware finds it instantly.
     */
    private function _updateCache(string $subdomain, string $db_name, int $school_id): void
    {
        $this->masterDb->replace('subdomain_cache', [
            'subdomain' => $subdomain,
            'db_name'   => $db_name,
            'school_id' => $school_id,
            'status'    => 1,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function _generateDbName(): string
    {
        $row = $this->masterDb->select_max('id')->get('schools')->row();
        $next = ((int) ($row->id ?? 0)) + 1;
        return 'eduroot_s' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function _generateTempPassword(): string
    {
        // Format: Edu + 4 random digits + special char
        // Easy enough to communicate verbally, secure enough for first login
        return 'Edu@' . rand(1000, 9999);
    }

    private function _sanitiseSubdomain(string $input): string
    {
        // Lowercase, letters/numbers/hyphens only, max 50 chars, no reserved words
        $clean = strtolower(preg_replace('/[^a-z0-9\-]/', '', $input));
        $clean = trim($clean, '-');
        $clean = substr($clean, 0, 50);

        $reserved = ['www','admin','api','static','cdn','mail','smtp',
                     'support','billing','app','login','register','eduroot'];
        if (in_array($clean, $reserved, true)) { return ''; }

        return strlen($clean) >= 3 ? $clean : '';
    }

    private function _subdomainExists(string $subdomain): bool
    {
        $row = $this->masterDb->get_where('subdomain_cache', ['subdomain' => $subdomain])->row();
        return (bool) $row;
    }
}
