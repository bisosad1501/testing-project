#!/bin/bash

# Script để chạy tất cả các test thành công và tạo báo cáo độ phủ
# Sử dụng: ./test-all.sh

# Đường dẫn đến phpunit
PHPUNIT="/opt/homebrew/opt/php@5.6/bin/php vendor/bin/phpunit"

# Đường dẫn đến thư mục coverage
COVERAGE_DIR="./tests/coverage"

# Kiểm tra xem phpunit có tồn tại không
if [ ! -f "vendor/bin/phpunit" ]; then
    echo "PHPUnit không tìm thấy tại vendor/bin/phpunit"
    exit 1
fi

# Tạo thư mục coverage nếu chưa tồn tại
if [ ! -d "$COVERAGE_DIR" ]; then
    mkdir -p "$COVERAGE_DIR"
else
    # Xóa nội dung cũ trong thư mục coverage
    rm -rf "$COVERAGE_DIR"/*
fi

# Tạo file phpunit.xml tạm thời
TEMP_PHPUNIT_XML="./phpunit.temp.xml"
cat > "$TEMP_PHPUNIT_XML" << EOF
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="false">
    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">./models</directory>
            <directory suffix=".php">./controllers</directory>
            <directory suffix=".php">./core</directory>
        </whitelist>
    </filter>
</phpunit>
EOF

echo "Đang chạy tất cả các test thành công và tạo báo cáo độ phủ..."

# Chạy test suite với báo cáo độ phủ
$PHPUNIT -c "$TEMP_PHPUNIT_XML" --coverage-html "$COVERAGE_DIR" tests/AllSuccessfulTests.php

if [ $? -eq 0 ]; then
    echo "==================================================="
    echo "✅ Tất cả các test đã chạy thành công!"
    echo "==================================================="
else
    echo "==================================================="
    echo "❌ Một số test thất bại, nhưng báo cáo độ phủ vẫn được tạo!"
    echo "==================================================="
fi

# Hiển thị đường dẫn đến báo cáo độ phủ
COVERAGE_PATH="$(pwd)/$COVERAGE_DIR/index.html"
echo ""
echo "📊 Báo cáo độ phủ đã được tạo tại:"
echo "file://$COVERAGE_PATH"
echo ""
echo "Bạn có thể click vào link trên để xem báo cáo độ phủ."

# Tự động mở báo cáo độ phủ trong trình duyệt mặc định
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    open "$COVERAGE_PATH"
elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
    # Linux với xdg-open
    xdg-open "$COVERAGE_PATH" &>/dev/null &
elif [[ "$OSTYPE" == "msys" || "$OSTYPE" == "cygwin" ]]; then
    # Windows
    start "$COVERAGE_PATH"
fi

echo "==================================================="

# Xóa file phpunit.xml tạm thời
rm "$TEMP_PHPUNIT_XML"
