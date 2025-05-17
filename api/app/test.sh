#!/bin/bash
# Script để chạy một file test cụ thể
# Sử dụng: ./test.sh <tên file test>
# Ví dụ: ./test.sh AppointmentRecordsModelTest

# Kiểm tra tham số dòng lệnh
if [ $# -lt 1 ]; then
    echo "Sử dụng: ./test.sh <tên file test>"
    echo "Ví dụ: ./test.sh AppointmentRecordsModelTest"
    exit 1
fi

# Lấy tên file test
TEST_NAME=$1

# Thêm đuôi .php nếu chưa có
if [[ $TEST_NAME != *".php" ]]; then
    TEST_NAME="${TEST_NAME}.php"
fi

# Tìm file test trong thư mục tests/models hoặc tests/controllers
MODELS_DIR="tests/models"
CONTROLLERS_DIR="tests/controllers"
TEST_FILE=""

# Kiểm tra trong thư mục models
if [ -f "${MODELS_DIR}/${TEST_NAME}" ]; then
    TEST_FILE="${MODELS_DIR}/${TEST_NAME}"
# Kiểm tra trong thư mục controllers
elif [ -f "${CONTROLLERS_DIR}/${TEST_NAME}" ]; then
    TEST_FILE="${CONTROLLERS_DIR}/${TEST_NAME}"
# Kiểm tra trong thư mục tests
elif [ -f "tests/${TEST_NAME}" ]; then
    TEST_FILE="tests/${TEST_NAME}"
else
    echo "File test không tồn tại: ${TEST_NAME}"
    echo "Các file test có sẵn trong models:"
    ls -1 $MODELS_DIR
    echo "Các file test có sẵn trong controllers:"
    ls -1 $CONTROLLERS_DIR
    echo "Các file test có sẵn trong thư mục tests:"
    ls -1 tests/*.php 2>/dev/null
    exit 1
fi

# Tạo lệnh PHPUnit
PHPUNIT_COMMAND="/opt/homebrew/opt/php@5.6/bin/php vendor/bin/phpunit $TEST_FILE"

# Hiển thị thông báo
echo "==================================================="
echo "🧪 Đang chạy test: $TEST_NAME"
echo "==================================================="

# Chạy PHPUnit
$PHPUNIT_COMMAND

# Kiểm tra kết quả
if [ $? -eq 0 ]; then
    echo "==================================================="
    echo "✅ Test đã chạy thành công!"
    echo "==================================================="
else
    echo "==================================================="
    echo "❌ Test thất bại!"
    echo "==================================================="
fi

# Hiển thị đường dẫn đến báo cáo độ phủ
COVERAGE_PATH="$(pwd)/tests/coverage/index.html"
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
