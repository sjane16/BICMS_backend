<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (preg_match('/^http:\/\/(localhost|127\.0\.0\.1):\d+$/', $origin) || $origin === 'https://bicms.example.com') {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/residents_db.php';
require_once __DIR__ . '/vendor/autoload.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["message" => "Access Denied. User needs to log-in"]);
    exit;
}

$userID = $_SESSION['user_id'];

$searchUser = $conn->prepare("SELECT CONCAT(first_name, ' ', LEFT(middle_name, 1), '.', ' ', last_name) AS admin_name FROM users WHERE user_id = ?");
$searchUser->bind_param("i", $userID);
$searchUser->execute();
$searchUser->store_result();
$searchUser->bind_result($username);
$searchUser->fetch();
$searchUser->close();

date_default_timezone_set('Asia/Manila');

$startdate = date('Y-m-01');
$enddate   = date('Y-m-t');
$startrep  = date('F 1, Y');
$endrep    = date('F t, Y');

$date = new DateTime();
$day = $date->format("j");
$month = $date->format("F");
$year = $date->format("Y");
$period = $month . ' ' . $year;

$report_type = "Complaint Report";
$generation_date = $date->format('Y-m-d H:i:s');
$date_generated  = $date->format('F j, Y');

$reportlog = $conn->prepare("INSERT INTO report_logs(report_type, period, generation_date, generated_by) VALUES(?,?,?,?)");
$reportlog->bind_param("ssss", $report_type, $period, $generation_date, $username);
$reportlog->execute();

$summary = "SELECT 
    COUNT(CASE WHEN complaint_status = 'resolved' THEN 1 END ) AS resolved,
    COUNT(CASE WHEN complaint_status IN ('pending', 'processing') THEN 1 END) AS ongoing
FROM complaints
WHERE submitted_on BETWEEN '$startdate' AND '$enddate'";

$resultSummary = $conn->query($summary);
if ($resultSummary && $row = $resultSummary->fetch_assoc()) {
    $resolved = $row['resolved'];
    $ongoing  = $row['ongoing'];
}

$totals = "SELECT 
    COUNT(*) AS total_complaints,
    COUNT(CASE WHEN complaint_status = 'resolved' THEN 1 END) AS total_resolved,
    COUNT(CASE WHEN complaint_status = 'in progress' THEN 1 END) AS total_processing,
    COUNT(CASE WHEN complaint_status = 'pending' THEN 1 END) AS total_pending,
    COUNT(CASE WHEN complaint_status = 'dismissed' THEN 1 END) AS total_fileaction
FROM complaints
WHERE submitted_on BETWEEN '$startdate' AND '$enddate'";

$resultTotals = $conn->query($totals);
if ($resultTotals && $row = $resultTotals->fetch_assoc()) {
    $total_complaints  = $row['total_complaints'];
    $total_resolved    = $row['total_resolved'];
    $total_processing  = $row['total_processing'];
    $total_pending     = $row['total_pending'];
    $total_fileaction  = $row['total_fileaction'];
}

$type = "SELECT 
    COUNT(CASE WHEN type = 'Noise Complaint' THEN 1 END) AS total_noise,
    COUNT(CASE WHEN type = 'Sanitation Issue' THEN 1 END) AS total_sanitation,
    COUNT(CASE WHEN type = 'Property/Neighbor Dispute' THEN 1 END) AS total_dispute,
    COUNT(CASE WHEN type = 'Infrastructure Problem' THEN 1 END) AS total_infras,
    COUNT(CASE WHEN type NOT IN ('Noise Complaint', 'Sanitation Issue', 'Property/Neighbor Dispute', 'Infrastructure Problem') THEN 1 END) AS total_others
FROM complaints
WHERE submitted_on BETWEEN '$startdate' AND '$enddate'";

$sumtype = $conn->query($type);
if ($sumtype && $row = $sumtype->fetch_assoc()) {
    $total_noise      = $row['total_noise'];
    $total_sanitation = $row['total_sanitation'];
    $total_dispute    = $row['total_dispute'];
    $total_infras     = $row['total_infras'];
    $total_others     = $row['total_others'];
}

$types = [
    'Noise Disturbance'          => $total_noise,
    'Sanitation Issue'           => $total_sanitation,
    'Property/Neighbor Dispute'  => $total_dispute,
    'Infrastructure Problem'     => $total_infras,
    'Others'                     => $total_others
];

arsort($types);
$top = array_slice(array_keys($types), 0, 2);
$remark = "Most complaints were about " . implode(" and ", $top) . ".";
$resolutionrate = $total_complaints > 0 ? ($total_resolved / $total_complaints) * 100 : 0;

$customTempDir = '/tmp/mpdf';

if(!file_exists($customTempDir)){
    mkdir($customTempDir, 0777, true);
}

