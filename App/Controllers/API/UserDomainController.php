<?php

namespace App\Controllers\API;

use Dotenv\Dotenv;
use App\Core\DB;
use PDO;

class UserDomainController
{
    protected $pdo;
    protected $dynadotApiKey;
    protected $userId;
    protected $enomUserId;
    protected $enomApiToken;
    protected $whogohostUsername;
    protected $whogohostApi;
    protected $iruHostDns1;
    protected $iruHostDns2;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        $this->pdo = DB::connection();
        $this->dynadotApiKey = $_ENV['DYNADOT_API_PRODUCTION_KEY'] ?? null;
        $this->userId = $_SESSION['user']['user_id'] ?? null;
        $this->enomUserId = $_ENV['ENOM_USER_ID'] ?? null;
        $this->enomApiToken = $_ENV['ENOM_API_TOKEN'] ?? null;
        $this->whogohostUsername = $_ENV['WHOGOHOST_USERNAME'] ?? null;
        $this->whogohostApi = $_ENV['WHOGOHOST_API'] ?? null;
        $this->iruHostDns1 = $_ENV['IRUHOST_DNS1'] ?? null;
        $this->iruHostDns2 = $_ENV['IRUHOST_DNS2'] ?? null;
    }

    public function UpdateDns(){
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data) || empty($data['domain'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Domain is required'
            ]);
            return;
        }

        $domainName = strtolower(trim($data['domain']));
        $provider = $data['dnsProvide'] ?? '';

        if ($provider === 'iruhost') {
            $nameservers = [
                $this->iruHostDns1 ?? 'ns1.iruhost.com',
                $this->iruHostDns2 ?? 'ns2.iruhost.com'
            ];
        } else {
            $nameservers = [];

            for ($i = 1; $i <= 4; $i++) {
                $ns = trim($data["nameserver$i"] ?? '');

                if ($ns !== '') {
                    $nameservers[] = $ns;
                }
            }
        }

        if (count($nameservers) < 2) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'At least two nameservers are required'
            ]);
            return;
        }

        // Build Dynadot API parameters.
        $params = [
            'key' => $this->dynadotApiKey,
            'command' => 'set_ns',
            'domain' => $domainName
        ];

        foreach ($nameservers as $index => $nameserver) {
            $params['ns' . $index] = $nameserver;
        }

        $url = 'https://api.dynadot.com/api3.json?'
            . http_build_query($params);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($response === false || $curlError !== '') {
            http_response_code(502);

            echo json_encode([
                'status' => 'error',
                'message' => 'Could not connect to Dynadot',
                'details' => $curlError
            ]);
            return;
        }

        $result = json_decode($response, true);
        $apiResult = $result['SetNsResponse'] ?? [];

        if (
            $httpCode >= 200 &&
            $httpCode < 300 &&
            ($apiResult['Status'] ?? '') === 'success' &&
            (string)($apiResult['ResponseCode'] ?? '') === '0'
        ) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Nameservers updated successfully',
                'domain' => $domainName,
                'nameservers' => $nameservers
            ]);
        } else {
            http_response_code(502);

            echo json_encode([
                'status' => 'error',
                'message' => 'Dynadot could not update the nameservers',
                'details' => $apiResult['Error']
                    ?? $apiResult['Status']
                    ?? 'Unexpected API response'
            ]);
}


        // $tdl = substr($domainName, strpos($domainName, '.') + 1);
        // $sld = substr($domainName, 0, strpos($domainName, '.'));

        // $tlds = ['ng', 'com.ng', 'org.ng', 'gov.ng', 'edu.ng', 'net.ng', 'sch.ng', 'name.ng', 'mobi.ng', 'mil.ng', 'i.ng'];

        // if (in_array($tdl, $tlds)){
        //     $endpoint = "https://www.whogohost.com/host/modules/addons/DomainsReseller/api/index.php";
        //     $action = "/domains/$domainName/nameservers";
        //     $params = [
        //         "domain"    => $domainName,
        //         "nameservers" => [
        //             $dns1,
        //             $dns2,
        //         ],
        //     ];

        //     $headers = [
        //         "username: $this->whogohostUsername",
        //         "token: ". base64_encode(hash_hmac("sha256", "$this->whogohostApi", "$this->whogohostUsername:".gmdate("y-m-d H")))
        //     ];

        //     $curl = curl_init();

        //     curl_setopt($curl, CURLOPT_URL, "{$endpoint}{$action}");
        //     curl_setopt($curl, CURLOPT_POST, true);
        //     curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //     curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        //     curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        //     $response = curl_exec($curl);
        //     curl_close($curl);

        //     print_r($response);
            
        //     return;
        // }

        // $url = "https://reseller.enom.com/interface.asp?command=ModifyNS&uid=$this->enomUserId&pw=$this->enomApiToken&sld=$sld&tld=$tdl&ns1=$dns1&ns2=$dns2&ns3=$dns3&ns4=$dns4&responsetype=xml";
    
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // $response = curl_exec($ch);
        // curl_close($ch);

        // $xml = simplexml_load_string($response);

        // $rrpCode = (int) $xml->RRPCode;
        // $rrpText = (string) $xml->RRPText;

        // if ($rrpCode === 200){
        //     echo json_encode([
        //         'msg' => 'success',
        //         'value' => $rrpText
        //     ]);
        // }else{
        //     echo json_encode([
        //         'msg' => 'unsuccess',
        //         'value' => $rrpText
        //     ]);
        // }
    }

    public function UpdateHost(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data["domain"];
        $type = $data["type"];
        $host = $data["host"];
        $value = $data["value"];
        $type2 = $data["type2"];
        $host2 = $data["host2"];
        $value2 = $data["value2"];

        $tdl = substr($domainName, strpos($domainName, '.') + 1);
        $sld = substr($domainName, 0, strpos($domainName, '.'));

        $url = "https://reseller.enom.com/interface.asp?command=SetHosts&uid=$this->enomUserId&pw=$this->enomApiToken&SLD=$sld&TLD=$tdl&HostName1=$host&RecordType1=$type&Address1=$value&HostName2=$host2&RecordType2=$type2&Address2=$value2&&responsetype=xml";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        $errorCount = (int) $xml->ErrCount;

        if ($errorCount === 0){
            echo json_encode([
                'msg' => 'success',
                'value' => $errorCount
            ]);
        }
    }

    public function GetHost(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data["domain"];

        $tdl = substr($domainName, strpos($domainName, '.') + 1);
        $sld = substr($domainName, 0, strpos($domainName, '.'));

        $url = "https://reseller.enom.com/interface.asp?command=GetHosts&uid=$this->enomUserId&pw=$this->enomApiToken&SLD=$sld&TLD=$tdl&ResponseType=XML";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        if (isset($xml->host)) {
            foreach ($xml->host as $host) {
                $name = (string) $host->name;
                $type = (string) $host->type;
                $address = (string) $host->address;

                $hostRecords[] = [
                    'name' => $name,
                    'type' => $type,
                    'address' => $address
                ];
            }

            echo json_encode([
                "status" => "success",
                "response" => $hostRecords
            ]);
        }
    }

    public function GetDomainDetails(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data["domain"] ?? null;

        $getDomainName = $this->pdo->prepare("SELECT * FROM products WHERE product_name = ? AND domain = ?");
        $getDomainName->execute([$domainName, $domainName]);

        if ($getDomainName->rowCount() > 0){
            $domainRow = $getDomainName->fetch();
        }

        $expiration = date('Y-m-d', strtotime($domainRow['expiry_date']));
        $registration = date('Y-m-d', strtotime($domainRow['created_at']));

        $currentDate = date('Y-m-d');

        if ($currentDate < $expiration) {
            $status = "Active";
        } elseif ($currentDate == $expiration) {
            $status = "Expired";
        } else {
            $status = "Expired";
        }

        $results = [
            'expiration' => $expiration,
            'registration' => $registration,
            'currentData' => $currentDate,
            'status' => $status
        ];
        
        echo json_encode([
            'msg' => 'success',
            'value' => $results
        ]);
    }

    public function GetDomainLockStatus(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domain = $data['domain'];

        $tld = substr($domain, strpos($domain, '.') + 1);
        $sld = substr($domain, 0, strpos($domain, '.'));

        $url = "https://reseller.enom.com/interface.asp?command=GetDomainInfo&uid=$this->enomUserId&pw=$this->enomApiToken&sld=$sld&tld=$tld&responsetype=xml";
        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        $transferLock = null; // default

        // Loop through all <entry> elements
        foreach ($xml->GetDomainInfo->services->entry as $entry) {
            if ((string)$entry['name'] === 'irtpsettings') {
                $transferLock = (string)$entry->irtpsetting->transferlock;
                break;
            }
        }

        echo json_encode([
            'status' => 'success',
            'result' => $transferLock
        ]);
    }

    public function ChangeOwnership () {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domain = $data['domain'];
        $newEmail = $data['email'];

        if (empty($domain) || empty($newEmail)){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$newEmail, 'user']);

        if (!$stmt->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'User do not exist']);
            return;
        }

        $row = $stmt->fetch();

        $sessionId = $_SESSION['user']['user_id'];
        $newUser = $row['user_id'];

        $domainStmt = $this->pdo->prepare("SELECT * FROM products WHERE domain = ?");
        $domainStmt->execute([$domain]);

        if (!$domainStmt->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'Domain do not exist']);
            return;
        }

        $domainRow = $domainStmt->fetch();

        if ($_SESSION['user']['user_id'] != $domainRow['user_id']){
            echo json_encode(['status' => 'error', 'message' => 'Domain not yours']);
            return;
        }

        $stmt = $this->pdo->prepare("UPDATE products SET user_id = ? WHERE domain = ?");
        $result = $stmt->execute([$newUser, $domain]);

        if ($result){
            echo json_encode([
                'status' => 'success',
                'message' => 'Ownership Changed Successfully'
            ]);
        }
    }

    public function DomainManager(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domain = $data['domain'];
        $newEmail = $data['email'];

        if (empty($domain) || empty($newEmail)){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)){
            echo json_encode(['status' => 'error', 'message' => 'All field required']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$newEmail, 'user']);

        if (!$stmt->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'User do not exist']);
            return;
        }

        $row = $stmt->fetch();

        $sessionId = $_SESSION['user']['user_id'];
        $newUser = $row['user_id'];

        $domainStmt = $this->pdo->prepare("SELECT * FROM products WHERE domain = ? AND domain = ?");
        $domainStmt->execute([$domain, $domain]);

        if (!$domainStmt->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'Domain do not exist']);
            return;
        }

        $domainRow = $domainStmt->fetch();

        if ($_SESSION['user']['user_id'] != $domainRow['user_id']){
            echo json_encode(['status' => 'error', 'message' => 'You can not add manager']);
            return;
        }

        $productId = $domainRow['product_id'];
        $product = $domainRow['product'];
        $url = $domainRow['url'];
        $text = $domainRow['text'];
        $exp = $domainRow['expiry_date'];


        $stmtCheck = $this->pdo->prepare("SELECT * FROM manager WHERE domain = ? AND manager_id = ?");
        $stmtCheck->execute([$domain, $newUser]);

        if ($stmtCheck->rowCount() > 0){
            echo json_encode(['status' => 'error', 'message' => 'User already a manager']);
            return;
        }

        $stmtAdd = $this->pdo->prepare("INSERT INTO `manager`(`user_id`, `product_id`, `manager_id`, `product`, `domain`, `url`, `text`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?)");
        $addResult = $stmtAdd->execute([$sessionId, $productId, $newUser, $product, $domain, $url, $text, $exp]);

        if (!$addResult){
            echo json_encode(['status' => 'error', 'message' => 'Error adding manager']);
        }
        echo json_encode(['status' => 'success', 'message' => 'User now a manager']);
    }

    public function UnlockDomain(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domain = $data['domain'];
        $status = $data['lock'];

        $tld = substr($domain, strpos($domain, '.') + 1);
        $sld = substr($domain, 0, strpos($domain, '.'));

        $url = "";

        if ($status === "False"){
            $url = "https://reseller.enom.com/interface.asp?command=setreglock&uid=$this->enomUserId&pw=$this->enomApiToken&sld=$sld&tld=$tld&unlockregistrar=1&responsetype=xml";
        }

        if ($status === "True"){
            $url = "https://reseller.enom.com/interface.asp?command=setreglock&uid=$this->enomUserId&pw=$this->enomApiToken&sld=$sld&tld=$tld&unlockregistrar=0&responsetype=xml";
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        print_r($xml);
        
        print_r($data);
    }

}