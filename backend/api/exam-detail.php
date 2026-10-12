<?php
/**
 * REST API: Lấy chi tiết đề thi gồm bài đọc và câu hỏi
 * GET /backend/api/exam-detail.php?id={MaDeThi}
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

$examId = (int)($_GET['id'] ?? 0);
if ($examId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id đề thi'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getConnection();

    // 1. Đề thi
    $stmt = $db->prepare("SELECT * FROM DeThi WHERE MaDeThi = ?");
    $stmt->execute([$examId]);
    $exam = $stmt->fetch();

    if (!$exam) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy đề thi'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Bài đọc
    $stmt = $db->prepare("SELECT * FROM DoanDoc WHERE MaDeThi = ? ORDER BY MaDoanDoc ASC");
    $stmt->execute([$examId]);
    $passages = $stmt->fetchAll();

    // 3. Câu hỏi và lựa chọn
    $stmt = $db->prepare("
        SELECT c.MaCauHoi, c.SoThuTuCau, c.PhanThi, c.MaDoanDoc, c.NoiDungCauHoi,
               l.MaLuaChon, l.KyHieu, l.NoiDungLuaChon
        FROM CauHoi c
        LEFT JOIN LuaChonDapAn l ON c.MaCauHoi = l.MaCauHoi
        WHERE c.MaDeThi = ?
        ORDER BY c.SoThuTuCau ASC, l.KyHieu ASC
    ");
    $stmt->execute([$examId]);
    $rows = $stmt->fetchAll();

    $questions = [];
    foreach ($rows as $r) {
        $qId = $r['MaCauHoi'];
        if (!isset($questions[$qId])) {
            $questions[$qId] = [
                'MaCauHoi' => $r['MaCauHoi'],
                'SoThuTuCau' => $r['SoThuTuCau'],
                'PhanThi' => $r['PhanThi'],
                'MaDoanDoc' => $r['MaDoanDoc'],
                'NoiDungCauHoi' => $r['NoiDungCauHoi'],
                'LuaChon' => []
            ];
        }
        if ($r['MaLuaChon']) {
            $questions[$qId]['LuaChon'][] = [
                'MaLuaChon' => $r['MaLuaChon'],
                'KyHieu' => $r['KyHieu'],
                'NoiDung' => $r['NoiDungLuaChon']
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'exam' => $exam,
        'passages' => $passages,
        'questions' => array_values($questions)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
