<?php
/**
 * Script để chạy tất cả các test và lưu báo cáo độ phủ vào các thư mục riêng biệt
 */

// Định nghĩa đường dẫn
define('APP_PATH', realpath(__DIR__));

// Danh sách các file test
$testFiles = [
    'tests/models/DoctorModelTest.php',
    'tests/models/AppointmentModelTest.php',
    'tests/models/PatientModelTest.php',
    'tests/models/BookingModelTest.php',
    'tests/models/AppointmentRecordModelTest.php',
];

// Tạo thư mục coverage nếu chưa tồn tại
if (!is_dir(APP_PATH . '/tests/coverage_all')) {
    mkdir(APP_PATH . '/tests/coverage_all', 0777, true);
}

// Chạy từng test và lưu báo cáo độ phủ vào thư mục riêng
foreach ($testFiles as $file) {
    if (!file_exists($file)) {
        echo "\nFile không tồn tại: $file\n";
        continue;
    }

    // Lấy tên file không có đường dẫn và phần mở rộng
    $fileName = pathinfo($file, PATHINFO_FILENAME);
    
    // Tạo thư mục cho báo cáo độ phủ của file này
    $coverageDir = APP_PATH . '/tests/coverage_all/' . $fileName;
    if (!is_dir($coverageDir)) {
        mkdir($coverageDir, 0777, true);
    }

    echo "\n=== Chạy test cho file: $file ===\n";
    
    // Chạy PHPUnit với tùy chọn --coverage-html cho file này
    $phpunitCommand = '/opt/homebrew/opt/php@5.6/bin/php ' . APP_PATH . '/vendor/bin/phpunit --coverage-html ' . $coverageDir . ' ' . $file;
    passthru($phpunitCommand, $returnCode);

    if ($returnCode !== 0) {
        echo "Test cho file $file kết thúc với mã lỗi: $returnCode\n";
    } else {
        echo "Test cho file $file thành công!\n";
        echo "Báo cáo độ phủ đã được tạo tại $coverageDir/index.html\n";
    }
}

// Tạo trang index.html để liệt kê tất cả các báo cáo độ phủ
$indexContent = '<!DOCTYPE html>
<html>
<head>
    <title>Báo cáo độ phủ</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        ul { list-style-type: none; padding: 0; }
        li { margin: 10px 0; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Báo cáo độ phủ</h1>
    <ul>';

foreach ($testFiles as $file) {
    $fileName = pathinfo($file, PATHINFO_FILENAME);
    $indexContent .= '        <li><a href="' . $fileName . '/index.html">' . $fileName . '</a></li>' . "\n";
}

$indexContent .= '    </ul>
</body>
</html>';

file_put_contents(APP_PATH . '/tests/coverage_all/index.html', $indexContent);

echo "\n=== Tất cả các test đã chạy xong ===\n";
echo "Báo cáo độ phủ tổng hợp đã được tạo tại " . APP_PATH . "/tests/coverage_all/index.html\n";
