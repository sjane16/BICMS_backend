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

$name = $data['name'];
$address = $data['address'];
$purpose = $data['purpose'];
$issued = $data['issued'];
$type = $data['type'];

$date = !empty($issued) ? new DateTime($issued) : new DateTime();

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

$pdf = new \Mpdf\Mpdf([
    'format' => 'A4',
    'margin_left' => 20,
    'margin_right' => 20,
    'margin_bottom' => 20,
    'margin_top' => 20
]);

$background = __DIR__ . '/assets/certbg.png';
$pdf->SetDefaultBodyCSS('background', "url($background) no-repeat center center");
$pdf->SetDefaultBodyCSS('background-image-resize', 6);

$html = "<div style='text-align: center; font-family: Arial, sans-serif; padding-top: 130px;'>";

switch($data['type']){
    case 'Barangay Clearance':
       $html .= "<h1 style='text-align: center; font-size: 32px; margin-bottom: 20px;'>Barangay Clearance</h1>";
        $html .= "<div style='max-width: 600px; margin: 0 90px 0 190px; padding: 5px; text-align: justify; font-size: 18px; line-height: 1.6;'>";
        $html .= "<p><strong>TO WHOM IT MAY CONCERN:</strong></p>";

        $html .= "<p style='text-indent: 30px;'>This is to certify that <strong>$name</strong>, of legal age, is a bonafide resident of Barangay 134, Zone 11, District 1 with postal address at <strong>$address</strong>.</p>";

        $html .= "<p style='text-indent: 30px;'>This further certifies that he/she has a good moral character and reputation in the community.</p>";

        $html .= "<p style='text-indent: 30px;'>This certification is issued upon the request of the above-mentioned person for <strong>$purpose.</p>";

        $html .= "<p style='text-indent: 30px;'>Issued this <b>$finalday </b> day of <b>$month $year</b>, at the Office of the Punong Barangay, Barangay 134 Zone 11 Distict 1.</p>";

        $html .= "<br><br>";
        $html .= "<p style='text-align: center; font-size: 18px; margin-left: 100px;'><strong>EDGARDO A. BORNALES</strong><br>Punong Barangay</p>";
        $html .= "</div>";
    break;
    case 'Certificate of Indigency': 
        $html .= "<h1 style='text-align: center; font-size: 32px; margin-bottom: 20px;'>Certificate of Indigency</h1>";
        $html .= "<div style='max-width: 600px; margin: 0 90px 0 190px; padding: 5px; text-align: justify; font-size: 18px; line-height: 1.6;'>";
        $html .= "<p><strong>TO WHOM IT MAY CONCERN:</strong></p>";

        $html .= "<p style='text-indent: 30px;'>This is to certify that <strong>$name</strong>, of legal age, is a bonafide resident of Barangay 134, Zone 11, District 1 with postal address at <strong>$address</strong>.</p>";

        $html .= "<p style='text-indent: 30px;'>This further certifies that his/her family currently has no means of living at present.</p>";

        $html .= "<p style='text-indent: 30px;'>This certification is issued upon the request of the above-mentioned person for <strong>$purpose</strong>.</p>";

        $html .= "<p style='text-indent: 30px;'>Issued this <b>$finalday </b> day of <b>$month $year</b>, at the Office of the Punong Barangay, Barangay 134 Zone 11 Distict 1.</p>";

        $html .= "<br><br>";
        $html .= "<p style='text-align: center; font-size: 18px; margin-left: 100px;'><strong>EDGARDO A. BORNALES</strong><br>Punong Barangay</p>";
        $html .= "</div>";
    break;
    case 'Certificate of Residency' :
        $html .= "<h1 style='text-align: center; font-size: 32px; margin-bottom: 20px;'>Certificate of Residency</h1>";
        $html .= "<div style='max-width: 600px; margin: 0 90px 0 190px; padding: 5px; text-align: justify; font-size: 17px; line-height: 1.6;'>";
        $html .= "<p><strong>TO WHOM IT MAY CONCERN:</strong></p>";

        $html .= "<p style='text-indent: 30px;'>This is to certify that <strong>$name</strong>, of legal age, is a bonafide resident of Barangay 134, Zone 11, District 1 with postal address at <strong>$address</strong>.</p>";

        $html .= "<p style='text-indent: 30px;'>This certification is issued to attest that the aforementioned person is a resident of this barangay and has been living in the community for a considerable period of time.</p>";

        $html .= "<p style='text-indent: 30px;'>This certification is issued upon the request of the above-mentioned person for <strong>$purpose</strong>.</p>";

        $html .= "<p style='text-indent: 30px;'>Issued this <b>$finalday</b> day of <b>$month $year</b>, at the Office of the Punong Barangay, Barangay 134 Zone 11 Distict 1.</p>";

        $html .= "<br><br>";
        $html .= "<p style='text-align: center; font-size: 18px; margin-left: 100px;'><strong>EDGARDO A. BORNALES</strong><br>Punong Barangay</p>";
        $html .= "</div>";
    break;
}

$html .= "</div>";

$pdf->WriteHTML($html);

$filename = "Certificate_{$data['name']}.pdf";

$pdf->Output($filename, 'I');
?>