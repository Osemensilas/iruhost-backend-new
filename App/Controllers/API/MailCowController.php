<?php

namespace App\Controllers\API;

use Dotenv\Dotenv;
use App\Core\DB;
use Exception;
use PDO;

class MailCowController
{
    protected $encryptionKey;
    protected $encryptionIV;
    protected $mailcowApi;
    protected $mailCowUrl;
    protected $userId;
    protected $pdo;

    public function __construct(){

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        if (!isset($_ENV['ENOM_USER_ID'])) {
            die("Dotenv failed to load. Path: " . __DIR__ . '/../../../');
        }

        $this->mailcowApi = $_ENV['MAIL_COW_READ_WRITE_API'] ?? null;
        $this->mailCowUrl = 'https://mailcow.iruhost.com';
        $this->userId = $_SESSION['user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
        $this->pdo = DB::connection();
        $this->encryptionKey = hash('sha256', $_ENV['ENCRYPTION_KEY']);
        $this->encryptionIV = substr(hash('sha256', $_ENV['ENCRYPTION_IV']), 0, 16);
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