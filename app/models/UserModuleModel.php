<?php

class UserModuleModel extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByUserId(int $userId): array
    {
        $query = 'SELECT id, module_name, created_at FROM user_modules WHERE user_id = :user_id ORDER BY module_name';
        $statement = $this->db->prepare($query);
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
