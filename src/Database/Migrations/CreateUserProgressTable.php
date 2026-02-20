<?php
declare(strict_types=1);

namespace App\Database\Migrations;

final class CreateUserProgressTable implements MigrationInterface
{
    public function name(): string
    {
        return 'create_user_progress_table';
    }

    public function up(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS user_progress (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  topic_id BIGINT UNSIGNED NOT NULL,
  completed_lessons INT UNSIGNED NOT NULL DEFAULT 0,
  total_lessons INT UNSIGNED NOT NULL DEFAULT 0,
  accuracy_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  fluency_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_topic (user_id, topic_id),
  CONSTRAINT fk_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_progress_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL;
    }
}
