<?php
/**
 * Exam Importer - Lưu dữ liệu đề thi vào MySQL Database
 * Sử dụng PDO Transaction để đảm bảo tính toàn vẹn: chỉ commit khi 100% thành công!
 */

require_once __DIR__ . '/../config/database.php';

class ExamImporter {
    /**
     * Import toàn bộ dữ liệu đề thi vào cơ sở dữ liệu
     * @param string $examTitle Tên đề thi (VD: TOEIC Reading Practice Test 01)
     * @param int $duration Thời lượng (phút, mặc định 75)
     * @param array $parsedData Dữ liệu đã parse từ DocxParser
     * @return array [success => bool, exam_id => int, message => string]
     */
    public static function import(string $examTitle, int $duration, array $parsedData): array {
        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            $questions = $parsedData['questions'] ?? [];
            $passages = $parsedData['passages'] ?? [];
            $totalQuestions = count($questions);

            // 1. Chèn vào bảng DeThi
            $stmtDeThi = $db->prepare("
                INSERT INTO DeThi (TenDeThi, ThoiLuongPhut, TongSoCau, TrangThai, NgayTao)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmtDeThi->execute([$examTitle, $duration, $totalQuestions]);
            $examId = (int)$db->lastInsertId();

            // 2. Chèn các đoạn đọc (DoanDoc) và lưu ánh xạ temp_id => MaDoanDoc
            $passageIdMap = [];
            $stmtDoanDoc = $db->prepare("
                INSERT INTO DoanDoc (MaDeThi, PhanThi, LoaiDoan, NoiDung, DuongDanHinh)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($passages as $p) {
                $pPart = ($p['part'] >= 6) ? $p['part'] : 6;
                $stmtDoanDoc->execute([
                    $examId,
                    $pPart,
                    $p['type'] ?? 'Reading Passage',
                    $p['content'] ?? '',
                    $p['image_url'] ?? null
                ]);
                $newPassageId = (int)$db->lastInsertId();
                if (isset($p['temp_id'])) {
                    $passageIdMap[$p['temp_id']] = $newPassageId;
                }
            }

            // 3. Chèn câu hỏi (CauHoi) và các lựa chọn đáp án (LuaChonDapAn)
            $stmtCauHoi = $db->prepare("
                INSERT INTO CauHoi (MaDeThi, MaDoanDoc, SoThuTuCau, PhanThi, NoiDungCauHoi)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmtLuaChon = $db->prepare("
                INSERT INTO LuaChonDapAn (MaCauHoi, KyHieu, NoiDungLuaChon, LaDapAnDung)
                VALUES (?, ?, ?, ?)
            ");

            // Sắp xếp câu hỏi theo số thứ tự nếu có
            usort($questions, function($a, $b) {
                return ($a['number'] ?? 0) <=> ($b['number'] ?? 0);
            });

            $orderIndex = 1;
            foreach ($questions as $q) {
                $qNumber = $q['number'] ?? (100 + $orderIndex);
                $qPart = $q['part'] ?? 5;
                $qContent = trim($q['content'] ?? '');

                // Xác định MaDoanDoc liên kết
                $maDoanDoc = null;
                if ($qPart >= 6 && !empty($q['passage_temp_id']) && isset($passageIdMap[$q['passage_temp_id']])) {
                    $maDoanDoc = $passageIdMap[$q['passage_temp_id']];
                }

                $stmtCauHoi->execute([
                    $examId,
                    $maDoanDoc,
                    $qNumber,
                    $qPart,
                    $qContent
                ]);
                $cauHoiId = (int)$db->lastInsertId();

                // Chèn 4 đáp án A, B, C, D
                $choices = $q['choices'] ?? [];
                $correctAns = strtoupper(trim($q['correct_answer'] ?? ''));

                $choiceKeys = ['A', 'B', 'C', 'D'];
                foreach ($choiceKeys as $key) {
                    $cText = $choices[$key] ?? '';
                    $isCorrect = ($correctAns === $key) ? 1 : 0;

                    $stmtLuaChon->execute([
                        $cauHoiId,
                        $key,
                        $cText,
                        $isCorrect
                    ]);
                }

                $orderIndex++;
            }

            // Commit transaction
            $db->commit();

            return [
                'success' => true,
                'exam_id' => $examId,
                'total_questions' => $totalQuestions,
                'total_passages' => count($passages),
                'message' => "Import đề thi '{$examTitle}' thành công ({$totalQuestions} câu hỏi, " . count($passages) . " bài đọc)!"
            ];
        } catch (Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'Lỗi khi lưu dữ liệu đề thi vào CSDL: ' . $e->getMessage()
            ];
        }
    }
}
