<?php
/**
 * Lớp kiểm thử NotificationModel
 *
 * File: api/app/tests/models/NotificationModelTest.php
 * Class: NotificationModelTest
 *
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp NotificationModel, bao gồm:
 * - Các thao tác CRUD (Tạo, Đọc, Cập nhật, Xóa)
 * - Các phương thức đọc thông tin theo ID và name
 *
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class NotificationModelTest extends DatabaseTestCase
{
    /**
     * @var NotificationModel Đối tượng model thông báo dùng trong test
     */
    protected $notificationModel;

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
     * @var int ID của thông báo được tạo để sử dụng chung cho các test
     */
    protected static $testNotificationId;

    /**
     * @var array Dữ liệu thông báo mẫu được tạo
     */
    protected static $testNotificationData;

    /**
     * @var bool Kiểm soát việc bắt đầu/kết thúc transaction
     */
    protected $useTransaction = true;

    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo NotificationModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/NotificationModel.php';
        $this->notificationModel = new NotificationModel();

        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }

        // Tên bảng đầy đủ với prefix
        $fullTableName = TABLE_PREFIX.TABLE_NOTIFICATIONS;

        // Khởi tạo bảng test - sử dụng tên bảng đầy đủ
        $this->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS `{$fullTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `message` varchar(255) NOT NULL,
                `record_id` varchar(255) NOT NULL,
                `record_type` varchar(255) NOT NULL,
                `is_read` tinyint(1) NOT NULL DEFAULT '0',
                `create_at` datetime DEFAULT NULL,
                `update_at` datetime DEFAULT NULL,
                `patient_id` varchar(255) NOT NULL,
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
     * Tạo dữ liệu thông báo mẫu cho test
     *
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu thông báo mẫu
     */
    private function createTestNotification($override = [])
    {
        $timestamp = time();
        $datetime = date('Y-m-d H:i:s', $timestamp);
        return array_merge([
            'message' => 'Test notification message ' . $timestamp,
            'record_id' => 'REC_' . $timestamp,
            'record_type' => 'appointment',
            'is_read' => 0,
            'create_at' => $datetime,
            'update_at' => $datetime,
            'patient_id' => 'PAT_' . $timestamp
        ], $override);
    }

    /**
     * Test case NOTIF_CONS_01: Kiểm tra khởi tạo đối tượng NotificationModel
     */
    public function testConstructor()
    {
        $this->logSection("NOTIF_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng với ID không tồn tại", "Đối tượng được tạo, isAvailable = false");

        // Khởi tạo đối tượng với ID không tồn tại
        $notification = new NotificationModel(0);

        // Kiểm tra đối tượng được khởi tạo nhưng không có sẵn
        $isInstanceOfNotification = $notification instanceof NotificationModel;
        $isNotAvailable = !$notification->isAvailable();

        $this->logResult($isInstanceOfNotification && $isNotAvailable,
            "Instance created: " . ($isInstanceOfNotification ? "Yes" : "No") .
            ", Available: " . (!$isNotAvailable ? "Yes" : "No"));

        $this->assertInstanceOf(NotificationModel::class, $notification);
        $this->assertFalse($notification->isAvailable());
    }

    /**
     * Test case NOTIF_READ_02: Kiểm tra phương thức select với ID
     */
    public function testSelectById()
    {
        $this->logSection("NOTIF_READ_02: Kiểm tra select bằng ID");
        $this->logStep("Tạo dữ liệu test và chọn thông báo theo ID", "Thông báo được tìm thấy");

        // Tạo dữ liệu test
        $notificationData = $this->createTestNotification();
        $tableName = TABLE_PREFIX.TABLE_NOTIFICATIONS;

        $sql = "INSERT INTO `{$tableName}` (message, record_id, record_type, is_read, create_at, update_at, patient_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $notificationData['message'],
            $notificationData['record_id'],
            $notificationData['record_type'],
            $notificationData['is_read'],
            $notificationData['create_at'],
            $notificationData['update_at'],
            $notificationData['patient_id']
        ]);

        // Lấy ID của bản ghi vừa tạo
        $id = $this->pdo->lastInsertId();
        self::$testNotificationId = $id;
        self::$testNotificationData = $notificationData;

        // Chọn thông báo theo ID
        $notification = new NotificationModel($id);

        // Kiểm tra kết quả
        $isAvailable = $notification->isAvailable();
        $correctId = ($id == $notification->get("id"));
        $correctMessage = ($notificationData['message'] == $notification->get("message"));
        $correctRecordId = ($notificationData['record_id'] == $notification->get("record_id"));
        $correctRecordType = ($notificationData['record_type'] == $notification->get("record_type"));
        $correctIsRead = ($notificationData['is_read'] == $notification->get("is_read"));
        $correctCreateAt = ($notificationData['create_at'] == $notification->get("create_at"));
        $correctUpdateAt = ($notificationData['update_at'] == $notification->get("update_at"));
        $correctPatientId = ($notificationData['patient_id'] == $notification->get("patient_id"));

        $this->logResult($isAvailable && $correctId && $correctMessage && $correctRecordId &&
            $correctRecordType && $correctIsRead && $correctCreateAt && $correctUpdateAt &&
            $correctPatientId,
            "Available: " . ($isAvailable ? "Yes" : "No") .
            ", ID match: " . ($correctId ? "Yes" : "No") .
            ", Message match: " . ($correctMessage ? "Yes" : "No") .
            ", Record ID match: " . ($correctRecordId ? "Yes" : "No") .
            ", Record Type match: " . ($correctRecordType ? "Yes" : "No") .
            ", Is Read match: " . ($correctIsRead ? "Yes" : "No") .
            ", Create At match: " . ($correctCreateAt ? "Yes" : "No") .
            ", Update At match: " . ($correctUpdateAt ? "Yes" : "No") .
            ", Patient ID match: " . ($correctPatientId ? "Yes" : "No"));

        $this->assertTrue($notification->isAvailable());
        $this->assertEquals($id, $notification->get("id"));
        $this->assertEquals($notificationData['message'], $notification->get("message"));
        $this->assertEquals($notificationData['record_id'], $notification->get("record_id"));
        $this->assertEquals($notificationData['record_type'], $notification->get("record_type"));
        $this->assertEquals($notificationData['is_read'], $notification->get("is_read"));
        $this->assertEquals($notificationData['create_at'], $notification->get("create_at"));
        $this->assertEquals($notificationData['update_at'], $notification->get("update_at"));
        $this->assertEquals($notificationData['patient_id'], $notification->get("patient_id"));
    }

    /**
     * Test case NOTIF_SEL_NAME_03: Kiểm tra select chỉ hỗ trợ bằng ID
     * Ghi chú: NotificationModel không hỗ trợ select bằng trường name hay message
     * Đây là một test riêng để kiểm tra hành vi đó
     */
    public function testSelectOnlySupportID()
    {
        $this->logSection("NOTIF_SEL_NAME_03: Kiểm tra select chỉ hỗ trợ bằng ID");
        $this->logStep("Kiểm tra rằng NotificationModel chỉ hỗ trợ select theo ID", "Lỗi khi select theo chuỗi");

        // NotificationModel trong phương thức select() kiểm tra xem nếu tham số không phải số,
        // thì coi là name, tuy nhiên bảng notification không có cột name

        // Tạo dữ liệu test
        $notificationData = $this->createTestNotification();
        $tableName = TABLE_PREFIX.TABLE_NOTIFICATIONS;

        $sql = "INSERT INTO `{$tableName}` (message, record_id, record_type, is_read, create_at, update_at, patient_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $notificationData['message'],
            $notificationData['record_id'],
            $notificationData['record_type'],
            $notificationData['is_read'],
            $notificationData['create_at'],
            $notificationData['update_at'],
            $notificationData['patient_id']
        ]);

        $id = $this->pdo->lastInsertId();

        // Kiểm tra với ID số
        $notificationById = new NotificationModel($id);
        $availableById = $notificationById->isAvailable();

        // Kiểm tra rằng tìm kiếm theo ID hoạt động đúng
        $this->assertTrue($availableById);
        $this->logResult($availableById,
            "Select with numeric ID: " . ($availableById ? "Available (correct)" : "Not Available (incorrect)"));

        // Kiểm tra với chuỗi (sẽ cố gắng tìm theo name)
        // Lưu ý: Bảng notifications không có cột name, nên sẽ gây ra lỗi
        // Chúng ta sẽ kiểm tra lỗi này bằng cách bắt ngoại lệ

        $testString = "test_string_" . time();
        $notificationByString = new NotificationModel();

        // Ghi nhận lỗi khi tìm kiếm theo chuỗi
        $errorMessage = "";
        try {
            // Gọi trực tiếp phương thức select với chuỗi để tăng độ phủ code
            $notificationByString->select($testString);
            $this->fail("Expected PDOException was not thrown");
        } catch (PDOException $e) {
            // Bắt ngoại lệ PDOException khi cố gắng tìm kiếm theo cột name không tồn tại
            $errorMessage = $e->getMessage();
            $this->assertContains("Unknown column 'name'", $errorMessage);
        }

        $this->logResult(!empty($errorMessage),
            "Select with string (name): Throws PDOException with message: " . $errorMessage);

        // Ghi chú: Không thể kiểm tra trực tiếp giá trị trả về của phương thức select
        // vì nó sẽ ném ngoại lệ khi tìm kiếm theo chuỗi
        $this->logResult(true,
            "Phương thức select() có lỗi khi tìm kiếm theo chuỗi vì bảng notifications không có cột name");

        // Ghi nhận kết luận
        $this->logResult(true,
            "NotificationModel chỉ hỗ trợ select theo ID số, không hỗ trợ select theo name hay message");
    }

    /**
     * Test case NOTIF_DEF_04: Kiểm tra giá trị mặc định
     */
    public function testExtendDefaults()
    {
        $this->logSection("NOTIF_DEF_04: Kiểm tra giá trị mặc định");
        $this->logStep("Tạo đối tượng mới và gọi phương thức extendDefaults", "Các trường có giá trị mặc định");

        // Tạo mới model và gọi extendDefaults
        $notification = new NotificationModel();
        $notification->extendDefaults();

        // Kiểm tra các giá trị mặc định
        $checkMessage = $notification->get("message") === "";
        $checkRecordId = $notification->get("record_id") === "";
        $checkRecordType = $notification->get("record_type") === "";
        $checkIsRead = $notification->get("is_read") === "";
        $checkCreateAt = $notification->get("create_at") === "";
        $checkUpdateAt = $notification->get("update_at") === "";
        $checkPatientId = $notification->get("patient_id") === "";

        $allCorrect = $checkMessage && $checkRecordId && $checkRecordType && $checkIsRead &&
                      $checkCreateAt && $checkUpdateAt && $checkPatientId;

        $this->logResult($allCorrect,
            "Default values set correctly: " . ($allCorrect ? "Yes" : "No"));

        $this->assertEquals("", $notification->get("message"));
        $this->assertEquals("", $notification->get("record_id"));
        $this->assertEquals("", $notification->get("record_type"));
        $this->assertEquals("", $notification->get("is_read"));
        $this->assertEquals("", $notification->get("create_at"));
        $this->assertEquals("", $notification->get("update_at"));
        $this->assertEquals("", $notification->get("patient_id"));
    }

    /**
     * Test case NOTIF_INS_05: Kiểm tra thêm mới thông báo
     */
    public function testInsert()
    {
        $this->logSection("NOTIF_INS_05: Kiểm tra thêm mới thông báo");
        $this->logStep("Tạo và thêm mới thông báo", "Thông báo được thêm thành công với ID > 0");

        // Tạo dữ liệu test
        $notificationData = $this->createTestNotification();

        // Tạo model mới và thêm dữ liệu
        $notification = new NotificationModel();
        foreach ($notificationData as $key => $value) {
            $notification->set($key, $value);
        }

        // Thực hiện insert
        $id = $notification->insert();

        // Kiểm tra kết quả
        $success = $id > 0 && $notification->isAvailable();

        $this->logResult($success,
            "Insert successful: " . ($success ? "Yes" : "No") . ", ID: " . $id);

        $this->assertTrue($success);
        $this->assertTrue($notification->isAvailable());
        $this->assertGreaterThan(0, $id);

        // Lưu lại ID để sử dụng cho test khác
        if (!self::$testNotificationId) {
            self::$testNotificationId = $id;
            self::$testNotificationData = $notificationData;
        }
    }

    /**
     * Test case NOTIF_UPD_06: Kiểm tra cập nhật thông báo
     */
    public function testUpdate()
    {
        $this->logSection("NOTIF_UPD_06: Kiểm tra cập nhật thông báo");
        $this->logStep("Cập nhật thông tin thông báo", "Dữ liệu được cập nhật thành công");

        // Tạo một mục mới để cập nhật
        $notificationData = $this->createTestNotification([
            'message' => 'Notification To Update ' . time()
        ]);

        $tableName = TABLE_PREFIX.TABLE_NOTIFICATIONS;

        $sql = "INSERT INTO `{$tableName}` (message, record_id, record_type, is_read, create_at, update_at, patient_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $notificationData['message'],
            $notificationData['record_id'],
            $notificationData['record_type'],
            $notificationData['is_read'],
            $notificationData['create_at'],
            $notificationData['update_at'],
            $notificationData['patient_id']
        ]);

        $notificationId = $this->pdo->lastInsertId();

        // Lấy thông báo đã tạo
        $notification = new NotificationModel($notificationId);

        // Đảm bảo thông báo tồn tại
        $this->assertTrue($notification->isAvailable());

        // Cập nhật thông tin
        $timestamp = time();
        $datetime = date('Y-m-d H:i:s', $timestamp);
        $newData = [
            'message' => 'Updated message ' . $timestamp,
            'record_id' => 'Updated_REC_' . $timestamp,
            'record_type' => 'booking',
            'is_read' => 1,
            'create_at' => $datetime,
            'update_at' => $datetime,
            'patient_id' => 'Updated_PAT_' . $timestamp
        ];

        foreach ($newData as $key => $value) {
            $notification->set($key, $value);
        }

        // Thực hiện update
        $result = $notification->update();

        // Kiểm tra kết quả update
        $updateSuccess = $result !== false;

        $this->logResult($updateSuccess,
            "Update result: " . ($updateSuccess ? "Success" : "Failed"));

        // Lấy lại thông báo từ database để kiểm tra dữ liệu đã được cập nhật chưa
        $updatedNotification = new NotificationModel($notificationId);

        // Kiểm tra dữ liệu cập nhật
        $messageUpdated = $updatedNotification->get("message") === $newData['message'];
        $recordIdUpdated = $updatedNotification->get("record_id") === $newData['record_id'];
        $recordTypeUpdated = $updatedNotification->get("record_type") === $newData['record_type'];
        $isReadUpdated = $updatedNotification->get("is_read") === $newData['is_read'];
        $createAtUpdated = $updatedNotification->get("create_at") === $newData['create_at'];
        $updateAtUpdated = $updatedNotification->get("update_at") === $newData['update_at'];
        $patientIdUpdated = $updatedNotification->get("patient_id") === $newData['patient_id'];

        $allUpdated = $messageUpdated && $recordIdUpdated && $recordTypeUpdated &&
                      $isReadUpdated && $createAtUpdated && $updateAtUpdated && $patientIdUpdated;

        $this->logResult($allUpdated,
            "Data updated in DB: " . ($allUpdated ? "Yes" : "No") .
            " (Message: " . $updatedNotification->get("message") .
            ", Record ID: " . $updatedNotification->get("record_id") .
            ", Record Type: " . $updatedNotification->get("record_type") .
            ", Is Read: " . $updatedNotification->get("is_read") .
            ", Create At: " . $updatedNotification->get("create_at") .
            ", Update At: " . $updatedNotification->get("update_at") .
            ", Patient ID: " . $updatedNotification->get("patient_id") . ")");

        $this->assertTrue($updateSuccess);
        $this->assertTrue($allUpdated);
    }

    /**
     * Test case NOTIF_DEL_07: Kiểm tra xóa thông báo
     */
    public function testDelete()
    {
        $this->logSection("NOTIF_DEL_07: Kiểm tra xóa thông báo");
        $this->logStep("Xóa thông báo đã tạo", "Thông báo bị xóa, isAvailable = false");

        // Tạo dữ liệu test mới để xóa
        $notificationData = $this->createTestNotification([
            'message' => 'Notification To Delete ' . time()
        ]);

        $tableName = TABLE_PREFIX.TABLE_NOTIFICATIONS;

        $sql = "INSERT INTO `{$tableName}` (message, record_id, record_type, is_read, create_at, update_at, patient_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $notificationData['message'],
            $notificationData['record_id'],
            $notificationData['record_type'],
            $notificationData['is_read'],
            $notificationData['create_at'],
            $notificationData['update_at'],
            $notificationData['patient_id']
        ]);

        $id = $this->pdo->lastInsertId();

        // Chọn thông báo để xóa
        $notification = new NotificationModel($id);

        // Thực hiện xóa
        $deleteResult = $notification->delete();

        // Kiểm tra kết quả xóa
        $this->logResult($deleteResult,
            "Delete successful: " . ($deleteResult ? "Yes" : "No"));

        // Kiểm tra thông báo không còn tồn tại
        $deletedNotification = new NotificationModel($id);
        $notAvailable = !$deletedNotification->isAvailable();

        $this->logResult($notAvailable,
            "Record deleted from DB: " . ($notAvailable ? "Yes" : "No"));

        $this->assertTrue($deleteResult);
        $this->assertFalse($deletedNotification->isAvailable());

        // Kiểm tra trong database
        $record = $this->getRecord($tableName, ['id' => $id]);
        $this->logResult(!$record, "Record physically deleted: " . (!$record ? "Yes" : "No"));
        $this->assertFalse($record);
    }

    /**
     * Test case NOTIF_ERR_ID_08: Kiểm tra select với ID không tồn tại
     */
    public function testSelectWithNonExistingId()
    {
        $this->logSection("NOTIF_ERR_ID_08: Kiểm tra select với ID không tồn tại");
        $this->logStep("Tìm thông báo với ID không tồn tại", "Model không khả dụng (isAvailable = false)");

        // Tạo ID chắc chắn không tồn tại
        $nonExistingId = 999999;

        // Thử select với ID không tồn tại
        $notification = new NotificationModel($nonExistingId);

        // Kiểm tra kết quả
        $notAvailable = !$notification->isAvailable();

        $this->logResult($notAvailable,
            "Select with non-existing ID: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));

        $this->assertFalse($notification->isAvailable());
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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ NOTIFICATIONMODEL\n");
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