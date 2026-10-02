<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$db_host = "localhost";
$db_name = "aqualarion_app";
$db_user = "root";       
$db_pass = "";          

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
  die(json_encode([
    "status" => "error",
    "message" => "DB connect failed: " . $conn->connect_error
  ]));
}

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
  die(json_encode(["status" => "error", "message" => "Invalid JSON received"]));
}

// Required fields EXCEPT student_id now
$required = ['ph', 'turbidity', 'tds', 'temperature_c', 'fluorescence', 'safe_status'];
foreach ($required as $key) {
  if (!isset($data[$key])) {
    die(json_encode(["status" => "error", "message" => "Missing field: $key"]));
  }
}

// Set student_id to NULL if empty/0/missing
$student_id = !empty($data['student_id']) ? (int)$data['student_id'] : null;

$ph             = (float)$data['ph'];
$turbidity      = (float)$data['turbidity'];
$tds            = (float)$data['tds'];
$temperature_c  = (float)$data['temperature_c'];
$fluorescence   = (float)$data['fluorescence'];
$safe_status    = (string)$data['safe_status'];

// Insert — bind type: "s" for null-safe value
$stmt = $conn->prepare("INSERT INTO water_diagnostics 
  (student_id, ph, turbidity, tds, temperature_c, fluorescence, safe_status, created_at)
  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

// Use "s" for student_id so NULL works perfectly
$stmt->bind_param("sddddds",
  $student_id,
  $ph,
  $turbidity,
  $tds,
  $temperature_c,
  $fluorescence,
  $safe_status
);

if ($stmt->execute()) {
  echo json_encode([
    "status" => "success",
    "message" => "Data saved successfully",
    "insert_id" => $conn->insert_id
  ]);
} else {
  echo json_encode([
    "status" => "error",
    "message" => "Insert failed: " . $stmt->error
  ]);
}

$stmt->close();
$conn->close();
?>