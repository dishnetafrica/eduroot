<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MigrationRunner
 *
 * Applies schema changes to every school database in one command.
 * This is the solution to the "N databases = N migrations" problem.
 *
 * Usage — run from CLI:
 *   php index.php tools run_migration --sql="ALTER TABLE students ADD COLUMN photo_url VARCHAR(255) NULL"
 *   php index.php tools run_migration --file=migrations/044_add_photo_url.sql
 *
 * Or call from a controller:
 *   $this->load->library('MigrationRunner');
 *   $result = $this->migrationrunner->run("ALTER TABLE students ADD COLUMN photo_url VARCHAR(255)");
 *   echo $result['success'] . '/' . $result['total'] . ' schools updated';
 */
class MigrationRunner
{
    private $CI;
    private $masterDb;
    private $dbHost;
    private $dbPort;
    private $dbSuperUser;
    private $dbSuperPass;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->masterDb    = $this->CI->load->database('master', true);
        $this->dbHost      = $this->CI->config->item('db_host')       ?? 'localhost';
        $this->dbPort      = $this->CI->config->item('db_port')       ?? 3306;
        $this->dbSuperUser = $this->CI->config->item('db_super_user') ?? 'root';
        $this->dbSuperPass = $this->CI->config->item('db_super_pass') ?? '';
    }

    /**
     * Run a SQL string against all active school databases.
     *
     * @param string $sql       Single SQL statement OR semicolon-separated multi-statement
     * @param bool   $dry_run   If true, just reports what would run — does not execute
     * @return array ['total', 'success', 'failed', 'errors']
     */
    public function run(string $sql, bool $dry_run = false): array
    {
        $schools = $this->masterDb
            ->select('id, name, subdomain, db_name')
            ->where('status', 'active')
            ->where('db_name IS NOT NULL')
            ->get('schools')
            ->result();

        $total   = count($schools);
        $success = 0;
        $failed  = 0;
        $errors  = [];

        echo "Migration target: {$total} schools" . ($dry_run ? ' [DRY RUN]' : '') . PHP_EOL;
        echo str_repeat('-', 60) . PHP_EOL;

        foreach ($schools as $school) {
            if ($dry_run) {
                echo "[DRY] {$school->subdomain} ({$school->db_name})" . PHP_EOL;
                $success++;
                continue;
            }

            try {
                $mysqli = new \mysqli(
                    $this->dbHost, $this->dbSuperUser,
                    $this->dbSuperPass, $school->db_name, $this->dbPort
                );

                if ($mysqli->connect_error) {
                    throw new \RuntimeException("Connect failed: " . $mysqli->connect_error);
                }

                $mysqli->multi_query($sql);
                do {
                    if ($result = $mysqli->store_result()) { $result->free(); }
                } while ($mysqli->next_result());

                if ($mysqli->error) {
                    throw new \RuntimeException("Query failed: " . $mysqli->error);
                }

                $mysqli->close();
                $success++;
                echo "[OK]  {$school->subdomain} ({$school->db_name})" . PHP_EOL;

            } catch (\Exception $e) {
                $failed++;
                $msg = "{$school->subdomain}: " . $e->getMessage();
                $errors[] = $msg;
                echo "[ERR] {$msg}" . PHP_EOL;
                log_message('error', "MigrationRunner: {$msg}");
            }
        }

        echo str_repeat('-', 60) . PHP_EOL;
        echo "Done: {$success}/{$total} succeeded, {$failed} failed." . PHP_EOL;

        // Log migration in master DB
        if (!$dry_run) {
            $this->masterDb->insert('migration_log', [
                'sql_preview' => substr($sql, 0, 500),
                'total'       => $total,
                'success'     => $success,
                'failed'      => $failed,
                'errors'      => $failed ? json_encode($errors) : null,
                'run_by'      => $this->CI->session->userdata('superadmin_name') ?? 'cli',
                'run_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        return compact('total', 'success', 'failed', 'errors');
    }

    /**
     * Run a .sql file against all school databases.
     *
     * @param string $file_path  Full path to the .sql file
     * @param bool   $dry_run
     */
    public function runFile(string $file_path, bool $dry_run = false): array
    {
        if (!file_exists($file_path)) {
            return ['total' => 0, 'success' => 0, 'failed' => 1,
                    'errors' => ["File not found: {$file_path}"]];
        }
        $sql = file_get_contents($file_path);
        return $this->run($sql, $dry_run);
    }

    /**
     * Run migration on a single school by subdomain.
     * Useful for testing before rolling out to all schools.
     */
    public function runOnSchool(string $subdomain, string $sql): array
    {
        $school = $this->masterDb
            ->get_where('schools', ['subdomain' => $subdomain])
            ->row();

        if (!$school || !$school->db_name) {
            return ['success' => false, 'error' => 'School not found'];
        }

        try {
            $mysqli = new \mysqli(
                $this->dbHost, $this->dbSuperUser,
                $this->dbSuperPass, $school->db_name, $this->dbPort
            );
            $mysqli->multi_query($sql);
            do { if ($r = $mysqli->store_result()) $r->free(); } while ($mysqli->next_result());
            if ($mysqli->error) { throw new \RuntimeException($mysqli->error); }
            $mysqli->close();
            return ['success' => true, 'school' => $subdomain, 'db' => $school->db_name];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}


// ============================================================
// CLI Tools Controller (add this to application/controllers/)
// Usage: php index.php tools run_migration
// ============================================================
class Tools extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // CLI only — never expose via web
        if (!$this->input->is_cli_request()) {
            show_error('This controller is CLI only.', 403);
        }
        $this->load->library('MigrationRunner');
    }

    /**
     * php index.php tools run_migration --sql="ALTER TABLE ..."
     * php index.php tools run_migration --file=path/to/file.sql
     * php index.php tools run_migration --file=... --dry_run
     */
    public function run_migration(): void
    {
        $args = $this->_parseArgs();

        $sql      = $args['sql']     ?? null;
        $file     = $args['file']    ?? null;
        $dry_run  = isset($args['dry_run']);
        $school   = $args['school']  ?? null;

        if (!$sql && !$file) {
            die("Usage:\n  php index.php tools run_migration --sql=\"ALTER TABLE ...\"\n  php index.php tools run_migration --file=migrations/044.sql\n");
        }

        if ($file && !$sql) {
            $path = APPPATH . $file;
            if (!file_exists($path)) { $path = $file; }
            if (!file_exists($path)) { die("File not found: {$file}\n"); }
            $sql = file_get_contents($path);
        }

        if ($school) {
            $result = $this->migrationrunner->runOnSchool($school, $sql);
        } else {
            $result = $this->migrationrunner->run($sql, $dry_run);
        }

        if (!empty($result['errors'])) {
            echo "\nErrors:\n";
            foreach ($result['errors'] as $err) { echo "  - {$err}\n"; }
        }
    }

    /**
     * List all school databases
     * php index.php tools list_schools
     */
    public function list_schools(): void
    {
        $masterDb = $this->load->database('master', true);
        $schools  = $masterDb->select('subdomain, db_name, status, plan, trial_ends, created_at')
            ->order_by('created_at', 'DESC')
            ->get('schools')->result();

        printf("%-20s %-20s %-10s %-12s %-12s\n", 'Subdomain', 'DB Name', 'Status', 'Plan', 'Trial Ends');
        echo str_repeat('-', 80) . "\n";
        foreach ($schools as $s) {
            printf("%-20s %-20s %-10s %-12s %-12s\n",
                $s->subdomain, $s->db_name, $s->status, $s->plan, $s->trial_ends ?? '-');
        }
    }

    /**
     * Parse CLI --key=value and --flag arguments
     */
    private function _parseArgs(): array
    {
        $args = [];
        foreach (array_slice($_SERVER['argv'] ?? [], 3) as $arg) {
            if (preg_match('/^--([^=]+)(?:=(.+))?$/', $arg, $m)) {
                $args[$m[1]] = $m[2] ?? true;
            }
        }
        return $args;
    }
}
