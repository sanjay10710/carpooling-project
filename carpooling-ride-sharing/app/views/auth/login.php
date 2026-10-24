<?php $pageTitle = 'Login'; require __DIR__ . '/../partials/header.php'; ?>
<div class="auth-hero min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md relative">
        <div class="text-center mb-8">
            <span class="inline-flex w-14 h-14 rounded-lg bg-sky items-center justify-center mb-3">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="#F7F9FB"/>
                    <circle cx="12" cy="9" r="2.6" fill="#84CC16"/>
                </svg>
            </span>
            <h1 class="font-display text-3xl font-bold text-white">TransitPool</h1>
            <p class="text-white/70 text-sm mt-1">Community carpooling &amp; ride-sharing. Sign in to continue.</p>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-7">
            <?php if (!empty($errors['login'])): ?>
                <div class="alert alert-error"><?= e($errors['login']) ?></div>
            <?php endif; ?>
            <?php $success = flash_get('success'); if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>
            <form method="POST" action="/index.php?route=login" class="space-y-4">
                <?= Csrf::field() ?>
                <div>
                    <label class="field-label">Email</label>
                    <input type="email" name="email" value="<?= old($old ?? [], 'email') ?>" class="field-input" required>
                    <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Password</label>
                    <input type="password" name="password" class="field-input" required>
                    <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
                </div>
                <button type="submit" class="btn-primary w-full">Sign In</button>
            </form>
            <p class="text-center text-sm text-slateink mt-5">
                New driver or rider? <a href="/index.php?route=register" class="text-sky-dark font-semibold hover:underline">Create an account</a>
            </p>
        </div>
        <div class="mt-6 text-center text-xs text-white/60 space-y-1">
            <p class="font-semibold text-white/80">Demo logins</p>
            <p>Admin: admin@transitpool.test / Admin@123</p>
            <p>Driver: driver1@transitpool.test / Driver@123</p>
            <p>Rider: rider1@transitpool.test / Rider@123</p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
