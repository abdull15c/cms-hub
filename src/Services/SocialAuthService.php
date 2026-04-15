<?php
namespace Src\Services;
use Config\Database;

class SocialAuthService {
    public function createState() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        return $state;
    }
    public function verifyAndConsumeState($state) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $expected = (string)($_SESSION['oauth_state'] ?? '');
        unset($_SESSION['oauth_state']);
        return $expected !== '' && $state !== '' && hash_equals($expected, (string)$state);
    }
    
    public function getAuthUrl($provider) {
        $callback = BASE_URL . '/auth/callback/' . $provider;
        $state = $this->createState();

        if ($provider === 'google') {
            $params = [
                'client_id' => SettingsService::get('google_client_id'),
                'redirect_uri' => $callback,
                'response_type' => 'code',
                'scope' => 'email profile',
                'state' => $state
            ];
            return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        }

        if ($provider === 'github') {
            $params = [
                'client_id' => SettingsService::get('github_client_id'),
                'redirect_uri' => $callback,
                'scope' => 'user:email',
                'state' => $state
            ];
            return 'https://github.com/login/oauth/authorize?' . http_build_query($params);
        }
        return '/login';
    }

    public function handleCallback($provider, $code) {
        $callback = BASE_URL . '/auth/callback/' . $provider;
        
        if ($provider === 'google') {
            // 1. Exchange Code for Token
            $tokenData = $this->curl('https://oauth2.googleapis.com/token', [
                'client_id' => SettingsService::get('google_client_id'),
                'client_secret' => SettingsService::get('google_client_secret'),
                'redirect_uri' => $callback,
                'grant_type' => 'authorization_code',
                'code' => $code
            ]);
            
            if (empty($tokenData['access_token'])) return null;

            // 2. Get User Info
            $user = $this->curl('https://www.googleapis.com/oauth2/v1/userinfo', [], $tokenData['access_token']);
            return [
                'id' => $user['id'],
                'email' => $user['email'],
                'provider' => 'google'
            ];
        }

        if ($provider === 'github') {
            // 1. Exchange Code for Token
            $tokenData = $this->curl('https://github.com/login/oauth/access_token', [
                'client_id' => SettingsService::get('github_client_id'),
                'client_secret' => SettingsService::get('github_client_secret'),
                'code' => $code,
                'redirect_uri' => $callback,
            ]); // Github returns string unless header set, handled in curl method

            if (empty($tokenData['access_token'])) return null;

            // 2. Get User Info
            $user = $this->curl('https://api.github.com/user', [], $tokenData['access_token']);
            
            // Github Email might be private, fetch explicitly
            if (empty($user['email'])) {
                $emails = $this->curl('https://api.github.com/user/emails', [], $tokenData['access_token']);
                foreach ($emails as $em) {
                    if ($em['primary'] && $em['verified']) {
                        $user['email'] = $em['email'];
                        break;
                    }
                }
            }

            return [
                'id' => $user['id'],
                'email' => $user['email'] ?? null,
                'provider' => 'github'
            ];
        }
        return null;
    }

    private function curl($url, $postParams = [], $bearerToken = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CMS-HUB'); // Github requires User-Agent
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $headers = ['Accept: application/json'];
        if ($bearerToken) $headers[] = "Authorization: Bearer $bearerToken";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($postParams)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postParams));
        }

        $response = curl_exec($ch);
        if ($response === false) {
            curl_close($ch);
            return null;
        }
        curl_close($ch);
        return json_decode($response, true);
    }
}
