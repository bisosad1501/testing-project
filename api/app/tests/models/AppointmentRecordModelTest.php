<?php
/**
 * Lớp kiểm thử AppointmentRecordModel
 * 
 * File: api/app/tests/models/AppointmentRecordModelTest.php
 * Class: AppointmentRecordModelTest
 * 
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp AppointmentRecordModel, bao gồm:
 * - Các thao tác CRUD (Tạo, Đọc, Cập nhật, Xóa)
 * - Các phương thức đọc thông tin theo appointment_id
 * 
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class AppointmentRecordModelTest extends DatabaseTestCase 
{
    /**
     * @var AppointmentRecordModel Đối tượng model bản ghi cuộc hẹn dùng trong test
     */
    protected $appointmentRecordModel;
    
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
     * @var int ID của bản ghi cuộc hẹn được tạo để sử dụng chung cho các test
     */
    protected static $testAppointmentRecordId;

    /**
     * @var array Dữ liệu bản ghi cuộc hẹn mẫu được tạo
     */
    protected static $testAppointmentRecordData;
    
    /**
     * @var bool Kiểm soát việc bắt đầu/kết thúc transaction
     */
    protected $useTransaction = true;

    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo AppointmentRecordModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/AppointmentRecordModel.php';
        $this->appointmentRecordModel = new AppointmentRecordModel();
        
        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }
        
        // Tên bảng đầy đủ với prefix
        $fullTableName = TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS;
        
        // Khởi tạo bảng test - sử dụng tên bảng đầy đủ
        $this->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS `{$fullTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `appointment_id` varchar(255) NOT NULL,
                `reason` varchar(255) NOT NULL,
                `description` text NOT NULL,
                `status_before` varchar(255) NOT NULL,
                `status_after` varchar(255) NOT NULL,
                `create_at` varchar(255) NOT NULL,
                `update_at` varchar(255) NOT NULL,
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
     * Tạo dữ liệu bản ghi cuộc hẹn mẫu cho test
     * 
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu bản ghi cuộc hẹn mẫu
     */
    private function createTestAppointmentRecord($override = [])
    {
        $timestamp = time();
        return array_merge([
            'appointment_id' => 'AP_' . $timestamp,
            'reason' => 'Reason_' . $timestamp,
            'description' => 'Description for appointment record ' . $timestamp,
            'status_before' => 'pending',
            'status_after' => 'confirmed',
            'create_at' => date('Y-m-d H:i:s'),
            'update_at' => date('Y-m-d H:i:s')
        ], $override);
    }
    
    /**
     * Test case TC-01: Kiểm tra khởi tạo đối tượng AppointmentRecordModel
     * Test case AREC_CONS_01: Kiểm tra khởi tạo đối tượng AppointmentRecordModel
     */
    public function testConstructor()
    {
        $this->logSection("AREC_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng với ID không tồn tại", "Đối tượng được tạo, isAvailable = false");
        
        // Khởi tạo đối tượng với ID không tồn tại
        $record = new AppointmentRecordModel(0);
        
        // Kiểm tra đối tượng được khởi tạo nhưng không có sẵn
        $isInstanceOfRecord = $record instanceof AppointmentRecordModel;
        $isNotAvailable = !$record->isAvailable();
        
        $this->logResult($isInstanceOfRecord && $isNotAvailable, 
            "Instance created: " . ($isInstanceOfRecord ? "Yes" : "No") . 
            ", Available: " . (!$isNotAvailable ? "Yes" : "No"));
        
        $this->assertInstanceOf(AppointmentRecordModel::class, $record);
        $this->assertFalse($record->isAvailable());
    }
    
    /**
     * Test case TC-02: Kiểm tra phương thức select với ID
     * Test case AREC_READ_02: Kiểm tra phương thức select với ID
     */
    public function testSelectById()
    {
        $this->logSection("AREC_READ_02: Kiểm tra select bằng ID");
        $this->logStep("Tạo dữ liệu test và chọn bản ghi cuộc hẹn theo ID", "Bản ghi cuộc hẹn được tìm thấy");
        
        // Tạo dữ liệu test
        $recordData = $this->createTestAppointmentRecord();
        $tableName = TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS;
        
        $sql = "INSERT INTO `{$tableName}` (appointment_id, reason, description, status_before, status_after, create_at, update_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $recordData['appointment_id'],
            $recordData['reason'],
            $recordData['description'],
            $recordData['status_before'],
            $recordData['status_after'],
            $recordData['create_at'],
            $recordData['update_at']
        ]);
        
        // Lấy ID của bản ghi vừa tạo
        $id = $this->pdo->lastInsertId();
        self::$testAppointmentRecordId = $id;
        self::$testAppointmentRecordData = $recordData;
        
        // Chọn bản ghi cuộc hẹn theo ID
        $record = new AppointmentRecordModel($id);
        
        // Kiểm tra kết quả
        $isAvailable = $record->isAvailable();
        $correctId = ($id == $record->get("id"));
        $correctAppointmentId = ($recordData['appointment_id'] == $record->get("appointment_id"));
        $correctReason = ($recordData['reason'] == $record->get("reason"));
        $correctDescription = ($recordData['description'] == $record->get("description"));
        $correctStatusBefore = ($recordData['status_before'] == $record->get("status_before"));
        $correctStatusAfter = ($recordData['status_after'] == $record->get("status_after"));
        $correctCreateAt = ($recordData['create_at'] == $record->get("create_at"));
        $correctUpdateAt = ($recordData['update_at'] == $record->get("update_at"));
        
        $this->logResult($isAvailable && $correctId && $correctAppointmentId && $correctReason && $correctDescription && 
            $correctStatusBefore && $correctStatusAfter && $correctCreateAt && $correctUpdateAt, 
            "Available: " . ($isAvailable ? "Yes" : "No") . 
            ", ID match: " . ($correctId ? "Yes" : "No") . 
            ", Appointment ID match: " . ($correctAppointmentId ? "Yes" : "No") . 
            ", Reason match: " . ($correctReason ? "Yes" : "No") . 
            ", Description match: " . ($correctDescription ? "Yes" : "No") . 
            ", Status Before match: " . ($correctStatusBefore ? "Yes" : "No") . 
            ", Status After match: " . ($correctStatusAfter ? "Yes" : "No") . 
            ", Create At match: " . ($correctCreateAt ? "Yes" : "No") . 
            ", Update At match: " . ($correctUpdateAt ? "Yes" : "No"));
        
        $this->assertTrue($record->isAvailable());
        $this->assertEquals($id, $record->get("id"));
        $this->assertEquals($recordData['appointment_id'], $record->get("appointment_id"));
        $this->assertEquals($recordData['reason'], $record->get("reason"));
        $this->assertEquals($recordData['description'], $record->get("description"));
        $this->assertEquals($recordData['status_before'], $record->get("status_before"));
        $this->assertEquals($recordData['status_after'], $record->get("status_after"));
        $this->assertEquals($recordData['create_at'], $record->get("create_at"));
        $this->assertEquals($recordData['update_at'], $record->get("update_at"));
    }
    
    /**
     * Test case TC-03: Kiểm tra phương thức select với appointment_id
     * Test case AREC_FIND_03: Kiểm tra phương thức select với appointment_id
     */
    public function testSelectByAppointmentId()
    {
        $this->logSection("AREC_FIND_03: Kiểm tra select bằng appointment_id");
        $this->logStep("Chọn bản ghi cuộc hẹn theo appointment_id", "Bản ghi cuộc hẹn được tìm thấy");
        
        // Tạo dữ liệu test mới với appointment_id duy nhất
        $timestamp = time();
        $recordData = $this->createTestAppointmentRecord([
            'appointment_id' => 'appointment_record_test_' . $timestamp
        ]);
        
        $tableName = TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS;
        
        $sql = "INSERT INTO `{$tableName}` (appointment_id, reason, description, status_before, status_after, create_at, update_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $recordData['appointment_id'],
            $recordData['reason'],
            $recordData['description'],
            $recordData['status_before'],
            $recordData['status_after'],
            $recordData['create_at'],
            $recordData['update_at']
        ]);
        
        // Chọn bản ghi cuộc hẹn theo appointment_id
        $record = new AppointmentRecordModel($recordData['appointment_id']);
        
        // Kiểm tra kết quả
        $isAvailable = $record->isAvailable();
        $correctAppointmentId = ($recordData['appointment_id'] == $record->get("appointment_id"));
        $correctReason = ($recordData['reason'] == $record->get("reason"));
        $correctDescription = ($recordData['description'] == $record->get("description"));
        $correctStatusBefore = ($recordData['status_before'] == $record->get("status_before"));
        $correctStatusAfter = ($recordData['status_after'] == $record->get("status_after"));
        $correctCreateAt = ($recordData['create_at'] == $record->get("create_at"));
        $correctUpdateAt = ($recordData['update_at'] == $record->get("update_at"));
        
        $this->logResult($isAvailable && $correctAppointmentId && $correctReason && $correctDescription && 
            $correctStatusBefore && $correctStatusAfter && $correctCreateAt && $correctUpdateAt, 
            "Available: " . ($isAvailable ? "Yes" : "No") . 
            ", Appointment ID match: " . ($correctAppointmentId ? "Yes" : "No") . 
            ", Reason match: " . ($correctReason ? "Yes" : "No") . 
            ", Description match: " . ($correctDescription ? "Yes" : "No") . 
            ", Status Before match: " . ($correctStatusBefore ? "Yes" : "No") . 
            ", Status After match: " . ($correctStatusAfter ? "Yes" : "No") . 
            ", Create At match: " . ($correctCreateAt ? "Yes" : "No") . 
            ", Update At match: " . ($correctUpdateAt ? "Yes" : "No") . 
            " (Expected: " . $recordData['appointment_id'] . ", Found: " . $record->get("appointment_id") . ")");
        
        $this->assertTrue($record->isAvailable());
        $this->assertEquals($recordData['appointment_id'], $record->get("appointment_id"));
        $this->assertEquals($recordData['reason'], $record->get("reason"));
        $this->assertEquals($recordData['description'], $record->get("description"));
        $this->assertEquals($recordData['status_before'], $record->get("status_before"));
        $this->assertEquals($recordData['status_after'], $record->get("status_after"));
        $this->assertEquals($recordData['create_at'], $record->get("create_at"));
        $this->assertEquals($recordData['update_at'], $record->get("update_at"));
    }
    
    /**
     * Test case TC-04: Kiểm tra phương thức extendDefaults
     * Test case AREC_DEF_04: Kiểm tra phương thức extendDefaults
     */
    public function testExtendDefaults()
    {
        $this->logSection("AREC_DEF_04: Kiểm tra giá trị mặc định");
        $this->logStep("Tạo đối tượng mới và gọi phương thức extendDefaults", "Các trường có giá trị mặc định");
        
        // Tạo mới model và gọi extendDefaults
        $record = new AppointmentRecordModel();
        $record->extendDefaults();
        
        // Kiểm tra các giá trị mặc định
        $checkAppointmentId = $record->get("appointment_id") === "";
        $checkReason = $record->get("reason") === "";
        $checkDescription = $record->get("description") === "";
        $checkStatusBefore = $record->get("status_before") === "";
        $checkStatusAfter = $record->get("status_after") === "";
        $checkCreateAt = $record->get("create_at") === "";
        $checkUpdateAt = $record->get("update_at") === "";
        
        $allCorrect = $checkAppointmentId && $checkReason && $checkDescription && 
            $checkStatusBefore && $checkStatusAfter && $checkCreateAt && $checkUpdateAt;
        
        $this->logResult($allCorrect, 
            "Default values set correctly: " . ($allCorrect ? "Yes" : "No"));
        
        $this->assertEquals("", $record->get("appointment_id"));
        $this->assertEquals("", $record->get("reason"));
        $this->assertEquals("", $record->get("description"));
        $this->assertEquals("", $record->get("status_before"));
        $this->assertEquals("", $record->get("status_after"));
        $this->assertEquals("", $record->get("create_at"));
        $this->assertEquals("", $record->get("update_at"));
    }
    
    /**
     * Test case TC-05: Kiểm tra thêm mới bản ghi cuộc hẹn
     * Test case AREC_INS_05: Kiểm tra thêm mới bản ghi cuộc hẹn
     */
    public function testInsert()
    {
        $this->logSection("AREC_INS_05: Kiểm tra thêm mới bản ghi cuộc hẹn");
        $this->logStep("Tạo và thêm mới bản ghi cuộc hẹn", "Bản ghi cuộc hẹn được thêm thành công với ID > 0");
        
        // Tạo dữ liệu test
        $recordData = $this->createTestAppointmentRecord();
        
        // Tạo model mới và thêm dữ liệu
        $record = new AppointmentRecordModel();
        foreach ($recordData as $key => $value) {
            $record->set($key, $value);
        }
        
        // Thực hiện insert
        $id = $record->insert();
        
        // Kiểm tra kết quả
        $success = $id > 0 && $record->isAvailable();
        
        $this->logResult($success, 
            "Insert successful: " . ($success ? "Yes" : "No") . ", ID: " . $id);
        
        $this->assertTrue($success);
        $this->assertTrue($record->isAvailable());
        $this->assertGreaterThan(0, $id);
        
        // Lưu lại ID để sử dụng cho test khác
        if (!self::$testAppointmentRecordId) {
            self::$testAppointmentRecordId = $id;
            self::$testAppointmentRecordData = $recordData;
        }
    }
    
    /**
     * Test case TC-06: Kiểm tra cập nhật bản ghi cuộc hẹn
     * Test case AREC_UPD_06: Kiểm tra cập nhật bản ghi cuộc hẹn
     */
    public function testUpdate()
    {
        $this->logSection("AREC_UPD_06: Kiểm tra cập nhật bản ghi cuộc hẹn");
        $this->logStep("Cập nhật thông tin bản ghi cuộc hẹn", "Dữ liệu được cập nhật thành công");
        
        // Tạo một mục mới để cập nhật
        $recordData = $this->createTestAppointmentRecord([
            'appointment_id' => 'AppointmentRecord To Update ' . time()
        ]);
        
        $tableName = TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS;
        
        $sql = "INSERT INTO `{$tableName}` (appointment_id, reason, description, status_before, status_after, create_at, update_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $recordData['appointment_id'],
            $recordData['reason'],
            $recordData['description'],
            $recordData['status_before'],
            $recordData['status_after'],
            $recordData['create_at'],
            $recordData['update_at']
        ]);
        
        $recordId = $this->pdo->lastInsertId();
        
        // Lấy bản ghi cuộc hẹn đã tạo
        $record = new AppointmentRecordModel($recordId);
        
        // Đảm bảo bản ghi tồn tại
        $this->assertTrue($record->isAvailable());
        
        // Cập nhật thông tin
        $newData = [
            'appointment_id' => 'Updated AppointmentRecord ID_' . time(),
            'reason' => 'Updated Reason_' . time(),
            'description' => 'Updated description for appointment record ' . time(),
            'status_before' => 'confirmed',
            'status_after' => 'completed',
            'create_at' => $recordData['create_at'], // Giữ nguyên
            'update_at' => date('Y-m-d H:i:s') // Cập nhật thời gian
        ];
        
        foreach ($newData as $key => $value) {
            $record->set($key, $value);
        }
        
        // Thực hiện update
        $result = $record->update();
        
        // Kiểm tra kết quả update
        $updateSuccess = $result !== false;
        
        $this->logResult($updateSuccess, 
            "Update result: " . ($updateSuccess ? "Success" : "Failed"));
        
        // Lấy lại bản ghi từ database để kiểm tra dữ liệu đã được cập nhật chưa
        $updatedRecord = new AppointmentRecordModel($recordId);
        
        // Kiểm tra dữ liệu cập nhật
        $appointmentIdUpdated = $updatedRecord->get("appointment_id") === $newData['appointment_id'];
        $reasonUpdated = $updatedRecord->get("reason") === $newData['reason'];
        $descriptionUpdated = $updatedRecord->get("description") === $newData['description'];
        $statusBeforeUpdated = $updatedRecord->get("status_before") === $newData['status_before'];
        $statusAfterUpdated = $updatedRecord->get("status_after") === $newData['status_after'];
        $createAtUpdated = $updatedRecord->get("create_at") === $newData['create_at'];
        $updateAtUpdated = $updatedRecord->get("update_at") === $newData['update_at'];
        
        $allUpdated = $appointmentIdUpdated && $reasonUpdated && $descriptionUpdated && 
            $statusBeforeUpdated && $statusAfterUpdated && $createAtUpdated && $updateAtUpdated;
        
        $this->logResult($allUpdated, 
            "Data updated in DB: " . ($allUpdated ? "Yes" : "No") . 
            " (Appointment ID: " . $updatedRecord->get("appointment_id") . 
            ", Reason: " . $updatedRecord->get("reason") . 
            ", Description: " . $updatedRecord->get("description") . 
            ", Status Before: " . $updatedRecord->get("status_before") . 
            ", Status After: " . $updatedRecord->get("status_after") . 
            ", Create At: " . $updatedRecord->get("create_at") . 
            ", Update At: " . $updatedRecord->get("update_at") . ")");
        
        $this->assertTrue($updateSuccess);
        $this->assertTrue($allUpdated);
    }
    
    /**
     * Test case TC-07: Kiểm tra xóa bản ghi cuộc hẹn
     * Test case AREC_DEL_07: Kiểm tra xóa bản ghi cuộc hẹn
     */
    public function testDelete()
    {
        $this->logSection("AREC_DEL_07: Kiểm tra xóa bản ghi cuộc hẹn");
        $this->logStep("Xóa bản ghi cuộc hẹn đã tạo", "Bản ghi cuộc hẹn bị xóa, isAvailable = false");
        
        // Tạo dữ liệu test mới để xóa
        $recordData = $this->createTestAppointmentRecord([
            'appointment_id' => 'AppointmentRecord To Delete ' . time()
        ]);
        
        $tableName = TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS;
        
        $sql = "INSERT INTO `{$tableName}` (appointment_id, reason, description, status_before, status_after, create_at, update_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $recordData['appointment_id'],
            $recordData['reason'],
            $recordData['description'],
            $recordData['status_before'],
            $recordData['status_after'],
            $recordData['create_at'],
            $recordData['update_at']
        ]);
        
        $id = $this->pdo->lastInsertId();
        
        // Chọn bản ghi cuộc hẹn để xóa
        $record = new AppointmentRecordModel($id);
        
        // Thực hiện xóa
        $deleteResult = $record->delete();
        
        // Kiểm tra kết quả xóa
        $this->logResult($deleteResult, 
            "Delete successful: " . ($deleteResult ? "Yes" : "No"));
        
        // Kiểm tra bản ghi cuộc hẹn không còn tồn tại
        $deletedRecord = new AppointmentRecordModel($id);
        $notAvailable = !$deletedRecord->isAvailable();
        
        $this->logResult($notAvailable, 
            "Record deleted from DB: " . ($notAvailable ? "Yes" : "No"));
        
        $this->assertTrue($deleteResult);
        $this->assertFalse($deletedRecord->isAvailable());
        
        // Kiểm tra trong database
        $record = $this->getRecord($tableName, ['id' => $id]);
        $this->logResult(!$record, "Record physically deleted: " . (!$record ? "Yes" : "No"));
        $this->assertFalse($record);
    }
    
    /**
     * Test case TC-08: Kiểm tra select với ID không tồn tại
     * Test case AREC_ERR_ID_08: Kiểm tra select với ID không tồn tại
     */
    public function testSelectWithNonExistingId()
    {
        $this->logSection("AREC_ERR_ID_08: Kiểm tra select với ID không tồn tại");
        $this->logStep("Tìm bản ghi cuộc hẹn với ID không tồn tại", "Model không khả dụng (isAvailable = false)");
        
        // Tạo ID chắc chắn không tồn tại
        $nonExistingId = 999999;
        
        // Thử select với ID không tồn tại
        $record = new AppointmentRecordModel($nonExistingId);
        
        // Kiểm tra kết quả
        $notAvailable = !$record->isAvailable();
        
        $this->logResult($notAvailable, 
            "Select with non-existing ID: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));
        
        $this->assertFalse($record->isAvailable());
    }
    
    /**
     * Test case TC-09: Kiểm tra select với appointment_id không tồn tại
     * Test case AREC_ERR_FIND_09: Kiểm tra select với appointment_id không tồn tại
     */
    public function testSelectWithNonExistingAppointmentId()
    {
        $this->logSection("AREC_ERR_FIND_09: Kiểm tra select với appointment_id không tồn tại");
        $this->logStep("Tìm bản ghi cuộc hẹn với appointment_id không tồn tại", "Model không khả dụng (isAvailable = false)");
        
        // Tạo appointment_id chắc chắn không tồn tại
        $nonExistingAppointmentId = "NonExistingAppointmentRecord_" . time();
        
        // Thử select với appointment_id không tồn tại
        $record = new AppointmentRecordModel($nonExistingAppointmentId);
        
        // Kiểm tra kết quả
        $notAvailable = !$record->isAvailable();
        
        $this->logResult($notAvailable, 
            "Select with non-existing appointment_id: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));
        
        $this->assertFalse($record->isAvailable());
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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ APPOINTMENTRECORDMODEL\n");
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