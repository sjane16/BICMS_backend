<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/config/residents_db.php";

$query = "SELECT
c.complaint_id AS id,
c.subject,
c.type,
c.description AS complaint_desc,
c.assigned_to,
c.complaint_status,
c.submitted_on,
CONCAT(
  c.respondent_fname, ' ',
  LEFT(IFNULL(c.respondent_mname, ''), 1),
  IF(c.respondent_mname IS NOT NULL, '.', ''),
  ' ',
  c.respondent_lname
) AS respondent_fullname,
c.relationship,
c.respondent_address,
c.incident_date,
CONCAT(r.first_name, ' ', LEFT(r.middle_name,1), '.', ' ', r.last_name) AS fullname,
r.contact,
r.address,
res.resolution_type,
res.Terms_Conditions,
res.payment_type,
res.resolution_date,
res.compliance_date,
res.actualcompliance_date,
res.amount,
res.description AS resolution_desc,
res.resolution_status
FROM complaints c
JOIN residents r ON c.resident_ID = r.resident_ID
LEFT JOIN resolutions res ON c.complaint_id = res.complaint_id
WHERE c.submitted_on >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
ORDER BY c.submitted_on DESC";

$complaints = [];
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $raw_status = $row['complaint_status'] ?? '';
        $display_status = (strtolower($raw_status) === 'dismissed') ? 'escalated' : $raw_status;

        $complaints[] = [
            "fullname" => $row['fullname'],
            "contact" => $row['contact'],
            "address" => $row['address'],
            "type" => $row['type'],
            "description" => $row['complaint_desc'] ?? '',
            "date" => $row['submitted_on'],
            "id" => $row['id'],
            "subject" => $row['subject'],
            "status" => $display_status ?: 'Pending',
            "assigned_to" => $row['assigned_to'],
            "respondent_name" => $row['respondent_fullname'],
            "relationship" => $row['relationship'] ?? 'N/A',
            "respondent_address" => $row['respondent_address'] ?? 'N/A',
            "incident_date" => $row['incident_date'],
            "resolution_type" => $row['resolution_type'],
            "Terms_Conditions" => $row['Terms_Conditions'],
            "payment_type" => $row['payment_type'],
            "resolution_date" => $row['resolution_date'],
            "compliance_date" => $row['compliance_date'],
            "actualcompliance_date" => $row['actualcompliance_date'],
            "amount" => $row['amount'],
            "resolution_desc" => $row['resolution_desc'] ?? '',
            "resolution_status" => $row['resolution_status']
        ];
    }
}

if (!$result) {
    echo json_encode(["error" => $conn->error]);
    exit;
}

echo json_encode($complaints);
$conn->close();
?>
