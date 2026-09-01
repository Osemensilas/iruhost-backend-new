<?php

namespace App\Controllers\API;

use PDO;
use App\Core\DB;
use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Resend;

class ChatsController{

    protected $userId;
    protected $pdo;
    protected $resend;
    protected $resendApiCode;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        $this->pdo = DB::connection();
        $this->userId = $_SESSION['user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
        $this->resendApiCode = $_ENV['RESEND_API_KEY'] ?? null;
        //$this->resend = Resend::client($this->resendApiCode);
    }

    public function CreateChatUser(){
        if (!isset($_SESSION['user']['user_id'])){

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
                return;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);

            $email = $data['email'];
            $fullname = $data['fullname'];

            if (empty($email) || empty($fullname)){
                echo json_encode(['status' => 'error', 'message' => 'All field required']);
                return;
            }

            if (!preg_match('/^[a-zA-Z|| ]+$/', $fullname)){
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid name'
                ]);
                return;
            }

            if (!filter_var( $email, FILTER_VALIDATE_EMAIL)){
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid email address'
                ]);
                return;
            }

            $stmt = $this->pdo->prepare("SELECT * FROM chats_reg WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0){
                echo json_encode([
                    'status' => 'success',
                    'user' => $fullname
                ]);
                return;
            }

            $stmt = $this->pdo->prepare("INSERT INTO `chats_reg`(`user_id`, `email`, `fullname`) VALUES (?,?,?)");
            $stmt->execute([$this->userId, $email, $fullname]);

            echo json_encode([
                'status' => 'success',
                'user' => $fullname
            ]);
        }
    }

    public function addChat(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $message = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');
        $image = '';

        if (empty($_FILES['image']['name'] ?? '') && empty($message)) {
            return;
        }

        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . "../../../../public/uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename   = time() . "_" . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = $filename;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Image upload failed']);
                return;
            }
        }
            
        try{
            $recieverId = 'admin';

            $stmt = $this->pdo->prepare("INSERT INTO `chats`(`user_id`, `reciever_id`, `message`, `status`, `image`) VALUES (?,?,?,?,?)");
            $result = $stmt->execute([$this->userId, $recieverId, $message, 'new', $image]);

            if ($result){
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'message recieved'
                ]);

                $this->sentChatMessage($message);
            }
        }catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            return;
        }
    }

    public function getChats(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $stmt = $this->pdo->prepare("SELECT * FROM chats WHERE user_id = ? OR reciever_id = ? ORDER BY id");
        $stmt->execute([$this->userId, $this->userId]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            //print_r($rows);

            echo json_encode([
                'status' => 'success',
                'message' => $rows
            ]);
        }
    }

    private function sentChatMessage($message){
       try {
            $this->resend->emails->send([
                'from' => 'IruHost <contact@iruhost.com>',
                'to' => ['osemensilas@gmail.com'],
                'subject' => 'New Chat',
                'html' => "
                <p>{$message}</p>
                "
            ]);

        } catch (\Exception $e) {
            
        }
    }
}