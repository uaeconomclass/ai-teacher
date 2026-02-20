<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class OpenAIService
{
    private string $apiKey;
    private string $model;
    private string $caBundle;

    public function __construct()
    {
        $this->apiKey = trim((string) ($_ENV['OPENAI_API_KEY'] ?? ''));
        $this->model = trim((string) ($_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini'));
        $configuredCa = trim((string) ($_ENV['OPENAI_CA_BUNDLE'] ?? 'certs/cacert.pem'));
        $this->caBundle = $this->resolveCaBundlePath($configuredCa);
    }

    /**
     * @param array<int, array{role: string, content: string}> $history
     * @return array{reply: string, tip: string}
     */
    public function tutorReply(string $level, string $topic, array $history): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is missing');
        }

        $systemPrompt = <<<TEXT
You are an English speaking tutor.
Level: {$level}. Topic: {$topic}.
Rules:
- Keep reply concise (1-3 sentences).
- Continue conversation naturally.
- Correct only one high-impact mistake.
- Return strict JSON object with keys: reply, tip.
TEXT;

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history
        );

        $payload = [
            'model' => $this->model,
            'temperature' => 0.7,
            'messages' => $messages,
            'response_format' => ['type' => 'json_object'],
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        $options = [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 45,
        ];

        if ($this->caBundle !== '' && is_file($this->caBundle)) {
            $options[CURLOPT_CAINFO] = $this->caBundle;
        }

        curl_setopt_array($ch, $options);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $curlError !== '') {
            throw new RuntimeException('OpenAI request failed: ' . ($curlError !== '' ? $curlError : 'unknown cURL error'));
        }

        $data = json_decode($raw, true);
        if ($httpCode >= 400) {
            throw new RuntimeException('OpenAI returned HTTP ' . $httpCode);
        }

        $content = (string) ($data['choices'][0]['message']['content'] ?? '');
        $json = json_decode($content, true);

        if (is_array($json) && isset($json['reply'])) {
            return [
                'reply' => trim((string) $json['reply']),
                'tip' => trim((string) ($json['tip'] ?? '')),
            ];
        }

        return [
            'reply' => trim($content) !== '' ? trim($content) : 'Let us continue. Tell me more.',
            'tip' => 'Use one more detail in your next sentence.',
        ];
    }

    private function resolveCaBundlePath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z]:\\\\/', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        return dirname(__DIR__, 2) . '/' . ltrim(str_replace('\\', '/', $path), '/');
    }
}
