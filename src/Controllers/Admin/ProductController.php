<?php
namespace Src\Controllers\Admin;

use Config\Database;
use Src\Services\Gate;
use Src\Services\ProductService;
use Src\Repositories\ProductRepository;
use Src\Services\AiService;
use Src\Services\QueueService; // ADDED
use Src\Services\SessionService;

class ProductController extends BaseAdminController {
    
    private $service;
    private $repo;

    public function __construct() {
        $this->service = new ProductService();
        $this->repo = new ProductRepository();
    }

    public function create() { 
        $this->checkAuth(); 
        Gate::authorize('product.create'); 
        $cats = Database::connect()->query("SELECT id, name FROM categories")->fetchAll(); // OPTIMIZED SQL
        $translations = [
            'ru' => ['title' => '', 'description' => '', 'meta_title' => '', 'meta_desc' => '', 'meta_keywords' => ''],
            'en' => ['title' => '', 'description' => '', 'meta_title' => '', 'meta_desc' => '', 'meta_keywords' => ''],
        ];
        $product = [
            'price' => '',
            'category_id' => '',
            'has_license' => 1,
            'sale_price' => '',
            'sale_end' => '',
            'file_path' => '',
            'status' => 'published',
            'demo_enabled' => 0,
            'demo_url' => '',
            'demo_login' => '',
            'demo_password' => '',
        ];
        $this->view('admin/create_product', ['categories' => $cats, 'product' => $product, 'translations' => $translations, 'mode' => 'create']); 
    }

