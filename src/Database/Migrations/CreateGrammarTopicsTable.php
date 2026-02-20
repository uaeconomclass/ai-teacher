<?php
declare(strict_types=1);

namespace App\Database\Migrations;

final class CreateGrammarTopicsTable implements MigrationInterface
{
    public function name(): string
    {
        return 'create_grammar_topics_table';
    }

    public function up(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS grammar_topics (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  level_id BIGINT UNSIGNED NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  title VARCHAR(180) NOT NULL,
  position INT UNSIGNED NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_grammar_level FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL;
    }
}
