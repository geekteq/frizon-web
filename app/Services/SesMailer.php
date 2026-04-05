<?php
declare(strict_types=1);

/**
 * Minimal AWS SES v2 email sender via cURL + SigV4.
 * No Composer or external SDK required.
 */
class SesMailer
{
    public function __construct(
        private readonly string $key,
        private readonly string $secret,
        private readonly string $region,
        private readonly string $from,
    ) {}

    public static function fromEnv(): self
    {
        return new self(
            key:    $_ENV['AWS_SES_KEY']    ?? '',
            secret: $_ENV['AWS_SES_SECRET'] ?? '',
            region: $_ENV['AWS_SES_REGION'] ?? 'eu-north-1',
            from:   $_ENV['MAIL_FROM']      ?? 'frizon@mobileminds.se',
        );
    }

    /**
     * Send a plain-text email via SES v2.
     *
     * @throws RuntimeException on HTTP or API error
     */
    public function send(string $to, string $replyTo, string $subject, string $body): void
    {
        $path    = '/v2/email/outbound-emails';
        $payload = json_encode([
            'FromEmailAddress' => $this->from,
            'Destination'      => ['ToAddresses' => [$to]],
            'ReplyToAddresses' => [$replyTo],
            'Content'          => [
                'Simple' => [
                    'Subject' => ['Data' => $subject, 'Charset' => 'UTF-8'],
                    'Body'    => ['Text' => ['Data' => $body, 'Charset' => 'UTF-8']],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $headers = $this->buildHeaders('POST', $path, $payload);

        $ch = curl_init("https://email.{$this->region}.amazonaws.com{$path}");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = (string) curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            throw new RuntimeException('SES cURL error: ' . $curlErr);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException("SES HTTP {$httpCode}: " . $response);
        }
    }

    /** @return list<string> */
    private function buildHeaders(string $method, string $path, string $payload): array
    {
        $service  = 'ses';
        $now      = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $date     = $now->format('Ymd');
        $dateTime = $now->format('Ymd\THis\Z');
        $host     = "email.{$this->region}.amazonaws.com";
        $hash     = hash('sha256', $payload);

        $canonicalHeaders = "content-type:application/json\nhost:{$host}\nx-amz-date:{$dateTime}\n";
        $signedHeaders    = 'content-type;host;x-amz-date';
        $canonicalRequest = implode("\n", [$method, $path, '', $canonicalHeaders, $signedHeaders, $hash]);

        $scope        = "{$date}/{$this->region}/{$service}/aws4_request";
        $stringToSign = implode("\n", ['AWS4-HMAC-SHA256', $dateTime, $scope, hash('sha256', $canonicalRequest)]);

        $signingKey = hash_hmac('sha256', 'aws4_request',
            hash_hmac('sha256', $service,
                hash_hmac('sha256', $this->region,
                    hash_hmac('sha256', $date, 'AWS4' . $this->secret, true),
                true),
            true),
        true);

        $signature  = hash_hmac('sha256', $stringToSign, $signingKey);
        $authHeader = "AWS4-HMAC-SHA256 Credential={$this->key}/{$scope}, "
                    . "SignedHeaders={$signedHeaders}, Signature={$signature}";

        return [
            'Content-Type: application/json',
            "Host: {$host}",
            "X-Amz-Date: {$dateTime}",
            "Authorization: {$authHeader}",
        ];
    }
}
