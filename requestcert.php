<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowed_origins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'https://bicms.vercel.app'
];

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    
}

header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/config/residents_db.php";

$data = json_decode(file_get_contents("php://input"), true);

session_start();

if(!isset($_SESSION['user_id'])){
    echo json_encode(["message" => "Access Denied. User needs to log-in"]);
    exit;
}

$userID = $_SESSION['user_id'];

$search = $conn->prepare("SELECT resident_ID FROM residents WHERE user_id = ?");
$search->bind_param("i", $userID);
$search->execute();
$search->bind_result($resident_ID);
$search->fetch();
$search->close();

if(!$resident_ID){
    echo json_encode(["message" => "Resident not found"]);
    exit;
}

$restrict = "SELECT COUNT(*) as total_request 
FROM certificates
WHERE resident_ID = ? AND
MONTH(submitted_on) = MONTH(CURRENT_DATE())
AND YEAR(submitted_on) = YEAR(CURRENT_DATE())";

$count = $conn->prepare($restrict);
$count->bind_param("i", $resident_ID);
$count->execute();
$result_count = $count->get_result();
$row_count = $result_count->fetch_assoc();

$total_req = $row_count['total_request'];

if($total_req >= 5){
    echo json_encode(["message" => "Only up to five request of certificates per month"]);
    exit;
}

$comp_restrict = "SELECT COUNT(*) AS all_complaints
FROM complaints 
WHERE resident_ID = ?
AND complaint_status NOT IN ('resolved', 'dismissed')
AND submitted_on <= DATE_SUB(NOW(), INTERVAL 1 YEAR)";

$search_comp = $conn->prepare($comp_restrict);
$search_comp->bind_param("i", $resident_ID);
$search_comp->execute();
$res_comp = $search_comp->get_result();
$row_comp = $res_comp->fetch_assoc();

$complaint = $row_comp['all_complaints'];

if($complaint > 0){
    echo json_encode(["message" => "You can not request certificate. You have an unresolved complaint older than 1 year"]);
    exit;
}

$response = [];
$type = $data['type'];
$firstname = ucwords(trim(strtolower($data['firstname'])));
$middlename = ucwords(trim(strtolower($data['middlename'])));
$lastname = ucwords(trim(strtolower($data['lastname'])));
$contact = trim($data['contact']);
$address = ucwords(trim(strtolower($data['address'])));
$purpose = ucwords(trim(strtolower($data['purpose'])));

$update = $conn->prepare("UPDATE residents SET last_name =?, first_name = ?, middle_name = ?, address = ?, contact = ? WHERE resident_ID = ?");
$update->bind_param("sssssi", $lastname, $firstname, $middlename, $address, $contact, $resident_ID);

if($update->execute()){
    $response["message"] = "Resident info successfully updated";
}else{
    $response["message"] = "Resident info failed to update";
}

$insert = $conn->prepare("INSERT INTO certificates(type, purpose, resident_ID) VALUES (?,?,?)");
$insert->bind_param("ssi", $type, $purpose, $resident_ID);

if($insert->execute()){
    $response["success"] = true;
    $response["message"] = "Request submitted";
}else{
    $response["success"] = false;
    $response["message"] = "Request submission failed";
}

echo json_encode($response);
$update->close();
$insert->close();
$conn->close();
?>