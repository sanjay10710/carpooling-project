<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Vehicle.php';
require_once __DIR__ . '/../models/Ride.php';
require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/Rating.php';
require_once __DIR__ . '/../models/Report.php';

class AdminController
{
    public function __construct()
    {
        Auth::requireRole('super_admin');
    }

    public function dashboard(): void
    {
        $stats = [
            'drivers'        => User::countByRole('driver'),
            'riders'         => User::countByRole('rider'),
            'active_drivers' => User::countActiveByRole('driver'),
            'active_riders'  => User::countActiveByRole('rider'),
            'vehicles'       => Vehicle::count(),
            'rides'          => Ride::count(),
            'scheduled'      => Ride::countByStatus('scheduled'),
            'completed'      => Ride::countByStatus('completed'),
            'cancelled'      => Ride::countByStatus('cancelled'),
            'bookings'       => Booking::count(),
            'revenue'        => Booking::totalRevenue(),
            'ratings'        => Rating::count(),
            'open_reports'   => Report::countOpen(),
        ];
        $recentRides = array_slice(Ride::all(), 0, 8);
        require __DIR__ . '/../views/admin/dashboard.php';
    }

    public function users(): void
    {
        $role = $_GET['role'] ?? '';
        $users = $role !== '' ? User::all($role) : User::all();
        require __DIR__ . '/../views/admin/users.php';
    }

    public function userStatus(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['active', 'inactive'], true)) {
            User::setStatus($id, $status);
            flash_set('success', 'User status updated.');
        }
        redirect('admin/users');
    }

    public function vehicles(): void
    {
        $vehicles = Vehicle::all();
        require __DIR__ . '/../views/admin/vehicles.php';
    }

    public function vehicleStatus(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['active', 'inactive'], true)) {
            Vehicle::setStatus($id, $status);
            flash_set('success', 'Vehicle status updated.');
        }
        redirect('admin/vehicles');
    }

    public function rides(): void
    {
        $status = $_GET['status'] ?? '';
        $rides = Ride::all($status);
        require __DIR__ . '/../views/admin/rides.php';
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
        $roster = Booking::forRide($id);
        $ratings = Rating::forRide($id);
        require __DIR__ . '/../views/admin/ride_show.php';
    }

    public function rideCancel(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        if (Ride::adminCancel($id)) {
            flash_set('success', 'Ride cancelled and its confirmed bookings released.');
        } else {
            flash_set('error', 'Ride could not be cancelled (already completed/cancelled?).');
        }
        redirect('admin/rides');
    }

    public function bookings(): void
    {
        $bookings = Booking::all();
        require __DIR__ . '/../views/admin/bookings.php';
    }

    public function ratings(): void
    {
        $ratings = Rating::all();
        require __DIR__ . '/../views/admin/ratings.php';
    }

    public function reports(): void
    {
        $status = $_GET['status'] ?? '';
        $reports = Report::all($status);
        require __DIR__ . '/../views/admin/reports.php';
    }

    public function reportResolve(): void
    {
        Csrf::requireValid();
        $id = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $status = $action === 'dismiss' ? 'dismissed' : 'resolved';
        if (Report::resolve($id, Auth::id(), $status)) {
            flash_set('success', 'Report ' . $status . '.');
        } else {
            flash_set('error', 'Report could not be updated (already closed?).');
        }
        redirect('admin/reports');
    }
}
