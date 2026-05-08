<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
header("Content-Type: application/json");

// Step 1: Read the raw JSON input
$rawData = file_get_contents("php://input");

// Step 2: DEBUG — Save it to a log file
file_put_contents("debug_log.txt", date("Y-m-d H:i:s") . " | RAW: " . $rawData . PHP_EOL, FILE_APPEND);

// Step 3: Decode JSON
$data = json_decode($rawData, true);

// Step 4: If data not valid
if (!$data) {
    echo json_encode([
        "success" => false,
        "message" => "❌ No JSON received",
        "debug" => $rawData
    ]);
    exit;
}

// Step 5: Extract and validate fields
$patientID = $data["patientID"] ?? null;
$diagnosis = $data["diagnosis"] ?? '';
$treatment = $data["treatment"] ?? '';
$followUp = $data["followUp"] ?? '';

if (!$patientID || !$diagnosis || !$treatment || !$followUp) {
    echo json_encode([
        "success" => false,
        "message" => "❌ Missing required fields"
    ]);
    exit;
}

// Step 6: Connect to database
$conn = new mysqli("localhost", "root", "", "CapitalHealthDB");

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "❌ Database connection failed"
    ]);
    exit;
}

// Step 7: Prepare and call stored procedure
$stmt = $conn->prepare("CALL InsertDischargeSummary(?, ?, ?, ?)");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "❌ Prepare failed: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("isss", $patientID, $diagnosis, $treatment, $followUp);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "✅ Discharge summary added"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "❌ Execution failed: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
