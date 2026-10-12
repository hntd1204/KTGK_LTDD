<?php
/**
 * Chỉnh Sửa Nội Dung Đề Thi (Full Exam Editor)
 * Cho phép sửa tên, thời lượng, câu hỏi, 4 đáp án, đáp án đúng, bài đọc Part 6 & 7
 */
$pageTitle = 'Chỉnh Sửa Đề Thi';
$activePage = 'exams';
require_once __DIR__ . '/views/header.php';
require_once __DIR__ . '/config/database.php';

$examId = (int)($_GET['id'] ?? 0);
$db = Database::getConnection();

// Lấy thông tin đề thi
$stmt = $db->prepare("SELECT * FROM DeThi WHERE MaDeThi = ?");
$stmt->execute([$examId]);
$exam = $stmt->fetch();

if (!$exam) {
    echo "<div class='alert alert-danger'>Không tìm thấy đề thi #{$examId}. <a href='exams.php'>Quay lại</a></div>";
    require_once __DIR__ . '/views/footer.php';
    exit;
}

// Lấy danh sách đoạn đọc
$stmt = $db->prepare("SELECT * FROM DoanDoc WHERE MaDeThi = ? ORDER BY MaDoanDoc ASC");
$stmt->execute([$examId]);
$passages = $stmt->fetchAll();

