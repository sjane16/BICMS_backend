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

$firstname = $data['firstname'];
$middlename = $data['middlename'];
$lastname = $data['lastname'];
$password = $data['password'];
$contact = $data['contactNo'];
$email = $data['email'];

$hashedpassword = password_hash($password, PASSWORD_DEFAULT);

$response = [];

$update = $conn->prepare("UPDATE users SET email = ?, last_name = ?, first_name = ?, middle_name = ?, password = ? WHERE user_id = ?");
$update->bind_param("sssssi", $email, $lastname, $firstname, $middlename, $hashedpassword, $userID);

if($update->execute()){
    $response['success'] = true;
    $response['message'] = "Users information is successfully updated";
}else{
    $response['success'] = false;
    $response['message'] = "Users information failed to update";
}

$updateRes = $conn->prepare("UPDATE residents SET last_name = ?, first_name = ?, middle_name = ?,contact = ? WHERE user_id = ?");
$updateRes->bind_param("ssssi", $lastname, $firstname, $middlename, $contact, $userID);

if($updateRes->execute()){
    $response['success'] = true;
    $response['message'] = "Residents information is successfully updated";
}else{
    $response['success'] = false;
    $response['message'] = "Residents information failed to update";
}

echo json_encode($response);
$update->close();
$updateRes->close();
$conn->close();
?>