<?php
/**
 * Danh Sách Đề Thi
 */
$pageTitle = 'Quản Lý Đề Thi';
$activePage = 'exams';
require_once __DIR__ . '/views/header.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getConnection();

// Lọc và tìm kiếm
$search = trim($_GET['q'] ?? '');
$sql = "
    SELECT d.*, 
           (SELECT COUNT(*) FROM CauHoi c WHERE c.MaDeThi = d.MaDeThi) as SoCauThucTe,
           (SELECT COUNT(*) FROM DoanDoc dd WHERE dd.MaDeThi = d.MaDeThi) as SoDoanDoc
    FROM DeThi d
";
$params = [];
if (!empty($search)) {
    $sql .= " WHERE d.TenDeThi LIKE ?";
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY d.MaDeThi DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$exams = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            Tất Cả Đề Thi (<?= count($exams) ?>)
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <form method="GET" action="exams.php" style="display: flex; gap: 8px;">
                <input type="text" name="q" class="form-control" placeholder="Tìm tên đề thi..." value="<?= htmlspecialchars($search) ?>" style="width: 220px; padding: 6px 12px; font-size: 13px;">
                <button type="submit" class="btn btn-outline btn-sm">Tìm kiếm</button>
            </form>
            <a href="import.php" class="btn btn-primary btn-sm">
                + Import File Word
            </a>
        </div>
    </div>

    <?php if (empty($exams)): ?>
        <div style="text-align: center; padding: 48px 16px;">
            <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
            <h3 style="font-size: 16px; margin-bottom: 8px;">Không tìm thấy đề thi nào</h3>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">
                Chưa có đề thi nào phù hợp với bộ lọc. Hãy tải lên file Word để tạo đề thi mới!
            </p>
            <a href="import.php" class="btn btn-primary">Import File Word Ngay</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Mã</th>
                        <th>Tên Đề Thi</th>
                        <th style="width: 120px;">Thời Lượng</th>
                        <th style="width: 110px;">Số Câu</th>
                        <th style="width: 110px;">Bài Đọc</th>
                        <th style="width: 120px;">Trạng Thái</th>
                        <th style="width: 150px;">Ngày Tạo</th>
                        <th style="width: 260px; text-align: right;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $exam): ?>
                        <tr id="exam-row-<?= $exam['MaDeThi'] ?>">
                            <td><strong>#<?= $exam['MaDeThi'] ?></strong></td>
                            <td>
                                <a href="preview.php?id=<?= $exam['MaDeThi'] ?>" style="font-weight: 600; font-size: 14px; color: var(--primary);">
                                    <?= htmlspecialchars($exam['TenDeThi']) ?>
                                </a>
                            </td>
                            <td><?= $exam['ThoiLuongPhut'] ?> phút</td>
                            <td>
                                <span class="badge badge-primary"><?= $exam['SoCauThucTe'] ?> câu</span>
                            </td>
                            <td>
                                <span class="badge badge-purple"><?= $exam['SoDoanDoc'] ?> đoạn</span>
                            </td>
                            <td>
                                <button class="badge <?= $exam['TrangThai'] == 1 ? 'badge-success' : 'badge-warning' ?>" 
                                        onclick="toggleExamStatus(<?= $exam['MaDeThi'] ?>, this)" 
                                        style="cursor: pointer; border: none; font-family: inherit;">
                                    <?= $exam['TrangThai'] == 1 ? '● Đang mở' : '● Tạm ẩn' ?>
                                </button>
                            </td>
                            <td style="color: var(--text-muted); font-size: 13px;">
                                <?= date('d/m/Y H:i', strtotime($exam['NgayTao'])) ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="preview.php?id=<?= $exam['MaDeThi'] ?>" class="btn btn-outline btn-sm" title="Xem trước đề thi">
                                    Xem trước
                                </a>
                                <a href="edit.php?id=<?= $exam['MaDeThi'] ?>" class="btn btn-primary btn-sm" title="Chỉnh sửa nội dung">
                                    Chỉnh sửa
                                </a>
                                <button onclick="deleteExam(<?= $exam['MaDeThi'] ?>, '<?= htmlspecialchars(addslashes($exam['TenDeThi'])) ?>')" class="btn btn-danger btn-sm" title="Xóa đề thi">
                                    Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleExamStatus(examId, btn) {
    const formData = new FormData();
    formData.append('exam_id', examId);
    formData.append('action', 'toggle_status');

    fetch('ajax/update_exam.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.new_status == 1) {
                btn.className = 'badge badge-success';
                btn.innerText = '● Đang mở';
            } else {
                btn.className = 'badge badge-warning';
                btn.innerText = '● Tạm ẩn';
            }
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Lỗi khi cập nhật trạng thái', 'danger');
        }
    })
    .catch(e => showToast('Lỗi kết nối máy chủ', 'danger'));
}

function deleteExam(examId, title) {
    if (!confirm(`Bạn có chắc chắn muốn xóa đề thi: "${title}"?\nThao tác này sẽ xóa toàn bộ câu hỏi và đoạn đọc liên quan!`)) {
        return;
    }

    const formData = new FormData();
    formData.append('exam_id', examId);

    fetch('ajax/delete_exam.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.getElementById('exam-row-' + examId);
            if (row) row.remove();
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Không thể xóa đề thi', 'danger');
        }
    })
    .catch(e => showToast('Lỗi kết nối máy chủ', 'danger'));
}
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
