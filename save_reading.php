<?php
declare(strict_types=1);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once __DIR__ . '/connection.php';

$student_id   = $_POST['student_id']   ?? null;
$ph           = $_POST['ph']           ?? null;
$turbidity    = $_POST['turbidity']    ?? null;
$tds          = $_POST['tds']          ?? null;
$temperature_c= $_POST['temperature_c']?? null;
$fluorescence = $_POST['fluorescence'] ?? null;
$safe_status  = $_POST['safe_status']  ?? 'safe';

if (!$student_id) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'student_id is required']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO water_diagnostics 
    (student_id, ph, turbidity, tds, temperature_c, fluorescence, safe_status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
");
$stmt->execute([
    $student_id, $ph, $turbidity, $tds, $temperature_c, $fluorescence, $safe_status
]);

echo json_encode(['status'=>'success','message'=>'Data saved with student_id']);
?>