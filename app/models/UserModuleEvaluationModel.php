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
}
