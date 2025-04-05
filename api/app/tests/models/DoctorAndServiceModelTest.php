<?php
/**
 * Lớp kiểm thử DoctorAndServiceModel
 * 
 * File: api/app/tests/models/DoctorAndServiceModelTest.php
 * Class: DoctorAndServiceModelTest
 * 
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp DoctorAndServiceModel, bao gồm:
 * - Các thao tác CRUD (Tạo, Đọc, Cập nhật, Xóa)
 * - Các phương thức đọc thông tin theo ID
 * 
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class DoctorAndServiceModelTest extends DatabaseTestCase 
{
    /**
     * @var DoctorAndServiceModel Đối tượng model mối quan hệ bác sĩ và dịch vụ dùng trong test
     */
    protected $doctorAndServiceModel;
    
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
     * @var int ID của mối quan hệ được tạo để sử dụng chung cho các test
     */
    protected static $testDoctorAndServiceId;

    /**
     * @var array Dữ liệu mối quan hệ mẫu được tạo
     */
    protected static $testDoctorAndServiceData;
    
    /**
     * @var bool Kiểm soát việc bắt đầu/kết thúc transaction
     */
    protected $useTransaction = true;

    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo DoctorAndServiceModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/DoctorAndServiceModel.php';
        $this->doctorAndServiceModel = new DoctorAndServiceModel();
        
        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }
        
        // Tên bảng đầy đủ với prefix
        $fullTableName = TABLE_PREFIX.TABLE_DOCTOR_AND_SERVICE;
        
        // Khởi tạo bảng test - sử dụng tên bảng đầy đủ
        $this->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS `{$fullTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `service_id` varchar(255) NOT NULL,
                `doctor_id` varchar(255) NOT NULL,
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
     * Tạo dữ liệu mối quan hệ bác sĩ-dịch vụ mẫu cho test
     * 
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu mối quan hệ mẫu
     */
    private function createTestDoctorAndService($override = [])
    {
        $timestamp = time();
        return array_merge([
            'service_id' => 'SRV_' . $timestamp,
            'doctor_id' => 'DOC_' . $timestamp
        ], $override);
    }
    
    /**
     * Test case DOCSVC_CONS_01: Kiểm tra khởi tạo đối tượng DoctorAndServiceModel
     */
    public function testConstructor()
    {
        $this->logSection("DOCSVC_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng với ID không tồn tại", "Đối tượng được tạo, isAvailable = false");
        
        // Khởi tạo đối tượng với ID không tồn tại
        $relation = new DoctorAndServiceModel(0);
        
        // Kiểm tra đối tượng được khởi tạo nhưng không có sẵn
        $isInstanceOfRelation = $relation instanceof DoctorAndServiceModel;
        $isNotAvailable = !$relation->isAvailable();
        
        $this->logResult($isInstanceOfRelation && $isNotAvailable, 
            "Instance created: " . ($isInstanceOfRelation ? "Yes" : "No") . 
            ", Available: " . (!$isNotAvailable ? "Yes" : "No"));
        
        $this->assertInstanceOf(DoctorAndServiceModel::class, $relation);
        $this->assertFalse($relation->isAvailable());
    }
    
    /**
     * Test case DOCSVC_READ_02: Kiểm tra phương thức select với ID
     */
    public function testSelectById()
    {
        $this->logSection("DOCSVC_READ_02: Kiểm tra select bằng ID");
        $this->logStep("Tạo dữ liệu test và chọn mối quan hệ theo ID", "Mối quan hệ được tìm thấy");
        
        // Tạo dữ liệu test
        $relationData = $this->createTestDoctorAndService();
        $tableName = TABLE_PREFIX.TABLE_DOCTOR_AND_SERVICE;
        
        $sql = "INSERT INTO `{$tableName}` (service_id, doctor_id)
                VALUES (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $relationData['service_id'],
            $relationData['doctor_id']
        ]);
        
        // Lấy ID của bản ghi vừa tạo
        $id = $this->pdo->lastInsertId();
        self::$testDoctorAndServiceId = $id;
        self::$testDoctorAndServiceData = $relationData;
        
        // Chọn mối quan hệ theo ID
        $relation = new DoctorAndServiceModel($id);
        
        // Kiểm tra kết quả
        $isAvailable = $relation->isAvailable();
        $correctId = ($id == $relation->get("id"));
        $correctServiceId = ($relationData['service_id'] == $relation->get("service_id"));
        $correctDoctorId = ($relationData['doctor_id'] == $relation->get("doctor_id"));
        
        $this->logResult($isAvailable && $correctId && $correctServiceId && $correctDoctorId, 
            "Available: " . ($isAvailable ? "Yes" : "No") . 
            ", ID match: " . ($correctId ? "Yes" : "No") . 
            ", Service ID match: " . ($correctServiceId ? "Yes" : "No") . 
            ", Doctor ID match: " . ($correctDoctorId ? "Yes" : "No"));
        
        $this->assertTrue($relation->isAvailable());
        $this->assertEquals($id, $relation->get("id"));
        $this->assertEquals($relationData['service_id'], $relation->get("service_id"));
        $this->assertEquals($relationData['doctor_id'], $relation->get("doctor_id"));
    }
    
    /**
     * Test case DOCSVC_FIND_DOC_03: Kiểm tra phương thức select với doctor_id
     */
    public function testSelectByDoctorId()
    {
        $this->logSection("DOCSVC_FIND_DOC_03: Kiểm tra select bằng doctor_id");
        $this->logStep("Chọn mối quan hệ theo doctor_id", "Mối quan hệ được tìm thấy");
        
        // Tạo dữ liệu test
        $relationData = $this->createTestDoctorAndService();
        $tableName = TABLE_PREFIX.TABLE_DOCTOR_AND_SERVICE;
        
        $sql = "INSERT INTO `{$tableName}` (service_id, doctor_id)
                VALUES (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $relationData['service_id'],
            $relationData['doctor_id']
        ]);
        
        // Lấy ID của bản ghi vừa tạo
        $id = $this->pdo->lastInsertId();
        self::$testDoctorAndServiceId = $id;
        self::$testDoctorAndServiceData = $relationData;
        
        // Chọn mối quan hệ theo doctor_id
        $relation = new DoctorAndServiceModel();
        $relation->set("doctor_id", $relationData['doctor_id']);
        
        // Kiểm tra kết quả
        $isAvailable = $relation->isAvailable();
        $correctId = ($id == $relation->get("id"));
        $correctServiceId = ($relationData['service_id'] == $relation->get("service_id"));
        $correctDoctorId = ($relationData['doctor_id'] == $relation->get("doctor_id"));
        
        $this->logResult($isAvailable && $correctId && $correctServiceId && $correctDoctorId, 
            "Available: " . ($isAvailable ? "Yes" : "No") . 
            ", ID match: " . ($correctId ? "Yes" : "No") . 
            ", Service ID match: " . ($correctServiceId ? "Yes" : "No") . 
            ", Doctor ID match: " . ($correctDoctorId ? "Yes" : "No"));
        
        $this->assertTrue($relation->isAvailable());
        $this->assertEquals($id, $relation->get("id"));
        $this->assertEquals($relationData['service_id'], $relation->get("service_id"));
        $this->assertEquals($relationData['doctor_id'], $relation->get("doctor_id"));
    }
    
    /**
     * Test case DOCSVC_FIND_SVC_04: Kiểm tra phương thức select với service_id
     */
    public function testSelectByServiceId()
    {
        $this->logSection("DOCSVC_FIND_SVC_04: Kiểm tra select bằng service_id");
        $this->logStep("Chọn mối quan hệ theo service_id", "Mối quan hệ được tìm thấy");
        
        // Tạo dữ liệu test
        $relationData = $this->createTestDoctorAndService();
        $tableName = TABLE_PREFIX.TABLE_DOCTOR_AND_SERVICE;
        
        $sql = "INSERT INTO `{$tableName}` (service_id, doctor_id)
                VALUES (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $relationData['service_id'],
            $relationData['doctor_id']
        ]);
        
        // Lấy ID của bản ghi vừa tạo
        $id = $this->pdo->lastInsertId();
        self::$testDoctorAndServiceId = $id;
        self::$testDoctorAndServiceData = $relationData;
        
        // Chọn mối quan hệ theo service_id
        $relation = new DoctorAndServiceModel();
        $relation->set("service_id", $relationData['service_id']);
        
        // Kiểm tra kết quả
        $isAvailable = $relation->isAvailable();
        $correctId = ($id == $relation->get("id"));
        $correctServiceId = ($relationData['service_id'] == $relation->get("service_id"));
        $correctDoctorId = ($relationData['doctor_id'] == $relation->get("doctor_id"));
        
        $this->logResult($isAvailable && $correctId && $correctServiceId && $correctDoctorId, 
            "Available: " . ($isAvailable ? "Yes" : "No") . 
            ", ID match: " . ($correctId ? "Yes" : "No") . 
            ", Service ID match: " . ($correctServiceId ? "Yes" : "No") . 
            ", Doctor ID match: " . ($correctDoctorId ? "Yes" : "No"));
        
        $this->assertTrue($relation->isAvailable());
        $this->assertEquals($id, $relation->get("id"));
        $this->assertEquals($relationData['service_id'], $relation->get("service_id"));
        $this->assertEquals($relationData['doctor_id'], $relation->get("doctor_id"));
    }
    
    /**
     * Test case DOCSVC_DEF_05: Kiểm tra giá trị mặc định
     */
    public function testExtendDefaults()
    {
        $this->logSection("DOCSVC_DEF_05: Kiểm tra giá trị mặc định");
        $this->logStep("Tạo đối tượng mới và gọi phương thức extendDefaults", "Các trường có giá trị mặc định");
        
        // Tạo mới model và gọi extendDefaults
        $relation = new DoctorAndServiceModel();
        $relation->extendDefaults();
        
        // Kiểm tra các giá trị mặc định
        $checkServiceId = $relation->get("service_id") === "";
        $checkDoctorId = $relation->get("doctor_id") === "";
        
        $allCorrect = $checkServiceId && $checkDoctorId;
        
        $this->logResult($allCorrect, 
            "Default values set correctly: " . ($allCorrect ? "Yes" : "No"));
        
        $this->assertEquals("", $relation->get("service_id"));
        $this->assertEquals("", $relation->get("doctor_id"));
    }
    
    /**
     * Test case DOCSVC_INS_06: Kiểm tra thêm mới mối quan hệ
     */
    public function testInsert()
    {
        $this->logSection("DOCSVC_INS_06: Kiểm tra thêm mới mối quan hệ");
        $this->logStep("Tạo và thêm mới mối quan hệ", "Mối quan hệ được thêm thành công với ID > 0");
        
        // Tạo dữ liệu test
        $relationData = $this->createTestDoctorAndService();
        
        // Tạo model mới và thêm dữ liệu
        $relation = new DoctorAndServiceModel();
        foreach ($relationData as $key => $value) {
            $relation->set($key, $value);
        }
        
        // Thực hiện insert
        $id = $relation->insert();
        
        // Kiểm tra kết quả
        $success = $id > 0 && $relation->isAvailable();
        
        $this->logResult($success, 
            "Insert successful: " . ($success ? "Yes" : "No") . ", ID: " . $id);
        
        $this->assertTrue($success);
        $this->assertTrue($relation->isAvailable());
        $this->assertGreaterThan(0, $id);
        
        // Lưu lại ID để sử dụng cho test khác
        if (!self::$testDoctorAndServiceId) {
            self::$testDoctorAndServiceId = $id;
            self::$testDoctorAndServiceData = $relationData;
        }
    }
    
    /**
     * Test case DOCSVC_UPD_07: Kiểm tra cập nhật mối quan hệ
     */
    public function testUpdate()
    {
        $this->logSection("DOCSVC_UPD_07: Kiểm tra cập nhật mối quan hệ");
        $this->logStep("Cập nhật thông tin mối quan hệ", "Dữ liệu được cập nhật thành công");
        
        // Tạo một mục mới để cập nhật
        $relationData = $this->createTestDoctorAndService();
        
        $tableName = TABLE_PREFIX.TABLE_DOCTOR_AND_SERVICE;
        
        $sql = "INSERT INTO `{$tableName}` (service_id, doctor_id)
                VALUES (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $relationData['service_id'],
            $relationData['doctor_id']
        ]);
        
        $relationId = $this->pdo->lastInsertId();
        
        // Lấy mối quan hệ đã tạo
        $relation = new DoctorAndServiceModel($relationId);
        
        // Đảm bảo mối quan hệ tồn tại
        $this->assertTrue($relation->isAvailable());
        
        // Cập nhật thông tin
        $newData = [
            'service_id' => 'Updated_SRV_' . time(),
            'doctor_id' => 'Updated_DOC_' . time()
        ];
        
        foreach ($newData as $key => $value) {
            $relation->set($key, $value);
        }
        
        // Thực hiện update
        $result = $relation->update();
        
        // Kiểm tra kết quả update
        $updateSuccess = $result !== false;
        
        $this->logResult($updateSuccess, 
            "Update result: " . ($updateSuccess ? "Success" : "Failed"));
        
        // Lấy lại mối quan hệ từ database để kiểm tra dữ liệu đã được cập nhật chưa
        $updatedRelation = new DoctorAndServiceModel($relationId);
        
        // Kiểm tra dữ liệu cập nhật
        $serviceIdUpdated = $updatedRelation->get("service_id") === $newData['service_id'];
        $doctorIdUpdated = $updatedRelation->get("doctor_id") === $newData['doctor_id'];
        
        $allUpdated = $serviceIdUpdated && $doctorIdUpdated;
        
        $this->logResult($allUpdated, 
            "Data updated in DB: " . ($allUpdated ? "Yes" : "No") . 
            " (Service ID: " . $updatedRelation->get("service_id") . 
            ", Doctor ID: " . $updatedRelation->get("doctor_id") . ")");
        
        $this->assertTrue($updateSuccess);
        $this->assertTrue($allUpdated);
    }
    
    /**
     * Test case DOCSVC_DEL_08: Kiểm tra xóa mối quan hệ
     */
    public function testDelete()
    {
        $this->logSection("DOCSVC_DEL_08: Kiểm tra xóa mối quan hệ");
        $this->logStep("Xóa mối quan hệ đã tạo", "Mối quan hệ bị xóa, isAvailable = false");
        
        // Tạo dữ liệu test mới để xóa
        $relationData = $this->createTestDoctorAndService();
        
        $tableName = TABLE_PREFIX.TABLE_DOCTOR_AND_SERVICE;
        
        $sql = "INSERT INTO `{$tableName}` (service_id, doctor_id)
                VALUES (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $relationData['service_id'],
            $relationData['doctor_id']
        ]);
        
        $id = $this->pdo->lastInsertId();
        
        // Chọn mối quan hệ để xóa
        $relation = new DoctorAndServiceModel($id);
        
        // Thực hiện xóa
        $deleteResult = $relation->delete();
        
        // Kiểm tra kết quả xóa
        $this->logResult($deleteResult, 
            "Delete successful: " . ($deleteResult ? "Yes" : "No"));
        
        // Kiểm tra mối quan hệ không còn tồn tại
        $deletedRelation = new DoctorAndServiceModel($id);
        $notAvailable = !$deletedRelation->isAvailable();
        
        $this->logResult($notAvailable, 
            "Record deleted from DB: " . ($notAvailable ? "Yes" : "No"));
        
        $this->assertTrue($deleteResult);
        $this->assertFalse($deletedRelation->isAvailable());
        
        // Kiểm tra trong database
        $record = $this->getRecord($tableName, ['id' => $id]);
        $this->logResult(!$record, "Record physically deleted: " . (!$record ? "Yes" : "No"));
        $this->assertFalse($record);
    }
    
    /**
     * Test case DOCSVC_ERR_ID_09: Kiểm tra select với ID không tồn tại
     */
    public function testSelectWithNonExistingId()
    {
        $this->logSection("DOCSVC_ERR_ID_09: Kiểm tra select với ID không tồn tại");
        $this->logStep("Tìm mối quan hệ với ID không tồn tại", "Model không khả dụng (isAvailable = false)");
        
        // Tạo ID chắc chắn không tồn tại
        $nonExistingId = 999999;
        
        // Thử select với ID không tồn tại
        $relation = new DoctorAndServiceModel($nonExistingId);
        
        // Kiểm tra kết quả
        $notAvailable = !$relation->isAvailable();
        
        $this->logResult($notAvailable, 
            "Select with non-existing ID: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));
        
        $this->assertFalse($relation->isAvailable());
    }
    
    /**
     * Test case DOCSVC_ERR_DOC_10: Kiểm tra select với doctor_id không tồn tại
     */
    public function testSelectWithNonExistingDoctorId()
    {
        $this->logSection("DOCSVC_ERR_DOC_10: Kiểm tra select với doctor_id không tồn tại");
        $this->logStep("Tìm mối quan hệ với doctor_id không tồn tại", "Model không khả dụng (isAvailable = false)");
        
        // Tạo doctor_id chắc chắn không tồn tại
        $nonExistingDoctorId = 'DOC_999999';
        
        // Thử select với doctor_id không tồn tại
        $relation = new DoctorAndServiceModel();
        $relation->set("doctor_id", $nonExistingDoctorId);
        
        // Kiểm tra kết quả
        $notAvailable = !$relation->isAvailable();
        
        $this->logResult($notAvailable, 
            "Select with non-existing doctor_id: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));
        
        $this->assertFalse($relation->isAvailable());
    }
    
    /**
     * Test case DOCSVC_ERR_SVC_11: Kiểm tra select với service_id không tồn tại
     */
    public function testSelectWithNonExistingServiceId()
    {
        $this->logSection("DOCSVC_ERR_SVC_11: Kiểm tra select với service_id không tồn tại");
        $this->logStep("Tìm mối quan hệ với service_id không tồn tại", "Model không khả dụng (isAvailable = false)");
        
        // Tạo service_id chắc chắn không tồn tại
        $nonExistingServiceId = 'SRV_999999';
        
        // Thử select với service_id không tồn tại
        $relation = new DoctorAndServiceModel();
        $relation->set("service_id", $nonExistingServiceId);
        
        // Kiểm tra kết quả
        $notAvailable = !$relation->isAvailable();
        
        $this->logResult($notAvailable, 
            "Select with non-existing service_id: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));
        
        $this->assertFalse($relation->isAvailable());
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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ DOCTORANDSERVICEMODEL\n");
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