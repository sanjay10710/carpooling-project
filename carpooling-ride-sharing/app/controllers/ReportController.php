<?php

require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Ride.php';
require_once __DIR__ . '/../models/User.php';

/**
 * Filing a report is available to both Driver and Rider personas -- kept as
 * one small shared controller (per the same "shared screen, role-checked
 * inside" reasoning as sibling projects in this suite) rather than two
 * near-identical copies in DriverController/RiderController.
 */
class ReportController
{
    public function __construct()
    {
        Auth::requireRole('driver', 'rider');
    }

    public function newForm(): void
    {
        $rideId = isset($_GET['ride_id']) ? (int)$_GET['ride_id'] : null;
        $ride = $rideId ? Ride::find($rideId) : null;
        $errors = [];
        require __DIR__ . '/../views/reports/new.php';
    }

    public function store(): void
    {
        Csrf::requireValid();

        $rideId = !empty($_POST['ride_id']) ? (int)$_POST['ride_id'] : null;
        $reportedEmail = trim((string)($_POST['reported_email'] ?? ''));
        $reason = trim((string)($_POST['reason'] ?? ''));
        $details = trim((string)($_POST['details'] ?? ''));

        $v = new Validator();
        $v->required($_POST, 'reason', 'Reason')->maxLength($_POST, 'reason', 150, 'Reason')
          ->maxLength($_POST, 'details', 2000, 'Details');
        $errors = $v->errors();

        $reportedUserId = null;
        if ($reportedEmail !== '') {
            $reportedUser = User::findByEmail($reportedEmail);
            if (!$reportedUser) {
                $errors['reported_email'] = 'No account found with that email.';
            } else {
                $reportedUserId = (int)$reportedUser['id'];
            }
        }

        if (!empty($errors)) {
            $ride = $rideId ? Ride::find($rideId) : null;
            require __DIR__ . '/../views/reports/new.php';
            return;
        }

        Report::create(Auth::id(), $reportedUserId, $rideId, $reason, $details !== '' ? $details : null);
        flash_set('success', 'Report submitted. The platform admin will review it.');
        redirect(Auth::dashboardRoute(Auth::role()));
    }
}
