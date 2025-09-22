<h1 class="h3 mb-4">Iniciar sesión</h1>
<form method="POST" action="/login" novalidate>
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
    <button type="submit" class="btn btn-primary w-100">Entrar</button>
    <p class="mt-3 mb-0 text-center">
        ¿No tienes cuenta? <a href="/registro">Regístrate aquí</a>
    </p>
</form>
