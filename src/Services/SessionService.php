<?php
declare(strict_types=1);

namespace App\Services;

final class SessionService
{
    private DialogueService $dialogueService;

    public function __construct(?DialogueService $dialogueService = null)
    {
        $this->dialogueService = $dialogueService ?? new DialogueService();
    }

    /**
     * @return array{dialogue_id: int, mode: string, level: string, topic: string, grammar_focus: string}
     */
    public function applyFilters(
        int $requestedUserId,
        string $mode,
        string $level,
        string $topic,
        string $grammarFocus = ''
    ): array {
        $normalizedMode = $mode === 'lesson' ? 'lesson' : 'conversation';
        $normalizedLevel = strtoupper(trim($level)) !== '' ? strtoupper(trim($level)) : 'A1';
        $normalizedTopic = trim($topic) !== '' ? trim($topic) : 'introductions';
        $normalizedGrammarFocus = trim($grammarFocus);

        $userId = $this->dialogueService->resolveUserId($requestedUserId);
        $dialogueId = $this->dialogueService->createDialogue($userId, $normalizedLevel, $normalizedTopic);

        return [
            'dialogue_id' => $dialogueId,
            'mode' => $normalizedMode,
            'level' => $normalizedLevel,
            'topic' => $normalizedTopic,
            'grammar_focus' => $normalizedGrammarFocus,
        ];
    }
}
