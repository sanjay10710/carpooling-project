<?php

require_once __DIR__ . '/../models/Vehicle.php';
require_once __DIR__ . '/../models/Ride.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/Rating.php';

class DriverController
{
    public function __construct()
    {
        Auth::requireRole('driver');
    }

    public function dashboard(): void
    {
        $driverId = Auth::id();
        $rides = Ride::forDriver($driverId);
        $upcoming = array_values(array_filter($rides, fn($r) => $r['status'] === 'scheduled'));
        $ongoing = array_values(array_filter($rides, fn($r) => $r['status'] === 'ongoing'));
        $completed = array_values(array_filter($rides, fn($r) => $r['status'] === 'completed'));
        $vehicles = Vehicle::forDriver($driverId);
        $avgRating = Rating::averageForRatee($driverId);
        require __DIR__ . '/../views/driver/dashboard.php';
    }

    // ---------------- Vehicles ----------------

    public function vehicles(): void
    {
        $vehicles = Vehicle::forDriver(Auth::id());
        require __DIR__ . '/../views/driver/vehicles.php';
    }

    public function vehicleNew(): void
    {
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/driver/vehicle_form.php';
    }

    public function vehicleStore(): void
    {
        Csrf::requireValid();
        [$errors, $data] = $this->validateVehicle($_POST);

        if (!empty($errors)) {
            $old = $_POST;
            require __DIR__ . '/../views/driver/vehicle_form.php';
            return;
        }

        Vehicle::create(Auth::id(), $data['make'], $data['model'], $data['color'], $data['plate'], $data['type'], $data['seats']);
        flash_set('success', 'Vehicle added.');
        redirect('driver/vehicles');
    }

