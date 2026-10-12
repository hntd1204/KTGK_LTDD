<?php
/**
 * Xem Trước Đề Thi (Preview & Rà Soát Lỗi Chính Tả / Bố Cục)
 * Bố cục phân chia Part 5, Part 6, Part 7 chuyên nghiệp
 */
$pageTitle = 'Xem Trước & Rà Soát Đề Thi';
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
    echo "<div class='alert alert-danger'>Không tìm thấy đề thi #{$examId}. <a href='exams.php'>Quay lại danh sách</a></div>";
    require_once __DIR__ . '/views/footer.php';
    exit;
}

// Lấy danh sách đoạn đọc
$stmt = $db->prepare("SELECT * FROM DoanDoc WHERE MaDeThi = ? ORDER BY MaDoanDoc ASC");
$stmt->execute([$examId]);
$passages = $stmt->fetchAll();
$passageMap = [];
foreach ($passages as $p) {
    $passageMap[$p['MaDoanDoc']] = $p;
}

// Lấy danh sách câu hỏi kèm đáp án
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

// Gom nhóm câu hỏi
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
            'choices' => []
        ];
    }
    if ($r['MaLuaChon']) {
        $questions[$qId]['choices'][$r['KyHieu']] = [
            'MaLuaChon' => $r['MaLuaChon'],
            'NoiDung' => $r['NoiDungLuaChon'],
            'LaDapAnDung' => (int)$r['LaDapAnDung']
        ];
    }
}

// Phân loại câu hỏi theo Part
$part5 = array_filter($questions, fn($q) => $q['PhanThi'] == 5);
$part6 = array_filter($questions, fn($q) => $q['PhanThi'] == 6);
$part7 = array_filter($questions, fn($q) => $q['PhanThi'] == 7);
?>

<!-- Header Summary Card -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <h2 style="font-size: 22px; font-weight: 800; color: var(--text-primary);">
                    <?= htmlspecialchars($exam['TenDeThi']) ?>
                </h2>
                <span class="badge <?= $exam['TrangThai'] == 1 ? 'badge-success' : 'badge-warning' ?>">
                    <?= $exam['TrangThai'] == 1 ? '● Đang mở' : '● Tạm ẩn' ?>
                </span>
            </div>
            <div style="font-size: 13px; color: var(--text-muted); display: flex; gap: 16px;">
                <span>⏱ Thời lượng: <b><?= $exam['ThoiLuongPhut'] ?> phút</b></span>
                <span>📋 Tổng số câu: <b><?= count($questions) ?> câu</b></span>
                <span>📅 Ngày tạo: <b><?= date('d/m/Y H:i', strtotime($exam['NgayTao'])) ?></b></span>
            </div>
        </div>

        <div style="display: flex; gap: 10px;">
            <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; cursor: pointer; background: var(--bg-main); padding: 8px 14px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                <input type="checkbox" id="toggle-answers" checked onchange="toggleAnswers(this.checked)" style="accent-color: var(--success); width: 16px; height: 16px;">
                Hiện đáp án đúng
            </label>
            <a href="edit.php?id=<?= $examId ?>" class="btn btn-primary btn-sm">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                Chỉnh Sửa Nội Dung Đề
            </a>
            <a href="exams.php" class="btn btn-outline btn-sm">Quay lại danh sách</a>
        </div>
    </div>
</div>

<!-- Tabs for Part Navigation -->
<div class="tab-nav">
    <button class="tab-btn active" onclick="switchTab('all', this)">
        Tất Cả (<?= count($questions) ?>)
    </button>
    <button class="tab-btn" onclick="switchTab('part5', this)">
        Part 5: Incomplete Sentences (<?= count($part5) ?>)
    </button>
    <button class="tab-btn" onclick="switchTab('part6', this)">
        Part 6: Text Completion (<?= count($part6) ?>)
    </button>
    <button class="tab-btn" onclick="switchTab('part7', this)">
        Part 7: Reading Comprehension (<?= count($part7) ?>)
    </button>
</div>

<!-- Content View Containers -->

