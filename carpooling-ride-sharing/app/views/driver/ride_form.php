<?php $editing = !empty($old['id']); $pageTitle = $editing ? 'Edit Ride' : 'Post a Ride'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-xl mx-auto px-4 py-8">
    <a href="/index.php?route=driver/rides" class="text-sm text-sky-dark hover:underline">&larr; Back to my rides</a>
    <div class="card mt-4">
        <div class="card-head"><h1 class="font-display text-xl font-bold"><?= $editing ? 'Edit Ride' : 'Post a Ride' ?></h1></div>

        <?php if (empty($vehicles)): ?>
            <p class="empty-state">You need at least one active vehicle before you can post a ride. <a href="/index.php?route=driver/vehicles/new" class="text-sky-dark font-semibold hover:underline">Add one now</a>.</p>
        <?php else: ?>
        <form method="POST" action="/index.php?route=<?= $editing ? 'driver/rides/update' : 'driver/rides/store' ?>" class="space-y-4">
            <?= Csrf::field() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$old['id'] ?>"><?php endif; ?>
            <div>
                <label class="field-label">Vehicle</label>
                <select name="vehicle_id" class="field-select" required>
                    <option value="">Select&hellip;</option>
                    <?php $currentVehicle = (int)($old['vehicle_id'] ?? 0); ?>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?= (int)$v['id'] ?>" <?= $currentVehicle === (int)$v['id'] ? 'selected' : '' ?>>
                            <?= e($v['color']) ?> <?= e($v['make']) ?> <?= e($v['model']) ?> (<?= e($v['plate_number']) ?>) &mdash; <?= (int)$v['total_seats'] ?> seats
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['vehicle_id'])): ?><p class="field-error"><?= e($errors['vehicle_id']) ?></p><?php endif; ?>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Origin</label>
                    <input type="text" name="origin" placeholder="e.g. Koramangala, Bengaluru" value="<?= old($old ?? [], 'origin') ?>" class="field-input" required>
                    <?php if (!empty($errors['origin'])): ?><p class="field-error"><?= e($errors['origin']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Destination</label>
                    <input type="text" name="destination" placeholder="e.g. Whitefield, Bengaluru" value="<?= old($old ?? [], 'destination') ?>" class="field-input" required>
                    <?php if (!empty($errors['destination'])): ?><p class="field-error"><?= e($errors['destination']) ?></p><?php endif; ?>
                </div>
            </div>
            <div>
                <label class="field-label">Departure date &amp; time</label>
                <?php
                    $dep = $old['departure_at'] ?? '';
                    $depValue = $dep !== '' ? date('Y-m-d\TH:i', strtotime($dep)) : '';
                ?>
                <input type="datetime-local" name="departure_at" value="<?= e($depValue) ?>" class="field-input" required>
                <?php if (!empty($errors['departure_at'])): ?><p class="field-error"><?= e($errors['departure_at']) ?></p><?php endif; ?>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="field-label">Seats offered<?= $editing ? ' (fixed after posting)' : '' ?></label>
                    <?php if ($editing): ?>
                        <input type="text" class="field-input font-mono-tab bg-canvas" value="<?= (int)$old['seats_total'] ?>" disabled>
                        <input type="hidden" name="seats" value="<?= (int)$old['seats_total'] ?>">
                    <?php else: ?>
                        <input type="number" name="seats" min="1" max="8" value="<?= old($old ?? [], 'seats') ?>" class="field-input" required>
                        <?php if (!empty($errors['seats'])): ?><p class="field-error"><?= e($errors['seats']) ?></p><?php endif; ?>
                        <p class="field-hint">Cannot exceed the chosen vehicle's total seats.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Price per seat (&#8377;)</label>
                    <input type="number" step="0.01" name="price" min="1" value="<?= old($old ?? [], 'price', (string)($old['price_per_seat'] ?? '')) ?>" class="field-input" required>
                    <?php if (!empty($errors['price'])): ?><p class="field-error"><?= e($errors['price']) ?></p><?php endif; ?>
                </div>
            </div>
            <div>
                <label class="field-label">Notes (optional)</label>
                <textarea name="notes" rows="2" class="field-textarea" placeholder="AC, luggage limits, pickup instructions..."><?= old($old ?? [], 'notes') ?></textarea>
                <?php if (!empty($errors['notes'])): ?><p class="field-error"><?= e($errors['notes']) ?></p><?php endif; ?>
            </div>
            <button type="submit" class="btn-primary w-full"><?= $editing ? 'Save Changes' : 'Post Ride' ?></button>
        </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>
