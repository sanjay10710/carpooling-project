<?php $pageTitle = 'Register'; require __DIR__ . '/../partials/header.php'; ?>
<div class="auth-hero min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <span class="inline-flex w-14 h-14 rounded-lg bg-sky items-center justify-center mb-3">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="#F7F9FB"/>
                    <circle cx="12" cy="9" r="2.6" fill="#84CC16"/>
                </svg>
            </span>
            <h1 class="font-display text-3xl font-bold text-white">Create your account</h1>
            <p class="text-white/70 text-sm mt-1">Join as a driver posting rides, or a rider looking for one.</p>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-7">
            <form method="POST" action="/index.php?route=register" class="space-y-4">
                <?= Csrf::field() ?>
                <div>
                    <label class="field-label">I want to join as</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 border border-line rounded-lg px-3 py-2.5 cursor-pointer has-[:checked]:border-sky has-[:checked]:bg-sky-50">
                            <input type="radio" name="role" value="rider" <?= (($old['role'] ?? 'rider') === 'rider') ? 'checked' : '' ?>>
                            <span class="text-sm font-medium">Rider</span>
                        </label>
                        <label class="flex items-center gap-2 border border-line rounded-lg px-3 py-2.5 cursor-pointer has-[:checked]:border-sky has-[:checked]:bg-sky-50">
                            <input type="radio" name="role" value="driver" <?= (($old['role'] ?? '') === 'driver') ? 'checked' : '' ?>>
                            <span class="text-sm font-medium">Driver</span>
                        </label>
                    </div>
                    <?php if (!empty($errors['role'])): ?><p class="field-error"><?= e($errors['role']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Full name</label>
                    <input type="text" name="name" value="<?= old($old ?? [], 'name') ?>" class="field-input" required>
                    <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Email</label>
                    <input type="email" name="email" value="<?= old($old ?? [], 'email') ?>" class="field-input" required>
                    <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label class="field-label">Phone</label>
                    <input type="text" name="phone" value="<?= old($old ?? [], 'phone') ?>" class="field-input" required>
                    <?php if (!empty($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="field-label">Password</label>
                        <input type="password" name="password" class="field-input" required>
                        <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label class="field-label">Confirm password</label>
                        <input type="password" name="confirm_password" class="field-input" required>
                        <?php if (!empty($errors['confirm_password'])): ?><p class="field-error"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full">Create Account</button>
            </form>
            <p class="text-center text-sm text-slateink mt-5">
                Already have an account? <a href="/index.php?route=login" class="text-sky-dark font-semibold hover:underline">Sign in</a>
            </p>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../partials/footer.php'; ?>
