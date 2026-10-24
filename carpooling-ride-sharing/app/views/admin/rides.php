<?php $pageTitle = 'Rides'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">Rides</h1>
            <p class="text-slateink text-sm mt-1">Platform-wide ride oversight.</p>
        </div>
        <div class="flex gap-2 text-sm flex-wrap">
            <?php $current = $_GET['status'] ?? ''; ?>
            <a href="/index.php?route=admin/rides" class="btn-secondary btn-sm <?= $current === '' ? 'border-sky text-sky-dark' : '' ?>">All</a>
            <a href="/index.php?route=admin/rides&status=scheduled" class="btn-secondary btn-sm <?= $current === 'scheduled' ? 'border-sky text-sky-dark' : '' ?>">Scheduled</a>
            <a href="/index.php?route=admin/rides&status=ongoing" class="btn-secondary btn-sm <?= $current === 'ongoing' ? 'border-sky text-sky-dark' : '' ?>">Ongoing</a>
            <a href="/index.php?route=admin/rides&status=completed" class="btn-secondary btn-sm <?= $current === 'completed' ? 'border-sky text-sky-dark' : '' ?>">Completed</a>
            <a href="/index.php?route=admin/rides&status=cancelled" class="btn-secondary btn-sm <?= $current === 'cancelled' ? 'border-sky text-sky-dark' : '' ?>">Cancelled</a>
        </div>
    </div>

    <div class="card">
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Route</th><th>Driver</th><th>Vehicle</th><th>Departure</th><th>Seats</th><th>Price/seat</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($rides)): ?>
                    <tr><td colspan="8" class="empty-state">No rides found.</td></tr>
                <?php endif; ?>
                <?php foreach ($rides as $r): ?>
                    <tr>
                        <td><a href="/index.php?route=admin/rides/show&id=<?= (int)$r['id'] ?>" class="text-sky-dark hover:underline"><?= e($r['origin']) ?> &rarr; <?= e($r['destination']) ?></a></td>
                        <td><?= e($r['driver_name']) ?></td>
                        <td><?= e($r['make']) ?> <?= e($r['model']) ?> (<?= e($r['plate_number']) ?>)</td>
                        <td class="font-mono-tab"><?= format_datetime($r['departure_at']) ?></td>
                        <td class="font-mono-tab"><?= (int)($r['seats_total'] - $r['seats_available']) ?>/<?= (int)$r['seats_total'] ?></td>
                        <td class="font-mono-tab"><?= money($r['price_per_seat']) ?></td>
                        <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                        <td>
                            <?php if (in_array($r['status'], ['scheduled', 'ongoing'], true)): ?>
                                <form method="POST" action="/index.php?route=admin/rides/cancel" class="inline" data-confirm="Cancel this ride? Any confirmed bookings on it will be released.">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button type="submit" class="btn-danger btn-sm">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
