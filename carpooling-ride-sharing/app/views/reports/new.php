<?php $pageTitle = 'Report an Issue'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-xl mx-auto px-4 py-8">
    <a href="/index.php?route=<?= e(Auth::dashboardRoute(Auth::role())) ?>" class="text-sm text-sky-dark hover:underline">&larr; Back to dashboard</a>

    <div class="card mt-4">
        <div class="card-head"><h1 class="font-display text-xl font-bold">Report an Issue</h1></div>
        <?php if ($ride): ?>
            <p class="text-sm text-slateink mb-4">About ride: <span class="font-medium text-ink"><?= e($ride['origin']) ?> &rarr; <?= e($ride['destination']) ?></span></p>
        <?php endif; ?>
        <form method="POST" action="/index.php?route=reports/store" class="space-y-4">
            <?= Csrf::field() ?>
            <?php if ($ride): ?><input type="hidden" name="ride_id" value="<?= (int)$ride['id'] ?>"><?php endif; ?>
            <div>
                <label class="field-label">Reason</label>
                <input type="text" name="reason" value="<?= old($_POST ?? [], 'reason') ?>" class="field-input" placeholder="e.g. Driver arrived late" required>
                <?php if (!empty($errors['reason'])): ?><p class="field-error"><?= e($errors['reason']) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="field-label">Other person's email (optional)</label>
                <input type="email" name="reported_email" value="<?= old($_POST ?? [], 'reported_email') ?>" class="field-input" placeholder="If reporting a specific driver/rider">
                <?php if (!empty($errors['reported_email'])): ?><p class="field-error"><?= e($errors['reported_email']) ?></p><?php endif; ?>
            </div>
            <div>
                <label class="field-label">Details (optional)</label>
                <textarea name="details" rows="4" class="field-textarea"><?= old($_POST ?? [], 'details') ?></textarea>
                <?php if (!empty($errors['details'])): ?><p class="field-error"><?= e($errors['details']) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn-primary w-full">Submit Report</button>
        </form>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
