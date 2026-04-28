<?php
namespace Src\Services;

class AiService {
    
    // ... (Generate methods wrapper) ...
    
    public function generate($prompt, $quality = 'fast') {
        $provider = SettingsService::get('ai_provider');
        if ($provider === 'openai') return $this->askOpenAI($prompt, $quality);
        if ($provider === 'gemini') return $this->askGemini($prompt);
        return null;
    }

    public function generateSeo($title, $desc, $lang = 'en') {
        // ... (Logic kept same) ...
        $langName = ($lang === 'ru') ? 'Russian' : 'English';
        $prompt = "Act as an SEO Expert. Product: " . $title . ". Desc: " . $desc . ". Generate JSON keys: meta_title, meta_desc, meta_keywords in $langName.";
        $result = $this->generate($prompt, 'smart'); 
        if (!$result) return null;
        $result = str_replace(['```json', '```'], '', $result);
        $start = strpos($result, '{'); $end = strrpos($result, '}');
        if ($start !== false && $end !== false) $result = substr($result, $start, $end - $start + 1);
        return json_decode(trim($result), true);
    }
    
    public function generateBlogPost($topic, $lang = 'en') {
        $langName = ($lang === 'ru') ? 'Russian' : 'English';
        $prompt = "Write a polished blog article about '{$topic}'. Return valid HTML only with one intro paragraph, 3 section headings, bullet list, and closing CTA. Language: {$langName}.";
        $result = $this->generate($prompt, 'smart');
        if ($result) {
            return trim(str_replace(['```html', '```'], '', $result));
        }
        return '<h2>' . htmlspecialchars($topic, ENT_QUOTES, 'UTF-8') . '</h2><p>This product solves a practical problem with a focused feature set, fast onboarding, and a workflow designed for everyday use.</p><h3>Why it matters</h3><p>Teams usually need predictable setup, clear documentation, and an interface that gets out of the way. This product is positioned around exactly that value.</p><h3>What stands out</h3><ul><li>Fast start for new users</li><li>Useful core feature set</li><li>Simple path to adoption</li></ul><h3>Who it fits</h3><p>It is best suited for buyers who want dependable results without a long implementation cycle.</p><p><strong>Takeaway:</strong> a practical option for users who value speed, clarity, and immediate utility.</p>';
    }
    public function generateSupportReply($subject, $message, $userEmail) {
        $prompt = "Write a concise support reply. Subject: {$subject}. User email: {$userEmail}. Message: {$message}. Requirements: helpful, specific, friendly, no placeholders.";
        $result = $this->generate($prompt, 'smart');
        return $result ?: "Hello,\n\nThanks for reaching out. We reviewed your request and recommend checking the latest order status in your profile, confirming the exact product involved, and replying with any error text or screenshot if the issue remains. Once we have that, we can help you much faster.\n\nBest regards,\nSupport";
    }
    public function analyzeCode($input) {
        $prompt = "Review the following code and return a concise report with sections: Risks, Bugs, Improvements, Test Ideas. Code:\n" . $input;
        $result = $this->generate($prompt, 'smart');
        return $result ?: "Risks:\n- Validate all external input.\n- Check error handling around network and file operations.\n\nBugs:\n- Look for missing null checks and inconsistent state transitions.\n\nImprovements:\n- Extract duplicated logic and standardize responses.\n\nTest Ideas:\n- Add happy-path, validation, and failure-path tests.";
    }
    public function translateProduct($data, $target) {
        $payload = [
            'title' => (string)($data['title'] ?? ''),
            'description' => (string)($data['description'] ?? ''),
            'meta_title' => (string)($data['meta_title'] ?? ''),
            'meta_desc' => (string)($data['meta_desc'] ?? ''),
            'meta_keywords' => (string)($data['meta_keywords'] ?? ''),
        ];
        $prompt = "Translate the following product data to {$target}. Return JSON with the same keys and translated values only: " . json_encode($payload, JSON_UNESCAPED_UNICODE);
        $result = $this->generate($prompt, 'smart');
        if ($result) {
            $result = str_replace(['```json', '```'], '', $result);
            $decoded = json_decode(trim($result), true);
            if (is_array($decoded)) {
                return array_merge($payload, array_intersect_key($decoded, $payload));
            }
        }
        return $payload;
    }
    public function rewriteMarketing($text) {
        $prompt = "Rewrite this marketing text to be clearer, more persuasive, and benefit-driven without hype. Keep it concise:\n" . $text;
        $result = $this->generate($prompt, 'smart');
        return $result ?: trim($text);
    }

