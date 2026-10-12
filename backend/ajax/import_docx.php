<?php
/**
 * AJAX Endpoint: Import DOCX into Database
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/docx_parser.php';
require_once __DIR__ . '/../helpers/exam_validator.php';
require_once __DIR__ . '/../helpers/exam_importer.php';

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['docx_file'])) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy file tải lên.']);
    exit;
}

$file = $_FILES['docx_file'];
$examTitle = trim($_POST['exam_title'] ?? 'Đề Thi TOEIC Reading');
$duration = (int)($_POST['exam_duration'] ?? 75);
$isStrict = isset($_POST['strict_validation']) && $_POST['strict_validation'] === '1';

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Lỗi upload file.']);
    exit;
}

try {
    $parser = new DocxParser($file['tmp_name']);
    $parsedData = $parser->parseExamStructure();

    // 1. Kiểm tra định dạng
    $report = ExamValidator::validate($parsedData, $isStrict);

    // Nếu ở chế độ nghiêm ngặt và có lỗi -> Từ chối lưu
    if ($isStrict && !$report['is_valid']) {
        echo json_encode([
            'success' => false,
            'message' => 'Phát hiện lỗi định dạng! Hệ thống từ chối lưu vào CSDL theo quy định.',
            'report' => $report,
            'part_counts' => $parsedData['part_counts'] ?? []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Lưu vào CSDL
    $result = ExamImporter::import($examTitle, $duration, $parsedData);
    $result['report'] = $report;
    $result['part_counts'] = $parsedData['part_counts'] ?? [];

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi xử lý file: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
