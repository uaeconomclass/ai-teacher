<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use PDO;

final class TopicService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /**
     * @return array<int, array{id:int, level:string, slug:string, title:string}>
     */
    public function listByLevel(?string $levelCode = null): array
    {
        if ($levelCode !== null && $levelCode !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT t.id, l.code AS level, t.slug, t.title
                 FROM topics t
                 INNER JOIN levels l ON l.id = t.level_id
                 WHERE l.code = :level
                 ORDER BY t.position ASC, t.id ASC'
            );
            $stmt->execute(['level' => strtoupper($levelCode)]);
            return $stmt->fetchAll() ?: [];
        }

        $stmt = $this->pdo->query(
            'SELECT t.id, l.code AS level, t.slug, t.title
             FROM topics t
             INNER JOIN levels l ON l.id = t.level_id
             ORDER BY l.id ASC, t.position ASC, t.id ASC'
        );

        return $stmt->fetchAll() ?: [];
    }
}