    public function extractProductProfile(string $sourceSummary, array $categories = []): array {
        $categoryHint = empty($categories) ? 'No categories provided.' : 'Allowed categories: ' . implode(', ', $categories);
        $prompt = "You are a senior product marketer and software analyst. Analyze the source summary below and identify what the project actually is, what it does for the buyer, the most marketable capabilities, and the likely category. Return valid JSON only with keys: product_name, short_summary, project_type, audience, tech_stack, key_features, monetizable_value, suggested_category, category_confidence, seo_topics, risks. category_confidence must be an integer from 0 to 100. Keep key_features, tech_stack, seo_topics, risks as arrays of short strings. Source summary:\n" . $sourceSummary . "\n" . $categoryHint;
        $result = $this->generate($prompt, 'smart');
        if ($result) {
            $result = str_replace(['```json', '```'], '', $result);
            $decoded = json_decode(trim($result), true);
            if (is_array($decoded)) {
                return $this->normalizeProfile($decoded, $categories);
            }
        }
        return $this->heuristicProfile($sourceSummary, $categories);
    }

    public function generateLocalizedProductCopy(array $profile, string $lang): array {
        $langName = $lang === 'ru' ? 'Russian' : 'English';
        $prompt = "You are writing a high-converting marketplace product page. Use the project profile below. Create native {$langName} copy, not a translation of another language. Return valid JSON only with keys: title, description, meta_title, meta_desc, meta_keywords. Description should be polished HTML with intro paragraph, feature bullets, and a short buyer-oriented closing paragraph. Project profile: " . json_encode($profile, JSON_UNESCAPED_UNICODE);
        $result = $this->generate($prompt, 'smart');
        if ($result) {
            $result = str_replace(['```json', '```'], '', $result);
            $decoded = json_decode(trim($result), true);
            if (is_array($decoded)) {
                return $this->normalizeLocalizedCopy($decoded, $profile, $lang);
            }
        }
        return $this->fallbackLocalizedCopy($profile, $lang);
    }

    private function normalizeProfile(array $profile, array $categories): array {
        $normalized = [
            'product_name' => trim((string)($profile['product_name'] ?? 'Untitled Product')),
            'short_summary' => trim((string)($profile['short_summary'] ?? 'Software project package')),
            'project_type' => trim((string)($profile['project_type'] ?? 'software')),
            'audience' => trim((string)($profile['audience'] ?? 'Developers and teams')),
            'tech_stack' => array_values(array_filter(array_map('trim', (array)($profile['tech_stack'] ?? [])))),
            'key_features' => array_values(array_filter(array_map('trim', (array)($profile['key_features'] ?? [])))),
            'monetizable_value' => trim((string)($profile['monetizable_value'] ?? 'Ready-made project foundation')),
            'suggested_category' => trim((string)($profile['suggested_category'] ?? '')),
            'category_confidence' => max(0, min(100, (int)($profile['category_confidence'] ?? 0))),
            'seo_topics' => array_values(array_filter(array_map('trim', (array)($profile['seo_topics'] ?? [])))),
            'risks' => array_values(array_filter(array_map('trim', (array)($profile['risks'] ?? [])))),
        ];

        if ($normalized['suggested_category'] === '' && !empty($categories)) {
            $normalized['suggested_category'] = $categories[0];
        }

        return $normalized;
    }

    private function heuristicProfile(string $sourceSummary, array $categories): array {
        $summary = trim(strip_tags($sourceSummary));
        $name = 'Software Product';
        if (preg_match('/(?:title|name)\s*[:=]\s*([^\n]+)/i', $summary, $m)) {
            $name = trim($m[1]);
        }

        $stack = [];
        foreach (['PHP', 'Laravel', 'React', 'Vue', 'Node.js', 'MySQL', 'Bootstrap', 'API', 'Docker'] as $needle) {
            if (stripos($summary, $needle) !== false) {
                $stack[] = $needle;
            }
        }

        $features = [];
        foreach (['admin', 'payment', 'analytics', 'auth', 'dashboard', 'subscription', 'marketplace', 'chat'] as $needle) {
            if (stripos($summary, $needle) !== false) {
                $features[] = ucfirst($needle) . ' module';
            }
        }

        return [
            'product_name' => $name,
            'short_summary' => mb_substr($summary !== '' ? $summary : 'Software project package', 0, 240),
            'project_type' => 'software project',
            'audience' => 'Founders, developers, and product teams',
            'tech_stack' => $stack,
            'key_features' => $features ?: ['Structured codebase', 'Reusable business logic', 'Ready for customization'],
            'monetizable_value' => 'Can be launched faster than building from scratch',
            'suggested_category' => $categories[0] ?? '',
            'category_confidence' => !empty($categories) ? 45 : 0,
            'seo_topics' => array_slice($stack, 0, 5),
            'risks' => ['Verify deployment requirements before launch'],
        ];
    }

