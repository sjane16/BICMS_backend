<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET");
header("Content-Type: application/json");

require_once __DIR__ . '/config/residents_db.php';

$return = [];

$totalComp = "SELECT 
COUNT(*) AS total,
COUNT(CASE WHEN complaint_status = 'resolved' THEN 1 END) AS resolved,
COUNT(CASE WHEN complaint_status IN ('pending' , 'in progress') THEN 1 END) AS active
FROM complaints";

$searchComp = $conn->query($totalComp);

if($searchComp && $row = $searchComp->fetch_assoc()){
    $totalComplaints = $row['total'];
    $resolved = $row['resolved'];
    $active = $row['active'];
    $resolvedPercent = $totalComplaints > 0 ? round(($resolved / $totalComplaints) * 100, 2) : 0;

        $return['success'] = true;
        $return['totalComplaints'] = $totalComplaints;
        $return['resolved'] = $resolved;
        $return['resolvedPercent'] = $resolvedPercent;
        $return['active'] = $active;
}else{
    $return['success'] = false;
    $return['message'] = "Query failed in complaints";
}

$totalCert = "SELECT 
COUNT(*) AS total,
COUNT(CASE WHEN cert_status = 'claimed' THEN 1 END) AS claimed
FROM certificates";

$searchCert = $conn->query($totalCert);

if($searchCert && $row = $searchCert->fetch_assoc()){
    $totalCert = $row['total'];
    $claimed = $row['claimed'];

    $return['success'] = true;
    $return['totalCert'] = $totalCert;
    $return['claimed'] = $claimed;
}else{
    $return['success'] = false;
    $return['message'] = "Query failed in certificates";
}

$compCategory = "SELECT 
COUNT(CASE WHEN type = 'Noise Complaint' THEN 1 END) AS noise,
COUNT(CASE WHEN type = 'Sanitation Issue' THEN 1 END) AS sanitation,
COUNT(CASE WHEN type = 'Property/Neighbor Dispute' THEN 1 END) AS dispute,
COUNT(CASE WHEN type = 'Infrastructure Problem' THEN 1 END) AS infrastructure,
COUNT(CASE WHEN type = 'Others' THEN 1 END) AS others
FROM complaints";

$categoryComp = $conn->query($compCategory);

if($categoryComp && $row = $categoryComp->fetch_assoc()){
    $noise = $row['noise'];
    $sanitation = $row['sanitation'];
    $dispute = $row['dispute'];
    $infrastructure = $row['infrastructure'];
    $others = $row['others'];

    $return['success'] = true;
    $return['noise'] = $noise;
    $return['sanitation'] = $sanitation;
    $return['dispute'] = $dispute;
    $return['infrastructure'] = $infrastructure;
    $return['others'] = $others;
}else{
    $return['success'] = false;
    $return['message'] = "Query failed in complaint types";
}

$certCategory = "SELECT 
COUNT(CASE WHEN type = 'Barangay Clearance' THEN 1 END) AS clearance,
COUNT(CASE WHEN type = 'Certificate of Residency' THEN 1 END) AS residency,
COUNT(CASE WHEN type = 'Certificate of Indigency' THEN 1 END) AS indigency
FROM certificates";

$categoryCert = $conn->query($certCategory);

if($categoryCert && $row = $categoryCert->fetch_assoc()){
    $clearance = $row['clearance'];
    $residency = $row['residency'];
    $indigency = $row['indigency'];

    $return['success'] = true;
    $return['clearance'] = $clearance;
    $return['residency'] = $residency;
    $return['indigency'] = $indigency;
}else{
    $return['success'] = false;
    $return['message'] = "Query failed in certificate types";
}

echo json_encode($return);
$conn->close();
$searchComp->close();
$searchCert->close();
?>