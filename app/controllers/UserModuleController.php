<?php

class UserModuleController extends Controller
{
    private const STEPS = ['unidades', 'trimestres', 'criterios', 'pesos', 'resumen'];

    public function create(): void
    {
        $this->ensureAuthenticated();

        $_SESSION['module_wizard_show'] = true;
        $_SESSION['active_tab'] = 'modules';

        $anchor = '#nuevo-modulo';
        $this->redirect('/?tab=modules' . $anchor);
    }

    public function store(): void
    {
        $this->ensureAuthenticated();

        $moduleCode = strtoupper(trim($_POST['module_code'] ?? ''));
        if ($moduleCode === '') {
            $_SESSION['errors']['module_wizard']['module_code'] = 'Debes seleccionar un módulo para continuar.';
            $_SESSION['module_wizard_show'] = true;
            $_SESSION['active_tab'] = 'modules';
            $this->redirect('/?tab=modules#nuevo-modulo');
        }

        $cycleModuleModel = new CycleModuleModel();
        $module = $cycleModuleModel->findByCode($moduleCode);
        if ($module === null) {
            $_SESSION['errors']['module_wizard']['module_code'] = 'El módulo seleccionado no es válido.';
            $_SESSION['old']['module_wizard'] = ['module_code' => $moduleCode];
            $_SESSION['module_wizard_show'] = true;
            $_SESSION['active_tab'] = 'modules';
            $this->redirect('/?tab=modules#nuevo-modulo');
        }

        $userModuleModel = new UserModuleModel();
        $userId = (int) $_SESSION['user_id'];
        $moduleId = $userModuleModel->createFromCatalog($userId, $module);

        $_SESSION['success'] = 'Módulo seleccionado correctamente. Continúa con la configuración.';
        $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=unidades');
    }

