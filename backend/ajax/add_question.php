<?php
/**
 * AJAX Endpoint: Add New Question to Exam
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

$db = Database::getConnection();

$examId = (int)($_POST['exam_id'] ?? 0);
$qNumber = (int)($_POST['question_number'] ?? 0);
$qPart = (int)($_POST['part'] ?? 5);
$qContent = trim($_POST['content'] ?? '');
$passageId = !empty($_POST['passage_id']) ? (int)$_POST['passage_id'] : null;
$correctAns = strtoupper(trim($_POST['correct_answer'] ?? 'A'));

$choiceA = trim($_POST['choice_a'] ?? '');
$choiceB = trim($_POST['choice_b'] ?? '');
$choiceC = trim($_POST['choice_c'] ?? '');
$choiceD = trim($_POST['choice_d'] ?? '');

if ($examId <= 0 || $qNumber <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thông tin câu hỏi không hợp lệ.']);
    exit;
}

try {
    $db->beginTransaction();

    // 1. Chèn câu hỏi
    $stmt = $db->prepare("
        INSERT INTO CauHoi (MaDeThi, MaDoanDoc, SoThuTuCau, PhanThi, NoiDungCauHoi)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$examId, $passageId, $qNumber, $qPart, $qContent]);
    $newQId = (int)$db->lastInsertId();

    // 2. Chèn 4 đáp án
    $choices = [
        'A' => $choiceA,
        'B' => $choiceB,
        'C' => $choiceC,
        'D' => $choiceD
    ];

    $stmtChoice = $db->prepare("
        INSERT INTO LuaChonDapAn (MaCauHoi, KyHieu, NoiDungLuaChon, LaDapAnDung)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($choices as $key => $text) {
        $isCorrect = ($correctAns === $key) ? 1 : 0;
        $stmtChoice->execute([$newQId, $key, $text, $isCorrect]);
    }

    $db->commit();
    echo json_encode([
        'success' => true, 
        'question_id' => $newQId,
        'message' => "Đã thêm mới câu {$qNumber} vào đề thi!"
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
