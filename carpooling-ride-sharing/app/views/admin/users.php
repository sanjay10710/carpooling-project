<?php $pageTitle = 'Users'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-8">
    <?php require __DIR__ . '/../partials/flash.php'; ?>
    <div class="card-head">
        <div>
            <h1 class="font-display text-2xl font-bold">Users</h1>
            <p class="text-slateink text-sm mt-1">All driver and rider accounts. Deactivating an account blocks login immediately.</p>
        </div>
        <div class="flex gap-2 text-sm">
            <a href="/index.php?route=admin/users" class="btn-secondary btn-sm <?= empty($_GET['role']) ? 'border-sky text-sky-dark' : '' ?>">All</a>
            <a href="/index.php?route=admin/users&role=driver" class="btn-secondary btn-sm <?= ($_GET['role'] ?? '') === 'driver' ? 'border-sky text-sky-dark' : '' ?>">Drivers</a>
            <a href="/index.php?route=admin/users&role=rider" class="btn-secondary btn-sm <?= ($_GET['role'] ?? '') === 'rider' ? 'border-sky text-sky-dark' : '' ?>">Riders</a>
        </div>
    </div>

    <div class="card">
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="empty-state">No users found.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="font-medium"><?= e($u['name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td class="font-mono-tab"><?= e($u['phone']) ?></td>
                        <td class="capitalize"><?= e(str_replace('_', ' ', $u['role'])) ?></td>
                        <td><span class="badge <?= status_badge_class($u['status']) ?>"><?= e(ucfirst($u['status'])) ?></span></td>
                        <td>
                            <?php if ($u['role'] !== 'super_admin'): ?>
                                <form method="POST" action="/index.php?route=admin/users/status" class="inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $u['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button type="submit" class="btn-secondary btn-sm">
                                        <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                    </button>
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
