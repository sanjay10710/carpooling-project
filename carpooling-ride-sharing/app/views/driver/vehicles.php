<?php $pageTitle = 'My Vehicles'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-5xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">My Vehicles</h1>
            <p class="text-slateink text-sm mt-1">Only an active vehicle can be used to post a new ride.</p>
        </div>
        <a href="/index.php?route=driver/vehicles/new" class="btn-accent">+ Add Vehicle</a>
    </div>

    <div class="card">
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Vehicle</th><th>Plate</th><th>Type</th><th>Seats</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($vehicles)): ?>
                    <tr><td colspan="6" class="empty-state">No vehicles yet. Add one to start posting rides.</td></tr>
                <?php endif; ?>
                <?php foreach ($vehicles as $v): ?>
                    <tr>
                        <td class="font-medium"><?= e($v['color']) ?> <?= e($v['make']) ?> <?= e($v['model']) ?></td>
                        <td class="font-mono-tab"><?= e($v['plate_number']) ?></td>
                        <td class="capitalize"><?= e($v['vehicle_type']) ?></td>
                        <td class="font-mono-tab"><?= (int)$v['total_seats'] ?></td>
                        <td><span class="badge <?= status_badge_class($v['status']) ?>"><?= e(ucfirst($v['status'])) ?></span></td>
                        <td class="flex gap-2">
                            <a href="/index.php?route=driver/vehicles/edit&id=<?= (int)$v['id'] ?>" class="btn-secondary btn-sm">Edit</a>
                            <?php if ($v['status'] === 'active'): ?>
                                <form method="POST" action="/index.php?route=driver/vehicles/deactivate" data-confirm="Deactivate this vehicle? It won't be usable for new rides until reactivated.">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                    <button type="submit" class="btn-secondary btn-sm">Deactivate</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="/index.php?route=driver/vehicles/activate">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                    <button type="submit" class="btn-secondary btn-sm">Activate</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
