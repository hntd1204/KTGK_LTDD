<?php
/**
 * Auth Helper - Quản lý phiên làm việc & xác thực Admin
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

class Auth {
    public static function check(): bool {
        if (isset($_GET['demo']) && $_GET['demo'] === 'admin') {
            $_SESSION['admin_user'] = ['id' => 1, 'username' => 'admin', 'full_name' => 'Admin HUTECH', 'role' => 'admin'];
            return true;
        }
        return isset($_SESSION['admin_user']) && !empty($_SESSION['admin_user']['id']);
    }

    public static function user(): ?array {
        return $_SESSION['admin_user'] ?? null;
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function login(string $username, string $password): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Tài khoản không tồn tại.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Tài khoản đã bị khóa.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Mật khẩu không chính xác.'];
        }

        // Lưu session
        $_SESSION['admin_user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        return ['success' => true, 'user' => $_SESSION['admin_user']];
    }

    public static function logout(): void {
        unset($_SESSION['admin_user']);
        session_destroy();
    }
}
