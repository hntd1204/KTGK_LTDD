<?php
/**
 * REST API: Lấy kết quả bài thi theo mã lượt thi (attempt_id)
 * GET /backend/api/result.php?id={MaLuotThi}
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$attemptId = (int)($_GET['id'] ?? 0);
if ($attemptId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu id lượt thi'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getConnection();

    // 1. Lấy thông tin lượt thi và kết quả
    $stmt = $db->prepare("
        SELECT lt.*, d.TenDeThi, d.ThoiLuongPhut, d.TongSoCau,
               kq.SoCauDung, kq.SoCauSai, kq.SoCauBoQua, kq.DiemSo, kq.ThoiGianCham
        FROM LuotThi lt
        INNER JOIN DeThi d ON lt.MaDeThi = d.MaDeThi
        LEFT JOIN KetQuaThi kq ON lt.MaLuotThi = kq.MaLuotThi
        WHERE lt.MaLuotThi = ?
    ");
    $stmt->execute([$attemptId]);
    $attempt = $stmt->fetch();

    if (!$attempt) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy lượt thi'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Lấy chi tiết các câu trả lời của lượt thi
    $stmt = $db->prepare("
        SELECT c.MaCauHoi, c.SoThuTuCau, c.PhanThi, c.MaDoanDoc, c.NoiDungCauHoi,
               tl.MaLuaChon AS MaLuaChonDaChon,
               lcUser.KyHieu AS KyHieuDaChon,
               lcDung.MaLuaChon AS MaDapAnDung,
               lcDung.KyHieu AS KyHieuDung,
               lcDung.NoiDungLuaChon AS NoiDungDapAnDung
        FROM CauHoi c
        INNER JOIN CauTraLoi tl ON c.MaCauHoi = tl.MaCauHoi AND tl.MaLuotThi = ?
        LEFT JOIN LuaChonDapAn lcUser ON tl.MaLuaChon = lcUser.MaLuaChon
        LEFT JOIN LuaChonDapAn lcDung ON c.MaCauHoi = lcDung.MaCauHoi AND lcDung.LaDapAnDung = 1
        WHERE c.MaDeThi = ?
        ORDER BY c.SoThuTuCau ASC
    ");
    $stmt->execute([$attemptId, $attempt['MaDeThi']]);
    $questions = $stmt->fetchAll();

    $details = [];
    foreach ($questions as $q) {
        $isCorrect = ($q['MaLuaChonDaChon'] && $q['MaLuaChonDaChon'] == $q['MaDapAnDung']);
        $isSkipped = empty($q['MaLuaChonDaChon']);
        $details[] = [
            'question_id' => (int)$q['MaCauHoi'],
            'question_number' => (int)$q['SoThuTuCau'],
            'part' => (int)$q['PhanThi'],
            'passage_id' => $q['MaDoanDoc'] ? (int)$q['MaDoanDoc'] : null,
            'question_text' => $q['NoiDungCauHoi'],
            'selected_choice_id' => $q['MaLuaChonDaChon'] ? (int)$q['MaLuaChonDaChon'] : null,
            'selected_symbol' => $q['KyHieuDaChon'] ?? '',
            'correct_choice_id' => $q['MaDapAnDung'] ? (int)$q['MaDapAnDung'] : null,
            'correct_symbol' => $q['KyHieuDung'] ?? '',
            'correct_answer_text' => $q['NoiDungDapAnDung'] ?? '',
            'is_correct' => $isCorrect,
            'is_skipped' => $isSkipped
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'attempt' => $attempt,
            'details' => $details
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
