<?php
/**
 * AJAX Endpoint: Update Exam Info / Status
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

$db = Database::getConnection();
$action = $_POST['action'] ?? '';
$examId = (int)($_POST['exam_id'] ?? 0);

if ($examId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mã đề thi không hợp lệ.']);
    exit;
}

if ($action === 'toggle_status') {
    $current = (int)$db->query("SELECT TrangThai FROM DeThi WHERE MaDeThi = {$examId}")->fetchColumn();
    $newStatus = ($current == 1) ? 0 : 1;
    $stmt = $db->prepare("UPDATE DeThi SET TrangThai = ? WHERE MaDeThi = ?");
    $stmt->execute([$newStatus, $examId]);

    echo json_encode([
        'success' => true,
        'new_status' => $newStatus,
        'message' => $newStatus == 1 ? 'Đã kích hoạt mở đề thi.' : 'Đã chuyển đề thi sang tạm ẩn.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'update_info') {
    $title = trim($_POST['title'] ?? '');
    $duration = (int)($_POST['duration'] ?? 75);
    $status = (int)($_POST['status'] ?? 1);

    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Tên đề thi không được để trống.']);
        exit;
    }

    $stmt = $db->prepare("UPDATE DeThi SET TenDeThi = ?, ThoiLuongPhut = ?, TrangThai = ? WHERE MaDeThi = ?");
    $stmt->execute([$title, $duration, $status, $examId]);

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật thông tin đề thi thành công!'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
