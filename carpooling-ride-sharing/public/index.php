<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $assetPath = __DIR__ . $requestPath;
    if (!str_ends_with($requestPath, '.php') && is_file($assetPath)) {
        return false;
    }
}

date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', '1');
error_reporting(E_ALL);

// A distinct session name keeps this app's session isolated from sibling
// projects in the same suite -- PHP's default "PHPSESSID" cookie isn't
// port-scoped on 127.0.0.1/localhost, so two suite projects running their
// dev servers at once would otherwise share one session (and, since the
// default file-based session handler keys purely by session ID with no
// per-app namespace, actually share session *data* too).
session_name('carpooling_ride_sharing_sess');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Router.php';

require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Vehicle.php';
require_once __DIR__ . '/../app/models/Ride.php';
require_once __DIR__ . '/../app/models/Booking.php';
require_once __DIR__ . '/../app/models/Rating.php';
require_once __DIR__ . '/../app/models/Report.php';

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/DriverController.php';
require_once __DIR__ . '/../app/controllers/RiderController.php';
require_once __DIR__ . '/../app/controllers/ReportController.php';

$router = new Router();

// ---- Auth ----
$router->get('login', fn() => (new AuthController())->showLogin());
$router->post('login', fn() => (new AuthController())->login());
$router->get('register', fn() => (new AuthController())->showRegister());
$router->post('register', fn() => (new AuthController())->register());
$router->post('logout', fn() => (new AuthController())->logout());

// ---- Super Admin ----
$router->get('admin/dashboard', fn() => (new AdminController())->dashboard());
$router->get('admin/users', fn() => (new AdminController())->users());
$router->post('admin/users/status', fn() => (new AdminController())->userStatus());
$router->get('admin/vehicles', fn() => (new AdminController())->vehicles());
$router->post('admin/vehicles/status', fn() => (new AdminController())->vehicleStatus());
$router->get('admin/rides', fn() => (new AdminController())->rides());
$router->get('admin/rides/show', fn() => (new AdminController())->rideShow());
$router->post('admin/rides/cancel', fn() => (new AdminController())->rideCancel());
$router->get('admin/bookings', fn() => (new AdminController())->bookings());
$router->get('admin/ratings', fn() => (new AdminController())->ratings());
$router->get('admin/reports', fn() => (new AdminController())->reports());
$router->post('admin/reports/resolve', fn() => (new AdminController())->reportResolve());

// ---- Driver ----
$router->get('driver/dashboard', fn() => (new DriverController())->dashboard());
$router->get('driver/vehicles', fn() => (new DriverController())->vehicles());
$router->get('driver/vehicles/new', fn() => (new DriverController())->vehicleNew());
$router->post('driver/vehicles/store', fn() => (new DriverController())->vehicleStore());
$router->get('driver/vehicles/edit', fn() => (new DriverController())->vehicleEdit());
$router->post('driver/vehicles/update', fn() => (new DriverController())->vehicleUpdate());
$router->post('driver/vehicles/deactivate', fn() => (new DriverController())->vehicleDeactivate());
$router->post('driver/vehicles/activate', fn() => (new DriverController())->vehicleActivate());
$router->get('driver/rides', fn() => (new DriverController())->rides());
$router->get('driver/rides/new', fn() => (new DriverController())->rideNew());
$router->post('driver/rides/store', fn() => (new DriverController())->rideStore());
$router->get('driver/rides/edit', fn() => (new DriverController())->rideEdit());
$router->post('driver/rides/update', fn() => (new DriverController())->rideUpdate());
$router->post('driver/rides/cancel', fn() => (new DriverController())->rideCancel());
$router->post('driver/rides/start', fn() => (new DriverController())->rideStart());
$router->post('driver/rides/complete', fn() => (new DriverController())->rideComplete());
$router->get('driver/rides/roster', fn() => (new DriverController())->roster());
$router->get('driver/rides/rate', fn() => (new DriverController())->rateRiders());
$router->post('driver/ratings/store', fn() => (new DriverController())->rateRiderStore());
$router->get('driver/profile', fn() => (new DriverController())->profile());

// ---- Rider ----
$router->get('rider/dashboard', fn() => (new RiderController())->dashboard());
$router->get('rider/search', fn() => (new RiderController())->search());
$router->get('rider/rides/show', fn() => (new RiderController())->rideShow());
$router->post('rider/bookings/store', fn() => (new RiderController())->bookingStore());
$router->get('rider/bookings', fn() => (new RiderController())->bookings());
$router->post('rider/bookings/cancel', fn() => (new RiderController())->bookingCancel());
$router->get('rider/rides/rate', fn() => (new RiderController())->rateDriver());
$router->post('rider/ratings/store', fn() => (new RiderController())->rateDriverStore());
$router->get('rider/profile', fn() => (new RiderController())->profile());

// ---- Reports (shared: driver + rider) ----
$router->get('reports/new', fn() => (new ReportController())->newForm());
$router->post('reports/store', fn() => (new ReportController())->store());

$route = $_GET['route'] ?? '';

if ($route === '') {
    if (Auth::check()) {
        redirect(Auth::dashboardRoute(Auth::role()));
    }
    redirect('login');
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $route);
