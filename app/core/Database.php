<?php

class Database
{
    private static ?\PDO $connection = null;

    public static function getConnection(): \PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            try {
                self::$connection = new \PDO($dsn, DB_USER, DB_PASS, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]);
                self::migrate();
            } catch (\PDOException $exception) {
                throw new RuntimeException('No se pudo conectar a MySQL: ' . $exception->getMessage());
            }
        }

        return self::$connection;
    }

    private static function migrate(): void
    {
        $usersTable = <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $usersRoleMigration = function (\PDO $connection): void {
            $columnExistsQuery = <<<SQL
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'role';
            SQL;

            $columnExists = $connection->query($columnExistsQuery)->fetchColumn();

            if ((int) $columnExists === 0) {
                $connection->exec(<<<SQL
                ALTER TABLE users
                    ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER password;
                SQL);
            }
        };

        $userModulesTable = <<<SQL
        CREATE TABLE IF NOT EXISTS user_modules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            module_code CHAR(5) DEFAULT NULL,
            module_name VARCHAR(255) NOT NULL,
            units_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
            creation_state VARCHAR(32) NOT NULL DEFAULT 'seleccion',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_user_modules_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE,
            INDEX idx_user_modules_user (user_id),
            INDEX idx_user_modules_module (module_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $userModuleTableMigration = function (\PDO $connection): void {
            $columnExistsQuery = static function (string $column) use ($connection): bool {
                $query = <<<SQL
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'user_modules'
                  AND COLUMN_NAME = :column
                SQL;

                $statement = $connection->prepare($query);
                $statement->execute(['column' => $column]);

                return (int) $statement->fetchColumn() > 0;
            };

            if (!$columnExistsQuery('module_code')) {
                $connection->exec(<<<SQL
                ALTER TABLE user_modules
                    ADD COLUMN module_code CHAR(5) DEFAULT NULL AFTER user_id,
                    ADD INDEX idx_user_modules_module (module_code);
                SQL);
            }

            if (!$columnExistsQuery('units_count')) {
                $connection->exec(<<<SQL
                ALTER TABLE user_modules
                    ADD COLUMN units_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER module_name;
                SQL);
            }

            if (!$columnExistsQuery('creation_state')) {
                $connection->exec(<<<SQL
                ALTER TABLE user_modules
                    ADD COLUMN creation_state VARCHAR(32) NOT NULL DEFAULT 'seleccion' AFTER units_count;
                SQL);
            }
        };

        $userModuleEvaluationsTable = <<<SQL
        CREATE TABLE IF NOT EXISTS user_module_evaluations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            user_module_id INT UNSIGNED DEFAULT NULL,
            evaluation_name VARCHAR(255) NOT NULL,
            academic_year VARCHAR(9) NOT NULL DEFAULT '25/26',
            class_group VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_user_module_evaluations_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_user_module_evaluations_module
                FOREIGN KEY (user_module_id) REFERENCES user_modules(id)
                ON DELETE SET NULL,
            INDEX idx_user_module_evaluations_user (user_id),
            INDEX idx_user_module_evaluations_module (user_module_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $userModuleEvaluationsMigration = function (\PDO $connection): void {
            $columnExistsQuery = <<<SQL
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_module_evaluations'
              AND COLUMN_NAME = 'class_group';
            SQL;

            $statement = $connection->query($columnExistsQuery);

            if ((int) $statement->fetchColumn() === 0) {
                $connection->exec(<<<SQL
                ALTER TABLE user_module_evaluations
                    ADD COLUMN class_group VARCHAR(255) NOT NULL DEFAULT '' AFTER academic_year;
                SQL);

                $connection->exec(<<<SQL
                ALTER TABLE user_module_evaluations
                    ALTER COLUMN class_group DROP DEFAULT;
                SQL);
            }
        };

        $evaluationUnitsTable = <<<SQL
        CREATE TABLE IF NOT EXISTS evaluation_units (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            evaluation_id INT UNSIGNED NOT NULL,
            user_module_unit_id INT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_evaluation_units_evaluation
                FOREIGN KEY (evaluation_id) REFERENCES user_module_evaluations(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_evaluation_units_module_unit
                FOREIGN KEY (user_module_unit_id) REFERENCES user_module_units(id)
                ON DELETE CASCADE,
            UNIQUE KEY uniq_evaluation_unit (evaluation_id, user_module_unit_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $evaluationInstrumentsTable = <<<SQL
        CREATE TABLE IF NOT EXISTS evaluation_instruments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            evaluation_unit_id INT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            weight DECIMAL(6,2) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_evaluation_instruments_unit
                FOREIGN KEY (evaluation_unit_id) REFERENCES evaluation_units(id)
                ON DELETE CASCADE,
            INDEX idx_evaluation_instruments_unit (evaluation_unit_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $evaluationInstrumentCriteriaTable = <<<SQL
        CREATE TABLE IF NOT EXISTS evaluation_instrument_criteria (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            evaluation_instrument_id INT UNSIGNED NOT NULL,
            criteria_code VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_evaluation_instrument_criteria_instrument
                FOREIGN KEY (evaluation_instrument_id) REFERENCES evaluation_instruments(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_evaluation_instrument_criteria_criterion
                FOREIGN KEY (criteria_code) REFERENCES criterios_evaluacion(codigo)
                ON DELETE CASCADE,
            UNIQUE KEY uniq_evaluation_instrument_criteria (evaluation_instrument_id, criteria_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $cyclesTable = <<<SQL
        CREATE TABLE IF NOT EXISTS ciclos_formativos (
            codigo VARCHAR(10) PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            familia VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $cycleModulesTable = <<<SQL
        CREATE TABLE IF NOT EXISTS modulos_ciclo (
            codigo CHAR(5) PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            codigo_ciclo VARCHAR(10) NOT NULL,
            curso TINYINT UNSIGNED NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_modulos_ciclo_ciclo
                FOREIGN KEY (codigo_ciclo) REFERENCES ciclos_formativos(codigo)
                ON DELETE CASCADE,
            INDEX idx_modulos_ciclo_ciclo (codigo_ciclo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $learningOutcomesTable = <<<SQL
        CREATE TABLE IF NOT EXISTS resultados_aprendizaje (
            codigo VARCHAR(20) PRIMARY KEY,
            numero VARCHAR(10) NOT NULL,
            descripcion TEXT NOT NULL,
            codigo_modulo CHAR(5) NOT NULL,
            codigo_ciclo VARCHAR(10) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_resultados_aprendizaje_modulo
                FOREIGN KEY (codigo_modulo) REFERENCES modulos_ciclo(codigo)
                ON DELETE CASCADE,
            CONSTRAINT fk_resultados_aprendizaje_ciclo
                FOREIGN KEY (codigo_ciclo) REFERENCES ciclos_formativos(codigo)
                ON DELETE CASCADE,
            INDEX idx_resultados_aprendizaje_modulo (codigo_modulo),
            INDEX idx_resultados_aprendizaje_ciclo (codigo_ciclo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $evaluationCriteriaTable = <<<SQL
        CREATE TABLE IF NOT EXISTS criterios_evaluacion (
            codigo VARCHAR(20) PRIMARY KEY,
            letra CHAR(1) NOT NULL,
            descripcion TEXT NOT NULL,
            codigo_resultado VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_criterios_resultado
                FOREIGN KEY (codigo_resultado) REFERENCES resultados_aprendizaje(codigo)
                ON DELETE CASCADE,
            INDEX idx_criterios_resultado (codigo_resultado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $userModuleUnitsTable = <<<SQL
        CREATE TABLE IF NOT EXISTS user_module_units (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_module_id INT UNSIGNED NOT NULL,
            unit_number TINYINT UNSIGNED NOT NULL,
            unit_label VARCHAR(100) NOT NULL,
            trimester_1 TINYINT(1) NOT NULL DEFAULT 0,
            trimester_2 TINYINT(1) NOT NULL DEFAULT 0,
            trimester_3 TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_user_module_units_module
                FOREIGN KEY (user_module_id) REFERENCES user_modules(id)
                ON DELETE CASCADE,
            UNIQUE KEY uniq_user_module_unit (user_module_id, unit_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $userModuleCriteriaTable = <<<SQL
        CREATE TABLE IF NOT EXISTS user_module_unit_criteria (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_module_unit_id INT UNSIGNED NOT NULL,
            criteria_code VARCHAR(20) NOT NULL,
            weight DECIMAL(6,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_user_module_criteria_unit
                FOREIGN KEY (user_module_unit_id) REFERENCES user_module_units(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_user_module_criteria_criterion
                FOREIGN KEY (criteria_code) REFERENCES criterios_evaluacion(codigo)
                ON DELETE CASCADE,
            UNIQUE KEY uniq_user_module_unit_criteria (user_module_unit_id, criteria_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        self::$connection->exec($usersTable);
        $usersRoleMigration(self::$connection);
        self::$connection->exec($userModulesTable);
        $userModuleTableMigration(self::$connection);
        self::$connection->exec($userModuleEvaluationsTable);
        $userModuleEvaluationsMigration(self::$connection);
        self::$connection->exec($cyclesTable);
        self::$connection->exec($cycleModulesTable);
        self::$connection->exec($learningOutcomesTable);
        self::$connection->exec($evaluationCriteriaTable);
        self::$connection->exec($userModuleUnitsTable);
        self::$connection->exec($userModuleCriteriaTable);
        self::$connection->exec($evaluationUnitsTable);
        self::$connection->exec($evaluationInstrumentsTable);
        self::$connection->exec($evaluationInstrumentCriteriaTable);
    }
}