// Lấy danh sách câu hỏi kèm 4 lựa chọn
$stmt = $db->prepare("
    SELECT c.*, 
           l.MaLuaChon, l.KyHieu, l.NoiDungLuaChon, l.LaDapAnDung
    FROM CauHoi c
    LEFT JOIN LuaChonDapAn l ON c.MaCauHoi = l.MaCauHoi
    WHERE c.MaDeThi = ?
    ORDER BY c.SoThuTuCau ASC, l.KyHieu ASC
");
$stmt->execute([$examId]);
$rows = $stmt->fetchAll();

$questions = [];
foreach ($rows as $r) {
    $qId = $r['MaCauHoi'];
    if (!isset($questions[$qId])) {
        $questions[$qId] = [
            'MaCauHoi' => $r['MaCauHoi'],
            'SoThuTuCau' => $r['SoThuTuCau'],
            'PhanThi' => $r['PhanThi'],
            'MaDoanDoc' => $r['MaDoanDoc'],
            'NoiDungCauHoi' => $r['NoiDungCauHoi'],
            'choices' => [],
            'correct_choice' => 'A'
        ];
    }
    if ($r['MaLuaChon']) {
        $questions[$qId]['choices'][$r['KyHieu']] = $r['NoiDungLuaChon'];
        if ($r['LaDapAnDung'] == 1) {
            $questions[$qId]['correct_choice'] = $r['KyHieu'];
        }
    }
}
?>

<!-- Header Toolbar -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px;">
                Chỉnh Sửa: <?= htmlspecialchars($exam['TenDeThi']) ?>
            </h2>
            <div style="font-size: 13px; color: var(--text-muted);">
                Mã đề: #<?= $examId ?> | Tổng số: <?= count($questions) ?> câu hỏi | <?= count($passages) ?> bài đọc
            </div>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="preview.php?id=<?= $examId ?>" class="btn btn-outline btn-sm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                Xem Trước (Preview)
            </a>
            <button onclick="openModal('modal-add-question')" class="btn btn-primary btn-sm">
                + Thêm Câu Hỏi Mới
            </button>
            <button onclick="openModal('modal-add-passage')" class="btn btn-secondary btn-sm">
                + Thêm Bài Đọc Mới
            </button>
        </div>
    </div>
</div>

<!-- Section 1: Thông Tin Chung Đề Thi -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Cài Đặt Đề Thi
        </div>
    </div>
    <form id="form-exam-info" onsubmit="saveExamInfo(event)" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 16px; align-items: end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Tên Đề Thi</label>
            <input type="text" id="edit-title" class="form-control" value="<?= htmlspecialchars($exam['TenDeThi']) ?>" required>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Thời Lượng (Phút)</label>
            <input type="number" id="edit-duration" class="form-control" value="<?= $exam['ThoiLuongPhut'] ?>" min="5" max="180" required>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Trạng Thái</label>
            <select id="edit-status" class="form-control">
                <option value="1" <?= $exam['TrangThai'] == 1 ? 'selected' : '' ?>>Đang mở</option>
                <option value="0" <?= $exam['TrangThai'] == 0 ? 'selected' : '' ?>>Tạm ẩn</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height: 42px;">Lưu Cài Đặt</button>
    </form>
</div>

<!-- Section 2: Quản Lý Đoạn Đọc (Part 6 & Part 7) -->
<?php if (!empty($passages)): ?>
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
            Quản Lý Bài Đọc (<?= count($passages) ?> bài đọc)
        </div>
    </div>
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php foreach ($passages as $p): ?>
            <div id="passage-item-<?= $p['MaDoanDoc'] ?>" style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge badge-purple">Mã #<?= $p['MaDoanDoc'] ?></span>
                        <span class="badge badge-secondary">Part <?= $p['PhanThi'] ?></span>
                        <input type="text" id="p-type-<?= $p['MaDoanDoc'] ?>" class="form-control" value="<?= htmlspecialchars($p['LoaiDoan'] ?? 'Reading Passage') ?>" style="width: 160px; padding: 4px 8px; font-size: 13px;">
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="savePassage(<?= $p['MaDoanDoc'] ?>)" class="btn btn-outline btn-sm">Lưu Bài Đọc</button>
                        <button onclick="deletePassage(<?= $p['MaDoanDoc'] ?>)" class="btn btn-danger btn-sm">Xóa</button>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 8px;">
                    <textarea id="p-content-<?= $p['MaDoanDoc'] ?>" class="form-control" style="min-height: 120px; font-size: 13px; font-family: monospace;"><?= htmlspecialchars($p['NoiDung']) ?></textarea>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <input type="text" id="p-image-<?= $p['MaDoanDoc'] ?>" class="form-control" placeholder="Đường dẫn hình ảnh minh họa (nếu có)" value="<?= htmlspecialchars($p['DuongDanHinh'] ?? '') ?>" style="font-size: 12px;">
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Section 3: Danh Sách Câu Hỏi -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            Danh Sách Câu Hỏi (<?= count($questions) ?> câu)
        </div>
        <div style="display: flex; gap: 10px;">
            <input type="text" id="search-q" placeholder="Tìm kiếm câu hỏi..." class="form-control" style="width: 200px; padding: 6px 10px; font-size: 13px;" onkeyup="filterQuestions(this.value)">
        </div>
    </div>

    <div id="questions-container" style="display: flex; flex-direction: column; gap: 20px;">
        <?php foreach ($questions as $q): ?>
            <div class="question-edit-card" id="q-card-<?= $q['MaCauHoi'] ?>" style="background: white; border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; box-shadow: var(--shadow-sm);">
                <!-- Top controls -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="badge badge-primary">ID #<?= $q['MaCauHoi'] ?></span>
                        <label style="font-size: 13px; font-weight: 600;">Câu số:</label>
                        <input type="number" id="q-num-<?= $q['MaCauHoi'] ?>" class="form-control" value="<?= $q['SoThuTuCau'] ?>" style="width: 80px; padding: 4px 8px; font-size: 14px; font-weight: 700;">
                        
                        <label style="font-size: 13px; font-weight: 600; margin-left: 10px;">Phần:</label>
                        <select id="q-part-<?= $q['MaCauHoi'] ?>" class="form-control" style="width: 100px; padding: 4px 8px; font-size: 13px;">
                            <option value="5" <?= $q['PhanThi'] == 5 ? 'selected' : '' ?>>Part 5</option>
                            <option value="6" <?= $q['PhanThi'] == 6 ? 'selected' : '' ?>>Part 6</option>
                            <option value="7" <?= $q['PhanThi'] == 7 ? 'selected' : '' ?>>Part 7</option>
                        </select>

                        <label style="font-size: 13px; font-weight: 600; margin-left: 10px;">Bài đọc:</label>
                        <select id="q-passage-<?= $q['MaCauHoi'] ?>" class="form-control" style="width: 160px; padding: 4px 8px; font-size: 13px;">
                            <option value="">(Không có bài đọc)</option>
                            <?php foreach ($passages as $p): ?>
                                <option value="<?= $p['MaDoanDoc'] ?>" <?= ($q['MaDoanDoc'] == $p['MaDoanDoc']) ? 'selected' : '' ?>>
                                    #<?= $p['MaDoanDoc'] ?> (Part <?= $p['PhanThi'] ?> - <?= htmlspecialchars($p['LoaiDoan']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button onclick="saveQuestion(<?= $q['MaCauHoi'] ?>)" class="btn btn-primary btn-sm">
                            ✓ Lưu Câu Này
                        </button>
                        <button onclick="deleteQuestion(<?= $q['MaCauHoi'] ?>)" class="btn btn-danger btn-sm">
                            Xóa
                        </button>
                    </div>
                </div>

                <!-- Question Text -->
                <div class="form-group">
                    <label class="form-label">Nội Dung Câu Hỏi</label>
                    <textarea id="q-content-<?= $q['MaCauHoi'] ?>" class="form-control" style="min-height: 60px;"><?= htmlspecialchars($q['NoiDungCauHoi']) ?></textarea>
                </div>

                <!-- 4 Choices & Correct Answer Selection -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; background: #f8fafc; padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <?php foreach (['A', 'B', 'C', 'D'] as $letter): ?>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label style="display: flex; align-items: center; gap: 4px; font-weight: 700; cursor: pointer;" title="Chọn <?= $letter ?> làm đáp án đúng">
                                <input type="radio" name="correct_ans_<?= $q['MaCauHoi'] ?>" value="<?= $letter ?>" <?= ($q['correct_choice'] === $letter) ? 'checked' : '' ?> style="accent-color: var(--success); width: 18px; height: 18px;">
                                <span class="choice-letter" style="width: 24px; height: 24px; font-size: 12px;"><?= $letter ?></span>
                            </label>
                            <input type="text" id="q-choice-<?= strtolower($letter) ?>-<?= $q['MaCauHoi'] ?>" class="form-control" value="<?= htmlspecialchars($q['choices'][$letter] ?? '') ?>" placeholder="Phương án <?= $letter ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Thêm Câu Hỏi Mới -->
<div class="modal-overlay" id="modal-add-question">
    <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header">
            <h3 class="modal-title">+ Thêm Câu Hỏi Mới</h3>
            <button class="modal-close" onclick="closeModal('modal-add-question')">&times;</button>
        </div>
        <form onsubmit="submitAddQuestion(event)">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Số thứ tự câu</label>
                    <input type="number" id="new-q-num" class="form-control" value="<?= count($questions) + 101 ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Phần thi</label>
                    <select id="new-q-part" class="form-control">
                        <option value="5">Part 5</option>
                        <option value="6">Part 6</option>
                        <option value="7">Part 7</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Gán bài đọc</label>
                    <select id="new-q-passage" class="form-control">
                        <option value="">(Không có bài đọc)</option>
                        <?php foreach ($passages as $p): ?>
                            <option value="<?= $p['MaDoanDoc'] ?>">#<?= $p['MaDoanDoc'] ?> (Part <?= $p['PhanThi'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Nội dung câu hỏi</label>
                <textarea id="new-q-content" class="form-control" placeholder="Nhập câu hỏi..." style="min-height: 60px;"></textarea>
            </div>

            <div style="background: #f8fafc; padding: 14px; border-radius: var(--radius-md); margin-bottom: 16px;">
                <div style="font-weight: 600; font-size: 13px; margin-bottom: 8px;">4 Lựa chọn & Đáp án đúng:</div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach (['A', 'B', 'C', 'D'] as $k): ?>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="radio" name="new_correct_ans" value="<?= $k ?>" <?= ($k === 'A') ? 'checked' : '' ?> style="accent-color: var(--success); width: 18px; height: 18px;">
                            <span class="choice-letter" style="width: 24px; height: 24px; font-size: 12px;"><?= $k ?></span>
                            <input type="text" id="new-choice-<?= strtolower($k) ?>" class="form-control" placeholder="Nội dung lựa chọn <?= $k ?>" required>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-add-question')">Hủy</button>
                <button type="submit" class="btn btn-primary">Thêm Câu Hỏi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Thêm Bài Đọc Mới -->
<div class="modal-overlay" id="modal-add-passage">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">+ Thêm Bài Đọc Mới (Part 6 & 7)</h3>
            <button class="modal-close" onclick="closeModal('modal-add-passage')">&times;</button>
        </div>
        <form onsubmit="submitAddPassage(event)">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Phần thi</label>
                    <select id="new-p-part" class="form-control">
                        <option value="6">Part 6 (Điền chỗ trống)</option>
                        <option value="7" selected>Part 7 (Đọc hiểu)</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Thể loại bài đọc</label>
                    <input type="text" id="new-p-type" class="form-control" value="E-mail" placeholder="E-mail, Article, Notice...">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Nội dung bài đọc</label>
                <textarea id="new-p-content" class="form-control" placeholder="Nhập văn bản bài đọc..." style="min-height: 140px;" required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Đường dẫn hình ảnh (nếu có)</label>
                <input type="text" id="new-p-image" class="form-control" placeholder="URL hình ảnh hoặc để trống">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeModal('modal-add-passage')">Hủy</button>
                <button type="submit" class="btn btn-primary">Tạo Bài Đọc</button>
            </div>
        </form>
    </div>
</div>

<script>
const EXAM_ID = <?= $examId ?>;

function saveExamInfo(e) {
    e.preventDefault();
    const title = document.getElementById('edit-title').value.trim();
    const duration = document.getElementById('edit-duration').value;
    const status = document.getElementById('edit-status').value;

    const fd = new FormData();
    fd.append('exam_id', EXAM_ID);
    fd.append('action', 'update_info');
    fd.append('title', title);
    fd.append('duration', duration);
    fd.append('status', status);

    fetch('ajax/update_exam.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => showToast(d.message, d.success ? 'success' : 'danger'))
        .catch(() => showToast('Lỗi kết nối', 'danger'));
}

function saveQuestion(qId) {
    const fd = new FormData();
    fd.append('question_id', qId);
    fd.append('question_number', document.getElementById('q-num-' + qId).value);
    fd.append('part', document.getElementById('q-part-' + qId).value);
    fd.append('passage_id', document.getElementById('q-passage-' + qId).value);
    fd.append('content', document.getElementById('q-content-' + qId).value);
    fd.append('choice_a', document.getElementById('q-choice-a-' + qId).value);
    fd.append('choice_b', document.getElementById('q-choice-b-' + qId).value);
    fd.append('choice_c', document.getElementById('q-choice-c-' + qId).value);
    fd.append('choice_d', document.getElementById('q-choice-d-' + qId).value);

    const checkedAns = document.querySelector(`input[name="correct_ans_${qId}"]:checked`);
    fd.append('correct_answer', checkedAns ? checkedAns.value : 'A');

    fetch('ajax/update_question.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => showToast(d.message, d.success ? 'success' : 'danger'))
        .catch(() => showToast('Lỗi kết nối', 'danger'));
}

function deleteQuestion(qId) {
    if (!confirm('Bạn có chắc chắn muốn xóa câu hỏi này?')) return;
    const fd = new FormData();
    fd.append('question_id', qId);

    fetch('ajax/delete_question.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const card = document.getElementById('q-card-' + qId);
                if (card) card.remove();
                showToast(d.message, 'success');
            } else {
                showToast(d.message, 'danger');
            }
        })
        .catch(() => showToast('Lỗi kết nối', 'danger'));
}

function submitAddQuestion(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('exam_id', EXAM_ID);
    fd.append('question_number', document.getElementById('new-q-num').value);
    fd.append('part', document.getElementById('new-q-part').value);
    fd.append('passage_id', document.getElementById('new-q-passage').value);
    fd.append('content', document.getElementById('new-q-content').value);
    fd.append('choice_a', document.getElementById('new-choice-a').value);
    fd.append('choice_b', document.getElementById('new-choice-b').value);
    fd.append('choice_c', document.getElementById('new-choice-c').value);
    fd.append('choice_d', document.getElementById('new-choice-d').value);

    const checkedAns = document.querySelector('input[name="new_correct_ans"]:checked');
    fd.append('correct_answer', checkedAns ? checkedAns.value : 'A');

    fetch('ajax/add_question.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast(d.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(d.message, 'danger');
            }
        })
        .catch(() => showToast('Lỗi kết nối', 'danger'));
}

