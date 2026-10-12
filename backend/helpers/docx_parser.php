<?php
/**
 * DOCX Parser for TOEIC Reading Exams
 * Phân tích cấu trúc file Word (.docx) chứa thẻ tags chuẩn:
 * <TEST>, <PART id="5|6|7">, <PASSAGE>, <QUESTION>, <Q>, <CHOICES>, <C>, <ANSWER>, [PASSAGE_GROUP_END]
 */

class DocxParser {
    private string $filePath;
    private array $paragraphs = [];
    private array $extractedMedia = [];

    public function __construct(string $filePath) {
        $this->filePath = $filePath;
    }

    /**
     * Trích xuất toàn bộ đoạn văn bản từ file .docx
     */
    public function extractTextAndMedia(string $extractMediaDir = ''): bool {
        if (!file_exists($this->filePath)) {
            throw new Exception("File không tồn tại: " . $this->filePath);
        }

        $zip = new ZipArchive();
        if ($zip->open($this->filePath) !== true) {
            throw new Exception("Không thể mở file .docx (file bị hỏng hoặc sai định dạng zip).");
        }

        // 1. Trích xuất hình ảnh nếu có
        if (!empty($extractMediaDir)) {
            if (!is_dir($extractMediaDir)) {
                mkdir($extractMediaDir, 0777, true);
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (strpos($filename, 'word/media/') === 0) {
                    $mediaName = basename($filename);
                    $destPath = rtrim($extractMediaDir, '/') . '/' . $mediaName;
                    file_put_contents($destPath, $zip->getFromIndex($i));
                    $this->extractedMedia[$filename] = $mediaName;
                }
            }
        }

        // 2. Đọc nội dung word/document.xml
        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xmlContent) {
            throw new Exception("Không tìm thấy cấu trúc word/document.xml trong file .docx.");
        }

