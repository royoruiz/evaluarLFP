<?php
$modules = $modules ?? [];
$formErrors = $errors['evaluation_form'] ?? [];
$formOld = $old['evaluation_form'] ?? [];
$generalError = $errors['general'] ?? null;
?>

<div class="mb-4">
    <h1 class="h4 mb-1">Nueva evaluación</h1>
    <p class="text-muted mb-0">Extiende uno de tus módulos a un curso académico y grupo concreto.</p>
</div>

<?php if (!empty($generalError)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($generalError) ?></div>
<?php endif; ?>

<?php if (empty($modules)): ?>
    <div class="alert alert-info">
        Primero debes configurar al menos un módulo para poder crear evaluaciones.
    </div>
    <a href="/?tab=modules" class="btn btn-primary">Ir a mis módulos</a>
<?php else: ?>
    <form method="POST" action="/evaluaciones" class="row g-3">
        <div class="col-12">
            <label for="evaluation-name" class="form-label">Nombre de la evaluación</label>
            <input
                type="text"
                class="form-control<?php if (!empty($formErrors['evaluation_name'])): ?> is-invalid<?php endif; ?>"
                id="evaluation-name"
                name="evaluation_name"
                value="<?= htmlspecialchars($formOld['evaluation_name'] ?? '') ?>"
                maxlength="255"
                required
            >
            <?php if (!empty($formErrors['evaluation_name'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($formErrors['evaluation_name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <label for="academic-year" class="form-label">Año académico</label>
            <input
                type="text"
                class="form-control<?php if (!empty($formErrors['academic_year'])): ?> is-invalid<?php endif; ?>"
                id="academic-year"
                name="academic_year"
                value="<?= htmlspecialchars($formOld['academic_year'] ?? '') ?>"
                placeholder="25/26"
                maxlength="9"
                required
            >
            <?php if (!empty($formErrors['academic_year'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($formErrors['academic_year']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <label for="class-group" class="form-label">Clase o grupo</label>
            <input
                type="text"
                class="form-control<?php if (!empty($formErrors['class_group'])): ?> is-invalid<?php endif; ?>"
                id="class-group"
                name="class_group"
                value="<?= htmlspecialchars($formOld['class_group'] ?? '') ?>"
                maxlength="255"
                required
            >
            <?php if (!empty($formErrors['class_group'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($formErrors['class_group']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-12">
            <label for="module-id" class="form-label">Módulo base</label>
            <select
                class="form-select<?php if (!empty($formErrors['module_id'])): ?> is-invalid<?php endif; ?>"
                id="module-id"
                name="module_id"
                required
            >
                <option value="">Selecciona un módulo...</option>
                <?php foreach ($modules as $module): ?>
                    <option
                        value="<?= (int) ($module['id'] ?? 0) ?>"
                        <?php if ((int) ($formOld['module_id'] ?? 0) === (int) ($module['id'] ?? 0)): ?>selected<?php endif; ?>
                    >
                        <?= htmlspecialchars($module['module_name'] ?? 'Módulo sin nombre') ?>
                        (<?= (int) ($module['units_count'] ?? 0) ?> unidades)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($formErrors['module_id'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($formErrors['module_id']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-12 d-flex justify-content-between mt-4">
            <a href="/" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Crear evaluación</button>
        </div>
    </form>
<?php endif; ?>
