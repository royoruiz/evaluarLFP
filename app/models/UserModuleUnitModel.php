<?php

class UserModuleUnitModel extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByModule(int $userModuleId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, user_module_id, unit_number, unit_label, trimester_1, trimester_2, trimester_3
             FROM user_module_units
             WHERE user_module_id = :module_id
             ORDER BY unit_number'
        );

        $statement->execute(['module_id' => $userModuleId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function replaceUnits(int $userModuleId, int $unitsCount): void
    {
        $currentUnits = $this->getByModule($userModuleId);
        $currentCount = count($currentUnits);

        if ($unitsCount <= 0) {
            $deleteStatement = $this->db->prepare('DELETE FROM user_module_units WHERE user_module_id = :module_id');
            $deleteStatement->execute(['module_id' => $userModuleId]);
            return;
        }

        if ($currentCount === $unitsCount) {
            return;
        }

        if ($unitsCount < $currentCount) {
            $deleteStatement = $this->db->prepare(
                'DELETE FROM user_module_units WHERE user_module_id = :module_id AND unit_number > :limit'
            );
            $deleteStatement->execute([
                'module_id' => $userModuleId,
                'limit' => $unitsCount,
            ]);

            return;
        }

        $insertStatement = $this->db->prepare(
            'INSERT INTO user_module_units (user_module_id, unit_number, unit_label)
             VALUES (:module_id, :unit_number, :unit_label)'
        );

        for ($i = $currentCount + 1; $i <= $unitsCount; $i++) {
            $insertStatement->execute([
                'module_id' => $userModuleId,
                'unit_number' => $i,
                'unit_label' => 'Unidad ' . $i,
            ]);
        }
    }

    /**
     * @param array<int, array<int, string>> $selection
     */
    public function saveTrimesters(int $userModuleId, array $selection): void
    {
        $units = $this->getByModule($userModuleId);
        $updateStatement = $this->db->prepare(
            'UPDATE user_module_units
             SET trimester_1 = :t1, trimester_2 = :t2, trimester_3 = :t3
             WHERE id = :id'
        );

        foreach ($units as $unit) {
            $unitSelection = $selection[$unit['id']] ?? [];
            $trimester1 = in_array('1', $unitSelection, true) ? 1 : 0;
            $trimester2 = in_array('2', $unitSelection, true) ? 1 : 0;
            $trimester3 = in_array('3', $unitSelection, true) ? 1 : 0;

            $updateStatement->execute([
                't1' => $trimester1,
                't2' => $trimester2,
                't3' => $trimester3,
                'id' => $unit['id'],
            ]);
        }
    }
}
