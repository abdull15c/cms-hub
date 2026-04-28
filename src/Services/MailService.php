<?php
namespace Src\Services;

use Src\Core\Env;

class MailService {
    private function viewBasePath(): string {
        return defined('VIEW_PATH') ? VIEW_PATH : dirname(__DIR__, 2) . '/views';
    }
    
    // Send using HTML Template
    public function sendTemplate($to, $subject, $templateName, $data = []) {
        $basePath = $this->viewBasePath();
        $layoutPath = $basePath . '/emails/layout.php';
        $templatePath = $basePath . '/emails/' . $templateName . '.php';
        if (!is_file($layoutPath) || !is_file($templatePath)) {
            Logger::error('Mail template is missing', ['template' => $templateName]);
            return false;
        }

        $layout = (string) file_get_contents($layoutPath);
        $content = (string) file_get_contents($templatePath);
        
        // Inject Data into Content
        foreach ($data as $key => $val) {
            if (!is_scalar($val) && $val !== null) {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE);
            }
            $content = str_replace('{{'.$key.'}}', (string) $val, $content);
        }
        
        // Inject Content into Layout
        $body = str_replace('{{content}}', $content, $layout);
        $body = str_replace('{{base_url}}', defined('BASE_URL') ? BASE_URL : (string) Env::get('APP_URL', 'http://localhost'), $body);
        
