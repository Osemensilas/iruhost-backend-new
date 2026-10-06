<?php

namespace App\Controllers\API;

use Dotenv\Dotenv;
use App\Core\DB;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;

class WebsiteController{

    protected $pdo;
    protected $smtpPassword;
    protected $smtpUsername;
    protected $smtpHost;
    protected $smtpPort;
    protected $smtpEncryption;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        $this->pdo = DB::connection();

        $this->smtpHost = $_ENV['SMTP_HOST'] ?? null;
        $this->smtpPort = $_ENV['SMTP_PORT'] ?? null;
        $this->smtpUsername = $_ENV['SMTP_USERNAME'] ?? null;
        $this->smtpPassword = $_ENV['SMTP_PASSWORD'] ?? null;
        $this->smtpEncryption = $_ENV['SMTP_ENCRYPTION'] ?? null;
    }

    public function WebList(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM `websites` WHERE category = ? LIMIT 4");
        $stmt->execute([$data['website']]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'result' => $rows
            ]);
        }
    }

    public function WebListAll(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM `websites` WHERE category = ?");
        $stmt->execute([$data['website']]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'result' => $rows
            ]);
        }
    }

    public function WebApp(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $productId = $_GET['productId'] ?? null;

        $stmt = $this->pdo->prepare("SELECT * FROM `websites` WHERE web_id = ?");
        $stmt->execute([$productId]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch();

            $stack = json_decode($row['stack'], true);
            $features = json_decode($row['features'], true);

            echo json_encode([
                'status' => 'success',
                'result' => $row,
                'stack' =>  $stack,
                'features' => $features
            ]);
        }
    }

    public function GetSingleWeb(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM `websites` WHERE web_id = ?");
        $stmt->execute([$data['website']]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch();

            echo json_encode([
                'status' => 'success',
                'result' => $row
            ]);
        }
    }

    
    public function consult(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        $email = $data['email'];
        $phone = $data['phone'];
        $code = $data['code'];

        if (empty($firstname) || empty($lastname) || empty($email) || empty($phone) || empty($code)){
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        if (!preg_match('/^[a-zA-Z]+$/', $firstname)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid first name'
            ]);
            return;
        }

        if (!preg_match('/^[a-zA-Z]+$/', $lastname)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid last name'
            ]);
            return;
        }

        if (!preg_match('/^[+][0-9]{1,3}$/', $code)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid country code'
            ]);
            return;
        }

        if (!preg_match('/^[0-9]{7,11}$/', $phone)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid phone number'
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

        $consultResponse = $this->sendConsultMail($firstname, $lastname, $email, $code, $phone);
        
        $consult_status = $consultResponse['status'] ?? 'unknown';
        $consult_msg = $consultResponse['message'] ?? 'unknown';

        echo json_encode([
            'status' => $consult_status,
            'message' => $consult_msg
        ]);
    }

    private function sendConsultMail($firstname, $lastname, $email, $code, $phone){

        $subject = "New Consultation Request";

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
            $mail->addAddress("osemensilas@gmail.com", "Osemen Silas Oseobonoite");

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                        
                        <h2 style='color: #1a1a1a; text-align: center; margin-bottom: 20px;'>New Consultation Request</h2>
                        
                        <p style='color: #333; line-height: 1.6;'>Hello Osemen Silas Oseobonoite,</p>
                        <p style='color: #333; line-height: 1.6;'>You have a new website creation consultation request. The user details is outlined below: </p>
                        <ul style='color: #333; line-height: 1.6;'>
                            <li><strong>First Name:</strong> {$firstname}</li>
                            <li><strong>Last Name:</strong> {$lastname}</li>
                            <li><strong>Email:</strong> {$email}</li>
                            <li><strong>Phone Code:</strong> {$code}</li>
                            <li><strong>Phone Number:</strong> {$phone}</li>
                        </ul>
                        
                        <div style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
                        Thank you for being a valued member of the <strong>IruHost</strong> community.<br>
                        Need help? Contact us at <a href='mailto:support@iruhost.com'>support@iruhost.com</a>
                        <div class='logo' style='margin-top: 20px; height: max-content; width: 100%; display: flex; justify-content: center; align-items: center;'>
                            <img src='https://iruhost.com/logo.png' alt='IruHost Logo' style='display: block; margin: 20px auto; width: 60px; height: 60px; object-fit: contain;'>
                        </div>
                    </div>
                </div>
            ";

            if ($mail->send()){
                return [
                    'status' => 'success',
                    'message' => 'Your consultation request has been sent successfully. Our team will get back to you shortly.'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Failed to send consultation request. Please try again later.'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}