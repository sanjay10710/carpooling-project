<?php $pageTitle = 'Admin Dashboard'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">Platform Dashboard</h1>
            <p class="text-slateink text-sm mt-1">Fleet-wide overview across every driver, rider and ride.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="stat-tile">
            <span class="stat-value"><?= $stats['drivers'] ?></span>
            <span class="stat-label">Drivers (<?= $stats['active_drivers'] ?> active)</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?= $stats['riders'] ?></span>
            <span class="stat-label">Riders (<?= $stats['active_riders'] ?> active)</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?= $stats['vehicles'] ?></span>
            <span class="stat-label">Vehicles</span>
        </div>
        <div class="stat-tile stat-tile-accent">
            <span class="stat-value"><?= $stats['bookings'] ?></span>
            <span class="stat-label">Confirmed bookings</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?= $stats['scheduled'] ?></span>
            <span class="stat-label">Scheduled rides</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?= $stats['completed'] ?></span>
            <span class="stat-label">Completed rides</span>
        </div>
        <div class="stat-tile stat-tile-accent">
            <span class="stat-value font-mono-tab"><?= money($stats['revenue']) ?></span>
            <span class="stat-label">Total fare volume</span>
        </div>
        <div class="stat-tile <?= $stats['open_reports'] > 0 ? 'stat-tile-alert' : '' ?>">
            <span class="stat-value"><?= $stats['open_reports'] ?></span>
            <span class="stat-label">Open reports</span>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Recent rides</h2>
            <a href="/index.php?route=admin/rides" class="text-sm font-semibold text-sky-dark hover:underline">View all &rarr;</a>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Route</th><th>Driver</th><th>Departure</th><th>Seats</th><th>Price/seat</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($recentRides)): ?>
                    <tr><td colspan="6" class="empty-state">No rides posted yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentRides as $r): ?>
                    <tr>
                        <td><a href="/index.php?route=admin/rides/show&id=<?= (int)$r['id'] ?>" class="text-sky-dark hover:underline"><?= e($r['origin']) ?> &rarr; <?= e($r['destination']) ?></a></td>
                        <td><?= e($r['driver_name']) ?></td>
                        <td class="font-mono-tab"><?= format_datetime($r['departure_at']) ?></td>
                        <td class="font-mono-tab"><?= (int)($r['seats_total'] - $r['seats_available']) ?>/<?= (int)$r['seats_total'] ?></td>
                        <td class="font-mono-tab"><?= money($r['price_per_seat']) ?></td>
                        <td><span class="badge <?= status_badge_class($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
