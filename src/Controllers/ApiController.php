<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\Response;

final class ApiController
{
    public function health(): void
    {
        Response::json([
            'status' => 'ok',
            'service' => 'ai-teacher-api',
            'timestamp' => date(DATE_ATOM),
        ]);
    }

    public function topics(): void
    {
        Response::json([
            'data' => [
                ['id' => 1, 'level' => 'A1', 'slug' => 'introductions', 'title' => 'Introductions'],
                ['id' => 2, 'level' => 'A1', 'slug' => 'daily-routine', 'title' => 'Daily Routine'],
                ['id' => 3, 'level' => 'A2', 'slug' => 'travel-basics', 'title' => 'Travel Basics'],
            ],
        ]);
    }

    public function chat(): void
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '[]', true);

        $message = trim((string) ($data['message'] ?? ''));
        $level = trim((string) ($data['level'] ?? 'A1'));
        $topic = trim((string) ($data['topic'] ?? 'introductions'));

        if ($message === '') {
            Response::json(['error' => 'Message is required'], 422);
            return;
        }

        // Temporary mock logic until OpenAI integration.
        $reply = "Great. Let's keep practicing {$topic} ({$level}). You said: {$message}";

        Response::json([
            'data' => [
                'reply' => $reply,
                'feedback' => [
                    'tip' => 'Try one longer sentence with because.',
                ],
            ],
        ]);
    }
}
