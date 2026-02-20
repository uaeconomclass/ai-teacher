<?php
declare(strict_types=1);

namespace App\Database\Migrations;

interface MigrationInterface
{
    public function name(): string;

    public function up(): string;
}