    public function vehicleEdit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $vehicle = Vehicle::find($id);
        if (!$vehicle || !Vehicle::belongsToDriver($id, Auth::id())) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $errors = [];
        $old = $vehicle;
        require __DIR__ . '/../views/driver/vehicle_form.php';
    }

    public function vehicleUpdate(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (!Vehicle::belongsToDriver($id, Auth::id())) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        [$errors, $data] = $this->validateVehicle($_POST, $id);

        if (!empty($errors)) {
            $old = $_POST;
            $old['id'] = $id;
            require __DIR__ . '/../views/driver/vehicle_form.php';
            return;
        }

        Vehicle::update($id, Auth::id(), $data['make'], $data['model'], $data['color'], $data['plate'], $data['type'], $data['seats']);
        flash_set('success', 'Vehicle updated.');
        redirect('driver/vehicles');
    }

    public function vehicleDeactivate(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (Vehicle::belongsToDriver($id, Auth::id())) {
            Vehicle::setStatus($id, 'inactive');
            flash_set('success', 'Vehicle deactivated. It can no longer be used to post new rides.');
        }
        redirect('driver/vehicles');
    }

    public function vehicleActivate(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (Vehicle::belongsToDriver($id, Auth::id())) {
            Vehicle::setStatus($id, 'active');
            flash_set('success', 'Vehicle re-activated.');
        }
        redirect('driver/vehicles');
    }

    private function validateVehicle(array $post, ?int $excludeId = null): array
    {
        $v = new Validator();
        $v->required($post, 'make', 'Make')->maxLength($post, 'make', 60, 'Make')
          ->required($post, 'model', 'Model')->maxLength($post, 'model', 60, 'Model')
          ->required($post, 'color', 'Color')->maxLength($post, 'color', 30, 'Color')
          ->required($post, 'plate', 'Plate number')->maxLength($post, 'plate', 20, 'Plate number')
          ->required($post, 'type', 'Vehicle type')->inArray($post, 'type', Vehicle::TYPES, 'Vehicle type')
          ->required($post, 'seats', 'Total seats')->integerBetween($post, 'seats', 1, 8, 'Total seats');
        $errors = $v->errors();

        $plate = trim((string)($post['plate'] ?? ''));
        if (empty($errors) && Vehicle::plateExists($plate, $excludeId)) {
            $errors['plate'] = 'A vehicle with this plate number is already registered.';
        }

        $data = [
            'make' => trim((string)($post['make'] ?? '')),
            'model' => trim((string)($post['model'] ?? '')),
            'color' => trim((string)($post['color'] ?? '')),
            'plate' => $plate,
            'type' => $post['type'] ?? '',
            'seats' => (int)($post['seats'] ?? 0),
        ];

        return [$errors, $data];
    }

    // ---------------- Rides ----------------

    public function rides(): void
    {
        $rides = Ride::forDriver(Auth::id());
        require __DIR__ . '/../views/driver/rides.php';
    }

    public function rideNew(): void
    {
        $vehicles = Vehicle::activeForDriver(Auth::id());
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/driver/ride_form.php';
    }

    public function rideStore(): void
    {
        Csrf::requireValid();
        [$errors, $data] = $this->validateRide($_POST);

        if (!empty($errors)) {
            $vehicles = Vehicle::activeForDriver(Auth::id());
            $old = $_POST;
            require __DIR__ . '/../views/driver/ride_form.php';
            return;
        }

        Ride::create(Auth::id(), $data['vehicle_id'], $data['origin'], $data['destination'], $data['departure_at'], $data['seats'], $data['price'], $data['notes']);
        flash_set('success', 'Ride posted.');
        redirect('driver/rides');
    }

    public function rideEdit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $ride = Ride::find($id);
        if (!$ride || !Ride::belongsToDriver($id, Auth::id())) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        if ($ride['status'] !== 'scheduled') {
            flash_set('error', 'Only a scheduled ride (before it starts) can be edited.');
            redirect('driver/rides');
        }
        $vehicles = Vehicle::activeForDriver(Auth::id());
        $errors = [];
        $old = $ride;
        require __DIR__ . '/../views/driver/ride_form.php';
    }

    public function rideUpdate(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (!Ride::belongsToDriver($id, Auth::id())) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }

        [$errors, $data] = $this->validateRide($_POST);

        if (!empty($errors)) {
            $vehicles = Vehicle::activeForDriver(Auth::id());
            $old = $_POST;
            $old['id'] = $id;
            require __DIR__ . '/../views/driver/ride_form.php';
            return;
        }

        $ok = Ride::update($id, Auth::id(), $data['vehicle_id'], $data['origin'], $data['destination'], $data['departure_at'], $data['price'], $data['notes']);
        flash_set($ok ? 'success' : 'error', $ok ? 'Ride updated.' : 'Ride could not be updated (already started?).');
        redirect('driver/rides');
    }

    private function validateRide(array $post): array
    {
        $v = new Validator();
        $v->required($post, 'vehicle_id', 'Vehicle')
          ->required($post, 'origin', 'Origin')->maxLength($post, 'origin', 120, 'Origin')
          ->required($post, 'destination', 'Destination')->maxLength($post, 'destination', 120, 'Destination')
          ->required($post, 'departure_at', 'Departure date/time')->futureDatetime($post, 'departure_at', 'Departure date/time')
          ->required($post, 'seats', 'Seats offered')->integerBetween($post, 'seats', 1, 8, 'Seats offered')
          ->required($post, 'price', 'Price per seat')->decimalMin($post, 'price', 1, 'Price per seat')
          ->maxLength($post, 'notes', 255, 'Notes');
        $errors = $v->errors();

        $vehicleId = (int)($post['vehicle_id'] ?? 0);
        if (empty($errors) && !Vehicle::belongsToDriver($vehicleId, Auth::id())) {
            $errors['vehicle_id'] = 'Choose one of your own active vehicles.';
        }

        $seats = (int)($post['seats'] ?? 0);
        if (empty($errors)) {
            $vehicle = Vehicle::find($vehicleId);
            if ($vehicle && $vehicle['status'] !== 'active') {
                $errors['vehicle_id'] = 'That vehicle is deactivated and cannot be used to post a ride.';
            } elseif ($vehicle && $seats > (int)$vehicle['total_seats']) {
                $errors['seats'] = 'Seats offered cannot exceed the vehicle\'s total seats (' . $vehicle['total_seats'] . ').';
            }
        }

        $data = [
            'vehicle_id' => $vehicleId,
            'origin' => trim((string)($post['origin'] ?? '')),
            'destination' => trim((string)($post['destination'] ?? '')),
            'departure_at' => trim((string)($post['departure_at'] ?? '')),
            'seats' => $seats,
            'price' => (float)($post['price'] ?? 0),
            'notes' => trim((string)($post['notes'] ?? '')) !== '' ? trim((string)$post['notes']) : null,
        ];

        return [$errors, $data];
    }

    public function rideCancel(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (Ride::cancel($id, Auth::id())) {
            flash_set('success', 'Ride cancelled. Any confirmed bookings on it were released.');
        } else {
            flash_set('error', 'Ride could not be cancelled.');
        }
        redirect('driver/rides');
    }

    public function rideStart(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (Ride::updateStatus($id, Auth::id(), 'ongoing', ['scheduled'])) {
            flash_set('success', 'Ride marked ongoing.');
        } else {
            flash_set('error', 'Could not update ride status.');
        }
        redirect('driver/rides');
    }

    public function rideComplete(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (Ride::updateStatus($id, Auth::id(), 'completed', ['scheduled', 'ongoing'])) {
            flash_set('success', 'Ride marked completed. You can now rate your riders.');
        } else {
            flash_set('error', 'Could not update ride status.');
        }
        redirect('driver/rides');
    }

    public function roster(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $ride = Ride::find($id);
        if (!$ride || !Ride::belongsToDriver($id, Auth::id())) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        $roster = Booking::forRide($id);
        require __DIR__ . '/../views/driver/roster.php';
    }

    // ---------------- Ratings ----------------

    public function rateRiders(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $ride = Ride::find($id);
        if (!$ride || !Ride::belongsToDriver($id, Auth::id())) {
            http_response_code(404);
            require __DIR__ . '/../views/errors/404.php';
            return;
        }
        if ($ride['status'] !== 'completed') {
            flash_set('error', 'You can only rate riders once the ride is completed.');
            redirect('driver/rides');
        }
        $riders = Booking::confirmedRidersForRide($id);
        $alreadyRated = [];
        foreach ($riders as $r) {
            if (Rating::existsFor($id, Auth::id(), (int)$r['rider_id'])) {
                $alreadyRated[$r['rider_id']] = true;
            }
        }
        require __DIR__ . '/../views/driver/rate_riders.php';
    }

    public function rateRiderStore(): void
    {
        Csrf::requireValid();
        $rideId = (int)($_POST['ride_id'] ?? 0);
        $riderId = (int)($_POST['rider_id'] ?? 0);
        $stars = (int)($_POST['stars'] ?? 0);
        $comment = trim((string)($_POST['comment'] ?? ''));

        $ride = Ride::find($rideId);
        $valid = $ride && Ride::belongsToDriver($rideId, Auth::id()) && $ride['status'] === 'completed'
            && $stars >= 1 && $stars <= 5;

        if ($valid) {
            $confirmedRiderIds = array_column(Booking::confirmedRidersForRide($rideId), 'rider_id');
            if (!in_array($riderId, array_map('intval', $confirmedRiderIds), true)) {
                $valid = false;
            }
        }

        if ($valid) {
            $ok = Rating::create($rideId, Auth::id(), $riderId, 'driver', $stars, $comment !== '' ? $comment : null);
            flash_set($ok ? 'success' : 'error', $ok ? 'Rating submitted.' : 'You already rated this rider for this ride.');
        } else {
            flash_set('error', 'Invalid rating request.');
        }
        redirect('driver/rides/rate?id=' . $rideId);
    }

    public function profile(): void
    {
        $driverId = Auth::id();
        $ratings = Rating::forRatee($driverId);
        $avg = Rating::averageForRatee($driverId);
        require __DIR__ . '/../views/driver/profile.php';
    }
}
