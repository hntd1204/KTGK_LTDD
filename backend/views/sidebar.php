<?php
/**
 * Sidebar Navigation Component
 */
$activePage = $activePage ?? 'dashboard';
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <div class="brand-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            </svg>
        </div>
        <div class="brand-text">
            <h1>TOEIC Admin</h1>
            <span>Reading Test System</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">QUẢN LÝ CHÍNH</div>
        <a href="index.php" class="nav-link <?= ($activePage === 'dashboard') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            </span>
            <span>Tổng Quan (Dashboard)</span>
        </a>

        <a href="exams.php" class="nav-link <?= ($activePage === 'exams') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </span>
            <span>Danh Sách Đề Thi</span>
        </a>

        <div class="nav-section-title">CHỨC NĂNG ĐỀ THI</div>
        <a href="import.php" class="nav-link <?= ($activePage === 'import') ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            </span>
            <span>Import File Word (.docx)</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">
                <?= strtoupper(substr($currentUser['username'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($currentUser['full_name'] ?? 'Admin') ?></div>
                <div class="user-role"><?= htmlspecialchars($currentUser['role'] ?? 'Quản trị viên') ?></div>
            </div>
            <a href="logout.php" title="Đăng xuất" style="color: #94a3b8;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </a>
        </div>
    </div>
</aside>
