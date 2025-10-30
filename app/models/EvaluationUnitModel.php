<?php

class EvaluationUnitModel extends Model
{
    public function createFromModule(int $evaluationId, int $userModuleId): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO evaluation_units (evaluation_id, user_module_unit_id)
             VALUES (:evaluation_id, :unit_id)'
        );

        $unitModel = new UserModuleUnitModel();
        $units = $unitModel->getByModule($userModuleId);

        foreach ($units as $unit) {
            $statement->execute([
                'evaluation_id' => $evaluationId,
                'unit_id' => $unit['id'],
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByEvaluation(int $evaluationId): array
    {
        $query = <<<SQL
        SELECT
            eu.id AS evaluation_unit_id,
            eu.evaluation_id,
            umu.id AS module_unit_id,
            umu.unit_number,
            umu.unit_label
        FROM evaluation_units AS eu
        INNER JOIN user_module_units AS umu ON umu.id = eu.user_module_unit_id
        WHERE eu.evaluation_id = :evaluation_id
        ORDER BY umu.unit_number
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute(['evaluation_id' => $evaluationId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findForEvaluation(int $evaluationUnitId, int $evaluationId): ?array
    {
        $query = <<<SQL
        SELECT
            eu.id AS evaluation_unit_id,
            eu.evaluation_id,
            umu.id AS module_unit_id,
            umu.unit_number,
            umu.unit_label
        FROM evaluation_units AS eu
        INNER JOIN user_module_units AS umu ON umu.id = eu.user_module_unit_id
        WHERE eu.id = :id AND eu.evaluation_id = :evaluation_id
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute([
            'id' => $evaluationUnitId,
            'evaluation_id' => $evaluationId,
        ]);

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }
}
