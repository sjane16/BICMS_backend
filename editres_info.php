<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/residents_db.php';

$data = json_decode(file_get_contents("php://input"), true);

if(!$data || !is_array($data)){
    echo json_encode(["message" => "Invalid input data"]);
    exit;
}

$resident_ID = $data['resident_ID'];
$lastname = ucwords(strtolower(trim($data['lastName'])));
$firstname = ucwords(strtolower(trim($data['firstName'])));
$middlename = ucwords(strtolower(trim($data['middleName'])));
$gender = $data['gender'];
$birthday = $data['birthday'];
$age = (int) trim($data['age']);
$address = ucwords(strtolower(trim($data['address'])));
$civilstatus = $data['civilStatus'];
$contactnumber = trim($data['contactNumber']);
$occupation = ucwords(strtolower(trim($data['occupation'])));
$remarks = $data['remarks'];



$update = $conn->prepare("UPDATE residents SET 
last_name = ?, 
first_name = ?,
middle_name = ?,
gender = ?,
dob = ?,
age = ?,
address = ?,
civil_status = ?,
contact = ?,
occupation = ?,
remarks = ? WHERE resident_ID = ?");
$update->bind_param("sssssisssssi", $lastname, $firstname, $middlename, $gender, $birthday, $age, $address, $civilstatus, $contactnumber, $occupation, $remarks, $resident_ID);

if (!$update) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}

if($update->execute()){
    echo json_encode(["success" => true, "message" => "Edit saved"]);
}else{
    echo json_encode(["success" => false, "message" => "Error:" . $update->error]);
}

$update->close();
$conn->close();
?>