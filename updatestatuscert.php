<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type"); 
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once __DIR__ . '/config/residents_db.php';

$data = json_decode(file_get_contents("php://input"), true);

$certID = isset($data['id']) ? intval($data['id']): null;
$newStatus = isset($data['status']) ? $data['status']: null;

if (!$certID || !in_array($newStatus, ['processing', 'ready', 'claimed'])) {
    echo json_encode(["success" => false, "message" => "Invalid ID or status"]);
    exit;
}

if($newStatus === "processing"){
    $update = $conn->prepare("UPDATE certificates SET cert_status = ?, issued = NOW() WHERE certificate_id = ?");
    $update->bind_param("si", $newStatus, $certID);
}else{
    $update = $conn->prepare("UPDATE certificates SET cert_status = ? WHERE certificate_id = ?");
    $update->bind_param("si", $newStatus, $certID);
}

if($update->execute()){
    echo json_encode(["success" => true, "message" => "Status updated successfully", "updated_status" => $newStatus]);
}else{
    echo json_encode(["success" => false, "message" => "Database error:" . $update->error]);
}

$update->close();
$conn->close();
?>