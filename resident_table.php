<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/config/residents_db.php";

$result = $conn->query("SELECT r.*,
(SELECT COUNT(*) FROM complaints c WHERE r.resident_ID = c.resident_ID) AS complaint_count,
(SELECT COUNT(*) FROM certificates cer WHERE r.resident_ID = cer.resident_ID) AS certificate_count
FROM residents r ORDER BY r.resident_ID ASC");

$residents = [];

while($row = $result->fetch_assoc()){
    $residents[] = [
        "resident_ID" => $row['resident_ID'],
        "last_name" => $row['last_name'],
        "first_name" => $row['first_name'],
        "middle_name" => $row['middle_name'],
        "gender" => $row['gender'],
        "dob" => $row['dob'],
        "age" => $row['age'],
        "address" => $row['address'],
        "civil_status" => $row['civil_status'],
        "contact" => $row['contact'],
        "occupation" => $row['occupation'],
        "remarks" => $row['remarks'],
        "complaint_count" => $row['complaint_count'],
        "certificate_count" => $row['certificate_count']
    ];
}

echo json_encode($residents);
?>