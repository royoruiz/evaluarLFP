<?php
$module = $module ?? [];
$step = $step ?? 'unidades';
$steps = $steps ?? [];
$units = $units ?? [];
$availableCriteria = $availableCriteria ?? [];
$selectedCodesByUnit = $selectedCodesByUnit ?? [];
$criteriaDetailsByUnit = $criteriaDetailsByUnit ?? [];
$summaryByUnit = $summaryByUnit ?? [];
$summaryTotals = $summaryTotals ?? [];
$weightsMatrix = $weightsMatrix ?? [];
$errors = $errors ?? [];
$old = $old ?? [];

$stepLabels = [
    'unidades' => 'Número de unidades',
    'trimestres' => 'Asignación por trimestre',
    'criterios' => 'Criterios de evaluación por unidad',
    'pesos' => 'Pesos de criterios',
    'resumen' => 'Resumen de resultados de aprendizaje',
];

$currentState = $module['creation_state'] ?? 'unidades';
$currentStepIndex = array_search($step, $steps, true);
if ($currentStepIndex === false) {
    $currentStepIndex = 0;
}

$stateIndex = array_search($currentState, $steps, true);
if ($currentState === 'completado') {
    $stateIndex = count($steps) - 1;
}
if ($stateIndex === false) {
    $stateIndex = 0;
}

$maxAccessibleIndex = max($stateIndex, $currentStepIndex);

$unitLabels = [];
foreach ($units as $unit) {
    $unitLabels[(int) $unit['id']] = $unit['unit_label'];
}
?>

<style>
@media (min-width: 992px) {
    .module-config-layout {
        display: flex;
        gap: 1.5rem;
        align-items: flex-start;
    }

    .module-config-sidebar {
        flex: 0 0 15%;
        max-width: 15%;
    }

    .module-config-content {
        flex: 1;
    }
}

@media (max-width: 991.98px) {
    .module-config-sidebar {
        margin-bottom: 1.5rem;
    }
}
</style>

<div class="module-config-layout">
    <aside class="module-config-sidebar">
        <div class="bg-light rounded-3 p-3 h-100">
            <div class="nav nav-pills flex-lg-column gap-2 w-100" role="tablist" aria-orientation="vertical">
                <a class="nav-link active" href="#">Módulos</a>
                <a class="nav-link" href="/?tab=evaluations">Evaluaciones</a>
            </div>
        </div>
    </aside>

    <div class="module-config-content">
        <h1 class="h4 mb-4">Configurar módulo</h1>
        <div class="mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                <div>
                    <h2 class="h5 mb-1"><?= htmlspecialchars($module['module_name'] ?? 'Módulo sin nombre') ?></h2>
                    <p class="mb-0 text-muted">
                        <?php if (!empty($module['module_code'])): ?>
                            Código: <strong><?= htmlspecialchars($module['module_code']) ?></strong>
                        <?php else: ?>
                            Código no vinculado al catálogo
                        <?php endif; ?>
                    </p>
                </div>
                <div class="mt-3 mt-lg-0">
                    <a href="/" class="btn btn-outline-secondary">Volver al panel</a>
                </div>
            </div>
        </div>

