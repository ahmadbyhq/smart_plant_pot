<?php
// C:\xampp\htdocs\smart-plant-pot\get_realtime.php

header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");

require_once "../../backend/db.php";

$action = $_GET['action'] ?? 'realtime';

try {
    switch ($action) {

        // ── 1. Data realtime terbaru ──────────────────────────────────────────
        case 'realtime':
            $result = $conn->query("
                SELECT temperature, humidity, soil_percent, water_percent,
                       soil_alert, water_alert, temp_alert, hum_alert,
                       pump_status, created_at
                FROM monitoring_logs
                ORDER BY id DESC
                LIMIT 1
            ");
            $latest = $result->fetch_assoc();

            // Grafik: 20 data terakhir untuk semua sensor
            $chart = $conn->query("
                SELECT temperature, humidity, soil_percent, water_percent,
                       created_at
                FROM monitoring_logs
                ORDER BY id DESC
                LIMIT 20
            ");
            $chartRows = [];
            while ($row = $chart->fetch_assoc()) {
                $chartRows[] = $row;
            }
            $chartRows = array_reverse($chartRows);

            // Alert panel: 10 terbaru (WARNING / CRITICAL)
            $alerts = $conn->query("
                SELECT alert_type, alert_level, message, created_at
                FROM alert_logs
                WHERE alert_level IN ('WARNING','CRITICAL')
                ORDER BY id DESC
                LIMIT 10
            ");
            $alertRows = [];
            while ($row = $alerts->fetch_assoc()) {
                $alertRows[] = $row;
            }

            // Ringkasan statistik alert
            $statRes = $conn->query("
                SELECT
                    SUM(alert_level = 'CRITICAL') AS total_critical,
                    SUM(alert_level = 'WARNING')  AS total_warning,
                    SUM(alert_level = 'NORMAL')   AS total_normal
                FROM alert_logs
            ");
            $stats = $statRes->fetch_assoc();

            echo json_encode([
                'success'      => true,
                'latest'       => $latest,
                'chart'        => $chartRows,
                'alerts'       => $alertRows,
                'alert_stats'  => $stats,
                'server_time'  => date('Y-m-d H:i:s'),
            ]);
            break;

        // ── 2. Tabel riwayat monitoring (pagination + search + sort) ─────────
        case 'history':
            $page    = max(1, (int)($_GET['page']    ?? 1));
            $limit   = max(5,  (int)($_GET['limit']  ?? 10));
            $search  = $conn->real_escape_string($_GET['search'] ?? '');
            $sortCol = $_GET['sort']  ?? 'id';
            $sortDir = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

            $allowed = ['id','temperature','humidity','soil_percent',
                        'water_percent','pump_status','created_at'];
            if (!in_array($sortCol, $allowed)) {
                $sortCol = 'id';
            }

            $where = '';
            if ($search !== '') {
                $where = "WHERE
                    soil_alert  LIKE '%{$search}%' OR
                    water_alert LIKE '%{$search}%' OR
                    temp_alert  LIKE '%{$search}%' OR
                    hum_alert   LIKE '%{$search}%' OR
                    created_at  LIKE '%{$search}%'";
            }

            $countRes = $conn->query("SELECT COUNT(*) AS c FROM monitoring_logs {$where}");
            $total    = (int)$countRes->fetch_assoc()['c'];
            $offset   = ($page - 1) * $limit;

            $data = $conn->query("
                SELECT id, temperature, humidity, soil_percent, water_percent,
                       soil_alert, water_alert, temp_alert, hum_alert,
                       pump_status, created_at
                FROM monitoring_logs
                {$where}
                ORDER BY {$sortCol} {$sortDir}
                LIMIT {$limit} OFFSET {$offset}
            ");

            $rows = [];
            while ($row = $data->fetch_assoc()) {
                $rows[] = $row;
            }

            echo json_encode([
                'success'    => true,
                'data'       => $rows,
                'total'      => $total,
                'page'       => $page,
                'limit'      => $limit,
                'total_pages'=> ceil($total / $limit),
            ]);
            break;

        // ── 3. Tabel riwayat alert (pagination) ──────────────────────────────
        case 'alert_history':
            $page  = max(1, (int)($_GET['page']  ?? 1));
            $limit = max(5,  (int)($_GET['limit'] ?? 10));
            $filterLevel = $conn->real_escape_string($_GET['level'] ?? '');

            $where = '';
            if (in_array($filterLevel, ['NORMAL','WARNING','CRITICAL'])) {
                $where = "WHERE alert_level = '{$filterLevel}'";
            }

            $countRes = $conn->query("SELECT COUNT(*) AS c FROM alert_logs {$where}");
            $total    = (int)$countRes->fetch_assoc()['c'];
            $offset   = ($page - 1) * $limit;

            $data = $conn->query("
                SELECT id, alert_type, alert_level, message, created_at
                FROM alert_logs
                {$where}
                ORDER BY id DESC
                LIMIT {$limit} OFFSET {$offset}
            ");

            $rows = [];
            while ($row = $data->fetch_assoc()) {
                $rows[] = $row;
            }

            echo json_encode([
                'success'     => true,
                'data'        => $rows,
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => ceil($total / $limit),
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}