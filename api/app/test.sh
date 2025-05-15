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

# Đường dẫn đến thư mục tests/models
TESTS_DIR="tests/models"

# Đường dẫn đầy đủ đến file test
TEST_FILE="${TESTS_DIR}/${TEST_NAME}"

# Kiểm tra file test tồn tại
if [ ! -f "$TEST_FILE" ]; then
    echo "File test không tồn tại: $TEST_FILE"
    echo "Các file test có sẵn:"
    ls -1 $TESTS_DIR
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
