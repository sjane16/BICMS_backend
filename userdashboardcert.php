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

session_start();

if(!isset($_SESSION['user_id'])){
    echo json_encode(["message" => "Access Denied. User needs to log-in"]);
    exit;
}

$userID = $_SESSION['user_id'];

$resID = $conn->prepare("SELECT resident_ID FROM residents WHERE user_id = ?");
$resID->bind_param("i", $userID);
$resID->execute();
$resID->bind_result($resident_ID);
$resID->fetch();
$resID->close();

if(!$resident_ID){
    echo json_encode(["message" => "Resident not found"]);
    exit;
}

$search = $conn->prepare("SELECT type, submitted_on, cert_status FROM certificates WHERE resident_ID = ?
AND submitted_on >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$search->bind_param("i", $resident_ID);
$search->execute();
$result = $search->get_result();

$certificates = [];

while($row = $result->fetch_assoc()){
    $certificates[] = [
        "type" => $row['type'],
        "certdate" => $row['submitted_on'],
        "status" => $row['cert_status']
    ];
}

echo json_encode($certificates);
$search->close();
$conn->close();
?>