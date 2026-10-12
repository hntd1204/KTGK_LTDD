<?php
/**
 * Admin Login Page
 */
require_once __DIR__ . '/helpers/auth.php';

$error = '';
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $res = Auth::login($username, $password);
        if ($res['success']) {
            header('Location: index.php');
            exit;
        } else {
            $error = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Quản Trị | TOEIC Reading Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 15% 15%, rgba(79, 70, 229, 0.12), transparent 40%),
                        radial-gradient(circle at 85% 85%, rgba(14, 165, 233, 0.12), transparent 40%),
                        #0f172a;
            color: #f8fafc;
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: rgba(30, 41, 59, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-xl);
            padding: 40px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.4s ease-out;
        }
        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, #4f46e5, #0ea5e9);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.4);
            margin-bottom: 16px;
        }
        .login-title {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 6px;
        }
        .login-subtitle {
            font-size: 13px;
            color: #94a3b8;
        }
        .login-card .form-control {
            background-color: rgba(15, 23, 42, 0.6);
            border-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            padding: 12px 16px;
        }
        .login-card .form-control:focus {
            border-color: #6366f1;
            background-color: rgba(15, 23, 42, 0.85);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }
        .login-card .form-label {
            color: #cbd5e1;
        }
        .demo-chip {
            background: rgba(255, 255, 255, 0.05);
            border: 1px dashed rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-md);
            padding: 12px;
            font-size: 12px;
            color: #94a3b8;
            margin-top: 24px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        .demo-chip:hover {
            background: rgba(79, 70, 229, 0.15);
            border-color: #6366f1;
            color: #e0e7ff;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <h1 class="login-title">TOEIC Reading Admin</h1>
            <p class="login-subtitle">Hệ thống Quản Trị & Import Đề Thi TOEIC</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="background-color: rgba(239, 68, 68, 0.2); border-color: #ef4444; color: #fca5a5;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label class="form-label" for="username">Tên đăng nhập / Email</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Nhập admin" required autofocus value="admin">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required value="admin123">
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 10px;">
                Đăng Nhập Quản Trị
            </button>
        </form>

        <div class="demo-chip" onclick="fillDemo()" title="Click để tự động điền tài khoản">
            💡 Tài khoản mặc định: <b>admin</b> | Mật khẩu: <b>admin123</b>
            <div style="font-size: 11px; margin-top: 3px; color: #64748b;">(Click vào đây để điền nhanh)</div>
        </div>
    </div>

    <script>
        function fillDemo() {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'admin123';
        }
    </script>
</body>
</html>
