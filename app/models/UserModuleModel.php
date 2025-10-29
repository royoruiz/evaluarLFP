<?php

class UserModuleModel extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByUserId(int $userId): array
    {
        $query = <<<SQL
        SELECT
            um.id,
            um.module_code,
            COALESCE(mc.nombre, um.module_name) AS module_name,
            um.units_count,
            um.creation_state,
            um.created_at
        FROM user_modules AS um
        LEFT JOIN modulos_ciclo AS mc ON mc.codigo = um.module_code
        WHERE um.user_id = :user_id
        ORDER BY COALESCE(mc.nombre, um.module_name)
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createFromCatalog(int $userId, array $module): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO user_modules (user_id, module_code, module_name, units_count, creation_state)
             VALUES (:user_id, :module_code, :module_name, :units_count, :creation_state)'
        );

        $statement->execute([
            'user_id' => $userId,
            'module_code' => $module['codigo'] ?? null,
            'module_name' => $module['nombre'] ?? 'Módulo sin nombre',
            'units_count' => 0,
            'creation_state' => 'unidades',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findForUser(int $moduleId, int $userId): ?array
    {
        $query = <<<SQL
        SELECT
            um.id,
            um.user_id,
            um.module_code,
            COALESCE(mc.nombre, um.module_name) AS module_name,
            um.module_name AS stored_module_name,
            um.units_count,
            um.creation_state,
            um.created_at,
            mc.nombre AS catalog_name,
            mc.codigo_ciclo,
            mc.curso
        FROM user_modules AS um
        LEFT JOIN modulos_ciclo AS mc ON mc.codigo = um.module_code
        WHERE um.id = :id AND um.user_id = :user_id
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute([
            'id' => $moduleId,
            'user_id' => $userId,
        ]);

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }

    public function updateUnitsCount(int $moduleId, int $units): void
    {
        $statement = $this->db->prepare('UPDATE user_modules SET units_count = :units WHERE id = :id');
        $statement->execute([
            'units' => $units,
            'id' => $moduleId,
        ]);
    }

    public function updateCreationState(int $moduleId, string $state): void
    {
        $statement = $this->db->prepare('UPDATE user_modules SET creation_state = :state WHERE id = :id');
        $statement->execute([
            'state' => $state,
            'id' => $moduleId,
        ]);
    }

    public function markCompleted(int $moduleId): void
    {
        $this->updateCreationState($moduleId, 'completado');
    }
}