<ul class="nav nav-tabs flex-column flex-lg-row mb-4" id="module-config-tabs" role="tablist">
    <?php foreach ($steps as $index => $stepKey): ?>
        <?php
        $label = $stepLabels[$stepKey] ?? ucfirst($stepKey);
        $isActive = $index === $currentStepIndex;
        $isCompleted = $index < $stateIndex;
        $isEnabled = $index <= $maxAccessibleIndex;
        $tabId = 'tab-' . $stepKey;
        $linkClasses = 'nav-link w-100 text-center';
        if ($isActive) {
            $linkClasses .= ' active';
        }
        if (!$isEnabled) {
            $linkClasses .= ' disabled';
        }
        ?>
        <li class="nav-item flex-fill" role="presentation">
            <?php if ($isEnabled): ?>
                <a
                    class="<?= $linkClasses ?>"
                    id="<?= htmlspecialchars($tabId) ?>-tab"
                    href="/modulos/configurar?id=<?= (int) ($module['id'] ?? 0) ?>&paso=<?= htmlspecialchars($stepKey) ?>"
                    role="tab"
                    aria-controls="<?= htmlspecialchars($tabId) ?>"
                    aria-selected="<?= $isActive ? 'true' : 'false' ?>"
                >
                    <?php if ($isCompleted): ?>
                        <span class="badge bg-success me-2">✓</span>
                    <?php endif; ?>
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php else: ?>
                <span
                    class="<?= $linkClasses ?>"
                    id="<?= htmlspecialchars($tabId) ?>-tab"
                    role="tab"
                    aria-selected="false"
                >
                    <?= htmlspecialchars($label) ?>
                </span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content" id="module-config-tabs-content">
    <?php foreach ($steps as $stepKey): ?>
        <?php $isActive = $step === $stepKey; ?>
        <div
            class="tab-pane fade<?php if ($isActive): ?> show active<?php endif; ?>"
            id="tab-<?= htmlspecialchars($stepKey) ?>"
            role="tabpanel"
            aria-labelledby="tab-<?= htmlspecialchars($stepKey) ?>-tab"
        >
            <?php if ($stepKey === 'unidades'): ?>
                <?php if ($isActive): ?>
                    <form method="post" action="/modulos/configurar" class="mt-3">
                        <input type="hidden" name="module_id" value="<?= (int) ($module['id'] ?? 0) ?>">
                        <input type="hidden" name="step" value="unidades">

                        <div class="mb-3">
                            <label for="units_count" class="form-label">Número de unidades</label>
                            <?php
                            $unitsValue = $old['units_count'] ?? ($module['units_count'] ?? '');
                            if ((int) $unitsValue <= 0) {
                                $unitsValue = '';
                            }
                            ?>
                            <input
                                type="number"
                                min="1"
                                max="20"
                                class="form-control<?php if (!empty($errors['units_count'])): ?> is-invalid<?php endif; ?>"
                                id="units_count"
                                name="units_count"
                                value="<?= htmlspecialchars((string) $unitsValue) ?>"
                                required
                            >
                            <div class="form-text">Indica cuántas unidades didácticas tendrá el módulo (máximo 20).</div>
                            <?php if (!empty($errors['units_count'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['units_count']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Guardar y continuar</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Selecciona este paso para definir el número de unidades del módulo.</p>
                <?php endif; ?>
            <?php elseif ($stepKey === 'trimestres'): ?>
                <?php if ($isActive): ?>
                    <?php if (empty($units)): ?>
                        <div class="alert alert-warning">Primero debes definir el número de unidades.</div>
                    <?php else: ?>
                        <form method="post" action="/modulos/configurar">
                            <input type="hidden" name="module_id" value="<?= (int) ($module['id'] ?? 0) ?>">
                            <input type="hidden" name="step" value="trimestres">

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col">Unidad</th>
                                            <th scope="col" class="text-center">1.º trimestre</th>
                                            <th scope="col" class="text-center">2.º trimestre</th>
                                            <th scope="col" class="text-center">3.º trimestre</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($units as $unit): ?>
                                            <?php $unitId = (int) $unit['id']; ?>
                                            <tr>
                                                <th scope="row"><?= htmlspecialchars($unit['unit_label']) ?></th>
                                                <td class="text-center">
                                                    <div class="form-check justify-content-center d-inline-flex">
                                                        <input class="form-check-input" type="checkbox" id="unit<?= $unitId ?>-t1" name="trimesters[<?= $unitId ?>][]" value="1"<?php if ((int) $unit['trimester_1'] === 1): ?> checked<?php endif; ?>>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check justify-content-center d-inline-flex">
                                                        <input class="form-check-input" type="checkbox" id="unit<?= $unitId ?>-t2" name="trimesters[<?= $unitId ?>][]" value="2"<?php if ((int) $unit['trimester_2'] === 1): ?> checked<?php endif; ?>>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check justify-content-center d-inline-flex">
                                                        <input class="form-check-input" type="checkbox" id="unit<?= $unitId ?>-t3" name="trimesters[<?= $unitId ?>][]" value="3"<?php if ((int) $unit['trimester_3'] === 1): ?> checked<?php endif; ?>>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/modulos/configurar?id=<?= (int) ($module['id'] ?? 0) ?>&paso=unidades" class="btn btn-outline-secondary">Volver</a>
                                <button type="submit" class="btn btn-primary">Guardar y continuar</button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Selecciona este paso para asignar las unidades a cada trimestre.</p>
                <?php endif; ?>
            <?php elseif ($stepKey === 'criterios'): ?>
                <?php if ($isActive): ?>
                    <?php if (empty($units)): ?>
                        <div class="alert alert-warning">Primero debes definir las unidades antes de asignar criterios.</div>
                    <?php elseif (empty($availableCriteria)): ?>
                        <div class="alert alert-info">Todavía no hay criterios de evaluación asociados a este módulo en el catálogo.</div>
                    <?php else: ?>
                        <style>
                            .unit-criteria-layout {
                                display: flex;
                                flex-direction: column;
                            }

                            @media (min-width: 992px) {
                                .unit-criteria-layout {
                                    flex-direction: row;
                                    gap: 1.5rem;
                                }

                                .unit-criteria-tabs {
                                    flex: 0 0 220px;
                                }
                            }

                            .unit-criteria-tabs .nav-link {
                                text-align: left;
                            }

                            .ra-card-header {
                                display: flex;
                                align-items: center;
                                justify-content: space-between;
                                gap: 0.75rem;
                                padding-right: 1rem;
                            }

                            .ra-toggle {
                                background-color: transparent;
                                border: 0;
                                padding: 1rem;
                                width: 100%;
                                text-align: left;
                                display: flex;
                                align-items: flex-start;
                                justify-content: space-between;
                                gap: 1rem;
                                font-weight: 600;
                            }

                            .ra-toggle .toggle-icon::before {
                                content: '+';
                                font-size: 1.25rem;
                                line-height: 1;
                                display: inline-block;
                            }

                            .ra-toggle:not(.collapsed) .toggle-icon::before {
                                content: '\2212';
                            }

                            .ra-toggle .ra-text {
                                flex: 1;
                            }

                            .ra-toggle .ra-text .ra-description {
                                font-weight: 400;
                                font-size: 0.9rem;
                                color: #6c757d;
                                margin-top: 0.25rem;
                            }

                            .criteria-list .list-group-item {
                                display: flex;
                                align-items: flex-start;
                                justify-content: space-between;
                                gap: 1rem;
                            }

                            .criteria-list .criteria-text {
                                flex: 1;
                            }

                            .criteria-list .form-check-input {
                                margin-top: 0.35rem;
                            }

                            .ra-select-check {
                                display: flex;
                                align-items: center;
                                padding: 0 0.5rem;
                            }
                        </style>

                        <form method="post" action="/modulos/configurar">
                            <input type="hidden" name="module_id" value="<?= (int) ($module['id'] ?? 0) ?>">
                            <input type="hidden" name="step" value="criterios">

                            <div class="unit-criteria-layout">
                                <div class="unit-criteria-tabs mb-3 mb-lg-0">
                                    <div class="nav flex-lg-column nav-pills" id="unit-tabs" role="tablist" aria-orientation="vertical">
                                        <?php foreach ($units as $index => $unit): ?>
                                            <?php $unitId = (int) $unit['id']; ?>
                                            <button
                                                class="nav-link<?php if ($index === 0): ?> active<?php endif; ?>"
                                                id="tab-unit-<?= $unitId ?>"
                                                data-bs-toggle="tab"
                                                data-bs-target="#unit-pane-<?= $unitId ?>"
                                                type="button"
                                                role="tab"
                                                aria-controls="unit-pane-<?= $unitId ?>"
                                                aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                                            >
                                                <?= htmlspecialchars($unit['unit_label']) ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="tab-content flex-grow-1" id="unit-tab-content">
                                    <?php foreach ($units as $index => $unit): ?>
                                        <?php
                                        $unitId = (int) $unit['id'];
                                        $selectedForUnit = $selectedCodesByUnit[$unitId] ?? [];
                                        ?>
                                        <div
                                            class="tab-pane fade<?php if ($index === 0): ?> show active<?php endif; ?>"
                                            id="unit-pane-<?= $unitId ?>"
                                            role="tabpanel"
                                            aria-labelledby="tab-unit-<?= $unitId ?>"
                                        >
                                            <div class="accordion" id="accordion-unit-<?= $unitId ?>">
                                                <?php foreach ($availableCriteria as $outcome): ?>
                                                    <?php
                                                    $sanitizedCode = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $outcome['codigo']);
                                                    $collapseId = 'collapse-unit-' . $unitId . '-' . $sanitizedCode;
                                                    $raCheckboxId = 'ra-select-' . $unitId . '-' . $sanitizedCode;
                                                    $criterionCodes = array_column($outcome['criteria'], 'codigo');
                                                    $selectedCount = count(array_intersect($criterionCodes, $selectedForUnit));
                                                    $totalCriteria = count($criterionCodes);
                                                    $raInitialState = 'none';
                                                    if ($totalCriteria > 0 && $selectedCount === $totalCriteria) {
                                                        $raInitialState = 'all';
                                                    } elseif ($selectedCount > 0) {
                                                        $raInitialState = 'partial';
                                                    }
                                                    ?>
                                                    <div class="card mb-3 ra-card" data-unit="<?= $unitId ?>" data-ra-code="<?= htmlspecialchars($outcome['codigo']) ?>">
                                                        <div class="card-header p-0 bg-white border-0">
                                                            <div class="ra-card-header">
                                                                <button
                                                                    class="ra-toggle collapsed"
                                                                    type="button"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#<?= htmlspecialchars($collapseId) ?>"
                                                                    aria-expanded="false"
                                                                    aria-controls="<?= htmlspecialchars($collapseId) ?>"
                                                                >
                                                                    <span class="ra-text">
                                                                        <span>RA <?= htmlspecialchars($outcome['numero']) ?></span>
                                                                        <span class="ra-description"><?= htmlspecialchars($outcome['descripcion']) ?></span>
                                                                    </span>
                                                                    <span class="toggle-icon" aria-hidden="true"></span>
                                                                </button>
                                                                <div class="ra-select-check form-check mb-0">
                                                                    <input
                                                                        class="form-check-input ra-select-all"
                                                                        type="checkbox"
                                                                        id="<?= htmlspecialchars($raCheckboxId) ?>"
                                                                        data-initial-state="<?= $raInitialState ?>"
                                                                        <?php if ($raInitialState === 'all'): ?>checked<?php endif; ?>
                                                                    >
                                                                    <label class="form-check-label visually-hidden" for="<?= htmlspecialchars($raCheckboxId) ?>">
                                                                        Seleccionar todos los criterios del RA <?= htmlspecialchars($outcome['numero']) ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div
                                                            id="<?= htmlspecialchars($collapseId) ?>"
                                                            class="collapse"
                                                            data-bs-parent="#accordion-unit-<?= $unitId ?>"
                                                        >
                                                            <div class="list-group list-group-flush criteria-list">
                                                                <?php foreach ($outcome['criteria'] as $criterion): ?>
                                                                    <?php
                                                                    $code = $criterion['codigo'];
                                                                    $inputId = 'unit' . $unitId . '-' . $code;
                                                                    $isChecked = in_array($code, $selectedForUnit, true);
                                                                    ?>
                                                                    <label class="list-group-item">
                                                                        <span class="criteria-text">
                                                                            <span class="fw-semibold">CE <?= htmlspecialchars($criterion['letra']) ?>.</span>
                                                                            <span class="d-block small text-muted mt-1"><?= htmlspecialchars($criterion['descripcion']) ?></span>
                                                                        </span>
                                                                        <input
                                                                            class="form-check-input ra-criteria-checkbox"
                                                                            type="checkbox"
                                                                            id="<?= htmlspecialchars($inputId) ?>"
                                                                            name="criteria[<?= $unitId ?>][]"
                                                                            value="<?= htmlspecialchars($code) ?>"
                                                                            <?php if ($isChecked): ?>checked<?php endif; ?>
                                                                        >
                                                                    </label>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="/modulos/configurar?id=<?= (int) ($module['id'] ?? 0) ?>&paso=trimestres" class="btn btn-outline-secondary">Volver</a>
                                <button type="submit" class="btn btn-primary">Guardar y continuar</button>
                            </div>
                        </form>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                document.querySelectorAll('.ra-card').forEach(function (card) {
                                    var raCheckbox = card.querySelector('.ra-select-all');
                                    if (!raCheckbox) {
                                        return;
                                    }

                                    var criteriaCheckboxes = card.querySelectorAll('.ra-criteria-checkbox');
                                    if (!criteriaCheckboxes.length) {
                                        raCheckbox.disabled = true;
                                        return;
                                    }

                                    var applyInitialState = function () {
                                        var state = raCheckbox.getAttribute('data-initial-state');
                                        if (state === 'all') {
                                            raCheckbox.checked = true;
                                            raCheckbox.indeterminate = false;
                                        } else if (state === 'partial') {
                                            raCheckbox.checked = false;
                                            raCheckbox.indeterminate = true;
                                        } else {
                                            raCheckbox.checked = false;
                                            raCheckbox.indeterminate = false;
                                        }
                                    };

                                    var syncFromChildren = function () {
                                        var total = criteriaCheckboxes.length;
                                        var checked = 0;

                                        criteriaCheckboxes.forEach(function (checkbox) {
                                            if (checkbox.checked) {
                                                checked += 1;
                                            }
                                        });

                                        if (checked === 0) {
                                            raCheckbox.checked = false;
                                            raCheckbox.indeterminate = false;
                                        } else if (checked === total) {
                                            raCheckbox.checked = true;
                                            raCheckbox.indeterminate = false;
                                        } else {
                                            raCheckbox.checked = false;
                                            raCheckbox.indeterminate = true;
                                        }
                                    };

                                    applyInitialState();

                                    raCheckbox.addEventListener('change', function () {
                                        var shouldCheckAll = raCheckbox.checked;
                                        raCheckbox.indeterminate = false;

                                        criteriaCheckboxes.forEach(function (checkbox) {
                                            checkbox.checked = shouldCheckAll;
                                        });

                                        syncFromChildren();
                                    });

                                    criteriaCheckboxes.forEach(function (checkbox) {
                                        checkbox.addEventListener('change', function () {
                                            raCheckbox.setAttribute('data-initial-state', 'updated');
                                            syncFromChildren();
                                        });
                                    });

                                    syncFromChildren();
                                });
                            });
                        </script>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Selecciona este paso para escoger los criterios de evaluación por unidad.</p>
                <?php endif; ?>
            <?php elseif ($stepKey === 'pesos'): ?>
                <?php if ($isActive): ?>
                    <?php if (empty($weightsMatrix)): ?>
                        <div class="alert alert-info">Selecciona al menos un criterio para cada unidad antes de definir los pesos.</div>
                    <?php else: ?>
                        <form method="post" action="/modulos/configurar">
                            <input type="hidden" name="module_id" value="<?= (int) ($module['id'] ?? 0) ?>">
                            <input type="hidden" name="step" value="pesos">

                            <?php if (!empty($errors['weights'])): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($errors['weights']) ?></div>
                            <?php endif; ?>

                            <?php $oldWeights = $old['weights'] ?? []; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle weights-matrix">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="text-nowrap">Código</th>
                                            <th scope="col" class="text-nowrap">Resultados de aprendizaje</th>
                                            <th scope="col">Criterios de evaluación</th>
                                            <?php foreach ($units as $unit): ?>
                                                <?php $unitId = (int) $unit['id']; ?>
                                                <th scope="col" class="text-center bg-warning-subtle text-nowrap">Peso <?= htmlspecialchars($unitLabels[$unitId] ?? $unit['unit_label']) ?></th>
                                            <?php endforeach; ?>
                                            <th scope="col" class="text-center bg-warning-subtle text-nowrap">Peso RA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $unitTotals = [];
                                        foreach ($units as $unit) {
                                            $unitTotals[(int) $unit['id']] = 0.0;
                                        }
                                        ?>
                                        <?php foreach ($weightsMatrix as $raCode => $raData): ?>
                                            <?php
                                            $raUnitSums = [];
                                            foreach ($units as $unit) {
                                                $unitId = (int) $unit['id'];
                                                $sum = 0.0;
                                                if (!empty($raData['criteria'])) {
                                                    foreach ($raData['criteria'] as $criterion) {
                                                        if ((int) $criterion['unit_id'] !== $unitId) {
                                                            continue;
                                                        }
                                                        $value = $oldWeights[$unitId][$criterion['code']] ?? $criterion['weight'];
                                                        $sum += (float) $value;
                                                    }
                                                }
                                                $raUnitSums[$unitId] = $sum;
                                                $unitTotals[$unitId] += $sum;
                                            }
                                            $raTotal = array_sum($raUnitSums);
                                            $raNumber = $raData['numero'] !== '' ? $raData['numero'] : $raData['codigo'];
                                            ?>
                                            <tr class="table-secondary fw-semibold">
                                                <td>RA <?= htmlspecialchars($raNumber) ?></td>
                                                <td colspan="2"><?= htmlspecialchars($raData['descripcion'] ?? '') ?></td>
                                                <?php foreach ($units as $unit): ?>
                                                    <?php $unitId = (int) $unit['id']; ?>
                                                    <td class="bg-warning-subtle">
                                                        <input
                                                            type="number"
                                                            step="0.01"
                                                            min="0"
                                                            max="1"
                                                            class="form-control form-control-sm text-end ra-unit-input"
                                                            name="ra_unit_weights[<?= $unitId ?>][<?= htmlspecialchars($raCode) ?>]"
                                                            data-ra-code="<?= htmlspecialchars($raCode) ?>"
                                                            data-unit-id="<?= $unitId ?>"
                                                            value="<?= htmlspecialchars(number_format((float) $raUnitSums[$unitId], 2, '.', '')) ?>"
                                                        >
                                                    </td>
                                                <?php endforeach; ?>
                                                <td class="bg-warning-subtle">
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max="1"
                                                        class="form-control form-control-sm text-end ra-total-input"
                                                        name="ra_totals[<?= htmlspecialchars($raCode) ?>]"
                                                        data-ra-code="<?= htmlspecialchars($raCode) ?>"
                                                        value="<?= htmlspecialchars(number_format((float) $raTotal, 2, '.', '')) ?>"
                                                    >
                                                </td>
                                            </tr>
                                            <?php foreach ($raData['criteria'] as $criterion): ?>
                                                <?php
                                                $unitId = (int) $criterion['unit_id'];
                                                $value = $oldWeights[$unitId][$criterion['code']] ?? $criterion['weight'];
                                                $formattedValue = number_format((float) $value, 2, '.', '');
                                                ?>
                                                <tr>
                                                    <td class="text-muted small">CE <?= htmlspecialchars($criterion['letra'] ?: $criterion['code']) ?></td>
                                                    <td></td>
                                                    <td><?= htmlspecialchars($criterion['descripcion']) ?></td>
                                                    <?php foreach ($units as $unit): ?>
                                                        <?php $cellUnitId = (int) $unit['id']; ?>
                                                        <td<?php if ($cellUnitId === $unitId): ?> class="bg-warning-subtle"<?php endif; ?>>
                                                            <?php if ($cellUnitId === $unitId): ?>
                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    max="1"
                                                                    class="form-control form-control-sm text-end ce-weight-input"
                                                                    name="weights[<?= $cellUnitId ?>][<?= htmlspecialchars($criterion['code']) ?>]"
                                                                    data-ra-code="<?= htmlspecialchars($raCode) ?>"
                                                                    data-unit-id="<?= $cellUnitId ?>"
                                                                    value="<?= htmlspecialchars($formattedValue) ?>"
                                                                >
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                    <td></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                        <tr class="table-light fw-semibold">
                                            <td colspan="3" class="text-end">Total por unidad</td>
                                            <?php foreach ($units as $unit): ?>
                                                <?php $unitId = (int) $unit['id']; ?>
                                                <td>
                                                    <input
                                                        type="number"
                                                        class="form-control form-control-sm text-end unit-total-input"
                                                        data-unit-id="<?= $unitId ?>"
                                                        value="<?= htmlspecialchars(number_format((float) ($unitTotals[$unitId] ?? 0.0), 2, '.', '')) ?>"
                                                        readonly
                                                    >
                                                </td>
                                            <?php endforeach; ?>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    var matrix = document.querySelector('.weights-matrix');
                                    if (!matrix) {
                                        return;
                                    }

                                    var formatValue = function (value) {
                                        if (!isFinite(value)) {
                                            return '0.00';
                                        }
                                        return value.toFixed(2);
                                    };

                                    var updateRaUnitSum = function (raCode, unitId) {
                                        var selector = '.ce-weight-input[data-ra-code="' + raCode + '"][data-unit-id="' + unitId + '"]';
                                        var ceInputs = matrix.querySelectorAll(selector);
                                        var sum = 0;
                                        ceInputs.forEach(function (input) {
                                            var value = parseFloat(input.value);
                                            if (!isNaN(value)) {
                                                sum += value;
                                            }
                                        });

                                        var headerInput = matrix.querySelector('.ra-unit-input[data-ra-code="' + raCode + '"][data-unit-id="' + unitId + '"]');
                                        if (headerInput) {
                                            headerInput.value = formatValue(sum);
                                        }

                                        return sum;
                                    };

                                    var updateRaTotal = function (raCode) {
                                        var total = 0;
                                        matrix.querySelectorAll('.ra-unit-input[data-ra-code="' + raCode + '"]').forEach(function (input) {
                                            var value = parseFloat(input.value);
                                            if (!isNaN(value)) {
                                                total += value;
                                            }
                                        });

                                        var totalInput = matrix.querySelector('.ra-total-input[data-ra-code="' + raCode + '"]');
                                        if (totalInput) {
                                            totalInput.value = formatValue(total);
                                        }

                                        return total;
                                    };

                                    var updateUnitTotals = function () {
                                        matrix.querySelectorAll('.unit-total-input').forEach(function (input) {
                                            var unitId = input.getAttribute('data-unit-id');
                                            var sum = 0;
                                            matrix.querySelectorAll('.ra-unit-input[data-unit-id="' + unitId + '"]').forEach(function (unitInput) {
                                                var value = parseFloat(unitInput.value);
                                                if (!isNaN(value)) {
                                                    sum += value;
                                                }
                                            });
                                            input.value = formatValue(sum);
                                        });
                                    };

                                    var scaleCriteriaForRaUnit = function (raCode, unitId, target) {
                                        var selector = '.ce-weight-input[data-ra-code="' + raCode + '"][data-unit-id="' + unitId + '"]';
                                        var ceInputs = matrix.querySelectorAll(selector);
                                        if (ceInputs.length === 0) {
                                            updateRaUnitSum(raCode, unitId);
                                            return;
                                        }

                                        var currentSum = 0;
                                        ceInputs.forEach(function (input) {
                                            var value = parseFloat(input.value);
                                            if (!isNaN(value)) {
                                                currentSum += value;
                                            }
                                        });

                                        var values = [];
                                        if (currentSum <= 0) {
                                            var equalShare = target / ceInputs.length;
                                            ceInputs.forEach(function () {
                                                values.push(equalShare);
                                            });
                                        } else {
                                            var factor = target / currentSum;
                                            ceInputs.forEach(function (input) {
                                                var value = parseFloat(input.value);
                                                if (isNaN(value)) {
                                                    value = 0;
                                                }
                                                values.push(value * factor);
                                            });
                                        }

                                        values.forEach(function (value, index) {
                                            ceInputs[index].value = formatValue(value);
                                        });

                                        updateRaUnitSum(raCode, unitId);
                                    };

                                    var scaleRaByTotal = function (raCode, target) {
                                        var unitInputs = matrix.querySelectorAll('.ra-unit-input[data-ra-code="' + raCode + '"]');
                                        if (unitInputs.length === 0) {
                                            return;
                                        }

                                        var currentTotal = 0;
                                        unitInputs.forEach(function (input) {
                                            var value = parseFloat(input.value);
                                            if (!isNaN(value)) {
                                                currentTotal += value;
                                            }
                                        });

                                        if (currentTotal <= 0) {
                                            var equal = target / unitInputs.length;
                                            unitInputs.forEach(function (input) {
                                                input.value = formatValue(equal);
                                                var unitId = input.getAttribute('data-unit-id');
                                                scaleCriteriaForRaUnit(raCode, unitId, equal);
                                            });
                                            updateRaTotal(raCode);
                                            return;
                                        }

                                        var factor = target / currentTotal;
                                        unitInputs.forEach(function (input) {
                                            var currentValue = parseFloat(input.value);
                                            if (isNaN(currentValue)) {
                                                currentValue = 0;
                                            }
                                            var newValue = currentValue * factor;
                                            input.value = formatValue(newValue);
                                            var unitId = input.getAttribute('data-unit-id');
                                            scaleCriteriaForRaUnit(raCode, unitId, newValue);
                                        });

                                        updateRaTotal(raCode);
                                    };

                                    matrix.querySelectorAll('.ce-weight-input').forEach(function (input) {
                                        input.addEventListener('input', function (event) {
                                            var targetInput = event.currentTarget;
                                            var raCode = targetInput.getAttribute('data-ra-code');
                                            var unitId = targetInput.getAttribute('data-unit-id');
                                            updateRaUnitSum(raCode, unitId);
                                            updateRaTotal(raCode);
                                            updateUnitTotals();
                                        });
                                    });

                                    matrix.querySelectorAll('.ra-unit-input').forEach(function (input) {
                                        var handler = function (event) {
                                            var targetInput = event.currentTarget;
                                            var raCode = targetInput.getAttribute('data-ra-code');
                                            var unitId = targetInput.getAttribute('data-unit-id');
                                            var desired = parseFloat(targetInput.value);
                                            if (isNaN(desired)) {
                                                return;
                                            }
                                            scaleCriteriaForRaUnit(raCode, unitId, desired);
                                            updateRaTotal(raCode);
                                            updateUnitTotals();
                                        };

                                        input.addEventListener('change', handler);
                                        input.addEventListener('blur', handler);
                                    });

                                    matrix.querySelectorAll('.ra-total-input').forEach(function (input) {
                                        input.addEventListener('change', function (event) {
                                            var targetInput = event.currentTarget;
                                            var raCode = targetInput.getAttribute('data-ra-code');
                                            var desired = parseFloat(targetInput.value);
                                            if (isNaN(desired)) {
                                                return;
                                            }
                                            scaleRaByTotal(raCode, desired);
                                            matrix.querySelectorAll('.ra-unit-input[data-ra-code="' + raCode + '"]').forEach(function (unitInput) {
                                                var unitId = unitInput.getAttribute('data-unit-id');
                                                updateRaUnitSum(raCode, unitId);
                                            });
                                            updateRaTotal(raCode);
                                            updateUnitTotals();
                                        });
                                    });

                                    var initialize = function () {
                                        matrix.querySelectorAll('.ra-unit-input').forEach(function (input) {
                                            var raCode = input.getAttribute('data-ra-code');
                                            var unitId = input.getAttribute('data-unit-id');
                                            updateRaUnitSum(raCode, unitId);
                                        });

                                        matrix.querySelectorAll('.ra-total-input').forEach(function (input) {
                                            var raCode = input.getAttribute('data-ra-code');
                                            updateRaTotal(raCode);
                                        });

                                        updateUnitTotals();
                                    };

                                    initialize();
                                });
                            </script>

                            <p class="text-muted small">Utiliza valores entre 0 y 1. La suma por unidad debe ser exactamente 1.</p>

                            <div class="d-flex justify-content-between">
                                <a href="/modulos/configurar?id=<?= (int) ($module['id'] ?? 0) ?>&paso=criterios" class="btn btn-outline-secondary">Volver</a>
                                <button type="submit" class="btn btn-primary">Guardar y continuar</button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Selecciona este paso para repartir los pesos de los criterios.</p>
                <?php endif; ?>
            <?php elseif ($stepKey === 'resumen'): ?>
                <?php if ($isActive): ?>
                    <div class="mb-4">
                        <p class="text-muted mb-2">Consulta la distribución de pesos por resultados de aprendizaje en cada unidad.</p>
                        <?php if ($currentState === 'completado'): ?>
                            <div class="alert alert-success">Este módulo ya está marcado como completado.</div>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($summaryByUnit)): ?>
                        <div class="alert alert-info">Todavía no se han asignado criterios con pesos a este módulo.</div>
                    <?php else: ?>
                        <?php foreach ($units as $unit): ?>
                            <?php $unitId = (int) $unit['id']; ?>
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <strong><?= htmlspecialchars($unit['unit_label']) ?></strong>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($summaryByUnit[$unitId])): ?>
                                        <p class="text-muted mb-0">Esta unidad no tiene criterios asociados.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                      <tr>
                                                          <th scope="col">Resultado de aprendizaje</th>
                                                          <th scope="col" class="text-end">Peso total</th>
                                                      </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($summaryByUnit[$unitId] as $raCode => $info): ?>
                                                        <tr>
                                                            <td>RA <?= htmlspecialchars($info['numero']) ?> — <?= htmlspecialchars($info['descripcion']) ?></td>
                                                            <td class="text-end"><?= htmlspecialchars(number_format((float) $info['weight'], 2, '.', '')) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!empty($summaryTotals)): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-secondary text-white">
                                    <strong>Peso total por resultado de aprendizaje</strong>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-0">
                                            <tbody>
                                                <?php foreach ($summaryTotals as $raCode => $info): ?>
                                                    <tr>
                                                        <th scope="row" class="w-75">RA <?= htmlspecialchars($info['numero']) ?> — <?= htmlspecialchars($info['descripcion']) ?></th>
                                                        <td class="text-end"><?= htmlspecialchars(number_format((float) $info['weight'], 2, '.', '')) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($currentState !== 'completado' && !empty($summaryByUnit)): ?>
                        <form method="post" action="/modulos/configurar" class="mt-3">
                            <input type="hidden" name="module_id" value="<?= (int) ($module['id'] ?? 0) ?>">
                            <input type="hidden" name="step" value="finalizar">
                            <div class="d-flex justify-content-between">
                                <a href="/modulos/configurar?id=<?= (int) ($module['id'] ?? 0) ?>&paso=pesos" class="btn btn-outline-secondary">Volver</a>
                                <button type="submit" class="btn btn-success">Marcar como completado</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="d-flex justify-content-between">
                            <a href="/modulos/configurar?id=<?= (int) ($module['id'] ?? 0) ?>&paso=pesos" class="btn btn-outline-secondary">Volver</a>
                            <a href="/" class="btn btn-primary">Volver al panel</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Selecciona este paso para revisar el resumen del módulo.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

    </div>
</div>
