<?php

require_once __DIR__ . '/../models/Ride.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/Rating.php';

class RiderController
{
    public function __construct()
    {
        Auth::requireRole('rider');
    }

    public function dashboard(): void
    {
        $riderId = Auth::id();
        $bookings = Booking::forRider($riderId);
        $upcoming = array_values(array_filter($bookings, fn($b) => $b['status'] === 'confirmed' && in_array($b['ride_status'], ['scheduled', 'ongoing'], true)));
        $past = array_values(array_filter($bookings, fn($b) => $b['ride_status'] === 'completed'));
        $avgRating = Rating::averageForRatee($riderId);
        require __DIR__ . '/../views/rider/dashboard.php';
    }

    public function search(): void
    {
        $origin = trim((string)($_GET['origin'] ?? ''));
        $destination = trim((string)($_GET['destination'] ?? ''));
        $date = trim((string)($_GET['date'] ?? ''));
        $searched = isset($_GET['origin']) || isset($_GET['destination']) || isset($_GET['date']);
        $rides = $searched ? Ride::search($origin, $destination, $date) : Ride::search();
        require __DIR__ . '/../views/rider/search.php';
    }

    public function rideShow(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $ride = Ride::find($id);
        if (!$ride) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $errors = $_SESSION['booking_errors'] ?? [];
        unset($_SESSION['booking_errors']);
        require __DIR__ . '/../views/rider/ride_show.php';
    }

    public function bookingStore(): void
    {
        Csrf::requireValid();
        $rideId = (int)($_POST['ride_id'] ?? 0);
        $seats = (int)($_POST['seats'] ?? 0);

        $result = Booking::book($rideId, Auth::id(), $seats);

        if ($result['ok']) {
            flash_set('success', 'Booked ' . $result['seats_booked'] . ' seat(s) for ' . money($result['total_fare']) . ' total.');
            redirect('rider/bookings');
            return;
        }

        $messages = [
            Booking::ERR_RIDE_NOT_FOUND    => 'That ride no longer exists.',
            Booking::ERR_RIDE_NOT_BOOKABLE => 'That ride is no longer open for booking.',
            Booking::ERR_DEPARTED          => 'That ride has already departed.',
            Booking::ERR_SEATS_INVALID     => 'Choose at least 1 seat.',
            Booking::ERR_FULL              => 'Not enough seats left on this ride (only ' . ($result['seats_remaining'] ?? 0) . ' remaining).',
        ];
        $_SESSION['booking_errors'] = ['booking' => $messages[$result['error']] ?? 'Booking could not be completed.'];
        redirect('rider/rides/show?id=' . $rideId);
    }

    public function bookings(): void
    {
        $bookings = Booking::forRider(Auth::id());
        require __DIR__ . '/../views/rider/bookings.php';
    }

    public function bookingCancel(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (Booking::cancel($id, Auth::id())) {
            flash_set('success', 'Booking cancelled and seats released.');
        } else {
            flash_set('error', 'Booking could not be cancelled (already departed or already cancelled?).');
        }
        redirect('rider/bookings');
    }

    public function rateDriver(): void
    {
        $bookingId = (int)($_GET['booking_id'] ?? 0);
        $booking = Booking::find($bookingId);
        if (!$booking || !Booking::belongsToRider($bookingId, Auth::id())) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        if ($booking['ride_status'] !== 'completed' || $booking['status'] !== 'confirmed') {
            flash_set('error', 'You can only rate the driver for a completed ride you actually took.');
            redirect('rider/bookings');
        }
        $alreadyRated = Rating::existsFor((int)$booking['ride_id'], Auth::id(), (int)$booking['driver_id']);
        require __DIR__ . '/../views/rider/rate_driver.php';
    }

    public function rateDriverStore(): void
    {
        Csrf::requireValid();
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $stars = (int)($_POST['stars'] ?? 0);
        $comment = trim((string)($_POST['comment'] ?? ''));

        $booking = Booking::find($bookingId);
        $valid = $booking && Booking::belongsToRider($bookingId, Auth::id())
            && $booking['ride_status'] === 'completed' && $booking['status'] === 'confirmed'
            && $stars >= 1 && $stars <= 5;

        if ($valid) {
            $ok = Rating::create((int)$booking['ride_id'], Auth::id(), (int)$booking['driver_id'], 'rider', $stars, $comment !== '' ? $comment : null);
            flash_set($ok ? 'success' : 'error', $ok ? 'Rating submitted.' : 'You already rated this driver for this ride.');
        } else {
            flash_set('error', 'Invalid rating request.');
        }
        redirect('rider/bookings');
    }

    public function profile(): void
    {
        $riderId = Auth::id();
        $ratings = Rating::forRatee($riderId);
        $avg = Rating::averageForRatee($riderId);
        require __DIR__ . '/../views/rider/profile.php';
    }
}