<!-- Part 5 Section -->
<div class="part-section" id="section-part5">
    <div style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="font-size: 18px; font-weight: 700; color: var(--primary);">
            Part 5: Incomplete Sentences (Câu 101 - 140)
        </h3>
        <span class="badge badge-primary"><?= count($part5) ?> câu hỏi</span>
    </div>

    <?php if (empty($part5)): ?>
        <div class="alert alert-info">Không có câu hỏi nào thuộc Part 5.</div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(460px, 1fr)); gap: 16px;">
            <?php foreach ($part5 as $q): ?>
                <div class="question-item">
                    <div class="question-title">
                        <span class="badge badge-primary">Câu <?= $q['SoThuTuCau'] ?></span>
                        <div style="flex: 1;"><?= htmlspecialchars($q['NoiDungCauHoi']) ?></div>
                    </div>
                    <div class="choices-list">
                        <?php foreach (['A', 'B', 'C', 'D'] as $k): ?>
                            <?php 
                                $c = $q['choices'][$k] ?? null; 
                                $isCorrect = $c && $c['LaDapAnDung'] == 1;
                            ?>
                            <div class="choice-option <?= $isCorrect ? 'correct is-correct-answer' : '' ?>">
                                <div class="choice-letter"><?= $k ?></div>
                                <div style="flex: 1;"><?= htmlspecialchars($c['NoiDung'] ?? '---') ?></div>
                                <?php if ($isCorrect): ?>
                                    <span class="badge badge-success answer-tag" style="font-size: 11px;">Đáp án đúng</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Part 6 Section -->
<div class="part-section" id="section-part6" style="margin-top: 36px;">
    <div style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="font-size: 18px; font-weight: 700; color: var(--purple);">
            Part 6: Text Completion (Câu 141 - 152)
        </h3>
        <span class="badge badge-purple"><?= count($part6) ?> câu hỏi</span>
    </div>

    <?php 
    // Gom nhóm câu hỏi Part 6 theo MaDoanDoc
    $p6Groups = [];
    foreach ($part6 as $q) {
        $pId = $q['MaDoanDoc'] ?? 0;
        $p6Groups[$pId][] = $q;
    }
    ?>

    <?php if (empty($p6Groups)): ?>
        <div class="alert alert-info">Không có câu hỏi nào thuộc Part 6.</div>
    <?php else: ?>
        <?php foreach ($p6Groups as $pId => $groupQuestions): ?>
            <?php $passage = $passageMap[$pId] ?? null; ?>
            <div class="card" style="margin-bottom: 24px;">
                <div class="split-container">
                    <!-- Cột Bài Đọc -->
                    <div class="passage-panel">
                        <div class="passage-header">
                            <div>
                                <span class="badge badge-purple"><?= htmlspecialchars($passage['LoaiDoan'] ?? 'Đoạn văn') ?></span>
                                <b style="margin-left: 8px;">Đoạn Đọc #<?= $pId ?></b>
                            </div>
                        </div>
                        <?php if (!empty($passage['DuongDanHinh'])): ?>
                            <div style="margin-bottom: 14px;">
                                <img src="<?= htmlspecialchars($passage['DuongDanHinh']) ?>" alt="Minh họa" style="max-width: 100%; border-radius: var(--radius-md);">
                            </div>
                        <?php endif; ?>
                        <div class="passage-content"><?= htmlspecialchars($passage['NoiDung'] ?? '(Chưa có nội dung bài đọc)') ?></div>
                    </div>

                    <!-- Cột Câu Hỏi Đi Kèm -->
                    <div>
                        <?php foreach ($groupQuestions as $q): ?>
                            <div class="question-item">
                                <div class="question-title">
                                    <span class="badge badge-purple">Câu <?= $q['SoThuTuCau'] ?></span>
                                    <div style="flex: 1;"><?= htmlspecialchars($q['NoiDungCauHoi'] ?: '(Chỗ trống trong bài đọc)') ?></div>
                                </div>
                                <div class="choices-list">
                                    <?php foreach (['A', 'B', 'C', 'D'] as $k): ?>
                                        <?php 
                                            $c = $q['choices'][$k] ?? null; 
                                            $isCorrect = $c && $c['LaDapAnDung'] == 1;
                                        ?>
                                        <div class="choice-option <?= $isCorrect ? 'correct is-correct-answer' : '' ?>">
                                            <div class="choice-letter"><?= $k ?></div>
                                            <div style="flex: 1;"><?= htmlspecialchars($c['NoiDung'] ?? '---') ?></div>
                                            <?php if ($isCorrect): ?>
                                                <span class="badge badge-success answer-tag" style="font-size: 11px;">Đáp án đúng</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Part 7 Section -->
