<?php

namespace App\Controllers\API;

require_once __DIR__ . '/../../../vendor/autoload.php';

use App\Core\DB;
use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PDO;

class AuthController{
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

    public function Register(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $firstname = strtolower($data['firstname']) ?? null;
        $lastname = strtolower($data["lastname"]) ?? null;
        $email = strtolower($data['email']) ?? null;
        $password1 = $data['password1'] ?? null;
        $password2 = $data['password2'] ?? null;
        $referredBy = $data['referer_id'] ?? null;

        if (empty($firstname) || empty($lastname) || empty($email) || empty($password1) || empty($password2)) {
            //http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        if (!preg_match('/^[a-zA-Z|| ]+$/', $firstname)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid first name'
            ]);
            return;
        }

        if (!preg_match('/^[a-zA-Z|| ]+$/', $lastname)){
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

        if (strlen($password1) < 8){
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must be at least 8 characters'
            ]);
            return;
        }

        if (!preg_match('/[A-Z]/', $password1)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one uppercase'
            ]);
            return;
        }

        if (!preg_match('/[a-z]/', $password1)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one lowercase'
            ]);
            return;
        }

        if (!preg_match('/[0-9]/', $password1)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one number'
            ]);
            return;
        }

        if (!preg_match('/[\W]/', $password1)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password must contain at least one special character'
            ]);
            return;
        }

        if ($password2 != $password1){
            echo json_encode([
                'status' => 'error',
                'message' => 'Passwords do not match'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($rows = $stmt->fetch(PDO::FETCH_ASSOC)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Email already exist'
            ]);
            return;
        }

        $userId = uniqid("IRU_");
        $password = password_hash($password1, PASSWORD_BCRYPT);
        $role = "user";
        $permission = "none";

        $stmt = $this->pdo->prepare("INSERT INTO `users`(`user_id`, `role`, `permission`, `firstname`, `lastname`, `email`, `password`, `referred_by`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$userId, $role, $permission, $firstname, $lastname, $email, $password, $referredBy]);

            $_SESSION['user'] = [
                'user_id' => $userId,
                'name' => $firstname . " " . $lastname,
                'email' => $email,
            ];

            $userSession = $_SESSION['user'];
            $this->checkCart($userSession);
            $this->checkChat($userSession);

            session_regenerate_id(true);

            $balance = 0;
            $userId = $_SESSION['user']['user_id'];
            
            $stmt = $this->pdo->prepare("INSERT INTO `account_balance`(`user_id`, `balance`) VALUES (?,?)");
            $stmt->execute([$userId, $balance]);

            echo json_encode([
                'status' => 'success',
                'message' => 'User Created Successfully'
            ]);

            $name = $firstname . " " . $lastname;

            $this->regMessage($name, $email);
        } catch (\Throwable $err) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    public function Login(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        $email = strtolower($data['email']) ?? null;
        $password = $data['password'] ?? null;

        if (!filter_var( $email, FILTER_VALIDATE_EMAIL)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email address'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if (!$stmt->rowCount() > 0){
            echo json_encode([
                'status' => 'error',
                'message' => 'User do not exist'
            ]);
            return;
        }
        
        $rows = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($password, $rows['password'])){
            echo json_encode([
                'status' => 'error',
                'message' => 'Wrong password'
            ]);
            return;
        }
        
        $_SESSION['user'] = [
            'user_id' => $rows['user_id'],
            'name' => $rows['firstname'] . " " . $rows['lastname'],
            'email' => $email,
        ];

        $userSession = $_SESSION['user'];

        session_regenerate_id(true);
        $this->checkCart($userSession);
        $this->checkChat($userSession);

        echo json_encode([
            'status' => 'success',
            'message' => 'successful'
        ]);

        $this->loginMessage($rows['firstname'] . " " . $rows['lastname'], $email);
    }

    private function checkCart($userSession){
        $userId = $_SESSION['user']['user_id'];
        $guestId = $_SESSION['guest']['id'] ?? null;

        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->execute([$guestId]);

        if ($stmt->rowCount() > 0){
            $stmt = $this->pdo->prepare("UPDATE cart SET user_id = ? WHERE user_id = ?");
            $stmt->execute([$userId, $guestId]);
        }
    }

    public function UpdateEmail(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $email = $data['email'];
        $password = $data['password'];
        $user = $_SESSION['user']['user_id'];

        if (!isset($user)){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch();
        }

        if (!password_verify($password, $row['password'])){
            echo json_encode([
                'status' => 'error',
                'message' => 'Wrong password'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("UPDATE `users` SET `email`=? WHERE user_id = ?");
        $stmt->execute([$email, $user]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Updated Successfully'
        ]);
    }

    public function UserAddress(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        if (isset($_SESSION['user'])){
            $user = $_SESSION['user']['user_id'];

            $stmt = $this->pdo->prepare("SELECT * FROM address WHERE user_id = ?");
            $stmt->execute([$user]);

            if ($stmt->rowCount() > 0){
                $userData = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'user' => [
                        'address' => ($userData['address1']),
                        'phone' => $userData['cCode'] . $userData['phone'],
                    ]
                    ]);
            }
        }
    }

    public function UpdateAddress(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $address1 = $data['address1'];
        $address2 = $data['address2'];
        $city = $data['city'];
        $state = $data['state'];
        $zip = $data['zip'];
        $country = $data['country'];
        $cCode = $data['cCode'];
        $phone = $data['phone'];
        $user = $_SESSION['user']['user_id'];

        if (!isset($user)){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        if (empty($address1) || empty($city) || empty($state) || empty($zip) || empty($country) ||
        empty($cCode) || empty($phone)
        ){
            echo json_encode(['status' => 'error', 'message' => 'Fill all required field']);
            return;
        }

        if (!preg_match('/^[+][0-9]{1,3}$/', $cCode)){
            echo json_encode(['status' => 'error', 'message' => 'Ivalid country code']);
            return;
        }

        if (!preg_match('/^[0-9]{7,12}$/', $phone)){
            echo json_encode(['status' => 'error', 'message' => 'Ivalid phone']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `address` WHERE user_id = ?");
        $stmt->execute([$user]);

        if ($stmt->rowCount() > 0){
            $stmt = $this->pdo->prepare("UPDATE `address` SET `address1`=?,`address2`=?,`city`=?,`state`=?,`country`=?,`zip`=?,`cCode`=?,`phone`=? WHERE user_id = ?");
            $stmt->execute([$address1, $address2, $city, $state, $country, $zip, $cCode, $phone, $user]);
        
            echo json_encode(['status' => 'success', 'message' => 'Address updated']);
        }else{
            $stmt = $this->pdo->prepare("INSERT INTO `address`(`user_id`, `address1`, `address2`, `city`, `state`, `country`, `zip`, `cCode`, `phone`) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$user, $address1, $address2, $city, $state, $country, $zip, $cCode, $phone]);
        
            echo json_encode(['status' => 'success', 'message' => 'Address Added']);
        }
    }

    private function checkChat($userSession){
        $userId = $_SESSION['user']['user_id'];
        $guestId = $_SESSION['guest']['id'] ?? null;

        $stmt = $this->pdo->prepare("SELECT * FROM chats WHERE user_id = ?");
        $stmt->execute([$guestId]);

        if ($stmt->rowCount() > 0){
            $stmt = $this->pdo->prepare("UPDATE chats SET user_id = ? WHERE user_id = ?");
            $stmt->execute([$userId, $guestId]);
        }
    }

    private function regMessage($name, $email){

        $subject = "Welcome to IruHost, {$name}!";

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUsername;
            $mail->Password = $this->smtpPassword;
            $mail->SMTPSecure = $this->smtpEncryption;
            $mail->Port = $this->smtpPort;

            $mail->setFrom('noreply@iruhost.com', 'IruHost');
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                        
                        <h2 style='color: #1a1a1a; text-align: center; margin-bottom: 20px;'>Login Notification</h2>
                        
                        <p style='color: #333; line-height: 1.6;'>Hello {$name},</p>
                        <p style='color: #333; line-height: 1.6;'>Your account has been successfully created. You can now manage your domains, hosting, and more from your dashboard.</p>
                        <p style='color: #333; line-height: 1.6;'>If you need any help, our support team is always here for you.</p>
                        
                        <p style='color: #333; line-height: 1.6;'>Best regards,<br>
                        The IruHost Team</p>

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
                
            } else {
                
            }
        } catch (Exception $err) {
           json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    private function loginMessage($name, $email){

        $subject = "New Login to Your IruHost Account";
        

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
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                        
                        <h2 style='color: #1a1a1a; text-align: center; margin-bottom: 20px;'>Login Notification</h2>
                        
                        <p style='color: #333; line-height: 1.6;'>Hello {$name},</p>
                        <p style='color: #333; line-height: 1.6;'>You have successfully logged in to your IruHost account.</p>
                        <p style='color: #333; line-height: 1.6;'>If this login was not initiated by you, please contact our support team immediately.</p>
                        
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
                
            } else {
                
            }
        } catch (Exception $e) {
            
        }
    }

    public function userLogout(){
        if (isset($_SESSION['user'])){
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
                return;
            }
            session_unset();
            session_destroy();
            echo json_encode(['status' => 'success', 'message' => 'Logged out']);
        }
    }
}