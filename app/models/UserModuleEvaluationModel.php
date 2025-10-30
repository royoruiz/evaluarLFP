<?php

class UserModuleEvaluationModel extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByUserId(int $userId): array
    {
        $query = <<<SQL
        SELECT
            ume.id,
            ume.evaluation_name,
            ume.academic_year,
            ume.class_group,
            ume.created_at,
            ume.user_module_id,
            um.module_name
        FROM user_module_evaluations AS ume
        LEFT JOIN user_modules AS um ON um.id = ume.user_module_id
        WHERE ume.user_id = :user_id
        ORDER BY ume.academic_year DESC, um.module_name ASC, ume.evaluation_name ASC
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createEvaluation(
        int $userId,
        int $moduleId,
        string $name,
        string $academicYear,
        string $classGroup
    ): int {
        $statement = $this->db->prepare(
            'INSERT INTO user_module_evaluations (user_id, user_module_id, evaluation_name, academic_year, class_group)
             VALUES (:user_id, :module_id, :name, :academic_year, :class_group)'
        );

        $statement->execute([
            'user_id' => $userId,
            'module_id' => $moduleId,
            'name' => $name,
            'academic_year' => $academicYear,
            'class_group' => $classGroup,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findForUser(int $evaluationId, int $userId): ?array
    {
        $query = <<<SQL
        SELECT
            ume.id,
            ume.user_id,
            ume.user_module_id,
            ume.evaluation_name,
            ume.academic_year,
            ume.class_group,
            ume.created_at,
            um.module_code,
            COALESCE(mc.nombre, um.module_name) AS module_name,
            um.units_count
        FROM user_module_evaluations AS ume
        LEFT JOIN user_modules AS um ON um.id = ume.user_module_id
        LEFT JOIN modulos_ciclo AS mc ON mc.codigo = um.module_code
        WHERE ume.id = :id AND ume.user_id = :user_id
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute([
            'id' => $evaluationId,
            'user_id' => $userId,
        ]);

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }

    public function updateEvaluation(
        int $evaluationId,
        string $name,
        string $academicYear,
        string $classGroup
    ): void {
        $statement = $this->db->prepare(
            'UPDATE user_module_evaluations
             SET evaluation_name = :name, academic_year = :academic_year, class_group = :class_group
             WHERE id = :id'
        );

        $statement->execute([
            'name' => $name,
            'academic_year' => $academicYear,
            'class_group' => $classGroup,
            'id' => $evaluationId,
        ]);
    }
}
