<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/config/residents_db.php";

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');
$result = $conn->query("
    SELECT report_type, period, generation_date, generated_by 
    FROM report_logs 
    WHERE generation_date BETWEEN '$today 00:00:00' AND '$today 23:59:59'
    AND report_type = 'Certificate Report'
");

$reportinfo = [];

while($row = $result->fetch_assoc()){
    $reportinfo[] = [
        "report_type" => $row['report_type'],
        "period" => $row['period'],
        "generation_date" => $row['generation_date'],
        "generated_by" => $row['generated_by']
    ];
}

echo json_encode($reportinfo);
?>