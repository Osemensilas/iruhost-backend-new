<?php

namespace App\Controllers\API;

use App\Core\DB;
use PDO;
use Dotenv\Dotenv;
use Exception;

class BlogsController{

    protected $pdo;
    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        $this->pdo = DB::connection();
    }

    public function GetBlogs(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        $stmt = $this->pdo->prepare("SELECT * FROM blogs ORDER BY RAND()");
        $stmt->execute();

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($rows);
        }
    }

    public function RecentBlogs(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        $stmt = $this->pdo->prepare("SELECT * FROM blogs ORDER BY id DESC LIMIT 3");
        $stmt->execute();

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($rows);
        }
    }

    public function TodayBlogs(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        $rows = []; 
        
        $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE category = ? ORDER BY id DESC LIMIT 3");
        $stmt->execute(['Online Business']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($rows);
        }
    }

    public function SingleBlog(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE title = ?");
        $stmt->execute([$data['action']]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'result' => $rows
            ]);
        }
    }

    public function GetBlogBySlug($slug){

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE slug = ?");
            $stmt->execute([$slug]);

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode([
                    'status' => 'success',
                    'result' => $row
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Blog not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }


    public function RelatedBlog(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $slug = $data['slug'];

        $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE slug = ?");
        $stmt->execute([$slug]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetch(PDO::FETCH_ASSOC);  
        }

        $relatedBlogs = $this->pdo->prepare("SELECT * FROM blogs WHERE category = ? AND slug != ? ORDER BY id DESC LIMIT 3");
        $relatedBlogs->execute([$rows['category'], $slug]);

        if ($relatedBlogs->rowCount() > 0){

            $relatedRows = $relatedBlogs->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'result' => $relatedRows
            ]);
        }
    }

    public function OtherBlog(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $slug = $data['slug'];

        $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE slug != ? ORDER BY RAND() LIMIT 9");
        $stmt->execute([$slug]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'result' => $rows
            ]);
        }
    }
}