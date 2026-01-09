<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'];
$complainant_name = $data['complainant_name'];
$respondent_name = $data['respondent_name'];
$subject = $data['subject'];
$hearingDate = $data['hearingdate'];
$hearingTime = $data['hearingtime'];


$date = new DateTime($hearingDate);

$day = $date->format("j");
$month = $date->format("F");
$year = $date->format("Y");

function ordinal($day){
    if(!in_array(($day % 100), [11, 12, 13])){
        switch($day % 10){
            case 1: return $day . 'st';
            case 2: return $day .'nd';
            case 3: return $day . 'rd';
        }
    }
    return $day . 'th';
};

$finalday = ordinal($day);

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

$html = "<div style='text-align: center; font-family: Arial, sans-serif; padding-top: 130px;'>";
       $html .= "<h1 style='text-align: center; font-size: 25px; margin-bottom: 20px;'>OFFICE OF THE LUPON TAGAPAMAYAPA</h1>";
        $html .= "<p style='text-align: right; font-size: 16px;'>Barangay Case No: 2025-${id}</p>";
        $html .= "<p style='text-align: right; font-size: 16px;'>For: ${subject}</p>";
        $html .= "<p style='text-align: left; font-size: 16px;'><strong>$complainant_name</strong><br>Complainant</p>";
        $html .= "<p style='text-align: left; font-size: 16px;'><strong>$respondent_name</strong><br>Respondent</p>";

        $html .= "<div style='max-width: 600px; margin: 0 30px 0 30px; padding: 5px; text-align: justify; font-size: 16px; line-height: 1.6;'>";
        $html .= "<p style='font-size: 25px; text-align: center;'><strong>S U M M O N S</strong></p>";

        $html .= "<p style='text-indent: 30px;'>You are hereby summoned to appear before me in person together  with your witness, on the <b>$finalday</b>  day of <b>$month  $year</b>, at <b>$hearingTime pm </b> o’clock in the afternoon, then and there to answer to  a complaint made before me, copy of which is attached hereto, for  mediation/conciliation of your dispute with complainant/s. </p>";

        $html .= "<p style='text-indent: 30px;'>You are hereby warned that if you refuse or willfully fail to appear in  obedience to this summons, you may be barred from filing any  counterclaim arising from said complaint. </p>";

        $html .= "<p style='text-indent: 30px;'>FAIL NOT or else, face punishment as for contempt of court.</p>";

        $html .= "<br><br>";
        $html .= "<p style='text-align: center; font-size: 16px; margin-left: 250px;'><strong>EDGARDO A. BORNALES</strong><br>Punong Barangay/Pangkat Chairperson </p>";
        $html .= "</div>";
$html .= "</div>";

$pdf->WriteHTML($html);

$filename = "Summon_Letter_.pdf";

$pdf->Output($filename, 'I');
?>