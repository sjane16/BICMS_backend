<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/config/residents_db.php";

$query = "SELECT
c.certificate_id AS id,
c.type,
c.purpose,
c.cert_status,
c.submitted_on,
c.issued,
CONCAT(r.first_name, ' ', LEFT(r.middle_name,1), '.', ' ', r.last_name) AS fullname,
r.contact,
r.address
FROM certificates c
JOIN residents r ON c.resident_ID = r.resident_ID
WHERE c.submitted_on >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
ORDER BY c.submitted_on DESC";


 $certificates = [];

 $result = $conn->query($query);

 if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $certificates[] = [
            "fullname" => $row['fullname'],
            "contact" => $row['contact'],
            "address" => $row['address'],
            "type" => $row['type'],
            "purpose" => $row['purpose'],
            "date" => $row['submitted_on'],
            "id" =>$row['id'],
            "cert_status" => $row['cert_status'],
            "issued" => $row['issued']
        ];
    }
 }

 if (!$result) {
    echo json_encode(["error" => $conn->error]);
    exit;
}


 echo json_encode($certificates);
 $conn->close();
?>