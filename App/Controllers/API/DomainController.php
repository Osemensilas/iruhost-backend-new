<?php

namespace App\Controllers\API;

use Dotenv\Dotenv;
use App\Core\DB;
use PDO;

class DomainController{
    protected $pdo;
    protected $dynadotApiKey;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        $this->pdo = DB::connection();
        $this->dynadotApiKey = $_ENV['DYNADOT_API_PRODUCTION_KEY'] ?? null;
    }

    public function DomainSearch(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!preg_match('/^(?!\-)(?:[a-zA-Z0-9\-]{1,63}(?<!\-)\.)+[a-zA-Z]{2,}$/', $data)){
            echo json_encode([
                'status' => 'error',
                'requested_domain' => $data,
                'response' => 'Invalid domain name'
            ]);
            return;
        }

        $domainId = uniqid('domain_');

        $stmtSearch = $this->pdo->prepare("INSERT INTO `searched_domain`(`domain_id`, `domain`) VALUES (?,?)");
        $stmtSearch->execute([$domainId, $data]);

        $tdls = array_unique([substr($data, strpos($data, '.') + 1),'com', 'org', 'net', 'xyz', 'io', 'co', 'ai', 'info', 'us', 'me']);
        $sld = substr($data, 0, strpos($data, '.'));
        $tdl = substr($data, strpos($data, '.') + 1);
        $domain = $data;
        $commission = 6000;
        $firstYearCommission = 3000;

        $domainTld = substr($data, strpos($data, '.') + 1);

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

        if (in_array($domainTld, $ngTld)){
            $this->nigerianDomain($data);
            return;
        }

        $apiKey = trim($this->dynadotApiKey);
        $domainList = array_map(fn($tld) => "{$sld}.{$tld}", $tdls);

        $queryParams = http_build_query([
            'show_price' => 'true',
            'currency' => 'NGN',
            'domain_name_list' => implode(',', $domainList)
        ]);
        $api = "https://api.dynadot.com/restful/v2/domains/bulk_search?{$queryParams}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Authorization: Bearer " . $apiKey,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($data['code'] == 200){

            if ($tdl == "com"){
                $firstYearCommission = 3000;
                $commission = 6000;
            }else{
                if($tdl == "net"){
                    $firstYearCommission = 3000;
                    $commission = 8000;
                }
            }

            $suggestions = array_map(function ($domain) use ($firstYearCommission, $commission) {
                $domain['price_list'][0]['registration_price'] = $domain['price_list'][0]['registration_price'] + $firstYearCommission;
                $domain['price_list'][0]['renewal_price'] = $domain['price_list'][0]['renewal_price'] + $commission;
                return $domain;
            }, $data["data"]["domain_result_list"]);

            echo json_encode([
                "status" => "success",
                "domain_name" => $data["data"]["domain_result_list"][0]["domain_name"],
                "available" => $data["data"]["domain_result_list"][0]["available"],
                "registration_price" => $data["data"]["domain_result_list"][0]["price_list"][0]["registration_price"] + $firstYearCommission,
                "renewal_price" => $data["data"]["domain_result_list"][0]["price_list"][0]["renewal_price"] + $commission,
                "suggestion" => $suggestions
            ]);
        }else{
            echo json_encode([
                "status" => "error",
                "data" => $data
            ]);
        }
    }

    public function SingleSearch() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!preg_match('/^(?!\-)(?:[a-zA-Z0-9\-]{1,63}(?<!\-)\.)+[a-zA-Z]{2,}$/', $data['action'])){
            echo json_encode([
                'status' => 'error',
                'requested_domain' => $data['action'],
                'response' => 'Invalid domain name'
            ]);
            return;
        }

        $domainName = $data['action'];

        $tdl = substr($domainName, strpos($domainName, '.') + 1);
        $sld = substr($domainName, 0, strpos($domainName, '.'));

        $commission = 6000;
        $firstYearCommission = 3000;

        $queryParams = http_build_query([
            'show_price' => 'true',
            'currency' => 'NGN',
        ]);
        $api = "https://api.dynadot.com/restful/v2/domains/$domainName/search?{$queryParams}";
    
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

        if ($tdl == "com"){
            $firstYearCommission = 3000;
            $commission = 6000;
        }else{
            if($tdl == "net"){
                $firstYearCommission = 3000;
                $commission = 8000;
            }
        }

        $regPrice = (float) $data['data']['price_list'][0]['registration_price'] + $firstYearCommission;
        $renewPrice = (float) $data['data']['price_list'][0]['renewal_price'] + $commission;
        
        echo json_encode([
            'status' => 'success',
            'requested_domain' => $domainName,
            'rrpCode' => $data['code'],
            'regPrice' => $regPrice,
            'renewPrice' => $renewPrice,
            'available' => $data['data']['available']
        ]);
    }

    private function nigerianDomain($domain){

        echo json_encode([
            "status" => "error",
            "domain_name" => $domain,
            "message" => "This tld is not available yet!"
        ]);
    }

    public function ExistingCheck(){

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!preg_match('/^(?!\-)(?:[a-zA-Z0-9\-]{1,63}(?<!\-)\.)+[a-zA-Z]{2,}$/', $data['action'])){
            echo json_encode([
                'status' => 'error',
                'requested_domain' => $data['action'],
                'response' => 'Invalid domain name'
            ]);
            return;
        }

        $domainName = $data['action'];

        $apiKey = trim($this->dynadotApiKey);

        $api = "https://api.dynadot.com/restful/v2/domains/{$domainName}/search";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: application/json",
            "Authorization: Bearer " . $apiKey,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if (!$response || $httpCode >= 400) {
            http_response_code(503);
            echo json_encode([
                'status' => 'error',
                'message' => 'Dynadot API request failed'
            ]);
            return;
        }

        if ($data['data']['available'] === "Yes"){
            $rrpCode = 210;
        }else{
            $rrpCode = 211;
        }

        echo json_encode([
            'status' => 'success',
            'requested_domain' => $domainName,
            'rrpCode' => $rrpCode,
        ]);
    }

    public function getDomainPrices(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $tdls = array_unique(['com', 'org', 'net', 'xyz', 'io', 'co', 'ai', 'info', 'us', 'me', 'uk']);
        $commission = 6000;
        $firstYearCommission = 3000;
        
        $queryParams = http_build_query([
            'currency' => 'NGN',
            'tlds' => implode(',', $tdls)
        ]);
        $api = "https://api.dynadot.com/restful/v2/domains/get_tld_price?{$queryParams}";

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

        if ($data['code'] == 200){

            $tdl = $data['data']['tld_price_list'];

            if ($tdl == "com"){
                $firstYearCommission = 3000;
                $commission = 6000;
            }else{
                if($tdl == "net"){
                    $firstYearCommission = 3000;
                    $commission = 8000;
                }
            }

            $result = array_map(function ($tldEntry) use ($firstYearCommission, $commission) {
                $tldEntry['all_years_register_price'][0] = $tldEntry['all_years_register_price'][0] + $firstYearCommission;
                $tldEntry['all_years_renew_price'][0] = $tldEntry['all_years_renew_price'][0] + $commission;
                return $tldEntry;
            }, $data['data']['tld_price_list']);

            echo json_encode([
                'status' => 'success',
                'result' => $result,
                'dotCom' => $data['data']['tld_price_list'][0]['all_years_register_price'][0] + $firstYearCommission,
                'dotOrg' => $data['data']['tld_price_list'][2]['all_years_register_price'][0] + $firstYearCommission,
                'dotNet' => $data['data']['tld_price_list'][5]['all_years_register_price'][0] + $firstYearCommission,
            ]);
        }else{
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch data',
                'data' => $data
            ]);
        }
    }
}