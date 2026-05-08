<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$patientID = $data["patientID"] ?? null;

$conn = new mysqli("localhost", "root", "", "CapitalHealthDB");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed"]);
    exit;
}

$stmt = $conn->prepare("CALL GetDischargeSummary(?)");
$stmt->bind_param("i", $patientID);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode(["success" => true, "data" => $rows]);

$stmt->close();
$conn->close();
