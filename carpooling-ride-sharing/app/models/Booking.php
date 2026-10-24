<?php

require_once __DIR__ . '/../../core/Database.php';

class Booking
{
    public const ERR_RIDE_NOT_FOUND    = 'ride_not_found';
    public const ERR_RIDE_NOT_BOOKABLE = 'ride_not_bookable';
    public const ERR_DEPARTED          = 'departed';
    public const ERR_SEATS_INVALID     = 'seats_invalid';
    public const ERR_FULL              = 'full';

    /**
     * Books $seatsRequested seats for $riderId on $rideId. Returns
     * ['ok' => true, 'booking_id' => ..., 'total_fare' => ...] on success,
     * or ['ok' => false, 'error' => self::ERR_*, ...] on a rejected attempt.
     *
     * ---------------------------------------------------------------------
     * TECHNICAL CENTERPIECE -- race-safe capacity-limited seat booking.
     * ---------------------------------------------------------------------
     * The naive approach is check-then-act: SELECT the ride, read
     * seats_available, and if it's enough, INSERT the booking and UPDATE the
     * counter. Two riders' requests can both run that SELECT in the same
     * instant, both see "3 seats left, I want 2, that's fine", and both
     * proceed to book -- nothing in that flow told either request the other
     * was mid-flight. Whichever UPDATE runs last simply overwrites the
     * counter, and the ride silently overbooks.
     *
     * This method closes that race with the same "row lock as mutex"
     * pattern already used elsewhere in this suite (project 3's slot
     * reservation, project 19's registration capacity check):
     *
     *   1. `SELECT ... FOR UPDATE` on the *ride* row, inside an explicit
     *      transaction. Every concurrent booking attempt against this same
     *      ride must wait for MySQL's row-level exclusive lock before it can
     *      even read seats_available -- which serializes what would
     *      otherwise be a classic read-then-write race into a safe queue of
     *      one-at-a-time transactions.
     *   2. While still holding that lock, seats_available is re-checked
     *      against the request. Only one of two simultaneous "3 left, book
     *      2" requests can now actually succeed -- the second sees the
     *      *already-decremented* value once its turn comes, not the stale
     *      number it might have read before locking.
     *   3. The booking row is inserted and rides.seats_available is
     *      decremented in the same transaction, which commits together --
     *      an INSERT can never succeed while leaving the counter stale, and
     *      a crash mid-way rolls back both instead of leaving the ride
     *      quietly overbooked.
     *
     * Nothing here ever trusts a seats-remaining number the client already
     * had on screen -- it is re-read from the locked row every time.
     */
    public static function book(int $rideId, int $riderId, int $seatsRequested): array
    {
        if ($seatsRequested < 1) {
            return ['ok' => false, 'error' => self::ERR_SEATS_INVALID];
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            // Step 1: lock the ride row -- this IS the mutex.
            $stmt = $pdo->prepare('SELECT * FROM rides WHERE id = ? FOR UPDATE');
            $stmt->execute([$rideId]);
            $ride = $stmt->fetch();

            if (!$ride) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => self::ERR_RIDE_NOT_FOUND];
            }

            if ($ride['status'] !== 'scheduled') {
                $pdo->rollBack();
                return ['ok' => false, 'error' => self::ERR_RIDE_NOT_BOOKABLE];
            }

