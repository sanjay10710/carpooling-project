<?php

require_once __DIR__ . '/../../core/Database.php';

class Report
{
    public static function create(int $reporterId, ?int $reportedUserId, ?int $rideId, string $reason, ?string $details): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO reports (reporter_id, reported_user_id, ride_id, reason, details, status) VALUES (?, ?, ?, ?, ?, "open")'
        );
        $stmt->execute([$reporterId, $reportedUserId, $rideId, $reason, $details]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function all(string $status = ''): array
    {
        $pdo = Database::connect();
        $sql = "SELECT rp.*, u.name AS reporter_name, ru.name AS reported_name, r.origin, r.destination
                FROM reports rp
                JOIN users u ON u.id = rp.reporter_id
                LEFT JOIN users ru ON ru.id = rp.reported_user_id
                LEFT JOIN rides r ON r.id = rp.ride_id";
        $params = [];
        if ($status !== '') {
            $sql .= ' WHERE rp.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY rp.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM reports WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function resolve(int $id, int $adminId, string $status): bool
    {
        $stmt = Database::connect()->prepare(
            "UPDATE reports SET status = ?, resolved_by = ?, resolved_at = NOW() WHERE id = ? AND status = 'open'"
        );
        $stmt->execute([$status, $adminId, $id]);
        return $stmt->rowCount() > 0;
    }

    public static function countOpen(): int
    {
        return (int)Database::connect()->query("SELECT COUNT(*) AS c FROM reports WHERE status = 'open'")->fetch()['c'];
    }
}
