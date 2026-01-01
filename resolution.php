<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type"); 
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require_once __DIR__ . '/config/residents_db.php';

$data = json_decode(file_get_contents("php://input"), true);

$compID = isset($data['complaint_id']) ? intval($data['complaint_id']): null;
$res_type = isset($data['resolution_type']) ? $data['resolution_type'] : null;
$agreement_date = isset($data['agreement_date']) ? $data['agreement_date'] : null;
$terms_condi = isset($data['TaC']) ? $data['TaC'] : null;
$monetary_amount = isset($data['monetary_amount']) ? $data['monetary_amount'] : null;
$payment_type = isset($data['payment_type']) ? $data['payment_type'] : null;
$payment_date = isset($data['payment_date']) ? $data['payment_date'] : null;
$installment_sched = isset($data['installment_sched']) ? $data['installment_sched'] : null;
$item_desc = isset($data['item_desc']) ? $data['item_desc'] : null;
$return_date = isset($data['return_date']) ? $data['return_date'] : null;
$service_desc = isset($data['service_desc']) ? $data['service_desc'] : null;
$deadline = isset($data['deadline']) ? $data['deadline'] : null;
$apology_date = isset($data['apology_date']) ? $data['apology_date'] : null;
$moveout_date = isset($data['moveout_date']) ? $data['moveout_date'] : null;
$debt_amount = isset($data['debt_amount']) ? $data['debt_amount'] : null;
$payment_plan = isset($data['payment_plan']) ? $data['payment_plan'] : null;
$others_desc = isset($data['others_desc']) ? $data['others_desc'] : null;
$withdrawal_date = isset($data['withdrawal_date']) ? $data['withdrawal_date'] : null;
$issuance_date = isset($data['issuance_date']) ? $data['issuance_date'] : null;
$resolution_status = ucwords(strtolower($data['resolution_status'] ?? ''));
$complaint_status = isset($data['complaint_status']) ? $data['complaint_status'] : null;

$resolution_date = null;
$compliance_date = null;
$amount = null;
$description = null;


switch($terms_condi){
    case 'Monetary Claim Settlement':
        $amount = $monetary_amount;

        if($payment_type === "Full Payment"){
            $compliance_date = $payment_date;
            $description = null;
        }else{
            $compliance_date = null;
            $description = $installment_sched;
        }
    break;
    case 'Return/Restitution of Property':
        $description = $item_desc;
        $compliance_date = $return_date;
    break;
    case 'Performance of Service/Action':
        $description = $service_desc;
        $compliance_date = $deadline;
    break;
    case 'Apology/Formal Reconciliation':
        $compliance_date = $apology_date;
    break;
    case 'Agreement to Vacate Property':
        $compliance_date = $moveout_date;
    break;
    case 'Acknowledgement of Debt':
        $amount = $debt_amount;
        $description = $payment_plan;
    break;
    case 'Others':
        $description = $others_desc;
    break;
}

switch($res_type){
    case 'Amicable Settlement (Kasunduang Pag-aayos)':
        $resolution_date = $agreement_date;
    break;
    case 'Arbitration Award':
        $resolution_date = $agreement_date;
    break;
    case 'Withdrawal of Complaint':
        $resolution_date = $withdrawal_date;
    break;
    case 'Referral to Court/Proper Agency':
        $resolution_date = $issuance_date;
    break;
}

$response = [];

$resolution_table = "INSERT INTO resolutions (complaint_id, resolution_type, Terms_Conditions, payment_type, resolution_date, 
compliance_date, amount, description, resolution_status) VALUES (?,?,?,?,?,?,?,?,?)";

$insert_resol =$conn->prepare($resolution_table);
$insert_resol->bind_param("issssssss", $compID, $res_type, $terms_condi, $payment_type, $resolution_date, $compliance_date, $amount, $description, $resolution_status);

if($insert_resol->execute()){
    $response['success'] = true;
    $response['message'] = "Resolution saved successfully";
}else{
    $response['success'] = false;
    $response['message'] = "Resolution can't be save";
}

$update_complaints = $conn->prepare("UPDATE complaints SET complaint_status = ? WHERE complaint_id = ?");
$update_complaints->bind_param("si", $complaint_status, $compID);

if($update_complaints->execute()){
    $response['success'] = true;
     $response['message'] = "Update saved";
}else{
    $response['success'] = false;
     $response['message'] = "Update fail";
}

echo json_encode($response);

$insert_resol->close();
$update_complaints->close();
$conn->close();
?>