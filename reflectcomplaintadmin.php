<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");


require_once __DIR__ . "/config/residents_db.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$query = "SELECT c.subject, c.submitted_on,
CONCAT(r.first_name, ' ', r.last_name) AS fullname
FROM complaints c 
INNER JOIN residents r ON c.resident_ID = r.resident_ID
ORDER BY c.submitted_on DESC
";

$result = $conn->query($query);

$complaints = [];

if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $complaints[] =[
            "fullname" => $row['fullname'],
            "subject" => $row['subject'],
            "date" => $row['submitted_on']
        ];
    }
}

echo json_encode($complaints);
$conn->close();
?>