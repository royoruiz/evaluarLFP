<?php
$evaluation = $evaluation ?? [];
$units = $units ?? [];
$updateErrors = $errors['evaluation_update'] ?? [];
$instrumentErrors = $errors['evaluation_instrument'] ?? [];
$generalError = $errors['general'] ?? null;
$updateOld = $old['evaluation_update'] ?? [];
$instrumentOld = $old['evaluation_instrument'] ?? null;

$evaluationName = $updateOld['evaluation_name'] ?? ($evaluation['evaluation_name'] ?? '');
$evaluationYear = $updateOld['academic_year'] ?? ($evaluation['academic_year'] ?? '');
$evaluationClass = $updateOld['class_group'] ?? ($evaluation['class_group'] ?? '');
?>

<div class="mb-4">
    <h1 class="h4 mb-1">Editar evaluación</h1>
    <p class="text-muted mb-0">
        <?= htmlspecialchars($evaluation['evaluation_name'] ?? 'Evaluación sin nombre') ?> ·
        <?= htmlspecialchars($evaluation['module_name'] ?? 'Módulo sin asignar') ?>
    </p>
</div>

<?php if (!empty($generalError)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($generalError) ?></div>
<?php endif; ?>

<section class="mb-5">
    <h2 class="h5 mb-3">Datos generales</h2>
    <form method="POST" action="/evaluaciones/actualizar" class="row g-3">
        <input type="hidden" name="evaluation_id" value="<?= (int) ($evaluation['id'] ?? 0) ?>">

        <div class="col-12">
            <label for="evaluation-name" class="form-label">Nombre</label>
            <input
                type="text"
                class="form-control<?php if (!empty($updateErrors['evaluation_name'])): ?> is-invalid<?php endif; ?>"
                id="evaluation-name"
                name="evaluation_name"
                value="<?= htmlspecialchars($evaluationName) ?>"
                maxlength="255"
                required
            >
            <?php if (!empty($updateErrors['evaluation_name'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($updateErrors['evaluation_name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <label for="academic-year" class="form-label">Año académico</label>
            <input
                type="text"
                class="form-control<?php if (!empty($updateErrors['academic_year'])): ?> is-invalid<?php endif; ?>"
                id="academic-year"
                name="academic_year"
                value="<?= htmlspecialchars($evaluationYear) ?>"
                maxlength="9"
                required
            >
            <?php if (!empty($updateErrors['academic_year'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($updateErrors['academic_year']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-md-8">
            <label for="class-group" class="form-label">Clase o grupo</label>
            <input
                type="text"
                class="form-control<?php if (!empty($updateErrors['class_group'])): ?> is-invalid<?php endif; ?>"
                id="class-group"
                name="class_group"
                value="<?= htmlspecialchars($evaluationClass) ?>"
                maxlength="255"
                required
            >
            <?php if (!empty($updateErrors['class_group'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($updateErrors['class_group']) ?></div>
            <?php endif; ?>
        </div>

        <div class="col-12 d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
    </form>
</section>

<section>
    <h2 class="h5 mb-4">Unidades e instrumentos</h2>

    <?php if (empty($units)): ?>
        <div class="alert alert-info">Esta evaluación no tiene unidades vinculadas al módulo.</div>
    <?php else: ?>
        <div class="evaluation-units-tabs">
            <style>
                .evaluation-units-tabs .tab-pane { display: none; }
                .evaluation-units-tabs .tab-pane.active { display: block; }

                .evaluation-modal {
                    position: fixed;
                    inset: 0;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    background: rgba(0, 0, 0, 0.35);
                    padding: 1.5rem;
                    z-index: 1050;
                }

                .evaluation-modal.show {
                    display: flex;
                }

                .evaluation-modal__dialog {
                    background: #fff;
                    border-radius: .5rem;
                    box-shadow: 0 1.5rem 4rem rgba(0, 0, 0, 0.2);
                    max-width: 36rem;
                    width: 100%;
                    overflow: hidden;
                }

                .evaluation-modal__header,
                .evaluation-modal__footer {
                    padding: 1rem 1.5rem;
                    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                }

                .evaluation-modal__footer {
                    border-top: 1px solid rgba(0, 0, 0, 0.1);
                    border-bottom: 0;
                    display: flex;
                    justify-content: flex-end;
                    gap: .75rem;
                }

                .evaluation-modal__body {
                    padding: 1.5rem;
                    max-height: 70vh;
                    overflow-y: auto;
                }

                body.modal-open {
                    overflow: hidden;
                }
            </style>

            <ul class="nav nav-tabs" role="tablist">
                <?php foreach ($units as $index => $unit): ?>
                    <?php $unitId = (int) ($unit['evaluation_unit_id'] ?? 0); ?>
                    <li class="nav-item" role="presentation">
                        <button
                            id="unit-tab-<?= $unitId ?>"
                            class="nav-link<?php if ($index === 0): ?> active<?php endif; ?>"
                            type="button"
                            role="tab"
                            data-unit-target="unit-pane-<?= $unitId ?>"
                            aria-controls="unit-pane-<?= $unitId ?>"
                            aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                        >
                            Unidad <?= htmlspecialchars((string) ($unit['unit_number'] ?? '')) ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom p-4 bg-white">
                <?php foreach ($units as $index => $unit): ?>
                    <?php
                    $unitId = (int) ($unit['evaluation_unit_id'] ?? 0);
                    $unitCriteria = $unit['criteria'] ?? [];
                    $unitInstruments = $unit['instruments'] ?? [];
                    $missingCriteria = $unit['missing_criteria'] ?? [];
                    $allowedCodes = $unit['allowed_codes'] ?? [];
                    $unitError = $instrumentErrors[$unitId] ?? null;
                    ?>
                    <div
                        id="unit-pane-<?= $unitId ?>"
                        class="tab-pane<?php if ($index === 0): ?> active<?php endif; ?>"
                        role="tabpanel"
                        aria-labelledby="unit-tab-<?= $unitId ?>"
                    >
                        <article id="unidad-<?= $unitId ?>" class="mb-4">
                            <div class="d-flex justify-content-between align-items-baseline mb-2">
                                <h3 class="h6 mb-0">
                                    Unidad <?= htmlspecialchars((string) ($unit['unit_number'] ?? '')) ?> ·
                                    <?= htmlspecialchars($unit['unit_label'] ?? '') ?>
                                </h3>
                                <span class="badge bg-secondary"><?= count($unitCriteria) ?> criterios</span>
                            </div>

                            <?php if (!empty($unitError)): ?>
                                <div class="alert alert-danger mb-3"><?= htmlspecialchars($unitError) ?></div>
                            <?php endif; ?>

                            <?php if (!empty($missingCriteria)): ?>
                                <div class="alert alert-warning mb-3">
                                    <strong>Faltan criterios por asignar:</strong>
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($missingCriteria as $criterion): ?>
                                            <li>
                                                RA<?= htmlspecialchars($criterion['resultado_numero'] ?? '') ?> ·
                                                C<?= htmlspecialchars($criterion['letra'] ?? '') ?> -
                                                <?= htmlspecialchars($criterion['descripcion'] ?? '') ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <?php if (!empty($unitCriteria)): ?>
                                    <div class="alert alert-success mb-3">Todos los criterios de la unidad están cubiertos por los instrumentos.</div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="row g-4">
                                <div class="col-lg-5">
                                    <div class="border rounded p-3 h-100">
                                        <h4 class="h6">Criterios del módulo</h4>
                                        <?php if (empty($unitCriteria)): ?>
                                            <p class="text-muted mb-0">Esta unidad no tiene criterios asociados en el módulo.</p>
                                        <?php else: ?>
                                            <ul class="list-unstyled mb-0 small">
                                                <?php foreach ($unitCriteria as $criterion): ?>
                                                    <li class="mb-2">
                                                        <strong>RA<?= htmlspecialchars($criterion['resultado_numero'] ?? '') ?> · C<?= htmlspecialchars($criterion['letra'] ?? '') ?></strong><br>
                                                        <span class="text-muted"><?= htmlspecialchars($criterion['descripcion'] ?? '') ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-lg-7">
                                    <?php if (empty($unitInstruments)): ?>
                                        <div class="alert alert-warning">En esta unidad faltan CE por evaluar.</div>
                                    <?php endif; ?>

                                    <?php foreach ($unitInstruments as $instrument): ?>
                                        <?php
                                        $instrumentId = (int) ($instrument['id'] ?? 0);
                                        $isEditing = is_array($instrumentOld)
                                            && ($instrumentOld['unit_id'] ?? 0) === $unitId
                                            && ($instrumentOld['instrument_id'] ?? 0) === $instrumentId;
                                        $instrumentName = $isEditing ? ($instrumentOld['name'] ?? '') : ($instrument['name'] ?? '');
                                        $instrumentDescription = $isEditing ? ($instrumentOld['description'] ?? '') : ($instrument['description'] ?? '');
                                        $instrumentCriteria = $isEditing ? ($instrumentOld['criteria'] ?? []) : array_map(
                                            static fn ($row) => $row['criteria_code'],
                                            $instrument['criteria'] ?? []
                                        );
                                        $editModalId = 'edit-instrument-modal-' . $instrumentId;
                                        ?>
                                        <div class="card mb-3">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <span class="fw-semibold mb-0"><?= htmlspecialchars($instrumentName) ?></span>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary btn-sm"
                                                        data-instrument-modal-open="<?= $editModalId ?>"
                                                    >
                                                        Modificar
                                                    </button>
                                                    <form
                                                        method="POST"
                                                        action="/evaluaciones/instrumentos/eliminar"
                                                        onsubmit="return confirm('¿Seguro que quieres eliminar este instrumento?');"
                                                        class="m-0"
                                                    >
                                                        <input type="hidden" name="evaluation_id" value="<?= (int) ($evaluation['id'] ?? 0) ?>">
                                                        <input type="hidden" name="evaluation_unit_id" value="<?= $unitId ?>">
                                                        <input type="hidden" name="instrument_id" value="<?= $instrumentId ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="evaluation-modal<?php if ($isEditing): ?> show<?php endif; ?>"
                                            id="<?= $editModalId ?>"
                                            role="dialog"
                                            aria-modal="true"
                                            aria-hidden="<?= $isEditing ? 'false' : 'true' ?>"
                                            <?php if ($isEditing): ?>data-auto-open="true"<?php endif; ?>
                                        >
                                            <div class="evaluation-modal__dialog" role="document">
                                                <div class="evaluation-modal__header d-flex justify-content-between align-items-center">
                                                    <h4 class="h6 mb-0">Editar instrumento</h4>
                                                    <button type="button" class="btn-close" aria-label="Cerrar" data-instrument-modal-close></button>
                                                </div>
                                                <div class="evaluation-modal__body">
                                                    <form method="POST" action="/evaluaciones/instrumentos/actualizar">
                                                        <input type="hidden" name="evaluation_id" value="<?= (int) ($evaluation['id'] ?? 0) ?>">
                                                        <input type="hidden" name="evaluation_unit_id" value="<?= $unitId ?>">
                                                        <input type="hidden" name="instrument_id" value="<?= $instrumentId ?>">

                                                        <div class="mb-3">
                                                            <label for="instrument-name-<?= $instrumentId ?>" class="form-label">Nombre del instrumento</label>
                                                            <input
                                                                type="text"
                                                                class="form-control"
                                                                id="instrument-name-<?= $instrumentId ?>"
                                                                name="name"
                                                                value="<?= htmlspecialchars($instrumentName) ?>"
                                                                maxlength="255"
                                                                required
                                                            >
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="instrument-description-<?= $instrumentId ?>" class="form-label">Descripción (opcional)</label>
                                                            <textarea
                                                                class="form-control"
                                                                id="instrument-description-<?= $instrumentId ?>"
                                                                name="description"
                                                                rows="2"
                                                            ><?= htmlspecialchars($instrumentDescription) ?></textarea>
                                                        </div>

                                                        <div class="mb-3">
                                                            <span class="form-label d-block">Criterios evaluados</span>
                                                            <?php if (empty($unitCriteria)): ?>
                                                                <p class="text-muted mb-0">No hay criterios que asignar.</p>
                                                            <?php else: ?>
                                                                <div class="row row-cols-1 row-cols-sm-2 g-2">
                                                                    <?php foreach ($unitCriteria as $criterion): ?>
                                                                        <?php $code = $criterion['criteria_code']; ?>
                                                                        <div class="col">
                                                                            <div class="form-check">
                                                                                <input
                                                                                    class="form-check-input"
                                                                                    type="checkbox"
                                                                                    value="<?= htmlspecialchars($code) ?>"
                                                                                    id="instrument-<?= $instrumentId ?>-criterion-<?= htmlspecialchars($code) ?>"
                                                                                    name="criteria[]"
                                                                                    <?php if (in_array($code, $instrumentCriteria, true)): ?>checked<?php endif; ?>
                                                                                >
                                                                                <label class="form-check-label" for="instrument-<?= $instrumentId ?>-criterion-<?= htmlspecialchars($code) ?>">
                                                                                    RA<?= htmlspecialchars($criterion['resultado_numero'] ?? '') ?> · C<?= htmlspecialchars($criterion['letra'] ?? '') ?>
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="evaluation-modal__footer">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-instrument-modal-close>Cancelar</button>
                                                            <button type="submit" class="btn btn-primary btn-sm">Guardar cambios</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php
                                    $isCreating = is_array($instrumentOld)
                                        && ($instrumentOld['unit_id'] ?? 0) === $unitId
                                        && empty($instrumentOld['instrument_id'] ?? null);
                                    $newInstrumentName = $isCreating ? ($instrumentOld['name'] ?? '') : '';
                                    $newInstrumentDescription = $isCreating ? ($instrumentOld['description'] ?? '') : '';
                                    $newInstrumentCriteria = $isCreating ? ($instrumentOld['criteria'] ?? []) : $allowedCodes;
                                    ?>

                                    <div class="d-flex justify-content-end">
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary btn-sm"
                                            data-instrument-modal-open="add-instrument-modal-<?= $unitId ?>"
                                        >
                                            <span aria-hidden="true">+</span>
                                            <span class="ms-1">Añadir instrumento</span>
                                        </button>
                                    </div>

                                    <div
                                        class="evaluation-modal<?php if ($isCreating): ?> show<?php endif; ?>"
                                        id="add-instrument-modal-<?= $unitId ?>"
                                        role="dialog"
                                        aria-modal="true"
                                        aria-hidden="<?= $isCreating ? 'false' : 'true' ?>"
                                        <?php if ($isCreating): ?>data-auto-open="true"<?php endif; ?>
                                    >
                                        <div class="evaluation-modal__dialog" role="document">
                                            <div class="evaluation-modal__header d-flex justify-content-between align-items-center">
                                                <h4 class="h6 mb-0">Añadir instrumento</h4>
                                                <button type="button" class="btn-close" aria-label="Cerrar" data-instrument-modal-close></button>
                                            </div>
                                            <div class="evaluation-modal__body">
                                                <form method="POST" action="/evaluaciones/instrumentos">
                                                    <input type="hidden" name="evaluation_id" value="<?= (int) ($evaluation['id'] ?? 0) ?>">
                                                    <input type="hidden" name="evaluation_unit_id" value="<?= $unitId ?>">

                                                    <div class="mb-3">
                                                        <label for="new-instrument-name-<?= $unitId ?>" class="form-label">Nombre</label>
                                                        <input
                                                            type="text"
                                                            class="form-control"
                                                            id="new-instrument-name-<?= $unitId ?>"
                                                            name="name"
                                                            value="<?= htmlspecialchars($newInstrumentName) ?>"
                                                            maxlength="255"
                                                            required
                                                        >
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="new-instrument-description-<?= $unitId ?>" class="form-label">Descripción (opcional)</label>
                                                        <textarea
                                                            class="form-control"
                                                            id="new-instrument-description-<?= $unitId ?>"
                                                            name="description"
                                                            rows="2"
                                                        ><?= htmlspecialchars($newInstrumentDescription) ?></textarea>
                                                    </div>

                                                    <div class="mb-3">
                                                        <span class="form-label d-block">Criterios evaluados</span>
                                                        <?php if (empty($unitCriteria)): ?>
                                                            <p class="text-muted mb-0">No hay criterios que asignar.</p>
                                                        <?php else: ?>
                                                            <div class="row row-cols-1 row-cols-sm-2 g-2">
                                                                <?php foreach ($unitCriteria as $criterion): ?>
                                                                    <?php $code = $criterion['criteria_code']; ?>
                                                                    <div class="col">
                                                                        <div class="form-check">
                                                                            <input
                                                                                class="form-check-input"
                                                                                type="checkbox"
                                                                                value="<?= htmlspecialchars($code) ?>"
                                                                                id="new-instrument-<?= $unitId ?>-criterion-<?= htmlspecialchars($code) ?>"
                                                                                name="criteria[]"
                                                                                <?php if (in_array($code, $newInstrumentCriteria, true)): ?>checked<?php endif; ?>
                                                                            >
                                                                            <label class="form-check-label" for="new-instrument-<?= $unitId ?>-criterion-<?= htmlspecialchars($code) ?>">
                                                                                RA<?= htmlspecialchars($criterion['resultado_numero'] ?? '') ?> · C<?= htmlspecialchars($criterion['letra'] ?? '') ?>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="evaluation-modal__footer">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-instrument-modal-close>Cancelar</button>
                                                        <button type="submit" class="btn btn-primary btn-sm">Guardar instrumento</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.evaluation-units-tabs').forEach(function (container) {
        var buttons = container.querySelectorAll('[data-unit-target]');
        var panes = container.querySelectorAll('.tab-pane');

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var targetId = button.getAttribute('data-unit-target');
                if (!targetId) {
                    return;
                }

                buttons.forEach(function (btn) {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-selected', 'false');
                });
                button.classList.add('active');
                button.setAttribute('aria-selected', 'true');

                panes.forEach(function (pane) {
                    pane.classList.remove('active');
                });
                var target = container.querySelector('#' + targetId);
                if (target) {
                    target.classList.add('active');
                }
            });
        });
    });

    function openModal(modal) {
        if (!modal) {
            return;
        }

        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    document.querySelectorAll('[data-instrument-modal-open]').forEach(function (trigger) {
        var modalId = trigger.getAttribute('data-instrument-modal-open');
        var modal = modalId ? document.getElementById(modalId) : null;

        trigger.addEventListener('click', function () {
            openModal(modal);
        });
    });

    document.querySelectorAll('.evaluation-modal').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });

        modal.querySelectorAll('[data-instrument-modal-close]').forEach(function (closer) {
            closer.addEventListener('click', function () {
                closeModal(modal);
            });
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            var openedModal = document.querySelector('.evaluation-modal.show');
            if (openedModal) {
                closeModal(openedModal);
            }
        }
    });

    document.querySelectorAll('.evaluation-modal[data-auto-open="true"]').forEach(function (modal) {
        openModal(modal);
    });
});
</script>
