<?php

class EvaluationController extends Controller
{
    public function create(): void
    {
        $this->ensureAuthenticated();

        $userId = (int) $_SESSION['user_id'];
        $moduleModel = new UserModuleModel();

        $sessionErrors = $_SESSION['errors'] ?? [];
        $sessionOld = $_SESSION['old'] ?? [];

        $formErrors = $sessionErrors['evaluation_form'] ?? [];
        $generalError = $sessionErrors['general'] ?? null;
        $oldForm = $sessionOld['evaluation_form'] ?? [];

        unset(
            $_SESSION['errors']['evaluation_form'],
            $_SESSION['old']['evaluation_form']
        );

        if ($generalError !== null) {
            unset($_SESSION['errors']['general']);
        }

        $this->render('evaluations/create', [
            'title' => 'Nueva evaluación',
            'modules' => $moduleModel->getByUserId($userId),
            'errors' => [
                'general' => $generalError,
                'evaluation_form' => $formErrors,
            ],
            'old' => [
                'evaluation_form' => $oldForm,
            ],
        ]);
    }

    public function store(): void
    {
        $this->ensureAuthenticated();

        $userId = (int) $_SESSION['user_id'];

        $evaluationName = trim($_POST['evaluation_name'] ?? '');
        $academicYear = trim($_POST['academic_year'] ?? '');
        $classGroup = trim($_POST['class_group'] ?? '');
        $moduleId = (int) ($_POST['module_id'] ?? 0);

        $errors = [];

        if ($evaluationName === '') {
            $errors['evaluation_name'] = 'Debes indicar un nombre para la evaluación.';
        }

        if ($classGroup === '') {
            $errors['class_group'] = 'Debes indicar la clase o grupo al que se aplica la evaluación.';
        }

        if (!$this->isValidAcademicYear($academicYear)) {
            $errors['academic_year'] = 'El año académico debe tener el formato 23/24 o 2023/2024.';
        }

        $moduleModel = new UserModuleModel();
        $module = $moduleModel->findForUser($moduleId, $userId);
        if ($module === null) {
            $errors['module_id'] = 'Debes seleccionar un módulo válido.';
        } elseif ((int) ($module['units_count'] ?? 0) <= 0) {
            $errors['module_id'] = 'El módulo seleccionado no tiene unidades configuradas.';
        }

        if (!empty($errors)) {
            $_SESSION['errors']['evaluation_form'] = $errors;
            $_SESSION['old']['evaluation_form'] = [
                'evaluation_name' => $evaluationName,
                'academic_year' => $academicYear,
                'class_group' => $classGroup,
                'module_id' => $moduleId,
            ];
            $this->redirect('/evaluaciones/nuevo');
        }

        $evaluationModel = new UserModuleEvaluationModel();
        $evaluationUnitModel = new EvaluationUnitModel();
        $instrumentModel = new EvaluationInstrumentModel();
        $instrumentCriteriaModel = new EvaluationInstrumentCriteriaModel();
        $unitCriteriaModel = new UserModuleUnitCriteriaModel();

        $evaluationId = $evaluationModel->createEvaluation(
            $userId,
            $moduleId,
            $evaluationName,
            $academicYear,
            $classGroup
        );

        $evaluationUnitModel->createFromModule($evaluationId, $moduleId);

        $units = $evaluationUnitModel->getByEvaluation($evaluationId);
        foreach ($units as $unit) {
            $criteria = $unitCriteriaModel->getByUnit((int) $unit['module_unit_id']);
            if (empty($criteria)) {
                continue;
            }

            $instrumentId = $instrumentModel->create(
                (int) $unit['evaluation_unit_id'],
                'Instrumento de evaluación 1'
            );

            $instrumentCriteriaModel->setForInstrument(
                $instrumentId,
                array_map(static fn ($criterion) => $criterion['criteria_code'], $criteria)
            );
        }

        $_SESSION['success'] = 'Evaluación creada correctamente. Ajusta los instrumentos según necesites.';
        $this->redirect('/evaluaciones/editar?id=' . $evaluationId);
    }

