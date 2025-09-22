<h1 class="h3 mb-4">Crear cuenta</h1>
<form method="POST" action="/registro" novalidate>
    <div class="mb-3">
        <label for="name" class="form-label">Nombre</label>
        <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
        <?php if (isset($errors['name'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['name']) ?></div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
        <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
        <input type="password" class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" id="password_confirmation" name="password_confirmation" required>
        <?php if (isset($errors['password_confirmation'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirmation']) ?></div>
        <?php endif; ?>
    </div>
    <button type="submit" class="btn btn-success w-100">Crear cuenta</button>
    <p class="mt-3 mb-0 text-center">
        ¿Ya tienes cuenta? <a href="/login">Inicia sesión</a>
    </p>
</form>
