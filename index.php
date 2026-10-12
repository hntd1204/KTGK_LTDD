<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Thi TOEIC Reading - KTGK Lập Trình Di Động</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --secondary: #0ea5e9;
            --dark: #0f172a;
            --gray-100: #f8fafc;
            --gray-200: #e2e8f0;
            --gray-600: #475569;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .container {
            max-width: 1000px;
            width: 100%;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .badge {
            display: inline-block;
            background: rgba(79, 70, 229, 0.3);
            border: 1px solid rgba(129, 140, 248, 0.4);
            color: #c7d2fe;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .sub-title {
            color: #94a3b8;
            font-size: 15px;
            max-width: 650px;
            margin: 0 auto;
            line-height: 1.5;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        .card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card:hover {
            transform: translateY(-6px);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 20px 30px -10px rgba(79, 70, 229, 0.3);
        }
        .card-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .icon-admin {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
        }
        .icon-mobile {
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            color: #fff;
        }
        .card h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #f8fafc;
        }
        .card p {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .card-features {
            list-style: none;
            margin-bottom: 24px;
        }
        .card-features li {
            color: #cbd5e1;
            font-size: 13px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-features li i {
            color: #10b981;
            font-size: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
        }
        .btn-primary:hover {
            opacity: 0.95;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: #fff;
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.4);
        }
        .btn-secondary:hover {
            opacity: 0.95;
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.6);
        }
        .footer-info {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="badge"><i class="fa-solid fa-graduation-cap"></i> BÁO CÁO GIỮA KỲ • LẬP TRÌNH DI ĐỘNG</div>
            <h1>Hệ Thống Phân Tích & Thi TOEIC Reading</h1>
            <p class="sub-title">Cơ sở dữ liệu MySQL (XAMPP macOS) • Backend Web Quản trị Admin • Frontend Ứng dụng Di động Flutter & Web</p>
        </div>

        <div class="grid">
            <!-- Card 1: Backend Admin -->
            <div class="card">
                <div>
                    <div class="card-icon icon-admin"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <h2>1. Giảng Viên (Admin Web)</h2>
                    <p>Cổng thông tin dành cho giáo viên và quản trị viên quản lý ngân hàng đề thi TOEIC.</p>
                    <ul class="card-features">
                        <li><i class="fa-solid fa-check"></i> Upload và import đề thi từ file Word (.docx)</li>
                        <li><i class="fa-solid fa-check"></i> Xác thực chuẩn thẻ (Tags) & hủy bỏ nếu sai lỗi</li>
                        <li><i class="fa-solid fa-check"></i> Xem trước (Preview) chia đôi bài đọc Part 6 & 7</li>
                        <li><i class="fa-solid fa-check"></i> Chỉnh sửa câu hỏi, đáp án, thêm/xóa tức thì</li>
                    </ul>
                </div>
                <a href="backend/index.php" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Mở Trang Quản Trị Admin
                </a>
            </div>

            <!-- Card 2: User Mobile Frontend -->
            <div class="card">
                <div>
                    <div class="card-icon icon-mobile"><i class="fa-solid fa-mobile-screen-button"></i></div>
                    <h2>2. Thí Sinh (User Mobile App)</h2>
                    <p>Ứng dụng di động Flutter (và bản Web trực tiếp) dành cho sinh viên chọn đề và thi trực tuyến.</p>
                    <ul class="card-features">
                        <li><i class="fa-solid fa-check"></i> Danh sách đề thi chuẩn 100 câu Part 5, 6, 7</li>
                        <li><i class="fa-solid fa-check"></i> Đếm ngược thời gian thực 75 phút & cảnh báo 5 phút</li>
                        <li><i class="fa-solid fa-check"></i> Tự động thu bài, khóa chọn khi hết thời gian</li>
                        <li><i class="fa-solid fa-check"></i> Lưu tạm đáp án, bảng 100 câu, chấm điểm tự động</li>
                    </ul>
                </div>
                <a href="frontend/index.html" class="btn btn-secondary">
                    <i class="fa-solid fa-play"></i> Trải Nghiệm Ứng Dụng Thi (Web)
                </a>
            </div>
        </div>

        <div class="footer-info">
            <p><strong>Thực hiện:</strong> Huỳnh Nguyễn Thành Đạt • <strong>MSSV:</strong> 2280600579 • <strong>Lớp:</strong> CMP177 – 23DTHC7</p>
            <p style="margin-top: 4px; font-size: 12px;">Đã hoàn thành 8/8 tiêu chí theo Đề thi giữa kỳ môn Lập Trình Di Động (MySQL XAMPP on macOS).</p>
        </div>
    </div>
</body>
</html>
