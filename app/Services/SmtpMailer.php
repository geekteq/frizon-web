<?php
declare(strict_types=1);

/**
 * Minimal SMTP mailer — STARTTLS on port 587.
 * Tested with Amazon SES SMTP endpoint. No Composer required.
 */
class SmtpMailer
{
    public function __construct(
        private readonly string $host,
        private readonly int    $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $from,
        private readonly string $fromName = '',
    ) {}

    public static function fromEnv(): self
    {
        return new self(
            host:     $_ENV['SMTP_HOST']      ?? '',
            port:     (int) ($_ENV['SMTP_PORT'] ?? 587),
            username: $_ENV['SMTP_USERNAME']  ?? '',
            password: $_ENV['SMTP_PASSWORD']  ?? '',
            from:     $_ENV['SMTP_FROM']      ?? '',
            fromName: $_ENV['SMTP_FROM_NAME'] ?? '',
        );
    }

    /**
     * Send a plain-text email via STARTTLS SMTP.
     *
     * @throws RuntimeException on connection, auth, or delivery failure
     */
    public function send(string $to, string $replyTo, string $subject, string $body): void
    {
        $sock = stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errno, $errstr, 10
        );
        if (!$sock) {
            throw new RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
        }
        stream_set_timeout($sock, 10);

        $this->expect($sock, '220');

        $this->cmd($sock, 'EHLO ' . gethostname());
        $this->expect($sock, '250');

        $this->cmd($sock, 'STARTTLS');
        $this->expect($sock, '220');

        if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('SMTP STARTTLS handshake failed');
        }

        $this->cmd($sock, 'EHLO ' . gethostname());
        $this->expect($sock, '250');

        $this->cmd($sock, 'AUTH LOGIN');
        $this->expect($sock, '334');
        $this->cmd($sock, base64_encode($this->username));
        $this->expect($sock, '334');
        $this->cmd($sock, base64_encode($this->password));
        $this->expect($sock, '235');

        $this->cmd($sock, "MAIL FROM:<{$this->from}>");
        $this->expect($sock, '250');

        $this->cmd($sock, "RCPT TO:<{$to}>");
        $this->expect($sock, '250');

        $this->cmd($sock, 'DATA');
        $this->expect($sock, '354');

        $fromHeader = $this->fromName !== ''
            ? '=?UTF-8?B?' . base64_encode($this->fromName) . "?= <{$this->from}>"
            : $this->from;

        $headers = "Date: " . date('r') . "\r\n"
                 . "Message-ID: <" . uniqid('', true) . '@' . gethostname() . ">\r\n"
                 . "From: {$fromHeader}\r\n"
                 . "To: {$to}\r\n"
                 . "Reply-To: {$replyTo}\r\n"
                 . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
                 . "MIME-Version: 1.0\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: base64\r\n"
                 . "\r\n";

        fwrite($sock, $headers . chunk_split(base64_encode($body)) . "\r\n.\r\n");
        $this->expect($sock, '250');

        $this->cmd($sock, 'QUIT');
        fclose($sock);
    }

    private function cmd($sock, string $cmd): void
    {
        fwrite($sock, $cmd . "\r\n");
    }

    /** Reads multi-line SMTP response and asserts the expected status code. */
    private function expect($sock, string $code): string
    {
        $response = '';
        while ($line = fgets($sock, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') { // "250 " ends, "250-" continues
                break;
            }
        }
        if (substr($response, 0, 3) !== $code) {
            throw new RuntimeException("SMTP expected {$code}, got: " . trim($response));
        }
        return $response;
    }
}
