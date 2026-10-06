<?php

namespace App\Controllers\API;

use App\Core\DB;
use PDO;
use Dotenv\Dotenv;

use PHPMailer\PHPMailer\PHPMailer;
use Exception;

class CommentsController{
    protected $pdo;
    private $userId;
    protected $emailHost;
    protected $emailPassword;
    protected $emailEnct;
    protected $emailPort;
    protected $emailUsername;
    protected $resend;
    protected $resendApiCode;
    protected $smtpPassword;
    protected $smtpUsername;
    protected $smtpHost;
    protected $smtpPort;
    protected $smtpEncryption;

    public function __construct(){
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        $this->pdo = DB::connection();
        $this->userId = $_SESSION['user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
        $this->emailHost = $_ENV['MAIL_HOST'] ?? null;
        $this->emailUsername = $_ENV['MAIL_USERNAME'] ?? null;
        $this->emailPassword = $_ENV['MAIL_PASSWORD'] ?? null;
        $this->resendApiCode = $_ENV['RESEND_API_KEY'] ?? null;
        //$this->resend = Resend::client($this->resendApiCode);

        $this->smtpHost = $_ENV['SMTP_HOST'] ?? null;
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? null;
        $this->smtpUsername = $_ENV['SMTP_USERNAME'] ?? null;
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'] ?? null;
        $this->smtpEncryption = $_ENV['SMTP_ENCRYPTION'] ?? null;
    }

    public function AddNewComment(){

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $message = htmlspecialchars($data['msg'] ?? '', ENT_QUOTES, 'UTF-8');

        if (empty($message)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Message field is required'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO `general_comments` (user_id, comment_id, comment, comment_reply, reply_by) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$this->userId, uniqid(), $message, '', '']);

        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to add comment'
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'msg' => "message added"
        ]);

        $this->commentAlert($message);
    }

    public function GetComments(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET'){
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `general_comments` ORDER BY id DESC");
        $result = $stmt->execute();

        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch comments'
            ]);
            return;
        }

        $rows = [];

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $user = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            foreach ($rows as &$row){
                $user->execute([$row['user_id']]);
                $userRow = $user->fetch(PDO::FETCH_ASSOC);
                $row['name'] = $userRow['firstname'] . " " . $userRow['lastname'] ?? 'User';
            }

            echo json_encode([
                'status' => 'success',
                'message' => $rows
            ]);
        }
    }

    private function commentAlert($message){
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->smtpHost; // your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername; // SMTP username
            $mail->Password = $this->smtpPassword;   // SMTP password
            $mail->SMTPSecure = $this->smtpEncryption; // or ENCRYPTION_SMTPS
            $mail->Port = $this->smtpPort; // 465 for SSL

            $mail->setFrom('noreply@iruhost.com', 'IruHost');
            $mail->addAddress("osemensilas@gmail.com", "Osemen Silas");

            $mail->isHTML(true);
            $mail->Subject = "New Question from IruHost User";
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                        
                        <h2 style='color: #1a1a1a; text-align: center; margin-bottom: 20px;'>New Question from IruHost User</h2>
                        
                        <p style='color: #333; line-height: 1.6;'>Hello Osemen,</p>
                        <p style='color: #333; line-height: 1.6;'>{$message}</p>
                        
                        <div style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
                        Thank you for being a valued member of the <strong>IruHost</strong> community.<br>
                        Need help? Contact us at <a href='mailto:support@iruhost.com'>support@iruhost.com</a>
                        <div class='logo' style='margin-top: 20px; height: max-content; width: 100%; display: flex; justify-content: center; align-items: center;'>
                            <img src='https://iruhost.com/logo.png' alt='IruHost Logo' style='display: block; margin: 20px auto; width: 60px; height: 60px; object-fit: contain;'>
                        </div>
                    </div>
                </div>
            ";

            // if ($mail->send()){
            //     echo json_encode([
            //         'status' => 'success', 
            //         'message' => 'Message sent successfully'
            //     ]);
            // } else {
            //     echo json_encode([
            //         'status' => 'error', 
            //         'message' => 'Failed to send message'
            //     ]);
            // }
        } catch (Exception $e) {
            // echo json_encode([
            //     'status' => 'error', 
            //     'message' => 'SMTP Mail Error to ' . $email . ': ' . $mail->ErrorInfo
            // ]);
        }
    }
}