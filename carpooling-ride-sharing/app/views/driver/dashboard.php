<?php $pageTitle = 'Driver Dashboard'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
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
        <a href="/index.php?route=driver/rides/new" class="btn-accent">+ Post a Ride</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="stat-tile"><span class="stat-value"><?= count($upcoming) ?></span><span class="stat-label">Upcoming rides</span></div>
        <div class="stat-tile stat-tile-accent"><span class="stat-value"><?= count($ongoing) ?></span><span class="stat-label">Ongoing rides</span></div>
        <div class="stat-tile"><span class="stat-value"><?= count($completed) ?></span><span class="stat-label">Completed rides</span></div>
        <div class="stat-tile"><span class="stat-value"><?= count($vehicles) ?></span><span class="stat-label">Registered vehicles</span></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Upcoming rides</h2>
            <a href="/index.php?route=driver/rides" class="text-sm font-semibold text-sky-dark hover:underline">View all &rarr;</a>
        </div>
        <?php if (empty($upcoming) && empty($ongoing)): ?>
            <p class="empty-state">No upcoming rides. <a href="/index.php?route=driver/rides/new" class="text-sky-dark font-semibold hover:underline">Post one now</a>.</p>
        <?php endif; ?>
        <div class="space-y-3">
            <?php foreach (array_merge($ongoing, $upcoming) as $r): ?>
                <div class="ride-card">
                    <div class="route-strip mb-2">
                        <span class="route-dot"></span><span class="route-line"></span>
                        <span class="text-ink"><?= e($r['origin']) ?></span>
                        <span class="route-line"></span><span class="route-dot end"></span>
                        <span class="text-ink"><?= e($r['destination']) ?></span>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-slateink">
                        <span class="font-mono-tab"><?= format_datetime($r['departure_at']) ?></span>
                        <span class="font-mono-tab"><?= (int)($r['seats_total'] - $r['seats_available']) ?>/<?= (int)$r['seats_total'] ?> seats booked</span>
                        <span class="badge <?= status_badge_class($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                        <a href="/index.php?route=driver/rides/roster&id=<?= (int)$r['id'] ?>" class="text-sky-dark font-semibold hover:underline">Roster &rarr;</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
