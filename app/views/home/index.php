<?php
$modules = $modules ?? [];
$evaluations = $evaluations ?? [];
?>

<h1 class="h3 mb-3">Bienvenido, <?= htmlspecialchars($user['name']) ?></h1>
<p class="text-muted">Gestiona tus módulos y evaluaciones desde este panel.</p>

<div class="row g-4 align-items-start mt-2">
    <div class="col-lg-9 order-2 order-lg-1">
        <div class="tab-content" id="home-tabs-content">
            <div class="tab-pane fade show active" id="tab-modules" role="tabpanel" aria-labelledby="tab-button-modules">
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
                                    <th scope="col" class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modules as $module): ?>
                                    <?php
                                    $createdAt = $module['created_at'] ?? null;
                                    $formattedDate = $createdAt ? date('d/m/Y H:i', strtotime($createdAt)) : '—';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($module['module_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($formattedDate) ?></td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group" aria-label="Acciones del módulo">
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
                    <button type="button" class="btn btn-primary">Añadir módulo</button>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-evaluations" role="tabpanel" aria-labelledby="tab-button-evaluations">
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

    <div class="col-lg-3 order-1 order-lg-2">
        <div class="card border-0 bg-light">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Menú</h2>
                <div class="nav nav-pills flex-lg-column gap-2" id="home-tabs" role="tablist" aria-orientation="vertical">
                    <button
                        class="nav-link active"
                        id="tab-button-modules"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-modules"
                        type="button"
                        role="tab"
                        aria-controls="tab-modules"
                        aria-selected="true"
                    >
                        Módulos
                    </button>
                    <button
                        class="nav-link"
                        id="tab-button-evaluations"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-evaluations"
                        type="button"
                        role="tab"
                        aria-controls="tab-evaluations"
                        aria-selected="false"
                    >
                        Evaluaciones
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
