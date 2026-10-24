<?php $pageTitle = 'Rider Dashboard'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">Welcome back, <?= e(explode(' ', Auth::name())[0]) ?></h1>
            <p class="text-slateink text-sm mt-1">
                <?php if ($avgRating): ?>
                    <span class="star-rating"><?= stars((int)round($avgRating['avg'])) ?></span> <?= $avgRating['avg'] ?> avg (<?= $avgRating['count'] ?> ratings)
                <?php else: ?>
                    No ratings yet.
                <?php endif; ?>
            </p>
        </div>
        <a href="/index.php?route=rider/search" class="btn-accent">Search Rides</a>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-8">
        <div class="stat-tile"><span class="stat-value"><?= count($upcoming) ?></span><span class="stat-label">Upcoming bookings</span></div>
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= count($past) ?></span><span class="stat-label">Completed rides</span></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Upcoming bookings</h2>
            <a href="/index.php?route=rider/bookings" class="text-sm font-semibold text-sky-dark hover:underline">View all &rarr;</a>
        </div>
        <?php if (empty($upcoming)): ?>
            <p class="empty-state">No upcoming bookings. <a href="/index.php?route=rider/search" class="text-sky-dark font-semibold hover:underline">Search for a ride</a>.</p>
        <?php endif; ?>
        <div class="space-y-3">
            <?php foreach ($upcoming as $b): ?>
                <div class="ride-card">
                    <div class="route-strip mb-2">
                        <span class="route-dot"></span><span class="route-line"></span>
                        <span class="text-ink"><?= e($b['origin']) ?></span>
                        <span class="route-line"></span><span class="route-dot end"></span>
                        <span class="text-ink"><?= e($b['destination']) ?></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-slateink">
                        <span class="font-mono-tab"><?= format_datetime($b['departure_at']) ?></span>
                        <span>Driver: <?= e($b['driver_name']) ?></span>
                        <span class="font-mono-tab"><?= (int)$b['seats_booked'] ?> seat(s) &middot; <?= money($b['total_fare']) ?></span>
                        <span class="badge <?= status_badge_class($b['ride_status']) ?>"><?= e(ucfirst($b['ride_status'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
