<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/config/residents_db.php";

$complaintStats = $conn->query("SELECT 
    COUNT(*) AS total,
    COUNT(CASE WHEN complaint_status = 'processing' THEN 1 END) AS processing,
    COUNT(CASE WHEN complaint_status = 'resolved' THEN 1 END) AS resolved
    FROM complaints
");

$complaintData = $complaintStats->fetch_assoc();

$certificateStats = $conn->query("SELECT 
    COUNT(*) AS total,
    COUNT(CASE WHEN cert_status = 'processing' THEN 1 END) AS processing,
    COUNT(CASE WHEN cert_status = 'ready' THEN 1 END) AS ready,
    COUNT(CASE WHEN cert_status = 'claimed' THEN 1 END) AS claimed
    FROM certificates 
");

$certificateData = $certificateStats->fetch_assoc();

$residentStats = $conn->query("SELECT COUNT(*) AS total FROM residents");
$residentData = $residentStats->fetch_assoc();

echo json_encode([
    "complaints" => $complaintData,
    "certificates" => $certificateData,
    "residents" => $residentData
]);

$complaintStats->close();
$certificateStats->close();
$residentStats->close();
$conn->close();
?>