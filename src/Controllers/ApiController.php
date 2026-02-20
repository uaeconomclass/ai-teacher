<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\DialogueService;
use App\Services\OpenAIService;
use App\Support\Response;
use Throwable;

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
        $dialogueId = (int) ($data['dialogue_id'] ?? 0);
        $requestedUserId = (int) ($data['user_id'] ?? 0);

        if ($message === '') {
            Response::json(['error' => 'Message is required'], 422);
            return;
        }

        try {
            $dialogueService = new DialogueService();
            $openAIService = new OpenAIService();

            $userId = $dialogueService->resolveUserId($requestedUserId);
            if ($dialogueId > 0 && !$dialogueService->dialogueBelongsToUser($dialogueId, $userId)) {
                Response::json(['error' => 'Dialogue not found for user'], 404);
                return;
            }

            if ($dialogueId <= 0) {
                $dialogueId = $dialogueService->createDialogue($userId, $level, $topic);
            }

            $dialogueService->addMessage($dialogueId, 'user', $message);
            $history = $dialogueService->recentMessages($dialogueId, 12);
            $ai = $openAIService->tutorReply($level, $topic, $history);
            $reply = trim((string) ($ai['reply'] ?? ''));
            $tip = trim((string) ($ai['tip'] ?? ''));

            if ($reply === '') {
                $reply = 'Let us continue. Tell me more.';
            }

            $dialogueService->addMessage($dialogueId, 'assistant', $reply);

            Response::json([
                'data' => [
                    'dialogue_id' => $dialogueId,
                    'reply' => $reply,
                    'feedback' => [
                        'tip' => $tip,
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'error' => 'Chat service unavailable',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
