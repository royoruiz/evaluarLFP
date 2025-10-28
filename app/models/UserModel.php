<?php

class UserModel extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function create(string $name, string $email, string $password, string $role = 'user'): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT id, name, email, role, created_at FROM users ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRole(int $userId, string $role): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET role = :role WHERE id = :id');
        return $stmt->execute([
            'role' => $role,
            'id' => $userId,
        ]);
    }
}
