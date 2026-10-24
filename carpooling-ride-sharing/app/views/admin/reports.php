<?php $pageTitle = 'Reports'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">Reports</h1>
            <p class="text-slateink text-sm mt-1">Issues flagged by drivers or riders. Resolve or dismiss to close them out.</p>
        </div>
        <div class="flex gap-2 text-sm">
            <?php $current = $_GET['status'] ?? ''; ?>
            <a href="/index.php?route=admin/reports" class="btn-secondary btn-sm <?= $current === '' ? 'border-sky text-sky-dark' : '' ?>">All</a>
            <a href="/index.php?route=admin/reports&status=open" class="btn-secondary btn-sm <?= $current === 'open' ? 'border-sky text-sky-dark' : '' ?>">Open</a>
            <a href="/index.php?route=admin/reports&status=resolved" class="btn-secondary btn-sm <?= $current === 'resolved' ? 'border-sky text-sky-dark' : '' ?>">Resolved</a>
            <a href="/index.php?route=admin/reports&status=dismissed" class="btn-secondary btn-sm <?= $current === 'dismissed' ? 'border-sky text-sky-dark' : '' ?>">Dismissed</a>
        </div>
    </div>

    <div class="space-y-3">
        <?php if (empty($reports)): ?>
            <div class="card empty-state">No reports found.</div>
        <?php endif; ?>
        <?php foreach ($reports as $r): ?>
            <div class="card">
                <div class="card-head">
                    <div>
                        <h2 class="text-base"><?= e($r['reason']) ?></h2>
                        <p class="text-slateink text-xs mt-1">
                            Reported by <?= e($r['reporter_name']) ?>
                            <?php if ($r['reported_name']): ?> &middot; about <?= e($r['reported_name']) ?><?php endif; ?>
                            <?php if ($r['origin']): ?> &middot; ride <?= e($r['origin']) ?> &rarr; <?= e($r['destination']) ?><?php endif; ?>
                            &middot; <?= format_datetime($r['created_at']) ?>
                        </p>
                    </div>
                    <span class="badge <?= status_badge_class($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span>
                </div>
                <?php if (!empty($r['details'])): ?><p class="text-sm text-slateink mb-3"><?= e($r['details']) ?></p><?php endif; ?>
                <?php if ($r['status'] === 'open'): ?>
                    <div class="flex gap-2">
                        <form method="POST" action="/index.php?route=admin/reports/resolve">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="action" value="resolve">
                            <button type="submit" class="btn-accent btn-sm">Mark Resolved</button>
                        </form>
                        <form method="POST" action="/index.php?route=admin/reports/resolve">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="action" value="dismiss">
                            <button type="submit" class="btn-secondary btn-sm">Dismiss</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p class="text-xs text-slateink">Closed <?= format_datetime($r['resolved_at']) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
