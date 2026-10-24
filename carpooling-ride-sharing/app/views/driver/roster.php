<?php $pageTitle = 'Ride Roster'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-4xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <a href="/index.php?route=driver/rides" class="text-sm text-sky-dark hover:underline">&larr; Back to my rides</a>

    <div class="card mt-4">
        <div class="card-head">
            <div>
                <h1 class="font-display text-xl font-bold"><?= e($ride['origin']) ?> &rarr; <?= e($ride['destination']) ?></h1>
                <p class="text-slateink text-sm mt-1 font-mono-tab"><?= format_datetime($ride['departure_at']) ?></p>
            </div>
            <span class="badge <?= status_badge_class($ride['status']) ?>"><?= e(ucfirst($ride['status'])) ?></span>
        </div>
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div><p class="text-slateink text-xs uppercase font-semibold">Vehicle</p><p class="mt-1"><?= e($ride['make']) ?> <?= e($ride['model']) ?> (<?= e($ride['plate_number']) ?>)</p></div>
            <div><p class="text-slateink text-xs uppercase font-semibold">Seats</p><p class="font-mono-tab mt-1"><?= (int)($ride['seats_total'] - $ride['seats_available']) ?>/<?= (int)$ride['seats_total'] ?> booked</p></div>
            <div><p class="text-slateink text-xs uppercase font-semibold">Price/seat</p><p class="font-mono-tab mt-1"><?= money($ride['price_per_seat']) ?></p></div>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Riders (<?= count($roster) ?>)</h2></div>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Rider</th><th>Contact</th><th>Seats</th><th>Total fare</th><th>Booked at</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($roster)): ?>
                    <tr><td colspan="6" class="empty-state">No one has booked this ride yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($roster as $b): ?>
                    <tr>
                        <td class="font-medium"><?= e($b['rider_name']) ?></td>
                        <td class="text-xs text-slateink"><?= e($b['rider_email']) ?><br><?= e($b['rider_phone']) ?></td>
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
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
