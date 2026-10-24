<?php

require_once __DIR__ . '/../../core/Database.php';

class Ride
{
    public static function create(int $driverId, int $vehicleId, string $origin, string $destination, string $departureAt, int $seatsTotal, float $price, ?string $notes): int
    {
        $stmt = Database::connect()->prepare(
            'INSERT INTO rides (driver_id, vehicle_id, origin, destination, departure_at, seats_total, seats_available, price_per_seat, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "scheduled")'
        );
        $stmt->execute([$driverId, $vehicleId, $origin, $destination, $departureAt, $seatsTotal, $seatsTotal, $price, $notes]);
        return (int)Database::connect()->lastInsertId();
    }

    /** Only editable while still scheduled and owned by this driver -- enforced in the WHERE clause, not just the UI. */
    public static function update(int $id, int $driverId, int $vehicleId, string $origin, string $destination, string $departureAt, float $price, ?string $notes): bool
    {
        $stmt = Database::connect()->prepare(
            "UPDATE rides SET vehicle_id = ?, origin = ?, destination = ?, departure_at = ?, price_per_seat = ?, notes = ?
             WHERE id = ? AND driver_id = ? AND status = 'scheduled'"
        );
        $stmt->execute([$vehicleId, $origin, $destination, $departureAt, $price, $notes, $id, $driverId]);
        return $stmt->rowCount() > 0;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT r.*, u.name AS driver_name, u.email AS driver_email, u.phone AS driver_phone,
                    v.make, v.model, v.color, v.plate_number, v.vehicle_type
             FROM rides r
             JOIN users u ON u.id = r.driver_id
             JOIN vehicles v ON v.id = r.vehicle_id
             WHERE r.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function belongsToDriver(int $id, int $driverId): bool
    {
        $stmt = Database::connect()->prepare('SELECT id FROM rides WHERE id = ? AND driver_id = ?');
        $stmt->execute([$id, $driverId]);
        return (bool)$stmt->fetch();
    }

    public static function forDriver(int $driverId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT r.*, v.make, v.model, v.plate_number
             FROM rides r JOIN vehicles v ON v.id = r.vehicle_id
             WHERE r.driver_id = ? ORDER BY r.departure_at DESC'
        );
        $stmt->execute([$driverId]);
        return $stmt->fetchAll();
    }

    /**
     * Rider search: plain case-insensitive substring match on origin/destination
     * (no maps/geocoding per the project brief), only future scheduled rides
     * with at least 1 seat left.
     */
    public static function search(string $origin = '', string $destination = '', string $date = ''): array
    {
        $sql = "SELECT r.*, u.name AS driver_name, v.make, v.model, v.vehicle_type
                FROM rides r
                JOIN users u ON u.id = r.driver_id
                JOIN vehicles v ON v.id = r.vehicle_id
                WHERE r.status = 'scheduled' AND r.seats_available > 0 AND r.departure_at > NOW()";
        $params = [];
        if ($origin !== '') {
            $sql .= ' AND r.origin LIKE ?';
            $params[] = '%' . $origin . '%';
        }
        if ($destination !== '') {
            $sql .= ' AND r.destination LIKE ?';
            $params[] = '%' . $destination . '%';
        }
        if ($date !== '') {
            $sql .= ' AND DATE(r.departure_at) = ?';
            $params[] = $date;
        }
        $sql .= ' ORDER BY r.departure_at ASC';

        $stmt = Database::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function all(string $status = ''): array
    {
        $pdo = Database::connect();
        $sql = "SELECT r.*, u.name AS driver_name, v.make, v.model, v.plate_number
                FROM rides r JOIN users u ON u.id = r.driver_id JOIN vehicles v ON v.id = r.vehicle_id";
        $params = [];
        if ($status !== '') {
            $sql .= ' WHERE r.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY r.departure_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, int $driverId, string $newStatus, array $allowedFrom): bool
    {
        $pdo = Database::connect();
        $placeholders = implode(',', array_fill(0, count($allowedFrom), '?'));
        $stmt = $pdo->prepare("UPDATE rides SET status = ? WHERE id = ? AND driver_id = ? AND status IN ($placeholders)");
        $stmt->execute(array_merge([$newStatus, $id, $driverId], $allowedFrom));
        return $stmt->rowCount() > 0;
    }

    /**
     * Cancelling a ride cascades to every confirmed booking on it -- both
     * writes happen inside one transaction so a booking can never be left
     * "confirmed" on a ride that's actually cancelled.
     */
    public static function cancel(int $id, int $driverId): bool
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id FROM rides WHERE id = ? AND driver_id = ? AND status IN ('scheduled','ongoing') FOR UPDATE");
            $stmt->execute([$id, $driverId]);
            if (!$stmt->fetch()) {
                $pdo->rollBack();
                return false;
            }

            $pdo->prepare("UPDATE rides SET status = 'cancelled' WHERE id = ?")->execute([$id]);
            $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancelled_at = NOW() WHERE ride_id = ? AND status = 'confirmed'")->execute([$id]);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function adminCancel(int $id): bool
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id FROM rides WHERE id = ? AND status IN ('scheduled','ongoing') FOR UPDATE");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                $pdo->rollBack();
                return false;
            }
            $pdo->prepare("UPDATE rides SET status = 'cancelled' WHERE id = ?")->execute([$id]);
            $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancelled_at = NOW() WHERE ride_id = ? AND status = 'confirmed'")->execute([$id]);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function countByStatus(string $status): int
    {
        $stmt = Database::connect()->prepare('SELECT COUNT(*) AS c FROM rides WHERE status = ?');
        $stmt->execute([$status]);
        return (int)$stmt->fetch()['c'];
    }

    public static function count(): int
    {
        return (int)Database::connect()->query('SELECT COUNT(*) AS c FROM rides')->fetch()['c'];
    }
}
