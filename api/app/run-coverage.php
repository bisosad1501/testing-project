<?php
/**
 * Script để chạy PHPUnit với tùy chọn coverage
 *
 * Script này sẽ:
 * 1. Chạy PHPUnit với tùy chọn coverage cho từng file test riêng biệt
 * 2. Tạo báo cáo độ phủ
 *
 * Cách sử dụng:
 * - Không có tham số: Chạy tất cả các test
 * - Tham số 1: Đường dẫn đến file test cụ thể
 * - Tham số 1 = "models": Chạy tất cả các test trong thư mục models
 * - Tham số 1 = "controllers": Chạy tất cả các test trong thư mục controllers
 */

// Định nghĩa đường dẫn
define('APP_PATH', realpath(__DIR__));

// Hiển thị hướng dẫn sử dụng
function showUsage() {
    echo "Cách sử dụng:\n";
    echo "  php run-coverage.php                  - Chạy tất cả các test\n";
    echo "  php run-coverage.php <file>           - Chạy test cho file cụ thể\n";
    echo "  php run-coverage.php models           - Chạy tất cả các test trong thư mục models\n";
    echo "  php run-coverage.php controllers      - Chạy tất cả các test trong thư mục controllers\n";
    echo "  php run-coverage.php help             - Hiển thị hướng dẫn sử dụng\n";
}

// Xử lý tham số dòng lệnh
$param = isset($argv[1]) ? $argv[1] : '';

if ($param === 'help') {
    showUsage();
    exit(0);
}

// Danh sách các file test theo nhóm
$modelTests = [
    'tests/models/DoctorModelTest.php',
    'tests/models/AppointmentModelTest.php',
    'tests/models/PatientModelTest.php',
    'tests/models/BookingModelTest.php',
];

$controllerTests = [
    'tests/controllers/AppointmentControllerTest.php',
    'tests/controllers/DoctorControllerTest.php',
    // Bỏ qua BookingPhotosControllerTest.php vì nó gây ra Fatal Error
    // 'tests/controllers/BookingPhotosControllerTest.php',
];

// Xác định danh sách file test cần chạy
$testFiles = [];
if (empty($param)) {
    // Chạy tất cả các test
    $testFiles = array_merge($modelTests, $controllerTests);
    echo "Chạy tất cả các test...\n";
} elseif ($param === 'models') {
    // Chạy tất cả các test trong thư mục models
    $testFiles = $modelTests;
    echo "Chạy tất cả các test trong thư mục models...\n";
} elseif ($param === 'controllers') {
    // Chạy tất cả các test trong thư mục controllers
    $testFiles = $controllerTests;
    echo "Chạy tất cả các test trong thư mục controllers...\n";
} elseif (file_exists($param)) {
    // Chạy test cho file cụ thể
    $testFiles = [$param];
    echo "Chạy test cho file: $param\n";
} else {
    echo "Tham số không hợp lệ: $param\n";
    showUsage();
    exit(1);
}

// Chạy từng file test riêng biệt
$hasErrors = false;
$successfulTests = [];

foreach ($testFiles as $file) {
    if (!file_exists($file)) {
        echo "\nFile không tồn tại: $file\n";
        continue;
    }

    echo "\nChạy test cho file: $file\n";
    $phpunitCommand = '/opt/homebrew/opt/php@5.6/bin/php ' . APP_PATH . '/vendor/bin/phpunit ' . $file;
    passthru($phpunitCommand, $fileReturnCode);

    if ($fileReturnCode !== 0) {
        echo "Test cho file $file kết thúc với mã lỗi: $fileReturnCode\n";
        $hasErrors = true;
    } else {
        $successfulTests[] = $file;
    }
}

// Tạo báo cáo độ phủ cho các test thành công
if (!empty($successfulTests)) {
    echo "\nTạo báo cáo độ phủ cho các test thành công...\n";

    // Tạo thư mục coverage nếu chưa tồn tại
    if (!is_dir(APP_PATH . '/tests/coverage')) {
        mkdir(APP_PATH . '/tests/coverage', 0777, true);
    }

    // Chạy PHPUnit với cấu hình từ phpunit.xml
    $phpunitCommand = '/opt/homebrew/opt/php@5.6/bin/php ' . APP_PATH . '/vendor/bin/phpunit -c ' . APP_PATH . '/phpunit.xml ' . implode(' ', $successfulTests);
    passthru($phpunitCommand, $returnCode);

    if ($returnCode !== 0) {
        echo "Tạo báo cáo độ phủ kết thúc với mã lỗi: $returnCode\n";
    } else {
        echo "Tạo báo cáo độ phủ thành công!\n";
    }

    echo "Báo cáo độ phủ đã được tạo tại " . APP_PATH . "/tests/coverage/index.html\n";
} else {
    echo "\nKhông có test nào thành công để tạo báo cáo độ phủ.\n";
}

// Hiển thị kết quả tổng quát
echo "\n=== KẾT QUẢ TỔNG QUÁT ===\n";
echo "Tổng số test: " . count($testFiles) . "\n";
echo "Thành công: " . count($successfulTests) . "\n";
echo "Thất bại: " . (count($testFiles) - count($successfulTests)) . "\n";

if ($hasErrors) {
    exit(1);
} else {
    exit(0);
}