    public function configure(): void
    {
        $this->ensureAuthenticated();

        $userId = (int) $_SESSION['user_id'];
        $moduleId = (int) ($_GET['id'] ?? 0);
        $requestedStep = $_GET['paso'] ?? 'unidades';

        if ($moduleId <= 0) {
            $_SESSION['errors']['general'] = 'No se ha podido acceder a la configuración del módulo.';
            $this->redirect('/');
        }

        $userModuleModel = new UserModuleModel();
        $module = $userModuleModel->findForUser($moduleId, $userId);
        if ($module === null) {
            $_SESSION['errors']['general'] = 'El módulo solicitado no existe.';
            $this->redirect('/');
        }

        $step = $this->resolveStep($requestedStep, $module['creation_state'] ?? 'unidades');

        $unitModel = new UserModuleUnitModel();
        $unitCriteriaModel = new UserModuleUnitCriteriaModel();
        $criteriaModel = new EvaluationCriteriaModel();

        $units = $unitModel->getByModule($moduleId);
        $selectedCriteria = $unitCriteriaModel->getByModule($moduleId);

        $selectedCodesByUnit = [];
        $criteriaDetailsByUnit = [];
        foreach ($selectedCriteria as $criteria) {
            $unitId = (int) $criteria['user_module_unit_id'];
            $selectedCodesByUnit[$unitId][] = $criteria['criteria_code'];
            $criteriaDetailsByUnit[$unitId][$criteria['criteria_code']] = $criteria;
        }

        if (!empty($criteriaDetailsByUnit)) {
            $this->ensureCriteriaWeights($criteriaDetailsByUnit, $unitCriteriaModel);
        }

        $availableCriteria = [];
        if (!empty($module['module_code'])) {
            $availableCriteria = $this->groupCriteriaByOutcome(
                $criteriaModel->getByModule($module['module_code'])
            );
        }

        $unitRaWeights = $unitCriteriaModel->getUnitRaWeights($moduleId);
        $summaryByUnit = $this->organizeRaWeights($unitRaWeights);
        $summaryTotals = $this->calculateRaTotals($summaryByUnit);
        $weightsMatrix = $this->buildWeightsMatrix($criteriaDetailsByUnit);

        $errors = $_SESSION['errors']['module_wizard'] ?? [];
        $old = $_SESSION['old']['module_wizard'] ?? [];
        unset($_SESSION['errors']['module_wizard'], $_SESSION['old']['module_wizard']);

        $this->render('modules/configure', [
            'title' => 'Configurar módulo',
            'module' => $module,
            'step' => $step,
            'steps' => self::STEPS,
            'units' => $units,
            'availableCriteria' => $availableCriteria,
            'selectedCodesByUnit' => $selectedCodesByUnit,
            'criteriaDetailsByUnit' => $criteriaDetailsByUnit,
            'summaryByUnit' => $summaryByUnit,
            'summaryTotals' => $summaryTotals,
            'weightsMatrix' => $weightsMatrix,
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function saveStep(): void
    {
        $this->ensureAuthenticated();

        $userId = (int) $_SESSION['user_id'];
        $moduleId = (int) ($_POST['module_id'] ?? 0);
        $step = $_POST['step'] ?? '';

        if ($moduleId <= 0) {
            $_SESSION['errors']['general'] = 'No se pudo completar la acción solicitada.';
            $this->redirect('/');
        }

        $userModuleModel = new UserModuleModel();
        $module = $userModuleModel->findForUser($moduleId, $userId);
        if ($module === null) {
            $_SESSION['errors']['general'] = 'No tienes acceso a este módulo.';
            $this->redirect('/');
        }

        $unitModel = new UserModuleUnitModel();
        $criteriaModel = new UserModuleUnitCriteriaModel();
        $currentState = $module['creation_state'] ?? 'unidades';

        switch ($step) {
            case 'unidades':
                $unitsCount = (int) ($_POST['units_count'] ?? 0);
                if ($unitsCount < 1 || $unitsCount > 20) {
                    $_SESSION['errors']['module_wizard']['units_count'] = 'El número de unidades debe estar entre 1 y 20.';
                    $_SESSION['old']['module_wizard']['units_count'] = $unitsCount;
                    $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=unidades');
                }

                $previousCount = (int) ($module['units_count'] ?? 0);
                $previousState = $currentState;
                $userModuleModel->updateUnitsCount($moduleId, $unitsCount);
                if ($previousCount !== $unitsCount) {
                    $unitModel->replaceUnits($moduleId, $unitsCount);
                    $userModuleModel->updateCreationState($moduleId, 'trimestres');
                    $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=trimestres');
                }

                $stepRoutes = [
                    'seleccion' => 'unidades',
                    'unidades' => 'unidades',
                    'trimestres' => 'trimestres',
                    'criterios' => 'criterios',
                    'pesos' => 'pesos',
                    'resumen' => 'resumen',
                    'completado' => 'resumen',
                ];
                $redirectStep = $stepRoutes[$previousState] ?? 'trimestres';
                $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=' . $redirectStep);
                break;

            case 'trimestres':
                $selection = $_POST['trimesters'] ?? [];
                if (!is_array($selection)) {
                    $selection = [];
                }

                $unitModel->saveTrimesters($moduleId, $selection);

                if ($this->shouldAdvanceState($currentState, 'criterios')) {
                    $userModuleModel->updateCreationState($moduleId, 'criterios');
                    $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=criterios');
                }

                $redirectStep = $currentState === 'completado' ? 'trimestres' : 'criterios';
                $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=' . $redirectStep);
                break;

            case 'criterios':
                $criteriaSelection = $_POST['criteria'] ?? [];
                if (!is_array($criteriaSelection)) {
                    $criteriaSelection = [];
                }

                $units = $unitModel->getByModule($moduleId);
                foreach ($units as $unit) {
                    $unitId = (int) $unit['id'];
                    $selected = $criteriaSelection[$unitId] ?? [];
                    if (!is_array($selected)) {
                        $selected = [];
                    }
                    $filtered = array_values(array_unique(array_filter(
                        array_map(static fn($value): string => trim((string) $value), $selected),
                        static fn(string $value): bool => $value !== ''
                    )));

                    $criteriaModel->setCriteriaForUnit($unitId, $filtered);
                }

                if ($this->shouldAdvanceState($currentState, 'pesos')) {
                    $userModuleModel->updateCreationState($moduleId, 'pesos');
                    $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=pesos');
                }

                $redirectStep = $currentState === 'completado' ? 'criterios' : 'pesos';
                $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=' . $redirectStep);
                break;

            case 'pesos':
                $weightsInput = $_POST['weights'] ?? [];
                if (!is_array($weightsInput)) {
                    $weightsInput = [];
                }

                $units = $unitModel->getByModule($moduleId);
                $hasError = false;
                $normalizedByUnit = [];
                foreach ($units as $unit) {
                    $unitId = (int) $unit['id'];
                    $unitWeights = $weightsInput[$unitId] ?? [];
                    if (!is_array($unitWeights) || empty($unitWeights)) {
                        continue;
                    }

                    $normalizedWeights = [];
                    $sum = 0.0;
                    foreach ($unitWeights as $criteriaCode => $weightValue) {
                        if (!is_numeric($weightValue)) {
                            $hasError = true;
                            break;
                        }

                        $weight = round((float) $weightValue, 2);
                        if ($weight < 0 || $weight > 1) {
                            $hasError = true;
                            break;
                        }

                        $normalizedWeights[$criteriaCode] = $weight;
                        $sum += $weight;
                    }

                    if ($hasError) {
                        break;
                    }

                    if (!empty($normalizedWeights) && abs($sum - 1.0) > 0.01) {
                        $hasError = true;
                        break;
                    }

                    $normalizedByUnit[$unitId] = $normalizedWeights;
                }

                if ($hasError) {
                    $_SESSION['errors']['module_wizard']['weights'] = 'Los pesos deben ser numéricos, estar entre 0 y 1 y sumar 1 en cada unidad.';
                    $_SESSION['old']['module_wizard']['weights'] = $weightsInput;
                    $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=pesos');
                }

                foreach ($normalizedByUnit as $unitId => $normalizedWeights) {
                    $criteriaModel->updateWeightsForUnit((int) $unitId, $normalizedWeights);
                }

                if ($this->shouldAdvanceState($currentState, 'resumen')) {
                    $userModuleModel->updateCreationState($moduleId, 'resumen');
                    $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=resumen');
                }

                $redirectStep = $currentState === 'completado' ? 'pesos' : 'resumen';
                $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=' . $redirectStep);
                break;

            case 'finalizar':
                $userModuleModel->markCompleted($moduleId);
                $_SESSION['success'] = 'Configuración del módulo completada correctamente.';
                $this->redirect('/modulos/configurar?id=' . $moduleId . '&paso=resumen');
                break;

            default:
                $_SESSION['errors']['general'] = 'La acción solicitada no es válida.';
                $this->redirect('/');
        }
    }

    private function ensureAuthenticated(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    private function resolveStep(string $requested, string $currentState): string
    {
        if (!in_array($requested, self::STEPS, true)) {
            $requested = self::STEPS[0];
        }

        $requestedIndex = array_search($requested, self::STEPS, true);
        $currentIndex = array_search($currentState, self::STEPS, true);

        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        if ($currentState === 'completado') {
            return $requested;
        }

        if ($requestedIndex === false || $requestedIndex > $currentIndex) {
            return self::STEPS[$currentIndex];
        }

        return $requested;
    }

    private function shouldAdvanceState(string $currentState, string $targetState): bool
    {
        if ($currentState === 'completado') {
            return false;
        }

        $currentIndex = array_search($currentState, self::STEPS, true);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        $targetIndex = array_search($targetState, self::STEPS, true);
        if ($targetIndex === false) {
            return false;
        }

        return $targetIndex > $currentIndex;
    }

    /**
     * @param array<int, array<string, mixed>> $criteria
     * @return array<string, array<string, mixed>>
     */
    private function groupCriteriaByOutcome(array $criteria): array
    {
        $grouped = [];
        foreach ($criteria as $criterion) {
            $outcomeCode = $criterion['codigo_resultado'];
            if (!isset($grouped[$outcomeCode])) {
                $grouped[$outcomeCode] = [
                    'codigo' => $outcomeCode,
                    'numero' => $criterion['resultado_numero'] ?? '',
                    'descripcion' => $criterion['resultado_descripcion'] ?? '',
                    'criteria' => [],
                ];
            }

            $grouped[$outcomeCode]['criteria'][] = $criterion;
        }

        return $grouped;
    }

    /**
     * @param array<int, array<string, mixed>> $weights
     * @return array<int, array<string, array<string, mixed>>>
     */
    private function organizeRaWeights(array $weights): array
    {
        $organized = [];
        foreach ($weights as $row) {
            $unitId = (int) $row['unit_id'];
            if (!isset($organized[$unitId])) {
                $organized[$unitId] = [];
            }

            $organized[$unitId][$row['ra_codigo']] = [
                'numero' => $row['ra_numero'],
                'descripcion' => $row['ra_descripcion'],
                'weight' => $this->normalizeWeightValue((float) $row['total_weight']),
            ];
        }

        return $organized;
    }

    /**
     * @param array<int, array<string, array<string, mixed>>> $summaryByUnit
     * @return array<string, array<string, mixed>>
     */
    private function calculateRaTotals(array $summaryByUnit): array
    {
        $totals = [];
        foreach ($summaryByUnit as $unitRaWeights) {
            foreach ($unitRaWeights as $raCode => $info) {
                if (!isset($totals[$raCode])) {
                    $totals[$raCode] = [
                        'numero' => $info['numero'],
                        'descripcion' => $info['descripcion'],
                        'weight' => 0.0,
                    ];
                }

                $totals[$raCode]['weight'] += $info['weight'];
            }
        }

        foreach ($totals as &$total) {
            $total['weight'] = round((float) $total['weight'], 2);
        }

        return $totals;
    }

    /**
     * @param array<int, array<string, array<string, mixed>>> &$criteriaDetailsByUnit
     */
    private function ensureCriteriaWeights(array &$criteriaDetailsByUnit, UserModuleUnitCriteriaModel $criteriaModel): void
    {
        $pendingUpdates = [];

        foreach ($criteriaDetailsByUnit as $unitId => &$criteriaList) {
            $needsDefaultWeights = false;
            $weightsByCode = [];

            foreach ($criteriaList as $code => &$details) {
                if (!isset($details['weight']) || !is_numeric($details['weight'])) {
                    $needsDefaultWeights = true;
                    continue;
                }

                $normalized = $this->normalizeWeightValue((float) $details['weight']);
                if ($normalized !== (float) $details['weight']) {
                    $details['weight'] = $normalized;
                    $weightsByCode[$code] = $normalized;
                }
            }
            unset($details);

            if ($needsDefaultWeights) {
                $defaultWeights = $this->calculateDefaultWeightsForCount(count($criteriaList));

                $index = 0;
                foreach ($criteriaList as $code => &$details) {
                    $weight = $defaultWeights[$index] ?? 0.0;
                    $details['weight'] = $weight;
                    $weightsByCode[$code] = $weight;
                    $index++;
                }
                unset($details);
            }

            if (!empty($weightsByCode)) {
                $pendingUpdates[(int) $unitId] = $weightsByCode;
            }
        }
        unset($criteriaList);

        foreach ($pendingUpdates as $unitId => $weights) {
            $criteriaModel->updateWeightsForUnit($unitId, $weights);
        }
    }

    /**
     * @return array<int, float>
     */
    private function calculateDefaultWeightsForCount(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $totalHundredths = 100;
        $base = intdiv($totalHundredths, $count);
        $remainder = $totalHundredths - ($base * $count);

        $weights = [];
        for ($index = 0; $index < $count; $index++) {
            $hundredths = $base;
            if ($remainder > 0) {
                $hundredths++;
                $remainder--;
            }

            $weights[] = $hundredths / 100;
        }

        return $weights;
    }

    /**
     * @param array<int, array<string, array<string, mixed>>> $criteriaDetailsByUnit
     * @return array<string, array<string, mixed>>
     */
    private function buildWeightsMatrix(array $criteriaDetailsByUnit): array
    {
        $matrix = [];

        foreach ($criteriaDetailsByUnit as $unitId => $criteriaList) {
            foreach ($criteriaList as $code => $details) {
                $raCode = (string) ($details['codigo_resultado'] ?? '');
                if ($raCode === '') {
                    $raCode = 'ra_' . md5((string) $code);
                }

                if (!isset($matrix[$raCode])) {
                    $matrix[$raCode] = [
                        'codigo' => $raCode,
                        'numero' => $details['resultado_numero'] ?? '',
                        'descripcion' => $details['resultado_descripcion'] ?? '',
                        'criteria' => [],
                    ];
                }

                $matrix[$raCode]['criteria'][$code] = [
                    'code' => $code,
                    'letra' => $details['letra'] ?? '',
                    'descripcion' => $details['descripcion'] ?? '',
                    'unit_id' => (int) $unitId,
                    'weight' => $this->normalizeWeightValue((float) ($details['weight'] ?? 0.0)),
                ];
            }
        }

        uasort($matrix, static function (array $a, array $b): int {
            $numeroA = (string) ($a['numero'] ?? '');
            $numeroB = (string) ($b['numero'] ?? '');

            return strcmp($numeroA, $numeroB);
        });

        foreach ($matrix as &$raData) {
            if (!isset($raData['criteria'])) {
                continue;
            }

            uasort($raData['criteria'], static function (array $a, array $b): int {
                $labelA = (string) ($a['letra'] ?? $a['code'] ?? '');
                $labelB = (string) ($b['letra'] ?? $b['code'] ?? '');

                return strcmp($labelA, $labelB);
            });
        }
        unset($raData);

        return $matrix;
    }

    private function normalizeWeightValue(float $weight): float
    {
        if ($weight > 1.0) {
            return round($weight / 100, 2);
        }

        return round($weight, 2);
    }
}
