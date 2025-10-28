<?php

class LearningOutcomeModel extends Model
{
    public function getAll(): array
    {
        $sql = 'SELECT ra.codigo, ra.numero, ra.descripcion, ra.codigo_modulo, ra.codigo_ciclo,
                       m.nombre AS modulo_nombre, c.nombre AS ciclo_nombre
                FROM resultados_aprendizaje ra
                INNER JOIN modulos_ciclo m ON m.codigo = ra.codigo_modulo
                INNER JOIN ciclos_formativos c ON c.codigo = ra.codigo_ciclo
                ORDER BY c.nombre, m.nombre, ra.numero';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(string $codigo, string $numero, string $descripcion, string $codigoModulo, string $codigoCiclo): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO resultados_aprendizaje (codigo, numero, descripcion, codigo_modulo, codigo_ciclo)
            VALUES (:codigo, :numero, :descripcion, :codigo_modulo, :codigo_ciclo)
            ON DUPLICATE KEY UPDATE numero = VALUES(numero), descripcion = VALUES(descripcion),
                codigo_modulo = VALUES(codigo_modulo), codigo_ciclo = VALUES(codigo_ciclo)"
        );

        return $stmt->execute([
            'codigo' => $codigo,
            'numero' => $numero,
            'descripcion' => $descripcion,
            'codigo_modulo' => $codigoModulo,
            'codigo_ciclo' => $codigoCiclo,
        ]);
    }

    public function delete(string $codigo): bool
    {
        $stmt = $this->db->prepare('DELETE FROM resultados_aprendizaje WHERE codigo = :codigo');
        return $stmt->execute(['codigo' => $codigo]);
    }
}
