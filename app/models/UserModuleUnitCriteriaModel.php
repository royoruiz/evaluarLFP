<?php

class UserModuleUnitCriteriaModel extends Model
{
    /**
     * @param array<int, string> $criteriaCodes
     */
    public function setCriteriaForUnit(int $unitId, array $criteriaCodes): void
    {
        $deleteStatement = $this->db->prepare('DELETE FROM user_module_unit_criteria WHERE user_module_unit_id = :unit_id');
        $deleteStatement->execute(['unit_id' => $unitId]);

        if (empty($criteriaCodes)) {
            return;
        }

        $weights = $this->calculateDefaultWeights(count($criteriaCodes));
        $insertStatement = $this->db->prepare(
            'INSERT INTO user_module_unit_criteria (user_module_unit_id, criteria_code, weight)
             VALUES (:unit_id, :criteria_code, :weight)'
        );

        foreach (array_values($criteriaCodes) as $index => $criteriaCode) {
            $insertStatement->execute([
                'unit_id' => $unitId,
                'criteria_code' => $criteriaCode,
                'weight' => round((float) ($weights[$index] ?? 0.0) * 100, 2),
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByModule(int $userModuleId): array
    {
        $statement = $this->db->prepare(
            'SELECT
                 umuc.id,
                 umuc.user_module_unit_id,
                 umuc.criteria_code,
                 umuc.weight,
                 ce.letra,
                 ce.descripcion,
                 ce.codigo_resultado,
                 ra.numero AS resultado_numero,
                 ra.descripcion AS resultado_descripcion
             FROM user_module_unit_criteria AS umuc
             INNER JOIN user_module_units AS umu ON umu.id = umuc.user_module_unit_id
             INNER JOIN criterios_evaluacion AS ce ON ce.codigo = umuc.criteria_code
             INNER JOIN resultados_aprendizaje AS ra ON ra.codigo = ce.codigo_resultado
             WHERE umu.user_module_id = :module_id
             ORDER BY umu.unit_number, ra.numero, ce.letra'
        );

        $statement->execute(['module_id' => $userModuleId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByUnit(int $unitId): array
    {
        $statement = $this->db->prepare(
            'SELECT
                 umuc.id,
                 umuc.user_module_unit_id,
                 umuc.criteria_code,
                 umuc.weight,
                 ce.letra,
                 ce.descripcion,
                 ce.codigo_resultado,
                 ra.numero AS resultado_numero,
                 ra.descripcion AS resultado_descripcion
             FROM user_module_unit_criteria AS umuc
             INNER JOIN criterios_evaluacion AS ce ON ce.codigo = umuc.criteria_code
             INNER JOIN resultados_aprendizaje AS ra ON ra.codigo = ce.codigo_resultado
             WHERE umuc.user_module_unit_id = :unit_id
             ORDER BY ra.numero, ce.letra'
        );

        $statement->execute(['unit_id' => $unitId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUnitRaWeights(int $userModuleId): array
    {
        $statement = $this->db->prepare(
            'SELECT
                 umu.id AS unit_id,
                 umu.unit_number,
                 umu.unit_label,
                 ra.codigo AS ra_codigo,
                 ra.numero AS ra_numero,
                 ra.descripcion AS ra_descripcion,
                 SUM(umuc.weight) AS total_weight
             FROM user_module_unit_criteria AS umuc
             INNER JOIN user_module_units AS umu ON umu.id = umuc.user_module_unit_id
             INNER JOIN criterios_evaluacion AS ce ON ce.codigo = umuc.criteria_code
             INNER JOIN resultados_aprendizaje AS ra ON ra.codigo = ce.codigo_resultado
             WHERE umu.user_module_id = :module_id
             GROUP BY umu.id, ra.codigo
             ORDER BY umu.unit_number, ra.numero'
        );

        $statement->execute(['module_id' => $userModuleId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string, string> $weights
     */
    public function updateWeightsForUnit(int $unitId, array $weights): void
    {
        $updateStatement = $this->db->prepare(
            'UPDATE user_module_unit_criteria SET weight = :weight
             WHERE user_module_unit_id = :unit_id AND criteria_code = :criteria_code'
        );

        foreach ($weights as $criteriaCode => $weight) {
            $updateStatement->execute([
                'weight' => round((float) $weight, 2),
                'unit_id' => $unitId,
                'criteria_code' => $criteriaCode,
            ]);
        }
    }

    /**
     * @return array<int, float>
     */
    private function calculateDefaultWeights(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $weights = [];
        $accumulated = 0.0;

        for ($index = 0; $index < $count; $index++) {
            if ($index === $count - 1) {
                $value = max(0.0, 1.0 - $accumulated);
            } else {
                $value = 1.0 / $count;
                $accumulated += $value;
            }

            $weights[] = $value;
        }

        return $weights;
    }
}
