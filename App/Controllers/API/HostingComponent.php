<?php

namespace App\Controllers\API;

use PDO;
use App\Core\DB;

class HostingComponent{
    protected $pdo;

    public function __construct(){
        $this->pdo = DB::connection();
    }

    public function FetchSharedHostingPlans(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM hosting_list WHERE category = ?");
        $stmt->execute(["shared_hosting"]);

        if ($stmt->rowCount() < 1){
            echo json_encode(['status' => 'error', 'message' => 'No Hosting Available']);
            return;
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "hosting" => $rows
        ]);
    }
}