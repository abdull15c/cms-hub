<?php
namespace Src\Controllers\Admin;
use Config\Database;
use Src\Services\Gate;

class CategoryController extends BaseAdminController {
    public function index() { 
        $this->checkAuth(); 
        Gate::authorize('dashboard.view');
        $cats=Database::connect()->query("SELECT * FROM categories")->fetchAll(); 
        $this->view('admin/categories',['categories'=>$cats]); 
    }
    
    public function store() { 
        $this->checkAuth(); Gate::authorize('dashboard.view'); $this->verifyCsrf(); 
        $n=$_POST['name']; $s=strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/','-',$n))); 
        Database::connect()->prepare("INSERT INTO categories (name,slug) VALUES (?,?)")->execute([$n,$s]); 
        $this->redirect('/admin/categories'); 
    }
    
    public function delete($id) { 
        $this->checkAuth(); Gate::authorize('dashboard.view'); $this->verifyCsrf(); 
        Database::connect()->prepare("DELETE FROM categories WHERE id=?")->execute([$id]); 
        $this->redirect('/admin/categories'); 
    }
}