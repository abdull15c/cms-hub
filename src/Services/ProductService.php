<?php
namespace Src\Services;
use Src\Repositories\ProductRepository;
use Src\Services\Logger;
use Src\Services\QueueService;
use Src\Services\Security;

class ProductService {
    private $repo;
    public function __construct() { $this->repo = new ProductRepository(); }

    public function createProduct($postData, $files) {
        $fileName = null;
        if (isset($files['file']) && $files['file']['error'] === 0) {
            $fileName = $this->uploadSecureFile($files['file']);
        }
        if (!$fileName) throw new \Exception("Main ZIP file is required.");
        $data = $this->prepareData($postData);
        $data['file_path'] = $fileName;
        $pid = $this->repo->create($data);
        $this->repo->saveTranslations($pid, $data['translations']);
        $this->processImages($pid, $files);
        if (!empty($postData['auto_blog']) && $postData['auto_blog'] == '1') {
            $blogLang = $postData['ai_lang'] ?? 'ru';
            $blogTranslation = $data['translations'][$blogLang] ?? $data['translations']['ru'] ?? $data['translations']['en'] ?? ['title' => $data['title'], 'description' => $data['description']];
            QueueService::push('Src\Jobs\GenerateAiPostJob', [
                'product_id' => $pid, 'title' => $blogTranslation['title'], 'desc' => $blogTranslation['description'], 'lang' => $blogLang, 'quality' => 'fast'
            ]);
        }
        Logger::info("Product created: {$data['title']} (ID: $pid)");
        return $pid;
    }

    public function updateProduct($id, $postData, $files) {
        $data = $this->prepareData($postData);
        if (isset($files['file']) && $files['file']['error'] === 0) {
            $oldProd = $this->repo->find($id);
            if ($oldProd && $oldProd['file_path']) $this->deleteSecureFile($oldProd['file_path']);
            $data['file_path'] = $this->uploadSecureFile($files['file']);
        }
        $this->repo->update($id, $data);
        $this->repo->saveTranslations((int)$id, $data['translations']);
        $this->processImages($id, $files);
        Logger::info("Product updated: {$data['title']} (ID: $id)");
    }

    public function deleteProduct($id) {
        $prod = $this->repo->find($id);
        if (!$prod) return;
        if ($prod['file_path']) $this->deleteSecureFile($prod['file_path']);
        $images = $this->repo->getImages($id);
        foreach ($images as $img) {
            $path = ROOT_PATH . '/public/uploads/images/' . $img['image_path'];
            if (file_exists($path)) unlink($path);
        }
        $this->repo->delete($id);
        Logger::warning("Product deleted: ID $id");
    }

    public function duplicateProduct(int $id): int {
        $original = $this->repo->find($id);
        if (!$original) {
            throw new \Exception('Product not found.');
        }

        $copy = [
            'title' => $original['title'] . ' (Copy)',
            'price' => (float)$original['price'],
            'description' => $original['description'],
            'file_path' => $original['file_path'],
            'status' => 'draft',
            'category_id' => (int)$original['category_id'],
            'has_license' => (int)$original['has_license'],
            'sale_price' => $original['sale_price'] !== null ? (float)$original['sale_price'] : null,
            'sale_end' => $original['sale_end'],
            'meta_title' => $original['meta_title'],
            'meta_desc' => $original['meta_desc'],
            'meta_keywords' => $original['meta_keywords'],
        ];

        $newId = (int)$this->repo->create($copy);

        $translations = $this->repo->getTranslations($id);
        foreach ($translations as $lang => &$translation) {
            if ($lang === 'ru' || $lang === 'en') {
                $translation['title'] = ($translation['title'] ?? $original['title']) . ' (Copy)';
            }
        }
        unset($translation);
        if (!empty($translations)) {
            $this->repo->saveTranslations($newId, $translations);
        }

        foreach ($this->repo->getImages($id) as $index => $image) {
            $this->repo->addImage($newId, $image['image_path'], $index === 0 ? 1 : 0);
        }

        Logger::info("Product duplicated: {$original['title']} (Old ID: {$id}, New ID: {$newId})");
        return $newId;
    }

