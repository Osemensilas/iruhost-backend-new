<?php

namespace App\Controllers\API;

use PDO;
use Dotenv\Dotenv;
use App\Core\DB;
use Exception;

class UserProductController{
    protected $pdo;
    protected $dynadotApiKey;
    private $userId;
    protected $mailcowApi;
    protected $mailCowUrl;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        $this->pdo = DB::connection();
        $this->dynadotApiKey = $_ENV['DYNADOT_API_PRODUCTION_KEY'] ?? null;
        $this->userId = $_SESSION['user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
        $this->mailcowApi = $_ENV['MAIL_COW_READ_WRITE_API'] ?? null;
        $this->mailCowUrl = 'https://mail.iruhost.com';
    }
    
    public function GetDashboardProducts(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ?");
        $stmt->execute([$this->userId]);

        if ($stmt->rowCount() < 1){
            echo json_encode([
                'status' => 'success',
                'user' => $_SESSION['user']['name'],
                'products' => []
            ]);
        }

        $rows = $stmt->fetchAll();

        echo json_encode([
            'status' => 'success',
            'user' => $_SESSION['user']['name'],
            'products' => $rows
        ]);
    }

    public function ExpiringProduct() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ?");
        $stmt->execute([$this->userId]);

        $expiring = [];
        $price = 0;

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($rows as $row){
                $expiryDate = $row['expiry_date'];
                $twoWeeksBefore = date('Y-m-d', strtotime('-2 weeks', strtotime($expiryDate)));
            
                $now = date('Y-m-d');

                if ($row['product'] === "domain"){

                    $domainName = $row['product_name'];

                    $tdl = substr($domainName, strpos($domainName, '.') + 1);
                    $sld = substr($domainName, 0, strpos($domainName, '.'));
                    
                    $ngTld = [
                        'ng',          // root country code
                        'com.ng',      // for commercial entities
                        'org.ng',      // for non-profits
                        'gov.ng',      // for government institutions
                        'edu.ng',      // for accredited educational institutions
                        'net.ng',      // for network providers and ISPs
                        'sch.ng',      // for primary and secondary schools
                        'name.ng',     // for individuals
                        'mobi.ng',     // for mobile services and websites
                        'mil.ng',      // for military institutions
                        'i.ng',        // for personal or individual projects
                    ];

                    if (in_array($tdl, $ngTld)){
                        $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                        $getTdlStmt->execute([$tdl]);

                        if ($getTdlStmt->rowCount() < 1){
                            $price = 0;
                        } else {
                            $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                            $price = $tldRow['renewal'];
                        }

                        $row['renewal_price'] = $price;
                    }else{
                    
                        $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                        $getTdlStmt->execute([$tdl]);

                        if ($getTdlStmt->rowCount() < 1){
                            $price = 0;
                        } else {
                            $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                            $price = $tldRow['renewal'];
                        }

                        $row['renewal_price'] = $price;
                    }
                }

                if ($row['product'] === "hosting"){


                    if ($row['product_name'] == "lite"){
                        $price = 500;
                    }

                    if ($row['product_name'] == "essential"){
                        $price = 850;
                    }

                    if ($row['product_name'] == "standard"){
                        $price = 1200;
                    }

                    if ($row['product_name'] == "plus"){
                        $price = 1600;
                    }
                
                    if ($row['product_name'] == "starter"){
                        $price = 2400;
                    }

                    if ($row['product_name'] == "growth"){
                        $price = 3500;
                    }

                    if ($row['product_name'] == "pro"){
                        $price = 5000;
                    }

                    if ($row['product_name'] == "enterprise"){
                        $price = 9000;
                    }

                    $row['renewal_price'] = $price;
                }

                if ($now >= $twoWeeksBefore) {
                    $expiring[] = $row;
                }
            }

