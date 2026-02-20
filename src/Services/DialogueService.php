<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use PDO;

final class DialogueService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function resolveUserId(int $requestedUserId): int
    {
        if ($requestedUserId > 0) {
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $requestedUserId]);
            $id = $stmt->fetchColumn();
            if ($id !== false) {
                return (int) $id;
            }
        }

        return $this->ensureDemoUser();
    }

    public function dialogueBelongsToUser(int $dialogueId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM dialogues WHERE id = :id AND user_id = :user_id LIMIT 1');
        $stmt->execute([
            'id' => $dialogueId,
            'user_id' => $userId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function createDialogue(int $userId, string $level, string $topic): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO dialogues (user_id, lesson_id, level_code, topic_slug) VALUES (:user_id, NULL, :level_code, :topic_slug)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'level_code' => $level,
            'topic_slug' => $topic,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function addMessage(int $dialogueId, string $sender, string $text, ?string $audioUrl = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO dialogue_messages (dialogue_id, sender, text, audio_url) VALUES (:dialogue_id, :sender, :text, :audio_url)'
        );
        $stmt->execute([
            'dialogue_id' => $dialogueId,
            'sender' => $sender,
            'text' => $text,
            'audio_url' => $audioUrl,
        ]);
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function recentMessages(int $dialogueId, int $limit = 10): array
    {
        $limit = max(1, min($limit, 40));
        $stmt = $this->pdo->prepare(
            "SELECT sender, text FROM dialogue_messages WHERE dialogue_id = :dialogue_id ORDER BY id DESC LIMIT {$limit}"
        );
        $stmt->execute(['dialogue_id' => $dialogueId]);
        $rows = $stmt->fetchAll() ?: [];
        $rows = array_reverse($rows);

        $messages = [];
        foreach ($rows as $row) {
            $sender = (string) ($row['sender'] ?? 'assistant');
            $messages[] = [
                'role' => $sender === 'user' ? 'user' : 'assistant',
                'content' => (string) ($row['text'] ?? ''),
            ];
        }

        return $messages;
    }

    private function ensureDemoUser(): int
    {
        $email = $_ENV['DEMO_USER_EMAIL'] ?? 'demo@ai-teacher.local';
        $displayName = $_ENV['DEMO_USER_NAME'] ?? 'Demo User';

        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO users (email, password_hash, role, display_name) VALUES (:email, :password_hash, :role, :display_name)'
        );
        $stmt->execute([
            'email' => $email,
            'password_hash' => password_hash('demo-password', PASSWORD_BCRYPT),
            'role' => 'student',
            'display_name' => $displayName,
        ]);

        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : 1;
    }
}
