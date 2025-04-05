<?php
/**
 * Lớp kiểm thử ServiceModel
 * 
 * File: api/app/tests/models/ServiceModelTest.php
 * Class: ServiceModelTest
 * 
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp ServiceModel, bao gồm:
 * - Các thao tác CRUD (Tạo, Đọc, Cập nhật, Xóa)
 * - Các phương thức đọc thông tin theo tên dịch vụ
 * 
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class ServiceModelTest extends DatabaseTestCase 
{
    /**
     * @var ServiceModel Đối tượng model dịch vụ dùng trong test
     */
    protected $serviceModel;
    
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
     * @var int ID của dịch vụ được tạo để sử dụng chung cho các test
     */
    protected static $testServiceId;

    /**
     * @var array Dữ liệu dịch vụ mẫu được tạo
     */
    protected static $testServiceData;
    
    /**
     * @var bool Kiểm soát việc bắt đầu/kết thúc transaction
     */
    protected $useTransaction = true;

    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo ServiceModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/ServiceModel.php';
        $this->serviceModel = new ServiceModel();
        
        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }
        
        // Tên bảng đầy đủ với prefix
        $fullTableName = TABLE_PREFIX.TABLE_SERVICES;
        
        // Khởi tạo bảng test - sử dụng tên bảng đầy đủ
        $this->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS `{$fullTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `image` varchar(255) NOT NULL,
                `description` text NOT NULL,
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
     * Tạo dữ liệu dịch vụ mẫu cho test
     * 
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu dịch vụ mẫu
     */
    private function createTestService($override = [])
    {
        $timestamp = time();
        return array_merge([
            'name' => 'Service_' . $timestamp,
            'image' => 'image_' . $timestamp . '.jpg',
            'description' => 'Description for service ' . $timestamp
        ], $override);
    }
    
    /**
     * Test case SV_INIT_01: Kiểm tra khởi tạo đối tượng ServiceModel
     */
    public function testConstructor()
    {
        $this->logSection("SV_INIT_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng với ID không tồn tại", "Đối tượng được tạo, isAvailable = false");
        
        // Khởi tạo đối tượng với ID không tồn tại
        $service = new ServiceModel(0);
        
        // Kiểm tra đối tượng được khởi tạo nhưng không có sẵn
        $isInstanceOfService = $service instanceof ServiceModel;
        $isNotAvailable = !$service->isAvailable();
        
        $this->logResult($isInstanceOfService && $isNotAvailable, 
            "Instance created: " . ($isInstanceOfService ? "Yes" : "No") . 
            ", Available: " . (!$isNotAvailable ? "Yes" : "No"));
        
        $this->assertInstanceOf(ServiceModel::class, $service);
        $this->assertFalse($service->isAvailable());
    }
    
    /**
     * Test case SV_SEL_02: Kiểm tra phương thức select với ID
     */
    public function testSelectById()
    {
        $this->logSection("SV_SEL_02: Kiểm tra select bằng ID");
        $this->logStep("Tạo dữ liệu test và chọn dịch vụ theo ID", "Dịch vụ được tìm thấy");
        
        // Tạo dữ liệu test
        $serviceData = $this->createTestService();
        $tableName = TABLE_PREFIX.TABLE_SERVICES;
        
        $sql = "INSERT INTO `{$tableName}` (name, image, description)
                VALUES (?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $serviceData['name'],
            $serviceData['image'],
            $serviceData['description']
        ]);
        
        // Lấy ID của bản ghi vừa tạo
        $id = $this->pdo->lastInsertId();
        self::$testServiceId = $id;
        self::$testServiceData = $serviceData;
        
        // Chọn dịch vụ theo ID
        $service = new ServiceModel($id);
        
        // Kiểm tra kết quả
        $isAvailable = $service->isAvailable();
        $correctId = ($id == $service->get("id"));
        $correctName = ($serviceData['name'] == $service->get("name"));
        $correctImage = ($serviceData['image'] == $service->get("image"));
        $correctDescription = ($serviceData['description'] == $service->get("description"));
        
        $this->logResult($isAvailable && $correctId && $correctName && $correctImage && $correctDescription, 
            "Available: " . ($isAvailable ? "Yes" : "No") . 
            ", ID match: " . ($correctId ? "Yes" : "No") . 
            ", Name match: " . ($correctName ? "Yes" : "No") . 
            ", Image match: " . ($correctImage ? "Yes" : "No") . 
            ", Description match: " . ($correctDescription ? "Yes" : "No"));
        
        $this->assertTrue($service->isAvailable());
        $this->assertEquals($id, $service->get("id"));
        $this->assertEquals($serviceData['name'], $service->get("name"));
        $this->assertEquals($serviceData['image'], $service->get("image"));
        $this->assertEquals($serviceData['description'], $service->get("description"));
    }
    
    /**
     * Test case SV_SELNAME_03: Kiểm tra phương thức select với tên
     */
    public function testSelectByName()
    {
        $this->logSection("SV_SELNAME_03: Kiểm tra select bằng tên");
        $this->logStep("Chọn dịch vụ theo tên", "Dịch vụ được tìm thấy");
        
        // Tạo dữ liệu test mới với tên duy nhất
        $timestamp = time();
        $serviceData = $this->createTestService([
            'name' => 'service_test_' . $timestamp
        ]);
        
        $tableName = TABLE_PREFIX.TABLE_SERVICES;
        
        $sql = "INSERT INTO `{$tableName}` (name, image, description)
                VALUES (?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $serviceData['name'],
            $serviceData['image'],
            $serviceData['description']
        ]);
        
        // Chọn dịch vụ theo tên
        $service = new ServiceModel($serviceData['name']);
        
        // Kiểm tra kết quả
        $isAvailable = $service->isAvailable();
        $correctName = ($serviceData['name'] == $service->get("name"));
        $correctImage = ($serviceData['image'] == $service->get("image"));
        $correctDescription = ($serviceData['description'] == $service->get("description"));
        
        $this->logResult($isAvailable && $correctName && $correctImage && $correctDescription, 
            "Available: " . ($isAvailable ? "Yes" : "No") . 
            ", Name match: " . ($correctName ? "Yes" : "No") . 
            ", Image match: " . ($correctImage ? "Yes" : "No") . 
            ", Description match: " . ($correctDescription ? "Yes" : "No") . 
            " (Expected: " . $serviceData['name'] . ", Found: " . $service->get("name") . ")");
        
        $this->assertTrue($service->isAvailable());
        $this->assertEquals($serviceData['name'], $service->get("name"));
        $this->assertEquals($serviceData['image'], $service->get("image"));
        $this->assertEquals($serviceData['description'], $service->get("description"));
    }
    
    /**
     * Test case SV_DEF_04: Kiểm tra giá trị mặc định
     */
    public function testExtendDefaults()
    {
        $this->logSection("SV_DEF_04: Kiểm tra giá trị mặc định");
        $this->logStep("Tạo đối tượng mới và gọi phương thức extendDefaults", "Các trường có giá trị mặc định");
        
        // Tạo mới model và gọi extendDefaults
        $service = new ServiceModel();
        $service->extendDefaults();
        
        // Kiểm tra các giá trị mặc định
        $checkName = $service->get("name") === "";
        $checkImage = $service->get("image") === "";
        $checkDescription = $service->get("description") === "";
        
        $allCorrect = $checkName && $checkImage && $checkDescription;
        
        $this->logResult($allCorrect, 
            "Default values set correctly: " . ($allCorrect ? "Yes" : "No"));
        
        $this->assertEquals("", $service->get("name"));
        $this->assertEquals("", $service->get("image"));
        $this->assertEquals("", $service->get("description"));
    }
    
    /**
     * Test case SV_INS_05: Kiểm tra thêm mới dịch vụ
     */
    public function testInsert()
    {
        $this->logSection("SV_INS_05: Kiểm tra thêm mới dịch vụ");
        $this->logStep("Tạo và thêm mới dịch vụ", "Dịch vụ được thêm thành công với ID > 0");
        
        // Tạo dữ liệu test
        $serviceData = $this->createTestService();
        
        // Tạo model mới và thêm dữ liệu
        $service = new ServiceModel();
        foreach ($serviceData as $key => $value) {
            $service->set($key, $value);
        }
        
        // Thực hiện insert
        $id = $service->insert();
        
        // Kiểm tra kết quả
        $success = $id > 0 && $service->isAvailable();
        
        $this->logResult($success, 
            "Insert successful: " . ($success ? "Yes" : "No") . ", ID: " . $id);
        
        $this->assertTrue($success);
        $this->assertTrue($service->isAvailable());
        $this->assertGreaterThan(0, $id);
        
        // Lưu lại ID để sử dụng cho test khác
        if (!self::$testServiceId) {
            self::$testServiceId = $id;
            self::$testServiceData = $serviceData;
        }
    }
    
    /**
     * Test case SV_UPD_06: Kiểm tra cập nhật dịch vụ
     */
    public function testUpdate()
    {
        $this->logSection("SV_UPD_06: Kiểm tra cập nhật dịch vụ");
        $this->logStep("Cập nhật thông tin dịch vụ", "Dữ liệu được cập nhật thành công");
        
        // Tạo một mục mới để cập nhật
        $serviceData = $this->createTestService([
            'name' => 'Service To Update ' . time()
        ]);
        
        $tableName = TABLE_PREFIX.TABLE_SERVICES;
        
        $sql = "INSERT INTO `{$tableName}` (name, image, description)
                VALUES (?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $serviceData['name'],
            $serviceData['image'],
            $serviceData['description']
        ]);
        
        $serviceId = $this->pdo->lastInsertId();
        
        // Lấy dịch vụ đã tạo
        $service = new ServiceModel($serviceId);
        
        // Đảm bảo dịch vụ tồn tại
        $this->assertTrue($service->isAvailable());
        
        // Cập nhật thông tin
        $newData = [
            'name' => 'Updated Service Name_' . time(),
            'image' => 'updated_image_' . time() . '.jpg',
            'description' => 'Updated description for service ' . time()
        ];
        
        foreach ($newData as $key => $value) {
            $service->set($key, $value);
        }
        
        // Thực hiện update
        $result = $service->update();
        
        // Kiểm tra kết quả update
        $updateSuccess = $result !== false;
        
        $this->logResult($updateSuccess, 
            "Update result: " . ($updateSuccess ? "Success" : "Failed"));
        
        // Lấy lại dịch vụ từ database để kiểm tra dữ liệu đã được cập nhật chưa
        $updatedService = new ServiceModel($serviceId);
        
        // Kiểm tra dữ liệu cập nhật
        $nameUpdated = $updatedService->get("name") === $newData['name'];
        $imageUpdated = $updatedService->get("image") === $newData['image'];
        $descriptionUpdated = $updatedService->get("description") === $newData['description'];
        
        $allUpdated = $nameUpdated && $imageUpdated && $descriptionUpdated;
        
        $this->logResult($allUpdated, 
            "Data updated in DB: " . ($allUpdated ? "Yes" : "No") . 
            " (Name: " . $updatedService->get("name") . 
            ", Image: " . $updatedService->get("image") . 
            ", Description: " . $updatedService->get("description") . ")");
        
        $this->assertTrue($updateSuccess);
        $this->assertTrue($allUpdated);
    }
    
    /**
     * Test case SV_DEL_07: Kiểm tra xóa dịch vụ
     */
    public function testDelete()
    {
        $this->logSection("SV_DEL_07: Kiểm tra xóa dịch vụ");
        $this->logStep("Xóa dịch vụ đã tạo", "Dịch vụ bị xóa, isAvailable = false");
        
        // Tạo dữ liệu test mới để xóa
        $serviceData = $this->createTestService([
            'name' => 'Service To Delete ' . time()
        ]);
        
        $tableName = TABLE_PREFIX.TABLE_SERVICES;
        
        $sql = "INSERT INTO `{$tableName}` (name, image, description)
                VALUES (?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $serviceData['name'],
            $serviceData['image'],
            $serviceData['description']
        ]);
        
        $id = $this->pdo->lastInsertId();
        
        // Chọn dịch vụ để xóa
        $service = new ServiceModel($id);
        
        // Thực hiện xóa
        $deleteResult = $service->delete();
        
        // Kiểm tra kết quả xóa
        $this->logResult($deleteResult, 
            "Delete successful: " . ($deleteResult ? "Yes" : "No"));
        
        // Kiểm tra dịch vụ không còn tồn tại
        $deletedService = new ServiceModel($id);
        $notAvailable = !$deletedService->isAvailable();
        
        $this->logResult($notAvailable, 
            "Record deleted from DB: " . ($notAvailable ? "Yes" : "No"));
        
        $this->assertTrue($deleteResult);
        $this->assertFalse($deletedService->isAvailable());
        
        // Kiểm tra trong database
        $record = $this->getRecord($tableName, ['id' => $id]);
        $this->logResult(!$record, "Record physically deleted: " . (!$record ? "Yes" : "No"));
        $this->assertFalse($record);
    }
    
    /**
     * Test case SV_ERR_ID_08: Kiểm tra select với ID không tồn tại
     */
    public function testSelectWithNonExistingId()
    {
        $this->logSection("SV_ERR_ID_08: Kiểm tra select với ID không tồn tại");
        $this->logStep("Tìm dịch vụ với ID không tồn tại", "Model không khả dụng (isAvailable = false)");
        
        // Tạo ID chắc chắn không tồn tại
        $nonExistingId = 999999;
        
        // Thử select với ID không tồn tại
        $service = new ServiceModel($nonExistingId);
        
        // Kiểm tra kết quả
        $notAvailable = !$service->isAvailable();
        
        $this->logResult($notAvailable, 
            "Select with non-existing ID: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));
        
        $this->assertFalse($service->isAvailable());
    }
    
    /**
     * Test case SV_ERR_NAME_09: Kiểm tra select với tên không tồn tại
     */
    public function testSelectWithNonExistingName()
    {
        $this->logSection("SV_ERR_NAME_09: Kiểm tra select với tên không tồn tại");
        $this->logStep("Tìm dịch vụ với tên không tồn tại", "Model không khả dụng (isAvailable = false)");
        
        // Tạo tên chắc chắn không tồn tại
        $nonExistingName = "NonExistingService_" . time();
        
        // Thử select với tên không tồn tại
        $service = new ServiceModel($nonExistingName);
        
        // Kiểm tra kết quả
        $notAvailable = !$service->isAvailable();
        
        $this->logResult($notAvailable, 
            "Select with non-existing name: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));
        
        $this->assertFalse($service->isAvailable());
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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ SERVICEMODEL\n");
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