            echo json_encode([
                'status' => 'success',
                'user' => $_SESSION['user']['name'],
                'products' => $expiring,
                'total_price' => array_sum(array_column($expiring, 'renewal_price')),
            ]);
        }
    }

    public function DomainList(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'domain']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll();
        }

        echo json_encode([
            'status' => 'success',
            'user' => $_SESSION['user']['name'],
            'products' => $rows
        ]);
    }

    public function HostingList(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'hosting']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll();
        }

        echo json_encode([
            'status' => 'success',
            'user' => $_SESSION['user']['name'],
            'products' => $rows
        ]);
    }

    public function EmailList(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'email']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'user' => $_SESSION['user']['name'],
                'products' => $rows
            ]);
        }
    }

    public function SslList(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'ssl']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'user' => $_SESSION['user']['name'],
                'products' => $rows
            ]);
        }
    }

    public function AppList() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'web app']);

        if ($stmt->rowCount() > 0){

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'user' => $_SESSION['user']['name'],
                'products' => $rows
            ]);
        }
    }

    public function VerifyRenewal(){

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $productId = $_GET['product_id'];
        $amout = $_GET['amount'];
        $transactionId = $_GET['transaction_id'];
        $txRef = $_GET['tx_ref'];

        if ($productId == "all"){
            $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ?");
            $stmt->execute([$this->userId]);

            if ($stmt->rowCount() > 0){
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            foreach($rows as $row){
                $expiryDate = $row['expiry_date'];
                $twoWeeksBefore = date('Y-m-d', strtotime('-2 weeks', strtotime($expiryDate)));
        
                $now = date('Y-m-d');

                if ($now >= $twoWeeksBefore) {
                    
                    if ($row['product'] === "domain"){
                        $domainName = $row['product_name'];

                        $tdl = substr($domainName, strpos($domainName, '.') + 1);
                        
                        $ngTld = [
                            'ng',          // root country code
                            'com.ng',      // for commercial entities
                            'org.ng',      // for non-profits
                            'gov.ng',      // for government institutions
                            'edu.ng',      // for accredited educational institutions
                            'net.ng',      // for network providers and ISPs
                            'sch.ng',      // for primary and secondary schools
                            'name.ng',     // for individuals
                            'mobi.ng',     // for mobile services and websites
                            'mil.ng',      // for military institutions
                            'i.ng',        // for personal or individual projects
                        ];

                        if (in_array($tdl, $ngTld)){
                            $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                            $getTdlStmt->execute([$tdl]);

                            if ($getTdlStmt->rowCount() < 1){
                                $price = 0;
                            } else {
                                $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                                $price = $tldRow['renewal'];
                            }
                        }else{
                        
                            $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                            $getTdlStmt->execute([$tdl]);

                            if ($getTdlStmt->rowCount() < 1){
                                $price = 0;
                            } else {
                                $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                                $price = $tldRow['renewal'];
                            }
                        }

                        if ($row['billing'] === "month") {
                            $newExpiry = date('Y-m-d', strtotime('+1 month', strtotime($row['expiry_date'])));
                        } elseif ($row['billing'] === "quarter") {
                            $newExpiry = date('Y-m-d', strtotime('+3 months', strtotime($row['expiry_date'])));
                        } elseif ($row['billing'] === "year") {
                            $newExpiry = date('Y-m-d', strtotime('+1 year', strtotime($row['expiry_date'])));
                        }


                        $stmtUpdate = $this->pdo->prepare("UPDATE `products` SET `expiry_date` = ? WHERE product_name = ?");
                        $result = $stmtUpdate->execute([$newExpiry, $domainName]);

                        $transaction = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `product`, `product_name`, `amount`, `details`, `status`) VALUES (?,?,?,?,?,?,?,?)");
                        $transaction->execute([
                            $this->userId,
                            $transactionId,
                            $txRef,
                            'domain',
                            $domainName,
                            $price,
                            "renewal of $domainName",
                            'success'
                        ]);

                        if (!$result) {
                            echo json_encode(['status' => 'error', 'message' => 'Failed to update expiry date for ' . $domainName]);
                            return;
                        }
                    }

                    if ($row['product'] === "hosting"){
                        $hostingName = $row['product_name'];
                        
                        if ($hostingName == "lite"){
                            $price = 500;
                        }

                        if ($hostingName == "essential"){
                            $price = 850;
                        }

                        if ($hostingName == "standard"){
                            $price = 1200;
                        }

                        if ($hostingName == "plus"){
                            $price = 1600;
                        }

                        if ($hostingName == "starter"){
                            $price = 2400;
                        }

                        if ($hostingName == "growth"){
                            $price = 3500;
                        }

                        if ($hostingName == "pro"){
                            $price = 5000;
                        }

                        if ($hostingName == "enterprise"){
                            $price = 9000;
                        }

                        if ($row['billing'] === "month") {
                            $newExpiry = date('Y-m-d', strtotime('+1 month', strtotime($row['expiry_date'])));
                        } elseif ($row['billing'] === "quarter") {
                            $newExpiry = date('Y-m-d', strtotime('+3 months', strtotime($row['expiry_date'])));
                        } elseif ($row['billing'] === "year") {
                            $newExpiry = date('Y-m-d', strtotime('+1 year', strtotime($row['expiry_date'])));
                        }

                        $stmtUpdate = $this->pdo->prepare("UPDATE `products` SET `expiry_date` = ? WHERE product_name = ?");
                        $result = $stmtUpdate->execute([$newExpiry, $hostingName]);

                        $transaction = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `product`, `product_name`, `amount`, `details`, `status`) VALUES (?,?,?,?,?,?,?,?)");
                        $transaction->execute([
                            $this->userId,
                            $transactionId,
                            $txRef,
                            'hosting',
                            $hostingName,
                            $price,
                            "renewal of $hostingName",
                            'success'
                        ]);

                        if (!$result) {
                            echo json_encode(['status' => 'error', 'message' => 'Failed to update expiry date for ' . $hostingName]);
                            return;
                        }
                    }
                }
            }

            echo json_encode([
                'status' => 'success',
                'message' => "Payment verified and renewal processed successfully"
            ]);

            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE product_id = ?");
        $stmt->execute([$productId]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row['product'] === "domain"){
                $domainName = $row['product_name'];

                $tdl = substr($domainName, strpos($domainName, '.') + 1);
                
                $ngTld = [
                    'ng',          // root country code
                    'com.ng',      // for commercial entities
                    'org.ng',      // for non-profits
                    'gov.ng',      // for government institutions
                    'edu.ng',      // for accredited educational institutions
                    'net.ng',      // for network providers and ISPs
                    'sch.ng',      // for primary and secondary schools
                    'name.ng',     // for individuals
                    'mobi.ng',     // for mobile services and websites
                    'mil.ng',      // for military institutions
                    'i.ng',        // for personal or individual projects
                ];

                if (in_array($tdl, $ngTld)){
                    $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                    $getTdlStmt->execute([$tdl]);

                    if ($getTdlStmt->rowCount() < 1){
                        $price = 0;
                    } else {
                        $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                        $price = $tldRow['renewal'];
                    }
                }else{
                
                    $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                    $getTdlStmt->execute([$tdl]);

                    if ($getTdlStmt->rowCount() < 1){
                        $price = 0;
                    } else {
                        $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                        $price = $tldRow['renewal'];
                    }
                }

                if ($row['billing'] === "month") {
                    $newExpiry = date('Y-m-d', strtotime('+1 month', strtotime($row['expiry_date'])));
                } elseif ($row['billing'] === "quarter") {
                    $newExpiry = date('Y-m-d', strtotime('+3 months', strtotime($row['expiry_date'])));
                } elseif ($row['billing'] === "year") {
                    $newExpiry = date('Y-m-d', strtotime('+1 year', strtotime($row['expiry_date'])));
                }

                $stmtUpdate = $this->pdo->prepare("UPDATE `products` SET `expiry_date` = ? WHERE product_id = ?");
                $result = $stmtUpdate->execute([$newExpiry, $row['product_id']]);

                $transaction = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `product`, `product_name`, `amount`, `details`, `status`) VALUES (?,?,?,?,?,?,?,?)");
                $transaction->execute([
                    $this->userId,
                    $transactionId,
                    $txRef,
                    'domain',
                    $domainName,
                    $price,
                    "renewal of $domainName",
                    'success'
                ]);

                if (!$result) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update expiry date for ' . $domainName]);
                    return;
                }
            }

            if ($row['product'] === "hosting"){
                $hostingName = $row['product_name'];
                
                if ($hostingName == "lite"){
                    $price = 500;
                }

                if ($hostingName == "essential"){
                    $price = 850;
                }

                if ($hostingName == "standard"){
                    $price = 1200;
                }

                if ($hostingName == "plus"){
                    $price = 1600;
                }

                if ($hostingName == "starter"){
                    $price = 2400;
                }

                if ($hostingName == "growth"){
                    $price = 3500;
                }

                if ($hostingName == "pro"){
                    $price = 5000;
                }

                if ($hostingName == "enterprise"){
                    $price = 9000;
                }

                if ($row['billing'] === "month") {
                    $newExpiry = date('Y-m-d', strtotime('+1 month', strtotime($row['expiry_date'])));
                } elseif ($row['billing'] === "quarter") {
                    $newExpiry = date('Y-m-d', strtotime('+3 months', strtotime($row['expiry_date'])));
                } elseif ($row['billing'] === "year") {
                    $newExpiry = date('Y-m-d', strtotime('+1 year', strtotime($row['expiry_date'])));
                }

                $stmtUpdate = $this->pdo->prepare("UPDATE `products` SET `expiry_date` = ? WHERE product_id = ?");
                $result = $stmtUpdate->execute([$newExpiry, $row['product_id']]);

                $transaction = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `product`, `product_name`, `amount`, `details`, `status`) VALUES (?,?,?,?,?,?,?,?)");
                $transaction->execute([
                    $this->userId,
                    $transactionId,
                    $txRef,
                    'hosting',
                    $hostingName,
                    $price,
                    "renewal of $hostingName",
                    'success'
                ]);

                if (!$result) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update expiry date for ' . $domainName]);
                    return;
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Payment verified and renewal processed successfully"
        ]);
    }

    public function CreateEmailAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
            return;
        }

        print_r($this->userId);

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $username  = trim($data['mailbox'] ?? '');
        $domain    = trim($data['domain'] ?? '');
        $password  = $data['password'] ?? '';
        $productId = $data['id'] ?? '';

        if (!$username || !$domain || !$password || !$productId) {
            echo json_encode([
                'status' => 'error',
                'message' => 'All fields are required'
            ]);
            return;
        }

        /*
        * Get purchased product
        */
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM products
            WHERE user_id = ?
            AND product_id = ?
        ");

        $stmt->execute([
            $this->userId,
            $productId
        ]);

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Product not found'
            ]);
            return;
        }

        /*
        * Determine mailbox limit
        */
        $allowedMailboxes = match ($product['product_name']) {
            'Starter'      => 1,
            'Professional' => 5,
            'Premium'      => 10,
            'Enterprise'   => 30,
            default        => 0
        };

        if ($allowedMailboxes === 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email plan'
            ]);
            return;
        }

        /*
        * Count existing mailboxes
        */
        $productMail = $this->pdo->prepare("
            SELECT *
            FROM iruap_professional_mails
            WHERE product_id = ?
            AND user_id = ?
        ");

        $productMail->execute([
            $productId,
            $this->userId
        ]);

        $mailboxCount = $productMail->rowCount();

        if ($mailboxCount >= $allowedMailboxes) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Maximum mailbox limit reached'
            ]);
            return;
        }

        /*
        * Create mailbox on Mailcow
        */
        try {

            $mailcowResult = $this->createMailCowMailBox(
                $domain,
                $username,
                $password
            );

            /*
            * Check Mailcow response
            */
            if (!$mailcowResult['success']) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $mailcowResult['message']
                ]);
                return;
            }

            /*
            * Save mailbox in your database
            */
            $emailId = uniqid("email_");
            $password = password_hash($password, PASSWORD_BCRYPT);

            $insert = $this->pdo->prepare("
                INSERT INTO iruap_professional_mails
                (
                    user_id,
                    product_id,
                    email_id,
                    mailbox,
                    domain,
                    password
                )
                VALUES (?, ?, ?, ?,?,?)
            ");

            $insert->execute([
                $this->userId,
                $productId,
                $emailId,
                $username,
                $domain,
                $password
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Email account created successfully',
                'email' => $username . '@' . $domain
            ]);

        } catch (Exception $e) {

            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function FetchEmailAccount(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
            return;
        }

        if (!isset($_SESSION['user'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid user'
            ]);
            return;
        }

        $domain = $_GET['domain'] ?? null;

        if (!$domain) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Domain is required'
            ]);
            return;
        }

        $mailboxes = $this->getMailboxesByDomain($domain);

        $theMail = [];

        foreach ($mailboxes as $mailbox) {
            $theMail[] = $mailbox['username'];
        }

        echo json_encode([
            'status' => 'success',
            'mails' => $theMail,
            'message' => "emails retrieved successfully"
        ]);
    }

    public function DeleteEmailAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
            return;
        }

        if (!isset($_SESSION['user'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid user'
            ]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $mailbox = $data['mailbox'] ?? '';

        if (!$mailbox || !filter_var($mailbox, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid mailbox'
            ]);
            return;
        }

        // Delete mailbox from Mailcow
        $mailcowResult = $this->deleteMailCowMailbox($mailbox);

        if (!$mailcowResult['success']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to connect to Mailcow',
                'details' => $mailcowResult['message']
            ]);
            return;
        }

        // Check Mailcow response
        $mailcowResponse = $mailcowResult['response'];

        $mailcowItem = $mailcowResponse[0] ?? null;

        if (
            !$mailcowItem ||
            ($mailcowItem['type'] ?? '') !== 'success'
        ) {
            echo json_encode([
                'status' => 'error',
                'message' => $mailcowItem['msg']
                    ?? 'Failed to delete mailbox from Mailcow',
                'http_code' => $mailcowResult['http_code'],
                'mailcow_response' => $mailcowResponse
            ]);
            return;
        }

        // Mailcow deletion succeeded.
        // Now delete the mailbox from your database.

        $stmt = $this->pdo->prepare("
            DELETE FROM `iruap_professional_mails`
            WHERE `email` = ?
            AND `user_id` = ?
        ");

        $result = $stmt->execute([
            $mailbox,
            $this->userId
        ]);

        if (!$result) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Mailbox deleted from Mailcow but failed to delete from database'
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Mailbox deleted successfully'
        ]);
    }

    private function createMailCowMailBox(
        string $domain,
        string $localPart,
        string $password,
        int $quotaMb = 5120
    ): array {

        $mailCowUrl = $this->mailCowUrl;
        $apiKey = $this->mailcowApi;

        $payload = [
            'active' => '1',
            'domain' => $domain,
            'local_part' => $localPart,
            'name' => $localPart,
            'password' => $password,
            'password2' => $password,
            'quota' => (string) $quotaMb,
            'force_pw_update' => '0',
            'tls_enforce_in' => '1',
            'tls_enforce_out' => '1'
        ];

        $ch = curl_init(
            $mailCowUrl . '/api/v1/add/mailbox'
        );

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $apiKey
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            throw new Exception($error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $result = json_decode($response, true);

        /*
        * Mailcow normally returns an array
        * describing whether the operation succeeded.
        */
        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'message' => 'Mailcow returned HTTP ' . $httpCode,
                'response' => $result
            ];
        }

        return [
            'success' => true,
            'message' => 'Mailbox created',
            'response' => $result
        ];
    }

    private function getMailboxesByDomain(string $domain): array
    {
        $mailCowUrl = $this->mailCowUrl;
        $apiKey = $this->mailcowApi;

        $ch = curl_init(
            $mailCowUrl . '/api/v1/get/mailbox/all/' . urlencode($domain)
        );

        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $apiKey
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception(
                'Mailcow returned HTTP ' . $httpCode
            );
        }

        return json_decode($response, true) ?? [];
    }

    private function deleteMailCowMailbox(string $mailbox): array
    {
        $mailCowUrl = $this->mailCowUrl;
        $apiKey = $this->mailcowApi;

        $payload = [
            'items' => [
                $mailbox
            ]
        ];

        $ch = curl_init(
            rtrim($mailCowUrl, '/') . '/api/v1/delete/mailbox'
        );

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-Key: ' . $apiKey
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            return [
                'success' => false,
                'message' => $error
            ];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $result = json_decode($response, true);

        return [
            'success' => true,
            'http_code' => $httpCode,
            'response' => $result
        ];
    }
}