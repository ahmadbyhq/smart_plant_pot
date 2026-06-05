<!-- C:\xampp\htdocs\smart-plant-pot\insert.php -->
<?php

header("Content-Type: application/json");

require_once "db.php";

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (!$data) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON"
    ]);

    exit;
}

$stmt = $conn->prepare("
    INSERT INTO monitoring_logs (
        temperature,
        humidity,
        soil_raw,
        soil_percent,
        water_raw,
        water_percent,
        soil_alert,
        water_alert,
        temp_alert,
        pump_status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ddiiiisssi",
    $data['temperature'],
    $data['humidity'],
    $data['soil_raw'],
    $data['soil_percent'],
    $data['water_raw'],
    $data['water_percent'],
    $data['soil_alert'],
    $data['water_alert'],
    $data['temp_alert'],
    $data['pump_status']
);

$success = $stmt->execute();

echo json_encode([
    "success" => $success
]);
