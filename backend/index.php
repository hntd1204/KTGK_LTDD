<?php
/**
 * Admin Dashboard - Tổng quan hệ thống
 */
$pageTitle = 'Tổng Quan Hệ Thống';
$activePage = 'dashboard';
require_once __DIR__ . '/views/header.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getConnection();

// Lấy thống kê
$totalExams = (int)$db->query("SELECT COUNT(*) FROM DeThi")->fetchColumn();
$totalQuestions = (int)$db->query("SELECT COUNT(*) FROM CauHoi")->fetchColumn();
$totalPassages = (int)$db->query("SELECT COUNT(*) FROM DoanDoc")->fetchColumn();
$totalAttempts = (int)$db->query("SELECT COUNT(*) FROM LuotThi")->fetchColumn();

// Lấy danh sách đề thi gần đây
$recentExams = $db->query("
    SELECT d.*, 
           (SELECT COUNT(*) FROM CauHoi c WHERE c.MaDeThi = d.MaDeThi) as SoCauThucTe,
           (SELECT COUNT(*) FROM DoanDoc dd WHERE dd.MaDeThi = d.MaDeThi) as SoDoanDoc
    FROM DeThi d 
    ORDER BY d.MaDeThi DESC 
    LIMIT 6
")->fetchAll();
?>

<!-- Statistics Metric Cards -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalExams) ?></div>
            <div class="stat-label">Tổng Số Đề Thi</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon secondary">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalQuestions) ?></div>
            <div class="stat-label">Tổng Câu Hỏi CSDL</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalPassages) ?></div>
            <div class="stat-label">Bài Đọc (Part 6 & 7)</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon success">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <div class="stat-info">
            <div class="stat-value"><?= number_format($totalAttempts) ?></div>
            <div class="stat-label">Lượt Thi Thí Sinh</div>
        </div>
    </div>
</div>

<!-- Quick Actions & Recent Exams -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            Danh Sách Đề Thi Trong Hệ Thống
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="import.php" class="btn btn-primary btn-sm">
                + Import Đề Thi Word
            </a>
        </div>
    </div>

    <?php if (empty($recentExams)): ?>
        <div style="text-align: center; padding: 48px 16px;">
            <div style="font-size: 40px; margin-bottom: 12px;">📁</div>
            <h3 style="font-size: 16px; margin-bottom: 8px;">Chưa có đề thi nào trong cơ sở dữ liệu</h3>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">
                Bạn có thể import file Word (.docx) chứa cấu trúc đề thi để đưa vào hệ thống.
            </p>
            <a href="import.php" class="btn btn-primary">Import File Word Ngay</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Mã Đề</th>
                        <th>Tên Đề Thi</th>
                        <th style="width: 120px;">Thời Lượng</th>
                        <th style="width: 110px;">Số Câu</th>
                        <th style="width: 110px;">Bài Đọc</th>
                        <th style="width: 130px;">Trạng Thái</th>
                        <th style="width: 160px;">Ngày Tạo</th>
                        <th style="width: 200px; text-align: right;">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentExams as $exam): ?>
                        <tr>
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
                                <?php if ($exam['TrangThai'] == 1): ?>
                                    <span class="badge badge-success">● Đang mở</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">● Tạm ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 13px;">
                                <?= date('d/m/Y H:i', strtotime($exam['NgayTao'])) ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="preview.php?id=<?= $exam['MaDeThi'] ?>" class="btn btn-outline btn-sm" title="Xem trước & Rà soát">
                                    Xem trước
                                </a>
                                <a href="edit.php?id=<?= $exam['MaDeThi'] ?>" class="btn btn-primary btn-sm" title="Chỉnh sửa nội dung">
                                    Chỉnh sửa
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Specification Tags Cheatsheet for Teachers -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
            Quy Chuẩn Định Dạng Thẻ Tags File Word (.docx)
        </div>
        <span class="badge badge-secondary">Bắt buộc theo Đề cương</span>
    </div>

    <div style="font-size: 14px; color: var(--text-secondary); line-height: 1.7;">
        <p style="margin-bottom: 12px;">
            Theo quy định, file Word (.docx) tải lên hệ thống bắt buộc phải sử dụng các thẻ định dạng sau để hệ thống tự động bóc tách và phân cấp:
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-top: 16px;">
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px;">
                <h4 style="color: var(--primary); font-size: 14px; margin-bottom: 8px;">🔹 Thẻ Phân Đoạn & Nhóm</h4>
                <code style="display: block; background: #ffffff; padding: 10px; border-radius: 6px; font-size: 13px; line-height: 1.6; border: 1px solid #e2e8f0;">
                    &lt;TEST&gt; ... &lt;/TEST&gt;<br>
                    &lt;PART id="5"&gt; (Câu 101-140)<br>
                    &lt;PART id="6"&gt; (Câu 141-152)<br>
                    &lt;PART id="7"&gt; (Câu 153-200)<br>
                    &lt;PASSAGE&gt; ... &lt;/PASSAGE&gt; (Bài đọc)<br>
                    [PASSAGE_GROUP_END id="131-134"]
                </code>
            </div>

            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-md); padding: 16px;">
                <h4 style="color: var(--success); font-size: 14px; margin-bottom: 8px;">🔹 Thẻ Câu Hỏi & Đáp Án</h4>
                <code style="display: block; background: #ffffff; padding: 10px; border-radius: 6px; font-size: 13px; line-height: 1.6; border: 1px solid #e2e8f0;">
                    &lt;QUESTION&gt;<br>
                    &nbsp;&nbsp;&lt;Q&gt;101. Câu hỏi...&lt;/Q&gt;<br>
                    &nbsp;&nbsp;&lt;CHOICES&gt;<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;&lt;C&gt;A. Lựa chọn A&lt;/C&gt;<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;&lt;C&gt;B. Lựa chọn B&lt;/C&gt;<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;&lt;C&gt;C. Lựa chọn C&lt;/C&gt;<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;&lt;C&gt;D. Lựa chọn D&lt;/C&gt;<br>
                    &nbsp;&nbsp;&lt;/CHOICES&gt;<br>
                    &nbsp;&nbsp;&lt;ANSWER&gt;A&lt;/ANSWER&gt;<br>
                    &lt;/QUESTION&gt;
                </code>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/views/footer.php'; ?>
