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

                $criterionUnitInput = $_POST['criterion_unit_weights'] ?? [];
                if (!is_array($criterionUnitInput)) {
                    $criterionUnitInput = [];
                }

                $selectedCriteria = $criteriaModel->getByModule($moduleId);
                $criteriaByOutcome = [];
                foreach ($selectedCriteria as $criterion) {
                    $outcomeCode = (string) ($criterion['codigo_resultado'] ?? '');
                    if ($outcomeCode === '') {
                        continue;
                    }

                    if (!isset($criteriaByOutcome[$outcomeCode])) {
                        $criteriaByOutcome[$outcomeCode] = [
                            'criteria' => [],
                        ];
                    }

                    $criteriaCode = (string) $criterion['criteria_code'];
                    if ($criteriaCode === '') {
                        continue;
                    }

                    if (!isset($criteriaByOutcome[$outcomeCode]['criteria'][$criteriaCode])) {
                        $criteriaByOutcome[$outcomeCode]['criteria'][$criteriaCode] = [
                            'units' => [],
                        ];
                    }

                    $criteriaByOutcome[$outcomeCode]['criteria'][$criteriaCode]['units'][(int) $criterion['user_module_unit_id']] = true;
                }

                $hasError = false;
                $errorMessage = '';
                $normalizedByUnit = [];

                foreach ($criteriaByOutcome as $raCode => $data) {
                    $criteriaForOutcome = $data['criteria'];
                    if (empty($criteriaForOutcome)) {
                        continue;
                    }

                    $inputsForOutcome = $weightsInput[$raCode] ?? [];
                    if (!is_array($inputsForOutcome)) {
                        $inputsForOutcome = [];
                    }

                    $raSum = 0.0;

                    foreach ($criteriaForOutcome as $criteriaCode => $info) {
                        if (!array_key_exists($criteriaCode, $inputsForOutcome)) {
                            $hasError = true;
                            $errorMessage = 'Debes indicar un peso para cada criterio de evaluación seleccionado.';
                            break 2;
                        }

                        $rawValue = $inputsForOutcome[$criteriaCode];
                        if (!is_numeric($rawValue)) {
                            $hasError = true;
                            $errorMessage = 'Los pesos deben ser valores numéricos.';
                            break 2;
                        }

                        $weight = round((float) $rawValue, 2);
                        if ($weight < 0 || $weight > 100) {
                            $hasError = true;
                            $errorMessage = 'Los pesos de cada criterio deben estar comprendidos entre 0 y 100.';
                            break 2;
                        }

                        $raSum += $weight;

                        $unitIds = array_keys($info['units']);
                        if (empty($unitIds)) {
                            continue;
                        }

                        $unitInputs = $criterionUnitInput[$raCode][$criteriaCode] ?? [];
                        if (!is_array($unitInputs)) {
                            $unitInputs = [];
                        }

                        $shareSum = 0.0;

                        foreach ($unitIds as $unitId) {
                            if (!array_key_exists($unitId, $unitInputs)) {
                                $hasError = true;
                                $errorMessage = 'Debes indicar la distribución por unidades para cada criterio.';
                                break 3;
                            }

                            $rawShare = $unitInputs[$unitId];
                            if (!is_numeric($rawShare)) {
                                $hasError = true;
                                $errorMessage = 'La distribución por unidades debe ser numérica.';
                                break 3;
                            }

                            $share = round((float) $rawShare, 2);
                            if ($share < 0 || $share > 100) {
                                $hasError = true;
                                $errorMessage = 'Los porcentajes por unidad deben estar comprendidos entre 0 y 100.';
                                break 3;
                            }

                            $shareSum += $share;

                            if (!isset($normalizedByUnit[$unitId])) {
                                $normalizedByUnit[$unitId] = [];
                            }

                            $normalizedByUnit[$unitId][$criteriaCode] = round(($weight / 100) * ($share / 100), 4);
                        }

                        if ($hasError) {
                            break 2;
                        }

                        if (abs($shareSum - 100.0) > 0.5) {
                            $hasError = true;
                            $errorMessage = 'La distribución por unidades de cada criterio debe sumar 100%.';
                            break 2;
                        }
                    }

                    if ($hasError) {
                        break;
                    }

                    if (abs($raSum - 100.0) > 0.5) {
                        $hasError = true;
                        $errorMessage = 'Los pesos de los criterios de cada resultado de aprendizaje deben sumar 100%.';
                        break;
                    }
                }

                if ($hasError) {
                    $_SESSION['errors']['module_wizard']['weights'] = $errorMessage !== ''
                        ? $errorMessage
                        : 'No se pudieron validar los pesos indicados.';
                    $_SESSION['old']['module_wizard']['weights'] = $weightsInput;
                    $_SESSION['old']['module_wizard']['criterion_unit_weights'] = $criterionUnitInput;
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

            $weight = $this->normalizeWeightValue((float) $row['total_weight']);
            $organized[$unitId][$row['ra_codigo']] = [
                'numero' => $row['ra_numero'],
                'descripcion' => $row['ra_descripcion'],
                'weight' => round($weight * 100, 2),
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
        unset($total);

        return $totals;
    }

    /**
     * @param array<int, array<string, array<string, mixed>>> $criteriaDetailsByUnit
     * @return array<string, array<string, mixed>>
     */
    private function collectOutcomeCriteriaData(array $criteriaDetailsByUnit): array
    {
        $outcomes = [];

        foreach ($criteriaDetailsByUnit as $unitId => $criteriaList) {
            foreach ($criteriaList as $code => $details) {
                $raCode = (string) ($details['codigo_resultado'] ?? '');
                if ($raCode === '') {
                    continue;
                }

                if (!isset($outcomes[$raCode])) {
                    $outcomes[$raCode] = [
                        'codigo' => $raCode,
                        'numero' => $details['resultado_numero'] ?? '',
                        'descripcion' => $details['resultado_descripcion'] ?? '',
                        'criteria' => [],
                    ];
                }

                if (!isset($outcomes[$raCode]['criteria'][$code])) {
                    $outcomes[$raCode]['criteria'][$code] = [
                        'code' => $code,
                        'letra' => $details['letra'] ?? '',
                        'descripcion' => $details['descripcion'] ?? '',
                        'units' => [],
                    ];
                }

                $normalized = $this->normalizeWeightValue((float) ($details['weight'] ?? 0.0));
                if ($normalized < 0.0) {
                    $normalized = 0.0;
                }

                $outcomes[$raCode]['criteria'][$code]['units'][(int) $unitId] = $normalized;
            }
        }

        uasort($outcomes, static function (array $a, array $b): int {
            $numeroA = (string) ($a['numero'] ?? '');
            $numeroB = (string) ($b['numero'] ?? '');

            return strcmp($numeroA, $numeroB);
        });

        foreach ($outcomes as &$outcome) {
            if (!isset($outcome['criteria'])) {
                continue;
            }

            uasort($outcome['criteria'], static function (array $a, array $b): int {
                $labelA = (string) ($a['letra'] ?? $a['code'] ?? '');
                $labelB = (string) ($b['letra'] ?? $b['code'] ?? '');

                return strcmp($labelA, $labelB);
            });
        }
        unset($outcome);

        return $outcomes;
    }

    /**
     * @param array<int, array<string, array<string, mixed>>> &$criteriaDetailsByUnit
     */
    private function ensureCriteriaWeights(array &$criteriaDetailsByUnit, UserModuleUnitCriteriaModel $criteriaModel): void
    {
        $outcomes = $this->collectOutcomeCriteriaData($criteriaDetailsByUnit);
        $pendingUpdates = [];

        foreach ($outcomes as $raData) {
            $criteria = $raData['criteria'] ?? [];
            if (empty($criteria)) {
                continue;
            }

            $criteriaCount = count($criteria);
            if ($criteriaCount === 0) {
                continue;
            }

            $needsReset = false;
            $totalByOutcome = 0.0;

            foreach ($criteria as $criterionInfo) {
                $units = $criterionInfo['units'] ?? [];
                if (empty($units)) {
                    continue;
                }

                $criterionTotal = 0.0;
                foreach ($units as $weight) {
                    $value = $this->normalizeWeightValue((float) $weight);
                    if ($value < 0.0) {
                        $value = 0.0;
                    }
                    $criterionTotal += $value;
                }

                if ($criterionTotal <= 0.0) {
                    $needsReset = true;
                }

                $totalByOutcome += $criterionTotal;
            }

            if (!$needsReset && abs($totalByOutcome - 1.0) <= 0.01) {
                continue;
            }

            $defaultRaWeights = $this->calculateDefaultWeightsForCount($criteriaCount);
            $criterionIndex = 0;

            foreach ($criteria as $criterionCode => $criterionInfo) {
                $units = $criterionInfo['units'] ?? [];
                $unitIds = array_keys($units);
                $unitCount = count($unitIds);
                if ($unitCount === 0) {
                    $criterionIndex++;
                    continue;
                }

                $raWeight = $defaultRaWeights[$criterionIndex] ?? 0.0;
                $defaultShares = $this->calculateDefaultWeightsForCount($unitCount);

                foreach ($unitIds as $unitPosition => $unitId) {
                    $share = $defaultShares[$unitPosition] ?? 0.0;
                    $newWeight = round($raWeight * $share, 4);

                    if (!isset($pendingUpdates[$unitId])) {
                        $pendingUpdates[$unitId] = [];
                    }
                    $pendingUpdates[$unitId][$criterionCode] = $newWeight;

                    if (isset($criteriaDetailsByUnit[$unitId][$criterionCode])) {
                        $criteriaDetailsByUnit[$unitId][$criterionCode]['weight'] = $newWeight;
                    }
                }

                $criterionIndex++;
            }
        }

        foreach ($pendingUpdates as $unitId => $weights) {
            $criteriaModel->updateWeightsForUnit((int) $unitId, $weights);
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
        $outcomes = $this->collectOutcomeCriteriaData($criteriaDetailsByUnit);
        $matrix = [];

        foreach ($outcomes as $raCode => $raData) {
            $criteria = $raData['criteria'] ?? [];
            if (empty($criteria)) {
                continue;
            }

            $matrix[$raCode] = [
                'codigo' => $raData['codigo'],
                'numero' => $raData['numero'],
                'descripcion' => $raData['descripcion'],
                'criteria' => [],
            ];

            $criterionTotals = [];
            $totalByOutcome = 0.0;

            foreach ($criteria as $criterionCode => $criterionInfo) {
                $units = $criterionInfo['units'] ?? [];
                $criterionTotal = 0.0;

                foreach ($units as $unitId => $weight) {
                    $value = $this->normalizeWeightValue((float) $weight);
                    if ($value < 0.0) {
                        $value = 0.0;
                    }

                    $criterionTotal += $value;
                    $units[$unitId] = $value;
                }

                $criterionTotals[$criterionCode] = [
                    'total' => $criterionTotal,
                    'units' => $units,
                    'letra' => $criterionInfo['letra'] ?? '',
                    'descripcion' => $criterionInfo['descripcion'] ?? '',
                ];

                $totalByOutcome += $criterionTotal;
            }

            $criteriaCount = count($criterionTotals);
            $defaultRaWeights = $this->calculateDefaultWeightsForCount($criteriaCount);
            $criterionIndex = 0;

            foreach ($criterionTotals as $criterionCode => $info) {
                $criterionTotal = $info['total'];
                if ($totalByOutcome > 0.0) {
                    $weightFraction = $criterionTotal > 0 ? $criterionTotal / $totalByOutcome : 0.0;
                } else {
                    $weightFraction = $defaultRaWeights[$criterionIndex] ?? 0.0;
                }

                if ($weightFraction < 0.0) {
                    $weightFraction = 0.0;
                }

                $weightPercent = round($weightFraction * 100, 2);

                $units = $info['units'];
                $unitIds = array_keys($units);
                $unitShares = [];
                $shareTotal = 0.0;

                if (!empty($unitIds)) {
                    if ($criterionTotal > 0) {
                        foreach ($unitIds as $unitId) {
                            $unitWeight = $units[$unitId] ?? 0.0;
                            $shareFraction = $criterionTotal > 0 ? ($unitWeight > 0 ? $unitWeight / $criterionTotal : 0.0) : 0.0;
                            $sharePercent = round($shareFraction * 100, 2);
                            $unitShares[$unitId] = $sharePercent;
                            $shareTotal += $sharePercent;
                        }
                    } else {
                        $defaultShares = $this->calculateDefaultWeightsForCount(count($unitIds));
                        foreach ($unitIds as $index => $unitId) {
                            $sharePercent = round(($defaultShares[$index] ?? 0.0) * 100, 2);
                            $unitShares[$unitId] = $sharePercent;
                            $shareTotal += $sharePercent;
                        }
                    }
                }

                $matrix[$raCode]['criteria'][$criterionCode] = [
                    'code' => $criterionCode,
                    'letra' => $info['letra'],
                    'descripcion' => $info['descripcion'],
                    'weight' => $weightPercent,
                    'unit_shares' => $unitShares,
                    'unit_share_total' => round($shareTotal, 2),
                ];

                $criterionIndex++;
            }

            $matrix[$raCode]['total'] = round(array_sum(array_column($matrix[$raCode]['criteria'], 'weight')), 2);
        }

        return $matrix;
    }

    private function normalizeWeightValue(float $weight): float
    {
        if ($weight > 1.0) {
            $weight = $weight / 100;
        }

        if ($weight < 0.0) {
            return 0.0;
        }

        return round($weight, 4);
    }
}
