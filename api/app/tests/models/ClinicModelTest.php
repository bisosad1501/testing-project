<?php
/**
 * Lớp kiểm thử ClinicModel
 * 
 * File: api/app/tests/models/ClinicModelTest.php
 * Class: ClinicModelTest
 * 
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp ClinicModel, bao gồm:
 * - Các thao tác CRUD (Tạo, Đọc, Cập nhật, Xóa)
 * - Các phương thức đọc thông tin theo tên phòng khám
 * 
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class ClinicModelTest extends DatabaseTestCase 
{
    /**
     * @var ClinicModel Đối tượng model phòng khám dùng trong test
     */
    protected $clinicModel;
    
    /**
     * @var array Lưu trữ kết quả của tất cả các test
     */
    protected static $allTestResults = [];
    
    /**
     * @var string Nhóm test hiện tại
     */
    protected $currentGroup;
    
    /**
     * @var float Thời điểm bắt đầu test
     */
    protected static $startTime;
    
    /**
     * @var int ID của phòng khám được tạo để sử dụng chung cho các test
     */
    protected static $testClinicId;

    /**
     * @var array Dữ liệu phòng khám mẫu được tạo
     */
    protected static $testClinicData;
    
    /**
     * @var bool Kiểm soát việc bắt đầu/kết thúc transaction
     */
    protected $useTransaction = true;

    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo ClinicModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/ClinicModel.php';
        $this->clinicModel = new ClinicModel();
        
        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }
        
        // Tên bảng đầy đủ với prefix
        $fullTableName = TABLE_PREFIX.TABLE_CLINICS;
        
        // Khởi tạo bảng test - sử dụng tên bảng đầy đủ
        $this->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS `{$fullTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `address` text,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Xóa dữ liệu cũ từ bảng test (nếu có)
        $this->executeQuery("TRUNCATE TABLE `{$fullTableName}`");
    }

    /**
     * Ghi log tiêu đề phần test
     * 
     * @param string $title Tiêu đề phần test
     */
    private function logSection($title) 
    {
        $this->currentGroup = $title;
        fwrite(STDOUT, "\n" . str_repeat("=", 50) . "\n");
        fwrite(STDOUT, "🔍 {$title}\n");
        fwrite(STDOUT, str_repeat("=", 50) . "\n");
    }

    /**
     * Ghi log bước test
     * 
     * @param string $description Mô tả bước test
     * @param string|null $expected Kết quả mong đợi
     */
    private function logStep($description, $expected = null)
    {
        fwrite(STDOUT, "\n📋 {$description}\n");
        if ($expected) {
            fwrite(STDOUT, "  Expected: {$expected}\n");
        }
    }

    /**
     * Ghi log kết quả test
     * 
     * @param bool $success Kết quả test (true/false)
     * @param string $actual Kết quả thực tế
     * @param string|null $error Thông báo lỗi (nếu có)
     */
    private function logResult($success, $actual, $error = null) 
    {
        self::$allTestResults[] = [
            'group' => $this->currentGroup,
            'success' => $success,
            'actual' => $actual,
            'error' => $error
        ];

        $icon = $success ? "✅" : "❌";
        $status = $success ? "SUCCESS" : "FAILED";
        
        fwrite(STDOUT, "  Result: {$actual}\n");
        fwrite(STDOUT, "  Status: {$icon} {$status}" . 
            ($error ? " - {$error}" : "") . "\n");
    }

    /**
     * Tạo dữ liệu phòng khám mẫu cho test
     * 
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu phòng khám mẫu
     */
    private function createTestClinic($override = [])
    {
        $timestamp = time();
        return array_merge([
            'name' => 'Clinic_' . $timestamp,
            'address' => 'Address ' . $timestamp
        ], $override);
    }
    
    /**
     * Test case CLINIC_CONS_01: Kiểm tra khởi tạo đối tượng ClinicModel
     */
    public function testConstructor()
    {
        $this->logSection("CLINIC_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng với ID không tồn tại", "Đối tượng được tạo, isAvailable = false");
        
        // Khởi tạo đối tượng với ID không tồn tại
        $clinic = new ClinicModel(0);
        
        // Kiểm tra đối tượng được khởi tạo nhưng không có sẵn
        $isInstanceOfClinic = $clinic instanceof ClinicModel;
        $isNotAvailable = !$clinic->isAvailable();
        
        $this->logResult($isInstanceOfClinic && $isNotAvailable, 
            "Instance created: " . ($isInstanceOfClinic ? "Yes" : "No") . 
            ", Available: " . (!$isNotAvailable ? "Yes" : "No"));
        
        $this->assertInstanceOf(ClinicModel::class, $clinic);
        $this->assertFalse($clinic->isAvailable());
    }
    
    /**
     * Test case CLINIC_READ_02: Kiểm tra phương thức select với ID
     */
    public function testSelectById()
    {
        $this->logSection("CLINIC_READ_02: Kiểm tra select bằng ID");
        $this->logStep("Tạo dữ liệu test và chọn phòng khám theo ID", "Phòng khám được tìm thấy");
        
        // Tạo dữ liệu test
        $clinicData = $this->createTestClinic();
        $tableName = TABLE_PREFIX.TABLE_CLINICS;
        
        $sql = "INSERT INTO `{$tableName}` (name, address)
                VALUES (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $clinicData['name'],
            $clinicData['address']
        ]);
        
        // Lấy ID của bản ghi vừa tạo
        $id = $this->pdo->lastInsertId();
        self::$testClinicId = $id;
        self::$testClinicData = $clinicData;
        
        // Chọn phòng khám theo ID
        $clinic = new ClinicModel($id);
        
        // Kiểm tra kết quả
        $isAvailable = $clinic->isAvailable();
        $correctId = ($id == $clinic->get("id"));
        $correctName = ($clinicData['name'] == $clinic->get("name"));
        
        $this->logResult($isAvailable && $correctId && $correctName, 
            "Available: " . ($isAvailable ? "Yes" : "No") . 
            ", ID match: " . ($correctId ? "Yes" : "No") . 
            ", Name match: " . ($correctName ? "Yes" : "No") . 
            " (Found: " . $clinic->get("name") . ")");
        
        $this->assertTrue($clinic->isAvailable());
        $this->assertEquals($id, $clinic->get("id"));
        $this->assertEquals($clinicData['name'], $clinic->get("name"));
    }
    
    /**
     * Test case CLINIC_NAME_03: Kiểm tra phương thức select với tên
     */
    public function testSelectByName()
    {
        $this->logSection("CLINIC_NAME_03: Kiểm tra select bằng tên");
        $this->logStep("Chọn phòng khám theo tên", "Phòng khám được tìm thấy");
        
        // Tạo dữ liệu test mới với tên duy nhất
        $timestamp = time();
        $clinicData = $this->createTestClinic([
            'name' => 'clinic_test_' . $timestamp
        ]);
        
        $tableName = TABLE_PREFIX.TABLE_CLINICS;
        
        $sql = "INSERT INTO `{$tableName}` (name, address)
                VALUES (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $clinicData['name'],
            $clinicData['address']
        ]);
        
        // Chọn phòng khám theo tên
        $clinic = new ClinicModel($clinicData['name']);
        
        // Kiểm tra kết quả
        $isAvailable = $clinic->isAvailable();
        $correctName = ($clinicData['name'] == $clinic->get("name"));
        
        $this->logResult($isAvailable && $correctName, 
            "Available: " . ($isAvailable ? "Yes" : "No") . 
            ", Name match: " . ($correctName ? "Yes" : "No") . 
            " (Expected: " . $clinicData['name'] . ", Found: " . $clinic->get("name") . ")");
        
        $this->assertTrue($clinic->isAvailable());
        $this->assertEquals($clinicData['name'], $clinic->get("name"));
    }
    
    /**
     * Test case CLINIC_DEF_04: Kiểm tra giá trị mặc định
     */
    public function testExtendDefaults()
    {
        $this->logSection("CLINIC_DEF_04: Kiểm tra giá trị mặc định");
        $this->logStep("Tạo đối tượng mới và gọi phương thức extendDefaults", "Các trường có giá trị mặc định");
        
        // Tạo mới model và gọi extendDefaults
        $clinic = new ClinicModel();
        $clinic->extendDefaults();
        
        // Kiểm tra các giá trị mặc định
        $checkName = $clinic->get("name") === "";
        $checkAddress = $clinic->get("address") === "";
        
        $allCorrect = $checkName && $checkAddress;
        
        $this->logResult($allCorrect, 
            "Default values set correctly: " . ($allCorrect ? "Yes" : "No"));
        
        $this->assertEquals("", $clinic->get("name"));
        $this->assertEquals("", $clinic->get("address"));
    }
    
    /**
     * Test case CLINIC_INS_05: Kiểm tra thêm mới phòng khám
     */
    public function testInsert()
    {
        $this->logSection("CLINIC_INS_05: Kiểm tra thêm mới phòng khám");
        $this->logStep("Tạo và thêm mới phòng khám", "Phòng khám được thêm thành công với ID > 0");
        
        // Tạo dữ liệu test
        $clinicData = $this->createTestClinic();
        
        // Tạo model mới và thêm dữ liệu
        $clinic = new ClinicModel();
        foreach ($clinicData as $key => $value) {
            $clinic->set($key, $value);
        }
        
        // Thực hiện insert
        $id = $clinic->insert();
        
        // Kiểm tra kết quả
        $success = $id > 0 && $clinic->isAvailable();
        
        $this->logResult($success, 
            "Insert successful: " . ($success ? "Yes" : "No") . ", ID: " . $id);
        
        $this->assertTrue($success);
        $this->assertTrue($clinic->isAvailable());
        $this->assertGreaterThan(0, $id);
        
        // Lưu lại ID để sử dụng cho test khác
        if (!self::$testClinicId) {
            self::$testClinicId = $id;
            self::$testClinicData = $clinicData;
        }
    }
    
    /**
     * Test case CLINIC_UPD_06: Kiểm tra cập nhật phòng khám
     */
    public function testUpdate()
    {
        $this->logSection("CLINIC_UPD_06: Kiểm tra cập nhật phòng khám");
        $this->logStep("Cập nhật thông tin phòng khám", "Dữ liệu được cập nhật thành công");
        
        // Nếu chưa có test clinic, tạo mới
        if (!self::$testClinicId) {
            $this->testInsert();
        }
        
        // Lấy phòng khám đã tạo
        $clinic = new ClinicModel(self::$testClinicId);
        
        // Cập nhật thông tin
        $newData = [
            'name' => 'Updated Clinic Name',
            'address' => 'Updated Address'
        ];
        
        foreach ($newData as $key => $value) {
            $clinic->set($key, $value);
        }
        
        // Thực hiện update
        $result = $clinic->update();
        
        // Kiểm tra kết quả update
        $updateSuccess = $result instanceof ClinicModel;
        
        $this->logResult($updateSuccess, 
            "Update result: " . ($updateSuccess ? "Success" : "Failed"));
        
        // Lấy lại phòng khám từ database để kiểm tra dữ liệu đã được cập nhật chưa
        $updatedClinic = new ClinicModel(self::$testClinicId);
        
        // Kiểm tra dữ liệu cập nhật
        $nameUpdated = $updatedClinic->get("name") === $newData['name'];
        $addressUpdated = $updatedClinic->get("address") === $newData['address'];
        
        $allUpdated = $nameUpdated && $addressUpdated;
        
        $this->logResult($allUpdated, 
            "Data updated in DB: " . ($allUpdated ? "Yes" : "No") . 
            " (Name: " . $updatedClinic->get("name") . 
            ", Address: " . $updatedClinic->get("address") . ")");
        
        $this->assertInstanceOf(ClinicModel::class, $result);
        $this->assertTrue($allUpdated);
    }
    
    /**
     * Test case CLINIC_DEL_07: Kiểm tra xóa phòng khám
     */
    public function testDelete()
    {
        $this->logSection("CLINIC_DEL_07: Kiểm tra xóa phòng khám");
        $this->logStep("Xóa phòng khám đã tạo", "Phòng khám bị xóa, isAvailable = false");
        
        // Tạo dữ liệu test mới để xóa
        $clinicData = $this->createTestClinic([
            'name' => 'Clinic To Delete ' . time()
        ]);
        
        $tableName = TABLE_PREFIX.TABLE_CLINICS;
        
        $sql = "INSERT INTO `{$tableName}` (name, address)
                VALUES (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $clinicData['name'],
            $clinicData['address']
        ]);
        
        $id = $this->pdo->lastInsertId();
        
        // Chọn phòng khám để xóa
        $clinic = new ClinicModel($id);
        
        // Thực hiện xóa
        $deleteResult = $clinic->delete();
        
        // Kiểm tra kết quả xóa
        $this->logResult($deleteResult, 
            "Delete successful: " . ($deleteResult ? "Yes" : "No"));
        
        // Kiểm tra phòng khám không còn tồn tại
        $deletedClinic = new ClinicModel($id);
        $notAvailable = !$deletedClinic->isAvailable();
        
        $this->logResult($notAvailable, 
            "Record deleted from DB: " . ($notAvailable ? "Yes" : "No"));
        
        $this->assertTrue($deleteResult);
        $this->assertFalse($deletedClinic->isAvailable());
        
        // Kiểm tra trong database
        $record = $this->getRecord($tableName, ['id' => $id]);
        $this->logResult(!$record, "Record physically deleted: " . (!$record ? "Yes" : "No"));
        $this->assertFalse($record);
    }
    
    /**
     * Test case CLINIC_ERR_ID_08: Kiểm tra select với ID không tồn tại
     */
    public function testSelectWithNonExistingId()
    {
        $this->logSection("CLINIC_ERR_ID_08: Kiểm tra select với ID không tồn tại");
        $this->logStep("Tìm phòng khám với ID không tồn tại", "Model không khả dụng (isAvailable = false)");
        
        // Tạo ID chắc chắn không tồn tại
        $nonExistingId = 999999;
        
        // Thử select với ID không tồn tại
        $clinic = new ClinicModel($nonExistingId);
        
        // Kiểm tra kết quả
        $notAvailable = !$clinic->isAvailable();
        
        $this->logResult($notAvailable, 
            "Select with non-existing ID: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));
        
        $this->assertFalse($clinic->isAvailable());
    }
    
    /**
     * Test case CLINIC_ERR_NAME_09: Kiểm tra select với tên không tồn tại
     */
    public function testSelectWithNonExistingName()
    {
        $this->logSection("CLINIC_ERR_NAME_09: Kiểm tra select với tên không tồn tại");
        $this->logStep("Tìm phòng khám với tên không tồn tại", "Model không khả dụng (isAvailable = false)");
        
        // Tạo tên chắc chắn không tồn tại
        $nonExistingName = "NonExistingClinic_" . time();
        
        // Thử select với tên không tồn tại
        $clinic = new ClinicModel($nonExistingName);
        
        // Kiểm tra kết quả
        $notAvailable = !$clinic->isAvailable();
        
        $this->logResult($notAvailable, 
            "Select with non-existing name: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));
        
        $this->assertFalse($clinic->isAvailable());
    }
    
    /**
     * Dọn dẹp sau khi tất cả các test được chạy xong
     */
    protected function tearDown()
    {
        // In tổng kết nếu là test cuối cùng
        $reflection = new ReflectionClass($this);
        $currentTest = $this->getName();
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        $testMethods = array_filter($methods, function($method) {
            return strpos($method->name, 'test') === 0;
        });
        
        $lastMethod = end($testMethods);
        if ($currentTest === $lastMethod->name) {
            $this->printFinalSummary();
        }
        
        if ($this->useTransaction) {
            parent::tearDown();
        }
    }
    
    /**
     * In tổng kết cuối cùng sau khi tất cả các test hoàn thành
     */
    private function printFinalSummary()
    {
        $totalTests = count(self::$allTestResults);
        $passedTests = count(array_filter(self::$allTestResults, function($result) {
            return $result['success'];
        }));
        $failedTests = $totalTests - $passedTests;
        
        $executionTime = round(microtime(true) - self::$startTime, 2);
        
        fwrite(STDOUT, "\n" . str_repeat("=", 50) . "\n");
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ CLINICMODEL\n");
        fwrite(STDOUT, str_repeat("=", 50) . "\n");
        
        fwrite(STDOUT, "Tổng số test: {$totalTests}\n");
        fwrite(STDOUT, "✅ Thành công: {$passedTests}\n");
        fwrite(STDOUT, "❌ Thất bại: {$failedTests}\n");
        fwrite(STDOUT, "⏱️ Thời gian thực thi: {$executionTime}s\n");
        
        if ($failedTests > 0) {
            fwrite(STDOUT, "\n🔍 CHI TIẾT CÁC TEST THẤT BẠI:\n");
            fwrite(STDOUT, str_repeat("-", 50) . "\n");
            
            foreach (self::$allTestResults as $result) {
                if (!$result['success']) {
                    fwrite(STDOUT, "❌ {$result['group']}\n");
                    fwrite(STDOUT, "   Kết quả: {$result['actual']}\n");
                    if ($result['error']) {
                        fwrite(STDOUT, "   Lỗi: {$result['error']}\n");
                    }
                    fwrite(STDOUT, "\n");
                }
            }
        }
        
        fwrite(STDOUT, str_repeat("=", 50) . "\n");
    }
} 