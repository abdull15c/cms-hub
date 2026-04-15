<?php
namespace Src\Controllers\Admin;
use Config\Database;
use Src\Services\QueueService;
use Src\Services\Security;

class AdminContentController extends BaseAdminController {
    private function sanitizeHtml($html) {
        $html = Security::cleanHtml((string)$html);
        $allowed = '<p><br><ul><ol><li><strong><em><b><i><h2><h3><h4><blockquote><a><code><pre><img>';
        return strip_tags($html, $allowed);
    }
    
    // --- BLOG ---
    public function index() {
        $this->checkAuth();
        $posts = Database::connect()->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll();
        $this->view('admin/content/blog_list', ['posts' => $posts]);
    }

    public function createPost() { $this->checkAuth(); $this->view('admin/content/blog_form'); }
    
    public function storePost() {
        $this->checkAuth();
        $this->verifyCsrf();
        $title = $_POST['title'];
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $content = $this->sanitizeHtml($_POST['content'] ?? '');
        
        Database::connect()->prepare("INSERT INTO posts (title, slug, content) VALUES (?,?,?)")
            ->execute([$title, $slug, $content]);
        QueueService::push('Src\Jobs\GenerateSitemapJob', []);
            
        $this->redirect('/admin/blog');
    }
    
    public function deletePost($id) {
        $this->checkAuth();
        $this->verifyCsrf();
        Database::connect()->prepare("DELETE FROM posts WHERE id=?")->execute([$id]);
        QueueService::push('Src\Jobs\GenerateSitemapJob', []);
        $this->redirect('/admin/blog');
    }

    // --- FAQ ---
    public function faq() {
        $this->checkAuth();
        $faqs = Database::connect()->query("SELECT * FROM faqs ORDER BY sort_order ASC")->fetchAll();
        $this->view('admin/content/faq_list', ['faqs' => $faqs]);
    }
    
    public function storeFaq() {
        $this->checkAuth();
        $this->verifyCsrf();
        $q = $_POST['question'];
        $a = $_POST['answer'];
        $ord = intval($_POST['sort_order']);
        
        Database::connect()->prepare("INSERT INTO faqs (question, answer, sort_order) VALUES (?,?,?)")
            ->execute([$q, $a, $ord]);
        QueueService::push('Src\Jobs\GenerateSitemapJob', []);
            
        $this->redirect('/admin/faq');
    }
    
    public function deleteFaq($id) {
        $this->checkAuth();
        $this->verifyCsrf();
        Database::connect()->prepare("DELETE FROM faqs WHERE id=?")->execute([$id]);
        QueueService::push('Src\Jobs\GenerateSitemapJob', []);
        $this->redirect('/admin/faq');
    }

    public function ajaxAiPost() {
        $this->checkAuth();
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $topic = $input['topic'] ?? '';
        $lang = $input['lang'] ?? 'en';

        if (!$topic) { echo json_encode(['error' => 'Topic required']); exit; }

        try {
            $ai = new \Src\Services\AiService();
            $content = $ai->generateBlogPost($topic, $lang);
            if (!$content) throw new \Exception("AI returned empty result");
            echo json_encode(['status' => 'success', 'content' => $content]);
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }
}
