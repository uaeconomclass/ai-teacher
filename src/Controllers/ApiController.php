<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\DialogueService;
use App\Services\GrammarTopicService;
use App\Services\OpenAIService;
use App\Services\TopicService;
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
        $level = trim((string) ($_GET['level'] ?? ''));

        try {
            $topicService = new TopicService();
            $items = $topicService->listByLevel($level !== '' ? $level : null);

            Response::json([
                'data' => $items,
            ]);
        } catch (Throwable $e) {
            Response::json([
                'error' => 'Topics unavailable',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function grammarTopics(): void
    {
        $level = trim((string) ($_GET['level'] ?? ''));

        try {
            $grammarService = new GrammarTopicService();
            $items = $grammarService->listByLevel($level !== '' ? $level : null);

            Response::json([
                'data' => $items,
            ]);
        } catch (Throwable $e) {
            Response::json([
                'error' => 'Grammar topics unavailable',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function startSession(): void
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '[]', true);

        $level = strtoupper(trim((string) ($data['level'] ?? 'A1')));
        $topic = trim((string) ($data['topic'] ?? 'a1-introductions'));
        $requestedUserId = (int) ($data['user_id'] ?? 0);

        try {
            $dialogueService = new DialogueService();
            $userId = $dialogueService->resolveUserId($requestedUserId);
            $dialogueId = $dialogueService->createDialogue($userId, $level, $topic);

            Response::json([
                'data' => [
                    'dialogue_id' => $dialogueId,
                    'level' => $level,
                    'topic' => $topic,
                ],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'error' => 'Session start failed',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function chat(): void
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '[]', true);

        $message = trim((string) ($data['message'] ?? ''));
        $level = trim((string) ($data['level'] ?? 'A1'));
        $topic = trim((string) ($data['topic'] ?? 'introductions'));
        $grammarFocus = trim((string) ($data['grammar_focus'] ?? ''));
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
            $ai = $openAIService->tutorReply($level, $topic, $history, $grammarFocus !== '' ? $grammarFocus : null);
            $reply = trim((string) ($ai['reply'] ?? ''));
            $tip = trim((string) ($ai['tip'] ?? ''));
            $audioUrl = null;

            if ($reply === '') {
                $reply = 'Let us continue. Tell me more.';
            }

            try {
                $tts = $openAIService->synthesizeSpeech($reply);
                $audioUrl = $this->storeAudioBytes($tts['bytes'], '.mp3');
            } catch (Throwable $e) {
                $audioUrl = null;
            }

            $dialogueService->addMessage($dialogueId, 'assistant', $reply, $audioUrl);

            Response::json([
                'data' => [
                    'dialogue_id' => $dialogueId,
                    'reply' => $reply,
                    'audio_url' => $audioUrl,
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

    public function speechToText(): void
    {
        if (!isset($_FILES['audio']) || !is_array($_FILES['audio'])) {
            Response::json(['error' => 'Audio file is required'], 422);
            return;
        }

        $file = $_FILES['audio'];
        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $mimeType = (string) ($file['type'] ?? 'audio/webm');
        $originalName = (string) ($file['name'] ?? 'speech.webm');

        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            Response::json(['error' => 'Invalid uploaded audio'], 422);
            return;
        }

        try {
            $openAIService = new OpenAIService();
            $text = $openAIService->transcribeAudio($tmpPath, $mimeType, $originalName);

            Response::json([
                'data' => [
                    'text' => $text,
                ],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'error' => 'Speech-to-text unavailable',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function textToSpeech(): void
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '[]', true);
        $text = trim((string) ($data['text'] ?? ''));

        if ($text === '') {
            Response::json(['error' => 'Text is required'], 422);
            return;
        }

        try {
            $openAIService = new OpenAIService();
            $tts = $openAIService->synthesizeSpeech($text);
            $audioUrl = $this->storeAudioBytes($tts['bytes'], '.mp3');

            Response::json([
                'data' => [
                    'audio_url' => $audioUrl,
                ],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'error' => 'Text-to-speech unavailable',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    private function storeAudioBytes(string $bytes, string $extension): string
    {
        $dir = dirname(__DIR__, 2) . '/public/media/tts';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . $extension;
        $path = $dir . '/' . $name;
        file_put_contents($path, $bytes);

        return '/media/tts/' . $name;
    }
}
