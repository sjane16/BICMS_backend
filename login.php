<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (preg_match('/^http:\/\/(localhost|127\.0\.0\.1):\d+$/', $origin) ||
    $origin === 'https://bicms.vercel.app') {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/config/residents_db.php";

$data = json_decode(file_get_contents("php://input"), true);

if(!$data || !isset($data['email'], $data['password'])){
    echo json_encode(["message" => "Missing or invalid input data"]);
    exit;
}


$email = strtolower(trim($data['email']));
$password = $data['password'];

$check = $conn->prepare("SELECT password, first_name, last_name, role, user_id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if($check->num_rows === 1){
    $check->bind_result($hashedpassword, $firstname, $lastname, $role, $userID);
    $check->fetch();

    if(password_verify($password, $hashedpassword)){
        $fullname = $firstname . " " . $lastname;

        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'domain' => 'bicms-backend.onrender.com', 
            'secure' => true,     
            'httponly' => true,   
            'samesite' => 'None', 
        ]);

        session_start();
        $_SESSION['user_id'] = $userID;
        $_SESSION['fullname'] = $fullname;

        echo json_encode(["status" => "success", "fullname" => $fullname, "role" => $role, "message" => "Login successful!"]);
    }else{
        echo json_encode(["status" => "failure", "message" => "Incorrect password"]);
    }
}else{
    echo json_encode(["status" => "failure", "message" => "Email not found"]);
}

$check->close();
$conn->close();


?>