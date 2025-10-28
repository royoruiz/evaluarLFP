<?php
$cycles = $cycles ?? [];
$modules = $modules ?? [];
$learningOutcomes = $learningOutcomes ?? [];
$evaluationCriteria = $evaluationCriteria ?? [];
$users = $users ?? [];
$errors = $errors ?? [];
$old = $old ?? [];

$cycleOld = $old['cycle'] ?? ['codigo' => '', 'nombre' => '', 'familia' => ''];
$moduleOld = $old['module'] ?? ['codigo' => '', 'nombre' => '', 'codigoCiclo' => '', 'curso' => ''];
$learningOutcomeOld = $old['learning_outcome'] ?? [
    'codigo' => '',
    'numero' => '',
    'descripcion' => '',
    'codigoModulo' => '',
    'codigoCiclo' => '',
];
$evaluationCriterionOld = $old['evaluation_criterion'] ?? [
    'codigo' => '',
    'letra' => '',
    'descripcion' => '',
    'codigoResultado' => '',
];
?>

<h1 class="h3 mb-3">Panel de administración</h1>
<p class="text-muted">Gestiona los catálogos de ciclos formativos, módulos, resultados de aprendizaje y criterios de evaluación.</p>

<div class="row g-4 mt-2">
    <div class="col-lg-3">
        <div class="card border-0 bg-light h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Secciones</h2>
                <div class="nav nav-pills flex-column gap-2" id="admin-tabs" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active" id="tab-button-cycles" data-bs-toggle="tab" data-bs-target="#tab-cycles" type="button" role="tab" aria-controls="tab-cycles" aria-selected="true">Ciclos formativos</button>
                    <button class="nav-link" id="tab-button-modules" data-bs-toggle="tab" data-bs-target="#tab-modules" type="button" role="tab" aria-controls="tab-modules" aria-selected="false">Módulos del ciclo</button>
                    <button class="nav-link" id="tab-button-learning-outcomes" data-bs-toggle="tab" data-bs-target="#tab-learning-outcomes" type="button" role="tab" aria-controls="tab-learning-outcomes" aria-selected="false">Resultados de aprendizaje</button>
                    <button class="nav-link" id="tab-button-evaluation-criteria" data-bs-toggle="tab" data-bs-target="#tab-evaluation-criteria" type="button" role="tab" aria-controls="tab-evaluation-criteria" aria-selected="false">Criterios de evaluación</button>
                    <button class="nav-link" id="tab-button-users" data-bs-toggle="tab" data-bs-target="#tab-users" type="button" role="tab" aria-controls="tab-users" aria-selected="false">Usuarios</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="tab-content" id="admin-tabs-content">
            <div class="tab-pane fade show active" id="tab-cycles" role="tabpanel" aria-labelledby="tab-button-cycles">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Ciclos formativos</h2>
                    <small class="text-muted">Si el código existe se actualizará la información.</small>
                </div>

                <?php if (!empty($errors['cycle'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['cycle']) ?></div>
                <?php endif; ?>

                <form class="row g-3 mb-4" method="POST" action="/admin/ciclos">
                    <div class="col-md-3">
                        <label for="cycle-codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="cycle-codigo" name="codigo" value="<?= htmlspecialchars($cycleOld['codigo'] ?? '') ?>" maxlength="10" required>
                    </div>
                    <div class="col-md-4">
                        <label for="cycle-nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="cycle-nombre" name="nombre" value="<?= htmlspecialchars($cycleOld['nombre'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="cycle-familia" class="form-label">Familia profesional</label>
                        <input type="text" class="form-control" id="cycle-familia" name="familia" value="<?= htmlspecialchars($cycleOld['familia'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Guardar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Familia</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cycles)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Aún no hay ciclos registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cycles as $cycle): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cycle['codigo']) ?></td>
                                        <td><?= htmlspecialchars($cycle['nombre']) ?></td>
                                        <td><?= htmlspecialchars($cycle['familia']) ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="/admin/ciclos/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar este ciclo formativo?');">
                                                <input type="hidden" name="codigo" value="<?= htmlspecialchars($cycle['codigo']) ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-modules" role="tabpanel" aria-labelledby="tab-button-modules">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Módulos de ciclo</h2>
                    <small class="text-muted">El código debe tener 5 caracteres. Se actualizará si ya existe.</small>
                </div>

                <?php if (!empty($errors['module'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['module']) ?></div>
                <?php endif; ?>

                <form class="row g-3 mb-4" method="POST" action="/admin/modulos">
                    <div class="col-md-2">
                        <label for="module-codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="module-codigo" name="codigo" value="<?= htmlspecialchars($moduleOld['codigo'] ?? '') ?>" maxlength="5" required>
                    </div>
                    <div class="col-md-4">
                        <label for="module-nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="module-nombre" name="nombre" value="<?= htmlspecialchars($moduleOld['nombre'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="module-ciclo" class="form-label">Ciclo formativo</label>
                        <select class="form-select" id="module-ciclo" name="codigo_ciclo" required>
                            <option value="">Selecciona un ciclo</option>
                            <?php foreach ($cycles as $cycle): ?>
                                <option value="<?= htmlspecialchars($cycle['codigo']) ?>" <?= ($moduleOld['codigoCiclo'] ?? '') === $cycle['codigo'] ? 'selected' : '' ?>><?= htmlspecialchars($cycle['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="module-curso" class="form-label">Curso</label>
                        <input type="number" min="1" max="6" class="form-control" id="module-curso" name="curso" value="<?= htmlspecialchars((string)($moduleOld['curso'] ?? '')) ?>" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Guardar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Ciclo</th>
                                <th scope="col">Curso</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($modules)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Aún no hay módulos registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($modules as $module): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($module['codigo']) ?></td>
                                        <td><?= htmlspecialchars($module['nombre']) ?></td>
                                        <td><?= htmlspecialchars($module['ciclo_nombre']) ?></td>
                                        <td><?= htmlspecialchars($module['curso']) ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="/admin/modulos/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar este módulo?');">
                                                <input type="hidden" name="codigo" value="<?= htmlspecialchars($module['codigo']) ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-learning-outcomes" role="tabpanel" aria-labelledby="tab-button-learning-outcomes">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Resultados de aprendizaje</h2>
                    <small class="text-muted">Introduce la información oficial de los RA.</small>
                </div>

                <?php if (!empty($errors['learning_outcome'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['learning_outcome']) ?></div>
                <?php endif; ?>

                <form class="row g-3 mb-4" method="POST" action="/admin/resultados">
                    <div class="col-md-2">
                        <label for="ra-codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="ra-codigo" name="codigo" value="<?= htmlspecialchars($learningOutcomeOld['codigo'] ?? '') ?>" maxlength="20" required>
                    </div>
                    <div class="col-md-2">
                        <label for="ra-numero" class="form-label">Número</label>
                        <input type="text" class="form-control" id="ra-numero" name="numero" value="<?= htmlspecialchars($learningOutcomeOld['numero'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label for="ra-descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="ra-descripcion" name="descripcion" rows="2" required><?= htmlspecialchars($learningOutcomeOld['descripcion'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="ra-modulo" class="form-label">Módulo</label>
                        <select class="form-select" id="ra-modulo" name="codigo_modulo" required>
                            <option value="">Selecciona un módulo</option>
                            <?php foreach ($modules as $module): ?>
                                <option value="<?= htmlspecialchars($module['codigo']) ?>" <?= ($learningOutcomeOld['codigoModulo'] ?? '') === $module['codigo'] ? 'selected' : '' ?>><?= htmlspecialchars($module['nombre']) ?> (<?= htmlspecialchars($module['codigo']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="ra-ciclo" class="form-label">Ciclo formativo</label>
                        <select class="form-select" id="ra-ciclo" name="codigo_ciclo" required>
                            <option value="">Selecciona un ciclo</option>
                            <?php foreach ($cycles as $cycle): ?>
                                <option value="<?= htmlspecialchars($cycle['codigo']) ?>" <?= ($learningOutcomeOld['codigoCiclo'] ?? '') === $cycle['codigo'] ? 'selected' : '' ?>><?= htmlspecialchars($cycle['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Guardar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Número</th>
                                <th scope="col">Módulo</th>
                                <th scope="col">Ciclo</th>
                                <th scope="col">Descripción</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($learningOutcomes)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Aún no hay resultados de aprendizaje registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($learningOutcomes as $learningOutcome): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($learningOutcome['codigo']) ?></td>
                                        <td><?= htmlspecialchars($learningOutcome['numero']) ?></td>
                                        <td><?= htmlspecialchars($learningOutcome['modulo_nombre']) ?></td>
                                        <td><?= htmlspecialchars($learningOutcome['ciclo_nombre']) ?></td>
                                        <td><?= nl2br(htmlspecialchars($learningOutcome['descripcion'])) ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="/admin/resultados/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar este resultado de aprendizaje?');">
                                                <input type="hidden" name="codigo" value="<?= htmlspecialchars($learningOutcome['codigo']) ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-evaluation-criteria" role="tabpanel" aria-labelledby="tab-button-evaluation-criteria">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Criterios de evaluación</h2>
                    <small class="text-muted">Utiliza el código y letra oficiales de cada criterio.</small>
                </div>

                <?php if (!empty($errors['evaluation_criterion'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['evaluation_criterion']) ?></div>
                <?php endif; ?>

                <form class="row g-3 mb-4" method="POST" action="/admin/criterios">
                    <div class="col-md-3">
                        <label for="criterion-codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="criterion-codigo" name="codigo" value="<?= htmlspecialchars($evaluationCriterionOld['codigo'] ?? '') ?>" maxlength="20" required>
                    </div>
                    <div class="col-md-2">
                        <label for="criterion-letra" class="form-label">Letra</label>
                        <input type="text" class="form-control" id="criterion-letra" name="letra" value="<?= htmlspecialchars($evaluationCriterionOld['letra'] ?? '') ?>" maxlength="1" required>
                    </div>
                    <div class="col-md-7">
                        <label for="criterion-descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="criterion-descripcion" name="descripcion" rows="2" required><?= htmlspecialchars($evaluationCriterionOld['descripcion'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-9">
                        <label for="criterion-resultado" class="form-label">Resultado de aprendizaje</label>
                        <select class="form-select" id="criterion-resultado" name="codigo_resultado" required>
                            <option value="">Selecciona un resultado</option>
                            <?php foreach ($learningOutcomes as $learningOutcome): ?>
                                <option value="<?= htmlspecialchars($learningOutcome['codigo']) ?>" <?= ($evaluationCriterionOld['codigoResultado'] ?? '') === $learningOutcome['codigo'] ? 'selected' : '' ?>><?= htmlspecialchars($learningOutcome['numero']) ?> - <?= htmlspecialchars($learningOutcome['modulo_nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Guardar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Letra</th>
                                <th scope="col">Resultado</th>
                                <th scope="col">Módulo</th>
                                <th scope="col">Ciclo</th>
                                <th scope="col">Descripción</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($evaluationCriteria)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Aún no hay criterios de evaluación registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($evaluationCriteria as $criterion): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($criterion['codigo']) ?></td>
                                        <td><?= htmlspecialchars($criterion['letra']) ?></td>
                                        <td><?= htmlspecialchars($criterion['resultado_numero']) ?></td>
                                        <td><?= htmlspecialchars($criterion['modulo_nombre']) ?></td>
                                        <td><?= htmlspecialchars($criterion['ciclo_nombre']) ?></td>
                                        <td><?= nl2br(htmlspecialchars($criterion['descripcion'])) ?></td>
                                        <td class="text-end">
                                            <form method="POST" action="/admin/criterios/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar este criterio de evaluación?');">
                                                <input type="hidden" name="codigo" value="<?= htmlspecialchars($criterion['codigo']) ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-users" role="tabpanel" aria-labelledby="tab-button-users">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Usuarios</h2>
                    <small class="text-muted">Promociona o revoca permisos de administración.</small>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Correo</th>
                                <th scope="col">Rol</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No hay usuarios registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $userItem): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($userItem['name']) ?></td>
                                        <td><?= htmlspecialchars($userItem['email']) ?></td>
                                        <td>
                                            <span class="badge <?= $userItem['role'] === 'admin' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= htmlspecialchars($userItem['role'] === 'admin' ? 'Administrador' : 'Usuario') ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <form method="POST" action="/admin/users/role" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($userItem['id']) ?>">
                                                <input type="hidden" name="role" value="<?= $userItem['role'] === 'admin' ? 'user' : 'admin' ?>">
                                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                                    <?= $userItem['role'] === 'admin' ? 'Revocar admin' : 'Hacer admin' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