            // Departure check against the DB's own clock, inside the same locked transaction.
            $stmt = $pdo->prepare('SELECT NOW() >= ? AS departed');
            $stmt->execute([$ride['departure_at']]);
            if ((bool)$stmt->fetch()['departed']) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => self::ERR_DEPARTED];
            }

            // Step 2: race-safe capacity check (ride row already locked above).
            if ($seatsRequested > (int)$ride['seats_available']) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => self::ERR_FULL, 'seats_remaining' => (int)$ride['seats_available']];
            }

            $farePerSeat = (float)$ride['price_per_seat'];
            $totalFare = round($farePerSeat * $seatsRequested, 2);

            // Step 3: insert booking + decrement the counter, same transaction.
            $stmt = $pdo->prepare(
                'INSERT INTO bookings (ride_id, rider_id, seats_booked, fare_per_seat, total_fare, status)
                 VALUES (?, ?, ?, ?, ?, "confirmed")'
            );
            $stmt->execute([$rideId, $riderId, $seatsRequested, $farePerSeat, $totalFare]);
            $bookingId = (int)$pdo->lastInsertId();

            $pdo->prepare('UPDATE rides SET seats_available = seats_available - ? WHERE id = ?')
                ->execute([$seatsRequested, $rideId]);

            $pdo->commit();
            return ['ok' => true, 'booking_id' => $bookingId, 'total_fare' => $totalFare, 'seats_booked' => $seatsRequested];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancels a rider's own confirmed booking and restores the seats to the
     * ride under the same row-lock discipline as book() -- a cancel and a
     * concurrent new booking on the same ride can never both read a stale
     * seats_available.
     */
    public static function cancel(int $bookingId, int $riderId): bool
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                "SELECT b.*, r.status AS ride_status, r.departure_at
                 FROM bookings b JOIN rides r ON r.id = b.ride_id
                 WHERE b.id = ? AND b.rider_id = ? AND b.status = 'confirmed' FOR UPDATE"
            );
            $stmt->execute([$bookingId, $riderId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                $pdo->rollBack();
                return false;
            }

            // Can't cancel a booking on a ride that's already departed/completed.
            $stmt = $pdo->prepare('SELECT NOW() >= ? AS departed');
            $stmt->execute([$booking['departure_at']]);
            $departed = (bool)$stmt->fetch()['departed'];
            if ($departed || in_array($booking['ride_status'], ['completed'], true)) {
                $pdo->rollBack();
                return false;
            }

            $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?")->execute([$bookingId]);

            if (in_array($booking['ride_status'], ['scheduled', 'ongoing'], true)) {
                $pdo->prepare('UPDATE rides SET seats_available = seats_available + ? WHERE id = ?')
                    ->execute([$booking['seats_booked'], $booking['ride_id']]);
            }

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connect()->prepare(
            'SELECT b.*, r.origin, r.destination, r.departure_at, r.status AS ride_status, r.driver_id,
                    u.name AS rider_name, u.email AS rider_email
             FROM bookings b
             JOIN rides r ON r.id = b.ride_id
             JOIN users u ON u.id = b.rider_id
             WHERE b.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function belongsToRider(int $id, int $riderId): bool
    {
        $stmt = Database::connect()->prepare('SELECT id FROM bookings WHERE id = ? AND rider_id = ?');
        $stmt->execute([$id, $riderId]);
        return (bool)$stmt->fetch();
    }

    public static function forRider(int $riderId): array
    {
        $stmt = Database::connect()->prepare(
            'SELECT b.*, r.origin, r.destination, r.departure_at, r.status AS ride_status, r.driver_id,
                    u.name AS driver_name
             FROM bookings b
             JOIN rides r ON r.id = b.ride_id
             JOIN users u ON u.id = r.driver_id
             WHERE b.rider_id = ?
             ORDER BY r.departure_at DESC'
        );
        $stmt->execute([$riderId]);
        return $stmt->fetchAll();
    }

    public static function forRide(int $rideId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT b.*, u.name AS rider_name, u.email AS rider_email, u.phone AS rider_phone
             FROM bookings b JOIN users u ON u.id = b.rider_id
             WHERE b.ride_id = ? ORDER BY b.booked_at"
        );
        $stmt->execute([$rideId]);
        return $stmt->fetchAll();
    }

    public static function confirmedRidersForRide(int $rideId): array
    {
        $stmt = Database::connect()->prepare(
            "SELECT b.*, u.name AS rider_name
             FROM bookings b JOIN users u ON u.id = b.rider_id
             WHERE b.ride_id = ? AND b.status = 'confirmed' ORDER BY u.name"
        );
        $stmt->execute([$rideId]);
        return $stmt->fetchAll();
    }

    public static function all(): array
    {
        return Database::connect()->query(
            "SELECT b.*, r.origin, r.destination, r.departure_at, r.status AS ride_status,
                    ur.name AS rider_name, ud.name AS driver_name
             FROM bookings b
             JOIN rides r ON r.id = b.ride_id
             JOIN users ur ON ur.id = b.rider_id
             JOIN users ud ON ud.id = r.driver_id
             ORDER BY b.booked_at DESC"
        )->fetchAll();
    }

    public static function count(): int
    {
        return (int)Database::connect()->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'confirmed'")->fetch()['c'];
    }

    public static function totalRevenue(): float
    {
        return (float)Database::connect()->query("SELECT COALESCE(SUM(total_fare),0) AS s FROM bookings WHERE status = 'confirmed'")->fetch()['s'];
    }
}
