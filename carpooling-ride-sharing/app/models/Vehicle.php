<?php

require_once __DIR__ . '/../../core/Database.php';

class Vehicle
{
    public const TYPES = ['hatchback', 'sedan', 'suv', 'bike'];

    public static function create(int $driverId, string $make, string $model, string $color, string $plate, string $type, int $seats): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO vehicles (driver_id, make, model, color, plate_number, vehicle_type, total_seats, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, "active")'
        );
        $stmt->execute([$driverId, $make, $model, $color, strtoupper($plate), $type, $seats]);
        return (int)Database::connect()->lastInsertId();
    }

    public static function update(int $id, int $driverId, string $make, string $model, string $color, string $plate, string $type, int $seats): bool
    {
        $stmt = Database::connect()->prepare(
            'UPDATE vehicles SET make = ?, model = ?, color = ?, plate_number = ?, vehicle_type = ?, total_seats = ?
             WHERE id = ? AND driver_id = ?'
        );
        $stmt->execute([$make, $model, $color, strtoupper($plate), $type, $seats, $id, $driverId]);
        return $stmt->rowCount() > 0;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM vehicles WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function plateExists(string $plate, ?int $excludeId = null): bool
    {
        $pdo = Database::connect();
        if ($excludeId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM vehicles WHERE plate_number = ? AND id != ?');
            $stmt->execute([strtoupper($plate), $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM vehicles WHERE plate_number = ?');
            $stmt->execute([strtoupper($plate)]);
        }
        return (bool)$stmt->fetch();
    }

    public static function belongsToDriver(int $id, int $driverId): bool
    {
        $stmt = Database::connect()->prepare('SELECT id FROM vehicles WHERE id = ? AND driver_id = ?');
        $stmt->execute([$id, $driverId]);
        return (bool)$stmt->fetch();
    }

    public static function forDriver(int $driverId): array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM vehicles WHERE driver_id = ? ORDER BY created_at DESC');
        $stmt->execute([$driverId]);
        return $stmt->fetchAll();
    }

    public static function activeForDriver(int $driverId): array
    {
        $stmt = Database::connect()->prepare("SELECT * FROM vehicles WHERE driver_id = ? AND status = 'active' ORDER BY created_at DESC");
        $stmt->execute([$driverId]);
        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        return Database::connect()->query(
            'SELECT v.*, u.name AS driver_name, u.email AS driver_email
             FROM vehicles v JOIN users u ON u.id = v.driver_id
             ORDER BY v.created_at DESC'
        )->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = Database::connect()->prepare('UPDATE vehicles SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function count(): int
    {
        return (int)Database::connect()->query('SELECT COUNT(*) AS c FROM vehicles')->fetch()['c'];
    }
}
