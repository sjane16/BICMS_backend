<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if(preg_match('/^http:\/\/localhost:\d+$/', $origin)|| in_array($origin, ['https://bicms.example.com'])){
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}



header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    http_response_code(200);
    exit();
}


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



session_start();

$_SESSION=[];

session_destroy();

if(ini_get("session.use_cookies")){
    $param = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $param["path"], $param["domain"],
        $param["secure"], $param["httponly"]
    );
}

echo json_encode(["success" => true, "message" => "Logout successfully!"]);
?>