#!/bin/bash
# Script tự động build Flutter Web và triển khai lên XAMPP htdocs/KTGK_LTDD/frontend
set -e

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo "🚀 Đang chuẩn bị deploy Flutter Web..."

cd "$DIR/frontend"

if command -v flutter &> /dev/null; then
    echo "📦 Đang build Flutter Web với base-href='/KTGK_LTDD/frontend/'..."
    flutter build web --release --base-href /KTGK_LTDD/frontend/
fi

if [ -d "$DIR/frontend/build/web" ]; then
    echo "📋 Đang sao chép artifacts từ build/web sang frontend/..."
    cp -R "$DIR/frontend/build/web/"* "$DIR/frontend/"
    echo "✅ Triển khai hoàn tất! Truy cập: http://localhost/KTGK_LTDD/frontend/index.html"
else
    echo "❌ Không tìm thấy thư mục frontend/build/web. Vui lòng chạy 'flutter build web' trước!"
    exit 1
fi
