<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Connect to DB
$conn = new mysqli("localhost", "root", "", "capitalhealthdb");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "❌ DB connection failed"]);
    exit();
}

// Read raw JSON input
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["success" => false, "message" => "❌ No JSON received"]);
    exit();
}

// Extract variables from JSON
$summaryID = $input['summaryID'] ?? null;
$diagnosis = $input['diagnosis'] ?? null;
$treatment = $input['treatment'] ?? null;
$followUp  = $input['followUp'] ?? null;

if (!$summaryID || !$diagnosis || !$treatment || !$followUp) {
    echo json_encode(["success" => false, "message" => "❌ Missing required fields", "debug" => $input]);
    exit();
}

// Prepare UPDATE query
$stmt = $conn->prepare("UPDATE dischargesummary SET Diagnosis = ?, Treatment = ?, FollowUp = ? WHERE SummaryID = ?");
$stmt->bind_param("sssi", $diagnosis, $treatment, $followUp, $summaryID);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "✅ Summary updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "❌ Failed to update summary"]);
}

$stmt->close();
$conn->close();
?>
