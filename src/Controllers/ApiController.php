<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\GrammarTopicService;
use App\Services\OpenAIService;
use App\Services\SessionService;
use App\Services\TopicService;
use App\Services\TutorEngine;
use App\Support\Response;
use RuntimeException;
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

    public function promptPreview(): void
    {
        $level = strtoupper(trim((string) ($_GET['level'] ?? 'A1')));
        $topic = trim((string) ($_GET['topic'] ?? 'introductions'));
        $grammarFocus = trim((string) ($_GET['grammar_focus'] ?? ''));
        $mode = strtolower(trim((string) ($_GET['mode'] ?? 'conversation')));

        try {
            $openAIService = new OpenAIService();
            $prompt = $openAIService->buildTutorSystemPrompt(
                $level !== '' ? $level : 'A1',
                $topic !== '' ? $topic : 'introductions',
                $grammarFocus !== '' ? $grammarFocus : null,
                $mode === 'lesson' ? 'lesson' : 'conversation'
            );

            Response::json([
                'data' => [
                    'prompt' => $prompt,
                ],
            ]);
        } catch (Throwable $e) {
            Response::json([
                'error' => 'Prompt preview unavailable',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function startSession(): void
    {
        $data = $this->jsonBody();

        $level = strtoupper(trim((string) ($data['level'] ?? 'A1')));
        $topic = trim((string) ($data['topic'] ?? 'introductions'));
        $mode = strtolower(trim((string) ($data['mode'] ?? 'conversation')));
        $grammarFocus = trim((string) ($data['grammar_focus'] ?? ''));
        $requestedUserId = (int) ($data['user_id'] ?? 0);

        try {
            $sessionService = new SessionService();
            $session = $sessionService->applyFilters($requestedUserId, $mode, $level, $topic, $grammarFocus);
            Response::json([
                'data' => $session,
            ]);
        } catch (Throwable $e) {
            Response::json([
                'error' => 'Session start failed',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function applySessionFilters(): void
    {
        $this->startSession();
    }

    public function chat(): void
    {
        $data = $this->jsonBody();

        $message = trim((string) ($data['message'] ?? ''));
        $level = trim((string) ($data['level'] ?? 'A1'));
        $topic = trim((string) ($data['topic'] ?? 'introductions'));
        $grammarFocus = trim((string) ($data['grammar_focus'] ?? ''));
        $mode = strtolower(trim((string) ($data['mode'] ?? 'conversation')));
        $ttsMode = strtolower(trim((string) ($data['tts_mode'] ?? 'browser')));
        $dialogueId = (int) ($data['dialogue_id'] ?? 0);
        $requestedUserId = (int) ($data['user_id'] ?? 0);

        if ($message === '') {
            Response::json(['error' => 'Message is required'], 422);
            return;
        }

        try {
            $tutorEngine = new TutorEngine();
            $result = $tutorEngine->handleTurn(
                $requestedUserId,
                $dialogueId,
                $mode,
                $level,
                $topic,
                $grammarFocus,
                $message,
                $ttsMode
            );

            Response::json([
                'data' => $result,
            ]);
        } catch (RuntimeException $e) {
            Response::json([
                'error' => $e->getMessage(),
            ], $this->runtimeStatus($e));
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
        $data = $this->jsonBody();
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

    /**
     * @return array<string, mixed>
     */
    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '[]', true);

        return is_array($decoded) ? $decoded : [];
    }

    private function runtimeStatus(RuntimeException $e): int
    {
        return match ($e->getMessage()) {
            'Message is required' => 422,
            'Dialogue not found for user' => 404,
            default => 400,
        };
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
