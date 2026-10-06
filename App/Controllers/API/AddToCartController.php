<?php

namespace App\Controllers\API;

use App\Core\DB;
use Exception;

class AddToCartController{

    private $pdo;
    private $userId;
    private $myKey;
    protected $dynadotApiKey;

    public function __construct() {
        $this->pdo = DB::connection();
        $this->myKey = "3079601359d46e924bfbab85"; 
        $this->userId = $_SESSION['user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
        $this->dynadotApiKey = $_ENV['DYNADOT_API_PRODUCTION_KEY'] ?? null;
    }
    public function AddDomain() {
       
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data['cartDomainName'] ?? null;
        $domainPrice = $data['cartDomainPrice'] ?? null;
        $domainRenew = $data['cartDomainRenew'] ?? null;
        $domainDuration = $data['cartDomainDuration'] ?? null;
        $currency = $data['currency'] ?? null;
        $product = 'Domain Registration';
        $billing = 'year';
        $productId = uniqid("domain_");

        if (!$domainName || !$domainPrice || !$domainRenew || !$domainDuration) {
            echo json_encode(['status' => 'error', 'message' => 'Missing domain details']);
            return;
        }

        // Prevent duplicate domain for same user
        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE product_name = ? AND user_id = ?");
        $stmt->execute([$domainName, $this->userId]);

        if ($stmt->fetch()) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Domain already exists in your cart'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`,`currency`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)");

        try {
            $stmt->execute([$this->userId, $productId, $product, $domainName, $domainPrice, $domainRenew, $billing, $domainName, $currency]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Domain added to cart'
            ]);
        } catch (Exception $err) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    public function AddHosting(){
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data['domainName'] ?? null;
        $domainPrice = $data['domainPrice'] ?? null;
        $domainRenew = $data['domainRenew'] ?? null;
        $domainOp = $data['domainOperation'] ?? null;
        $currency = $data['currency'] ?? null;
        $domainDuration = 1;
        $domainId = uniqid("domain_");
        $domainProduct = 'Domain Registration';
        $domainBilling = 'year';

        $hostingName = $data['hosting'] ?? null;
        $hostingPrice = $data['hostingPrice'] ?? null;
        $hostingRenew = $data['billing'] ?? null;

        if (!$domainName || !$domainPrice || !$domainRenew || !$domainDuration) {
            echo json_encode(['status' => 'error', 'message' => 'Missing domain details']);
            return;
        }

        if ($domainName == '-'){
            echo json_encode(['status' => 'error', 'message' => 'Missing domain details']);
            return;
        }

        if ($domainOp === 'existing'){
            $this->addOld($this->userId, $domainName, $hostingName, $hostingPrice, $hostingRenew, $currency);
            return;
        }
        
        // Prevent duplicate domain for same user
        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE product_name = ? AND user_id = ?");
        $stmt->execute([$domainName, $this->userId]);

        if ($stmt->fetch()) {
            $this->addAny($this->userId, $domainName, $hostingName, $hostingPrice, $hostingRenew, $currency);
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$this->userId, $domainId, $domainProduct, $domainName, $domainPrice, $domainRenew, $domainBilling, $domainName, $currency]);

            $this->addAny($this->userId, $domainName, $hostingName, $hostingPrice, $hostingRenew, $currency);
        
        } catch (Exception $err) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    private function addAny($userId, $domainName, $hostingName, $hostingPrice, $hostingRenew, $currency) {

        $productId = uniqid('hosting_');
        $product = "Hosting Registration";
        $renewPrice = $hostingPrice;

        if ($hostingName === "Lite" && $hostingRenew === "month"){
            $renewPrice = 500;
        }

        if ($hostingName === "Standard" && $hostingRenew === "month"){
            $renewPrice = 850;
        }

        if ($hostingName === "Essential" && $hostingRenew === "month"){
            $renewPrice = 1200;
        }

        if ($hostingName === "Plus" && $hostingRenew === "month"){
            $renewPrice = 1600;
        }
        
        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try{
            $stmt->execute([$userId, $productId, $product, $hostingName, $hostingPrice, $renewPrice, $hostingRenew, $domainName, $currency]);
        
            echo json_encode([
                'status' => 'success',
                'message' => 'Hosting added to cart'
            ]);
        }catch(Exception $err){
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    private function addOld($userId, $domainName, $hostingName, $hostingPrice, $hostingRenew, $currency) {

        $productId = uniqid('hosting_');
        $product = "Hosting Registration";
        $renewPrice = $hostingPrice;

        if ($hostingName === "Lite" && $hostingRenew === "month"){
            $renewPrice = 500;
        }

        if ($hostingName === "Standard" && $hostingRenew === "month"){
            $renewPrice = 850;
        }

        if ($hostingName === "Essential" && $hostingRenew === "month"){
            $renewPrice = 1200;
        }

        if ($hostingName === "Plus" && $hostingRenew === "month"){
            $renewPrice = 1600;
        }
        
        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try{
            $stmt->execute([$userId, $productId, $product, $hostingName, $hostingPrice, $renewPrice, $hostingRenew, $domainName, $currency]);
        
            echo json_encode([
                'status' => 'success',
                'message' => 'Hosting added to cart'
            ]);
        }catch(Exception $err){
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    public function AddSSL(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $sslName = $data['product_name'] ?? null;
        $sslPrice = $data['price'] ?? null;
        $sslRenew = $data['price'] ?? null;
        $currency = $data['currency'] ?? null;
        $sslDuration = 1;
        $sslId = uniqid("ssl_");
        $sslProduct = 'SSL Registration';
        $sslBilling = 'year';
        $sslDomain = '';

        if (!$sslName || !$sslPrice || !$sslRenew || !$sslDuration) {
            echo json_encode(['status' => 'error', 'message' => 'Missing SSL details']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE product_name = ? AND user_id = ?");
        $stmt->execute([$sslName, $this->userId]);

        if ($stmt->fetch()) {
            echo json_encode([
                'status' => 'success',
                'mesage' => 'SSL added to cart'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$this->userId, $sslId, $sslProduct, $sslName, $sslPrice, $sslRenew, $sslBilling, $sslDomain, $currency]);
            
            echo json_encode([
                'status' => 'success',
                'mesage' => 'SSL added to cart'
            ]);
        } catch (Exception $err) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    public function AddEmail(){

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $emailName = $data['package'];
        $currency = "NGN";
        $emailDuration = 1;
        $emailId = uniqid("email_");
        $emailProduct = 'Email Registration';
        $emailBilling = 'year';
        $emailDomain = $data['domain'];
        $emailPrice = $data['amount'] ?? null;
        $emailRenew = $data['amount'] ?? null;

        if (!$emailName || !$emailPrice || !$emailRenew || !$emailDuration) {
            echo json_encode(['status' => 'error', 'message' => 'Missing Email details']);
            return;
        }


        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        try {
            $stmt->execute([$this->userId, $emailId, $emailProduct, $emailName, $emailPrice, $emailRenew, $emailBilling, $emailDomain, $currency]);
            
            echo json_encode([
                'status' => 'success',
                'mesage' => 'Email added to cart'
            ]);
        } catch (Exception $err) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    public function AddWebsite(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM `websites` WHERE web_id = ?");
        $stmt->execute([$data['website']]);

        if ($stmt->rowCount() > 0){
            $website = $stmt->fetch();

            $cartId = uniqid('prod_');
            $currency = "NGN";
            $renew = 0;

            $stmt = $this->pdo->prepare("SELECT * FROM `cart` WHERE domain = ? AND user_id = ?");
            $stmt->execute([$data['website'], $this->userId]);
        
            if (!$stmt->rowCount() > 0){
                $stmt = $this->pdo->prepare("INSERT INTO `cart`(`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$this->userId, $cartId, 'Web application', 'web app', $website['price'], $renew, '', $data['website'], $currency]);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Product added to cart'
                ]);
            }else{
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Product already in cart'
                ]);
            }
        }
    }

    public function AddCustomWebsite(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $cartId = uniqid('prod_');
        $price = $data['websitePrice'];
        $renew = $data['renewPrice'];
        $currency = $data['currency'];

        $stmt = $this->pdo->prepare("INSERT INTO `cart`(`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`) VALUES (?,?,?,?,?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $cartId, 'Custom web application', 'web app', $price, $renew, 'year', 'website', $currency]);

        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'Error adding to cart'
            ]);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Product added to cart',
            'data' => $data
        ]);
    }

    public function TranferDomain(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data['action'] ?? null;
        $auth = $data['auth'] ?? null;
        $product = 'Domain Transfer';
        $billing = 'year';
        $productId = uniqid("domain_");

        $tdl = substr($domainName, strpos($domainName, '.') + 1);
        $sld = substr($domainName, 0, strpos($domainName, '.'));

        $api = "https://api.dynadot.com/restful/v2/domains/{$domainName}/search";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Authorization: Bearer " . $this->dynadotApiKey,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        $currency = 'NGN';

        print_r($data);

        $domainPrice = "";

        $domainRenewal = "";
        
        $stmt = $this->pdo->prepare("INSERT INTO `cart`(`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`,`currency`) VALUES (?,?,?,?,?,?,?,?)");
        //$stmt->execute([$this->userId, $productId, $product, $domainName, $domainPrice, $domainRenewal, $billing, $auth, $currency]);


        echo json_encode([
            'status' => 'successful',
            'message' => 'Added to cart',
        ]);
    }
}