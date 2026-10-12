<?php
/**
 * Chức Năng Import Đề Thi Từ File Word (.docx)
 * Kiểm tra định dạng chuẩn theo yêu cầu: Hủy bỏ nếu có lỗi, chỉ lưu khi 100% chuẩn.
 */
$pageTitle = 'Import Đề Thi Từ File Word';
$activePage = 'import';
require_once __DIR__ . '/views/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start;">
    <!-- Main Import Form -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Tải Lên File Word Đề Thi (.docx)
                </div>

            </div>

            <!-- Upload Drop Zone -->
            <div class="upload-dropzone" id="drop-zone" onclick="document.getElementById('docx-file').click()">
                <input type="file" id="docx-file" accept=".docx" style="display: none;" onchange="handleFileSelected(this)">
                <div class="upload-icon">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                </div>
                <div class="upload-title" id="drop-title">Kéo thả file .docx vào đây hoặc click để chọn file</div>
                <div class="upload-hint" id="drop-hint">Hỗ trợ định dạng Microsoft Word (.docx) chứa bộ thẻ tags TOEIC</div>
            </div>

            <!-- Exam Options Form -->
            <div style="margin-top: 24px; display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label" for="exam-title">Tên Đề Thi <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="exam-title" class="form-control" placeholder="VD: TOEIC Reading Practice Test 03" value="TOEIC Reading Test 03">
                </div>

                <div class="form-group">
                    <label class="form-label" for="exam-duration">Thời Lượng (Phút) <span style="color: var(--danger);">*</span></label>
                    <input type="number" id="exam-duration" class="form-control" value="75" min="5" max="180">
                </div>
            </div>

            <div class="form-group" style="background: #f8fafc; padding: 14px 18px; border-radius: var(--radius-md); border: 1px solid var(--border);">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px; font-weight: 600;">
                    <input type="checkbox" id="strict-validation" checked style="width: 18px; height: 18px; accent-color: var(--primary);">
                    Chế độ kiểm tra nghiêm ngặt (Strict Validation 100%)
                </label>
                <div style="font-size: 12px; color: var(--text-muted); margin-left: 28px; margin-top: 4px;">
                    Theo quy định: Nếu phát hiện bất kỳ sai sót định dạng (thiếu thẻ, thiếu đáp án đúng, sai số câu), hệ thống sẽ từ chối lưu vào CSDL và liệt kê lỗi chi tiết.
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn btn-outline" id="btn-validate" onclick="validateDocx()" style="flex: 1;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Kiểm Tra Định Dạng (Validate)
                </button>

                <button type="button" class="btn btn-primary" id="btn-import" onclick="executeImport()" style="flex: 1.5;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Tiến Hành Import Vào CSDL
                </button>
            </div>
        </div>

        <!-- Result / Diagnostic Panel -->
        <div id="diagnostic-panel" style="display: none;">
            <div class="card" id="diagnostic-card">
                <div class="card-header">
                    <div class="card-title" id="diagnostic-title">
                        Kết Quả Phân Tích & Kiểm Tra Định Dạng
                    </div>
                    <span id="diagnostic-badge" class="badge"></span>
                </div>

                <div id="diagnostic-summary" style="margin-bottom: 20px;"></div>

                <!-- Metrics breakdown -->
                <div class="stat-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 20px;" id="metrics-grid">
                    <div class="stat-card" style="padding: 14px;">
                        <div class="stat-info">
                            <div class="stat-value" id="val-total-q" style="font-size: 20px;">0</div>
                            <div class="stat-label">Tổng Câu Hỏi</div>
                        </div>
                    </div>
                    <div class="stat-card" style="padding: 14px;">
                        <div class="stat-info">
                            <div class="stat-value" id="val-part5" style="font-size: 20px;">0</div>
                            <div class="stat-label">Part 5 (Đơn)</div>
                        </div>
                    </div>
                    <div class="stat-card" style="padding: 14px;">
                        <div class="stat-info">
                            <div class="stat-value" id="val-part6-7" style="font-size: 20px;">0</div>
                            <div class="stat-label">Part 6 & 7</div>
                        </div>
                    </div>
                    <div class="stat-card" style="padding: 14px;">
                        <div class="stat-info">
                            <div class="stat-value" id="val-passages" style="font-size: 20px;">0</div>
                            <div class="stat-label">Bài Đọc</div>
                        </div>
                    </div>
                </div>

                <!-- Error Table -->
                <div id="error-table-container">
                    <h4 style="font-size: 15px; margin-bottom: 12px; color: var(--danger); display: flex; align-items: center; gap: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        Danh Sách Lỗi Cần Xử Lý (<span id="error-count-num">0</span> lỗi)
                    </h4>
                    <div class="table-responsive">
                        <table class="table" id="error-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Câu</th>
                                    <th style="width: 140px;">Mã Lỗi</th>
                                    <th>Mô Tả Lỗi Chi Tiết</th>
                                    <th>Gợi Ý Cách Khắc Phục</th>
                                </tr>
                            </thead>
                            <tbody id="error-table-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Side Guide & Cheatsheet -->
    <div>
        <div class="card">
            <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 14px; color: var(--text-primary);">
                📖 Hướng Dẫn Tải Lên
            </h3>
            <div style="font-size: 13px; color: var(--text-secondary); line-height: 1.6;">
                <p style="margin-bottom: 10px;">
                    <b>Bước 1:</b> Chọn hoặc kéo thả file Word <code>.docx</code> vào ô tải lên.
                </p>
                <p style="margin-bottom: 10px;">
                    <b>Bước 2:</b> Nhấn <b>Kiểm Tra Định Dạng</b> để hệ thống quét và phát hiện lỗi trước khi lưu.
                </p>
                <p style="margin-bottom: 10px;">
                    <b>Bước 3:</b> Nếu không có lỗi (hoặc đã rà soát), nhấn <b>Tiến Hành Import</b> để lưu vào CSDL.
                </p>
            </div>

            <hr style="border: none; border-top: 1px solid var(--border); margin: 18px 0;">

            <div style="background: var(--primary-light); padding: 14px; border-radius: var(--radius-md); font-size: 13px; color: var(--primary);">
                💡 <b>Lưu ý định dạng:</b> File Word (.docx) cần theo đúng cú pháp các phần Part 5, Part 6 và Part 7 kèm các nhãn câu hỏi và lựa chọn (A), (B), (C), (D) để hệ thống nhận diện chính xác.
            </div>
        </div>
    </div>
