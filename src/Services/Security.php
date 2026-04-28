<?php
namespace Src\Services;

class Security {
    public static function clean($data) {
        if (is_array($data)) {
            foreach ($data as $k => $v) $data[$k] = self::clean($v);
            return $data;
        }
        return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
    }
    
    public static function cleanHtml($html) {
        if (empty($html)) return '';
        // 1. Encode UTF-8 for DOMDocument (Fixes Cyrillic issues on Windows)
        $html = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
        
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        // Wrapper for partial HTML
        $dom->loadHTML("<div>$html</div>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        
        // 2. Remove Blacklisted Tags
        $blacklist = ['script', 'iframe', 'object', 'embed', 'applet', 'link', 'style', 'form', 'input', 'button', 'meta', 'base', 'svg', 'body', 'head'];
        foreach ($blacklist as $tag) {
            foreach ($xpath->query("//$tag") as $node) {
                $node->parentNode->removeChild($node);
            }
        }
        
        // 3. Attribute Cleaning
        foreach ($xpath->query("//*") as $node) {
            if (!$node->hasAttributes()) continue;
            $remove = [];
            foreach ($node->attributes as $attr) {
                $name = strtolower($attr->name);
                $val  = strtolower($attr->value);
                if (strpos($name, 'on') === 0) $remove[] = $name; 
                if (in_array($name, ['href', 'src', 'action', 'data']) && preg_match('/^\s*(javascript|vbscript|data):/i', $val)) {
                    $remove[] = $name;
                }
            }
            foreach ($remove as $r) $node->removeAttribute($r);
        }
        
        $output = '';
        $wrapper = $dom->documentElement;
        if ($wrapper instanceof \DOMNode) {
            foreach ($wrapper->childNodes as $child) {
                $output .= $dom->saveHTML($child);
            }
        }
        
        // 4. Decode back to UTF-8
        return mb_decode_numericentity($output, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
    }
    
    public static function sanitizeFileName($filename) {
        $filename = basename($filename); // Path Traversal Block
        // Convert to .txt if dangerous ext
        $filename = preg_replace('/\.(php|phtml|php3|php4|php5|inc|phar|sh|exe|bat|cmd)$/i', '.txt', $filename);
        return preg_replace('/[^a-zA-Z0-9_.-]/', '', $filename);
    }
    
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}
