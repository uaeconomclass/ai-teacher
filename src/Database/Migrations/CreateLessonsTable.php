<?php
declare(strict_types=1);

namespace App\Database\Migrations;

final class CreateLessonsTable implements MigrationInterface
{
    public function name(): string
    {
        return 'create_lessons_table';
    }

    public function up(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS lessons (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  speaking_goal VARCHAR(255) NOT NULL,
  grammar_focus VARCHAR(255) NULL,
  position INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lessons_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL;
    }
}
