<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * JwtMiddleware
 *
 * Handles JWT creation, verification, and role-based access control
 * for the EduRoot REST API (v1).
 *
 * Uses the Firebase JWT library already bundled at:
 *   application/third_party/jwt/
 *
 * JWT payload structure:
 * {
 *   "sub":        (int)    users.id
 *   "role":       (string) admin | teacher | accountant | student | parent | librarian | bot
 *   "school_id":  (int)    sch_settings.id  (always 1 for single-school installs)
 *   "session_id": (int)    sessions.id  (current active academic year)
 *   "iat":        (int)    issued-at unix timestamp
 *   "exp":        (int)    expiry unix timestamp
 * }
 */
class JwtMiddleware
{
    /** @var CI_Controller */
    private $CI;

    /** @var string HS256 secret — read from config('jwt_secret') */
    private $secret;

    /** @var int Access token lifetime in seconds (default 1 hour) */
    private $ttl = 3600;

    /** @var int Refresh token lifetime in seconds (default 30 days) */
    private $refresh_ttl = 2592000;

    public function __construct()
    {
        $this->CI =& get_instance();

        // Load JWT library
        require_once APPPATH . 'third_party/jwt/autoload.php';

        // Secret must be set in application/config/config.php:
        //   $config['jwt_secret'] = 'your-secret-key-min-32-chars';
        $secret = $this->CI->config->item('jwt_secret');
        if (empty($secret)) {
            $this->_error(500, 'SERVER_MISCONFIGURED', 'jwt_secret not set in config.php');
            return;
        }
        $this->secret = $secret;
    }

    // -------------------------------------------------------------------------
    // Token creation
    // -------------------------------------------------------------------------

    /**
     * Create an access token.
     *
     * @param int    $user_id
     * @param string $role
     * @param int    $school_id
     * @param int    $session_id  Current academic year ID
     * @return string  Signed JWT
     */
    public function createToken(int $user_id, string $role, int $school_id, int $session_id): string
    {
        $now = time();

        $payload = [
            'sub'        => $user_id,
            'role'       => $role,
            'school_id'  => $school_id,
            'session_id' => $session_id,
            'iat'        => $now,
            'exp'        => $now + $this->ttl,
        ];

        return \Firebase\JWT\JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Create a refresh token (opaque random string).
     * Stored hashed in api_refresh_tokens table.
     *
     * @return array ['token' => raw_token, 'hash' => sha256_hash, 'expires_at' => datetime_string]
     */
    public function createRefreshToken(): array
    {
        $raw     = bin2hex(random_bytes(32));
        $hash    = hash('sha256', $raw);
        $expires = date('Y-m-d H:i:s', time() + $this->refresh_ttl);

        return [
            'token'      => $raw,
            'hash'       => $hash,
            'expires_at' => $expires,
        ];
    }

    // -------------------------------------------------------------------------
    // Request authentication
    // -------------------------------------------------------------------------

    /**
     * Require a valid JWT. Call this at the top of every protected handler.
     *
     * Usage:
     *   $payload = $this->jwt_middleware->requireAuth();
     *   $payload = $this->jwt_middleware->requireAuth(['admin', 'teacher']);
     *
     * On failure: outputs JSON error and ends the request (die).
     * On success:  returns the decoded JWT payload as a stdClass object.
     *
     * @param array $allowed_roles  Empty = any authenticated role is allowed.
     * @return object  JWT payload
     */
    public function requireAuth(array $allowed_roles = []): object
    {
        $token = $this->_extractBearerToken();

        if (!$token) {
            $this->_error(401, 'MISSING_TOKEN', 'Authorization header required');
        }

        try {
            $payload = \Firebase\JWT\JWT::decode(
                $token,
                new \Firebase\JWT\Key($this->secret, 'HS256')
            );
        } catch (\Firebase\JWT\ExpiredException $e) {
            $this->_error(401, 'TOKEN_EXPIRED', 'Token has expired — use /auth/refresh');
        } catch (\Exception $e) {
            $this->_error(401, 'INVALID_TOKEN', 'Token is invalid or tampered');
        }

        if (!empty($allowed_roles) && !in_array($payload->role, $allowed_roles, true)) {
            $this->_error(403, 'FORBIDDEN', 'Your role (' . $payload->role . ') cannot access this endpoint');
        }

        return $payload;
    }

    /**
     * Same as requireAuth but silently returns null instead of erroring.
     * Use when a field is optionally populated if logged in.
     *
     * @return object|null
     */
    public function optionalAuth(): ?object
    {
        $token = $this->_extractBearerToken();
        if (!$token) {
            return null;
        }

        try {
            return \Firebase\JWT\JWT::decode(
                $token,
                new \Firebase\JWT\Key($this->secret, 'HS256')
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Extract the raw token from "Authorization: Bearer {token}"
     */
    private function _extractBearerToken(): ?string
    {
        $header = isset($_SERVER['HTTP_AUTHORIZATION'])
            ? $_SERVER['HTTP_AUTHORIZATION']
            : ($this->CI->input->get_request_header('Authorization') ?? '');

        if (strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }

        return null;
    }

    /**
     * Output a JSON error response and terminate.
     * Uses die() intentionally — this is a terminal middleware failure,
     * not a controller flow (where return; is preferred).
     */
    private function _error(int $http_code, string $code, string $message): void
    {
        http_response_code($http_code);
        header('Content-Type: application/json');
        die(json_encode([
            'status'  => 'error',
            'code'    => $code,
            'message' => $message,
        ]));
    }
}
