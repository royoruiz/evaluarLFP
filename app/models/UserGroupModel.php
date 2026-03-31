<?php

class UserGroupModel extends Model
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByUserId(int $userId): array
    {
        $statement = $this->db->prepare(
            "SELECT id, group_name, status, created_at FROM user_groups WHERE user_id = :user_id AND status = 'Activa' ORDER BY group_name ASC"
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(int $userId, string $groupName): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO user_groups (user_id, group_name) VALUES (:user_id, :group_name)'
        );

        $statement->execute([
            'user_id' => $userId,
            'group_name' => $groupName,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findForUser(int $groupId, int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, user_id, group_name, status, created_at FROM user_groups WHERE id = :id AND user_id = :user_id'
        );

        $statement->execute([
            'id' => $groupId,
            'user_id' => $userId,
        ]);

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }

    public function markAsDeleted(int $groupId): void
    {
        $statement = $this->db->prepare("UPDATE user_groups SET status = 'Borrada' WHERE id = :id");
        $statement->execute(['id' => $groupId]);
    }
}
