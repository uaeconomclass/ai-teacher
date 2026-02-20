<?php
declare(strict_types=1);

namespace App\Database\Migrations;

final class CreateDialoguesTable implements MigrationInterface
{
    public function name(): string
    {
        return 'create_dialogues_table';
    }

    public function up(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS dialogues (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  lesson_id BIGINT UNSIGNED NULL,
  level_code ENUM('A1','A2','B1','B2','C1','C2') NOT NULL,
  topic_slug VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dialogues_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_dialogues_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL
) ENGINE=InnoDB;
SQL;
    }
}
