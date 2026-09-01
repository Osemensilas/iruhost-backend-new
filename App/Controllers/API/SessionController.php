<?php

namespace App\Controllers\API;

use App\Core\DB;
use PDO;
use Dotenv\Dotenv;

class SessionController{
    protected $pdo;
    private $publicKey;
    
    public function __construct(){
        $this->pdo = DB::connection();

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        $this->publicKey = $_ENV['FLUTTERWAVE_PUBLIC_KEY'] ?? null;
        //$this->publicKey = "FLWPUBK_TEST-ea3991777877ae8c494e5d206d286b33-X";
    }

    public function UserSession(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        if (isset($_SESSION['user'])){
            echo json_encode([
                'success' => true,
                'user' => $_SESSION['user']
            ]);
        }else{
            echo json_encode([
                "success" => false,
                "message" => "No active session"
            ]);
        }
    }

    public function UserData(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        if (isset($_SESSION['user'])){
            $user = $_SESSION['user']['user_id'];

            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user]);

            $ref = uniqid("ref_");

            if ($stmt->rowCount() > 0){
                $userData = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'user' => [
                        'name' => ($userData['firstname'] . " " . $userData['lastname']),
                        'email' => $userData['email'],
                        'user_id' => $userData['user_id'],
                        'pbk' => $this->publicKey,
                        'ref' => $ref,
                    ]
                    ]);
            }
        }
    }

    public function acctBal(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        if (isset($_SESSION['user'])){
            $user = $_SESSION['user']['user_id'];

            $stmt = $this->pdo->prepare("SELECT * FROM account_balance WHERE user_id = ?");
            $stmt->execute([$user]);

            if ($stmt->rowCount() > 0){
                $userData = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'user' => [
                        'balance' => ($userData['balance']),
                    ]
                    ]);
            }
        }
    }
}