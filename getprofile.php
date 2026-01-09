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
    http_response_code(403);
    exit("Origin not allowed");
}

header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    http_response_code(200);
    exit();
}


require_once __DIR__ . '/config/residents_db.php';

$data = json_decode(file_get_contents("php://input"), true);

session_start();

if(!isset($_SESSION['user_id'])){
    echo json_encode(["message" => "Access Denied. User needs to log-in"]);
    exit;
}

$userID = $_SESSION['user_id'];

$searchuser = $conn->prepare("SELECT u.first_name, u.middle_name, u.last_name, u.email, r.contact 
FROM users u JOIN residents r ON u.user_id = r.user_id WHERE u.user_id = ?");
$searchuser->bind_param("i", $userID);
$searchuser->execute();
$result = $searchuser->get_result();

$userinfo = [];

while($row = $result->fetch_assoc()){
    $userinfo[] = [
        "firstname" => $row['first_name'],
        "middlename" => $row['middle_name'],
        "lastname" => $row['last_name'],
        "email" => $row['email'],
        "contact" => $row['contact']
    ];
}

echo json_encode($userinfo);
$searchuser->close();
$conn->close();
?>