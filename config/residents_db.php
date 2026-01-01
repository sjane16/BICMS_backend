<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowed_origins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'https://BICMS.vercel.app'
];

if(in_array($origin, $allowed_origins)){
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Credentials: true");
}


header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$db_host = getenv('DB_HOST') ?: "localhost";
$db_user = getevn('DB_USER') ?: "root";
$db_pass = getenv('DB_PASS') ?: "";
$db_name = getenv('DB_NAME') ?: "residents_db";
$db_port = getenv('DB_PORT') ?: "3306";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if($conn->connect_error){
    echo json_encode(["message" => "Database connection failed" . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8mb4");
?>