    public function edit(): void
    {
        $this->ensureAuthenticated();

        $evaluationId = (int) ($_GET['id'] ?? 0);
        if ($evaluationId <= 0) {
            $_SESSION['errors']['general'] = 'No se pudo acceder a la evaluación solicitada.';
            $this->redirect('/');
        }

        $userId = (int) $_SESSION['user_id'];
        $evaluationModel = new UserModuleEvaluationModel();
        $evaluation = $evaluationModel->findForUser($evaluationId, $userId);

        if ($evaluation === null) {
            $_SESSION['errors']['general'] = 'La evaluación solicitada no existe o no tienes acceso a ella.';
            $this->redirect('/');
        }

        $evaluationUnitModel = new EvaluationUnitModel();
        $unitCriteriaModel = new UserModuleUnitCriteriaModel();
        $instrumentModel = new EvaluationInstrumentModel();
        $instrumentCriteriaModel = new EvaluationInstrumentCriteriaModel();

        $units = $evaluationUnitModel->getByEvaluation($evaluationId);

        $unitsData = [];
        foreach ($units as $unit) {
            $moduleUnitId = (int) $unit['module_unit_id'];
            $evaluationUnitId = (int) $unit['evaluation_unit_id'];

            $criteria = $unitCriteriaModel->getByUnit($moduleUnitId);
            $allowedCodes = array_map(static fn ($criterion) => $criterion['criteria_code'], $criteria);

            $instruments = $instrumentModel->getByUnit($evaluationUnitId);
            $instrumentCriteriaRows = $instrumentCriteriaModel->getByUnit($evaluationUnitId);
            $criteriaByInstrument = $this->groupInstrumentCriteria($instrumentCriteriaRows);

            $unitInstruments = [];
            $assignedCodes = [];
            foreach ($instruments as $instrument) {
                $instrumentId = (int) $instrument['id'];
                $instrumentCriteria = $criteriaByInstrument[$instrumentId] ?? [];
                foreach ($instrumentCriteria as $criterion) {
                    $assignedCodes[] = $criterion['criteria_code'];
                }

                $unitInstruments[] = [
                    'id' => $instrumentId,
                    'name' => $instrument['name'],
                    'description' => $instrument['description'],
                    'criteria' => $instrumentCriteria,
                ];
            }

            $missingCriteria = [];
            foreach ($criteria as $criterion) {
                if (!in_array($criterion['criteria_code'], $assignedCodes, true)) {
                    $missingCriteria[] = $criterion;
                }
            }

            $unitsData[] = [
                'evaluation_unit_id' => $evaluationUnitId,
                'module_unit_id' => $moduleUnitId,
                'unit_number' => $unit['unit_number'],
                'unit_label' => $unit['unit_label'],
                'criteria' => $criteria,
                'instruments' => $unitInstruments,
                'missing_criteria' => $missingCriteria,
                'allowed_codes' => $allowedCodes,
            ];
        }

        $sessionErrors = $_SESSION['errors'] ?? [];
        $sessionOld = $_SESSION['old'] ?? [];

        $updateErrors = $sessionErrors['evaluation_update'] ?? [];
        $instrumentErrors = $sessionErrors['evaluation_instrument'] ?? [];
        $generalError = $sessionErrors['general'] ?? null;

        $updateOld = $sessionOld['evaluation_update'] ?? [];
        $instrumentOld = $sessionOld['evaluation_instrument'] ?? null;

        unset(
            $_SESSION['errors']['evaluation_update'],
            $_SESSION['errors']['evaluation_instrument'],
            $_SESSION['old']['evaluation_update'],
            $_SESSION['old']['evaluation_instrument']
        );

        if ($generalError !== null) {
            unset($_SESSION['errors']['general']);
        }

        $this->render('evaluations/edit', [
            'title' => 'Editar evaluación',
            'evaluation' => $evaluation,
            'units' => $unitsData,
            'errors' => [
                'general' => $generalError,
                'evaluation_update' => $updateErrors,
                'evaluation_instrument' => $instrumentErrors,
            ],
            'old' => [
                'evaluation_update' => $updateOld,
                'evaluation_instrument' => $instrumentOld,
            ],
        ]);
    }

