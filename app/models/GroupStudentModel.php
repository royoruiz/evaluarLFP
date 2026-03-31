<?php

class GroupStudentModel extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveByGroup(int $groupId): array
    {
        $statement = $this->db->prepare(
            "SELECT id, user_group_id, nia, student_name, status, created_at
             FROM group_students
             WHERE user_group_id = :group_id AND status = 'Activa'
             ORDER BY student_name ASC"
        );

        $statement->execute(['group_id' => $groupId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createOrRestore(int $groupId, string $nia, string $studentName): void
    {
        $existing = $this->findByNia($groupId, $nia);

        if ($existing !== null) {
            $statement = $this->db->prepare(
                "UPDATE group_students
                 SET student_name = :student_name, status = 'Activa'
                 WHERE id = :id"
            );

            $statement->execute([
                'student_name' => $studentName,
                'id' => (int) $existing['id'],
            ]);

            return;
        }

        $statement = $this->db->prepare(
            'INSERT INTO group_students (user_group_id, nia, student_name) VALUES (:group_id, :nia, :student_name)'
        );

        $statement->execute([
            'group_id' => $groupId,
            'nia' => $nia,
            'student_name' => $studentName,
        ]);
    }

    public function findById(int $studentId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, user_group_id, nia, student_name, status FROM group_students WHERE id = :id'
        );
        $statement->execute(['id' => $studentId]);

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }

    public function findActiveByNia(int $groupId, string $nia): ?array
    {
        $student = $this->findByNia($groupId, $nia);
        if ($student === null) {
            return null;
        }

        return ($student['status'] ?? '') === 'Activa' ? $student : null;
    }

    private function findByNia(int $groupId, string $nia): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, user_group_id, nia, student_name, status FROM group_students WHERE user_group_id = :group_id AND nia = :nia LIMIT 1'
        );

        $statement->execute([
            'group_id' => $groupId,
            'nia' => $nia,
        ]);

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }

    public function markAsDeleted(int $studentId): void
    {
        $statement = $this->db->prepare("UPDATE group_students SET status = 'Borrada' WHERE id = :id");
        $statement->execute(['id' => $studentId]);
    }
}
