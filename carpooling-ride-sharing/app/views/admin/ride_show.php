<?php $pageTitle = 'Ride Detail'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-4xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <a href="/index.php?route=admin/rides" class="text-sm text-sky-dark hover:underline">&larr; Back to rides</a>

    <div class="card mt-4">
        <div class="card-head">
            <div>
                <h1 class="font-display text-xl font-bold"><?= e($ride['origin']) ?> &rarr; <?= e($ride['destination']) ?></h1>
                <p class="text-slateink text-sm mt-1">Driver: <?= e($ride['driver_name']) ?> (<?= e($ride['driver_email']) ?>)</p>
            </div>
            <span class="badge <?= status_badge_class($ride['status']) ?>"><?= e(ucfirst($ride['status'])) ?></span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><p class="text-slateink text-xs uppercase font-semibold">Departure</p><p class="font-mono-tab mt-1"><?= format_datetime($ride['departure_at']) ?></p></div>
            <div><p class="text-slateink text-xs uppercase font-semibold">Vehicle</p><p class="mt-1"><?= e($ride['make']) ?> <?= e($ride['model']) ?> (<?= e($ride['plate_number']) ?>)</p></div>
            <div><p class="text-slateink text-xs uppercase font-semibold">Seats</p><p class="font-mono-tab mt-1"><?= (int)($ride['seats_total'] - $ride['seats_available']) ?>/<?= (int)$ride['seats_total'] ?> booked</p></div>
            <div><p class="text-slateink text-xs uppercase font-semibold">Price/seat</p><p class="font-mono-tab mt-1"><?= money($ride['price_per_seat']) ?></p></div>
        </div>
        <?php if (!empty($ride['notes'])): ?>
            <p class="text-sm text-slateink mt-4 border-t border-line pt-4"><?= e($ride['notes']) ?></p>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-head"><h2>Bookings (<?= count($roster) ?>)</h2></div>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Rider</th><th>Seats</th><th>Total fare</th><th>Booked at</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($roster)): ?>
                    <tr><td colspan="5" class="empty-state">No bookings on this ride.</td></tr>
                <?php endif; ?>
                <?php foreach ($roster as $b): ?>
                    <tr>
                        <td><?= e($b['rider_name']) ?> <span class="text-slateink text-xs">(<?= e($b['rider_email']) ?>)</span></td>
                        <td class="font-mono-tab"><?= (int)$b['seats_booked'] ?></td>
                        <td class="font-mono-tab"><?= money($b['total_fare']) ?></td>
                        <td class="font-mono-tab"><?= format_datetime($b['booked_at']) ?></td>
                        <td><span class="badge <?= status_badge_class($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($ratings)): ?>
    <div class="card">
        <div class="card-head"><h2>Ratings for this ride</h2></div>
        <ul class="space-y-3">
            <?php foreach ($ratings as $rt): ?>
                <li class="border border-line rounded-lg p-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span><?= e($rt['rater_name']) ?> &rarr; <?= e($rt['ratee_name']) ?> <span class="text-slateink text-xs">(as <?= e($rt['rater_role']) ?>)</span></span>
                        <span class="star-rating"><?= stars((int)$rt['stars']) ?></span>
                    </div>
                    <?php if (!empty($rt['comment'])): ?><p class="text-slateink mt-1"><?= e($rt['comment']) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
