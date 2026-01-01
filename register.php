<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (preg_match('/^http:\/\/(localhost|127\.0\.0\.1):\d+$/', $origin) ||
    $origin === 'https://bicms.example.com') {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/residents_db.php';

$data = json_decode(file_get_contents("php://input"), true);

if(!$data || !isset($data['lastName'], $data['firstName'], $data['middleName'], $data['email'], $data['password'])){
    echo json_encode(["message" => "Missing or invalid input data"]);
    exit;
}

$lastname = ucwords(strtolower(trim($data['lastName'])));
$firstname = ucwords(strtolower(trim($data['firstName'])));
$middlename = ucwords(strtolower(trim($data['middleName'])));
$email = trim($data['email']);
$password = $data['password'];

$checkuser = $conn->prepare("SELECT resident_ID FROM residents WHERE first_name = ? AND last_name = ? AND middle_name = ?");
$checkuser->bind_param("sss", $firstname, $lastname, $middlename);
$checkuser->execute();
$result = $checkuser->get_result();
$resident = $result->fetch_assoc();
$checkuser->close();

if(!$resident){
    echo json_encode(["status" => "fail", "message" => "Resident isn't found in the system"]);
    exit;
}

$residentID = $resident['resident_ID'];


function isValidPassword($password){
    return preg_match('/^(?=.*[A-Z])(?=.*[\W_]).{8,}$/', $password);
}

if(!isValidPassword($password)){
    echo json_encode(["message" => "Password must be at least 8 characters long, include one uppercase letter and one special character"]);
    exit;
}

$emailcheck = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$emailcheck->bind_param("s", $email);
$emailcheck->execute();
$emailcheck->bind_result($count);
$emailcheck->fetch();
$emailcheck->close();

if($count > 0){
    echo json_encode(["message" => "Email already registered"]);
    exit;
}

$hashedpassword = password_hash($password, PASSWORD_DEFAULT);

$insertdb = $conn->prepare("INSERT INTO users (email, last_name, first_name, middle_name, password) VALUES(?, ?, ?, ?, ?)");
$insertdb->bind_param("sssss", $email, $lastname, $firstname, $middlename, $hashedpassword);

if($insertdb->execute()){
    $newuserID = $conn->insert_id;

    $updateResident = $conn->prepare("UPDATE residents SET user_id = ? WHERE resident_id = ?");
    $updateResident->bind_param("ii", $newuserID, $residentID);
    $updateResident->execute();
    $updateResident->close();

    echo json_encode(["status" => "success", "message" => "Registration successful"]);
}else{
    echo json_encode(["message" => "Something went wrong. Please try again later"]);
}

$insertdb->close();
$conn->close();
?>