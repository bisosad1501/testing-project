<?php
/**
 * Script để chạy một file test cụ thể và tạo báo cáo độ phủ
 * 
 * Sử dụng: php run-single-test.php <đường dẫn đến file test> [tên phương thức test]
 * Ví dụ: 
 * - Chạy toàn bộ file test: php run-single-test.php tests/models/AppointmentRecordsModelTest.php
 * - Chạy một phương thức test cụ thể: php run-single-test.php tests/models/AppointmentRecordsModelTest.php testConstructor
 */

// Kiểm tra tham số dòng lệnh
if ($argc < 2) {
    echo "Sử dụng: php run-single-test.php <đường dẫn đến file test> [tên phương thức test]\n";
    exit(1);
}

// Lấy đường dẫn đến file test
$testFilePath = $argv[1];
if (!file_exists($testFilePath)) {
    echo "File test không tồn tại: {$testFilePath}\n";
    exit(1);
}

// Lấy tên phương thức test (nếu có)
$testMethod = isset($argv[2]) ? $argv[2] : null;

// Xóa thư mục coverage cũ
echo "Xóa thư mục coverage cũ...\n";
$coverageDir = __DIR__ . '/tests/coverage';
if (is_dir($coverageDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($coverageDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($coverageDir);
}

// Tạo file cấu hình PHPUnit tạm thời
$configFile = __DIR__ . '/phpunit-single-test.xml';
$testFileName = basename($testFilePath);
$testClassName = pathinfo($testFileName, PATHINFO_FILENAME);

// Xác định filter cho phương thức test cụ thể
$filterAttribute = '';
if ($testMethod) {
    $filterAttribute = ' filter="' . $testClassName . '::' . $testMethod . '"';
}

$configContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit backupGlobals="false"
         backupStaticAttributes="false"
         bootstrap="vendor/autoload.php"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="false"{$filterAttribute}>
    <testsuites>
        <testsuite name="Single Test">
            <file>./{$testFilePath}</file>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">./models</directory>
        </whitelist>
    </filter>
    <logging>
        <log type="coverage-html" target="./tests/coverage" lowUpperBound="35" highLowerBound="70"/>
        <log type="coverage-clover" target="./tests/coverage/coverage.xml"/>
    </logging>
</phpunit>
XML;

file_put_contents($configFile, $configContent);

// Chạy test với cấu hình tạm thời
echo "Chạy test và tạo báo cáo độ phủ...\n";
$command = "vendor/bin/phpunit -c {$configFile}";
passthru($command, $returnCode);

// Xóa file cấu hình tạm thời
unlink($configFile);

// Hiển thị thông báo kết quả
if ($returnCode === 0) {
    echo "Test đã chạy thành công.\n";
} else {
    echo "Test thất bại với mã lỗi: {$returnCode}\n";
}

echo "Báo cáo độ phủ đã được tạo tại {$coverageDir}/index.html\n";

// Mở báo cáo độ phủ trong trình duyệt (chỉ hoạt động trên macOS)
if (PHP_OS === 'Darwin') {
    echo "Đang mở báo cáo độ phủ trong trình duyệt...\n";
    passthru("open {$coverageDir}/index.html");
}
