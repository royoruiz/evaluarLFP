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

.weights-matrix .criterion-info {
    background-color: #fffdf0;
}

.weights-matrix .ra-input-group .input-group-text {
    min-width: 2.5rem;
    justify-content: center;
}

.weights-matrix .ra-weight-row > td.bg-warning-subtle {
    background-color: #fff4c2 !important;
}

.weights-matrix .ra-total-indicator,
.weights-matrix .ce-share-indicator {
    font-variant-numeric: tabular-nums;
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

                            <?php
                            $oldWeights = $old['weights'] ?? [];
                            $oldCriterionUnitWeights = $old['criterion_unit_weights'] ?? [];
                            ?>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle weights-matrix">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="text-nowrap">RA</th>
                                            
                                            <th scope="col">Criterios de evaluación</th>
                                            <th scope="col" class="text-center bg-warning-subtle text-nowrap">Peso CE</th>
                                            <?php foreach ($units as $unit): ?>
                                                <?php $unitId = (int) $unit['id']; ?>
                                                <th scope="col" class="text-center bg-warning-subtle text-nowrap">Peso <?= htmlspecialchars($unitLabels[$unitId] ?? $unit['unit_label']) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($weightsMatrix as $raCode => $raData): ?>
                                            <?php
                                            $criteriaList = $raData['criteria'] ?? [];
                                            if (empty($criteriaList)) {
                                                continue;
                                            }

                                            $processedCriteria = [];
                                            $raDisplayedTotal = 0.0;

                                            foreach ($criteriaList as $criterionCode => $criterionInfo) {
                                                $rawWeight = $oldWeights[$raCode][$criterionCode] ?? ($criterionInfo['weight'] ?? 0.0);
                                                $weightValue = number_format((float) $rawWeight, 2, '.', '');
                                                $raDisplayedTotal += (float) $weightValue;

                                                $unitShares = [];
                                                foreach ($units as $unit) {
                                                    $unitId = (int) $unit['id'];
                                                    if (isset($criterionInfo['unit_shares'][$unitId])) {
                                                        $shareRaw = $oldCriterionUnitWeights[$raCode][$criterionCode][$unitId] ?? $criterionInfo['unit_shares'][$unitId];
                                                        $unitShares[$unitId] = number_format((float) $shareRaw, 2, '.', '');
                                                    } elseif (isset($oldCriterionUnitWeights[$raCode][$criterionCode][$unitId])) {
                                                        $unitShares[$unitId] = number_format((float) $oldCriterionUnitWeights[$raCode][$criterionCode][$unitId], 2, '.', '');
                                                    } else {
                                                        $unitShares[$unitId] = null;
                                                    }
                                                }

                                                $shareTotal = 0.0;
                                                foreach ($unitShares as $shareValue) {
                                                    if ($shareValue !== null) {
                                                        $shareTotal += (float) $shareValue;
                                                    }
                                                }

                                                $processedCriteria[] = [
                                                    'code' => $criterionInfo['code'] ?? $criterionCode,
                                                    'letra' => $criterionInfo['letra'] ?? '',
                                                    'descripcion' => $criterionInfo['descripcion'] ?? '',
                                                    'weight' => $weightValue,
                                                    'shares' => $unitShares,
                                                    'share_total' => number_format($shareTotal, 2, '.', ''),
                                                ];
                                            }

                                            $rowspan = count($processedCriteria);
                                            if ($rowspan === 0) {
                                                continue;
                                            }

                                            $raNumber = $raData['numero'] !== '' ? $raData['numero'] : $raData['codigo'];
                                            $raTotalFormatted = number_format($raDisplayedTotal, 2, '.', '');
                                            ?>
                                            <?php foreach ($processedCriteria as $index => $criterion): ?>
                                                <tr class="ra-weight-row" data-ra-code="<?= htmlspecialchars($raCode) ?>">
                                                    <?php if ($index === 0): ?>
                                                        <?php
                                                        $raDescription = trim((string) ($raData['descripcion'] ?? ''));
                                                        $raTooltip = preg_replace('/\s+/u', ' ', $raDescription);
                                                        ?>
                                                        <td class="fw-semibold text-nowrap align-top" rowspan="<?= $rowspan ?>">
                                                            <span
                                                                class="d-inline-block"
                                                                data-bs-toggle="tooltip"
                                                                data-bs-placement="top"
                                                                title="<?= htmlspecialchars($raTooltip ?? '') ?>"
                                                            >
                                                                RA <?= htmlspecialchars($raNumber) ?>
                                                            </span>
                                                            <div class="small text-muted mt-2">
                                                                Total RA: <span class="fw-semibold ra-total-indicator"><?= htmlspecialchars($raTotalFormatted) ?></span>%
                                                            </div>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td class="align-top">
                                                        <div class="criterion-info border rounded-2 p-2 bg-white h-100">
                                                            <?php
                                                            $criterionTooltip = preg_replace('/\s+/u', ' ', trim((string) $criterion['descripcion']));
                                                            ?>
                                                            <div
                                                                class="fw-semibold text-nowrap"
                                                                data-bs-toggle="tooltip"
                                                                data-bs-placement="top"
                                                                title="<?= htmlspecialchars($criterionTooltip ?? '') ?>"
                                                            >
                                                                CE <?= htmlspecialchars($criterion['letra'] !== '' ? $criterion['letra'] : $criterion['code']) ?>
                                                            </div>
                                                            <div class="small text-muted mt-2">Total unidades: <span class="fw-semibold ce-share-indicator"><?= htmlspecialchars($criterion['share_total']) ?></span>%</div>
                                                        </div>
                                                    </td>
                                                    <td class="bg-warning-subtle align-top">
                                                        <div class="input-group input-group-sm ra-input-group" style="width: 110px;">
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                max="100"
                                                                class="form-control text-end ce-weight-input"
                                                                name="weights[<?= htmlspecialchars($raCode) ?>][<?= htmlspecialchars($criterion['code']) ?>]"
                                                                value="<?= htmlspecialchars($criterion['weight']) ?>"
                                                            >
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </td>
                                                    <?php foreach ($units as $unit): ?>
                                                        <?php $unitId = (int) $unit['id']; ?>
                                                        <td class="bg-warning-subtle text-center align-top">
                                                            <?php if ($criterion['shares'][$unitId] !== null): ?>
                                                                <div class="input-group input-group-sm justify-content-end" style="width: 110px; margin-left: auto;">
                                                                    <input
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        max="100"
                                                                        class="form-control text-end ce-unit-input"
                                                                        name="criterion_unit_weights[<?= htmlspecialchars($raCode) ?>][<?= htmlspecialchars($criterion['code']) ?>][<?= $unitId ?>]"
                                                                        value="<?= htmlspecialchars($criterion['shares'][$unitId]) ?>"
                                                                    >
                                                                    <span class="input-group-text">%</span>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between mt-3">
                                <a href="/modulos/configurar?id=<?= (int) ($module['id'] ?? 0) ?>&paso=criterios" class="btn btn-outline-secondary">Volver</a>
                                <button type="submit" class="btn btn-primary">Guardar y continuar</button>
                            </div>
                        </form>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var tooltipElements = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                                if (tooltipElements.length) {
                                    var tooltipConstructor = null;
                                    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip === 'function') {
                                        tooltipConstructor = bootstrap.Tooltip;
                                    } else if (window.bootstrap && typeof window.bootstrap.Tooltip === 'function') {
                                        tooltipConstructor = window.bootstrap.Tooltip;
                                    }

                                    if (tooltipConstructor) {
                                        tooltipElements.forEach(function (el) {
                                            new tooltipConstructor(el);
                                        });
                                    }
                                }

                                var rows = document.querySelectorAll('.ra-weight-row');
                                if (!rows.length) {
                                    return;
                                }

                                var normalizeValue = function (input) {
                                    var value = parseFloat(input.value);
                                    if (!isFinite(value)) {
                                        value = 0;
                                    }
                                    if (value < 0) {
                                        value = 0;
                                    }
                                    if (value > 100) {
                                        value = 100;
                                    }
                                    input.value = value.toFixed(2);
                                    return value;
                                };

                                var groups = {};

                                rows.forEach(function (row) {
                                    var raCode = row.getAttribute('data-ra-code');
                                    if (!raCode) {
                                        return;
                                    }

                                    if (!groups[raCode]) {
                                        groups[raCode] = {
                                            rows: [],
                                            indicator: null
                                        };
                                    }

                                    groups[raCode].rows.push(row);

                                    if (!groups[raCode].indicator) {
                                        var indicator = row.querySelector('.ra-total-indicator');
                                        if (indicator) {
                                            groups[raCode].indicator = indicator;
                                        }
                                    }
                                });

                                Object.values(groups).forEach(function (group) {
                                    var indicator = group.indicator;
                                    if (!indicator) {
                                        return;
                                    }

                                    var inputs = [];
                                    group.rows.forEach(function (row) {
                                        inputs = inputs.concat(Array.from(row.querySelectorAll('.ce-weight-input')));
                                    });

                                    var updateTotal = function () {
                                        var total = 0;
                                        inputs.forEach(function (input) {
                                            var value = parseFloat(input.value);
                                            if (!isFinite(value)) {
                                                value = 0;
                                            }
                                            total += value;
                                        });

                                        indicator.textContent = total.toFixed(2);
                                        indicator.classList.toggle('text-danger', Math.abs(total - 100) > 0.5);
                                    };

                                    inputs.forEach(function (input) {
                                        input.addEventListener('input', updateTotal);
                                        input.addEventListener('blur', function () {
                                            normalizeValue(input);
                                            updateTotal();
                                        });
                                    });

                                    updateTotal();
                                });

                                rows.forEach(function (row) {
                                    var shareIndicator = row.querySelector('.ce-share-indicator');
                                    if (!shareIndicator) {
                                        return;
                                    }

                                    var unitInputs = Array.from(row.querySelectorAll('.ce-unit-input'));
                                    if (!unitInputs.length) {
                                        shareIndicator.textContent = '0.00';
                                        shareIndicator.classList.add('text-danger');
                                        return;
                                    }

                                    var updateShare = function () {
                                        var total = 0;
                                        unitInputs.forEach(function (input) {
                                            var value = parseFloat(input.value);
                                            if (!isFinite(value)) {
                                                value = 0;
                                            }
                                            total += value;
                                        });

                                        shareIndicator.textContent = total.toFixed(2);
                                        shareIndicator.classList.toggle('text-danger', Math.abs(total - 100) > 0.5);
                                    };

                                    unitInputs.forEach(function (input) {
                                        input.addEventListener('input', updateShare);
                                        input.addEventListener('blur', function () {
                                            normalizeValue(input);
                                            updateShare();
                                        });
                                    });

                                    updateShare();
                                });
                            });
                        </script>
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
                                                            <td class="text-end"><?= htmlspecialchars(number_format((float) $info['weight'], 2, '.', '')) ?>%</td>
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
                                                        <td class="text-end"><?= htmlspecialchars(number_format((float) $info['weight'], 2, '.', '')) ?>%</td>
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
