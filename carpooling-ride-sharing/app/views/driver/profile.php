<?php $pageTitle = 'My Profile'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-3xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card">
        <div class="card-head">
            <div>
                <h1 class="font-display text-2xl font-bold"><?= e(Auth::name()) ?></h1>
                <p class="text-slateink text-sm mt-1">Driver</p>
            </div>
            <?php if ($avg): ?>
                <div class="text-right">
                    <div class="star-rating text-lg"><?= stars((int)round($avg['avg'])) ?></div>
                    <p class="text-xs text-slateink"><?= $avg['avg'] ?> avg &middot; <?= $avg['count'] ?> ratings</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Ratings received from riders</h2></div>
        <?php if (empty($ratings)): ?>
            <p class="empty-state">No ratings yet.</p>
        <?php endif; ?>
        <ul class="space-y-3">
            <?php foreach ($ratings as $rt): ?>
                <li class="border border-line rounded-lg p-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-medium"><?= e($rt['rater_name']) ?> <span class="text-slateink text-xs font-normal">&middot; <?= e($rt['origin']) ?> &rarr; <?= e($rt['destination']) ?></span></span>
                        <span class="star-rating"><?= stars((int)$rt['stars']) ?></span>
                    </div>
                    <?php if (!empty($rt['comment'])): ?><p class="text-slateink mt-1"><?= e($rt['comment']) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
