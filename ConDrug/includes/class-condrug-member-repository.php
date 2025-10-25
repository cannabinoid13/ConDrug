<?php

namespace ConDrug;

class MemberRepository
{
    protected const TABLE_NAME = 'condrug_members';

    protected \wpdb $db;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
    }

    public function table(): string
    {
        return $this->db->prefix . static::TABLE_NAME;
    }

    protected function getCreateSql(): string
    {
        $table = $this->table();
        $charsetCollate = $this->db->get_charset_collate();

        return "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            tier_slug VARCHAR(50) NOT NULL DEFAULT 'free',
            metadata LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id)
        ) {$charsetCollate};";
    }

    public function createTable(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($this->getCreateSql());
    }

    public function ensureTableExists(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        maybe_create_table($this->table(), $this->getCreateSql());
    }

    public function upsertMember(int $userId, string $tierSlug = 'free', array $metadata = []): void
    {
        $this->db->replace(
            $this->table(),
            [
                'user_id' => $userId,
                'tier_slug' => $tierSlug,
                'metadata' => maybe_serialize($metadata),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s']
        );
    }

    public function getMember(int $userId): ?array
    {
        $row = $this->db->get_row($this->db->prepare("SELECT * FROM {$this->table()} WHERE user_id = %d", $userId), ARRAY_A);
        if (!$row) {
            return null;
        }

        $row['metadata'] = maybe_unserialize($row['metadata']);
        return $row;
    }

    public function getMembersByTier(string $tierSlug): array
    {
        $rows = $this->db->get_results($this->db->prepare("SELECT * FROM {$this->table()} WHERE tier_slug = %s", $tierSlug), ARRAY_A);
        return array_map(function ($row) {
            $row['metadata'] = maybe_unserialize($row['metadata']);
            return $row;
        }, $rows);
    }

    public function getAllMembers(): array
    {
        $rows = $this->db->get_results("SELECT * FROM {$this->table()}", ARRAY_A);
        return array_map(function ($row) {
            $row['metadata'] = maybe_unserialize($row['metadata']);
            return $row;
        }, $rows);
    }

    public function deleteMember(int $userId): void
    {
        $this->db->delete($this->table(), ['user_id' => $userId], ['%d']);
    }

    public function getMembersByPaymentStatus(bool $paid): array
    {
        $table = $this->table();
        $metaKey = 'condrug_subscription_id';
        if ($paid) {
            $query = "SELECT m.*, u.ID as user_id FROM {$table} m JOIN {$this->db->users} u ON u.ID = m.user_id WHERE EXISTS (SELECT 1 FROM {$this->db->usermeta} um WHERE um.user_id = m.user_id AND um.meta_key = %s AND um.meta_value <> '')";
        } else {
            $query = "SELECT m.*, u.ID as user_id FROM {$table} m JOIN {$this->db->users} u ON u.ID = m.user_id WHERE NOT EXISTS (SELECT 1 FROM {$this->db->usermeta} um WHERE um.user_id = m.user_id AND um.meta_key = %s AND um.meta_value <> '')";
        }

        $rows = $this->db->get_results($this->db->prepare($query, $metaKey), ARRAY_A);
        return array_map(function ($row) {
            $row['metadata'] = maybe_unserialize($row['metadata']);
            return $row;
        }, $rows);
    }
}
