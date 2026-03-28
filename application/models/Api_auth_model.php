<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Api_auth_model
 *
 * Manages the api_refresh_tokens table.
 * Tokens are stored as SHA-256 hashes — the raw token is never stored.
 *
 * Table DDL is in: docs/api/migration-api-tables.sql
 */
class Api_auth_model extends CI_Model
{
    /** @var string Table name */
    private $table = 'api_refresh_tokens';

    public function __construct()
    {
        parent::__construct();
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /**
     * Store a new refresh token.
     *
     * @param int    $user_id
     * @param string $token_hash   SHA-256 of the raw token
     * @param string $expires_at   MySQL DATETIME string
     * @return bool
     */
    public function store(int $user_id, string $token_hash, string $expires_at): bool
    {
        return $this->db->insert($this->table, [
            'user_id'    => $user_id,
            'token_hash' => $token_hash,
            'expires_at' => $expires_at,
            'revoked'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Revoke a specific token by its hash.
     *
     * @param string $token_hash
     * @return bool
     */
    public function revoke(string $token_hash): bool
    {
        $this->db->where('token_hash', $token_hash);
        return $this->db->update($this->table, ['revoked' => 1]);
    }

    /**
     * Revoke all refresh tokens for a user (full logout from all devices).
     *
     * @param int $user_id
     * @return bool
     */
    public function revokeAll(int $user_id): bool
    {
        $this->db->where('user_id', $user_id);
        return $this->db->update($this->table, ['revoked' => 1]);
    }

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Look up a refresh token by hash.
     * Returns false if not found, expired, or revoked.
     *
     * @param string $token_hash
     * @return object|false  Row object with user_id, expires_at
     */
    public function findValid(string $token_hash)
    {
        $this->db->where('token_hash', $token_hash);
        $this->db->where('revoked', 0);
        $this->db->where('expires_at >', date('Y-m-d H:i:s'));
        $row = $this->db->get($this->table)->row();

        return $row ?: false;
    }

    // -------------------------------------------------------------------------
    // Maintenance
    // -------------------------------------------------------------------------

    /**
     * Delete expired tokens. Called by cron daily to keep the table clean.
     *
     * @return int  Number of rows deleted
     */
    public function deleteExpired(): int
    {
        $this->db->where('expires_at <', date('Y-m-d H:i:s'));
        $this->db->delete($this->table);
        return $this->db->affected_rows();
    }
}