    public function index() {
        $this->checkAuth();
        Gate::authorize('product.create');

        $pdo = Database::connect();
        $status = in_array($_GET['status'] ?? 'all', ['all', 'draft', 'published'], true) ? ($_GET['status'] ?? 'all') : 'all';
        $term = trim((string)($_GET['q'] ?? ''));

        $where = [];
        $params = [];
        if ($status !== 'all') {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($term !== '') {
            $where[] = '(p.title LIKE ? OR ptru.title LIKE ? OR pten.title LIKE ?)';
            $like = '%' . $term . '%';
            array_push($params, $like, $like, $like);
        }
        $whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT p.*, c.name AS category_name, ptru.title AS title_ru, pten.title AS title_en,
                (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) AS images_count
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN product_translations ptru ON ptru.product_id = p.id AND ptru.lang = 'ru'
                LEFT JOIN product_translations pten ON pten.product_id = p.id AND pten.lang = 'en'
                {$whereSql}
                ORDER BY p.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        $counts = [
            'all' => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
            'draft' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'draft'")->fetchColumn(),
            'published' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'published'")->fetchColumn(),
        ];

        $this->view('admin/products', compact('products', 'status', 'term', 'counts'));
    }

    public function store() { 
        $this->checkAuth(); 
        $this->verifyCsrf(); 
        try {
            $this->service->createProduct($_POST, $_FILES);
            // OPTIMIZATION: Async Sitemap
            QueueService::push('Src\Jobs\GenerateSitemapJob', []);
            $this->redirect('/admin/dashboard', null, 'Product created successfully.');
        } catch (\Exception $e) {
            $this->redirect('/admin/product/new', 'Error: ' . $e->getMessage());
        }
    }

    public function edit($id) { 
        $this->checkAuth(); 
        $prod = $this->repo->find($id); 
        if(!$prod) $this->redirect('/admin/dashboard', 'Product not found'); 
        $cats = Database::connect()->query("SELECT id, name FROM categories")->fetchAll(); // OPTIMIZED SQL
        $translations = $this->repo->getTranslations((int)$id);
        foreach (['ru', 'en'] as $lang) {
            if (!isset($translations[$lang])) {
                $translations[$lang] = [
                    'lang' => $lang,
                    'title' => $prod['title'] ?? '',
                    'description' => $prod['description'] ?? '',
                    'meta_title' => $prod['meta_title'] ?? '',
                    'meta_desc' => $prod['meta_desc'] ?? '',
                    'meta_keywords' => $prod['meta_keywords'] ?? '',
                ];
            }
        }
        $this->view('admin/edit_product', ['product' => $prod, 'categories' => $cats, 'translations' => $translations, 'mode' => 'edit']); 
    }

    public function update($id) { 
        $this->checkAuth(); 
        $this->verifyCsrf(); 
        try {
            $this->service->updateProduct($id, $_POST, $_FILES);
            // OPTIMIZATION: Async Sitemap
            QueueService::push('Src\Jobs\GenerateSitemapJob', []);
            $this->redirect('/admin/dashboard', null, 'Product updated.');
        } catch (\Exception $e) {
            $this->redirect("/admin/product/edit/$id", 'Error: ' . $e->getMessage());
        }
    }
    
    public function delete($id) { 
        Gate::authorize('product.delete'); 
        $this->checkAuth(); 
        $this->verifyCsrf();
        $this->service->deleteProduct($id);
        QueueService::push('Src\Jobs\GenerateSitemapJob', []);
        $this->redirect('/admin/dashboard', null, 'Product deleted.'); 
    }

    public function duplicate($id) {
        $this->checkAuth();
        $this->verifyCsrf();
        try {
            $newId = $this->service->duplicateProduct((int)$id);
            $this->redirect('/admin/product/edit/' . $newId, null, 'Product duplicated as draft.');
        } catch (\Exception $e) {
            $this->redirect('/admin/products', 'Error: ' . $e->getMessage());
        }
    }

    // --- AJAX AI HANDLERS (Same as before) ---
    public function ajaxSeo() {
        $this->checkAuth();
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        try {
            $ai = new AiService();
            $data = $ai->generateSeo($input['title'] ?? '', $input['description'] ?? '', $input['lang'] ?? 'en');
            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (\Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
        exit;
    }
    public function ajaxAiCode() {
        $this->checkAuth();
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        try {
            $ai = new AiService();
            $res = $ai->analyzeCode($input['text'] ?? '');
            echo json_encode(['status' => 'success', 'review' => $res]);
        } catch (\Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
        exit;
    }
    public function ajaxTranslate() {
        $this->checkAuth();
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        try {
            $ai = new AiService();
            $res = $ai->translateProduct($input, $input['target_lang'] ?? 'en');
            echo json_encode(['status' => 'success', 'data' => $res]);
        } catch (\Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
        exit;
    }
    public function ajaxMarketing() {
        $this->checkAuth();
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        try {
            $ai = new AiService();
            $res = $ai->rewriteMarketing($input['text'] ?? '');
            echo json_encode(['status' => 'success', 'text' => $res]);
        } catch (\Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
        exit;
    }

    public function ajaxSourceAnalyze() {
        $this->checkAuth();
        header('Content-Type: application/json');

        try {
            $summary = $this->summarizeSourceInput();
            if ($summary['summary'] === '') {
                throw new \Exception('Provide source text or a ZIP archive for analysis.');
            }

            $categories = Database::connect()->query("SELECT name FROM categories ORDER BY name ASC")->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            $ai = new AiService();
            $profile = $ai->extractProductProfile($summary['summary'], $categories);
            $ru = $ai->generateLocalizedProductCopy($profile, 'ru');
            $en = $ai->generateLocalizedProductCopy($profile, 'en');

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'profile' => $profile,
                    'translations' => ['ru' => $ru, 'en' => $en],
                    'suggested_category' => $profile['suggested_category'] ?? '',
                    'source_kind' => $summary['kind'],
                ],
            ]);
        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
        }
        exit;
    }

    private function summarizeSourceInput(): array {
        $parts = [];
        $kind = 'text';

        $sourceText = trim((string)($_POST['source_text'] ?? ''));
        if ($sourceText !== '') {
            $parts[] = "User source text:\n" . mb_substr($sourceText, 0, 20000);
        }

        $repoUrl = trim((string)($_POST['repo_url'] ?? ''));
        if ($repoUrl !== '') {
            $parts[] = "Repository URL: {$repoUrl}";
        }

        if (!empty($_FILES['source_archive']) && ($_FILES['source_archive']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $kind = 'archive';
            $parts[] = $this->summarizeUploadedArchive($_FILES['source_archive']);
        }

        return ['summary' => trim(implode("\n\n", $parts)), 'kind' => $kind];
    }

    private function summarizeUploadedArchive(array $file): string {
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            throw new \Exception('Source analysis currently supports ZIP archives only.');
        }
        if (!class_exists('ZipArchive')) {
            throw new \Exception('ZIP support is not available in current PHP environment.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            throw new \Exception('Failed to read the uploaded ZIP archive.');
        }

        $files = [];
        $snippets = [];
        $interesting = [
            'readme.md', 'package.json', 'composer.json', '.env.example', 'routes/web.php',
            'public/index.php', 'src/app.js', 'src/main.js', 'src/main.ts', 'src/bootstrap.php'
        ];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!is_string($name)) {
                continue;
            }
            $normalized = str_replace('\\', '/', $name);
            $files[] = $normalized;

            $lower = strtolower($normalized);
            $basename = strtolower(basename($normalized));
            $isInteresting = in_array($basename, $interesting, true) || in_array($lower, $interesting, true);
            if (!$isInteresting) {
                continue;
            }

            $content = $zip->getFromIndex($i);
            if (!is_string($content) || $content === '') {
                continue;
            }

            $snippets[] = "File: {$normalized}\n" . mb_substr($content, 0, 4000);
            if (count($snippets) >= 8) {
                break;
            }
        }

        $zip->close();

        return "Archive file list:\n" . implode("\n", array_slice($files, 0, 150)) . "\n\nKey file excerpts:\n" . implode("\n\n", $snippets);
    }
}
