<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowed_origins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'https://bicms.vercel.app'
];

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    
}

header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/config/residents_db.php";

session_start();

if(!isset($_SESSION['user_id'])){
    echo json_encode(["message" => "User needs to login"]);
    exit;
}

$userID = $_SESSION['user_id'];

$get = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
$get->bind_param("i", $userID);
$get->execute();
$get->bind_result($profilepic);
$get->fetch();
$get->close();

if(!$profilepic){
    echo json_encode(["profile_picture" => "profile.png"]);
}else{
    echo json_encode(["profile_picture" => $profilepic]);
}

$conn->close();
?>