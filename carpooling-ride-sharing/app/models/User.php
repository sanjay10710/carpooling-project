<?php

require_once __DIR__ . '/../../core/Database.php';

class User
{
    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function emailExists(string $email): bool
    {
        return self::findByEmail($email) !== null;
    }

    /** Self-registration for driver/rider personas only (admin is seed-only). */
    public static function register(string $name, string $email, string $phone, string $password, string $role): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, "active")'
        );
        $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_BCRYPT), $role]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function all(?string $role = null): array
    {
        $pdo = Database::connect();
        if ($role !== null) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE role = ? ORDER BY name');
            $stmt->execute([$role]);
        } else {
            $stmt = $pdo->query('SELECT * FROM users ORDER BY role, name');
        }
        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connect()->prepare('UPDATE users SET status = ? WHERE id = ? AND role != "super_admin"');
        $stmt->execute([$status, $id]);
    }

    public static function countByRole(string $role): int
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) AS c FROM users WHERE role = ?');
        $stmt->execute([$role]);
        return (int)$stmt->fetch()['c'];
    }

    public static function countActiveByRole(string $role): int
    {
        $stmt = Database::connect()->prepare("SELECT COUNT(*) AS c FROM users WHERE role = ? AND status = 'active'");
        $stmt->execute([$role]);
        return (int)$stmt->fetch()['c'];
    }
}
