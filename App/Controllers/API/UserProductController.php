<?php

namespace App\Controllers\API;

use PDO;
use Dotenv\Dotenv;
use App\Core\DB;

class UserProductController{
    protected $pdo;
    protected $dynadotApiKey;
    private $userId;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        $this->pdo = DB::connection();
        $this->dynadotApiKey = $_ENV['DYNADOT_API_PRODUCTION_KEY'] ?? null;
        $this->userId = $_SESSION['user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
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

    public function VerifyRenewal(){

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $dollarRateStmt = $this->pdo->prepare("SELECT * FROM `currency` WHERE currency = ?");
        $dollarRateStmt->execute(['naira']);

        $dollarRate = $dollarRateStmt->fetch(PDO::FETCH_ASSOC);

        $dollarValue = $dollarRate['value'];

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
}