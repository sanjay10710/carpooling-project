<?php $pageTitle = 'Bookings'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">Bookings</h1>
            <p class="text-slateink text-sm mt-1">Every seat booking made on the platform.</p>
        </div>
    </div>

    <div class="card">
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Route</th><th>Rider</th><th>Driver</th><th>Departure</th><th>Seats</th><th>Total fare</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="7" class="empty-state">No bookings yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= e($b['origin']) ?> &rarr; <?= e($b['destination']) ?></td>
                        <td><?= e($b['rider_name']) ?></td>
                        <td><?= e($b['driver_name']) ?></td>
                        <td class="font-mono-tab"><?= format_datetime($b['departure_at']) ?></td>
                        <td class="font-mono-tab"><?= (int)$b['seats_booked'] ?></td>
                        <td class="font-mono-tab"><?= money($b['total_fare']) ?></td>
                        <td><span class="badge <?= status_badge_class($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
