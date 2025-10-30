<?php

class EvaluationInstrumentCriteriaModel extends Model
{
    /**
     * @param array<int, string> $criteriaCodes
     */
    public function setForInstrument(int $instrumentId, array $criteriaCodes): void
    {
        $delete = $this->db->prepare(
            'DELETE FROM evaluation_instrument_criteria WHERE evaluation_instrument_id = :instrument_id'
        );
        $delete->execute(['instrument_id' => $instrumentId]);

        if (empty($criteriaCodes)) {
            return;
        }

        $insert = $this->db->prepare(
            'INSERT INTO evaluation_instrument_criteria (evaluation_instrument_id, criteria_code)
             VALUES (:instrument_id, :criteria_code)'
        );

        foreach (array_unique($criteriaCodes) as $criteriaCode) {
            $insert->execute([
                'instrument_id' => $instrumentId,
                'criteria_code' => $criteriaCode,
            ]);
        }
    }

    /**
     * @param array<int, int> $instrumentIds
     * @return array<int, array<string, mixed>>
     */
    public function getByInstrumentIds(array $instrumentIds): array
    {
        if (empty($instrumentIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($instrumentIds), '?'));
        $query = <<<SQL
        SELECT
            eic.evaluation_instrument_id,
            eic.criteria_code,
            ce.letra,
            ce.descripcion,
            ce.codigo_resultado,
            ra.numero AS resultado_numero,
            ra.descripcion AS resultado_descripcion
        FROM evaluation_instrument_criteria AS eic
        INNER JOIN criterios_evaluacion AS ce ON ce.codigo = eic.criteria_code
        INNER JOIN resultados_aprendizaje AS ra ON ra.codigo = ce.codigo_resultado
        WHERE eic.evaluation_instrument_id IN ({$placeholders})
        ORDER BY eic.evaluation_instrument_id, ra.numero, ce.letra
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute(array_values($instrumentIds));

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByUnit(int $evaluationUnitId): array
    {
        $query = <<<SQL
        SELECT
            eic.evaluation_instrument_id,
            eic.criteria_code,
            ce.letra,
            ce.descripcion,
            ce.codigo_resultado,
            ra.numero AS resultado_numero,
            ra.descripcion AS resultado_descripcion
        FROM evaluation_instrument_criteria AS eic
        INNER JOIN evaluation_instruments AS ei ON ei.id = eic.evaluation_instrument_id
        INNER JOIN criterios_evaluacion AS ce ON ce.codigo = eic.criteria_code
        INNER JOIN resultados_aprendizaje AS ra ON ra.codigo = ce.codigo_resultado
        WHERE ei.evaluation_unit_id = :unit_id
        ORDER BY eic.evaluation_instrument_id, ra.numero, ce.letra
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute(['unit_id' => $evaluationUnitId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, string>
     */
    public function getAssignedCodesByUnit(int $evaluationUnitId): array
    {
        $statement = $this->db->prepare(
            'SELECT DISTINCT eic.criteria_code
             FROM evaluation_instrument_criteria AS eic
             INNER JOIN evaluation_instruments AS ei ON ei.id = eic.evaluation_instrument_id
             WHERE ei.evaluation_unit_id = :unit_id'
        );

        $statement->execute(['unit_id' => $evaluationUnitId]);

        return array_map(static fn ($row) => $row['criteria_code'], $statement->fetchAll(\PDO::FETCH_ASSOC));
    }
}
