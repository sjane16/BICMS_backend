<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

$allowed_origins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'https://bicms.example.com'
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

$search_resID = $conn->prepare("SELECT resident_ID FROM residents WHERE user_id = ?");
$search_resID->bind_param("i", $userID);
$search_resID->execute();
$search_resID->bind_result($resident_ID);
$search_resID->fetch();
$search_resID->close();

if(!$resident_ID){
    echo json_encode("Resident isn't found");
    exit;
}

$firstname = ucwords(trim(strtolower($data['firstname'])));
$middlename = ucwords(trim((strtolower($data['middlename']))));
$lastname = ucwords(trim(strtolower($data['lastname'])));
$contact = trim($data['contact']);
$address = ucwords(trim(strtolower($data['address'])));
$type = ucwords(trim(strtolower($data['category'])));
$subject = ucwords(trim(strtolower($data['subject'])));
$description = trim((strtolower($data['description'])));
$respondentfname = ucwords(trim(strtolower($data['respondentfname'])));
$respondentmname = ucwords(trim(strtolower($data['respondentmname'])));
$respondentlname = ucwords(trim(strtolower($data['respondentlname'])));
$relationship = $data['relationship'];
$respondentAddress = ucwords(trim(strtolower($data['respondentAddress'])));
$dateIncident = $data['dateIncident'];


$response = [];

$update = $conn->prepare("UPDATE residents SET last_name = ?, first_name = ?, middle_name =?, contact=?, address=? WHERE resident_ID = ?");
$update->bind_param("sssssi", $lastname, $firstname, $middlename, $contact, $address, $resident_ID);

if($update->execute()){
    $response['resident_update'] = "Resident info updated";
}else{
    $response['resident_update'] = "Resident info failed to update";
}

$insert = $conn->prepare("INSERT INTO complaints(subject, type, description, resident_ID, respondent_fname, respondent_mname, respondent_lname, relationship, respondent_address, incident_date) VALUES(?,?,?,?,?,?,?,?,?,?)");
$insert->bind_param("sssissssss", $subject, $type, $description, $resident_ID, $respondentfname, $respondentmname, $respondentlname, $relationship, $respondentAddress, $dateIncident);

if($insert->execute()){
    $response['success'] = true;
    $response['message'] = "Complaint submitted";
}else{
    $response['success'] = false;
    $response['message'] = "Complaint submission fails";
}

echo json_encode($response);

$update->close();
$insert->close();
$conn->close();
?>