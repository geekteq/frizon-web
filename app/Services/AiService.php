<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Provider interface
// ---------------------------------------------------------------------------

interface AiProviderInterface
{
    public function generateDraft(array $context): string;
}

// ---------------------------------------------------------------------------
// Claude (Anthropic) provider
// ---------------------------------------------------------------------------

class ClaudeAiProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $model = 'claude-sonnet-4-20250514';
    private int $maxTokens = 1000;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function generateDraft(array $context): string
    {
        $systemPrompt = 'Du är en reseskribent som skriver levande platsbeskrivningar för en husbilsreselogg. '
            . 'Skriv på svenska. Texten ska vara personlig men informativ, 2-4 stycken. '
            . 'Basera texten på de anteckningar och betyg som ges.';

        $userPrompt = $this->buildUserPrompt($context);

        $payload = [
            'model'      => $this->model,
            'max_tokens' => $this->maxTokens,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException('cURL-fel vid anrop till Claude API: ' . $curlError);
        }

        if ($httpCode !== 200) {
            $body = json_decode($response, true);
            $msg = $body['error']['message'] ?? 'HTTP ' . $httpCode;
            throw new RuntimeException('Claude API-fel: ' . $msg);
        }

        $data = json_decode($response, true);
        $text = $data['content'][0]['text'] ?? '';

        if (trim($text) === '') {
            throw new RuntimeException('Claude returnerade ett tomt svar.');
        }

        return trim($text);
    }

    private function buildUserPrompt(array $ctx): string
    {
        $lines = [];

        $lines[] = 'Plats: ' . ($ctx['place_name'] ?? 'Okänd plats');
        $lines[] = 'Typ: ' . ($ctx['place_type'] ?? 'okänd');

        if (!empty($ctx['visited_at'])) {
            $lines[] = 'Besökt: ' . $ctx['visited_at'];
        }
        if (!empty($ctx['raw_note'])) {
            $lines[] = 'Anteckning: ' . $ctx['raw_note'];
        }
        if (!empty($ctx['plus_notes'])) {
            $lines[] = 'Plussar: ' . $ctx['plus_notes'];
        }
        if (!empty($ctx['minus_notes'])) {
            $lines[] = 'Minusar: ' . $ctx['minus_notes'];
        }
        if (!empty($ctx['tips_notes'])) {
            $lines[] = 'Tips: ' . $ctx['tips_notes'];
        }
        if (!empty($ctx['suitable_for'])) {
            $lines[] = 'Passar för: ' . $ctx['suitable_for'];
        }
        if (!empty($ctx['price_level'])) {
            $priceMap = ['free' => 'Gratis', 'low' => 'Lågt (€)', 'medium' => 'Medel (€€)', 'high' => 'Högt (€€€)'];
            $lines[] = 'Prisnivå: ' . ($priceMap[$ctx['price_level']] ?? $ctx['price_level']);
        }
        if (!empty($ctx['would_return'])) {
            $returnMap = ['yes' => 'Ja', 'maybe' => 'Kanske', 'no' => 'Nej'];
            $lines[] = 'Skulle återvända: ' . ($returnMap[$ctx['would_return']] ?? $ctx['would_return']);
        }
        if (!empty($ctx['total_rating'])) {
            $lines[] = 'Totalbetyg: ' . $ctx['total_rating'] . ' av 5';
        }

        return implode("\n", $lines) . "\n\nSkriv en levande platsbeskrivning baserat på ovanstående.";
    }
}

// ---------------------------------------------------------------------------
// Fake provider — for dev/test, no API call
// ---------------------------------------------------------------------------

class FakeAiProvider implements AiProviderInterface
{
    public function generateDraft(array $context): string
    {
        $name    = $context['place_name'] ?? 'Platsen';
        $type    = $context['place_type'] ?? 'ställplats';
        $note    = $context['raw_note'] ?? '';
        $plus    = $context['plus_notes'] ?? '';
        $minus   = $context['minus_notes'] ?? '';
        $tips    = $context['tips_notes'] ?? '';
        $suitable = $context['suitable_for'] ?? '';
        $rating  = $context['total_rating'] ?? null;

        // First paragraph: place intro + raw note
        $para1 = $name . ' är en ' . $type . ' som vi besökte under vår resa med Frizze.';
        if ($note) {
            $para1 .= ' ' . rtrim($note, '.') . '.';
        }

        // Second paragraph: plus/minus/tips
        $para2 = '';
        if ($plus) {
            $para2 .= 'Det som var riktigt bra: ' . rtrim($plus, '.') . '.';
        }
        if ($minus) {
            $para2 .= ($para2 ? ' Dock var det lite si och så med ' : 'Dock var det lite si och så med ') . lcfirst(rtrim($minus, '.')) . '.';
        }
        if ($tips) {
            $para2 .= ($para2 ? ' Tips: ' : 'Tips: ') . rtrim($tips, '.') . '.';
        }

        // Third paragraph: suitable_for + rating
        $para3 = '';
        if ($suitable) {
            $para3 .= 'Platsen passar bra för ' . $suitable . '.';
        }
        if ($rating !== null && $rating !== '') {
            $para3 .= ($para3 ? ' ' : '') . 'Vi ger platsen ' . $rating . ' av 5 i totalbetyg.';
        }

        $parts = array_filter([$para1, $para2, $para3], fn($p) => trim($p) !== '');
        return implode("\n\n", $parts);
    }
}

// ---------------------------------------------------------------------------
// AiService — selects provider based on AI_PROVIDER env variable
// ---------------------------------------------------------------------------

class AiService
{
    private AiProviderInterface $provider;

    public function __construct()
    {
        $providerName = $_ENV['AI_PROVIDER'] ?? 'fake';

        if ($providerName === 'claude') {
            $apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';
            if (empty($apiKey)) {
                throw new RuntimeException('ANTHROPIC_API_KEY saknas i .env');
            }
            $this->provider = new ClaudeAiProvider($apiKey);
        } else {
            // 'fake' or any unknown value — safe fallback for dev/test
            $this->provider = new FakeAiProvider();
        }
    }

    public function generateDraft(array $context): string
    {
        return $this->provider->generateDraft($context);
    }
}
