<?php
/**
 * REST API: Danh sách đề thi cho ứng dụng Mobile Frontend
 * GET /backend/api/exams.php
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $stmt = $db->query("
        SELECT d.MaDeThi, d.TenDeThi, d.ThoiLuongPhut, d.TongSoCau, d.NgayTao,
               (SELECT COUNT(*) FROM CauHoi c WHERE c.MaDeThi = d.MaDeThi) as SoCauThucTe
        FROM DeThi d
        WHERE d.TrangThai = 1
        ORDER BY d.MaDeThi DESC
    ");
    $exams = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'total' => count($exams),
        'data' => $exams
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