$pdf = new \Mpdf\Mpdf([
    'tempDir' => $customTempDir,
    'format'       => 'A4',
    'margin_left'  => 20,
    'margin_right' => 20,
    'margin_bottom'=> 20,
    'margin_top'   => 20
]);

$background = __DIR__ . '/assets/summon_letter.png';
$pdf->SetDefaultBodyCSS('background', "url($background) no-repeat center center");
$pdf->SetDefaultBodyCSS('background-image-resize', 6);

$html1  = "<div style='text-align: center; font-family: Arial, sans-serif; padding-top: 130px;'>";
$html1 .= "<h1 style='text-align: center; font-size: 25px; margin-bottom: 20px;'>Barangay Complaint Monthly Report</h1>";
$html1 .= "<p style='text-align: left; font-size: 16px;'>
<strong>Month Covered: </strong>$month $year <br>
<strong>Date Generated:</strong> $date_generated <br>
<strong>Prepared by:</strong> $username <br>
<strong>Barangay:</strong> Barangay 134, Zone 11, District 1
</p>";
$html1 .= "<hr>";
$html1 .= "<h1 style='text-align: center; font-size: 18px; margin-bottom: 5px;'>Summary</h1>";
$html1 .= "<table style='width: 100%; border-collapse: collapse; font-size: 16px; margin: 20px 0;'>";
$html1 .= "<thead> 
<tr> 
<th style='border: 1px solid #000; padding: 8px;'>Complaints</th> 
<th style='border: 1px solid #000; padding: 8px;'>Total</th> 
</tr> 
</thead>";
$html1 .= "<tbody> 
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>All</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$total_complaints</strong></td> </tr>
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>Resolved</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$resolved</strong></td> </tr>
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>Ongoing</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$ongoing</strong></td> </tr>
</tbody>";
$html1 .= "</table>";

$html1 .= "<h1 style='text-align: center; font-size: 18px; margin-bottom: 5px; margin-top: 50px;'>Breakdown by Status</h1>";
$html1 .= "<table style='width: 100%; border-collapse: collapse; font-size: 16px; margin: 20px 0;'>";
$html1 .= "<thead> 
<tr> 
<th style='border: 1px solid #000; padding: 8px;'>Status</th> 
<th style='border: 1px solid #000; padding: 8px;'>Total</th> 
</tr> 
</thead>";
$html1 .= "<tbody> 
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>Pending</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$total_pending</strong></td> </tr>
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>Processing</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$total_processing</strong></td> </tr>
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>Resolved</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$total_resolved</strong></td> </tr>
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>Escalated</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$total_fileaction</strong></td> </tr>
</tbody>";
$html1 .= "</table>";
$html1 .= "</div>";

$pdf->WriteHTML($html1);
$pdf->AddPage();

$html2  = "<div style='text-align: center; font-family: Arial, sans-serif; padding-top: 130px;'>";
$html2 .= "<h1 style='text-align: center; font-size: 18px; margin-bottom: 5px;'>Breakdown by Type</h1>";
$html2 .= "<table style='width: 100%; border-collapse: collapse; font-size: 16px; margin: 20px 0;'>";
$html2 .= "<thead> 
<tr> 
<th style='border: 1px solid #000; padding: 8px;'>Type</th> 
<th style='border: 1px solid #000; padding: 8px;'>Total</th> 
</tr> 
</thead>";
$html2 .= "<tbody> 
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>Noise Complaint</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$total_noise</strong></td> </tr>
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>Sanitation Issue</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$total_sanitation</strong></td> </tr>
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>Property/Neighbor Dispute</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$total_dispute</strong></td> </tr>
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>Infrastructure Problem</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$total_infras</strong></td> </tr>
<tr> <td style='border: 1px solid #000; padding: 8px;'><strong>Others</strong></td> <td style='border: 1px solid #000; padding: 8px;'><strong>$total_others</strong></td> </tr>
</tbody>";
$html2 .= "</table>";
$html2 .= "<hr>";
$html2 .= "<h1 style='text-align: left; font-size: 18px; margin-bottom: 5px;'>Remarks</h1>";
$html2 .= "<p style='margin-top: 10px; font-size: 16px; text-align: justify;'>- " . htmlspecialchars($remark) . "<br>- Overall resolution rate: " . round($resolutionrate, 2) . "%</p>";
$html2 .= "<br><br><br>";
$html2 .= "<p style='text-align: center; font-size: 16px; margin-left: 250px; margin-bottom: 50px;'><strong>MANILYN R. MALONZO</strong><br>Barangay Secretary</p>";
$html2 .= "<p style='text-align: center; font-size: 16px; margin-left: 250px;'><strong>EDGARDO A. BORNALES</strong><br>Punong Barangay/Pangkat Chairperson </p>";
$html2 .= "</div>";

$pdf->WriteHTML($html2);

$filename = "Complaints_Report.pdf";
$pdf->Output($filename, 'I');
?>