function savePassage(pId) {
    const fd = new FormData();
    fd.append('action', 'update');
    fd.append('passage_id', pId);
    fd.append('type', document.getElementById('p-type-' + pId).value);
    fd.append('content', document.getElementById('p-content-' + pId).value);
    fd.append('image_url', document.getElementById('p-image-' + pId).value);

    fetch('ajax/update_passage.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => showToast(d.message, d.success ? 'success' : 'danger'))
        .catch(() => showToast('Lỗi kết nối', 'danger'));
}

function deletePassage(pId) {
    if (!confirm('Bạn có chắc chắn muốn xóa bài đọc này? Các câu hỏi thuộc bài đọc này sẽ chuyển sang độc lập.')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('passage_id', pId);

    fetch('ajax/update_passage.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const el = document.getElementById('passage-item-' + pId);
                if (el) el.remove();
                showToast(d.message, 'success');
            } else {
                showToast(d.message, 'danger');
            }
        })
        .catch(() => showToast('Lỗi kết nối', 'danger'));
}

function submitAddPassage(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', 'create');
    fd.append('exam_id', EXAM_ID);
    fd.append('part', document.getElementById('new-p-part').value);
    fd.append('type', document.getElementById('new-p-type').value);
    fd.append('content', document.getElementById('new-p-content').value);
    fd.append('image_url', document.getElementById('new-p-image').value);

    fetch('ajax/update_passage.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showToast(d.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(d.message, 'danger');
            }
        })
        .catch(() => showToast('Lỗi kết nối', 'danger'));
}

function filterQuestions(term) {
    term = term.toLowerCase();
    document.querySelectorAll('.question-edit-card').forEach(card => {
        const text = card.innerText.toLowerCase();
        card.style.display = text.includes(term) ? 'block' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
