<?php

class StudentInstrumentGradeModel extends Model
{
    /**
     * @return array<string, string>
     */
    public function getGradesByEvaluation(int $evaluationId): array
    {
        $query = <<<SQL
        SELECT
            sig.group_student_id,
            sig.evaluation_instrument_id,
            sig.grade
        FROM student_instrument_grades AS sig
        INNER JOIN evaluation_instruments AS ei ON ei.id = sig.evaluation_instrument_id
        INNER JOIN evaluation_units AS eu ON eu.id = ei.evaluation_unit_id
        WHERE eu.evaluation_id = :evaluation_id
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute(['evaluation_id' => $evaluationId]);

        $grades = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $key = (int) $row['group_student_id'] . ':' . (int) $row['evaluation_instrument_id'];
            $grades[$key] = (string) $row['grade'];
        }

        return $grades;
    }

    public function upsertGrade(int $studentId, int $instrumentId, string $grade): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO student_instrument_grades (group_student_id, evaluation_instrument_id, grade)
             VALUES (:student_id, :instrument_id, :grade)
             ON DUPLICATE KEY UPDATE grade = VALUES(grade)'
        );

        $statement->execute([
            'student_id' => $studentId,
            'instrument_id' => $instrumentId,
            'grade' => $grade,
        ]);
    }
}
