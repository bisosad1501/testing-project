<?php
/**
 * Lớp kiểm thử DrugModel
 * 
 * File: api/app/tests/models/DrugModelTest.php
 * Class: DrugModelTest
 * 
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp DrugModel, bao gồm:
 * - Các thao tác CRUD (Tạo, Đọc, Cập nhật, Xóa)
 * - Các phương thức đọc thông tin theo tên thuốc
 * 
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class DrugModelTest extends DatabaseTestCase 
{
    /**
     * @var DrugModel Đối tượng model thuốc dùng trong test
     */
    protected $drugModel;
    
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
     * @var int ID của thuốc được tạo để sử dụng chung cho các test
     */
    protected static $testDrugId;

    /**
     * @var array Dữ liệu thuốc mẫu được tạo
     */
    protected static $testDrugData;
    
    /**
     * @var bool Kiểm soát việc bắt đầu/kết thúc transaction
     */
    protected $useTransaction = true;

    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo DrugModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/DrugModel.php';
        $this->drugModel = new DrugModel();
        
        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }
        
        // Tên bảng đầy đủ với prefix
        $fullTableName = TABLE_PREFIX.TABLE_DRUGS;
        
        // Khởi tạo bảng test - sử dụng tên bảng đầy đủ
        $this->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS `{$fullTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
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
     * Tạo dữ liệu thuốc mẫu cho test
     * 
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu thuốc mẫu
     */
    private function createTestDrug($override = [])
    {
        $timestamp = time();
        return array_merge([
            'name' => 'Drug_' . $timestamp
        ], $override);
    }
    
    /**
     * Test case DRUG_CONS_01: Kiểm tra khởi tạo đối tượng DrugModel
     */
    public function testConstructor()
    {
        $this->logSection("DRUG_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng với ID không tồn tại", "Đối tượng được tạo, isAvailable = false");
        
        // Khởi tạo đối tượng với ID không tồn tại
        $drug = new DrugModel(0);
        
        // Kiểm tra đối tượng được khởi tạo nhưng không có sẵn
        $isInstanceOfDrug = $drug instanceof DrugModel;
        $isNotAvailable = !$drug->isAvailable();
        
        $this->logResult($isInstanceOfDrug && $isNotAvailable, 
            "Instance created: " . ($isInstanceOfDrug ? "Yes" : "No") . 
            ", Available: " . (!$isNotAvailable ? "Yes" : "No"));
        
        $this->assertInstanceOf(DrugModel::class, $drug);
        $this->assertFalse($drug->isAvailable());
    }
    
    /**
     * Test case DRUG_READ_02: Kiểm tra phương thức select với ID
     */
    public function testSelectById()
    {
        $this->logSection("DRUG_READ_02: Kiểm tra select bằng ID");
        $this->logStep("Tạo dữ liệu test và chọn thuốc theo ID", "Thuốc được tìm thấy");
        
        // Tạo dữ liệu test
        $drugData = $this->createTestDrug();
        $tableName = TABLE_PREFIX.TABLE_DRUGS;
        
        $sql = "INSERT INTO `{$tableName}` (name)
                VALUES (?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $drugData['name']
        ]);
        
        // Lấy ID của bản ghi vừa tạo
        $id = $this->pdo->lastInsertId();
        self::$testDrugId = $id;
        self::$testDrugData = $drugData;
        
        // Chọn thuốc theo ID
        $drug = new DrugModel($id);
        
        // Kiểm tra kết quả
        $isAvailable = $drug->isAvailable();
        $correctId = ($id == $drug->get("id"));
        $correctName = ($drugData['name'] == $drug->get("name"));
        
        $this->logResult($isAvailable && $correctId && $correctName, 
            "Available: " . ($isAvailable ? "Yes" : "No") . 
            ", ID match: " . ($correctId ? "Yes" : "No") . 
            ", Name match: " . ($correctName ? "Yes" : "No") . 
            " (Found: " . $drug->get("name") . ")");
        
        $this->assertTrue($drug->isAvailable());
        $this->assertEquals($id, $drug->get("id"));
        $this->assertEquals($drugData['name'], $drug->get("name"));
    }
    
    /**
     * Test case DRUG_NAME_03: Kiểm tra phương thức select với tên
     */
    public function testSelectByName()
    {
        $this->logSection("DRUG_NAME_03: Kiểm tra select bằng tên");
        $this->logStep("Chọn thuốc theo tên", "Thuốc được tìm thấy");
        
        // Tạo dữ liệu test mới với tên duy nhất
        $timestamp = time();
        $drugData = $this->createTestDrug([
            'name' => 'drug_test_' . $timestamp
        ]);
        
        $tableName = TABLE_PREFIX.TABLE_DRUGS;
        
        $sql = "INSERT INTO `{$tableName}` (name)
                VALUES (?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $drugData['name']
        ]);
        
        // Chọn thuốc theo tên
        $drug = new DrugModel($drugData['name']);
        
        // Kiểm tra kết quả
        $isAvailable = $drug->isAvailable();
        $correctName = ($drugData['name'] == $drug->get("name"));
        
        $this->logResult($isAvailable && $correctName, 
            "Available: " . ($isAvailable ? "Yes" : "No") . 
            ", Name match: " . ($correctName ? "Yes" : "No") . 
            " (Expected: " . $drugData['name'] . ", Found: " . $drug->get("name") . ")");
        
        $this->assertTrue($drug->isAvailable());
        $this->assertEquals($drugData['name'], $drug->get("name"));
    }
    
    /**
     * Test case DRUG_DEF_04: Kiểm tra giá trị mặc định
     */
    public function testExtendDefaults()
    {
        $this->logSection("DRUG_DEF_04: Kiểm tra giá trị mặc định");
        $this->logStep("Tạo đối tượng mới và gọi phương thức extendDefaults", "Các trường có giá trị mặc định");
        
        // Tạo mới model và gọi extendDefaults
        $drug = new DrugModel();
        $drug->extendDefaults();
        
        // Kiểm tra các giá trị mặc định
        $checkName = $drug->get("name") === "";
        
        $allCorrect = $checkName;
        
        $this->logResult($allCorrect, 
            "Default values set correctly: " . ($allCorrect ? "Yes" : "No"));
        
        $this->assertEquals("", $drug->get("name"));
    }
    
    /**
     * Test case DRUG_INS_05: Kiểm tra thêm mới thuốc
     */
    public function testInsert()
    {
        $this->logSection("DRUG_INS_05: Kiểm tra thêm mới thuốc");
        $this->logStep("Tạo và thêm mới thuốc", "Thuốc được thêm thành công với ID > 0");
        
        // Tạo dữ liệu test
        $drugData = $this->createTestDrug();
        
        // Tạo model mới và thêm dữ liệu
        $drug = new DrugModel();
        foreach ($drugData as $key => $value) {
            $drug->set($key, $value);
        }
        
        // Thực hiện insert
        $id = $drug->insert();
        
        // Kiểm tra kết quả
        $success = $id > 0 && $drug->isAvailable();
        
        $this->logResult($success, 
            "Insert successful: " . ($success ? "Yes" : "No") . ", ID: " . $id);
        
        $this->assertTrue($success);
        $this->assertTrue($drug->isAvailable());
        $this->assertGreaterThan(0, $id);
        
        // Lưu lại ID để sử dụng cho test khác
        if (!self::$testDrugId) {
            self::$testDrugId = $id;
            self::$testDrugData = $drugData;
        }
    }
    
    /**
     * Test case DRUG_UPD_06: Kiểm tra cập nhật thuốc
     */
    public function testUpdate()
    {
        $this->logSection("DRUG_UPD_06: Kiểm tra cập nhật thuốc");
        $this->logStep("Cập nhật thông tin thuốc", "Dữ liệu được cập nhật thành công");
        
        // Tạo một mục mới để cập nhật
        $drugData = $this->createTestDrug([
            'name' => 'Drug To Update ' . time()
        ]);
        
        $tableName = TABLE_PREFIX.TABLE_DRUGS;
        
        $sql = "INSERT INTO `{$tableName}` (name)
                VALUES (?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $drugData['name']
        ]);
        
        $drugId = $this->pdo->lastInsertId();
        
        // Lấy thuốc đã tạo
        $drug = new DrugModel($drugId);
        
        // Đảm bảo thuốc tồn tại
        $this->assertTrue($drug->isAvailable());
        
        // Cập nhật thông tin
        $newData = [
            'name' => 'Updated Drug Name_' . time()
        ];
        
        foreach ($newData as $key => $value) {
            $drug->set($key, $value);
        }
        
        // Thực hiện update
        $result = $drug->update();
        
        // Kiểm tra kết quả update
        $updateSuccess = $result !== false;
        
        $this->logResult($updateSuccess, 
            "Update result: " . ($updateSuccess ? "Success" : "Failed"));
        
        // Lấy lại thuốc từ database để kiểm tra dữ liệu đã được cập nhật chưa
        $updatedDrug = new DrugModel($drugId);
        
        // Kiểm tra dữ liệu cập nhật
        $nameUpdated = $updatedDrug->get("name") === $newData['name'];
        
        $allUpdated = $nameUpdated;
        
        $this->logResult($allUpdated, 
            "Data updated in DB: " . ($allUpdated ? "Yes" : "No") . 
            " (Name: " . $updatedDrug->get("name") . ")");
        
        $this->assertTrue($updateSuccess);
        $this->assertTrue($allUpdated);
    }
    
    /**
     * Test case DRUG_DEL_07: Kiểm tra xóa thuốc
     */
    public function testDelete()
    {
        $this->logSection("DRUG_DEL_07: Kiểm tra xóa thuốc");
        $this->logStep("Xóa thuốc đã tạo", "Thuốc bị xóa, isAvailable = false");
        
        // Tạo dữ liệu test mới để xóa
        $drugData = $this->createTestDrug([
            'name' => 'Drug To Delete ' . time()
        ]);
        
        $tableName = TABLE_PREFIX.TABLE_DRUGS;
        
        $sql = "INSERT INTO `{$tableName}` (name)
                VALUES (?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $drugData['name']
        ]);
        
        $id = $this->pdo->lastInsertId();
        
        // Chọn thuốc để xóa
        $drug = new DrugModel($id);
        
        // Thực hiện xóa
        $deleteResult = $drug->delete();
        
        // Kiểm tra kết quả xóa
        $this->logResult($deleteResult, 
            "Delete successful: " . ($deleteResult ? "Yes" : "No"));
        
        // Kiểm tra thuốc không còn tồn tại
        $deletedDrug = new DrugModel($id);
        $notAvailable = !$deletedDrug->isAvailable();
        
        $this->logResult($notAvailable, 
            "Record deleted from DB: " . ($notAvailable ? "Yes" : "No"));
        
        $this->assertTrue($deleteResult);
        $this->assertFalse($deletedDrug->isAvailable());
        
        // Kiểm tra trong database
        $record = $this->getRecord($tableName, ['id' => $id]);
        $this->logResult(!$record, "Record physically deleted: " . (!$record ? "Yes" : "No"));
        $this->assertFalse($record);
    }
    
    /**
     * Test case DRUG_ERR_ID_08: Kiểm tra select với ID không tồn tại
     */
    public function testSelectWithNonExistingId()
    {
        $this->logSection("DRUG_ERR_ID_08: Kiểm tra select với ID không tồn tại");
        $this->logStep("Tìm thuốc với ID không tồn tại", "Model không khả dụng (isAvailable = false)");
        
        // Tạo ID chắc chắn không tồn tại
        $nonExistingId = 999999;
        
        // Thử select với ID không tồn tại
        $drug = new DrugModel($nonExistingId);
        
        // Kiểm tra kết quả
        $notAvailable = !$drug->isAvailable();
        
        $this->logResult($notAvailable, 
            "Select with non-existing ID: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));
        
        $this->assertFalse($drug->isAvailable());
    }
    
    /**
     * Test case DRUG_ERR_NAME_09: Kiểm tra select với tên không tồn tại
     */
    public function testSelectWithNonExistingName()
    {
        $this->logSection("DRUG_ERR_NAME_09: Kiểm tra select với tên không tồn tại");
        $this->logStep("Tìm thuốc với tên không tồn tại", "Model không khả dụng (isAvailable = false)");
        
        // Tạo tên chắc chắn không tồn tại
        $nonExistingName = "NonExistingDrug_" . time();
        
        // Thử select với tên không tồn tại
        $drug = new DrugModel($nonExistingName);
        
        // Kiểm tra kết quả
        $notAvailable = !$drug->isAvailable();
        
        $this->logResult($notAvailable, 
            "Select with non-existing name: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));
        
        $this->assertFalse($drug->isAvailable());
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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ DRUGMODEL\n");
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