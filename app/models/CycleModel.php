<?php

class CycleModel extends Model
{
    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT codigo, nombre, familia, created_at FROM ciclos_formativos ORDER BY nombre');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(string $codigo, string $nombre, string $familia): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ciclos_formativos (codigo, nombre, familia) VALUES (:codigo, :nombre, :familia)
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), familia = VALUES(familia)"
        );

        return $stmt->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'familia' => $familia,
        ]);
    }

    public function delete(string $codigo): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ciclos_formativos WHERE codigo = :codigo');
        return $stmt->execute(['codigo' => $codigo]);
    }
}