        $this->paragraphs = $this->parseXmlParagraphs($xmlContent);
        return true;
    }

    /**
     * Đọc các paragraph và gộp văn bản các run text (<w:t>)
     */
    private function parseXmlParagraphs(string $xmlContent): array {
        $paragraphs = [];
        
        // Sử dụng DOMDocument và XPath
        $dom = new DOMDocument();
        // Bỏ qua lỗi HTML/XML không chuẩn
        libxml_use_internal_errors(true);
        $dom->loadXML($xmlContent, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $pNodes = $xpath->query('//w:p');
        if ($pNodes) {
            foreach ($pNodes as $p) {
                $tNodes = $xpath->query('.//w:t', $p);
                $pText = '';
                foreach ($tNodes as $t) {
                    $pText .= $t->nodeValue;
                }
                $trimmed = trim($pText);
                if ($trimmed !== '') {
                    $paragraphs[] = $trimmed;
                }
            }
        }

        return $paragraphs;
    }

    public function getRawParagraphs(): array {
        return $this->paragraphs;
    }

    /**
     * Phân tích dữ liệu đề thi thành cấu trúc mảng phân cấp
     */
    public function parseExamStructure(): array {
        if (empty($this->paragraphs)) {
            $this->extractTextAndMedia();
        }

        $currentPart = 5; // Mặc định Part 5
        $passages = [];
        $currentPassage = null;
        $questions = [];
        $errors = [];
        $warnings = [];

        $inPassage = false;
        $passageBuffer = [];
        $passageType = null;

        $inQuestion = false;
        $currentQ = null;
        $inChoices = false;

        $totalParagraphs = count($this->paragraphs);

        for ($i = 0; $i < $totalParagraphs; $i++) {
            $line = $this->paragraphs[$i];

            // 1. Nhận diện Part
            if (preg_match('/<PART\s+id=[\'"]?(\d+)[\'"]?>/i', $line, $m)) {
                $currentPart = (int)$m[1];
                continue;
            } elseif (preg_match('/<\/PART>/i', $line)) {
                continue;
            } elseif (preg_match('/^PART\s*([567])\b/i', $line, $m)) {
                $currentPart = (int)$m[1];
            }

            // 2. Nhận diện Passage / Group
            if (preg_match('/<PASSAGE>/i', $line)) {
                $inPassage = true;
                $passageBuffer = [];
                // Lấy nội dung sau thẻ <PASSAGE> nếu cùng dòng
                $after = preg_replace('/^.*?<PASSAGE>/i', '', $line);
                if (preg_match('/<\/PASSAGE>/i', $after)) {
                    $text = preg_replace('/<\/PASSAGE>.*$/i', '', $after);
                    if (trim($text) !== '') $passageBuffer[] = trim($text);
                    $inPassage = false;
                    $currentPassage = $this->createPassageRecord($currentPart, implode("\n\n", $passageBuffer), count($passages) + 1);
                    $passages[] = &$currentPassage;
                    unset($currentPassage);
                    $currentPassage = &$passages[count($passages) - 1];
                } elseif (trim($after) !== '') {
                    $passageBuffer[] = trim($after);
                }
                continue;
            }

            if ($inPassage) {
                if (preg_match('/<\/PASSAGE>/i', $line)) {
                    $before = preg_replace('/<\/PASSAGE>.*$/i', '', $line);
                    if (trim($before) !== '') $passageBuffer[] = trim($before);
                    $inPassage = false;
                    $passageContent = trim(implode("\n\n", $passageBuffer));
                    $currentPassage = $this->createPassageRecord($currentPart, $passageContent, count($passages) + 1);
                    $passages[] = &$currentPassage;
                    unset($currentPassage);
                    $currentPassage = &$passages[count($passages) - 1];
                    continue;
                } else {
                    $passageBuffer[] = $line;
                    continue;
                }
            }

            // Nhận diện thẻ [PASSAGE_GROUP_END id="..."] hoặc nhóm đề
            if (preg_match('/\[PASSAGE_GROUP_END(?:\s+id=[\'"]?([0-9\-]+)[\'"]?)?\]/i', $line, $m)) {
                // Đôi khi nội dung bài đọc nằm ngay sau thẻ này
                $afterText = preg_replace('/\[PASSAGE_GROUP_END.*?\]/i', '', $line);
                if (trim($afterText) !== '') {
                    $currentPassage = $this->createPassageRecord($currentPart, trim($afterText), count($passages) + 1);
                    $passages[] = &$currentPassage;
                    unset($currentPassage);
                    $currentPassage = &$passages[count($passages) - 1];
                }
                continue;
            }

            // Nhận diện <GROUP>
            if (preg_match('/<GROUP>/i', $line)) {
                continue;
            }

            // Nhận diện mở câu hỏi <QUESTION> hoặc bắt đầu câu hỏi mới bằng <Q>
            $isQStart = preg_match('/<QUESTION>/i', $line);
            $hasQTag = preg_match('/<Q>/i', $line);

            if ($isQStart || (!$inQuestion && $hasQTag) || ($inQuestion && $hasQTag && $currentQ !== null && $currentQ['number'] !== null)) {
                if ($inQuestion && $currentQ !== null) {
                    $questions[] = $currentQ;
                    $currentQ = null;
                }
                $inQuestion = true;
                $currentQ = [
                    'line_index' => $i + 1,
                    'part' => $currentPart,
                    'number' => null,
                    'content' => '',
                    'choices' => [],
                    'correct_answer' => null,
                    'passage_temp_id' => ($currentPart >= 6 && isset($currentPassage)) ? $currentPassage['temp_id'] : null,
                    'source_page' => null,
                ];

                if ($isQStart) {
                    $rest = preg_replace('/^.*?<QUESTION>/i', '', $line);
                    if (trim($rest) !== '') {
                        $this->parseLineInsideQuestion($rest, $currentQ, $inChoices);
                    }
                } else {
                    $this->parseLineInsideQuestion($line, $currentQ, $inChoices);
                }
                continue;
            }

            // Nếu đang trong câu hỏi
            if ($inQuestion) {
                // Nhận diện đóng câu hỏi </QUESTION>
                if (preg_match('/<\/QUESTION>/i', $line)) {
                    $before = preg_replace('/<\/QUESTION>.*$/i', '', $line);
                    if (trim($before) !== '') {
                        $this->parseLineInsideQuestion($before, $currentQ, $inChoices);
                    }
                    $questions[] = $currentQ;
                    $inQuestion = false;
                    $currentQ = null;
                    $inChoices = false;
                    continue;
                }

                $this->parseLineInsideQuestion($line, $currentQ, $inChoices);
                continue;
            }

            // Trường hợp văn bản bên ngoài câu hỏi ở Part 6 & Part 7 (có thể là bài đọc chưa được bọc thẻ <PASSAGE>)
            if ($currentPart >= 6 && !preg_match('/<[^>]+>/', $line)) {
                if (!preg_match('/^PART\s*\d/i', $line) && !preg_match('/^Directions:/i', $line)) {
                    // Nếu là đoạn mô tả bài đọc e.g. "Questions 153-154 refer to..."
                    if (preg_match('/^Questions?\s+\d+/i', $line) || (isset($currentPassage) && strlen($currentPassage['content']) < 50)) {
                        if (isset($currentPassage)) {
                            $currentPassage['content'] .= "\n\n" . $line;
                            $currentPassage['type'] = $this->detectPassageType($currentPassage['content']);
                        } else {
                            $currentPassage = $this->createPassageRecord($currentPart, $line, count($passages) + 1);
                            $passages[] = &$currentPassage;
                            unset($currentPassage);
                            $currentPassage = &$passages[count($passages) - 1];
                        }
                    }
                }
            }
        }

        // Nếu còn câu hỏi dang dở cuối file
        if ($inQuestion && $currentQ !== null) {
            $questions[] = $currentQ;
        }

        // Hậu xử lý (Post-process) và chuẩn hóa dữ liệu
        $this->normalizeQuestions($questions);

        return [
            'part_counts' => [
                'part5' => count(array_filter($questions, fn($q) => $q['part'] === 5)),
                'part6' => count(array_filter($questions, fn($q) => $q['part'] === 6)),
                'part7' => count(array_filter($questions, fn($q) => $q['part'] === 7)),
                'total' => count($questions)
            ],
            'passages' => $passages,
            'questions' => $questions,
            'media' => $this->extractedMedia
        ];
    }

    /**
     * Phân tích từng dòng nằm trong khối <QUESTION>
     */
    private function parseLineInsideQuestion(string $line, array &$currentQ, bool &$inChoices): void {
        // Source page
        if (preg_match('/<SOURCE_PAGE>(.*?)<\/SOURCE_PAGE>/i', $line, $m)) {
            $currentQ['source_page'] = trim($m[1]);
        }

        // Q tag
        if (preg_match('/<Q>(.*?)<\/Q>/is', $line, $m)) {
            $qRaw = trim($m[1]);
            // Tách số thứ tự câu hỏi: e.g. "102. Staff at the Bismarck..." or "1 05. ..."
            if (preg_match('/^(\d[\s\d]*)\.\s*(.*)$/s', $qRaw, $qm)) {
                $cleanNum = (int)preg_replace('/\s+/', '', $qm[1]);
                $currentQ['number'] = $cleanNum;
                $currentQ['content'] = trim($qm[2]);
            } else {
                $currentQ['content'] = $qRaw;
            }
        } elseif (preg_match('/<Q>(.*)$/is', $line, $m)) {
            $currentQ['content'] .= ' ' . trim($m[1]);
        }

        // Choices open/close
        if (preg_match('/<CHOICES>/i', $line)) {
            $inChoices = true;
        }
        if (preg_match('/<\/CHOICES>/i', $line)) {
            $inChoices = false;
        }

        // C tags: <C>A. choice text</C> hoặc <CHOICE>(A) text</CHOICE>
        if (preg_match_all('/<(?:C|CHOICE)>(.*?)<\/(?:C|CHOICE)>/is', $line, $cm)) {
            foreach ($cm[1] as $cText) {
                $cText = trim($cText);
                // Tách ký hiệu (A), A., A)
                if (preg_match('/^\(?([A-D])\)?[\.\s]?\s*(.*)$/is', $cText, $km)) {
                    $key = strtoupper($km[1]);
                    $val = trim($km[2]);
                    $currentQ['choices'][$key] = $val;
                } else {
                    // Nếu không có tiền tố A., tự gán theo số lượng hiện có
                    $keys = ['A', 'B', 'C', 'D'];
                    $nextKey = $keys[count($currentQ['choices'])] ?? '?';
                    $currentQ['choices'][$nextKey] = $cText;
                }
            }
        } elseif ($inChoices && preg_match('/^\(?([A-D])\)[\.\s]?\s*(.*)$/is', $line, $km)) {
            // Trường hợp dòng nằm trong <CHOICES> nhưng không bọc thẻ <C>
            $key = strtoupper($km[1]);
            $val = trim($km[2]);
            $currentQ['choices'][$key] = $val;
        }

        // Answer tag: <ANSWER>A</ANSWER>, <DAPAN>A</DAPAN>, hoặc Đáp án: A
        if (preg_match('/<(?:ANSWER|ANS|DAPAN)>(.*?)<\/(?:ANSWER|ANS|DAPAN)>/is', $line, $am)) {
            $ans = strtoupper(trim($am[1]));
            if (in_array($ans, ['A', 'B', 'C', 'D'])) {
                $currentQ['correct_answer'] = $ans;
            } else {
                $currentQ['correct_answer'] = $ans !== '' ? $ans : null;
            }
        } elseif (preg_match('/(?:Đáp án|Answer):\s*([A-D])/i', $line, $am)) {
            $currentQ['correct_answer'] = strtoupper(trim($am[1]));
        }
    }

    /**
     * Chuẩn hóa danh sách câu hỏi
     */
    private function normalizeQuestions(array &$questions): void {
        foreach ($questions as &$q) {
            // Giữ nguyên part nếu đã được khai báo rõ trong thẻ <PART id="...">
            // Nếu chưa có hoặc cần tự động phân loại theo chuẩn New Format TOEIC:
            if (empty($q['part']) && !empty($q['number'])) {
                if ($q['number'] >= 101 && $q['number'] <= 130) {
                    $q['part'] = 5;
                } elseif ($q['number'] >= 131 && $q['number'] <= 146) {
                    $q['part'] = 6;
                } elseif ($q['number'] >= 147 && $q['number'] <= 200) {
                    $q['part'] = 7;
                }
            }

            // Đảm bảo đủ các key A, B, C, D
            $keys = ['A', 'B', 'C', 'D'];
            foreach ($keys as $k) {
                if (!isset($q['choices'][$k])) {
                    // Không có thì để rỗng hoặc chưa đủ
                }
            }
        }
    }

    /**
     * Tạo bản ghi DoanDoc
     */
    private function createPassageRecord(int $part, string $content, int $index): array {
        return [
            'temp_id' => 'passage_' . $index,
            'part' => ($part >= 6 ? $part : 6),
            'type' => $this->detectPassageType($content),
            'content' => $content,
            'image_url' => null,
        ];
    }

    /**
     * Tự động phát hiện loại bài đọc từ nội dung
     */
    private function detectPassageType(string $content): string {
        $c = strtolower($content);
        if (strpos($c, 'e-mail') !== false || strpos($c, 'email') !== false || strpos($c, 'from:') !== false && strpos($c, 'to:') !== false) {
            return 'E-mail';
        } elseif (strpos($c, 'text-message') !== false || strpos($c, 'chain') !== false) {
            return 'Text Message';
        } elseif (strpos($c, 'article') !== false) {
            return 'Article';
        } elseif (strpos($c, 'notice') !== false) {
            return 'Notice';
        } elseif (strpos($c, 'invoice') !== false) {
            return 'Invoice';
        } elseif (strpos($c, 'advertisement') !== false) {
            return 'Advertisement';
        } elseif (strpos($c, 'memo') !== false) {
            return 'Memorandum';
        } elseif (strpos($c, 'review') !== false) {
            return 'Review';
        } elseif (strpos($c, 'schedule') !== false) {
            return 'Schedule';
        }
        return 'Reading Passage';
    }
}
