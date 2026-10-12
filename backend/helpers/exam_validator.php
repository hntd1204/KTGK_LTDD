<?php
/**
 * Exam Validator - Kiểm tra tính hợp lệ và cấu trúc chuẩn của đề thi Word
 * Tuân thủ yêu cầu đề bài: Chỉ lưu khi file đúng 100% chuẩn.
 * Báo lỗi chi tiết để Giảng viên sửa file.
 */

class ExamValidator {
    /**
     * Xác thực cấu trúc dữ liệu đề thi đã parse
     * @param array $parsedData Kết quả từ DocxParser
     * @param bool $strictAnswers Bắt buộc phải có sẵn thẻ <ANSWER> chứa đáp án đúng
     */
    public static function validate(array $parsedData, bool $strictAnswers = true): array {
        $questions = $parsedData['questions'] ?? [];
        $passages = $parsedData['passages'] ?? [];

        $errors = [];
        $warnings = [];

        // 1. Kiểm tra rỗng
        if (empty($questions)) {
            $errors[] = [
                'type' => 'EMPTY_EXAM',
                'severity' => 'danger',
                'question_number' => null,
                'message' => 'Không tìm thấy câu hỏi nào trong file. Vui lòng kiểm tra lại bộ thẻ <QUESTION> và <Q>.',
                'suggestion' => 'Đảm bảo mỗi câu hỏi được bao bọc bởi <QUESTION> ... </QUESTION>.'
            ];
            return self::buildReport($errors, $warnings, $questions, $passages);
        }

        // 2. Kiểm tra số thứ tự câu và trùng lặp
        $seenNumbers = [];
        $minNum = 9999;
        $maxNum = 0;

        foreach ($questions as $index => $q) {
            $qNum = $q['number'];
            $qDisplay = $qNum ?? ('Vị trí #' . ($index + 1));

            // a. Thiếu số thứ tự
            if ($qNum === null) {
                $errors[] = [
                    'type' => 'MISSING_Q_NUMBER',
                    'severity' => 'danger',
                    'question_number' => null,
                    'line_index' => $q['line_index'] ?? null,
                    'message' => "Câu hỏi tại dòng {$q['line_index']} thiếu số thứ tự câu (VD: <Q>101. ...</Q>).",
                    'suggestion' => 'Thêm số thứ tự và dấu chấm ở đầu thẻ <Q>, ví dụ: <Q>101. Staff at the...</Q>'
                ];
            } else {
                if ($qNum < $minNum) $minNum = $qNum;
                if ($qNum > $maxNum) $maxNum = $qNum;

                // b. Trùng số thứ tự câu
                if (isset($seenNumbers[$qNum])) {
                    $errors[] = [
                        'type' => 'DUPLICATE_Q_NUMBER',
                        'severity' => 'danger',
                        'question_number' => $qNum,
                        'line_index' => $q['line_index'] ?? null,
                        'message' => "Câu số {$qNum} bị trùng lặp (xuất hiện nhiều hơn 1 lần trong file).",
                        'suggestion' => "Kiểm tra lại nội dung, sửa lại số thứ tự câu {$qNum} hoặc xóa câu bị lặp."
                    ];
                }
                $seenNumbers[$qNum] = true;
            }

            // c. Nội dung câu hỏi rỗng
            if (empty(trim($q['content']))) {
                // Với Part 6, chỗ trống nằm trong bài đọc, câu hỏi chỉ là số thứ tự (VD: 131, 132) - hoàn toàn đúng chuẩn TOEIC
                if ($q['part'] !== 6 && ($q['part'] === 5 || $q['part'] === 7)) {
                    $warnings[] = [
                        'type' => 'EMPTY_QUESTION_CONTENT',
                        'severity' => 'warning',
                        'question_number' => $qNum,
                        'message' => "Câu số {$qDisplay} có nội dung câu hỏi rỗng.",
                        'suggestion' => 'Điền nội dung câu hỏi bên trong thẻ <Q>...</Q>.'
                    ];
                }
            }

            // d. Kiểm tra 4 phương án lựa chọn A, B, C, D
            $choices = $q['choices'] ?? [];
            $requiredChoices = ['A', 'B', 'C', 'D'];
            $missingChoices = [];

            foreach ($requiredChoices as $choiceKey) {
                if (!isset($choices[$choiceKey]) || trim($choices[$choiceKey]) === '') {
                    $missingChoices[] = $choiceKey;
                }
            }

            if (!empty($missingChoices)) {
                $errors[] = [
                    'type' => 'MISSING_CHOICES',
                    'severity' => 'danger',
                    'question_number' => $qNum,
                    'line_index' => $q['line_index'] ?? null,
                    'message' => "Câu số {$qDisplay} thiếu lựa chọn: " . implode(', ', $missingChoices) . " (hiện có " . count($choices) . "/4 lựa chọn).",
                    'suggestion' => 'Bổ sung đầy đủ 4 lựa chọn <C>A. ...</C>, <C>B. ...</C>, <C>C. ...</C>, <C>D. ...</C> bên trong <CHOICES>.'
                ];
            }

            // e. Kiểm tra đáp án đúng <ANSWER>
            $ans = $q['correct_answer'];
            if (empty($ans)) {
                if ($strictAnswers) {
                    $errors[] = [
                        'type' => 'MISSING_CORRECT_ANSWER',
                        'severity' => 'danger',
                        'question_number' => $qNum,
                        'line_index' => $q['line_index'] ?? null,
                        'message' => "Câu số {$qDisplay} chưa có đáp án đúng (<ANSWER></ANSWER> bị rỗng hoặc thiếu).",
                        'suggestion' => 'Điền đáp án đúng vào thẻ <ANSWER>, ví dụ: <ANSWER>A</ANSWER> hoặc sử dụng trình chỉnh sửa web để chọn đáp án sau khi import.'
                    ];
                } else {
                    $warnings[] = [
                        'type' => 'MISSING_CORRECT_ANSWER',
                        'severity' => 'warning',
                        'question_number' => $qNum,
                        'line_index' => $q['line_index'] ?? null,
                        'message' => "Câu số {$qDisplay} chưa có đáp án đúng. Bạn có thể bổ sung qua giao diện Chỉnh sửa sau khi import.",
                        'suggestion' => 'Cần chọn đáp án đúng trên giao diện quản trị trước khi phát hành đề cho học viên thi.'
                    ];
                }
            } elseif (!in_array($ans, ['A', 'B', 'C', 'D'])) {
                $errors[] = [
                    'type' => 'INVALID_ANSWER_KEY',
                    'severity' => 'danger',
                    'question_number' => $qNum,
                    'line_index' => $q['line_index'] ?? null,
                    'message' => "Câu số {$qDisplay} có đáp án '{$ans}' không hợp lệ (chỉ chấp nhận A, B, C hoặc D).",
                    'suggestion' => 'Sửa thẻ <ANSWER> thành một trong 4 ký hiệu A, B, C, D.'
                ];
            }

            // f. Kiểm tra liên kết bài đọc đối với Part 6 và Part 7
            if ($q['part'] >= 6 && empty($q['passage_temp_id'])) {
                $warnings[] = [
                    'type' => 'UNLINKED_PASSAGE',
                    'severity' => 'warning',
                    'question_number' => $qNum,
                    'message' => "Câu số {$qDisplay} thuộc Part {$q['part']} nhưng chưa được liên kết với đoạn văn đọc nào.",
                    'suggestion' => 'Đảm bảo câu hỏi nằm sau thẻ <PASSAGE>...</PASSAGE> hoặc nhóm bài đọc tương ứng.'
                ];
            }
        }

        // 3. Kiểm tra tính liên tục của số thứ tự câu (nếu là bài TOEIC Reading tiêu chuẩn 101 - 200)
        if ($minNum >= 101 && $maxNum <= 200) {
            $missingRange = [];
            for ($k = $minNum; $k <= $maxNum; $k++) {
                if (!isset($seenNumbers[$k])) {
                    $missingRange[] = $k;
                }
            }
            if (!empty($missingRange)) {
                $errStr = count($missingRange) > 10 
                    ? implode(', ', array_slice($missingRange, 0, 10)) . '... (tổng cộng ' . count($missingRange) . ' câu)'
                    : implode(', ', $missingRange);

                $errors[] = [
                    'type' => 'DISCONTINUOUS_QUESTION_NUMBERS',
                    'severity' => 'warning',
                    'question_number' => null,
                    'message' => "Phát hiện thiếu các câu hỏi trong dải số từ {$minNum} đến {$maxNum}: {$errStr}.",
                    'suggestion' => 'Kiểm tra file Word để đảm bảo không bị sót câu hỏi nào trong đề thi.'
                ];
            }
        }

        return self::buildReport($errors, $warnings, $questions, $passages);
    }

    private static function buildReport(array $errors, array $warnings, array $questions, array $passages): array {
        $dangerErrors = array_filter($errors, fn($e) => $e['severity'] === 'danger');
        $isValid = count($dangerErrors) === 0;

        return [
            'is_valid' => $isValid,
            'total_questions' => count($questions),
            'total_passages' => count($passages),
            'error_count' => count($dangerErrors),
            'warning_count' => count($warnings) + (count($errors) - count($dangerErrors)),
            'errors' => $errors,
            'warnings' => $warnings,
            'summary' => $isValid 
                ? 'File Word hoàn toàn hợp lệ và đúng chuẩn cấu trúc 100%!' 
                : 'Phát hiện ' . count($dangerErrors) . ' lỗi định dạng cần xử lý trước khi lưu vào CSDL.'
        ];
    }
}
