<?php $pageTitle = 'Search Rides'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">Search Rides</h1>
            <p class="text-slateink text-sm mt-1">Matches by origin/destination text and date &mdash; no maps required.</p>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="/index.php" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <input type="hidden" name="route" value="rider/search">
            <div>
                <label class="field-label">From</label>
                <input type="text" name="origin" value="<?= e($origin) ?>" placeholder="e.g. Koramangala" class="field-input">
            </div>
            <div>
                <label class="field-label">To</label>
                <input type="text" name="destination" value="<?= e($destination) ?>" placeholder="e.g. Whitefield" class="field-input">
            </div>
            <div>
                <label class="field-label">Date</label>
                <input type="date" name="date" value="<?= e($date) ?>" class="field-input">
            </div>
            <button type="submit" class="btn-primary">Search</button>
        </form>
    </div>

    <div class="space-y-3">
        <?php if (empty($rides)): ?>
            <div class="card empty-state">No matching rides. Try widening your search.</div>
        <?php endif; ?>
        <?php foreach ($rides as $r): ?>
            <div class="ride-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="route-strip mb-2">
                            <span class="route-dot"></span><span class="route-line"></span>
                            <span class="text-ink"><?= e($r['origin']) ?></span>
                            <span class="route-line"></span><span class="route-dot end"></span>
                            <span class="text-ink"><?= e($r['destination']) ?></span>
                        </div>
                        <div class="flex flex-wrap items-center gap-4 text-sm text-slateink">
                            <span class="font-mono-tab"><?= format_datetime($r['departure_at']) ?></span>
                            <span><?= e($r['driver_name']) ?> &middot; <?= e($r['make']) ?> <?= e($r['model']) ?> (<?= e(ucfirst($r['vehicle_type'])) ?>)</span>
                            <span class="font-mono-tab"><?= (int)$r['seats_available'] ?> seat(s) left</span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-mono-tab text-lg font-bold text-sky-dark"><?= money($r['price_per_seat']) ?></p>
                        <p class="text-xs text-slateink mb-2">per seat</p>
                        <a href="/index.php?route=rider/rides/show&id=<?= (int)$r['id'] ?>" class="btn-accent btn-sm">View &amp; Book</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
