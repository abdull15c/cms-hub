<?php
namespace Src\Repositories;

use Config\Database;
use PDO;

class ProductRepository {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::connect();
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function localizedColumns(string $lang, string $productAlias = 'p', string $translationAlias = 'pt'): string {
        $safeLang = preg_replace('/[^a-z]/i', '', $lang) ?: 'ru';
        return "COALESCE({$translationAlias}.title, {$productAlias}.title) AS title, COALESCE({$translationAlias}.description, {$productAlias}.description) AS description, COALESCE({$translationAlias}.meta_title, {$productAlias}.meta_title) AS meta_title, COALESCE({$translationAlias}.meta_desc, {$productAlias}.meta_desc) AS meta_desc, COALESCE({$translationAlias}.meta_keywords, {$productAlias}.meta_keywords) AS meta_keywords";
    }

    public function translationJoin(string $lang, string $productAlias = 'p', string $translationAlias = 'pt'): string {
        $safeLang = preg_replace('/[^a-z]/i', '', $lang) ?: 'ru';
        return "LEFT JOIN product_translations {$translationAlias} ON {$translationAlias}.product_id = {$productAlias}.id AND {$translationAlias}.lang = '" . $safeLang . "'";
    }

    public function create(array $data) {
        $sql = "INSERT INTO products (title, price, description, file_path, status, category_id, has_license, sale_price, sale_end, demo_enabled, demo_url, demo_login, demo_password, meta_title, meta_desc, meta_keywords) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->pdo->prepare($sql)->execute([
            $data['title'], $data['price'], $data['description'], $data['file_path'], $data['status'],
            $data['category_id'], $data['has_license'], $data['sale_price'], $data['sale_end'],
            $data['demo_enabled'], $data['demo_url'], $data['demo_login'], $data['demo_password'],
            $data['meta_title'], $data['meta_desc'], $data['meta_keywords']
        ]);
        return $this->pdo->lastInsertId();
    }

    public function saveTranslations(int $productId, array $translations): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO product_translations (product_id, lang, title, description, meta_title, meta_desc, meta_keywords)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), meta_title = VALUES(meta_title), meta_desc = VALUES(meta_desc), meta_keywords = VALUES(meta_keywords)"
        );

        foreach ($translations as $lang => $translation) {
            if (!is_array($translation) || trim((string)($translation['title'] ?? '')) === '') {
                continue;
            }
            $stmt->execute([
                $productId,
                $lang,
                $translation['title'],
                $translation['description'] ?? '',
                $translation['meta_title'] ?? '',
                $translation['meta_desc'] ?? '',
                $translation['meta_keywords'] ?? '',
            ]);
        }
    }

    public function getTranslations(int $productId): array {
        $stmt = $this->pdo->prepare("SELECT lang, title, description, meta_title, meta_desc, meta_keywords FROM product_translations WHERE product_id = ?");
        $stmt->execute([$productId]);
        $rows = $stmt->fetchAll();
        $translations = [];
        foreach ($rows as $row) {
            $translations[$row['lang']] = $row;
        }
        return $translations;
    }

    public function update($id, array $data) {
        $fields = [
            'title', 'price', 'description', 'status', 'category_id', 'has_license', 
            'sale_price', 'sale_end', 'demo_enabled', 'demo_url', 'demo_login', 'demo_password',
            'meta_title', 'meta_desc', 'meta_keywords'
        ];
        
        $set = [];
        $values = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $set[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (!empty($data['file_path'])) {
            $set[] = "file_path = ?";
            $values[] = $data['file_path'];
        }

        $values[] = $id;
        $sql = "UPDATE products SET " . implode(', ', $set) . " WHERE id = ?";
        $this->pdo->prepare($sql)->execute($values);
    }

    public function delete($id) {
        $this->pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    }
    
    public function getImages($productId) {
        $stmt = $this->pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function addImage($productId, $path, $isMain = 0) {
        $this->pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)")
            ->execute([$productId, $path, $isMain]);
    }
    
    public function hasImages($productId) {
        $stmt = $this->pdo->prepare("SELECT count(*) FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetchColumn() > 0;
    }
}
