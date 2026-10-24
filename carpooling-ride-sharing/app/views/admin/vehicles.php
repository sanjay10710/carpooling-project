<?php $pageTitle = 'Vehicles'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">Vehicles</h1>
            <p class="text-slateink text-sm mt-1">Every vehicle registered by a driver. A deactivated vehicle can't be used to post a new ride.</p>
        </div>
    </div>

    <div class="card">
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Vehicle</th><th>Plate</th><th>Type</th><th>Seats</th><th>Driver</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($vehicles)): ?>
                    <tr><td colspan="7" class="empty-state">No vehicles registered yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($vehicles as $v): ?>
                    <tr>
                        <td class="font-medium"><?= e($v['color']) ?> <?= e($v['make']) ?> <?= e($v['model']) ?></td>
                        <td class="font-mono-tab"><?= e($v['plate_number']) ?></td>
                        <td class="capitalize"><?= e($v['vehicle_type']) ?></td>
                        <td class="font-mono-tab"><?= (int)$v['total_seats'] ?></td>
                        <td><?= e($v['driver_name']) ?></td>
                        <td><span class="badge <?= status_badge_class($v['status']) ?>"><?= e(ucfirst($v['status'])) ?></span></td>
                        <td>
                            <form method="POST" action="/index.php?route=admin/vehicles/status" class="inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                                <input type="hidden" name="status" value="<?= $v['status'] === 'active' ? 'inactive' : 'active' ?>">
                                <button type="submit" class="btn-secondary btn-sm">
                                    <?= $v['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
