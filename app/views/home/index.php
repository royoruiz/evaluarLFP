<?php
$modules = $modules ?? [];
$evaluations = $evaluations ?? [];
$catalogModules = $catalogModules ?? [];
$activeTab = $activeTab ?? 'modules';
$showModuleWizard = $showModuleWizard ?? false;
$errors = $errors ?? [];
$old = $old ?? [];
$wizardErrors = $errors['module_wizard'] ?? [];
$wizardOld = $old['module_wizard'] ?? [];
?>

<style>
@media (min-width: 992px) {
    .home-layout {
        display: flex;
        gap: 1.5rem;
        align-items: stretch;
    }

    .home-sidebar {
        flex: 0 0 15%;
        max-width: 15%;
    }

    .home-content {
        flex: 1;
    }
}

@media (max-width: 991.98px) {
    .home-sidebar {
        margin-bottom: 1.5rem;
    }
}
</style>

<h1 class="h3 mb-3">Bienvenido, <?= htmlspecialchars($user['name']) ?></h1>
<p class="text-muted">Gestiona tus módulos y evaluaciones desde este panel.</p>

<div class="home-layout mt-2">
    <aside class="home-sidebar">
        <div class="bg-light rounded-3 p-3 h-100">
            <div class="nav nav-pills flex-lg-column gap-2 w-100" id="home-tabs" role="tablist" aria-orientation="vertical">
                <button
                    class="nav-link w-100 text-start<?php if ($activeTab === 'modules'): ?> active<?php endif; ?>"
                    id="tab-button-modules"
                    data-bs-toggle="tab"
                    data-bs-target="#tab-modules"
                    type="button"
                    role="tab"
                    aria-controls="tab-modules"
                    aria-selected="<?= $activeTab === 'modules' ? 'true' : 'false' ?>"
                >
                    Módulos
                </button>
                <button
                    class="nav-link w-100 text-start<?php if ($activeTab === 'evaluations'): ?> active<?php endif; ?>"
                    id="tab-button-evaluations"
                    data-bs-toggle="tab"
                    data-bs-target="#tab-evaluations"
                    type="button"
                    role="tab"
                    aria-controls="tab-evaluations"
                    aria-selected="<?= $activeTab === 'evaluations' ? 'true' : 'false' ?>"
                >
                    Evaluaciones
                </button>
            </div>
        </div>
    </aside>

    <div class="home-content">
        <div class="tab-content" id="home-tabs-content">
            <div class="tab-pane fade<?php if ($activeTab === 'modules'): ?> show active<?php endif; ?>" id="tab-modules" role="tabpanel" aria-labelledby="tab-button-modules">
                <h2 class="h5 mb-3">Módulos asignados</h2>

                <?php if (empty($modules)): ?>
                    <div class="alert alert-info">Todavía no tienes módulos asignados.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Módulo</th>
                                    <th scope="col" class="text-nowrap">Fecha de alta</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modules as $module): ?>
                                    <?php
                                    $createdAt = $module['created_at'] ?? null;
                                    $formattedDate = $createdAt ? date('d/m/Y H:i', strtotime($createdAt)) : '—';
                                    $state = $module['creation_state'] ?? 'unidades';
                                    $stateLabels = [
                                        'seleccion' => 'Selecciona el módulo del catálogo',
                                        'unidades' => 'Define las unidades didácticas',
                                        'trimestres' => 'Asigna las unidades a cada trimestre',
                                        'criterios' => 'Selecciona los criterios por unidad',
                                        'pesos' => 'Ajusta los pesos de los criterios',
                                        'resumen' => 'Revisa el resumen de resultados',
                                        'completado' => 'Configuración finalizada',
                                    ];
                                    $stepRoutes = [
                                        'seleccion' => 'unidades',
                                        'unidades' => 'unidades',
                                        'trimestres' => 'trimestres',
                                        'criterios' => 'criterios',
                                        'pesos' => 'pesos',
                                        'resumen' => 'resumen',
                                        'completado' => 'resumen',
                                    ];
                                    $stateLabel = $stateLabels[$state] ?? 'En preparación';
                                    $isCompleted = $state === 'completado';
                                    $nextStep = $stepRoutes[$state] ?? 'unidades';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($module['module_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($formattedDate) ?></td>
                                        <td>
                                            <span class="badge rounded-pill <?= $isCompleted ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= $isCompleted ? 'Completado' : 'No terminado' ?>
                                            </span>
                                            <?php if (!$isCompleted): ?>
                                                <div class="text-muted small mt-1">Paso actual: <?= htmlspecialchars($stateLabel) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group" aria-label="Acciones del módulo">
                                                <a
                                                    class="btn btn-outline-primary btn-sm"
                                                    href="/modulos/configurar?id=<?= (int) ($module['id'] ?? 0) ?>&paso=<?= htmlspecialchars($nextStep) ?>"
                                                >
                                                    <?= $state === 'completado' ? 'Ver resumen' : 'Continuar' ?>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-end mb-3">
                    <a href="/modulos/nuevo" class="btn btn-primary">Añadir módulo</a>
                </div>

                <div id="nuevo-modulo" class="card<?php if (!$showModuleWizard): ?> d-none<?php endif; ?>">
                    <div class="card-header">Seleccionar módulo del catálogo</div>
                    <div class="card-body">
                        <?php if (empty($catalogModules)): ?>
                            <div class="alert alert-warning mb-0">
                                Todavía no hay módulos disponibles en el catálogo. Solicita a un administrador que los cree desde el panel de administración.
                            </div>
                        <?php else: ?>
                            <form method="post" action="/modulos/nuevo" class="row g-3">
                                <div class="col-12">
                                    <label for="module_code" class="form-label">Módulo</label>
                                    <select
                                        class="form-select<?php if (!empty($wizardErrors['module_code'] ?? null)): ?> is-invalid<?php endif; ?>"
                                        id="module_code"
                                        name="module_code"
                                        required
                                    >
                                        <option value="">Selecciona un módulo...</option>
                                        <?php foreach ($catalogModules as $module): ?>
                                            <?php
                                            $value = htmlspecialchars($module['codigo']);
                                            $label = sprintf('%s — %s (Curso %s)', $module['codigo'], $module['nombre'], $module['curso']);
                                            $isSelected = ($wizardOld['module_code'] ?? '') === $module['codigo'];
                                            ?>
                                            <option value="<?= $value ?>"<?php if ($isSelected): ?> selected<?php endif; ?>><?= htmlspecialchars($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($wizardErrors['module_code'] ?? null)): ?>
                                        <div class="invalid-feedback"><?= htmlspecialchars($wizardErrors['module_code']) ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <a href="/" class="btn btn-outline-secondary">Cancelar</a>
                                    <button type="submit" class="btn btn-primary">Comenzar configuración</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade<?php if ($activeTab === 'evaluations'): ?> show active<?php endif; ?>" id="tab-evaluations" role="tabpanel" aria-labelledby="tab-button-evaluations">
                <h2 class="h5 mb-3">Evaluaciones</h2>

                <?php if (empty($evaluations)): ?>
                    <div class="alert alert-info">Aún no hay evaluaciones registradas para tus módulos.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Evaluación</th>
                                    <th scope="col">Módulo</th>
                                    <th scope="col" class="text-nowrap">Año académico</th>
                                    <th scope="col" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($evaluations as $evaluation): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($evaluation['evaluation_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($evaluation['module_name'] ?? 'Sin módulo vinculado') ?></td>
                                        <td><?= htmlspecialchars($evaluation['academic_year'] ?? '25/26') ?></td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group" aria-label="Acciones de la evaluación">
                                                <button type="button" class="btn btn-outline-secondary btn-sm">Editar</button>
                                                <button type="button" class="btn btn-outline-danger btn-sm">Borrar</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-primary">Añadir evaluación</button>
                </div>
            </div>
        </div>
    </div>
</div>
