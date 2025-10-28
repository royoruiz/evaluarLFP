<?php
$modules = $modules ?? [];
$errors = $errors ?? [];
$old = $old ?? [];
$selectedModule = $old['module_code'] ?? '';
?>

<h1 class="h4 mb-4">Alta de módulo</h1>
<p class="text-muted">Selecciona un módulo del catálogo para comenzar el proceso de configuración.</p>

<?php if (empty($modules)): ?>
    <div class="alert alert-warning">Todavía no hay módulos disponibles en el catálogo. Solicita a un administrador que los cree desde el panel de administración.</div>
<?php else: ?>
    <form method="post" action="/modulos/nuevo" class="mt-3">
        <div class="mb-3">
            <label for="module_code" class="form-label">Módulo</label>
            <select class="form-select<?php if (!empty($errors['module_code'])): ?> is-invalid<?php endif; ?>" id="module_code" name="module_code" required>
                <option value="">Selecciona un módulo...</option>
                <?php foreach ($modules as $module): ?>
                    <?php
                    $value = htmlspecialchars($module['codigo']);
                    $label = sprintf('%s — %s (Curso %s)', $module['codigo'], $module['nombre'], $module['curso']);
                    $isSelected = $selectedModule === $module['codigo'];
                    ?>
                    <option value="<?= $value ?>"<?php if ($isSelected): ?> selected<?php endif; ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['module_code'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['module_code']) ?></div>
            <?php endif; ?>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="/" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Comenzar configuración</button>
        </div>
    </form>
<?php endif; ?>
