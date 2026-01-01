<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: Application/json");

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

$search = $conn->prepare("SELECT user_id FROM residents WHERE resident_id = ?");
$search->bind_param("i", $resident_ID);
$search->execute();
$search->bind_result($user_id);
$search->fetch();
$search->close();

$delete = $conn->prepare("DELETE FROM residents WHERE resident_ID = ?");
$delete->bind_param("i", $resident_ID);

if($delete->execute()){
    if($user_id){
        $deleteuser = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $deleteuser->bind_param("i", $user_id);
        $deleteuser->execute();
        $deleteuser->close();
    }
    echo json_encode(["success" => true, "message" => "Resident is deleted on the system"]);
}else{
    echo json_encode(["success" => false, "message" => "Error:" . $delete->error]);
}

$delete->close();
$conn->close();
?>