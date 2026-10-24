<?php
/** @var string|null $currentNav */
$role = Auth::role();
$navItems = [];
if ($role === 'super_admin') {
    $navItems = [
        'admin/dashboard' => 'Dashboard',
        'admin/users'     => 'Users',
        'admin/vehicles'  => 'Vehicles',
        'admin/rides'     => 'Rides',
        'admin/bookings'  => 'Bookings',
        'admin/reports'   => 'Reports',
        'admin/ratings'   => 'Ratings',
    ];
} elseif ($role === 'driver') {
    $navItems = [
        'driver/dashboard' => 'Dashboard',
        'driver/vehicles'  => 'My Vehicles',
        'driver/rides'     => 'My Rides',
        'driver/profile'   => 'Profile',
    ];
} elseif ($role === 'rider') {
    $navItems = [
        'rider/dashboard' => 'Dashboard',
        'rider/search'    => 'Search Rides',
        'rider/bookings'  => 'My Bookings',
        'rider/profile'   => 'Profile',
    ];
}
$currentRoute = $_GET['route'] ?? '';
?>
<nav class="bg-ink sticky top-0 z-30 shadow-lg shadow-ink/10">
    <div class="max-w-6xl mx-auto px-4 flex items-center justify-between h-16">
        <a href="/index.php?route=<?= e(Auth::dashboardRoute($role ?? '')) ?>" class="flex items-center gap-2.5 shrink-0">
            <span class="w-9 h-9 rounded-lg bg-sky flex items-center justify-center shrink-0">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="#F7F9FB"/>
                    <circle cx="12" cy="9" r="2.6" fill="#84CC16"/>
                </svg>
            </span>
            <span class="font-display font-bold text-white text-lg tracking-tight hidden sm:inline">TransitPool</span>
        </a>
        <div class="flex-1 flex items-center gap-1 text-sm font-medium overflow-x-auto px-3 no-scrollbar">
            <?php foreach ($navItems as $route => $label): ?>
                <a href="/index.php?route=<?= e($route) ?>"
                   class="nav-link px-3 py-2 rounded-lg whitespace-nowrap <?= $currentRoute === $route ? 'nav-link-active' : '' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <span class="hidden md:flex flex-col items-end leading-tight">
                <span class="text-white text-sm font-semibold"><?= e(Auth::name()) ?></span>
                <span class="text-white/50 text-xs capitalize"><?= e(str_replace('_', ' ', $role ?? '')) ?></span>
            </span>
            <form method="POST" action="/index.php?route=logout">
                <?= Csrf::field() ?>
                <button type="submit" class="btn-ghost-dark btn-sm">Logout</button>
            </form>
        </div>
    </div>
    <div class="route-dashline"></div>
</nav>
