<?php

require_once __DIR__ . '/../../core/Database.php';

class Rating
{
    public static function create(int $rideId, int $raterId, int $rateeId, string $raterRole, int $stars, ?string $comment): bool
    {
        try {
            $stmt = Database::connect()->prepare(
                'INSERT INTO ratings (ride_id, rater_id, ratee_id, rater_role, stars, comment) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$rideId, $raterId, $rateeId, $raterRole, $stars, $comment]);
            return true;
        } catch (PDOException $e) {
            // Unique (ride_id, rater_id, ratee_id) key -- a duplicate-submit
            // second rating attempt for the same direction is rejected here.
            if ((int)$e->getCode() === 23000) {
                return false;
            }
            throw $e;
        }
    }

    public static function existsFor(int $rideId, int $raterId, int $rateeId): bool
    {
        $stmt = Database::connect()->prepare('SELECT id FROM ratings WHERE ride_id = ? AND rater_id = ? AND ratee_id = ?');
        $stmt->execute([$rideId, $raterId, $rateeId]);
        return (bool)$stmt->fetch();
    }

    /** Ratings this user has RECEIVED (from either role), newest first. */
    public static function forRatee(int $userId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT rt.*, r.origin, r.destination, r.departure_at, u.name AS rater_name
             FROM ratings rt
             JOIN rides r ON r.id = rt.ride_id
             JOIN users u ON u.id = rt.rater_id
             WHERE rt.ratee_id = ?
             ORDER BY rt.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function averageForRatee(int $userId): ?array
    {
        $stmt = Database::connect()->prepare('SELECT AVG(stars) AS avg_stars, COUNT(*) AS n FROM ratings WHERE ratee_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['n'] === 0) {
            return null;
        }
        return ['avg' => round((float)$row['avg_stars'], 1), 'count' => (int)$row['n']];
    }

    public static function forRide(int $rideId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT rt.*, ur.name AS rater_name, ue.name AS ratee_name
             FROM ratings rt
             JOIN users ur ON ur.id = rt.rater_id
             JOIN users ue ON ue.id = rt.ratee_id
             WHERE rt.ride_id = ? ORDER BY rt.created_at'
        );
        $stmt->execute([$rideId]);
        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        return Database::connect()->query(
            'SELECT rt.*, r.origin, r.destination, ur.name AS rater_name, ue.name AS ratee_name
             FROM ratings rt
             JOIN rides r ON r.id = rt.ride_id
             JOIN users ur ON ur.id = rt.rater_id
             JOIN users ue ON ue.id = rt.ratee_id
             ORDER BY rt.created_at DESC'
        )->fetchAll();
    }

    public static function count(): int
    {
        return (int)Database::connect()->query('SELECT COUNT(*) AS c FROM ratings')->fetch()['c'];
    }
}
