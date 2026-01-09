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

$data = json_decode(file_get_contents("php://input"), true);

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => 'bicms-backend.onrender.com', 
    'secure' => true,     
    'httponly' => true,   
    'samesite' => 'None', 
]);

session_start();

if(!isset($_SESSION['user_id'])){
    echo json_encode(["message" => "Access denied. User needs to log-in first"]);
    exit;
}

$userID = $_SESSION['user_id'];

$ref = $conn->prepare("SELECT first_name, middle_name, last_name, contact, address FROM residents WHERE user_id = ?");
$ref->bind_param("i", $userID);
$ref->execute();
$result = $ref->get_result();

$info = [];

while($row = $result->fetch_assoc()){
    $info[] = [
        "firstname" => $row['first_name'],
        "middlename" => $row['middle_name'],
        "lastname" => $row['last_name'],
        "contact" => $row['contact'],
        "address" => $row['address']
    ];
}

echo json_encode($info);
$ref->close();
$conn->close();
?>
