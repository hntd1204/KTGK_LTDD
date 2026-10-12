<?php
/**
 * AJAX Endpoint: Update Question, Choices & Correct Answer
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

$db = Database::getConnection();

$qId = (int)($_POST['question_id'] ?? 0);
$qNumber = (int)($_POST['question_number'] ?? 0);
$qPart = (int)($_POST['part'] ?? 5);
$qContent = trim($_POST['content'] ?? '');
$passageId = !empty($_POST['passage_id']) ? (int)$_POST['passage_id'] : null;
$correctAns = strtoupper(trim($_POST['correct_answer'] ?? 'A'));

$choiceA = trim($_POST['choice_a'] ?? '');
$choiceB = trim($_POST['choice_b'] ?? '');
$choiceC = trim($_POST['choice_c'] ?? '');
$choiceD = trim($_POST['choice_d'] ?? '');

if ($qId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mã câu hỏi không hợp lệ.']);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Cập nhật bảng CauHoi
    $stmt = $db->prepare("
        UPDATE CauHoi 
        SET SoThuTuCau = ?, PhanThi = ?, NoiDungCauHoi = ?, MaDoanDoc = ?
        WHERE MaCauHoi = ?
    ");
    $stmt->execute([$qNumber, $qPart, $qContent, $passageId, $qId]);

    // 2. Cập nhật 4 lựa chọn đáp án
    $choices = [
        'A' => $choiceA,
        'B' => $choiceB,
        'C' => $choiceC,
        'D' => $choiceD
    ];

    $stmtChoice = $db->prepare("
        INSERT INTO LuaChonDapAn (MaCauHoi, KyHieu, NoiDungLuaChon, LaDapAnDung)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            NoiDungLuaChon = VALUES(NoiDungLuaChon),
            LaDapAnDung = VALUES(LaDapAnDung)
    ");

    foreach ($choices as $key => $text) {
        $isCorrect = ($correctAns === $key) ? 1 : 0;
        $stmtChoice->execute([$qId, $key, $text, $isCorrect]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => "Đã lưu thay đổi cho câu {$qNumber}!"], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
