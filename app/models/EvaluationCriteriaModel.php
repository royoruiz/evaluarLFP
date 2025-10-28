<?php

class EvaluationCriteriaModel extends Model
{
    public function getAll(): array
    {
        $sql = 'SELECT ce.codigo, ce.letra, ce.descripcion, ce.codigo_resultado,
                       ra.numero AS resultado_numero, ra.codigo AS resultado_codigo,
                       m.nombre AS modulo_nombre, c.nombre AS ciclo_nombre
                FROM criterios_evaluacion ce
                INNER JOIN resultados_aprendizaje ra ON ra.codigo = ce.codigo_resultado
                INNER JOIN modulos_ciclo m ON m.codigo = ra.codigo_modulo
                INNER JOIN ciclos_formativos c ON c.codigo = ra.codigo_ciclo
                ORDER BY c.nombre, m.nombre, ra.numero, ce.letra';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByModule(string $moduleCode): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                 ce.codigo,
                 ce.letra,
                 ce.descripcion,
                 ce.codigo_resultado,
                 ra.numero AS resultado_numero,
                 ra.descripcion AS resultado_descripcion
             FROM criterios_evaluacion AS ce
             INNER JOIN resultados_aprendizaje AS ra ON ra.codigo = ce.codigo_resultado
             WHERE ra.codigo_modulo = :module_code
             ORDER BY ra.numero, ce.letra'
        );

        $stmt->execute(['module_code' => $moduleCode]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(string $codigo, string $letra, string $descripcion, string $codigoResultado): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO criterios_evaluacion (codigo, letra, descripcion, codigo_resultado)
            VALUES (:codigo, :letra, :descripcion, :codigo_resultado)
            ON DUPLICATE KEY UPDATE letra = VALUES(letra), descripcion = VALUES(descripcion),
                codigo_resultado = VALUES(codigo_resultado)"
        );

        return $stmt->execute([
            'codigo' => $codigo,
            'letra' => $letra,
            'descripcion' => $descripcion,
            'codigo_resultado' => $codigoResultado,
        ]);
    }

    public function delete(string $codigo): bool
    {
        $stmt = $this->db->prepare('DELETE FROM criterios_evaluacion WHERE codigo = :codigo');
        return $stmt->execute(['codigo' => $codigo]);
    }
}
