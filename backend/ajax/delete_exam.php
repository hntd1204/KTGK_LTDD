<?php
/**
 * AJAX Endpoint: Delete Exam
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

$examId = (int)($_POST['exam_id'] ?? 0);
if ($examId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mã đề thi không hợp lệ.']);
    exit;
}

try {
    $db = Database::getConnection();
    // Do có ON DELETE CASCADE, xóa DeThi sẽ tự động xóa CauHoi, DoanDoc, LuaChonDapAn
    $stmt = $db->prepare("DELETE FROM DeThi WHERE MaDeThi = ?");
    $stmt->execute([$examId]);

    echo json_encode([
        'success' => true,
        'message' => "Đã xóa đề thi #{$examId} thành công!"
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi xóa đề thi: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
