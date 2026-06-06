<!-- C:\xampp\htdocs\smart-plant-pot\insert.php -->
<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "telegram.php";


function saveAlert(
    $conn,
    $type,
    $level,
    $message
) {
    $stmt = $conn->prepare("
        INSERT INTO alert_logs (
            alert_type,
            alert_level,
            message
        )
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param(
        "sss",
        $type,
        $level,
        $message
    );

    $success = $stmt->execute();

    if ($success) {
        if ($level == "CRITICAL") {
            sendTelegramMessage(
                "🚨 SMART PLANT POT\n\n" .
                    "Sensor : {$type}\n" .
                    "Status : {$level}\n" .
                    "{$message}"
            );
        } else if ($level == "WARNING") {
            sendTelegramMessage(
                "⚠️ SMART PLANT POT\n\n" .
                    "Sensor : {$type}\n" .
                    "Status : {$level}\n" .
                    "{$message}"
            );
        } else if ($level == "NORMAL") {
            sendTelegramMessage(
                "✅ SMART PLANT POT\n\n" .
                    "Sensor : {$type}\n" .
                    "Status : NORMAL\n" .
                    "{$message}\n\n" .
                    "Condition recovered"
            );
        }
    }

    return $success;
}

function getLastAlertLevel(
    $conn,
    $type
) {
    $stmt = $conn->prepare("
        SELECT alert_level
        FROM alert_logs
        WHERE alert_type = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    $stmt->bind_param(
        "s",
        $type
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['alert_level'];
    }

    return "NORMAL";
}

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
        hum_alert,
        pump_status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ddiiiissssi",
    $data['temperature'],
    $data['humidity'],
    $data['soil_raw'],
    $data['soil_percent'],
    $data['water_raw'],
    $data['water_percent'],
    $data['soil_alert'],
    $data['water_alert'],
    $data['temp_alert'],
    $data['hum_alert'],
    $data['pump_status']
);

$success = $stmt->execute();

if ($success) {
    $lastSoil =
        getLastAlertLevel(
            $conn,
            "SOIL"
        );

    $currentSoil =
        $data['soil_alert'];

    if ($currentSoil != $lastSoil) {
        saveAlert(
            $conn,
            "SOIL",
            $currentSoil,
            "Soil Moisture "
                . $data['soil_percent']
                . "%"
        );
    }

    $lastWater =
        getLastAlertLevel(
            $conn,
            "WATER"
        );

    $currentWater =
        $data['water_alert'];

    if ($currentWater != $lastWater) {
        saveAlert(
            $conn,
            "WATER",
            $currentWater,
            "Water Tank "
                . $data['water_percent']
                . "%"
        );
    }

    $lastTemp =
        getLastAlertLevel(
            $conn,
            "TEMP"
        );

    $currentTemp =
        $data['temp_alert'];

    if ($currentTemp != $lastTemp) {
        saveAlert(
            $conn,
            "TEMP",
            $currentTemp,
            "Temperature "
                . $data['temperature']
                . " C"
        );
    }

    $lastHum =
        getLastAlertLevel(
            $conn,
            "HUM"
        );

    $currentHum =
        $data['hum_alert'];

    if ($currentHum != $lastHum) {
        saveAlert(
            $conn,
            "HUM",
            $currentHum,
            "Humidity "
                . $data['humidity']
                . "%"
        );
    }
}



echo json_encode([
    "success" => $success
]);
