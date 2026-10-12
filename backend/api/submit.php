<?php
/**
 * REST API: Nộp bài thi và chấm điểm tự động
 * POST /backend/api/submit.php
 * Content-Type: application/json
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Chỉ chấp nhận phương thức POST'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    // Thử lấy từ $_POST
    $data = $_POST;
}

$examId = (int)($data['exam_id'] ?? $data['MaDeThi'] ?? 0);
$userId = trim($data['user_id'] ?? $data['MaNguoiDung'] ?? 'ThiSinh_AnDanh');
$candidateName = trim($data['candidate_name'] ?? $data['HoTen'] ?? 'Thí sinh tự do');
$timeSpent = (int)($data['time_spent_seconds'] ?? $data['ThoiGianLamGiay'] ?? 0);
$answers = $data['answers'] ?? $data['CauTraLoi'] ?? [];

if ($examId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Thiếu mã đề thi (exam_id)'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getConnection();

    // 1. Kiểm tra đề thi tồn tại
    $stmt = $db->prepare("SELECT * FROM DeThi WHERE MaDeThi = ?");
    $stmt->execute([$examId]);
    $exam = $stmt->fetch();
    if (!$exam) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Đề thi không tồn tại'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Lấy danh sách toàn bộ câu hỏi và đáp án đúng của đề thi
    $stmt = $db->prepare("
        SELECT c.MaCauHoi, c.SoThuTuCau, c.PhanThi, c.MaDoanDoc, c.NoiDungCauHoi,
               l.MaLuaChon AS MaDapAnDung, l.KyHieu AS KyHieuDung, l.NoiDungLuaChon AS NoiDungDapAnDung
        FROM CauHoi c
        LEFT JOIN LuaChonDapAn l ON c.MaCauHoi = l.MaCauHoi AND l.LaDapAnDung = 1
        WHERE c.MaDeThi = ?
        ORDER BY c.SoThuTuCau ASC
    ");
    $stmt->execute([$examId]);
    $questions = $stmt->fetchAll();

    if (empty($questions)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Đề thi chưa có câu hỏi nào'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Lấy tất cả các lựa chọn để trả về kết quả chi tiết
    $stmt = $db->prepare("
        SELECT l.MaLuaChon, l.MaCauHoi, l.KyHieu, l.NoiDungLuaChon, l.LaDapAnDung
        FROM LuaChonDapAn l
        INNER JOIN CauHoi c ON l.MaCauHoi = c.MaCauHoi
        WHERE c.MaDeThi = ?
        ORDER BY c.SoThuTuCau ASC, l.KyHieu ASC
    ");
    $stmt->execute([$examId]);
    $allChoices = $stmt->fetchAll();

    $choicesByQ = [];
    foreach ($allChoices as $ch) {
        $qId = $ch['MaCauHoi'];
        if (!isset($choicesByQ[$qId])) {
            $choicesByQ[$qId] = [];
        }
        $choicesByQ[$qId][] = [
            'MaLuaChon' => $ch['MaLuaChon'],
            'KyHieu' => $ch['KyHieu'],
            'NoiDung' => $ch['NoiDungLuaChon'],
            'LaDapAnDung' => (int)$ch['LaDapAnDung']
        ];
    }

    // Chuẩn hóa mảng trả lời của thí sinh thành Map: [MaCauHoi => MaLuaChon hoặc KyHieu]
    $userAnswersMap = [];
    foreach ($answers as $ans) {
        $qId = (int)($ans['question_id'] ?? $ans['MaCauHoi'] ?? 0);
        $choiceId = isset($ans['choice_id']) ? (int)$ans['choice_id'] : (isset($ans['MaLuaChon']) ? (int)$ans['MaLuaChon'] : null);
        $symbol = strtoupper(trim($ans['choice_symbol'] ?? $ans['KyHieu'] ?? ''));
        if ($qId > 0) {
            $userAnswersMap[$qId] = [
                'choice_id' => $choiceId,
                'symbol' => $symbol
            ];
        }
    }

    // 3. Tiến hành Transaction ghi nhận Lượt thi và Chấm điểm
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO LuotThi (MaDeThi, MaNguoiDung, ThoiGianBatDau, ThoiGianNop, TrangThai)
        VALUES (?, ?, DATE_SUB(NOW(), INTERVAL ? SECOND), NOW(), 'DaNop')
    ");
    $stmt->execute([$examId, $userId, max(1, $timeSpent)]);
    $attemptId = (int)$db->lastInsertId();

    $totalQuestions = count($questions);
    $correctCount = 0;
    $wrongCount = 0;
    $skippedCount = 0;
    $details = [];

    $stmtInsertAnswer = $db->prepare("
        INSERT INTO CauTraLoi (MaLuotThi, MaCauHoi, MaLuaChon)
        VALUES (?, ?, ?)
    ");

    foreach ($questions as $q) {
        $qId = (int)$q['MaCauHoi'];
        $correctChoiceId = $q['MaDapAnDung'] ? (int)$q['MaDapAnDung'] : null;
        $correctSymbol = $q['KyHieuDung'] ? strtoupper(trim($q['KyHieuDung'])) : '';

        $userChoice = $userAnswersMap[$qId] ?? null;
        $selectedChoiceId = null;
        $selectedSymbol = '';

        if ($userChoice) {
            if (!empty($userChoice['choice_id'])) {
                $selectedChoiceId = (int)$userChoice['choice_id'];
                // Tìm symbol
                if (isset($choicesByQ[$qId])) {
                    foreach ($choicesByQ[$qId] as $cItem) {
                        if ($cItem['MaLuaChon'] === $selectedChoiceId) {
                            $selectedSymbol = $cItem['KyHieu'];
                            break;
                        }
                    }
                }
            } elseif (!empty($userChoice['symbol']) && isset($choicesByQ[$qId])) {
                $selectedSymbol = $userChoice['symbol'];
                foreach ($choicesByQ[$qId] as $cItem) {
                    if ($cItem['KyHieu'] === $selectedSymbol) {
                        $selectedChoiceId = $cItem['MaLuaChon'];
                        break;
                    }
                }
            }
        }

        // Lưu câu trả lời
        $stmtInsertAnswer->execute([$attemptId, $qId, $selectedChoiceId]);

        $isCorrect = false;
        if ($selectedChoiceId !== null && $correctChoiceId !== null) {
            if ($selectedChoiceId === $correctChoiceId || ($selectedSymbol !== '' && $selectedSymbol === $correctSymbol)) {
                $isCorrect = true;
                $correctCount++;
            } else {
                $wrongCount++;
            }
        } elseif ($selectedSymbol !== '' && $correctSymbol !== '') {
            if ($selectedSymbol === $correctSymbol) {
                $isCorrect = true;
                $correctCount++;
            } else {
                $wrongCount++;
            }
        } else {
            $skippedCount++;
        }

        $details[] = [
            'question_id' => $qId,
            'question_number' => (int)$q['SoThuTuCau'],
            'part' => (int)$q['PhanThi'],
            'passage_id' => $q['MaDoanDoc'] ? (int)$q['MaDoanDoc'] : null,
            'question_text' => $q['NoiDungCauHoi'],
            'selected_choice_id' => $selectedChoiceId,
            'selected_symbol' => $selectedSymbol,
            'correct_choice_id' => $correctChoiceId,
            'correct_symbol' => $correctSymbol,
            'correct_answer_text' => $q['NoiDungDapAnDung'] ?? '',
            'is_correct' => $isCorrect,
            'is_skipped' => ($selectedChoiceId === null && empty($selectedSymbol)),
            'choices' => $choicesByQ[$qId] ?? []
        ];
    }

    // Tính điểm TOEIC Reading ước lượng: thang điểm 5 - 495
    // Nếu làm 100 câu: Điểm chuẩn TOEIC Reading = scale(Số câu đúng).
    // Tính xấp xỉ tỉ lệ chuẩn TOEIC:
    $accuracyPercent = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 1) : 0;
    $rawScore = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 490 + 5) : 5;
    $rawScore = min(495, max(5, round($rawScore / 5) * 5)); // Bội số của 5 theo chuẩn TOEIC

    // Ghi vào bảng KetQuaThi
    $stmtResult = $db->prepare("
        INSERT INTO KetQuaThi (MaLuotThi, SoCauDung, SoCauSai, SoCauBoQua, DiemSo)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtResult->execute([$attemptId, $correctCount, $wrongCount, $skippedCount, $rawScore]);

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Nộp bài và chấm điểm thành công!',
        'attempt_id' => $attemptId,
        'exam' => [
            'id' => (int)$exam['MaDeThi'],
            'title' => $exam['TenDeThi'],
            'duration_minutes' => (int)$exam['ThoiLuongPhut']
        ],
        'candidate' => [
            'user_id' => $userId,
            'name' => $candidateName,
            'time_spent_seconds' => $timeSpent
        ],
        'score_summary' => [
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'skipped_count' => $skippedCount,
            'accuracy_percent' => $accuracyPercent,
            'toeic_score' => $rawScore,
            'max_toeic_score' => 495
        ],
        'details' => $details
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi xử lý nộp bài: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
