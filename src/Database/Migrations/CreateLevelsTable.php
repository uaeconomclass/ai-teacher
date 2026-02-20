<?php
declare(strict_types=1);

namespace App\Database\Migrations;

final class CreateLevelsTable implements MigrationInterface
{
    public function name(): string
    {
        return 'create_levels_table';
    }

    public function up(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS levels (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code ENUM('A1','A2','B1','B2','C1','C2') NOT NULL UNIQUE,
  title VARCHAR(50) NOT NULL
) ENGINE=InnoDB;
SQL;
    }
}
