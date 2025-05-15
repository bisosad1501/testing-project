<?php
/**
 * Script để chạy tất cả các test và tạo một báo cáo độ phủ tổng hợp
 */

// Định nghĩa đường dẫn
define('APP_PATH', realpath(__DIR__));

// Xóa thư mục coverage cũ nếu tồn tại
if (is_dir(APP_PATH . '/tests/coverage')) {
    echo "Xóa thư mục coverage cũ...\n";
    system('rm -rf ' . APP_PATH . '/tests/coverage');
}

// Tạo thư mục coverage mới
if (!is_dir(APP_PATH . '/tests/coverage')) {
    mkdir(APP_PATH . '/tests/coverage', 0777, true);
}

// Tạo file phpunit.xml tạm thời với cấu hình đặc biệt
$tempPhpunitXml = APP_PATH . '/phpunit.temp.xml';
$phpunitXmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Models">
            <file>./tests/models/AppointmentModelTest.php</file>
            <file>./tests/models/AppointmentRecordModelTest.php</file>
            <file>./tests/models/AppointmentRecordsModelTest.php</file>
            <file>./tests/models/AppointmentsModelTest.php</file>
            <file>./tests/models/BookingModelTest.php</file>
            <file>./tests/models/BookingPhotoModelTest.php</file>
            <file>./tests/models/BookingPhotosModelTest.php</file>
            <file>./tests/models/BookingsModelTest.php</file>
            <file>./tests/models/ClinicModelTest.php</file>
            <file>./tests/models/ClinicsModelTest.php</file>
            <file>./tests/models/DoctorAndServiceModelTest.php</file>
            <file>./tests/models/DoctorModelTest.php</file>
            <file>./tests/models/DoctorsModelTest.php</file>
            <file>./tests/models/DrugModelTest.php</file>
            <file>./tests/models/DrugsModelTest.php</file>
            <file>./tests/models/NotificationModelTest.php</file>
            <file>./tests/models/NotificationsModelTest.php</file>
            <file>./tests/models/PatientModelTest.php</file>
            <file>./tests/models/PatientsModelTest.php</file>
            <file>./tests/models/RoomModelTest.php</file>
            <file>./tests/models/RoomsModelTest.php</file>
            <file>./tests/models/ServiceModelTest.php</file>
            <file>./tests/models/SpecialityModelTest.php</file>
            <file>./tests/models/TreatmentModelTest.php</file>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist processUncoveredFilesFromWhitelist="true">
            <directory suffix=".php">./models</directory>
        </whitelist>
    </filter>
    <logging>
        <log type="coverage-html" target="./tests/coverage" lowUpperBound="35" highLowerBound="70"/>
        <log type="coverage-clover" target="./tests/coverage/clover.xml"/>
    </logging>
    <php>
        <env name="APP_ENV" value="testing"/>
    </php>
</phpunit>
XML;

file_put_contents($tempPhpunitXml, $phpunitXmlContent);

echo "Chạy tất cả các test và tạo báo cáo độ phủ...\n";

// Lấy tên phương thức test từ tham số dòng lệnh (nếu có)
$testMethod = isset($argv[1]) ? $argv[1] : null;

// Tạo lệnh PHPUnit
$phpunitCommand = '/opt/homebrew/opt/php@5.6/bin/php ' . APP_PATH . '/vendor/bin/phpunit -c ' . $tempPhpunitXml;
if ($testMethod) {
    $phpunitCommand .= " --filter=AppointmentRecordsModelTest::{$testMethod}";
}

// Chạy PHPUnit
echo "Đang chạy lệnh: {$phpunitCommand}\n";
passthru($phpunitCommand, $returnCode);

// Xóa file cấu hình tạm thời
unlink($tempPhpunitXml);

if ($returnCode !== 0) {
    echo "Một số test thất bại, nhưng báo cáo độ phủ vẫn được tạo.\n";
} else {
    echo "Tất cả các test đều thành công!\n";
}

echo "Báo cáo độ phủ đã được tạo tại " . APP_PATH . "/tests/coverage/index.html\n";
