<?php
/**
 * AJAX Endpoint: Validate DOCX File without saving to DB
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/docx_parser.php';
require_once __DIR__ . '/../helpers/exam_validator.php';

if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['docx_file'])) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy file tải lên.']);
    exit;
}

$file = $_FILES['docx_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Lỗi upload file: ' . $file['error']]);
    exit;
}

$strictAnswers = isset($_POST['strict_answers']) && $_POST['strict_answers'] === '1';

try {
    $parser = new DocxParser($file['tmp_name']);
    $parsedData = $parser->parseExamStructure();
    $report = ExamValidator::validate($parsedData, $strictAnswers);

    echo json_encode([
        'success' => true,
        'report' => $report,
        'part_counts' => $parsedData['part_counts'] ?? []
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi phân tích file Word: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
