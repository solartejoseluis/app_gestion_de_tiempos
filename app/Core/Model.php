<?php
declare(strict_types=1);

class Model
{
    protected PDO $db;
    protected string $table = '';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function findAll(string $where = '', array $params = []): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($where) $sql .= " WHERE {$where}";
        return $this->query($sql, $params)->fetchAll();
    }

    protected function findOne(string $where, array $params = []): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$where} LIMIT 1";
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    protected function insert(array $data): int
    {
        $cols   = implode(', ', array_keys($data));
        $places = implode(', ', array_fill(0, count($data), '?'));
        $this->query(
            "INSERT INTO {$this->table} ({$cols}) VALUES ({$places})",
            array_values($data)
        );
        return (int) $this->db->lastInsertId();
    }

    protected function update(int $id, array $data): bool
    {
        $sets = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $this->query(
            "UPDATE {$this->table} SET {$sets} WHERE id = ?",
            [...array_values($data), $id]
        );
        return true;
    }

    protected function softDelete(int $id): bool
    {
        $this->query(
            "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ?",
            [$id]
        );
        return true;
    }
}
