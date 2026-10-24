<?php $pageTitle = 'Rate Riders'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-3xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <a href="/index.php?route=driver/rides" class="text-sm text-sky-dark hover:underline">&larr; Back to my rides</a>

    <div class="card mt-4">
        <h1 class="font-display text-xl font-bold"><?= e($ride['origin']) ?> &rarr; <?= e($ride['destination']) ?></h1>
        <p class="text-slateink text-sm mt-1 font-mono-tab"><?= format_datetime($ride['departure_at']) ?> &middot; Completed</p>
    </div>

    <?php if (empty($riders)): ?>
        <div class="card empty-state">No confirmed riders on this ride to rate.</div>
    <?php endif; ?>

    <?php foreach ($riders as $r): ?>
        <div class="card">
            <div class="card-head">
                <div>
                    <h2 class="text-base font-semibold"><?= e($r['rider_name']) ?></h2>
                    <p class="text-slateink text-xs mt-0.5"><?= (int)$r['seats_booked'] ?> seat(s) &middot; <?= money($r['total_fare']) ?></p>
                </div>
                <?php if (!empty($alreadyRated[$r['rider_id']])): ?>
                    <span class="badge badge-lime">Already rated</span>
                <?php endif; ?>
            </div>
            <?php if (empty($alreadyRated[$r['rider_id']])): ?>
                <form method="POST" action="/index.php?route=driver/ratings/store" class="space-y-3">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="ride_id" value="<?= (int)$ride['id'] ?>">
                    <input type="hidden" name="rider_id" value="<?= (int)$r['rider_id'] ?>">
                    <div>
                        <label class="field-label">Rating</label>
                        <div class="star-picker flex items-center gap-1 text-2xl">
                            <input type="hidden" name="stars" value="0" required>
                            <span class="star-pick cursor-pointer select-none">☆</span>
                            <span class="star-pick cursor-pointer select-none">☆</span>
                            <span class="star-pick cursor-pointer select-none">☆</span>
                            <span class="star-pick cursor-pointer select-none">☆</span>
                            <span class="star-pick cursor-pointer select-none">☆</span>
                        </div>
                    </div>
                    <div>
                        <label class="field-label">Comment (optional)</label>
                        <textarea name="comment" rows="2" class="field-textarea" placeholder="How was this rider?"></textarea>
                    </div>
                    <button type="submit" class="btn-accent btn-sm">Submit Rating</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