    public function update(): void
    {
        $this->ensureAuthenticated();

        $evaluationId = (int) ($_POST['evaluation_id'] ?? 0);
        if ($evaluationId <= 0) {
            $_SESSION['errors']['general'] = 'No se pudo guardar la información de la evaluación.';
            $this->redirect('/');
        }

        $userId = (int) $_SESSION['user_id'];
        $evaluationModel = new UserModuleEvaluationModel();
        $evaluation = $evaluationModel->findForUser($evaluationId, $userId);

        if ($evaluation === null) {
            $_SESSION['errors']['general'] = 'La evaluación indicada no existe.';
            $this->redirect('/');
        }

        $name = trim($_POST['evaluation_name'] ?? '');
        $academicYear = trim($_POST['academic_year'] ?? '');
        $classGroup = trim($_POST['class_group'] ?? '');

        $errors = [];

        if ($name === '') {
            $errors['evaluation_name'] = 'El nombre de la evaluación es obligatorio.';
        }

        if ($classGroup === '') {
            $errors['class_group'] = 'Debes indicar la clase o grupo.';
        }

        if (!$this->isValidAcademicYear($academicYear)) {
            $errors['academic_year'] = 'El año académico debe tener el formato 23/24 o 2023/2024.';
        }

        if (!empty($errors)) {
            $_SESSION['errors']['evaluation_update'] = $errors;
            $_SESSION['old']['evaluation_update'] = [
                'evaluation_name' => $name,
                'academic_year' => $academicYear,
                'class_group' => $classGroup,
            ];
            $this->redirect('/evaluaciones/editar?id=' . $evaluationId);
        }

        $evaluationModel->updateEvaluation($evaluationId, $name, $academicYear, $classGroup);
        $_SESSION['success'] = 'Datos de la evaluación actualizados correctamente.';
        $this->redirect('/evaluaciones/editar?id=' . $evaluationId);
    }

