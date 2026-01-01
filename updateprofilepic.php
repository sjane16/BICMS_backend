<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowed_origins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'https://bicms.example.com'
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

$data = json_decode(file_get_contents("php://input"), true);

session_start();

if(!isset($_SESSION['user_id'])){
    echo json_encode(["message" => "Access Denied. User needs to log-in"]);
    exit;
}

$userID = $_SESSION['user_id'];

if(isset($_FILES['profileImage'])){
    $file = $_FILES['profileImage'];
    $dir = "profile_pic/";
    $filename = uniqid() . "_" . basename($file["name"]);
    $final = $dir . $filename;

    if(move_uploaded_file($file["tmp_name"], $final)){
        $upload = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $upload->bind_param("si", $filename, $userID);
        $upload->execute();
        echo json_encode(["message" => "Image uploaded" , "filename" => $filename]);
    }else{
        echo json_encode(["message" => "Upload failed"]);
    }
}else{
    echo json_encode(["message" => "No file received"]);
}


?>