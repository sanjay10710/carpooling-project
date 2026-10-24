<?php $pageTitle = 'Rate Driver'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <a href="/index.php?route=rider/bookings" class="text-sm text-sky-dark hover:underline">&larr; Back to my bookings</a>

    <div class="card mt-4">
        <div class="card-head">
            <div>
                <h1 class="font-display text-xl font-bold"><?= e($booking['origin']) ?> &rarr; <?= e($booking['destination']) ?></h1>
                <p class="text-slateink text-sm mt-1 font-mono-tab"><?= format_datetime($booking['departure_at']) ?></p>
            </div>
        </div>

        <?php if ($alreadyRated): ?>
            <p class="empty-state">You've already rated the driver for this ride.</p>
        <?php else: ?>
            <form method="POST" action="/index.php?route=rider/ratings/store" class="space-y-4">
                <?= Csrf::field() ?>
                <input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>">
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
                    <textarea name="comment" rows="3" class="field-textarea" placeholder="How was the ride?"></textarea>
                </div>
                <button type="submit" class="btn-accent w-full">Submit Rating</button>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
