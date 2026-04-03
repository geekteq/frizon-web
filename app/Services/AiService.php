<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Provider interface
// ---------------------------------------------------------------------------

interface AiProviderInterface
{
    public function generateDraft(array $context): string;
    public function generatePlaceSeo(array $place, array $visits): array;
    public function generateShopDescription(array $context): string;
    public function generateShopSeo(array $product): array;
    public function translateToSwedish(string $text): string;
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
            . 'Basera texten på de anteckningar och betyg som ges. '
            . 'Skriv ren text utan markdown, inga **, ##, - eller andra formateringstecken. Bara löpande text med styckebrytningar.'
            . $this->sw();

        $userPrompt = $this->buildUserPrompt($context);

        $payload = [
            'model'      => $this->model,
            'max_tokens' => $this->maxTokens,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        return $this->callClaude($payload);
    }

    public function generatePlaceSeo(array $place, array $visits): array
    {
        $placeTypes = [
            'stellplatz'   => 'ställplats', 'camping'   => 'camping',
            'wild_camping' => 'fricamping', 'fika'      => 'fika',
            'lunch'        => 'lunch',      'dinner'    => 'middag',
            'breakfast'    => 'frukost',    'sight'     => 'sevärdhet',
            'shopping'     => 'shopping',
        ];
        $typeLabel = $placeTypes[$place['place_type']] ?? $place['place_type'];

        $visitInfo = '';
        foreach ($visits as $v) {
            if (!empty($v['approved_public_text'])) {
                $visitInfo .= 'Besöksbeskrivning: ' . $v['approved_public_text'] . "\n";
            }
            if (!empty($v['suitable_for'])) {
                $visitInfo .= 'Passar för: ' . $v['suitable_for'] . "\n";
            }
            if (!empty($v['tips_notes'])) {
                $visitInfo .= 'Tips: ' . $v['tips_notes'] . "\n";
            }
            if (!empty($v['price_level'])) {
                $visitInfo .= 'Prisnivå: ' . $v['price_level'] . "\n";
            }
        }

        $userPrompt = "Plats: {$place['name']}\n"
            . "Typ: {$typeLabel}\n"
            . ($place['country_code'] ? "Land: " . strtoupper($place['country_code']) . "\n" : '')
            . ($place['default_public_text'] ? "Beskrivning: {$place['default_public_text']}\n" : '')
            . ($visitInfo ? "\nBesöksinformation:\n{$visitInfo}" : '')
            . "\nGenerera:\n"
            . "1. meta_description: SEO-beskrivning max 155 tecken, på svenska, lockar besökare.\n"
            . "2. faq: Array med 3-5 frågor och svar om platsen ur ett husbilsperspektiv.\n"
            . "   Typiska frågor: Passar den för husbilar? Vad finns i närheten? Kostar det något? Är det lugnt?\n\n"
            . "Svara ENBART med giltig JSON i exakt detta format, inget annat:\n"
            . '{"meta_description":"...","faq":[{"q":"...","a":"..."},{"q":"...","a":"..."}]}';

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 800,
            'system'     => 'Du är en SEO-expert som skriver på svenska för en husbilsreseblogg. Svara ALLTID med giltig JSON och inget annat.' . $this->sw(),
            'messages'   => [['role' => 'user', 'content' => $userPrompt]],
        ];

        $text   = $this->callClaude($payload);
        $result = json_decode($text, true);

        if (!isset($result['meta_description'], $result['faq']) || !is_array($result['faq'])) {
            throw new RuntimeException('Ogiltigt JSON-svar från Claude vid SEO-generering.');
        }

