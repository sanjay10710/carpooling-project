<?php $pageTitle = 'Ride Details'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-3xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <a href="/index.php?route=rider/search" class="text-sm text-sky-dark hover:underline">&larr; Back to search</a>

    <div class="card mt-4">
        <div class="card-head">
            <div>
                <div class="route-strip mb-2">
                    <span class="route-dot"></span><span class="route-line"></span>
                    <span class="text-ink text-base"><?= e($ride['origin']) ?></span>
                    <span class="route-line"></span><span class="route-dot end"></span>
                    <span class="text-ink text-base"><?= e($ride['destination']) ?></span>
                </div>
                <p class="text-slateink text-sm font-mono-tab"><?= format_datetime($ride['departure_at']) ?></p>
            </div>
            <span class="badge <?= status_badge_class($ride['status']) ?>"><?= e(ucfirst($ride['status'])) ?></span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm border-t border-line pt-4">
            <div><p class="text-slateink text-xs uppercase font-semibold">Driver</p><p class="mt-1"><?= e($ride['driver_name']) ?></p></div>
            <div><p class="text-slateink text-xs uppercase font-semibold">Vehicle</p><p class="mt-1"><?= e($ride['make']) ?> <?= e($ride['model']) ?> (<?= e(ucfirst($ride['vehicle_type'])) ?>)</p></div>
            <div><p class="text-slateink text-xs uppercase font-semibold">Seats left</p><p class="font-mono-tab mt-1"><?= (int)$ride['seats_available'] ?> of <?= (int)$ride['seats_total'] ?></p></div>
            <div><p class="text-slateink text-xs uppercase font-semibold">Price/seat</p><p class="font-mono-tab mt-1"><?= money($ride['price_per_seat']) ?></p></div>
        </div>
        <?php if (!empty($ride['notes'])): ?>
            <p class="text-sm text-slateink mt-4 border-t border-line pt-4"><?= e($ride['notes']) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($ride['status'] === 'scheduled' && $ride['seats_available'] > 0): ?>
    <div class="card">
        <div class="card-head"><h2>Book seats</h2></div>
        <?php if (!empty($errors['booking'])): ?>
            <div class="alert alert-error"><?= e($errors['booking']) ?></div>
        <?php endif; ?>
        <form method="POST" action="/index.php?route=rider/bookings/store" class="space-y-4">
            <?= Csrf::field() ?>
            <input type="hidden" name="ride_id" value="<?= (int)$ride['id'] ?>">
            <div class="grid grid-cols-2 gap-4 items-end">
                <div>
                    <label class="field-label">Number of seats</label>
                    <input type="number" id="booking-seats" name="seats" min="1" max="<?= (int)$ride['seats_available'] ?>" value="1"
                           data-price="<?= (float)$ride['price_per_seat'] ?>" class="field-input" required>
                </div>
                <div class="text-right">
                    <p class="text-xs text-slateink uppercase font-semibold">Total fare</p>
                    <p id="booking-total" class="font-mono-tab text-2xl font-bold text-sky-dark"><?= money($ride['price_per_seat']) ?></p>
                </div>
            </div>
            <p class="field-hint">Fare = seats &times; price per seat. The server re-checks seats availability at the moment you submit, so this never overbooks even if two riders book at the same time.</p>
            <button type="submit" class="btn-accent w-full">Confirm Booking</button>
        </form>
    </div>
    <?php elseif ($ride['status'] === 'scheduled'): ?>
        <div class="card empty-state">This ride is fully booked.</div>
    <?php else: ?>
        <div class="card empty-state">This ride is no longer open for booking.</div>
    <?php endif; ?>

    <div class="card">
        <a href="/index.php?route=reports/new&ride_id=<?= (int)$ride['id'] ?>" class="text-sm text-slateink hover:text-rose hover:underline">Report an issue with this ride</a>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