    public function storeInstrument(): void
    {
        $this->ensureAuthenticated();

        $evaluationId = (int) ($_POST['evaluation_id'] ?? 0);
        $evaluationUnitId = (int) ($_POST['evaluation_unit_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $criteriaCodes = $this->sanitizeCriteria($_POST['criteria'] ?? []);

        if ($evaluationId <= 0 || $evaluationUnitId <= 0) {
            $_SESSION['errors']['general'] = 'No se pudo completar la operación solicitada.';
            $this->redirect('/');
        }

        $userId = (int) $_SESSION['user_id'];
        $evaluationModel = new UserModuleEvaluationModel();
        $evaluation = $evaluationModel->findForUser($evaluationId, $userId);
        if ($evaluation === null) {
            $_SESSION['errors']['general'] = 'No tienes acceso a esta evaluación.';
            $this->redirect('/');
        }

        $unitModel = new EvaluationUnitModel();
        $unit = $unitModel->findForEvaluation($evaluationUnitId, $evaluationId);
        if ($unit === null) {
            $_SESSION['errors']['general'] = 'La unidad seleccionada no es válida.';
            $this->redirect('/');
        }

        $unitCriteriaModel = new UserModuleUnitCriteriaModel();
        $instrumentModel = new EvaluationInstrumentModel();
        $instrumentCriteriaModel = new EvaluationInstrumentCriteriaModel();

        $criteriaList = $unitCriteriaModel->getByUnit((int) $unit['module_unit_id']);
        $allowedCodes = array_map(static fn ($criterion) => $criterion['criteria_code'], $criteriaList);

        $errors = [];

        if ($name === '') {
            $errors[] = 'Debes indicar un nombre para el instrumento.';
        }

        if (!empty($allowedCodes) && empty($criteriaCodes)) {
            $errors[] = 'Selecciona al menos un criterio de evaluación.';
        }

        foreach ($criteriaCodes as $code) {
            if (!in_array($code, $allowedCodes, true)) {
                $errors[] = 'Se han seleccionado criterios que no pertenecen a la unidad.';
                break;
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors']['evaluation_instrument'][$evaluationUnitId] = implode(' ', $errors);
            $_SESSION['old']['evaluation_instrument'] = [
                'unit_id' => $evaluationUnitId,
                'name' => $name,
                'description' => $description,
                'criteria' => $criteriaCodes,
            ];
            $this->redirect('/evaluaciones/editar?id=' . $evaluationId . '#unidad-' . $evaluationUnitId);
        }

        $instrumentId = $instrumentModel->create($evaluationUnitId, $name, $description !== '' ? $description : null);
        $instrumentCriteriaModel->setForInstrument($instrumentId, $criteriaCodes);

        $_SESSION['success'] = 'Instrumento añadido correctamente.';
        $this->redirect('/evaluaciones/editar?id=' . $evaluationId . '#unidad-' . $evaluationUnitId);
    }

    public function updateInstrument(): void
    {
        $this->ensureAuthenticated();

        $evaluationId = (int) ($_POST['evaluation_id'] ?? 0);
        $instrumentId = (int) ($_POST['instrument_id'] ?? 0);
        $evaluationUnitId = (int) ($_POST['evaluation_unit_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $criteriaCodes = $this->sanitizeCriteria($_POST['criteria'] ?? []);

        if ($evaluationId <= 0 || $instrumentId <= 0 || $evaluationUnitId <= 0) {
            $_SESSION['errors']['general'] = 'No se pudo completar la operación solicitada.';
            $this->redirect('/');
        }

        $userId = (int) $_SESSION['user_id'];
        $evaluationModel = new UserModuleEvaluationModel();
        $evaluation = $evaluationModel->findForUser($evaluationId, $userId);
        if ($evaluation === null) {
            $_SESSION['errors']['general'] = 'No tienes acceso a esta evaluación.';
            $this->redirect('/');
        }

        $instrumentModel = new EvaluationInstrumentModel();
        $instrument = $instrumentModel->findForEvaluation($instrumentId, $evaluationId);
        if ($instrument === null || (int) $instrument['evaluation_unit_id'] !== $evaluationUnitId) {
            $_SESSION['errors']['general'] = 'El instrumento indicado no es válido.';
            $this->redirect('/');
        }

        $unitModel = new EvaluationUnitModel();
        $unit = $unitModel->findForEvaluation($evaluationUnitId, $evaluationId);
        if ($unit === null) {
            $_SESSION['errors']['general'] = 'La unidad indicada no es válida.';
            $this->redirect('/');
        }

        $unitCriteriaModel = new UserModuleUnitCriteriaModel();
        $instrumentCriteriaModel = new EvaluationInstrumentCriteriaModel();

        $criteriaList = $unitCriteriaModel->getByUnit((int) $unit['module_unit_id']);
        $allowedCodes = array_map(static fn ($criterion) => $criterion['criteria_code'], $criteriaList);

        $errors = [];

        if ($name === '') {
            $errors[] = 'El nombre del instrumento es obligatorio.';
        }

        foreach ($criteriaCodes as $code) {
            if (!in_array($code, $allowedCodes, true)) {
                $errors[] = 'Se han seleccionado criterios que no pertenecen a la unidad.';
                break;
            }
        }

        $assignments = $this->groupAssignmentsByInstrument(
            $instrumentCriteriaModel->getByUnit($evaluationUnitId)
        );
        $assignments[$instrumentId] = $criteriaCodes;
        $missing = $this->calculateMissingCriteria($allowedCodes, $assignments);
        if (!empty($allowedCodes) && !empty($missing)) {
            $errors[] = 'Debes asegurar que todos los criterios de la unidad estén asignados a algún instrumento.';
        }

        if (!empty($errors)) {
            $_SESSION['errors']['evaluation_instrument'][$evaluationUnitId] = implode(' ', $errors);
            $_SESSION['old']['evaluation_instrument'] = [
                'unit_id' => $evaluationUnitId,
                'instrument_id' => $instrumentId,
                'name' => $name,
                'description' => $description,
                'criteria' => $criteriaCodes,
            ];
            $this->redirect('/evaluaciones/editar?id=' . $evaluationId . '#unidad-' . $evaluationUnitId);
        }

        $instrumentModel->update($instrumentId, $name, $description !== '' ? $description : null);
        $instrumentCriteriaModel->setForInstrument($instrumentId, $criteriaCodes);

        $_SESSION['success'] = 'Instrumento actualizado correctamente.';
        $this->redirect('/evaluaciones/editar?id=' . $evaluationId . '#unidad-' . $evaluationUnitId);
    }

    public function deleteInstrument(): void
    {
        $this->ensureAuthenticated();

        $evaluationId = (int) ($_POST['evaluation_id'] ?? 0);
        $instrumentId = (int) ($_POST['instrument_id'] ?? 0);
        $evaluationUnitId = (int) ($_POST['evaluation_unit_id'] ?? 0);

        if ($evaluationId <= 0 || $instrumentId <= 0 || $evaluationUnitId <= 0) {
            $_SESSION['errors']['general'] = 'No se pudo completar la operación solicitada.';
            $this->redirect('/');
        }

        $userId = (int) $_SESSION['user_id'];
        $evaluationModel = new UserModuleEvaluationModel();
        $evaluation = $evaluationModel->findForUser($evaluationId, $userId);
        if ($evaluation === null) {
            $_SESSION['errors']['general'] = 'No tienes acceso a esta evaluación.';
            $this->redirect('/');
        }

        $instrumentModel = new EvaluationInstrumentModel();
        $instrument = $instrumentModel->findForEvaluation($instrumentId, $evaluationId);
        if ($instrument === null || (int) $instrument['evaluation_unit_id'] !== $evaluationUnitId) {
            $_SESSION['errors']['general'] = 'El instrumento indicado no es válido.';
            $this->redirect('/');
        }

        $unitModel = new EvaluationUnitModel();
        $unit = $unitModel->findForEvaluation($evaluationUnitId, $evaluationId);
        if ($unit === null) {
            $_SESSION['errors']['general'] = 'La unidad indicada no es válida.';
            $this->redirect('/');
        }

        $unitCriteriaModel = new UserModuleUnitCriteriaModel();
        $instrumentCriteriaModel = new EvaluationInstrumentCriteriaModel();

        $criteriaList = $unitCriteriaModel->getByUnit((int) $unit['module_unit_id']);
        $allowedCodes = array_map(static fn ($criterion) => $criterion['criteria_code'], $criteriaList);

        $assignments = $this->groupAssignmentsByInstrument(
            $instrumentCriteriaModel->getByUnit($evaluationUnitId)
        );
        unset($assignments[$instrumentId]);
        $missing = $this->calculateMissingCriteria($allowedCodes, $assignments);
        if (!empty($allowedCodes) && !empty($missing)) {
            $_SESSION['errors']['evaluation_instrument'][$evaluationUnitId] = 'No puedes eliminar el instrumento porque quedarían criterios sin asignar.';
            $this->redirect('/evaluaciones/editar?id=' . $evaluationId . '#unidad-' . $evaluationUnitId);
        }

        $instrumentModel->delete($instrumentId);
        $_SESSION['success'] = 'Instrumento eliminado correctamente.';
        $this->redirect('/evaluaciones/editar?id=' . $evaluationId . '#unidad-' . $evaluationUnitId);
    }

    private function ensureAuthenticated(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    private function isValidAcademicYear(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (preg_match('/^\d{2}\/\d{2}$/', $value) === 1) {
            return true;
        }

        return preg_match('/^\d{4}\/\d{4}$/', $value) === 1;
    }

    /**
     * @param array<int, mixed> $input
     * @return array<int, string>
     */
    private function sanitizeCriteria($input): array
    {
        if (!is_array($input)) {
            return [];
        }

        $criteria = [];
        foreach ($input as $value) {
            if (is_string($value) && $value !== '') {
                $criteria[] = $value;
            }
        }

        return array_values(array_unique($criteria));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function groupInstrumentCriteria(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $instrumentId = (int) $row['evaluation_instrument_id'];
            if (!isset($grouped[$instrumentId])) {
                $grouped[$instrumentId] = [];
            }

            $grouped[$instrumentId][] = $row;
        }

        return $grouped;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<int, string>>
     */
    private function groupAssignmentsByInstrument(array $rows): array
    {
        $assignments = [];
        foreach ($rows as $row) {
            $instrumentId = (int) $row['evaluation_instrument_id'];
            if (!isset($assignments[$instrumentId])) {
                $assignments[$instrumentId] = [];
            }

            $assignments[$instrumentId][] = $row['criteria_code'];
        }

        return $assignments;
    }

    /**
     * @param array<int, string> $allowedCodes
     * @param array<int, array<int, string>> $assignments
     * @return array<int, string>
     */
    private function calculateMissingCriteria(array $allowedCodes, array $assignments): array
    {
        if (empty($allowedCodes)) {
            return [];
        }

        $covered = [];
        foreach ($assignments as $codes) {
            foreach ($codes as $code) {
                $covered[$code] = true;
            }
        }

        $missing = [];
        foreach ($allowedCodes as $code) {
            if (!isset($covered[$code])) {
                $missing[] = $code;
            }
        }

        return $missing;
    }
}
