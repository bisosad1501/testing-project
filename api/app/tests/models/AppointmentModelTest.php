<?php
/**
 * Lớp kiểm thử AppointmentModel
 * 
 * File: api/app/tests/models/AppointmentModelTest.php
 * Class: AppointmentModelTest
 * 
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp AppointmentModel, bao gồm:
 * - Các thao tác CRUD (Tạo, Đọc, Cập nhật, Xóa)
 * - Kiểm tra các phương thức khởi tạo và truy vấn
 * - Xác nhận tính toàn vẹn dữ liệu khi thực hiện các thao tác
 * 
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class AppointmentModelTest extends DatabaseTestCase 
{
    /**
     * @var AppointmentModel Đối tượng model lịch hẹn dùng trong test
     */
    protected $appointmentModel;
    
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
     * @var int ID của lịch hẹn được tạo để sử dụng chung cho các test
     */
    protected static $testAppointmentId;

    /**
     * @var array Dữ liệu lịch hẹn mẫu được tạo
     */
    protected static $testAppointmentData;
    
    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo AppointmentModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/AppointmentModel.php';
        $this->appointmentModel = new AppointmentModel();
        
        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }
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
     * Tạo dữ liệu lịch hẹn mẫu cho test
     * 
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu lịch hẹn mẫu
     */
    private function createTestAppointment($override = [])
    {
        return array_merge([
            'patient_id' => 1,
            'booking_id' => rand(1000, 9999),
            'doctor_id' => 1,
            'patient_name' => 'Bệnh nhân Test',
            'patient_birthday' => '1990-01-01',
            'patient_reason' => 'Kiểm tra sức khỏe định kỳ',
            'patient_phone' => '098' . rand(1000000, 9999999),
            'numerical_order' => rand(1, 100),
            'position' => rand(1, 10),
            'appointment_time' => '09:00',
            'date' => date('Y-m-d'),
            'status' => 'đã xác nhận',
            'create_at' => date('Y-m-d H:i:s'),
            'update_at' => date('Y-m-d H:i:s')
        ], $override);
    }
    
    /**
     * Thực thi SQL trực tiếp và trả về kết quả
     * 
     * @param string $sql Câu lệnh SQL
     * @return array Kết quả truy vấn
     */
    private function executeSQL($sql)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Test constructor và phương thức select
     * 
     * Mã test case: APPT_CONS_01
     * Mục tiêu: Kiểm tra khởi tạo và chọn bản ghi từ DB
     * Input: ID lịch hẹn hợp lệ và không hợp lệ
     * Expected output: Khởi tạo thành công và chọn đúng bản ghi
     */
    public function testConstructAndSelect()
    {
        $this->logSection("APPT_CONS_01: Kiểm tra constructor và phương thức select");
        
        // Tạo dữ liệu mẫu và thêm vào DB
        $data = $this->createTestAppointment();
        $appointmentId = $this->insertFixture(TABLE_PREFIX.TABLE_APPOINTMENTS, $data);
        
        // Kiểm tra constructor với ID hợp lệ
        $this->logStep("Kiểm tra constructor với ID hợp lệ", "Model khởi tạo và select bản ghi thành công");
        $appointment = new AppointmentModel($appointmentId);
        $selectSuccess = $appointment->isAvailable();
        $this->logResult($selectSuccess, "Khởi tạo với ID {$appointmentId}: " . ($selectSuccess ? "Thành công" : "Thất bại"));
        $this->assertTrue($selectSuccess, "Lỗi khởi tạo AppointmentModel với ID hợp lệ");
        
        // Kiểm tra dữ liệu được load
        $this->logStep("Kiểm tra dữ liệu được load chính xác", "Dữ liệu trùng khớp với dữ liệu trong DB");
        $dataMatches = ($appointment->get("patient_name") === $data["patient_name"]);
        $this->logResult($dataMatches, "Dữ liệu load: " . ($dataMatches ? "Chính xác" : "Không chính xác"));
        $this->assertTrue($dataMatches, "Dữ liệu load không khớp với dữ liệu ban đầu");
        
        // Kiểm tra select với ID không tồn tại
        $this->logStep("Kiểm tra select với ID không tồn tại", "Model không available");
        $invalidId = 999999;
        $appointment->select($invalidId);
        $unavailable = !$appointment->isAvailable();
        $this->logResult($unavailable, "Select ID không tồn tại {$invalidId}: " . ($unavailable ? "Đúng" : "Sai"));
        $this->assertTrue($unavailable, "Lỗi khi select ID không tồn tại");
    }

    /**
     * Test phương thức extendDefaults
     * 
     * Mã test case: APPT_DEF_02
     * Mục tiêu: Kiểm tra thiết lập giá trị mặc định
     * Input: Model mới không có dữ liệu
     * Expected output: Các giá trị mặc định được thiết lập đúng
     */
    public function testExtendDefaults()
    {
        $this->logSection("APPT_DEF_02: Kiểm tra phương thức extendDefaults");
        
        $this->logStep("Kiểm tra các giá trị mặc định", "Các trường có giá trị mặc định đúng");
        
        // Tạo model mới và gọi extendDefaults
        $appointment = new AppointmentModel();
        $reflectionMethod = new ReflectionMethod('AppointmentModel', 'extendDefaults');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($appointment);
        
        // Kiểm tra các giá trị mặc định
        $defaultFields = [
            "patient_id", "booking_id", "doctor_id", "patient_name", "patient_birthday",
            "patient_reason", "patient_phone", "numerical_order", "position",
            "appointment_time", "date", "status", "create_at", "update_at"
        ];
        
        $allFieldsHaveDefaults = true;
        $missingDefaultFields = [];
        
        foreach ($defaultFields as $field) {
            $value = $appointment->get($field);
            if ($value === null) {
                $allFieldsHaveDefaults = false;
                $missingDefaultFields[] = $field;
            }
        }
        
        $this->logResult($allFieldsHaveDefaults, 
            "Tất cả trường có giá trị mặc định: " . ($allFieldsHaveDefaults ? "Đúng" : "Sai, thiếu: " . implode(", ", $missingDefaultFields)));
        $this->assertTrue($allFieldsHaveDefaults, "Một số trường không có giá trị mặc định");
    }

    /**
     * Test quy trình CRUD đầy đủ cho AppointmentModel
     * 
     * Mã test case: APPT_INS_03, APPT_READ_04, APPT_UPD_05, APPT_DEL_06
     * Mục tiêu: Kiểm tra tất cả các thao tác CRUD
     * Input: Dữ liệu lịch hẹn mẫu
     * Expected output: Các thao tác CRUD thành công
     * Ghi chú: Kiểm tra DB sau mỗi thao tác để xác nhận tính nhất quán
     */
    public function testCRUD()
    {
        $this->logSection("APPT: Kiểm tra quy trình CRUD");
        
        // ID và dữ liệu lịch hẹn để sử dụng xuyên suốt các bước test
        $appointmentId = null;
        $appointmentData = null;
        
        try {
            // BƯỚC 1: CREATE - APPT_INS_03
            $this->logStep("APPT_INS_03: Tạo mới lịch hẹn", "Lịch hẹn được tạo thành công với ID > 0");
            
            // Tạo dữ liệu kiểm thử
            $data = $this->createTestAppointment();
            $appointmentData = $data;
            
            // Set dữ liệu vào model
            foreach ($data as $field => $value) {
                $this->appointmentModel->set($field, $value);
            }
            
            // Thực hiện insert và kiểm tra
            $appointmentId = $this->appointmentModel->insert();
            $createSuccess = $appointmentId > 0;
            
            $this->logResult($createSuccess, "Insert lịch hẹn: " . ($createSuccess ? "Thành công, ID: {$appointmentId}" : "Thất bại"));
            $this->assertTrue($createSuccess, "Lỗi khi tạo mới lịch hẹn");
            
            // Kiểm tra dữ liệu trong DB
            $this->assertRecordExists(TABLE_PREFIX.TABLE_APPOINTMENTS, ["id" => $appointmentId]);
            
            // BƯỚC 2: READ - APPT_READ_04
            $this->logStep("APPT_READ_04: Đọc thông tin lịch hẹn", "Lịch hẹn được đọc thành công và dữ liệu khớp");
            
            // Tạo model mới để đọc dữ liệu
            $readModel = new AppointmentModel($appointmentId);
            $readSuccess = $readModel->isAvailable();
            
            // Kiểm tra dữ liệu đọc được
            $dataMatches = true;
            $mismatchedFields = [];
            
            foreach ($data as $field => $value) {
                if ($readModel->get($field) != $value && $field != 'id') {
                    $dataMatches = false;
                    $mismatchedFields[] = $field;
                }
            }
            
            $this->logResult($readSuccess && $dataMatches, 
                "Đọc lịch hẹn: " . ($readSuccess ? "Thành công" : "Thất bại") . 
                ", Dữ liệu khớp: " . ($dataMatches ? "Đúng" : "Sai, trường không khớp: " . implode(", ", $mismatchedFields)));
            
            $this->assertTrue($readSuccess, "Lỗi khi đọc lịch hẹn");
            $this->assertTrue($dataMatches, "Dữ liệu đọc không khớp với dữ liệu ban đầu");
            
            // BƯỚC 3: UPDATE - APPT_UPD_05
            $this->logStep("APPT_UPD_05: Cập nhật lịch hẹn", "Lịch hẹn được cập nhật thành công và dữ liệu được lưu trong DB");
            
            // Tạo dữ liệu cập nhật
            $updatedPosition = 5;
            $updatedTime = "14:30";
            $readModel->set("position", $updatedPosition);
            $readModel->set("appointment_time", $updatedTime);
            
            // Thực hiện update
            $updateResult = $readModel->update();
            $updateSuccess = $updateResult !== false;
            
            $this->logResult($updateSuccess, "Cập nhật lịch hẹn: " . ($updateSuccess ? "Thành công" : "Thất bại"));
            $this->assertTrue($updateSuccess, "Lỗi khi cập nhật lịch hẹn");
            
            // Kiểm tra dữ liệu trong DB
            $dbRecord = $this->getRecord(TABLE_PREFIX.TABLE_APPOINTMENTS, ["id" => $appointmentId]);
            $dbUpdateSuccess = ($dbRecord["position"] === $updatedPosition && $dbRecord["appointment_time"] === $updatedTime);
            
            $this->logResult($dbUpdateSuccess, 
                "Kiểm tra DB sau update: " . ($dbUpdateSuccess ? "Thành công" : "Thất bại") . 
                ", position: {$dbRecord['position']}, time: {$dbRecord['appointment_time']}");
            
            $this->assertTrue($dbUpdateSuccess, "Dữ liệu trong DB không được cập nhật đúng");
            
            // BƯỚC 4: DELETE - APPT_DEL_06
            $this->logStep("APPT_DEL_06: Xóa lịch hẹn", "Lịch hẹn được xóa thành công khỏi DB");
            
            // Thực hiện delete
            $deleteSuccess = $readModel->delete();
            
            $this->logResult($deleteSuccess, "Xóa lịch hẹn: " . ($deleteSuccess ? "Thành công" : "Thất bại"));
            $this->assertTrue($deleteSuccess, "Lỗi khi xóa lịch hẹn");
            
            // Kiểm tra trong DB
            $this->assertRecordNotExists(TABLE_PREFIX.TABLE_APPOINTMENTS, ["id" => $appointmentId]);
            
            // Kiểm tra trạng thái model
            $modelUnavailable = !$readModel->isAvailable();
            $this->logResult($modelUnavailable, 
                "Trạng thái model sau khi xóa: " . ($modelUnavailable ? "Không khả dụng (đúng)" : "Vẫn khả dụng (sai)"));
            $this->assertTrue($modelUnavailable, "Model vẫn khả dụng sau khi xóa");
            
        } catch (Exception $e) {
            $this->fail("Lỗi trong quá trình test CRUD: " . $e->getMessage());
        }
    }

    /**
     * Test hàm delete khi ID không tồn tại
     * 
     * Mã test case: APPT_ERR_07
     * Mục tiêu: Kiểm tra xử lý lỗi khi xóa bản ghi không tồn tại
     * Input: Model không tồn tại
     * Expected output: Phương thức delete trả về false
     */
    public function testDeleteNonExistent()
    {
        $this->logSection("APPT_ERR_07: Kiểm tra xóa lịch hẹn không tồn tại");
        $this->logStep("Kiểm tra xóa khi ID không tồn tại", "Phương thức delete trả về false");
        
        // Tạo model không khả dụng
        $appointment = new AppointmentModel();
        
        // Kiểm tra delete
        $deleteResult = $appointment->delete();
        $this->logResult($deleteResult === false, 
            "Kết quả delete: " . ($deleteResult === false ? "false (đúng)" : "không phải false (sai)"));
        $this->assertFalse($deleteResult, "Phương thức delete không trả về false khi model không khả dụng");
    }

    /**
     * Test giao diện fluent (method chaining)
     * 
     * Mã test case: APPT_CHAIN_08
     * Mục tiêu: Kiểm tra tính năng method chaining
     * Input: Gọi các phương thức theo chuỗi
     * Expected output: Các phương thức trả về đối tượng this
     */
    public function testMethodChaining()
    {
        $this->logSection("APPT_CHAIN_08: Kiểm tra giao diện fluent (method chaining)");
        $this->logStep("Kiểm tra các phương thức trả về đối tượng model", "Các phương thức select/update trả về đối tượng model");
        
        // Tạo dữ liệu mẫu
        $data = $this->createTestAppointment();
        $appointmentId = $this->insertFixture(TABLE_PREFIX.TABLE_APPOINTMENTS, $data);
        
        // Kiểm tra method chaining
        $appointment = new AppointmentModel();
        $result = $appointment->select($appointmentId);
        
        $this->logResult($result instanceof AppointmentModel, 
            "select() trả về: " . ($result instanceof AppointmentModel ? "AppointmentModel (đúng)" : "không phải AppointmentModel (sai)"));
        $this->assertInstanceOf(AppointmentModel::class, $result, "Phương thức select không trả về đối tượng model");
        
        // Kiểm tra update
        $updateResult = $appointment->set("position", 5)->update();
        
        $this->logResult($updateResult instanceof AppointmentModel, 
            "update() trả về: " . ($updateResult instanceof AppointmentModel ? "AppointmentModel (đúng)" : "không phải AppointmentModel (sai)"));
        $this->assertInstanceOf(AppointmentModel::class, $updateResult, "Phương thức update không trả về đối tượng model");
    }

    /**
     * In tổng kết kết quả test trong tearDown
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        // In tổng kết sau khi chạy tất cả test
        if ($this->getName() === 'testMethodChaining') {
            $this->printFinalSummary();
        }
    }

    /**
     * In tổng kết các test case đã chạy
     */
    private function printFinalSummary()
    {
        $totalTests = count(self::$allTestResults);
        $successTests = count(array_filter(self::$allTestResults, function($result) {
            return $result['success'];
        }));
        $failedTests = $totalTests - $successTests;
        
        $executionTime = round(microtime(true) - self::$startTime, 2);
        
        fwrite(STDOUT, "\n" . str_repeat("=", 50) . "\n");
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ AppointmentModel\n");
        fwrite(STDOUT, str_repeat("=", 50) . "\n");
        fwrite(STDOUT, "✅ Tổng số test thành công: {$successTests}\n");
        fwrite(STDOUT, "❌ Tổng số test thất bại: {$failedTests}\n");
        fwrite(STDOUT, "⏱️ Thời gian thực thi: {$executionTime}s\n");
        fwrite(STDOUT, str_repeat("=", 50) . "\n");
        
        // Liệt kê các test thất bại nếu có
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
                    fwrite(STDOUT, str_repeat("-", 50) . "\n");
                }
            }
        }
    }
}