    private function prepareData($post) {
        $translations = $this->prepareTranslations($post['translations'] ?? [], $post);
        $primary = $translations['ru'] ?? $translations['en'] ?? reset($translations) ?: [
            'title' => Security::clean($post['title'] ?? ''),
            'description' => Security::cleanHtml($post['description'] ?? ''),
            'meta_title' => Security::clean($post['meta_title'] ?? ''),
            'meta_desc' => Security::clean($post['meta_desc'] ?? ''),
            'meta_keywords' => Security::clean($post['meta_keywords'] ?? ''),
        ];

        $price = floatval($post['price'] ?? 0);
        $salePrice = !empty($post['sale_price']) ? floatval($post['sale_price']) : null;
        if ($price <= 0) {
            throw new \Exception('Price must be greater than zero.');
        }
        if ($salePrice !== null && $salePrice >= $price) {
            throw new \Exception('Sale price must be lower than the regular price.');
        }
        $saleEnd = !empty($post['sale_end']) ? str_replace('T', ' ', (string)$post['sale_end']) : null;
        if ($salePrice !== null && $saleEnd === null) {
            throw new \Exception('Sale end date is required when sale price is set.');
        }

        return [
            'title' => $primary['title'],
            'price' => $price,
            'description' => $primary['description'],
            'status' => ($post['status'] ?? 'published') === 'draft' ? 'draft' : 'published',
            'category_id' => intval($post['category_id']),
            'has_license' => isset($post['has_license']) ? 1 : 0,
            'sale_price' => $salePrice,
            'sale_end' => $saleEnd,
            'meta_title' => $primary['meta_title'],
            'meta_desc' => $primary['meta_desc'],
            'meta_keywords' => $primary['meta_keywords'],
            'translations' => $translations,
        ];
    }

    private function prepareTranslations(array $translations, array $fallbackPost): array {
        $langs = ['ru', 'en'];
        $result = [];
        foreach ($langs as $lang) {
            $payload = is_array($translations[$lang] ?? null) ? $translations[$lang] : [];
            $title = Security::clean($payload['title'] ?? ($lang === 'ru' ? ($fallbackPost['title'] ?? '') : ''));
            $description = Security::cleanHtml($payload['description'] ?? ($lang === 'ru' ? ($fallbackPost['description'] ?? '') : ''));
            $metaTitle = Security::clean($payload['meta_title'] ?? ($lang === 'ru' ? ($fallbackPost['meta_title'] ?? '') : ''));
            $metaDesc = Security::clean($payload['meta_desc'] ?? ($lang === 'ru' ? ($fallbackPost['meta_desc'] ?? '') : ''));
            $metaKeywords = Security::clean($payload['meta_keywords'] ?? ($lang === 'ru' ? ($fallbackPost['meta_keywords'] ?? '') : ''));

            if ($title === '') {
                continue;
            }

            $result[$lang] = [
                'title' => $title,
                'description' => $description,
                'meta_title' => $metaTitle,
                'meta_desc' => $metaDesc,
                'meta_keywords' => $metaKeywords,
            ];
        }

        if (empty($result['ru']) && !empty($result['en'])) {
            $result['ru'] = $result['en'];
        }
        if (empty($result['en']) && !empty($result['ru'])) {
            $result['en'] = $result['ru'];
        }
        if (empty($result)) {
            throw new \Exception('At least one localized title is required.');
        }

        return $result;
    }

    private function uploadSecureFile($file) {
        if ($file['error'] !== UPLOAD_ERR_OK) throw new \Exception("Upload Error Code: " . $file['error']);
        
        $allowedMimes = [
            'application/zip', 
            'application/x-zip-compressed', 
            'multipart/x-zip', 
            'application/x-rar-compressed', 
            'application/x-7z-compressed', 
            'application/octet-stream'
        ];
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['zip', 'rar', '7z'])) throw new \Exception("Invalid file extension.");
        
        // FIXED: Magic Byte Check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowedMimes)) {
            Logger::warning("Security Risk: Upload blocked. Mime: $mime, Ext: $ext");
            throw new \Exception("Security Risk: File type mismatch.");
        }
        
        $name = Security::generateToken(16) . '.' . $ext;
        $dest = STORAGE_PATH . '/secure_uploads/' . $name;
        
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $dest)) throw new \Exception("Failed to move uploaded file.");
        return $name;
    }

    private function deleteSecureFile($name) {
        $path = STORAGE_PATH . '/secure_uploads/' . basename($name); 
        if (file_exists($path)) unlink($path);
    }

    private function processImages($pid, $files) {
        if (!isset($files['images'])) return;
        $imgDir = ROOT_PATH . '/public/uploads/images/';
        if (!is_dir($imgDir)) mkdir($imgDir, 0755, true);
        $hasMain = $this->repo->hasImages($pid);
        $allowedImgMimes = ['image/jpeg', 'image/png', 'image/webp'];
        
        $count = count($files['images']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['images']['error'][$i] === 0) {
                $ext = strtolower(pathinfo($files['images']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) continue;
                
                // Mime check for images
                $mime = mime_content_type($files['images']['tmp_name'][$i]);
                if (!in_array($mime, $allowedImgMimes)) continue;
                
                $n = bin2hex(random_bytes(16)) . '.' . $ext;
                if (move_uploaded_file($files['images']['tmp_name'][$i], $imgDir . $n)) {
                    $isMain = (!$hasMain && $i === 0) ? 1 : 0;
                    $this->repo->addImage($pid, $n, $isMain);
                }
            }
        }
    }
}
