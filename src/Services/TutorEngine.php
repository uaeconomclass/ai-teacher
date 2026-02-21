<?php
declare(strict_types=1);

namespace App\Services;

use App\Support\Config;
use RuntimeException;

final class TutorEngine
{
    private DialogueService $dialogueService;
    private OpenAIService $openAIService;
    private bool $allowServerTts;

    public function __construct(?DialogueService $dialogueService = null, ?OpenAIService $openAIService = null)
    {
        $this->dialogueService = $dialogueService ?? new DialogueService();
        $this->openAIService = $openAIService ?? new OpenAIService();
        $this->allowServerTts = Config::getBool('FEATURE_SERVER_TTS', false);
    }

    /**
     * @return array{
     *   dialogue_id: int,
     *   mode: string,
     *   level: string,
     *   topic: string,
     *   grammar_focus: string,
     *   reply: string,
     *   audio_url: ?string,
     *   feedback?: array{tip: string}
     * }
     */
    public function handleTurn(
        int $requestedUserId,
        int $dialogueId,
        string $mode,
        string $level,
        string $topic,
        string $grammarFocus,
        string $message,
        string $ttsMode
    ): array {
        $normalizedMessage = trim($message);
        if ($normalizedMessage === '') {
            throw new RuntimeException('Message is required');
        }

        $normalizedMode = $mode === 'lesson' ? 'lesson' : 'conversation';
        $normalizedLevel = strtoupper(trim($level)) !== '' ? strtoupper(trim($level)) : 'A1';
        $normalizedTopic = trim($topic) !== '' ? trim($topic) : 'introductions';
        $normalizedGrammarFocus = trim($grammarFocus);
        $normalizedTtsMode = strtolower(trim($ttsMode));

        $userId = $this->dialogueService->resolveUserId($requestedUserId);
        if ($dialogueId > 0 && !$this->dialogueService->dialogueBelongsToUser($dialogueId, $userId)) {
            throw new RuntimeException('Dialogue not found for user');
        }

        if ($dialogueId <= 0) {
            $dialogueId = $this->dialogueService->createDialogue($userId, $normalizedLevel, $normalizedTopic);
        }

        $this->dialogueService->addMessage($dialogueId, 'user', $normalizedMessage);
        $history = $this->dialogueService->recentMessages($dialogueId, 12);
        $ai = $this->openAIService->tutorReply(
            $normalizedLevel,
            $normalizedTopic,
            $history,
            $normalizedGrammarFocus !== '' ? $normalizedGrammarFocus : null,
            $normalizedMode
        );

        $reply = trim((string) ($ai['reply'] ?? ''));
        $tip = trim((string) ($ai['tip'] ?? ''));
        if ($reply === '') {
            $reply = 'Let us continue. Tell me more.';
        }

        $audioUrl = null;
        if ($this->allowServerTts && $normalizedTtsMode === 'server') {
            try {
                $tts = $this->openAIService->synthesizeSpeech($reply);
                $audioUrl = $this->storeAudioBytes($tts['bytes'], '.mp3');
            } catch (\Throwable $e) {
                $audioUrl = null;
            }
        }

        $this->dialogueService->addMessage($dialogueId, 'assistant', $reply, $audioUrl);

        $response = [
            'dialogue_id' => $dialogueId,
            'mode' => $normalizedMode,
            'level' => $normalizedLevel,
            'topic' => $normalizedTopic,
            'grammar_focus' => $normalizedGrammarFocus,
            'reply' => $reply,
            'audio_url' => $audioUrl,
        ];

        if ($tip !== '') {
            $response['feedback'] = [
                'tip' => $tip,
            ];
        }

        return $response;
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