</div>

<script>
let selectedFile = null;

// Drag and drop handlers
const dropZone = document.getElementById('drop-zone');
['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    }, false);
});

dropZone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
});

function handleFileSelected(input) {
    if (input.files.length > 0) {
        handleFile(input.files[0]);
    }
}

function handleFile(file) {
    if (!file.name.endsWith('.docx')) {
        showToast('Vui lòng chỉ tải lên file định dạng Word (.docx)!', 'danger');
        return;
    }
    selectedFile = file;
    document.getElementById('drop-title').innerHTML = `📄 <b>${file.name}</b>`;
    document.getElementById('drop-hint').innerText = `Dung lượng: ${(file.size / 1024).toFixed(1)} KB. Đã sẵn sàng kiểm tra.`;
    
    // Tự động gợi ý tên đề thi
    let baseName = file.name.replace(/\.docx$/i, '').replace(/_/g, ' ');
    document.getElementById('exam-title').value = baseName;
    showToast('Đã chọn file: ' + file.name, 'success');
}

function validateDocx() {
    if (!selectedFile) {
        showToast('Vui lòng chọn file .docx trước khi kiểm tra!', 'warning');
        return;
    }

    const btn = document.getElementById('btn-validate');
    btn.disabled = true;
    btn.innerText = 'Đang phân tích...';

    const formData = new FormData();
    formData.append('docx_file', selectedFile);
    formData.append('strict_answers', document.getElementById('strict-validation').checked ? '1' : '0');

    fetch('ajax/validate_docx.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> Kiểm Tra Định Dạng (Validate)';
        
        displayDiagnosticResult(data);
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = 'Kiểm Tra Định Dạng (Validate)';
        showToast('Lỗi khi gửi yêu cầu phân tích file', 'danger');
    });
}

