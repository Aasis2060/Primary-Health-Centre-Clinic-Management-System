<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

$summaryID = $data["summaryID"] ?? null;
$reportType = $data["reportType"] ?? '';
$filePath = $data["filePath"] ?? '';

$conn = new mysqli("localhost", "root", "", "CapitalHealthDB");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed"]);
    exit;
}

$stmt = $conn->prepare("CALL InsertLabReport(?, ?, ?)");
$stmt->bind_param("iss", $summaryID, $reportType, $filePath);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Lab report inserted."]);
} else {
    echo json_encode(["success" => false, "message" => "Insert failed"]);
}

$stmt->close();
$conn->close();
