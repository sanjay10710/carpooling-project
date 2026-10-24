<?php $editing = !empty($old['id']); $pageTitle = $editing ? 'Edit Vehicle' : 'Add Vehicle'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-xl mx-auto px-4 py-8">
    <a href="/index.php?route=driver/vehicles" class="text-sm text-sky-dark hover:underline">&larr; Back to vehicles</a>
    <div class="card mt-4">
        <div class="card-head"><h1 class="font-display text-xl font-bold"><?= $editing ? 'Edit Vehicle' : 'Add Vehicle' ?></h1></div>
        <form method="POST" action="/index.php?route=<?= $editing ? 'driver/vehicles/update' : 'driver/vehicles/store' ?>" class="space-y-4">
            <?= Csrf::field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$old['id'] ?>"><?php endif; ?>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Make</label>
                    <input type="text" name="make" value="<?= old($old ?? [], 'make') ?>" class="field-input" required>
                    <?php if (!empty($errors['make'])): ?><p class="field-error"><?= e($errors['make']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Model</label>
                    <input type="text" name="model" value="<?= old($old ?? [], 'model') ?>" class="field-input" required>
                    <?php if (!empty($errors['model'])): ?><p class="field-error"><?= e($errors['model']) ?></p><?php endif; ?>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Color</label>
                    <input type="text" name="color" value="<?= old($old ?? [], 'color') ?>" class="field-input" required>
                    <?php if (!empty($errors['color'])): ?><p class="field-error"><?= e($errors['color']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Plate number</label>
                    <input type="text" name="plate" value="<?= old($old ?? [], 'plate', $old['plate_number'] ?? '') ?>" class="field-input font-mono-tab" required>
                    <?php if (!empty($errors['plate'])): ?><p class="field-error"><?= e($errors['plate']) ?></p><?php endif; ?>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Vehicle type</label>
                    <select name="type" class="field-select" required>
                        <option value="">Select&hellip;</option>
                        <?php $currentType = $old['type'] ?? ($old['vehicle_type'] ?? ''); ?>
                        <?php foreach (Vehicle::TYPES as $t): ?>
                            <option value="<?= e($t) ?>" <?= $currentType === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['type'])): ?><p class="field-error"><?= e($errors['type']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Total seats (excl. driver)</label>
                    <input type="number" name="seats" min="1" max="8" value="<?= old($old ?? [], 'seats', (string)($old['total_seats'] ?? '')) ?>" class="field-input" required>
                    <?php if (!empty($errors['seats'])): ?><p class="field-error"><?= e($errors['seats']) ?></p><?php endif; ?>
                </div>
            </div>
            <button type="submit" class="btn-primary w-full"><?= $editing ? 'Save Changes' : 'Add Vehicle' ?></button>
        </form>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