        return $this->send($to, $subject, $body);
    }

    public function send($to, $subject, $body) {
        if (!filter_var((string) $to, FILTER_VALIDATE_EMAIL)) {
            Logger::warning('Mail send skipped because recipient is invalid', ['to' => (string) $to]);
            return false;
        }

        $host = (string) Env::get('SMTP_HOST', '');
        if ($host !== '') {
            return $this->sendSmtp((string) $to, (string) $subject, (string) $body);
        }
        return $this->sendNative((string) $to, (string) $subject, (string) $body);
    }

    private function sendNative(string $to, string $subject, string $body): bool {
        $from = $this->fromAddress();
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->formatAddress($from, $this->fromName()),
            'Reply-To: <' . $from . '>',
        ];

        $sent = mail(
            $to,
            $this->encodeHeader($subject),
            $body,
            implode("\r\n", $headers) . "\r\n"
        );

        if (!$sent) {
            Logger::error('Native mail() delivery failed', ['to' => $to]);
        }

        return $sent;
    }

    private function sendSmtp(string $to, string $subject, string $body): bool {
        $host = (string) Env::get('SMTP_HOST', '');
        $port = (int) Env::get('SMTP_PORT', 587);
        $user = trim((string) Env::get('SMTP_USER', ''));
        $pass = (string) Env::get('SMTP_PASS', '');
        $from = $this->fromAddress();
        $encryption = strtolower(trim((string) Env::get('SMTP_ENCRYPTION', $port === 465 ? 'ssl' : 'tls')));
        $timeout = (int) Env::get('SMTP_TIMEOUT', 15);
        $verifyPeer = $this->envBool('SMTP_VERIFY_PEER', true);
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            $encryption = 'tls';
        }
        if ($timeout <= 0) {
            $timeout = 15;
        }

        try {
            $socket = $this->openSocket($host, $port, $timeout, $encryption, $verifyPeer);
            if (!is_resource($socket)) {
                Logger::error('SMTP connection failed', ['host' => $host, 'port' => $port]);
                return false;
            }

            $helloHost = $this->helloHost();
            $this->expectResponse($socket, [220], 'connect');
            $this->sendEhlo($socket, $helloHost);

            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220], 'STARTTLS');
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('Unable to enable STARTTLS.');
                }
                $this->sendEhlo($socket, $helloHost);
            }

            if ($user !== '' || $pass !== '') {
                if ($user === '' || $pass === '') {
                    throw new \RuntimeException('SMTP credentials are incomplete.');
                }
                $this->command($socket, 'AUTH LOGIN', [334], 'AUTH LOGIN');
                $this->command($socket, base64_encode($user), [334], 'SMTP username');
                $this->command($socket, base64_encode($pass), [235], 'SMTP password');
            }

            $this->command($socket, 'MAIL FROM: <' . $from . '>', [250], 'MAIL FROM');
            $this->command($socket, 'RCPT TO: <' . $to . '>', [250, 251], 'RCPT TO');
            $this->command($socket, 'DATA', [354], 'DATA');
            $this->writeData($socket, $this->buildMimeMessage($to, $subject, $body));
            $this->expectResponse($socket, [250], 'message body');
            $this->command($socket, 'QUIT', [221], 'QUIT');
            fclose($socket);
            return true;
        } catch (\Throwable $e) {
            if (isset($socket) && is_resource($socket)) {
                @fwrite($socket, "QUIT\r\n");
                fclose($socket);
            }
            Logger::error('SMTP send failed', [
                'to' => $to,
                'host' => $host,
                'port' => $port,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function openSocket(string $host, int $port, int $timeout, string $encryption, bool $verifyPeer) {
        $transport = $encryption === 'ssl' ? 'ssl' : 'tcp';
        $contextOptions = [
            'ssl' => [
                'verify_peer' => $verifyPeer,
                'verify_peer_name' => $verifyPeer,
                'allow_self_signed' => !$verifyPeer,
            ],
        ];
        $caPath = ROOT_PATH . '/config/cacert.pem';
        if ($verifyPeer && is_file($caPath)) {
            $contextOptions['ssl']['cafile'] = $caPath;
        }
        $context = stream_context_create($contextOptions);

        $socket = @stream_socket_client(
            $transport . '://' . $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!is_resource($socket)) {
            Logger::error('SMTP socket open failed', [
                'host' => $host,
                'port' => $port,
                'errno' => $errno ?? null,
                'error' => $errstr ?? 'unknown',
            ]);
            return false;
        }

        stream_set_timeout($socket, $timeout);
        return $socket;
    }

    private function command($socket, string $command, array $expectedCodes, string $stage): string {
        fwrite($socket, $command . "\r\n");
        return $this->expectResponse($socket, $expectedCodes, $stage);
    }

    private function sendEhlo($socket, string $helloHost): void {
        try {
            $this->command($socket, 'EHLO ' . $helloHost, [250], 'EHLO');
        } catch (\RuntimeException $e) {
            $this->command($socket, 'HELO ' . $helloHost, [250], 'HELO');
        }
    }

    private function expectResponse($socket, array $expectedCodes, string $stage): string {
        $response = $this->read($socket);
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new \RuntimeException('SMTP ' . $stage . ' failed: ' . trim($response));
        }
        return $response;
    }

    private function writeData($socket, string $payload): void {
        fwrite($socket, $payload . "\r\n.\r\n");
    }

    private function read($socket): string {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    private function buildMimeMessage(string $to, string $subject, string $body): string {
        $headers = [
            'Date: ' . date('r'),
            'From: ' . $this->formatAddress($this->fromAddress(), $this->fromName()),
            'To: <' . $to . '>',
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        $normalizedBody = preg_replace("/\r\n|\r|\n/", "\r\n", $body) ?? $body;
        $normalizedBody = preg_replace('/(^|\r\n)\./', '$1..', $normalizedBody) ?? $normalizedBody;

        return implode("\r\n", $headers) . "\r\n\r\n" . $normalizedBody;
    }

    private function fromAddress(): string {
        $configured = trim((string) Env::get('MAIL_FROM_ADDRESS', ''));
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_EMAIL)) {
            return $configured;
        }

        $smtpUser = trim((string) Env::get('SMTP_USER', ''));
        if ($smtpUser !== '' && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
            return $smtpUser;
        }

        $host = strtolower($this->helloHost());
        if ($host === '' || strpos($host, '.') === false) {
            $host = 'example.test';
        }
        return 'noreply@' . preg_replace('/[^a-z0-9.-]/', '', $host);
    }

    private function fromName(): string {
        $name = trim((string) Env::get('MAIL_FROM_NAME', 'Market'));
        return $name !== '' ? $name : 'Market';
    }

    private function helloHost(): string {
        $host = $_SERVER['HTTP_HOST'] ?? parse_url((string) (defined('BASE_URL') ? BASE_URL : Env::get('APP_URL', 'http://localhost')), PHP_URL_HOST) ?? 'localhost';
        $host = strtolower((string) preg_replace('/:\d+$/', '', (string) $host));
        return preg_replace('/[^a-z0-9.-]/', '', $host) ?: 'localhost';
    }

    private function formatAddress(string $email, string $name): string {
        $safeName = str_replace(["\r", "\n"], '', $name);
        return '=?UTF-8?B?' . base64_encode($safeName) . '?= <' . $email . '>';
    }

    private function encodeHeader(string $value): string {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function envBool(string $key, bool $default): bool {
        $value = Env::get($key, null);
        if ($value === null || $value === '') {
            return $default;
        }
        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $parsed ?? $default;
    }
}