        return [
            'meta_description' => mb_substr((string) $result['meta_description'], 0, 155),
            'faq_content'      => json_encode($result['faq'], JSON_UNESCAPED_UNICODE) ?: '[]',
        ];
    }

    /** Appended to every Swedish system prompt. */
    private function sw(): string
    {
        return ' Skriv alltid "ställplats" — använd ALDRIG "Stellplatz", "Stellplats" eller "stellplats".';
    }

    private function callClaude(array $payload): string
    {
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
            CURLOPT_TIMEOUT => 30,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException('cURL-fel vid anrop till Claude API: ' . $curlError);
        }
        if ($httpCode !== 200) {
            $body = json_decode($response, true);
            throw new RuntimeException('Claude API-fel: ' . ($body['error']['message'] ?? 'HTTP ' . $httpCode));
        }

        $data = json_decode($response, true);
        $text = trim($data['content'][0]['text'] ?? '');
        if ($text === '') {
            throw new RuntimeException('Claude returnerade ett tomt svar.');
        }
        return $text;
    }

    public function generateShopDescription(array $context): string
    {
        $title       = $context['title'] ?? 'Produkten';
        $amazonDesc  = $context['amazon_description'] ?? '';
        $currentText = $context['current_text'] ?? '';

        $prompt = "Produkt: {$title}\n"
            . ($amazonDesc ? "Amazon-beskrivning: {$amazonDesc}\n" : '')
            . ($currentText ? "Nuvarande text: {$currentText}\n" : '')
            . "\nSkriv en personlig och säljande produktbeskrivning på svenska (2-3 stycken) som förklarar "
            . "varför vi gillar den, hur vi använder den på resan med vår husbil Frizze, och varför vi rekommenderar den. "
            . "Skriv ren löpande text utan markdown, inga **, ## eller liknande.";

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 600,
            'system'     => 'Du är en entusiastisk husbilsresenär som skriver produktrekommendationer på svenska. Skriv personligt, varmt och övertygande.' . $this->sw(),
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ];

        return $this->callClaude($payload);
    }

    public function generateShopSeo(array $product): array
    {
        $title    = $product['title'] ?? '';
        $desc     = $product['amazon_description'] ?? $product['our_description'] ?? '';
        $category = $product['category'] ?? '';

        $prompt = "Produkt: {$title}\n"
            . ($category ? "Kategori: {$category}\n" : '')
            . ($desc ? "Beskrivning: " . mb_substr($desc, 0, 500) . "\n" : '')
            . "\nGenerera:\n"
            . "1. seo_title: En säljande sidtitel max 60 tecken på svenska, inkludera produktnamnet.\n"
            . "2. seo_description: En lockande meta-beskrivning max 155 tecken på svenska som beskriver produkten och nämner att det är en rekommendation från Frizon.\n\n"
            . "Svara ENBART med giltig JSON:\n"
            . '{"seo_title":"...","seo_description":"..."}';

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 300,
            'system'     => 'Du är en SEO-expert som skriver säljande produkttitlar och beskrivningar på svenska. Svara ALLTID med giltig JSON och inget annat.' . $this->sw(),
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ];

        $text   = $this->callClaude($payload);
        $result = json_decode($text, true);

        if (!isset($result['seo_title'], $result['seo_description'])) {
            throw new RuntimeException('Ogiltigt JSON-svar från Claude vid shop SEO-generering.');
        }

        return [
            'seo_title'       => mb_substr((string) $result['seo_title'], 0, 60),
            'seo_description' => mb_substr((string) $result['seo_description'], 0, 155),
        ];
    }

    public function translateToSwedish(string $text): string
    {
        if (trim($text) === '') {
            return $text;
        }

        $payload = [
            'model'      => $this->model,
            'max_tokens' => 500,
            'system'     => 'Du är en professionell översättare. Översätt texten till korrekt, naturlig svenska. Svara ENBART med den översatta texten, inget annat.' . $this->sw(),
            'messages'   => [['role' => 'user', 'content' => $text]],
        ];

        return $this->callClaude($payload);
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

    public function generatePlaceSeo(array $place, array $visits): array
    {
        $placeTypes = [
            'stellplatz'   => 'ställplats', 'camping'   => 'camping',
            'wild_camping' => 'fricamping', 'fika'      => 'fika',
            'lunch'        => 'lunch',      'dinner'    => 'middag',
            'breakfast'    => 'frukost',    'sight'     => 'sevärdhet',
            'shopping'     => 'shopping',
        ];
        $typeLabel = $placeTypes[$place['place_type']] ?? $place['place_type'];
        $country   = $place['country_code'] ? ' i ' . strtoupper($place['country_code']) : '';

        $meta = "{$place['name']} — en {$typeLabel}{$country}. Recenserad av Mattias och Ulrica på Frizon of Sweden ur ett husbilsperspektiv.";

        $faq = [
            [
                'q' => 'Passar ' . $place['name'] . ' för husbilar?',
                'a' => 'Ja, vi besökte platsen med vår Adria Twin och upplevde den som passande för husbilar.',
            ],
            [
                'q' => 'Vad finns att göra i närheten?',
                'a' => 'Se platssidan för mer information om aktiviteter och sevärdheter i närheten.',
            ],
            [
                'q' => 'Hur tar man sig dit?',
                'a' => 'Koordinater och karta finns på platssidan. Vägarna är framkomliga med husbil.',
            ],
        ];

        return [
            'meta_description' => mb_substr($meta, 0, 155),
            'faq_content'      => json_encode($faq, JSON_UNESCAPED_UNICODE),
        ];
    }

    public function generateShopDescription(array $context): string
    {
        $title = $context['title'] ?? 'Produkten';
        return "{$title} är en produkt vi verkligen gillar och använder under våra resor med Frizze.\n\n"
            . "Den har visat sig vara ett praktiskt inköp som vi gärna rekommenderar till andra husbilsresenärer. "
            . "Kvaliteten är bra och den är enkel att använda även när man befinner sig på resande fot.";
    }

    public function generateShopSeo(array $product): array
    {
        $title = $product['title'] ?? 'Produkt';
        $desc  = $product['amazon_description'] ?? $product['our_description'] ?? '';
        return [
            'seo_title'       => mb_substr($title . ' — Frizon rekommenderar', 0, 60),
            'seo_description' => mb_substr($desc ?: "Vi rekommenderar {$title} för husbilsresor.", 0, 155),
        ];
    }

    public function translateToSwedish(string $text): string
    {
        // Fake provider: return unchanged (assume already Swedish in dev)
        return $text;
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

    public function generatePlaceSeo(array $place, array $visits): array
    {
        return $this->provider->generatePlaceSeo($place, $visits);
    }

    public function generateShopDescription(array $context): string
    {
        return $this->provider->generateShopDescription($context);
    }

    public function generateShopSeo(array $product): array
    {
        return $this->provider->generateShopSeo($product);
    }

    public function translateToSwedish(string $text): string
    {
        return $this->provider->translateToSwedish($text);
    }
}
