<?php

class EvaluationInstrumentModel extends Model
{
    public function create(int $evaluationUnitId, string $name, ?string $description = null): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO evaluation_instruments (evaluation_unit_id, name, description)
             VALUES (:unit_id, :name, :description)'
        );

        $statement->execute([
            'unit_id' => $evaluationUnitId,
            'name' => $name,
            'description' => $description,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $instrumentId, string $name, ?string $description = null): void
    {
        $statement = $this->db->prepare(
            'UPDATE evaluation_instruments
             SET name = :name, description = :description
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $instrumentId,
            'name' => $name,
            'description' => $description,
        ]);
    }

    public function delete(int $instrumentId): void
    {
        $statement = $this->db->prepare('DELETE FROM evaluation_instruments WHERE id = :id');
        $statement->execute(['id' => $instrumentId]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByEvaluation(int $evaluationId): array
    {
        $query = <<<SQL
        SELECT
            ei.id,
            ei.evaluation_unit_id,
            ei.name,
            ei.description
        FROM evaluation_instruments AS ei
        INNER JOIN evaluation_units AS eu ON eu.id = ei.evaluation_unit_id
        WHERE eu.evaluation_id = :evaluation_id
        ORDER BY ei.id
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute(['evaluation_id' => $evaluationId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByUnit(int $evaluationUnitId): array
    {
        $statement = $this->db->prepare(
            'SELECT id, evaluation_unit_id, name, description
             FROM evaluation_instruments
             WHERE evaluation_unit_id = :unit_id
             ORDER BY id'
        );

        $statement->execute(['unit_id' => $evaluationUnitId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findForEvaluation(int $instrumentId, int $evaluationId): ?array
    {
        $query = <<<SQL
        SELECT
            ei.id,
            ei.evaluation_unit_id,
            ei.name,
            ei.description,
            eu.evaluation_id
        FROM evaluation_instruments AS ei
        INNER JOIN evaluation_units AS eu ON eu.id = ei.evaluation_unit_id
        WHERE ei.id = :id AND eu.evaluation_id = :evaluation_id
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute([
            'id' => $instrumentId,
            'evaluation_id' => $evaluationId,
        ]);

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }
}
