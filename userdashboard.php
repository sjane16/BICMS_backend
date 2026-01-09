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


require_once __DIR__ . '/config/residents_db.php';

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
    echo json_encode(["message" => "Access Denied. User needs to log-in"]);
    exit;
}

$userID = $_SESSION['user_id'];

$result = $conn->prepare("SELECT first_name, middle_name, last_name, address, contact, dob, gender, civil_status, occupation, remarks
FROM residents WHERE user_id = ?");
$result->bind_param("i", $userID);
$result->execute();
$finalresult = $result->get_result();


$residentinfo = [];

while($row = $finalresult->fetch_assoc()){
    $residentinfo[] = [
     "firstname" => $row['first_name'],
     "last_name" => $row['last_name'],
     "address" => $row['address'],
     "contact" => $row['contact'],
     "dob" => $row['dob'],
     "gender" => $row['gender'],
     "civil_status" => $row['civil_status'],
     "occupation" => $row['occupation'],
     "remarks" => $row['remarks']
    ];
}

echo json_encode($residentinfo);
$result->close();
$conn->close();

?>