function displayDiagnosticResult(res) {
    const panel = document.getElementById('diagnostic-panel');
    panel.style.display = 'block';

    const report = res.report || {};
    const isValid = report.is_valid;
    const badge = document.getElementById('diagnostic-badge');
    const summary = document.getElementById('diagnostic-summary');

    if (isValid) {
        badge.className = 'badge badge-success';
        badge.innerText = '✓ 100% HỢP LỆ';
        summary.innerHTML = `
            <div class="alert alert-success">
                <b>Tuyệt vời!</b> ${report.summary} Đề thi đã sẵn sàng để lưu vào cơ sở dữ liệu.
            </div>
        `;
        document.getElementById('error-table-container').style.display = 'none';
    } else {
        badge.className = 'badge badge-danger';
        badge.innerText = `✕ CÓ ${report.error_count} LỖI`;
        summary.innerHTML = `
            <div class="alert alert-danger">
                <b>Chú ý:</b> ${report.summary} Toàn bộ quá trình import sẽ bị tạm dừng để Giảng viên rà soát và chỉnh sửa file Word.
            </div>
        `;

        // Render errors
        const tbody = document.getElementById('error-table-body');
        tbody.innerHTML = '';
        document.getElementById('error-count-num').innerText = report.error_count;
        document.getElementById('error-table-container').style.display = 'block';

        (report.errors || []).forEach(err => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${err.question_number || '-'}</strong></td>
                <td><span class="badge ${err.severity === 'danger' ? 'badge-danger' : 'badge-warning'}">${err.type}</span></td>
                <td>${err.message}</td>
                <td style="color: var(--text-secondary); font-size: 13px;">${err.suggestion || '-'}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Fill counts
    const counts = res.part_counts || {};
    document.getElementById('val-total-q').innerText = counts.total || report.total_questions || 0;
    document.getElementById('val-part5').innerText = counts.part5 || 0;
    document.getElementById('val-part6-7').innerText = (counts.part6 || 0) + (counts.part7 || 0);
    document.getElementById('val-passages').innerText = report.total_passages || 0;

    // Scroll to results
    panel.scrollIntoView({ behavior: 'smooth' });
}

function executeImport() {
    if (!selectedFile) {
        showToast('Vui lòng chọn file .docx trước khi import!', 'warning');
        return;
    }

    const title = document.getElementById('exam-title').value.trim();
    if (!title) {
        showToast('Vui lòng nhập tên đề thi!', 'warning');
        return;
    }

    const duration = parseInt(document.getElementById('exam-duration').value) || 75;
    const isStrict = document.getElementById('strict-validation').checked ? '1' : '0';

    const btn = document.getElementById('btn-import');
    btn.disabled = true;
    btn.innerText = 'Đang tiến hành import...';

    const formData = new FormData();
    formData.append('docx_file', selectedFile);
    formData.append('exam_title', title);
    formData.append('exam_duration', duration);
    formData.append('strict_validation', isStrict);

    fetch('ajax/import_docx.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = 'Tiến Hành Import Vào CSDL';

        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.href = 'preview.php?id=' + data.exam_id;
            }, 1200);
        } else {
            showToast(data.message || 'Import thất bại!', 'danger');
            if (data.report) {
                displayDiagnosticResult({ report: data.report, part_counts: data.part_counts });
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = 'Tiến Hành Import Vào CSDL';
        showToast('Lỗi kết nối khi import dữ liệu', 'danger');
    });
}
</script>

<?php require_once __DIR__ . '/views/footer.php'; ?>
