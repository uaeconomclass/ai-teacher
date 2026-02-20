<?php
declare(strict_types=1);

namespace App\Database\Migrations;

final class CreateDialogueMessagesTable implements MigrationInterface
{
    public function name(): string
    {
        return 'create_dialogue_messages_table';
    }

    public function up(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS dialogue_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dialogue_id BIGINT UNSIGNED NOT NULL,
  sender ENUM('user','assistant') NOT NULL,
  text TEXT NOT NULL,
  audio_url VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_messages_dialogue FOREIGN KEY (dialogue_id) REFERENCES dialogues(id) ON DELETE CASCADE
) ENGINE=InnoDB;
SQL;
    }
}
