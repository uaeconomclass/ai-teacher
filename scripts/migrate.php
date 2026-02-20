<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Database\Database;
use App\Database\Migrations\CreateDialogueMessagesTable;
use App\Database\Migrations\CreateDialoguesTable;
use App\Database\Migrations\CreateLessonsTable;
use App\Database\Migrations\CreateLevelsTable;
use App\Database\Migrations\CreateTopicsTable;
use App\Database\Migrations\CreateUserProgressTable;
use App\Database\Migrations\CreateUsersTable;
use App\Database\Migrations\MigrationInterface;

$pdo = Database::pdo();

/** @var MigrationInterface[] $migrations */
$migrations = [
    new CreateUsersTable(),
    new CreateLevelsTable(),
    new CreateTopicsTable(),
    new CreateLessonsTable(),
    new CreateDialoguesTable(),
    new CreateDialogueMessagesTable(),
    new CreateUserProgressTable(),
];

echo "Running migrations...\n";

foreach ($migrations as $migration) {
    $pdo->exec($migration->up());
    echo "[OK] {$migration->name()}\n";
}

$pdo->exec(<<<SQL
INSERT IGNORE INTO levels (id, code, title) VALUES
  (1, 'A1', 'Beginner'),
  (2, 'A2', 'Elementary'),
  (3, 'B1', 'Intermediate'),
  (4, 'B2', 'Upper Intermediate'),
  (5, 'C1', 'Advanced'),
  (6, 'C2', 'Proficient');
SQL);

echo "[OK] seed_levels\n";

$topicSeed = [
    'A1' => ['Introductions', 'Daily Routine', 'Food and Drinks', 'City Directions', 'Shopping Basics'],
    'A2' => ['Travel Plans', 'Health and Appointments', 'Work Day', 'Housing and Services', 'Past Weekend Stories'],
    'B1' => ['Career Goals', 'Relationships', 'Media Habits', 'Problem Solving', 'Culture and Lifestyle'],
    'B2' => ['Business Talks', 'Technology and Society', 'Environment Debate', 'Negotiation Skills', 'Education Systems'],
    'C1' => ['Leadership', 'Ethics and Decisions', 'Innovation', 'Intercultural Communication', 'Public Speaking'],
    'C2' => ['Policy and Society', 'Advanced Debate', 'Expert Domains', 'Rhetoric and Persuasion', 'Nuance and Tone'],
];

$topicStmt = $pdo->prepare(
    'INSERT IGNORE INTO topics (level_id, slug, title, position)
     VALUES (
       (SELECT id FROM levels WHERE code = :level_code LIMIT 1),
       :slug,
       :title,
       :position
     )'
);

foreach ($topicSeed as $levelCode => $titles) {
    $position = 1;
    foreach ($titles as $title) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        $topicStmt->execute([
            'level_code' => $levelCode,
            'slug' => strtolower($levelCode) . '-' . $slug,
            'title' => $title,
            'position' => $position,
        ]);
        $position++;
    }
}

echo "[OK] seed_topics\n";
echo "Migrations completed.\n";
