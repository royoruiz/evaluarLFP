<?php
$group = $group ?? [];
$students = $students ?? [];
$errors = $errors ?? [];
$old = $old ?? [];
$studentErrors = $errors['group_student_form'] ?? [];
$studentOld = $old['group_student_form'] ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1">Grupo: <?= htmlspecialchars($group['group_name'] ?? '') ?></h1>
        <p class="text-muted mb-0">Mantén el alumnado con NIA y nombre.</p>
    </div>
    <a href="/?tab=groups" class="btn btn-outline-secondary">Volver a grupos</a>
</div>

<div class="card mb-4">
    <div class="card-header">Alta de alumno</div>
    <div class="card-body">
        <form method="POST" action="/grupos/alumnos" class="row g-3 align-items-end">
            <input type="hidden" name="group_id" value="<?= (int) ($group['id'] ?? 0) ?>">

            <div class="col-md-4">
                <label for="nia" class="form-label">NIA</label>
                <input
                    type="text"
                    id="nia"
                    name="nia"
                    class="form-control<?php if (!empty($studentErrors['nia'] ?? null)): ?> is-invalid<?php endif; ?>"
                    value="<?= htmlspecialchars($studentOld['nia'] ?? '') ?>"
                    required
                >
                <?php if (!empty($studentErrors['nia'] ?? null)): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($studentErrors['nia']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="student_name" class="form-label">Nombre</label>
                <input
                    type="text"
                    id="student_name"
                    name="student_name"
                    class="form-control<?php if (!empty($studentErrors['student_name'] ?? null)): ?> is-invalid<?php endif; ?>"
                    value="<?= htmlspecialchars($studentOld['student_name'] ?? '') ?>"
                    required
                >
                <?php if (!empty($studentErrors['student_name'] ?? null)): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($studentErrors['student_name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">Añadir</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($students)): ?>
    <div class="alert alert-info">Este grupo aún no tiene alumnos.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col">NIA</th>
                    <th scope="col">Nombre</th>
                    <th scope="col" class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['nia'] ?? '') ?></td>
                        <td><?= htmlspecialchars($student['student_name'] ?? '') ?></td>
                        <td class="text-end">
                            <form method="POST" action="/grupos/alumnos/eliminar" class="d-inline">
                                <input type="hidden" name="group_id" value="<?= (int) ($group['id'] ?? 0) ?>">
                                <input type="hidden" name="student_id" value="<?= (int) ($student['id'] ?? 0) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar este alumno del grupo?');">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
