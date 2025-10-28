<?php

class CycleModuleModel extends Model
{
    public function getAll(): array
    {
        $sql = 'SELECT m.codigo, m.nombre, m.curso, m.codigo_ciclo, c.nombre AS ciclo_nombre
                FROM modulos_ciclo m
                INNER JOIN ciclos_formativos c ON c.codigo = m.codigo_ciclo
                ORDER BY c.nombre, m.nombre';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(string $codigo, string $nombre, string $codigoCiclo, int $curso): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO modulos_ciclo (codigo, nombre, codigo_ciclo, curso)
            VALUES (:codigo, :nombre, :codigo_ciclo, :curso)
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), codigo_ciclo = VALUES(codigo_ciclo), curso = VALUES(curso)"
        );

        return $stmt->execute([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'codigo_ciclo' => $codigoCiclo,
            'curso' => $curso,
        ]);
    }

    public function delete(string $codigo): bool
    {
        $stmt = $this->db->prepare('DELETE FROM modulos_ciclo WHERE codigo = :codigo');
        return $stmt->execute(['codigo' => $codigo]);
    }

    public function findByCode(string $codigo): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT codigo, nombre, codigo_ciclo, curso FROM modulos_ciclo WHERE codigo = :codigo'
        );
        $stmt->execute(['codigo' => $codigo]);

        $module = $stmt->fetch(PDO::FETCH_ASSOC);

        return $module !== false ? $module : null;
    }
}
