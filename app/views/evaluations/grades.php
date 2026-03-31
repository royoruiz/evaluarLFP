<?php
$evaluation = $evaluation ?? [];
$students = $students ?? [];
$units = $units ?? [];
$instrumentsByUnit = $instruments_by_unit ?? [];
$grades = $grades ?? [];
$errors = $errors ?? [];
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1">Notas de evaluación</h1>
        <p class="text-muted mb-0">
            <?= htmlspecialchars($evaluation['evaluation_name'] ?? 'Evaluación') ?> ·
            <?= htmlspecialchars($evaluation['module_name'] ?? 'Módulo') ?> ·
            Grupo <?= htmlspecialchars($evaluation['class_group'] ?? '-') ?>
        </p>
    </div>
    <a href="/evaluaciones/editar?id=<?= (int) ($evaluation['id'] ?? 0) ?>" class="btn btn-outline-secondary">Volver a evaluación</a>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars((string) $errors['general']) ?></div>
<?php endif; ?>
<?php if (!empty($errors['grades'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars((string) $errors['grades']) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">Carga masiva (CSV desde Excel)</div>
    <div class="card-body">
        <p class="text-muted">Descarga la plantilla, rellénala en Excel y súbela en formato CSV UTF-8.</p>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="/evaluaciones/notas/plantilla?id=<?= (int) ($evaluation['id'] ?? 0) ?>" class="btn btn-outline-primary">Descargar plantilla CSV</a>
        </div>
        <form method="POST" action="/evaluaciones/notas/importar" enctype="multipart/form-data" class="row g-2 align-items-end">
            <input type="hidden" name="evaluation_id" value="<?= (int) ($evaluation['id'] ?? 0) ?>">
            <div class="col-md-8">
                <label for="grades-csv" class="form-label">Archivo CSV</label>
                <input type="file" id="grades-csv" name="grades_csv" class="form-control" accept=".csv,text/csv" required>
            </div>
            <div class="col-md-4 d-grid">
                <button type="submit" class="btn btn-primary">Importar notas</button>
            </div>
        </form>
    </div>
</div>

<?php
$totalInstruments = 0;
foreach ($units as $unit) {
    $totalInstruments += count($instrumentsByUnit[(int) ($unit['evaluation_unit_id'] ?? 0)] ?? []);
}
?>

<?php if (empty($students) || $totalInstruments === 0): ?>
    <div class="alert alert-info">
        Para registrar notas necesitas alumnos en el grupo y al menos un instrumento en esta evaluación.
    </div>
<?php else: ?>
    <form method="POST" action="/evaluaciones/notas/guardar">
        <input type="hidden" name="evaluation_id" value="<?= (int) ($evaluation['id'] ?? 0) ?>">
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2">Alumno</th>
                        <th rowspan="2">NIA</th>
                        <?php foreach ($units as $unit): ?>
                            <?php
                            $unitId = (int) ($unit['evaluation_unit_id'] ?? 0);
                            $unitInstruments = $instrumentsByUnit[$unitId] ?? [];
                            if (empty($unitInstruments)) {
                                continue;
                            }
                            ?>
                            <th colspan="<?= count($unitInstruments) ?>" class="text-center">
                                Unidad <?= htmlspecialchars((string) ($unit['unit_number'] ?? '')) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($units as $unit): ?>
                            <?php foreach (($instrumentsByUnit[(int) ($unit['evaluation_unit_id'] ?? 0)] ?? []) as $instrument): ?>
                                <th class="text-nowrap"><?= htmlspecialchars((string) ($instrument['name'] ?? 'Instrumento')) ?></th>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <?php $studentId = (int) ($student['id'] ?? 0); ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($student['student_name'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string) ($student['nia'] ?? '')) ?></td>
                            <?php foreach ($units as $unit): ?>
                                <?php foreach (($instrumentsByUnit[(int) ($unit['evaluation_unit_id'] ?? 0)] ?? []) as $instrument): ?>
                                    <?php
                                    $instrumentId = (int) ($instrument['id'] ?? 0);
                                    $gradeKey = $studentId . ':' . $instrumentId;
                                    $value = $grades[$gradeKey] ?? '';
                                    ?>
                                    <td>
                                        <input
                                            type="text"
                                            name="grade[<?= $studentId ?>][<?= $instrumentId ?>]"
                                            class="form-control form-control-sm"
                                            value="<?= htmlspecialchars((string) $value) ?>"
                                            placeholder="Ej. 7.5"
                                        >
                                    </td>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-success">Guardar notas</button>
        </div>
    </form>
<?php endif; ?>
