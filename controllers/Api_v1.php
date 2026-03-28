<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api_v1
 *
 * EduRoot REST API — version 1
 * Base URL: /api/v1/
 *
 * This controller deliberately does NOT extend MY_Controller or Admin_Controller.
 * MY_Controller loads 100+ models on every request — far too heavy for API calls.
 * Instead we extend CI_Controller and load only what each method needs.
 *
 * ─────────────────────────────────────────────────────────────────
 *  Routing (add to application/config/routes.php):
 *
 *    $route['api/v1/(:any)'] = 'api_v1/route/$1';
 *    // OPTIONS preflight for CORS
 *    $route['api/v1/(:any)', 'OPTIONS'] = 'api_v1/options';
 *
 * ─────────────────────────────────────────────────────────────────
 *  Implemented endpoints (this file):
 *
 *    POST  /api/v1/auth/login          Get JWT + refresh token
 *    POST  /api/v1/auth/refresh        Rotate access token
 *    POST  /api/v1/auth/logout         Revoke refresh token
 *    GET   /api/v1/student/me          Own profile (student / parent)
 *    GET   /api/v1/students            List students (admin / teacher / accountant)
 *    GET   /api/v1/students/{id}       Single student detail
 *
 *  Stub endpoints (return 501 — implement in Phase 2):
 *
 *    GET   /api/v1/fees/balance
 *    GET   /api/v1/fees/history
 *    GET   /api/v1/fees/receipt/{id}
 *    GET   /api/v1/attendance
 *    POST  /api/v1/attendance
 *    GET   /api/v1/exams
 *    GET   /api/v1/exams/{id}/result
 *    POST  /api/v1/notifications/send
 *    GET   /api/v1/notifications
 *    POST  /api/v1/ai/report-card-remark
 *    GET   /api/v1/ai/fee-risk
 */
class Api_v1 extends CI_Controller
{
    // -------------------------------------------------------------------------
    // Bootstrap
    // -------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();

        // CORS headers on every response
        $this->_cors();

