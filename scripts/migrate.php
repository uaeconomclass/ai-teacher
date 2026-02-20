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
echo "Migrations completed.\n";
