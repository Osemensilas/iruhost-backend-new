<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

// Create guest session if user not logged in
if (!isset($_SESSION['user'])) {
    $_SESSION['guest'] = [
        'id' => session_id(),
        'started_at' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];
}

// ------------------------------------------------------------
// 🔐 CORS CONFIGURATION
// ------------------------------------------------------------
header('Content-Type: application/json');

$allowedOrigins = [
    'http://localhost:3000',
];

// Get the Origin header
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Check if origin is in allowed list
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    
    // Add additional headers for better browser support
    header("Access-Control-Max-Age: 86400"); // 24 hours cache for preflight
    header("Vary: Origin"); // Important for caching responses with different origins
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No content needed for OPTIONS
    exit();
}

// Log debugging information
error_log("Request Origin: " . ($origin ?: 'NO ORIGIN'));
error_log("Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'NO METHOD'));
error_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'NO URI'));

// ------------------------------------------------------------
// 🧩 BOOTSTRAP APPLICATION
// ------------------------------------------------------------
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Initialize Router
use App\Core\Router;
$router = new Router();

// Load route definitions
require_once __DIR__ . '/../routes/web.php';

// Resolve and handle request
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Handle the route
$router->resolve($requestUri, $method);