<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET");
header("Content-Type: application/json");

require_once __DIR__ . "/config/residents_db.php";

$startdate = date('Y-m-01');
$endate = date('Y-m-t');

$statsCert = "SELECT
COUNT(CASE WHEN cert_status = 'pending' THEN 1 END) AS pending,
COUNT(CASE WHEN cert_status = 'processing' THEN 1 END) AS processing,
COUNT(CASE WHEN cert_status = 'ready' THEN 1 END) AS ready,
COUNT(CASE WHEN cert_status = 'claimed' THEN 1 END) AS claimed
FROM certificates
WHERE submitted_on BETWEEN '$startdate' AND '$endate'";

$return = [];

$total = $conn->query($statsCert);

if($total && $row = $total->fetch_assoc()){
    $pending = $row['pending'];
    $processing = $row['processing'];
    $ready = $row['ready'];
    $claimed = $row['claimed'];

    $return['success'] = true;
    $return['pending'] = $pending;
    $return['processing'] = $processing;
    $return['ready'] = $ready;
    $return['claimed'] = $claimed;
}else{
    $return['success'] = false;
    $return['message'] = "Failed to query complaints";
}

echo json_encode($return);
$total->close();
$conn->close();
?>