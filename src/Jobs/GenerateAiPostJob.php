<?php
namespace Src\Jobs;
use Config\Database;
use Src\Services\AiService;

class GenerateAiPostJob {
    public function handle($data) {
        $title = $data['title'];
        $pid = $data['product_id'];
        $desc = $data['desc'] ?? 'No description provided.';
        $lang = $data['lang'] ?? 'en'; // Default to English for AI prompt logic
        
        // Console output (visible only in logs)
        echo "[AI] Processing: $title...\n";

        $pdo = Database::connect();

        // 1. GET DATA
        $stmtP = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmtP->execute([$pid]);
        $product = $stmtP->fetch();
        $price = $product['price'] ?? 0;

        $stmtI = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_main DESC LIMIT 4");
        $stmtI->execute([$pid]);
        $images = $stmtI->fetchAll();
        $mainImage = isset($images[0]) ? $images[0]['image_path'] : null;
        $extraImages = array_slice($images, 1);

        // 2. GENERATE TEXT
        // We ensure prompts use English instructions to get the best result from AI
        $userPrompt = "Write a professional review for the product: '$title'.
        Input Data: '$desc'.
        
        RULES:
        1. If description is short, INVENT plausible features based on title.
        2. DO NOT use placeholders. Write full sentences.
        3. HTML Structure required:
           <h2>INTRODUCTION</h2> (paragraph)
           <h2>KEY FEATURES</h2> (<ul> list with 5 items)
           <h2>VERDICT</h2> (paragraph)
        4. Output Language: " . ($lang === 'ru' ? 'Russian' : 'English');

        $ai = new AiService();
        $text = $ai->generate($userPrompt, $data["quality"] ?? "fast");

        if (!$text) { 
            echo "[AI] Error: Empty response.\n"; 
            return; 
        }

        // 3. BUILD HTML
        $html = '<div class="blog-post-wrapper">';

        // HERO IMAGE
        if ($mainImage) {
            $baseUrl = defined('BASE_URL') ? BASE_URL : ''; 
            $imgUrl = $baseUrl . '/uploads/images/' . $mainImage;
            $html .= "<div class='mb-4 position-relative overflow-hidden rounded shadow-lg border border-secondary'><img src='$imgUrl' class='img-fluid w-100' style='max-height: 450px; object-fit: cover;' alt='Main'></div>";
        }

        // TEXT CONTENT
        $html .= $text;

        // GALLERY SECTION
        if (!empty($extraImages)) {
            $galleryTitle = ($lang === 'ru') ? 'Gallery' : 'Gallery'; // Keep English or use Language File
            $html .= "<h2 class='mt-5'>$galleryTitle</h2><div class='row g-3 mb-4'>";
            foreach ($extraImages as $img) {
                $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                $subUrl = $baseUrl . '/uploads/images/' . $img['image_path'];
                $html .= "<div class='col-md-6'><a href='$subUrl' target='_blank'><div class='rounded overflow-hidden border border-secondary'><img src='$subUrl' class='img-fluid w-100' style='height: 200px; object-fit: cover;'></div></a></div>";
            }
            $html .= "</div>";
        }

        // ACTION BUTTONS
        $link = defined('BASE_URL') ? BASE_URL . '/product/' . $pid : '#';
        
        if ($price > 0) {
            $btnText = "Buy for \$$price"; // Standardized English
            $btnClass = "btn-cyber";
        } else {
            $btnText = "Download Free"; // Standardized English
            $btnClass = "btn-success";
        }
        
        $ctaTitle = "Interested?"; // Standardized English
        
        $html .= "
        <div class='p-5 bg-black bg-opacity-25 border border-secondary rounded text-center mt-5'>
            <h3 class='text-light mb-3'>$ctaTitle</h3>
            <a href='$link' class='btn $btnClass btn-lg px-5 shadow-lg rounded-pill'>$btnText</a>
        </div>";

        $html .= '</div>';

        // SAVE TO DB
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))) . '-review-' . rand(100,999);
        $titlePrefix = "Review: ";
        
        $pdo->prepare("INSERT INTO posts (title, slug, content) VALUES (?, ?, ?)")
            ->execute([$titlePrefix . $title, $slug, $html]);
            
        echo "[AI] Post saved successfully.\n";
    }
}