<div class="part-section" id="section-part7" style="margin-top: 36px;">
    <div style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="font-size: 18px; font-weight: 700; color: var(--secondary);">
            Part 7: Reading Comprehension (Câu 153 - 200)
        </h3>
        <span class="badge badge-secondary"><?= count($part7) ?> câu hỏi</span>
    </div>

    <?php 
    // Gom nhóm câu hỏi Part 7 theo MaDoanDoc
    $p7Groups = [];
    foreach ($part7 as $q) {
        $pId = $q['MaDoanDoc'] ?? 0;
        $p7Groups[$pId][] = $q;
    }
    ?>

    <?php if (empty($p7Groups)): ?>
        <div class="alert alert-info">Không có câu hỏi nào thuộc Part 7.</div>
    <?php else: ?>
        <?php foreach ($p7Groups as $pId => $groupQuestions): ?>
            <?php $passage = $passageMap[$pId] ?? null; ?>
            <div class="card" style="margin-bottom: 24px;">
                <div class="split-container">
                    <!-- Cột Bài Đọc -->
                    <div class="passage-panel">
                        <div class="passage-header">
                            <div>
                                <span class="badge badge-secondary"><?= htmlspecialchars($passage['LoaiDoan'] ?? 'Reading Passage') ?></span>
                                <b style="margin-left: 8px;">Bài Đọc #<?= $pId ?></b>
                            </div>
                        </div>
                        <?php if (!empty($passage['DuongDanHinh'])): ?>
                            <div style="margin-bottom: 14px;">
                                <img src="<?= htmlspecialchars($passage['DuongDanHinh']) ?>" alt="Minh họa" style="max-width: 100%; border-radius: var(--radius-md);">
                            </div>
                        <?php endif; ?>
                        <div class="passage-content"><?= htmlspecialchars($passage['NoiDung'] ?? '(Chưa có nội dung bài đọc)') ?></div>
                    </div>

                    <!-- Cột Câu Hỏi Đi Kèm -->
                    <div>
                        <?php foreach ($groupQuestions as $q): ?>
                            <div class="question-item">
                                <div class="question-title">
                                    <span class="badge badge-secondary">Câu <?= $q['SoThuTuCau'] ?></span>
                                    <div style="flex: 1;"><?= htmlspecialchars($q['NoiDungCauHoi']) ?></div>
                                </div>
                                <div class="choices-list">
                                    <?php foreach (['A', 'B', 'C', 'D'] as $k): ?>
                                        <?php 
                                            $c = $q['choices'][$k] ?? null; 
                                            $isCorrect = $c && $c['LaDapAnDung'] == 1;
                                        ?>
                                        <div class="choice-option <?= $isCorrect ? 'correct is-correct-answer' : '' ?>">
                                            <div class="choice-letter"><?= $k ?></div>
                                            <div style="flex: 1;"><?= htmlspecialchars($c['NoiDung'] ?? '---') ?></div>
                                            <?php if ($isCorrect): ?>
                                                <span class="badge badge-success answer-tag" style="font-size: 11px;">Đáp án đúng</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function switchTab(part, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const s5 = document.getElementById('section-part5');
    const s6 = document.getElementById('section-part6');
    const s7 = document.getElementById('section-part7');

    if (part === 'all') {
        s5.style.display = 'block';
        s6.style.display = 'block';
        s7.style.display = 'block';
    } else if (part === 'part5') {
        s5.style.display = 'block';
        s6.style.display = 'none';
        s7.style.display = 'none';
    } else if (part === 'part6') {
        s5.style.display = 'none';
        s6.style.display = 'block';
        s7.style.display = 'none';
    } else if (part === 'part7') {
        s5.style.display = 'none';
        s6.style.display = 'none';
        s7.style.display = 'block';
    }
}

function toggleAnswers(show) {
    document.querySelectorAll('.is-correct-answer').forEach(el => {
        if (show) {
            el.classList.add('correct');
            const tag = el.querySelector('.answer-tag');
            if (tag) tag.style.display = 'inline-flex';
        } else {
            el.classList.remove('correct');
            const tag = el.querySelector('.answer-tag');
            if (tag) tag.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