        // Load only what the API needs
        $this->load->library('JwtMiddleware');
        $this->load->library('Enc_lib');
        $this->load->model('Api_auth_model');
        $this->load->model('setting_model');
    }

    // -------------------------------------------------------------------------
    // Router — dispatches URI segments to handler methods
    // -------------------------------------------------------------------------

    /**
     * Entry point. Called as: api_v1/route/{path}
     * Routes by METHOD + path to internal handler methods.
     *
     * @param string $path  Everything after /api/v1/
     */
    public function route(string $path = ''): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $parts  = explode('/', trim($path, '/'));

        // auth/*
        if ($parts[0] === 'auth') {
            $action = $parts[1] ?? '';
            if ($method === 'POST' && $action === 'login')   { $this->_authLogin();   return; }
            if ($method === 'POST' && $action === 'refresh') { $this->_authRefresh(); return; }
            if ($method === 'POST' && $action === 'logout')  { $this->_authLogout();  return; }
        }

        // student/me
        if ($method === 'GET' && $parts[0] === 'student' && ($parts[1] ?? '') === 'me') {
            $this->_studentMe();
            return;
        }

        // students / students/{id}
        if ($parts[0] === 'students') {
            $id = isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : null;
            if ($method === 'GET' && $id) { $this->_studentShow($id); return; }
            if ($method === 'GET')        { $this->_studentList();    return; }
        }

        // fees/*
        if ($parts[0] === 'fees') {
            $action = $parts[1] ?? '';
            if ($method === 'GET' && $action === 'balance')  { $this->_stub('fees/balance');  return; }
            if ($method === 'GET' && $action === 'history')  { $this->_stub('fees/history');  return; }
            if ($method === 'GET' && $action === 'receipt')  { $this->_stub('fees/receipt');  return; }
        }

        // attendance
        if ($parts[0] === 'attendance') {
            if ($method === 'GET')  { $this->_stub('GET attendance');  return; }
            if ($method === 'POST') { $this->_stub('POST attendance'); return; }
        }

        // exams / exams/{id}/result
        if ($parts[0] === 'exams') {
            if ($method === 'GET' && isset($parts[1]) && isset($parts[2]) && $parts[2] === 'result') {
                $this->_stub('exams/{id}/result');
                return;
            }
            if ($method === 'GET') { $this->_stub('exams list'); return; }
        }

        // notifications
        if ($parts[0] === 'notifications') {
            $action = $parts[1] ?? '';
            if ($method === 'POST' && $action === 'send') { $this->_stub('notifications/send'); return; }
            if ($method === 'GET')                        { $this->_stub('notifications list'); return; }
        }

        // ai/*
        if ($parts[0] === 'ai') {
            $action = $parts[1] ?? '';
            if ($method === 'POST' && $action === 'report-card-remark') { $this->_stub('ai/report-card-remark'); return; }
            if ($method === 'GET'  && $action === 'fee-risk')            { $this->_stub('ai/fee-risk');            return; }
        }

        // Nothing matched
        $this->_json(['status' => 'error', 'code' => 'NOT_FOUND', 'message' => 'Endpoint not found'], 404);
    }

    /**
     * Handle OPTIONS preflight (CORS).
     */
    public function options(): void
    {
        http_response_code(204);
    }

    // -------------------------------------------------------------------------
    // AUTH handlers
    // -------------------------------------------------------------------------

    /**
     * POST /api/v1/auth/login
     *
     * Accepts:
     *   { "username": "...", "password": "...", "school_id": 1 }
     *
     * username can be:
     *   - Staff:   email address
     *   - Student: admission_no, username, or mobile (per school login settings)
     */
    private function _authLogin(): void
    {
        $body = $this->_body();

        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        if (!$username || !$password) {
            $this->_json([
                'status'  => 'error',
                'code'    => 'VALIDATION_FAILED',
                'message' => 'username and password are required',
                'errors'  => array_filter([
                    'username' => !$username ? 'Required' : null,
                    'password' => !$password ? 'Required' : null,
                ]),
            ], 422);
            return;
        }

        // ------------------------------------------------------------------
        // 1. Load school settings (active session, currency, date format)
        // ------------------------------------------------------------------
        $this->load->model('session_model');
        $settings = $this->setting_model->getSetting();

        if (!$settings) {
            $this->_json(['status' => 'error', 'code' => 'SCHOOL_NOT_CONFIGURED'], 500);
            return;
        }

        $school_id  = (int) ($settings->id ?? 1);
        $session_id = (int) ($settings->session_id ?? 0);

        // ------------------------------------------------------------------
        // 2. Try staff login first
        // ------------------------------------------------------------------
        $this->load->model('staff_model');
        $this->load->model('staffroles_model');

        $staff = $this->staff_model->checkLogin([
            'email'    => $username,
            'password' => $password,
        ]);

        if ($staff) {
            if (!$staff->is_active) {
                $this->_json(['status' => 'error', 'code' => 'ACCOUNT_DISABLED'], 403);
                return;
            }

            // Determine role string from roles array
            $role = $this->_staffRoleString($staff->roles ?? []);

            $token         = $this->jwtmiddleware->createToken($staff->id, $role, $school_id, $session_id);
            $refresh       = $this->jwtmiddleware->createRefreshToken();
            $this->api_auth_model->store($staff->id, $refresh['hash'], $refresh['expires_at']);

            $this->_json([
                'status' => 'success',
                'data'   => [
                    'token'         => $token,
                    'refresh_token' => $refresh['token'],
                    'expires_in'    => 3600,
                    'user'          => [
                        'id'              => (int) $staff->id,
                        'name'            => trim(($staff->name ?? '') . ' ' . ($staff->surname ?? '')),
                        'email'           => $staff->email ?? '',
                        'role'            => $role,
                        'image_url'       => $staff->image ? base_url('uploads/' . $staff->image) : null,
                        'currency_symbol' => $settings->currency_symbol ?? '₹',
                        'date_format'     => $settings->date_format ?? 'd/m/Y',
                        'lang_code'       => $settings->language_code ?? 'en',
                    ],
                ],
            ]);
            return;
        }

        // ------------------------------------------------------------------
        // 3. Try student / parent login
        // ------------------------------------------------------------------
        $this->load->model('student_model');

        $student = $this->student_model->checkLogin([
            'username' => $username,
            'password' => $password,
        ]);

        if ($student) {
            if ($student->user_tbl_active == 0 || $student->is_active === 'no') {
                $this->_json(['status' => 'error', 'code' => 'ACCOUNT_DISABLED'], 403);
                return;
            }

            $role   = $student->role ?? 'student';
            $userId = (int) ($student->user_tbl_id ?? $student->id);

            $token   = $this->jwtmiddleware->createToken($userId, $role, $school_id, $session_id);
            $refresh = $this->jwtmiddleware->createRefreshToken();
            $this->api_auth_model->store($userId, $refresh['hash'], $refresh['expires_at']);

            $this->_json([
                'status' => 'success',
                'data'   => [
                    'token'         => $token,
                    'refresh_token' => $refresh['token'],
                    'expires_in'    => 3600,
                    'user'          => [
                        'id'              => $userId,
                        'name'            => trim(($student->firstname ?? '') . ' ' . ($student->lastname ?? '')),
                        'email'           => $student->email ?? '',
                        'role'            => $role,
                        'image_url'       => $student->image ? base_url('uploads/' . $student->image) : null,
                        'currency_symbol' => $settings->currency_symbol ?? '₹',
                        'date_format'     => $settings->date_format ?? 'd/m/Y',
                        'lang_code'       => $settings->language_code ?? 'en',
                    ],
                ],
            ]);
            return;
        }

        // ------------------------------------------------------------------
        // 4. Nothing matched
        // ------------------------------------------------------------------
        $this->_json(['status' => 'error', 'code' => 'INVALID_CREDENTIALS', 'message' => 'Username or password is incorrect'], 401);
    }

    /**
     * POST /api/v1/auth/refresh
     *
     * Body: { "refresh_token": "..." }
     * Returns a new access token. Rotates the refresh token.
     */
    private function _authRefresh(): void
    {
        $body  = $this->_body();
        $raw   = $body['refresh_token'] ?? '';

        if (!$raw) {
            $this->_json(['status' => 'error', 'code' => 'MISSING_REFRESH_TOKEN'], 422);
            return;
        }

        $hash = hash('sha256', $raw);
        $row  = $this->api_auth_model->findValid($hash);

        if (!$row) {
            $this->_json(['status' => 'error', 'code' => 'INVALID_REFRESH_TOKEN', 'message' => 'Token not found, expired, or already used'], 401);
            return;
        }

        // Load school settings for session_id
        $settings   = $this->setting_model->getSetting();
        $school_id  = (int) ($settings->id ?? 1);
        $session_id = (int) ($settings->session_id ?? 0);

        // Get user role
        $this->load->model('user_model');
        $user = $this->user_model->getUserById((int) $row->user_id);

        if (!$user) {
            $this->_json(['status' => 'error', 'code' => 'USER_NOT_FOUND'], 401);
            return;
        }

        // Revoke the old refresh token (rotation — one-time use)
        $this->api_auth_model->revoke($hash);

        // Issue new tokens
        $newToken   = $this->jwtmiddleware->createToken((int) $row->user_id, $user->role, $school_id, $session_id);
        $newRefresh = $this->jwtmiddleware->createRefreshToken();
        $this->api_auth_model->store((int) $row->user_id, $newRefresh['hash'], $newRefresh['expires_at']);

        $this->_json([
            'status' => 'success',
            'data'   => [
                'token'         => $newToken,
                'refresh_token' => $newRefresh['token'],
                'expires_in'    => 3600,
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Body: { "refresh_token": "..." }  (optional — if omitted, revokes all tokens for user)
     */
    private function _authLogout(): void
    {
        $payload = $this->jwtmiddleware->requireAuth();

        $body = $this->_body();
        $raw  = $body['refresh_token'] ?? '';

        if ($raw) {
            $hash = hash('sha256', $raw);
            $this->api_auth_model->revoke($hash);
        } else {
            // Revoke all sessions for this user
            $this->api_auth_model->revokeAll((int) $payload->sub);
        }

        $this->_json(['status' => 'success']);
    }

    // -------------------------------------------------------------------------
    // STUDENT handlers
    // -------------------------------------------------------------------------

    /**
     * GET /api/v1/student/me
     * Roles: student, parent
     */
    private function _studentMe(): void
    {
        $payload = $this->jwtmiddleware->requireAuth(['student', 'parent']);

        $this->load->model('student_model');
        $this->load->model('studentsession_model');

        $settings   = $this->setting_model->getSetting();
        $session_id = (int) $settings->session_id;

        // For student: payload->sub is users.id = students.id (same value)
        // For parent: payload->sub is the parent's users.id — find their child
        if ($payload->role === 'parent') {
            $student = $this->student_model->getStudentByParentId((int) $payload->sub, $session_id);
        } else {
            $student = $this->student_model->getRecentRecord((int) $payload->sub);
        }

        if (!$student) {
            $this->_json(['status' => 'error', 'code' => 'NOT_FOUND', 'message' => 'Student record not found'], 404);
            return;
        }

        // getRecentRecord returns array; getStudentByParentId may return object — normalise
        if (is_array($student)) {
            $s = (object) $student;
        } else {
            $s = $student;
        }

        $this->_json([
            'status' => 'success',
            'data'   => [
                'id'                => (int) ($s->id ?? 0),
                'admission_no'      => $s->admission_no ?? '',
                'firstname'         => $s->firstname ?? '',
                'middlename'        => $s->middlename ?? '',
                'lastname'          => $s->lastname ?? '',
                'class'             => $s->class ?? '',
                'section'           => $s->section ?? '',
                'roll_no'           => $s->roll_no ?? '',
                'dob'               => $s->dob ?? '',
                'gender'            => $s->gender ?? '',
                'mobileno'          => $s->mobileno ?? '',
                'email'             => $s->email ?? '',
                'guardian_name'     => $s->guardian_name ?? '',
                'guardian_phone'    => $s->guardian_phone ?? '',
                'session'           => $settings->session ?? '',
                'student_session_id' => (int) ($s->student_session_id ?? 0),
                'image_url'         => $s->image ? base_url('uploads/' . $s->image) : null,
                'school_house'      => $s->house_name ?? '',
                'category'          => $s->category ?? '',
            ],
        ]);
    }

    /**
     * GET /api/v1/students
     * Roles: admin, teacher, accountant
     *
     * Query params: class_id, section_id, search, is_active, page
     */
    private function _studentList(): void
    {
        $payload = $this->jwtmiddleware->requireAuth(['admin', 'teacher', 'accountant', 'superadmin']);

        $this->load->model('student_model');
        $this->load->model('studentsession_model');

        $settings   = $this->setting_model->getSetting();
        $session_id = (int) $settings->session_id;

        $class_id   = (int) ($this->input->get('class_id') ?? 0);
        $section_id = (int) ($this->input->get('section_id') ?? 0);
        $search     = $this->input->get('search') ?? '';
        $is_active  = $this->input->get('is_active') ?? 'yes';
        $page       = max(1, (int) ($this->input->get('page') ?? 1));
        $per_page   = 50;
        $offset     = ($page - 1) * $per_page;

        // Build query against student_session JOIN students
        $this->db->select('
            students.id,
            students.admission_no,
            students.firstname,
            students.middlename,
            students.lastname,
            students.mobileno,
            students.email,
            students.gender,
            students.image,
            classes.class,
            sections.section,
            student_session.roll_no,
            student_session.id AS student_session_id
        ');
        $this->db->from('students');
        $this->db->join('student_session', 'student_session.student_id = students.id');
        $this->db->join('classes',  'classes.id = student_session.class_id');
        $this->db->join('sections', 'sections.id = student_session.section_id');
        $this->db->where('student_session.session_id', $session_id);
        $this->db->where('students.is_active', $is_active);

        if ($class_id)   { $this->db->where('student_session.class_id', $class_id); }
        if ($section_id) { $this->db->where('student_session.section_id', $section_id); }

        if ($search) {
            $safe = $this->db->escape_like_str($search);
            $this->db->group_start();
            $this->db->like('students.firstname', $safe);
            $this->db->or_like('students.lastname', $safe);
            $this->db->or_like('students.admission_no', $safe);
            $this->db->group_end();
        }

        // Count total (before limit)
        $total = $this->db->count_all_results('', false);

        $this->db->limit($per_page, $offset);
        $rows = $this->db->get()->result();

        $data = array_map(function ($s) {
            return [
                'id'                => (int) $s->id,
                'admission_no'      => $s->admission_no,
                'name'              => trim($s->firstname . ' ' . $s->lastname),
                'class'             => $s->class,
                'section'           => $s->section,
                'roll_no'           => $s->roll_no ?? '',
                'mobileno'          => $s->mobileno ?? '',
                'email'             => $s->email ?? '',
                'gender'            => $s->gender ?? '',
                'image_url'         => $s->image ? base_url('uploads/' . $s->image) : null,
                'student_session_id' => (int) $s->student_session_id,
            ];
        }, $rows);

        $this->_json([
            'status' => 'success',
            'data'   => $data,
            'meta'   => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => (int) ceil($total / $per_page),
            ],
        ]);
    }

    /**
     * GET /api/v1/students/{id}
     * Full student profile. Roles: admin, teacher, accountant
     */
    private function _studentShow(int $id): void
    {
        $payload = $this->jwtmiddleware->requireAuth(['admin', 'teacher', 'accountant', 'superadmin']);

        $this->load->model('student_model');

        $student = $this->student_model->getRecentRecord($id);

        if (!$student) {
            $this->_json(['status' => 'error', 'code' => 'NOT_FOUND', 'message' => 'Student not found'], 404);
            return;
        }

        $s = is_array($student) ? (object) $student : $student;

        $this->_json([
            'status' => 'success',
            'data'   => [
                'id'               => (int) ($s->id ?? 0),
                'admission_no'     => $s->admission_no ?? '',
                'firstname'        => $s->firstname ?? '',
                'middlename'       => $s->middlename ?? '',
                'lastname'         => $s->lastname ?? '',
                'dob'              => $s->dob ?? '',
                'gender'           => $s->gender ?? '',
                'blood_group'      => $s->blood_group ?? '',
                'religion'         => $s->religion ?? '',
                'category'         => $s->category ?? '',
                'mobileno'         => $s->mobileno ?? '',
                'email'            => $s->email ?? '',
                'current_address'  => $s->current_address ?? '',
                'permanent_address'=> $s->permanent_address ?? '',
                'adhar_no'         => $s->adhar_no ?? '',
                'rte'              => (bool) ($s->rte ?? false),
                'class'            => $s->class ?? '',
                'section'          => $s->section ?? '',
                'roll_no'          => $s->roll_no ?? '',
                'admission_date'   => $s->admission_date ?? '',
                'is_active'        => $s->is_active ?? 'yes',
                'father_name'      => $s->father_name ?? '',
                'father_phone'     => $s->father_phone ?? '',
                'mother_name'      => $s->mother_name ?? '',
                'mother_phone'     => $s->mother_phone ?? '',
                'guardian_name'    => $s->guardian_name ?? '',
                'guardian_phone'   => $s->guardian_phone ?? '',
                'guardian_email'   => $s->guardian_email ?? '',
                'guardian_relation'=> $s->guardian_relation ?? '',
                'bank_account_no'  => $s->bank_account_no ?? '',
                'bank_name'        => $s->bank_name ?? '',
                'ifsc_code'        => $s->ifsc_code ?? '',
                'image_url'        => $s->image ? base_url('uploads/' . $s->image) : null,
                'school_house'     => $s->house_name ?? '',
                'hostel_room_id'   => $s->hostel_room_id ? (int) $s->hostel_room_id : null,
                'student_session_id' => (int) ($s->student_session_id ?? 0),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Stub — placeholder for Phase 2 endpoints
    // -------------------------------------------------------------------------

    private function _stub(string $name): void
    {
        $this->_json([
            'status'  => 'error',
            'code'    => 'NOT_IMPLEMENTED',
            'message' => "Endpoint '{$name}' is planned for Phase 2. See docs/api/api-spec-v1.md",
        ], 501);
    }

    // -------------------------------------------------------------------------
    // Shared utilities
    // -------------------------------------------------------------------------

    /**
     * Set CORS headers.
     * In production, replace '*' with your actual front-end domain.
     */
    private function _cors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Output a JSON response and stop execution.
     *
     * @param array $data
     * @param int   $code  HTTP status code
     */
    private function _json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // Using die() here is correct — we're done with this request
        die();
    }

    /**
     * Parse the raw JSON request body.
     *
     * @return array  Decoded body or empty array on failure
     */
    private function _body(): array
    {
        $raw = file_get_contents('php://input');
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Map the CI roles array (from staff_model) to a single role string.
     * The roles array comes back as ['Admin' => 1, 'Teacher' => 2] etc.
     *
     * @param array $roles  e.g. ['Admin' => 1]
     * @return string       e.g. 'admin'
     */
    private function _staffRoleString(array $roles): string
    {
        if (empty($roles)) {
            return 'staff';
        }

        $name = strtolower(array_key_first($roles));

        // Normalise to the canonical role names used in the JWT
        $map = [
            'superadmin'  => 'superadmin',
            'super admin' => 'superadmin',
            'admin'       => 'admin',
            'teacher'     => 'teacher',
            'accountant'  => 'accountant',
            'librarian'   => 'librarian',
        ];

        return $map[$name] ?? 'staff';
    }
}
