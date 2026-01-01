<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/residents_db.php';

$data = json_decode(file_get_contents("php://input"), true);

if(!isset($data['lastName'], $data['firstName'], $data['middleName'], $data['gender'], $data['birthday'], $data['age'], $data['address'], $data['civilStatus'], $data['contactNumber'], $data['occupation'], $data['remarks'])){
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

if(!$data || !is_array($data)){
    echo json_encode(["message" => "Invalid input data"]);
    exit;
}

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



$check = $conn->prepare("SELECT * FROM residents WHERE first_name = ? AND last_name = ? AND dob = ?");
$check->bind_param("sss", $firstname, $lastname, $birthday);
$check->execute();
$check->store_result();

if($check->num_rows > 0){
   echo json_encode(["success" => false, "message" => "Resident already exist"]);
   exit;
}

$check->close();

$insertdb = $conn->prepare("INSERT INTO residents (last_name, first_name, middle_name, gender, dob, age, address, civil_status, contact, occupation, remarks)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$insertdb->bind_param("sssssisssss", $lastname, $firstname, $middlename, $gender, $birthday, $age, $address, $civilstatus, $contactnumber, $occupation, $remarks);

if($insertdb->execute()){
    echo json_encode(["success" => true, "message" => "Resident is successfully added!"]);
}else{
    echo json_encode(["success" => false, "message" => "Error:" . $insertdb->error]);
}

$insertdb->close();
$conn->close();

?>