<?php $pageTitle = 'Ratings'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">Ratings</h1>
            <p class="text-slateink text-sm mt-1">Post-ride ratings between drivers and riders, both directions.</p>
        </div>
    </div>

    <div class="card">
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Route</th><th>From</th><th>To</th><th>Role</th><th>Stars</th><th>Comment</th></tr></thead>
                <tbody>
                <?php if (empty($ratings)): ?>
                    <tr><td colspan="6" class="empty-state">No ratings yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($ratings as $rt): ?>
                    <tr>
                        <td><?= e($rt['origin']) ?> &rarr; <?= e($rt['destination']) ?></td>
                        <td><?= e($rt['rater_name']) ?></td>
                        <td><?= e($rt['ratee_name']) ?></td>
                        <td class="capitalize"><?= e($rt['rater_role']) ?></td>
                        <td class="star-rating"><?= stars((int)$rt['stars']) ?></td>
                        <td class="text-slateink"><?= e($rt['comment'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
