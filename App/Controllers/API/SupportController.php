<?php

namespace App\Controllers\API;

use PDO;
use App\Core\DB;
use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Resend;

class SupportController{

    protected $pdo;
    protected $userId;
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

    public function OpenTicket(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $name = $data["name"];
        $email = $data["email"];
        $subject = $data["subject"];
        $department = $data["department"];
        $priority = $data["priority"];
        $message = $data["message"];
        $userId = $this->userId;

        $parts = preg_split('/\s+/', trim($name));

        $avatar = strtoupper(
            substr($parts[0], 0, 1) . 
            (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
        );

        if (empty($email) || empty($subject) || empty($department) || empty($priority) || empty($message)){
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        if (strlen($subject) < 5) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Subject is too short. Please be more specific.'
            ]);
            return;
        }

        if (strlen($subject) > 100) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Subject is too long. Keep it under 100 characters.'
            ]);
            return;
        }

        if ($subject !== strip_tags($subject)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'HTML tags are not allowed in the subject.'
            ]);
            return;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please enter a valid email address'
            ]);
            return;
        }

        // Validate message content
        if (strlen($message) < 10) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Your message is too short. Please provide more details.'
            ]);
            return;
        }

        if (strlen($message) > 1000) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Your message is too long. Please shorten it (max 1000 characters).'
            ]);
            return;
        }

        // Prevent HTML or scripts
        if ($message !== strip_tags($message)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'HTML or script content is not allowed in the message.'
            ]);
            return;
        }

        // Optional: detect spammy content (URLs, etc.)
        if (preg_match('/https?:\/\//i', $message)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Links are not allowed in support messages.'
            ]);
            return;
        }

        $ticketId = uniqid("SP_", );

        $stmtInsert = $this->pdo->prepare("INSERT INTO `support`(`user_id`, `ticket_id`, `name`, `email`, `subject`, `department`, `priority`, `message`, `status`) VALUES (?,?,?,?,?,?,?,?,?)");
        $result = $stmtInsert->execute([$userId, $ticketId, $name, $email, $subject, $department, $priority, $message, "unresolved"]);
    
        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'We are upgrading our servers. Check back later'
            ]);
        }

        $insertChat = $this->pdo->prepare("INSERT INTO `support_chats`(`ticket_id`, `sender_id`, sender, `reciever_id`, `message`, `status`, `image`, `avatar`) VALUES (?,?,?,?,?,?,?,?)");
        $insertResult = $insertChat->execute([$ticketId, $userId, $name, 'admin', $message, 'not opened', '', $avatar]);

        if (!$insertResult){
            echo json_encode([
                'status' => 'error',
                'message' => 'We are upgrading our servers. Check back later'
            ]);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Your support tick has been opened and active'
        ]);

        $this->sendEmail($message, $email, $name);
    }

    public function GetTickets(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $status = $_GET['status'] ?? 'unresolved';

        $stmtTickets = $this->pdo->prepare("SELECT * FROM `support` WHERE status = ? AND user_id = ?");
        $result = $stmtTickets->execute([$status, $this->userId]);

        if (!$result){
            echo json_encode([
                'status' => 'success',
                'message' => 'no ticket opened'
            ]);
            return;
        }

        $rows = [];

        if ($stmtTickets->rowCount() > 0){
            $rows = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'message' => $rows
            ]);
        }
    }

    public function GetUnresolvedTickets(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $ticketId = $_GET['ticket_id'] ?? null;

        $stmtTickets = $this->pdo->prepare("SELECT * FROM `support` WHERE ticket_id = ? AND user_id = ?");
        $result = $stmtTickets->execute([$ticketId, $this->userId]);

        if (!$result){
            echo json_encode([
                'status' => 'success',
                'message' => 'ticket not found'
            ]);
            return;
        }

        $row = $stmtTickets->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'message' => $row
        ]);
    }

    public function GetSupportMessages(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $ticketId = $_GET['ticket_id'] ?? null;

        $stmtTickets = $this->pdo->prepare("SELECT * FROM `support_chats` WHERE ticket_id = ?");
        $result = $stmtTickets->execute([$ticketId]);

        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'no message found'
            ]);
            return;
        }

        $rows = [];

        if ($stmtTickets->rowCount() > 0){
            $rows = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'message' => $rows
            ]);
        }
    }

    private function sendEmail($message, $email, $name){
        // try {
        //     $this->resend->emails->send([
        //         'from' => 'IruHost <support@iruhost.com>',
        //         'to' => [$email, 'osemensilas@gmail.com'],
        //         'subject' => 'Support Ticket Message',
        //         'html' => "
        //         <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
        //         <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                    
        //             <p style='color: #333; line-height: 1.6;'>
        //             Your support ticket with the below message has been received. our support team will get back to you as soon as possible.
        //             </p>

        //             <p style='color:#333; line-height:1.6;'>
        //             <em>{$message}</em>
        //             </p>

        //             <p style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
        //             Thank you for choosing <strong>IruHost</strong>.<br>
        //             Need help? Contact us at <a href='mailto:support@iruhost.com' style='color:#007bff;'>support@iruhost.com</a>
        //             </p>
        //         </div>
        //         </div>
        //         "
        //     ]);
        // } catch (Exception $e) {
        //     //error_log("Email sending failed: " . $e->getMessage());
        // }
    }
}