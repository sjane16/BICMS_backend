<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type"); 
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once __DIR__ . '/config/residents_db.php';

$data = json_decode(file_get_contents("php://input"), true);

$compID = isset($data['id']) ? intval($data['id']): null;
$status = isset($data['status']) ? $data['status']: null;

if (!$compID || !$status){
    echo json_encode(["success" => false, "message" => "Invalid ID or status"]);
    exit;
}

    $update = $conn->prepare("UPDATE complaints SET complaint_status = ? WHERE complaint_id = ?");
    $update->bind_param("si", $status, $compID);

if($update->execute()){
    echo json_encode(["success" => true, "message" => "Generation of certificate to file action is complete"]);
}else{
    echo json_encode(["success" => false, "message" => "Database error:" . $update->error]);
}

$update->close();
$conn->close();
?>