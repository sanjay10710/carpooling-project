<?php $pageTitle = 'My Rides'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">My Rides</h1>
            <p class="text-slateink text-sm mt-1">Everything you've posted, in every status.</p>
        </div>
        <a href="/index.php?route=driver/rides/new" class="btn-accent">+ Post a Ride</a>
    </div>

    <div class="space-y-3">
        <?php if (empty($rides)): ?>
            <div class="card empty-state">You haven't posted a ride yet.</div>
        <?php endif; ?>
        <?php foreach ($rides as $r): ?>
            <div class="ride-card">
                <div class="card-head !mb-2">
                    <div class="route-strip">
                        <span class="route-dot"></span><span class="route-line"></span>
                        <span class="text-ink"><?= e($r['origin']) ?></span>
                        <span class="route-line"></span><span class="route-dot end"></span>
                        <span class="text-ink"><?= e($r['destination']) ?></span>
                    </div>
                    <span class="badge <?= status_badge_class($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-sm text-slateink mb-3">
                    <span class="font-mono-tab"><?= format_datetime($r['departure_at']) ?></span>
                    <span><?= e($r['make']) ?> <?= e($r['model']) ?> (<?= e($r['plate_number']) ?>)</span>
                    <span class="font-mono-tab"><?= (int)($r['seats_total'] - $r['seats_available']) ?>/<?= (int)$r['seats_total'] ?> booked</span>
                    <span class="font-mono-tab"><?= money($r['price_per_seat']) ?>/seat</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="/index.php?route=driver/rides/roster&id=<?= (int)$r['id'] ?>" class="btn-secondary btn-sm">Roster</a>
                    <?php if ($r['status'] === 'scheduled'): ?>
                        <a href="/index.php?route=driver/rides/edit&id=<?= (int)$r['id'] ?>" class="btn-secondary btn-sm">Edit</a>
                        <form method="POST" action="/index.php?route=driver/rides/start">
                            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="btn-secondary btn-sm">Mark Ongoing</button>
                        </form>
                        <form method="POST" action="/index.php?route=driver/rides/cancel" data-confirm="Cancel this ride? Confirmed bookings on it will be released.">
                            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="btn-danger btn-sm">Cancel</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($r['status'] === 'ongoing'): ?>
                        <form method="POST" action="/index.php?route=driver/rides/complete">
                            <?= Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" class="btn-accent btn-sm">Mark Completed</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($r['status'] === 'completed'): ?>
                        <a href="/index.php?route=driver/rides/rate&id=<?= (int)$r['id'] ?>" class="btn-accent btn-sm">Rate Riders</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
