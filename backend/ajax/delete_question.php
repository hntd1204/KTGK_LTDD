<?php
/**
 * AJAX Endpoint: Delete Question
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

$qId = (int)($_POST['question_id'] ?? 0);
if ($qId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mã câu hỏi không hợp lệ.']);
    exit;
}

try {
    $db = Database::getConnection();
    // Do ON DELETE CASCADE, LuaChonDapAn sẽ tự động bị xóa theo
    $stmt = $db->prepare("DELETE FROM CauHoi WHERE MaCauHoi = ?");
    $stmt->execute([$qId]);

    echo json_encode(['success' => true, 'message' => 'Đã xóa câu hỏi thành công!'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
