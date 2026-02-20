<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use PDO;

final class GrammarTopicService
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
                'SELECT g.id, l.code AS level, g.slug, g.title
                 FROM grammar_topics g
                 INNER JOIN levels l ON l.id = g.level_id
                 WHERE l.code = :level
                 ORDER BY g.position ASC, g.id ASC'
            );
            $stmt->execute(['level' => strtoupper($levelCode)]);
            return $stmt->fetchAll() ?: [];
        }

        $stmt = $this->pdo->query(
            'SELECT g.id, l.code AS level, g.slug, g.title
             FROM grammar_topics g
             INNER JOIN levels l ON l.id = g.level_id
             ORDER BY l.id ASC, g.position ASC, g.id ASC'
        );

        return $stmt->fetchAll() ?: [];
    }
}
