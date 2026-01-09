<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (preg_match('/^http:\/\/(localhost|127\.0\.0\.1):\d+$/', $origin) ||
    $origin === 'https://bicms.vercel.app') {
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

if(!isset($_SESSION['user_id'])){
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
$enddate = date('Y-m-t');
$date_generated = date('F j, Y');
$startrep = date('F 1, Y');
$endrep = date('F t, Y');

$date = new DateTime('now', new DateTimeZone('Asia/Manila'));


$day = $date->format("j");
$month = $date->format("F");
$year = $date->format("Y");

$period = $month . ' ' . $year;
$report_type = "Certificate Report";
$generation_date = $date->format('Y-m-d');


$reportlog = $conn->prepare("INSERT INTO report_logs(report_type, period, generation_date, generated_by)
VALUES(?,?,?,?)");
$reportlog->bind_param("ssss", $report_type, $period, $generation_date, $username);
$reportlog->execute();


$summary = "SELECT 
COUNT(CASE WHEN cert_status = 'claimed' THEN 1 END ) AS claimed,
COUNT(CASE WHEN cert_status IN ('processing', 'ready') THEN 1 END) AS ongoing
FROM certificates
WHERE submitted_on BETWEEN '$startdate' AND '$enddate'";

$resultSummary = $conn->query($summary);

if($resultSummary && $row = $resultSummary->fetch_assoc()){
    $claimed = $row['claimed'];
    $ongoing = $row['ongoing'];
}

$totals = "SELECT 
COUNT(*) AS total_certificates,
COUNT(CASE WHEN cert_status = 'pending' THEN 1 END) AS total_pending,
COUNT(CASE WHEN cert_status = 'processing' THEN 1 END) AS total_processing,
COUNT(CASE WHEN cert_status = 'ready' THEN 1 END) AS total_ready,
COUNT(CASE WHEN cert_status = 'claimed' THEN 1 END) AS total_claimed
FROM certificates
WHERE submitted_on BETWEEN '$startdate' AND '$enddate'";

$resultTotals = $conn->query($totals);

if($resultTotals && $row = $resultTotals->fetch_assoc()){
    $total_certificates = $row['total_certificates'];
    $total_pending = $row['total_pending'];
    $total_processing = $row['total_processing'];
    $total_ready = $row['total_ready'];
    $total_claimed = $row['total_claimed'];
}

$type = "SELECT
    COUNT(CASE WHEN type = 'Barangay Clearance' THEN 1 END) AS total_clearance,
    COUNT(CASE WHEN type = 'Certificate of Indigency' THEN 1 END) AS total_indigency,
    COUNT(CASE WHEN type = 'Certificate of Residency' THEN 1 END) AS total_residency
FROM certificates
WHERE submitted_on BETWEEN '$startdate' AND '$enddate'";

$sumtype = $conn->query($type);

if($sumtype && $row = $sumtype->fetch_assoc()){
    $total_clearance = $row['total_clearance'];
    $total_indigency = $row['total_indigency'];
    $total_residency = $row['total_residency'];
}

$types = [
    'Barangay Clearance' => $total_clearance,
    'Certificate of Indigency' => $total_indigency,
    'Certificate of Residency' => $total_residency
];

arsort($types);
$top = array_slice(array_keys($types), 0,2);

$remark = "Most requested certificates were " . implode(" and ", $top) . ".";

$resolutionrate = $total_certificates > 0 ? ($total_claimed/$total_certificates) * 100 : 0;

$customTempDir = '/tmp/mpdf';

if(!file_exists($customTempDir)){
    mkdir($customTempDir, 0777, true);
}

$pdf = new \Mpdf\Mpdf([
    'tempDir' => $customTempDir,
    'format' => 'A4',
    'margin_left' => 20,
    'margin_right' => 20,
    'margin_bottom' => 20,
    'margin_top' => 20
]);

$background = __DIR__ . '/assets/summon_letter.png';
$pdf->SetDefaultBodyCSS('background', "url($background) no-repeat center center");
$pdf->SetDefaultBodyCSS('background-image-resize', 6);

$html1 = "<div style='text-align: center; font-family: Arial, sans-serif; padding-top: 130px;'>";
    $html1 .= "<h1 style='text-align: center; font-size: 25px; margin-bottom: 20px;'>Barangay Certificate Monthly Report</h1>";
    $html1 .= "<p style='text-align: left; font-size: 16px;'><strong>Month Covered: </strong>$month $year
        <br><strong>Date Generated:</strong>  $date_generated
        <br><strong>Prepared by:</strong> $username
        <br><strong>Barangay:</strong> Barangay 134, Zone 11, District 1
        </p>";
    $html1 .= "<hr>";
    $html1 .= "<h1 style='text-align: center; font-size: 18px; margin-bottom: 5px;'>Summary</h1>";

$html1 .= "<table style='width: 100%; border-collapse: collapse; font-size: 16px; margin: 20px 0;'>";
$html1 .= "<thead>
            <tr>
                <th style='border: 1px solid #000; padding: 8px;'>Certificates</th>
                <th style='border: 1px solid #000; padding: 8px;'>Total</th>
            </tr>
          </thead>";
$html1 .= "<tbody>
            <tr>
                <td style='border: 1px solid #000; padding: 8px;'><strong>All</strong></td>
                <td style='border: 1px solid #000; padding: 8px;'><strong>$total_certificates</strong></td>
            </tr>
            <tr>
                <td style='border: 1px solid #000; padding: 8px;'><strong>Claimed</strong></td>
                <td style='border: 1px solid #000; padding: 8px;'><strong>$claimed</strong></td>
            </tr>
            <tr>
                <td style='border: 1px solid #000; padding: 8px;'><strong>In Progress</strong></td>
                <td style='border: 1px solid #000; padding: 8px;'><strong>$ongoing</strong></td>
            </tr>
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
            <tr>
                <td style='border: 1px solid #000; padding: 8px;'><strong>Pending</strong></td>
                <td style='border: 1px solid #000; padding: 8px;'><strong>$total_pending</strong></td>
            </tr>
            <tr>
                <td style='border: 1px solid #000; padding: 8px;'><strong>Processing</strong></td>
                <td style='border: 1px solid #000; padding: 8px;'><strong>$total_processing</strong></td>
            </tr>
            <tr>
                <td style='border: 1px solid #000; padding: 8px;'><strong>Ready</strong></td>
                <td style='border: 1px solid #000; padding: 8px;'><strong>$total_ready</strong></td>
            </tr>
            <tr>
                <td style='border: 1px solid #000; padding: 8px;'><strong>Claimed</strong></td>
                <td style='border: 1px solid #000; padding: 8px;'><strong>$total_claimed</strong></td>
            </tr>
          </tbody>";
$html1 .= "</table>";
$html1 .= "</div>";
$pdf->WriteHTML($html1);

$pdf->AddPage();

$html2 = "<div style='text-align: center; font-family: Arial, sans-serif; padding-top: 130px;'>";
    $html2 .= "<h1 style='text-align: center; font-size: 18px; margin-bottom: 5px;'>Breakdown by Type</h1>";

$html2 .= "<table style='width: 100%; border-collapse: collapse; font-size: 16px; margin: 20px 0;'>";
$html2 .= "<thead>
            <tr>
                <th style='border: 1px solid #000; padding: 8px;'>Type</th>
                <th style='border: 1px solid #000; padding: 8px;'>Total</th>
            </tr>
          </thead>";
$html2 .= "<tbody>
            <tr>
                <td style='border: 1px solid #000; padding: 8px;'><strong>Barangay Clearance</strong></td>
                <td style='border: 1px solid #000; padding: 8px;'><strong>$total_clearance</strong></td>
            </tr>
            <tr>
                <td style='border: 1px solid #000; padding: 8px;'><strong>Certificate of Indigency</strong></td>
                <td style='border: 1px solid #000; padding: 8px;'><strong>$total_indigency</strong></td>
            </tr>
            <tr>
                <td style='border: 1px solid #000; padding: 8px;'><strong>Certificate of Residency</strong></td>
                <td style='border: 1px solid #000; padding: 8px;'><strong>$total_residency</strong></td>
            </tr>
          </tbody>";
$html2 .= "</table>";
$html2 .= "<hr>";
$html2 .= "<h1 style='text-align: left; font-size: 18px; margin-bottom: 5px;'>Remarks</h1>";
$html2 .= "<p style='margin-top: 10px; font-size: 16px; text-align: justify;'>- " . htmlspecialchars($remark). 
"<br>- Overall resolution rate: " . round($resolutionrate, 2) . "%</p>";
$html2 .= "<br>";
$html2 .= "<br>";
$html2 .= "<br>";
$html2 .= "<p style='text-align: center; font-size: 16px; margin-left: 250px; margin-bottom: 50px;'><strong>MANILYN R. MALONZO</strong><br>Barangay Secretary</p>";
$html2 .= "<p style='text-align: center; font-size: 16px; margin-left: 250px;'><strong>EDGARDO A. BORNALES</strong><br>Punong Barangay/Pangkat Chairperson </p>";


$html2 .= "</div>";
$pdf->WriteHTML($html2);
$filename = "Certificate_Reports.pdf";

$pdf->Output($filename, 'I');
?>