    private function normalizeLocalizedCopy(array $payload, array $profile, string $lang): array {
        $fallback = $this->fallbackLocalizedCopy($profile, $lang);
        return [
            'title' => trim((string)($payload['title'] ?? $fallback['title'])),
            'description' => trim((string)($payload['description'] ?? $fallback['description'])),
            'meta_title' => trim((string)($payload['meta_title'] ?? $fallback['meta_title'])),
            'meta_desc' => trim((string)($payload['meta_desc'] ?? $fallback['meta_desc'])),
            'meta_keywords' => trim((string)($payload['meta_keywords'] ?? $fallback['meta_keywords'])),
        ];
    }

    private function fallbackLocalizedCopy(array $profile, string $lang): array {
        $title = (string)($profile['product_name'] ?? 'Software Product');
        $features = array_slice((array)($profile['key_features'] ?? []), 0, 4);
        $stack = implode(', ', array_slice((array)($profile['tech_stack'] ?? []), 0, 5));
        $summary = (string)($profile['short_summary'] ?? 'Ready-made software project');
        $audience = (string)($profile['audience'] ?? 'Developers and teams');
        $value = (string)($profile['monetizable_value'] ?? 'Ready-made project foundation');
        $featureItems = $features ?: ($lang === 'ru'
            ? ['Готовая архитектура', 'Ускоренный запуск', 'Подходит для доработки']
            : ['Structured architecture', 'Faster launch path', 'Built for customization']);
        $featureHtml = '<ul><li>' . implode('</li><li>', array_map(static fn($item) => htmlspecialchars((string)$item, ENT_QUOTES, 'UTF-8'), $featureItems)) . '</li></ul>';

        if ($lang === 'ru') {
            return [
                'title' => $title,
                'description' => '<p>' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') . '</p>' . $featureHtml . '<p>Подходит для: ' . htmlspecialchars($audience, ENT_QUOTES, 'UTF-8') . '. Ключевая ценность: ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . ($stack !== '' ? '. Стек: ' . htmlspecialchars($stack, ENT_QUOTES, 'UTF-8') : '') . '.</p>',
                'meta_title' => $title . ' - готовый проект',
                'meta_desc' => $summary,
                'meta_keywords' => implode(', ', array_filter(array_merge([$title], $features, (array)($profile['seo_topics'] ?? [])))),
            ];
        }

        return [
            'title' => $title,
            'description' => '<p>' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') . '</p>' . $featureHtml . '<p>Best for ' . htmlspecialchars($audience, ENT_QUOTES, 'UTF-8') . '. Core value: ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . ($stack !== '' ? '. Stack: ' . htmlspecialchars($stack, ENT_QUOTES, 'UTF-8') : '') . '.</p>',
            'meta_title' => $title . ' - ready-made project',
            'meta_desc' => $summary,
            'meta_keywords' => implode(', ', array_filter(array_merge([$title], $features, (array)($profile['seo_topics'] ?? [])))),
        ];
    }

    private function askOpenAI($prompt, $quality) {
        $key = SettingsService::get('openai_key');
        if (!$key) return null;
        $model = SettingsService::get('openai_model') ?: (($quality === 'smart') ? 'gpt-4o-mini' : 'gpt-4o-mini');
        return $this->curl('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.7
        ], ["Authorization: Bearer " . $key], 'choices.0.message.content');
    }

    private function askGemini($prompt) {
        $key = SettingsService::get('gemini_key');
        if (!$key) return null;
        $model = SettingsService::get('gemini_model') ?: 'gemini-1.5-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . $key;
        return $this->curl($url, [
            'contents' => [['parts' => [['text' => $prompt]]]]
        ], [], 'candidates.0.content.parts.0.text');
    }

    // FIXED: Enforce SSL Verification using local Cert Bundle
    private function curl($url, $data, $headers, $path) {
        $ch = curl_init($url);
        $headers[] = 'Content-Type: application/json';
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // SECURITY FIX: Enforce SSL and provide CA Bundle
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        if (file_exists(CONFIG_PATH . '/cacert.pem')) {
            curl_setopt($ch, CURLOPT_CAINFO, CONFIG_PATH . '/cacert.pem');
        }
        
        $res = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log("AI Curl Error: " . curl_error($ch));
            curl_close($ch);
            return null;
        }
        curl_close($ch);
        
        $json = json_decode($res, true);
        $keys = explode('.', $path);
        $val = $json;
        foreach($keys as $k) {
            if(isset($val[$k])) $val = $val[$k];
            else return null;
        }
        return $val;
    }
}
