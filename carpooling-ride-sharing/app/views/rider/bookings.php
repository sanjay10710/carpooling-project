<?php $pageTitle = 'My Bookings'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-5xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">My Bookings</h1>
            <p class="text-slateink text-sm mt-1">Every seat you've booked, across every ride.</p>
        </div>
        <a href="/index.php?route=rider/search" class="btn-accent">Search Rides</a>
    </div>

    <div class="space-y-3">
        <?php if (empty($bookings)): ?>
            <div class="card empty-state">No bookings yet.</div>
        <?php endif; ?>
        <?php foreach ($bookings as $b): ?>
            <div class="ride-card">
                <div class="card-head !mb-2">
                    <div class="route-strip">
                        <span class="route-dot"></span><span class="route-line"></span>
                        <span class="text-ink"><?= e($b['origin']) ?></span>
                        <span class="route-line"></span><span class="route-dot end"></span>
                        <span class="text-ink"><?= e($b['destination']) ?></span>
                    </div>
                    <span class="badge <?= status_badge_class($b['status'] === 'cancelled' ? 'cancelled' : $b['ride_status']) ?>">
                        <?= e(ucfirst($b['status'] === 'cancelled' ? 'cancelled' : $b['ride_status'])) ?>
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-sm text-slateink mb-3">
                    <span class="font-mono-tab"><?= format_datetime($b['departure_at']) ?></span>
                    <span>Driver: <?= e($b['driver_name']) ?></span>
                    <span class="font-mono-tab"><?= (int)$b['seats_booked'] ?> seat(s) &middot; <?= money($b['total_fare']) ?></span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php if ($b['status'] === 'confirmed' && in_array($b['ride_status'], ['scheduled'], true)): ?>
                        <form method="POST" action="/index.php?route=rider/bookings/cancel" data-confirm="Cancel this booking?">
                            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                            <button type="submit" class="btn-danger btn-sm">Cancel Booking</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($b['status'] === 'confirmed' && $b['ride_status'] === 'completed'): ?>
                        <a href="/index.php?route=rider/rides/rate&booking_id=<?= (int)$b['id'] ?>" class="btn-accent btn-sm">Rate Driver</a>
                    <?php endif; ?>
                    <a href="/index.php?route=reports/new&ride_id=<?= (int)$b['ride_id'] ?>" class="btn-secondary btn-sm">Report Issue</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
