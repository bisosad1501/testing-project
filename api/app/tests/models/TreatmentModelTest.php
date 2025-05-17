<?php
/**
 * Lớp kiểm thử TreatmentModel
 *
 * File: api/app/tests/models/TreatmentModelTest.php
 * Class: TreatmentModelTest
 *
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp TreatmentModel, bao gồm:
 * - Các thao tác CRUD (Tạo, Đọc, Cập nhật, Xóa)
 * - Các phương thức đọc thông tin theo ID và name
 *
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class TreatmentModelTest extends DatabaseTestCase
{
    /**
     * @var TreatmentModel Đối tượng model phương pháp điều trị dùng trong test
     */
    protected $treatmentModel;

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
     * @var int ID của phương pháp điều trị được tạo để sử dụng chung cho các test
     */
    protected static $testTreatmentId;

    /**
     * @var array Dữ liệu phương pháp điều trị mẫu được tạo
     */
    protected static $testTreatmentData;

    /**
     * @var bool Kiểm soát việc bắt đầu/kết thúc transaction
     */
    protected $useTransaction = true;

    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo TreatmentModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/TreatmentModel.php';
        $this->treatmentModel = new TreatmentModel();

        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }

        // Tên bảng đầy đủ với prefix
        $fullTableName = TABLE_PREFIX.TABLE_TREATMENTS;

        // Khởi tạo bảng test - sử dụng tên bảng đầy đủ
        $this->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS `{$fullTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `appointment_id` varchar(255) NOT NULL,
                `name` varchar(255) NOT NULL,
                `type` varchar(255) NOT NULL,
                `times` varchar(255) NOT NULL,
                `purpose` varchar(255) NOT NULL,
                `instruction` text NOT NULL,
                `repeat_days` varchar(255) NOT NULL,
                `repeat_time` varchar(255) NOT NULL,
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
     * Tạo dữ liệu phương pháp điều trị mẫu cho test
     *
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu phương pháp điều trị mẫu
     */
    private function createTestTreatment($override = [])
    {
        $timestamp = time();
        return array_merge([
            'appointment_id' => 'AP_' . $timestamp,
            'name' => 'Treatment_' . $timestamp,
            'type' => 'Type_' . $timestamp,
            'times' => '3 times daily',
            'purpose' => 'Purpose_' . $timestamp,
            'instruction' => 'Instruction for treatment ' . $timestamp,
            'repeat_days' => '7',
            'repeat_time' => '3'
        ], $override);
    }

    /**
     * Test case TREAT_CONS_01: Kiểm tra khởi tạo đối tượng TreatmentModel
     */
    public function testConstructor()
    {
        $this->logSection("TREAT_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng với ID không tồn tại", "Đối tượng được tạo, isAvailable = false");

        // Khởi tạo đối tượng với ID không tồn tại
        $treatment = new TreatmentModel(0);

        // Kiểm tra đối tượng được khởi tạo nhưng không có sẵn
        $isInstanceOfTreatment = $treatment instanceof TreatmentModel;
        $isNotAvailable = !$treatment->isAvailable();

        $this->logResult($isInstanceOfTreatment && $isNotAvailable,
            "Instance created: " . ($isInstanceOfTreatment ? "Yes" : "No") .
            ", Available: " . (!$isNotAvailable ? "Yes" : "No"));

        $this->assertInstanceOf(TreatmentModel::class, $treatment);
        $this->assertFalse($treatment->isAvailable());
    }

    /**
     * Test case TREAT_READ_02: Kiểm tra phương thức select với ID
     */
    public function testSelectById()
    {
        $this->logSection("TREAT_READ_02: Kiểm tra select bằng ID");
        $this->logStep("Tạo dữ liệu test và chọn phương pháp điều trị theo ID", "Phương pháp điều trị được tìm thấy");

        // Tạo dữ liệu test
        $treatmentData = $this->createTestTreatment();
        $tableName = TABLE_PREFIX.TABLE_TREATMENTS;

        $sql = "INSERT INTO `{$tableName}` (appointment_id, name, type, times, purpose, instruction, repeat_days, repeat_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $treatmentData['appointment_id'],
            $treatmentData['name'],
            $treatmentData['type'],
            $treatmentData['times'],
            $treatmentData['purpose'],
            $treatmentData['instruction'],
            $treatmentData['repeat_days'],
            $treatmentData['repeat_time']
        ]);

        // Lấy ID của bản ghi vừa tạo
        $id = $this->pdo->lastInsertId();
        self::$testTreatmentId = $id;
        self::$testTreatmentData = $treatmentData;

        // Chọn phương pháp điều trị theo ID
        $treatment = new TreatmentModel($id);

        // Kiểm tra kết quả
        $isAvailable = $treatment->isAvailable();
        $correctId = ($id == $treatment->get("id"));
        $correctAppointmentId = ($treatmentData['appointment_id'] == $treatment->get("appointment_id"));
        $correctName = ($treatmentData['name'] == $treatment->get("name"));
        $correctType = ($treatmentData['type'] == $treatment->get("type"));
        $correctTimes = ($treatmentData['times'] == $treatment->get("times"));
        $correctPurpose = ($treatmentData['purpose'] == $treatment->get("purpose"));
        $correctInstruction = ($treatmentData['instruction'] == $treatment->get("instruction"));
        $correctRepeatDays = ($treatmentData['repeat_days'] == $treatment->get("repeat_days"));
        $correctRepeatTime = ($treatmentData['repeat_time'] == $treatment->get("repeat_time"));

        $this->logResult($isAvailable && $correctId && $correctAppointmentId && $correctName &&
            $correctType && $correctTimes && $correctPurpose && $correctInstruction &&
            $correctRepeatDays && $correctRepeatTime,
            "Available: " . ($isAvailable ? "Yes" : "No") .
            ", ID match: " . ($correctId ? "Yes" : "No") .
            ", Appointment ID match: " . ($correctAppointmentId ? "Yes" : "No") .
            ", Name match: " . ($correctName ? "Yes" : "No") .
            ", Type match: " . ($correctType ? "Yes" : "No") .
            ", Times match: " . ($correctTimes ? "Yes" : "No") .
            ", Purpose match: " . ($correctPurpose ? "Yes" : "No") .
            ", Instruction match: " . ($correctInstruction ? "Yes" : "No") .
            ", Repeat days match: " . ($correctRepeatDays ? "Yes" : "No") .
            ", Repeat time match: " . ($correctRepeatTime ? "Yes" : "No"));

        $this->assertTrue($treatment->isAvailable());
        $this->assertEquals($id, $treatment->get("id"));
        $this->assertEquals($treatmentData['appointment_id'], $treatment->get("appointment_id"));
        $this->assertEquals($treatmentData['name'], $treatment->get("name"));
        $this->assertEquals($treatmentData['type'], $treatment->get("type"));
        $this->assertEquals($treatmentData['times'], $treatment->get("times"));
        $this->assertEquals($treatmentData['purpose'], $treatment->get("purpose"));
        $this->assertEquals($treatmentData['instruction'], $treatment->get("instruction"));
        $this->assertEquals($treatmentData['repeat_days'], $treatment->get("repeat_days"));
        $this->assertEquals($treatmentData['repeat_time'], $treatment->get("repeat_time"));
    }

    /**
     * Test case TREAT_NAME_03: Kiểm tra select với name
     */
    public function testSelectByName()
    {
        $this->logSection("TREAT_NAME_03: Kiểm tra select bằng name");
        $this->logStep("Chọn phương pháp điều trị theo name", "Phương pháp điều trị được tìm thấy");

        // Tạo dữ liệu test mới với tên duy nhất
        $timestamp = time();
        $treatmentData = $this->createTestTreatment([
            'name' => 'treatment_test_' . $timestamp
        ]);

        $tableName = TABLE_PREFIX.TABLE_TREATMENTS;

        $sql = "INSERT INTO `{$tableName}` (appointment_id, name, type, times, purpose, instruction, repeat_days, repeat_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $treatmentData['appointment_id'],
            $treatmentData['name'],
            $treatmentData['type'],
            $treatmentData['times'],
            $treatmentData['purpose'],
            $treatmentData['instruction'],
            $treatmentData['repeat_days'],
            $treatmentData['repeat_time']
        ]);

        // Chọn phương pháp điều trị theo name
        $treatment = new TreatmentModel($treatmentData['name']);

        // Kiểm tra kết quả
        $isAvailable = $treatment->isAvailable();
        $correctAppointmentId = ($treatmentData['appointment_id'] == $treatment->get("appointment_id"));
        $correctName = ($treatmentData['name'] == $treatment->get("name"));
        $correctType = ($treatmentData['type'] == $treatment->get("type"));
        $correctTimes = ($treatmentData['times'] == $treatment->get("times"));
        $correctPurpose = ($treatmentData['purpose'] == $treatment->get("purpose"));
        $correctInstruction = ($treatmentData['instruction'] == $treatment->get("instruction"));
        $correctRepeatDays = ($treatmentData['repeat_days'] == $treatment->get("repeat_days"));
        $correctRepeatTime = ($treatmentData['repeat_time'] == $treatment->get("repeat_time"));

        $this->logResult($isAvailable && $correctAppointmentId && $correctName &&
            $correctType && $correctTimes && $correctPurpose && $correctInstruction &&
            $correctRepeatDays && $correctRepeatTime,
            "Available: " . ($isAvailable ? "Yes" : "No") .
            ", Appointment ID match: " . ($correctAppointmentId ? "Yes" : "No") .
            ", Name match: " . ($correctName ? "Yes" : "No") .
            ", Type match: " . ($correctType ? "Yes" : "No") .
            ", Times match: " . ($correctTimes ? "Yes" : "No") .
            ", Purpose match: " . ($correctPurpose ? "Yes" : "No") .
            ", Instruction match: " . ($correctInstruction ? "Yes" : "No") .
            ", Repeat days match: " . ($correctRepeatDays ? "Yes" : "No") .
            ", Repeat time match: " . ($correctRepeatTime ? "Yes" : "No") .
            " (Expected: " . $treatmentData['name'] . ", Found: " . $treatment->get("name") . ")");

        $this->assertTrue($treatment->isAvailable());
        $this->assertEquals($treatmentData['appointment_id'], $treatment->get("appointment_id"));
        $this->assertEquals($treatmentData['name'], $treatment->get("name"));
        $this->assertEquals($treatmentData['type'], $treatment->get("type"));
        $this->assertEquals($treatmentData['times'], $treatment->get("times"));
        $this->assertEquals($treatmentData['purpose'], $treatment->get("purpose"));
        $this->assertEquals($treatmentData['instruction'], $treatment->get("instruction"));
        $this->assertEquals($treatmentData['repeat_days'], $treatment->get("repeat_days"));
        $this->assertEquals($treatmentData['repeat_time'], $treatment->get("repeat_time"));
    }

    /**
     * Test case TREAT_DEF_04: Kiểm tra giá trị mặc định
     */
    public function testExtendDefaults()
    {
        $this->logSection("TREAT_DEF_04: Kiểm tra giá trị mặc định");
        $this->logStep("Tạo đối tượng mới và gọi phương thức extendDefaults", "Các trường có giá trị mặc định");

        // Tạo mới model và gọi extendDefaults
        $treatment = new TreatmentModel();
        $treatment->extendDefaults();

        // Kiểm tra các giá trị mặc định
        $checkAppointmentId = $treatment->get("appointment_id") === "";
        $checkName = $treatment->get("name") === "";
        $checkType = $treatment->get("type") === "";
        $checkTimes = $treatment->get("times") === "";
        $checkPurpose = $treatment->get("purpose") === "";
        $checkInstruction = $treatment->get("instruction") === "";
        $checkRepeatDays = $treatment->get("repeat_days") === "";
        $checkRepeatTime = $treatment->get("repeat_time") === "";

        $allCorrect = $checkAppointmentId && $checkName && $checkType && $checkTimes &&
                      $checkPurpose && $checkInstruction && $checkRepeatDays && $checkRepeatTime;

        $this->logResult($allCorrect,
            "Default values set correctly: " . ($allCorrect ? "Yes" : "No"));

        $this->assertEquals("", $treatment->get("appointment_id"));
        $this->assertEquals("", $treatment->get("name"));
        $this->assertEquals("", $treatment->get("type"));
        $this->assertEquals("", $treatment->get("times"));
        $this->assertEquals("", $treatment->get("purpose"));
        $this->assertEquals("", $treatment->get("instruction"));
        $this->assertEquals("", $treatment->get("repeat_days"));
        $this->assertEquals("", $treatment->get("repeat_time"));
    }

    /**
     * Test case TREAT_INS_05: Kiểm tra thêm mới phương pháp điều trị
     */
    public function testInsert()
    {
        $this->logSection("TREAT_INS_05: Kiểm tra thêm mới phương pháp điều trị");
        $this->logStep("Tạo và thêm mới phương pháp điều trị", "Phương pháp điều trị được thêm thành công với ID > 0");

        // Tạo dữ liệu test
        $treatmentData = $this->createTestTreatment();

        // Tạo model mới và thêm dữ liệu
        $treatment = new TreatmentModel();
        foreach ($treatmentData as $key => $value) {
            $treatment->set($key, $value);
        }

        // Thực hiện insert
        $id = $treatment->insert();

        // Kiểm tra kết quả
        $success = $id > 0 && $treatment->isAvailable();

        $this->logResult($success,
            "Insert successful: " . ($success ? "Yes" : "No") . ", ID: " . $id);

        $this->assertTrue($success);
        $this->assertTrue($treatment->isAvailable());
        $this->assertGreaterThan(0, $id);

        // Lưu lại ID để sử dụng cho test khác
        if (!self::$testTreatmentId) {
            self::$testTreatmentId = $id;
            self::$testTreatmentData = $treatmentData;
        }
    }

    /**
     * Test case TREAT_UPD_06: Kiểm tra cập nhật phương pháp điều trị
     */
    public function testUpdate()
    {
        $this->logSection("TREAT_UPD_06: Kiểm tra cập nhật phương pháp điều trị");
        $this->logStep("Cập nhật thông tin phương pháp điều trị", "Dữ liệu được cập nhật thành công");

        // Tạo một mục mới để cập nhật
        $treatmentData = $this->createTestTreatment([
            'name' => 'Treatment To Update ' . time()
        ]);

        $tableName = TABLE_PREFIX.TABLE_TREATMENTS;

        $sql = "INSERT INTO `{$tableName}` (appointment_id, name, type, times, purpose, instruction, repeat_days, repeat_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $treatmentData['appointment_id'],
            $treatmentData['name'],
            $treatmentData['type'],
            $treatmentData['times'],
            $treatmentData['purpose'],
            $treatmentData['instruction'],
            $treatmentData['repeat_days'],
            $treatmentData['repeat_time']
        ]);

        $treatmentId = $this->pdo->lastInsertId();

        // Lấy phương pháp điều trị đã tạo
        $treatment = new TreatmentModel($treatmentId);

        // Đảm bảo phương pháp điều trị tồn tại
        $this->assertTrue($treatment->isAvailable());

        // Cập nhật thông tin
        $newData = [
            'appointment_id' => 'Updated_AP_' . time(),
            'name' => 'Updated Treatment Name_' . time(),
            'type' => 'Updated Type_' . time(),
            'times' => 'Updated times - 4 times daily',
            'purpose' => 'Updated purpose_' . time(),
            'instruction' => 'Updated instruction for treatment ' . time(),
            'repeat_days' => '14',
            'repeat_time' => '4'
        ];

        foreach ($newData as $key => $value) {
            $treatment->set($key, $value);
        }

        // Thực hiện update
        $result = $treatment->update();

        // Kiểm tra kết quả update
        $updateSuccess = $result !== false;

        $this->logResult($updateSuccess,
            "Update result: " . ($updateSuccess ? "Success" : "Failed"));

        // Lấy lại phương pháp điều trị từ database để kiểm tra dữ liệu đã được cập nhật chưa
        $updatedTreatment = new TreatmentModel($treatmentId);

        // Kiểm tra dữ liệu cập nhật
        $appointmentIdUpdated = $updatedTreatment->get("appointment_id") === $newData['appointment_id'];
        $nameUpdated = $updatedTreatment->get("name") === $newData['name'];
        $typeUpdated = $updatedTreatment->get("type") === $newData['type'];
        $timesUpdated = $updatedTreatment->get("times") === $newData['times'];
        $purposeUpdated = $updatedTreatment->get("purpose") === $newData['purpose'];
        $instructionUpdated = $updatedTreatment->get("instruction") === $newData['instruction'];
        $repeatDaysUpdated = $updatedTreatment->get("repeat_days") === $newData['repeat_days'];
        $repeatTimeUpdated = $updatedTreatment->get("repeat_time") === $newData['repeat_time'];

        $allUpdated = $appointmentIdUpdated && $nameUpdated && $typeUpdated && $timesUpdated &&
                      $purposeUpdated && $instructionUpdated && $repeatDaysUpdated && $repeatTimeUpdated;

        $this->logResult($allUpdated,
            "Data updated in DB: " . ($allUpdated ? "Yes" : "No") .
            " (Appointment ID: " . $updatedTreatment->get("appointment_id") .
            ", Name: " . $updatedTreatment->get("name") .
            ", Type: " . $updatedTreatment->get("type") .
            ", Times: " . $updatedTreatment->get("times") .
            ", Purpose: " . $updatedTreatment->get("purpose") .
            ", Instruction: " . $updatedTreatment->get("instruction") .
            ", Repeat days: " . $updatedTreatment->get("repeat_days") .
            ", Repeat time: " . $updatedTreatment->get("repeat_time") . ")");

        $this->assertTrue($updateSuccess);
        $this->assertTrue($allUpdated);
    }

    /**
     * Test case TREAT_DEL_07: Kiểm tra xóa phương pháp điều trị
     */
    public function testDelete()
    {
        $this->logSection("TREAT_DEL_07: Kiểm tra xóa phương pháp điều trị");
        $this->logStep("Xóa phương pháp điều trị đã tạo", "Phương pháp điều trị bị xóa, isAvailable = false");

        // Tạo dữ liệu test mới để xóa
        $treatmentData = $this->createTestTreatment([
            'name' => 'Treatment To Delete ' . time()
        ]);

        $tableName = TABLE_PREFIX.TABLE_TREATMENTS;

        $sql = "INSERT INTO `{$tableName}` (appointment_id, name, type, times, purpose, instruction, repeat_days, repeat_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $treatmentData['appointment_id'],
            $treatmentData['name'],
            $treatmentData['type'],
            $treatmentData['times'],
            $treatmentData['purpose'],
            $treatmentData['instruction'],
            $treatmentData['repeat_days'],
            $treatmentData['repeat_time']
        ]);

        $id = $this->pdo->lastInsertId();

        // Chọn phương pháp điều trị để xóa
        $treatment = new TreatmentModel($id);

        // Thực hiện xóa
        $deleteResult = $treatment->delete();

        // Kiểm tra kết quả xóa
        $this->logResult($deleteResult,
            "Delete successful: " . ($deleteResult ? "Yes" : "No"));

        // Kiểm tra phương pháp điều trị không còn tồn tại
        $deletedTreatment = new TreatmentModel($id);
        $notAvailable = !$deletedTreatment->isAvailable();

        $this->logResult($notAvailable,
            "Record deleted from DB: " . ($notAvailable ? "Yes" : "No"));

        $this->assertTrue($deleteResult);
        $this->assertFalse($deletedTreatment->isAvailable());

        // Kiểm tra trong database
        $record = $this->getRecord($tableName, ['id' => $id]);
        $this->logResult(!$record, "Record physically deleted: " . (!$record ? "Yes" : "No"));
        $this->assertFalse($record);
    }

    /**
     * Test case TREAT_ERR_ID_08: Kiểm tra select với ID không tồn tại
     */
    public function testSelectWithNonExistingId()
    {
        $this->logSection("TREAT_ERR_ID_08: Kiểm tra select với ID không tồn tại");
        $this->logStep("Tìm phương pháp điều trị với ID không tồn tại", "Model không khả dụng (isAvailable = false)");

        // Tạo ID chắc chắn không tồn tại
        $nonExistingId = 999999;

        // Thử select với ID không tồn tại
        $treatment = new TreatmentModel($nonExistingId);

        // Kiểm tra kết quả
        $notAvailable = !$treatment->isAvailable();

        $this->logResult($notAvailable,
            "Select with non-existing ID: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));

        $this->assertFalse($treatment->isAvailable());
    }

    /**
     * Test case TREAT_ERR_NAME_09: Kiểm tra select với name không tồn tại
     */
    public function testSelectWithNonExistingName()
    {
        $this->logSection("TREAT_ERR_NAME_09: Kiểm tra select với name không tồn tại");
        $this->logStep("Tìm phương pháp điều trị với name không tồn tại", "Model không khả dụng (isAvailable = false)");

        // Tạo name chắc chắn không tồn tại
        $nonExistingName = "NonExistingTreatment_" . time();

        // Thử select với name không tồn tại
        $treatment = new TreatmentModel($nonExistingName);

        // Kiểm tra kết quả
        $notAvailable = !$treatment->isAvailable();

        $this->logResult($notAvailable,
            "Select with non-existing name: " . ($notAvailable ? "Not available (correct)" : "Available (incorrect)"));

        $this->assertFalse($treatment->isAvailable());
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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ TREATMENTMODEL\n");
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