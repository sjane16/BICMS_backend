<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once __DIR__ . '/config/residents_db.php';

$data = json_decode(file_get_contents("php://input"), true);

$actualcompdate = $data['actualCompDate'] ? : null;
$compID = $data['complaintId'];
$agreementstats = ucwords(strtolower($data['agreementStatus'])) ? : null;
$complaintstats = $data['complaintStatus'] ? : null;

$insert = $conn->prepare("UPDATE resolutions SET actualcompliance_date = ?, resolution_status = ? WHERE complaint_id = ?");
$insert->bind_param("ssi", $actualcompdate, $agreementstats, $compID);

if($insert->execute()){
    echo json_encode(["message"=> "Successfully saved"]);
}else{
    echo json_encode(["message" => "Error:", $insert->error]);
}

$update_comp = $conn->prepare("UPDATE complaints SET complaint_status = ? WHERE complaint_id = ?");
$update_comp->bind_param("si", $complaintstats, $compID);
$update_comp->execute();

$insert->close();
$update_comp->close();
$conn->close();
?>