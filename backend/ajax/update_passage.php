<?php
/**
 * AJAX Endpoint: Update or Create Passage
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

$db = Database::getConnection();

$action = $_POST['action'] ?? 'update';
$passageId = (int)($_POST['passage_id'] ?? 0);
$examId = (int)($_POST['exam_id'] ?? 0);
$part = (int)($_POST['part'] ?? 6);
$type = trim($_POST['type'] ?? 'Reading Passage');
$content = trim($_POST['content'] ?? '');
$imageUrl = trim($_POST['image_url'] ?? '');

try {
    if ($action === 'create') {
        $stmt = $db->prepare("
            INSERT INTO DoanDoc (MaDeThi, PhanThi, LoaiDoan, NoiDung, DuongDanHinh)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$examId, $part, $type, $content, $imageUrl ?: null]);
        $newId = (int)$db->lastInsertId();

        echo json_encode([
            'success' => true,
            'passage_id' => $newId,
            'message' => 'Đã tạo bài đọc mới thành công!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'update' && $passageId > 0) {
        $stmt = $db->prepare("
            UPDATE DoanDoc 
            SET PhanThi = ?, LoaiDoan = ?, NoiDung = ?, DuongDanHinh = ?
            WHERE MaDoanDoc = ?
        ");
        $stmt->execute([$part, $type, $content, $imageUrl ?: null, $passageId]);

        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật bài đọc thành công!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete' && $passageId > 0) {
        // Gỡ liên kết câu hỏi trước
        $db->prepare("UPDATE CauHoi SET MaDoanDoc = NULL WHERE MaDoanDoc = ?")->execute([$passageId]);
        // Xóa đoạn đọc
        $db->prepare("DELETE FROM DoanDoc WHERE MaDoanDoc = ?")->execute([$passageId]);

        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa bài đọc thành công!'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
