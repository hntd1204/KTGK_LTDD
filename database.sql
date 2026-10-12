-- CSDL THI TOEIC READING - MYSQL / MARIADB (phpMyAdmin, XAMPP)
-- Tên bảng và cột tiếng Việt không dấu, dễ dùng trong code PHP.
-- Chọn database dự án trước khi chạy. Script không xóa dữ liệu hiện có.

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS DeThi (
    MaDeThi INT NOT NULL AUTO_INCREMENT,
    TenDeThi VARCHAR(200) NOT NULL,
    ThoiLuongPhut INT NOT NULL DEFAULT 75,
    TongSoCau INT NOT NULL DEFAULT 100,
    TrangThai TINYINT(1) NOT NULL DEFAULT 1,
    NgayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (MaDeThi),
    CONSTRAINT CK_DeThi_ThoiLuong CHECK (ThoiLuongPhut > 0),
    CONSTRAINT CK_DeThi_TongSoCau CHECK (TongSoCau > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS DoanDoc (
    MaDoanDoc INT NOT NULL AUTO_INCREMENT,
    MaDeThi INT NOT NULL,
    PhanThi TINYINT NOT NULL,
    LoaiDoan VARCHAR(30) NULL,
    NoiDung LONGTEXT NOT NULL,
    DuongDanHinh VARCHAR(500) NULL,
    PRIMARY KEY (MaDoanDoc),
    KEY IX_DoanDoc_MaDeThi (MaDeThi),
    CONSTRAINT FK_DoanDoc_DeThi FOREIGN KEY (MaDeThi)
        REFERENCES DeThi(MaDeThi) ON DELETE CASCADE,
    CONSTRAINT CK_DoanDoc_PhanThi CHECK (PhanThi IN (6, 7))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS CauHoi (
    MaCauHoi INT NOT NULL AUTO_INCREMENT,
    MaDeThi INT NOT NULL,
    MaDoanDoc INT NULL,
    SoThuTuCau INT NOT NULL,
    PhanThi TINYINT NOT NULL,
    NoiDungCauHoi LONGTEXT NOT NULL,
    PRIMARY KEY (MaCauHoi),
    UNIQUE KEY UQ_CauHoi_DeThi_SoThuTu (MaDeThi, SoThuTuCau),
    KEY IX_CauHoi_MaDoanDoc (MaDoanDoc),
    KEY IX_CauHoi_DeThi_PhanThi (MaDeThi, PhanThi),
    CONSTRAINT FK_CauHoi_DeThi FOREIGN KEY (MaDeThi)
        REFERENCES DeThi(MaDeThi) ON DELETE CASCADE,
    CONSTRAINT FK_CauHoi_DoanDoc FOREIGN KEY (MaDoanDoc)
        REFERENCES DoanDoc(MaDoanDoc) ON DELETE SET NULL,
    CONSTRAINT CK_CauHoi_PhanThi CHECK (PhanThi IN (5, 6, 7))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS LuaChonDapAn (
    MaLuaChon INT NOT NULL AUTO_INCREMENT,
    MaCauHoi INT NOT NULL,
    KyHieu CHAR(1) NOT NULL,
    NoiDungLuaChon LONGTEXT NOT NULL,
    LaDapAnDung TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (MaLuaChon),
    UNIQUE KEY UQ_LuaChon_CauHoi_KyHieu (MaCauHoi, KyHieu),
    CONSTRAINT FK_LuaChonDapAn_CauHoi FOREIGN KEY (MaCauHoi)
        REFERENCES CauHoi(MaCauHoi) ON DELETE CASCADE,
    CONSTRAINT CK_LuaChonDapAn_KyHieu CHECK (KyHieu IN ('A', 'B', 'C', 'D'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS LuotThi (
    MaLuotThi INT NOT NULL AUTO_INCREMENT,
    MaDeThi INT NOT NULL,
    MaNguoiDung VARCHAR(100) NOT NULL,
    ThoiGianBatDau DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ThoiGianNop DATETIME NULL,
    TrangThai ENUM('DangThi', 'DaNop', 'HetGio') NOT NULL DEFAULT 'DangThi',
    PRIMARY KEY (MaLuotThi),
    KEY IX_LuotThi_NguoiDung_DeThi (MaNguoiDung, MaDeThi),
    CONSTRAINT FK_LuotThi_DeThi FOREIGN KEY (MaDeThi)
        REFERENCES DeThi(MaDeThi) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS CauTraLoi (
    MaCauTraLoi INT NOT NULL AUTO_INCREMENT,
    MaLuotThi INT NOT NULL,
    MaCauHoi INT NOT NULL,
    MaLuaChon INT NULL,
    ThoiGianTraLoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (MaCauTraLoi),
    UNIQUE KEY UQ_CauTraLoi_LuotThi_CauHoi (MaLuotThi, MaCauHoi),
    KEY IX_CauTraLoi_MaCauHoi (MaCauHoi),
    KEY IX_CauTraLoi_MaLuaChon (MaLuaChon),
    CONSTRAINT FK_CauTraLoi_LuotThi FOREIGN KEY (MaLuotThi)
        REFERENCES LuotThi(MaLuotThi) ON DELETE CASCADE,
    CONSTRAINT FK_CauTraLoi_CauHoi FOREIGN KEY (MaCauHoi)
        REFERENCES CauHoi(MaCauHoi) ON DELETE CASCADE,
    CONSTRAINT FK_CauTraLoi_LuaChon FOREIGN KEY (MaLuaChon)
        REFERENCES LuaChonDapAn(MaLuaChon) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS KetQuaThi (
    MaKetQua INT NOT NULL AUTO_INCREMENT,
    MaLuotThi INT NOT NULL,
    SoCauDung INT NOT NULL DEFAULT 0,
    SoCauSai INT NOT NULL DEFAULT 0,
    SoCauBoQua INT NOT NULL DEFAULT 0,
    DiemSo DECIMAL(6,2) NOT NULL DEFAULT 0,
    ThoiGianCham DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (MaKetQua),
    UNIQUE KEY UQ_KetQuaThi_MaLuotThi (MaLuotThi),
    CONSTRAINT FK_KetQuaThi_LuotThi FOREIGN KEY (MaLuotThi)
        REFERENCES LuotThi(MaLuotThi) ON DELETE CASCADE,
    CONSTRAINT CK_KetQuaThi_SoCau CHECK (
        SoCauDung >= 0 AND SoCauSai >= 0 AND SoCauBoQua >= 0
    ),
    CONSTRAINT CK_KetQuaThi_Diem CHECK (DiemSo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Kiểm tra các bảng đã tạo
SHOW TABLES;
