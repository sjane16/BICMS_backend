<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET");
header("Content-Type: application/json");

require_once __DIR__ . "/config/residents_db.php";

$startdate = date('Y-m-01');
$endate = date('Y-m-t');

$statsComp = "SELECT
COUNT(*) AS totalComp,
COUNT(CASE WHEN complaint_status = 'pending' THEN 1 END) AS pending,
COUNT(CASE WHEN complaint_status = 'in progress' THEN 1 END) AS progress,
COUNT(CASE WHEN complaint_status = 'resolved' THEN 1 END) AS resolved,
COUNT(CASE WHEN complaint_status = 'dismissed' THEN 1 END) AS dismissed
FROM complaints
WHERE submitted_on BETWEEN '$startdate' AND '$endate'";

$return = [];

$total = $conn->query($statsComp);

if($total && $row = $total->fetch_assoc()){
    $totalComp = $row['totalComp'];
    $pending = $row['pending'];
    $progress = $row['progress'];
    $resolved = $row['resolved'];
    $dismissed = $row['dismissed'];
    $resolvedPercent = $totalComp > 0 ? round(($resolved / $totalComp) * 100, 2) : 0;

    $return['success'] = true;
    $return['pending'] = $pending;
    $return['progress'] = $progress;
    $return['resolved'] = $resolved;
    $return['dismissed'] = $dismissed;
    $return['resolvedPercent'] = $resolvedPercent;
}else{
    $return['success'] = false;
    $return['message'] = "Failed to query complaints";
}

echo json_encode($return);
$total->close();
$conn->close();
?>