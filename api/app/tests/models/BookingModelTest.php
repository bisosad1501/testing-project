<?php
/**
 * Lớp kiểm thử BookingModel
 * 
 * File: api/app/tests/models/BookingModelTest.php
 * Class: BookingModelTest
 * 
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp BookingModel, bao gồm:
 * - Các thao tác CRUD (Tạo, Đọc, Cập nhật, Xóa)
 * - Kiểm tra các phương thức đọc thông tin theo ID
 * - Kiểm tra tính nhất quán của dữ liệu trong DB
 * 
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class BookingModelTest extends DatabaseTestCase 
{
    /**
     * @var BookingModel Đối tượng model đặt lịch dùng trong test
     */
    protected $bookingModel;
    
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
     * @var string Tên người dùng hiện tại
     */
    const CURRENT_USER = 'bisosad1501';
    
    /**
     * @var int ID của đặt lịch được tạo để sử dụng chung cho các test
     */
    protected static $testBookingId;

    /**
     * @var array Dữ liệu đặt lịch mẫu được tạo
     */
    protected static $testBookingData;
    
    /**
     * @var bool Kiểm soát việc bắt đầu/kết thúc transaction
     */
    protected $useTransaction = true;

    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo BookingModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/BookingModel.php';
        $this->bookingModel = new BookingModel();
        
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
     * Tạo dữ liệu đặt lịch mẫu cho test
     * 
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu đặt lịch mẫu
     */
    private function createTestBooking($override = [])
    {
        $currentDate = date('Y-m-d');
        $currentDatetime = date('Y-m-d H:i:s');

        return array_merge([
            'doctor_id' => 1, // ID mặc định
            'patient_id' => 1, // ID mặc định
            'service_id' => 1, // ID mặc định
            'booking_name' => 'Test Booking ' . substr(time(), -5),
            'booking_phone' => '098' . rand(1000000, 9999999),
            'name' => 'Test Patient ' . substr(time(), -5),
            'gender' => rand(0, 1),
            'birthday' => '1990-01-01',
            'address' => 'Test Address ' . rand(100, 999),
            'reason' => 'Test Reason ' . rand(100, 999),
            'appointment_date' => $currentDate,
            'appointment_time' => '10:00',
            'status' => 'pending',
            'create_at' => $currentDatetime,
            'update_at' => $currentDatetime
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
     * Test đầy đủ CRUD cho BookingModel
     * 
     * Mã test case: BOOK_INS_01, BOOK_READ_02, BOOK_UPD_03, BOOK_DEL_04
     * Mục tiêu: Kiểm tra cả quy trình CRUD trong một test
     * Input: Dữ liệu đặt lịch mẫu
     * Expected output: Thao tác CRUD thành công
     * Ghi chú: Thực hiện kiểm tra DB sau mỗi thao tác để xác nhận dữ liệu nhất quán
     */
    public function testCRUD()
    {
        $this->logSection("BOOK: Kiểm tra quy trình CRUD");
        
        // ID và dữ liệu của đặt lịch để sử dụng qua các bước
        $bookingId = null;
        $bookingData = null;
        
        try {
            // BƯỚC 1: CREATE - TC-BM-01
            $this->logStep("BOOK_INS_01: Tạo mới đặt lịch", "Đặt lịch được tạo thành công với ID > 0");
            
            // Tạo dữ liệu kiểm thử
            $data = $this->createTestBooking();
            $bookingData = $data;
            
            // Set dữ liệu vào model
            foreach ($data as $field => $value) {
                $this->bookingModel->set($field, $value);
            }
            
            // Thực hiện insert và kiểm tra
            $bookingId = $this->bookingModel->insert();
            $createSuccess = $bookingId > 0;
            
            $this->logResult($createSuccess, 
                "Booking ID: " . ($createSuccess ? $bookingId : "Không tạo được"),
                $createSuccess ? null : "Không thể tạo đặt lịch mới");
            
            $this->assertTrue($createSuccess, "Không thể tạo đặt lịch mới");
            
            // Kiểm tra dữ liệu trong DB
            $this->assertRecordExists(TABLE_PREFIX.TABLE_BOOKINGS, ['id' => $bookingId]);
            
            // Lưu lại ID để sử dụng trong các test sau
            self::$testBookingId = $bookingId;
            self::$testBookingData = $data;
            
            // BƯỚC 2: READ - TC-BM-02
            $this->logStep("BOOK_READ_02: Đọc thông tin đặt lịch theo ID", 
                "Đặt lịch được tìm thấy và có dữ liệu đúng");
            
            // Tạo model mới và select theo ID
            $readModel = new BookingModel($bookingId);
            $readSuccess = $readModel->isAvailable();
            
            // Kiểm tra thông tin đọc về
            $dataMatches = true;
            $mismatchFields = [];
            
            foreach ($data as $field => $value) {
                if ($readModel->get($field) != $value) {
                    $dataMatches = false;
                    $mismatchFields[] = $field;
                }
            }
            
            $readResult = "ID: {$bookingId} - Tìm thấy: " . ($readSuccess ? "Có" : "Không");
            $readResult .= $dataMatches ? ", Dữ liệu khớp" : ", Dữ liệu không khớp ở các trường: " . implode(", ", $mismatchFields);
            
            $this->logResult($readSuccess && $dataMatches, $readResult);
            
            $this->assertTrue($readSuccess, "Không thể đọc thông tin đặt lịch với ID {$bookingId}");
            $this->assertTrue($dataMatches, "Dữ liệu không khớp");
            
            // BƯỚC 3: UPDATE - TC-BM-03
            $this->logStep("BOOK_UPD_03: Cập nhật thông tin đặt lịch", 
                "Đặt lịch được cập nhật thành công");
            
            // Cập nhật dữ liệu
            $updateData = [
                'booking_name' => 'Updated Booking ' . substr(time(), -5),
                'status' => 'confirmed',
                'appointment_time' => '11:30',
                'update_at' => date('Y-m-d H:i:s')
            ];
            
            // Áp dụng dữ liệu mới vào model
            foreach ($updateData as $field => $value) {
                $readModel->set($field, $value);
            }
            
            // Thực hiện update
            $readModel->update();
            
            // Kiểm tra dữ liệu sau khi update
            $updatedModel = new BookingModel($bookingId);
            $updateSuccess = $updatedModel->isAvailable();
            
            // Kiểm tra dữ liệu đã được cập nhật
            $updateMatches = true;
            $updateMismatchFields = [];
            
            foreach ($updateData as $field => $value) {
                if ($updatedModel->get($field) != $value) {
                    $updateMatches = false;
                    $updateMismatchFields[] = $field;
                }
            }
            
            $updateResult = "Cập nhật " . ($updateSuccess ? "thành công" : "thất bại");
            $updateResult .= $updateMatches ? ", Dữ liệu khớp" : ", Dữ liệu không khớp ở các trường: " . implode(", ", $updateMismatchFields);
            
            $this->logResult($updateSuccess && $updateMatches, $updateResult);
            
            $this->assertTrue($updateSuccess, "Không thể cập nhật thông tin đặt lịch");
            $this->assertTrue($updateMatches, "Dữ liệu sau khi cập nhật không khớp");
            
            // BƯỚC 4: DELETE - TC-BM-04
            $this->logStep("BOOK_DEL_04: Xóa đặt lịch", "Đặt lịch được xóa thành công");
            
            // Thực hiện xóa
            $deleteSuccess = $updatedModel->delete();
            
            // Kiểm tra đặt lịch đã bị xóa
            $deletedModel = new BookingModel($bookingId);
            $deleteVerify = !$deletedModel->isAvailable();
            
            // Kiểm tra dữ liệu trong DB
            $recordExists = false;
            try {
                $this->assertRecordNotExists(TABLE_PREFIX.TABLE_BOOKINGS, ['id' => $bookingId]);
                $recordExists = false;
            } catch (ExpectationFailedException $e) {
                $recordExists = true;
            }
            
            $deleteResult = "Xóa " . ($deleteSuccess ? "thành công" : "thất bại");
            $deleteResult .= ", Kiểm tra tồn tại: " . ($deleteVerify ? "Đã xóa" : "Vẫn tồn tại");
            $deleteResult .= ", Kiểm tra DB: " . ($recordExists ? "Vẫn tồn tại trong DB" : "Đã xóa khỏi DB");
            
            $this->logResult($deleteSuccess && $deleteVerify && !$recordExists, $deleteResult);
            
            $this->assertTrue($deleteSuccess, "Không thể xóa đặt lịch");
            $this->assertTrue($deleteVerify, "Đặt lịch vẫn tồn tại sau khi xóa");
            $this->assertRecordNotExists(TABLE_PREFIX.TABLE_BOOKINGS, ['id' => $bookingId], "Đặt lịch vẫn tồn tại trong DB");
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test CRUD thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test select với ID không tồn tại
     * 
     * Mã test case: BOOK_ERR_ID_05
     * Mục tiêu: Kiểm tra trường hợp tìm kiếm với ID không tồn tại
     * Input: ID không tồn tại
     * Expected output: Model không khả dụng (isAvailable() = false)
     */
    public function testSelectWithNonExistentId()
    {
        $this->logSection("BOOK_ERR_ID_05: Kiểm tra select với ID không tồn tại");
        
        try {
            $this->logStep("Tìm kiếm đặt lịch với ID không tồn tại", 
                "Đặt lịch không được tìm thấy");
            
            // Tạo ID không tồn tại bằng cách lấy max ID hiện tại + 1000
            $sql = "SELECT MAX(id) as max_id FROM " . TABLE_PREFIX . TABLE_BOOKINGS;
            $result = $this->executeSQL($sql);
            $nonExistentId = $result[0]['max_id'] + 1000;
            
            // Thực hiện tìm kiếm
            $model = new BookingModel($nonExistentId);
            $isAvailable = $model->isAvailable();
            
            $selectResult = "ID không tồn tại: {$nonExistentId}, Kết quả: " . 
                ($isAvailable ? "Tìm thấy (không đúng)" : "Không tìm thấy (đúng)");
            
            $this->logResult(!$isAvailable, $selectResult);
            $this->assertFalse($isAvailable, "Model vẫn khả dụng khi ID không tồn tại");
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test select với ID không tồn tại thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test chức năng extendDefaults
     * 
     * Mã test case: BOOK_DEF_06
     * Mục tiêu: Kiểm tra phương thức extendDefaults thiết lập giá trị mặc định đúng
     * Input: Model không có dữ liệu
     * Expected output: Các trường được thiết lập giá trị mặc định
     */
    public function testExtendDefaults()
    {
        $this->logSection("BOOK_DEF_06: Kiểm tra phương thức extendDefaults");
        
        try {
            $this->logStep("Kiểm tra giá trị mặc định sau khi gọi extendDefaults", 
                "Các trường được thiết lập đúng giá trị mặc định");
            
            // Tạo model mới không có dữ liệu
            $model = new BookingModel();
            $this->assertFalse($model->isAvailable(), "Model mới không nên khả dụng");
            
            // Gọi extendDefaults
            $model->extendDefaults();
            
            // Kiểm tra các giá trị mặc định
            $expectedDefaults = [
                'doctor_id' => '',
                'patient_id' => '',
                'service_id' => '',
                'booking_name' => '',
                'booking_phone' => '',
                'name' => '',
                'gender' => '',
                'birthday' => '',
                'address' => '',
                'reason' => '',
                'appointment_date' => '',
                'appointment_time' => '',
                'status' => '',
                'create_at' => '',
                'update_at' => ''
            ];
            
            $defaultsMatch = true;
            $mismatchFields = [];
            
            foreach ($expectedDefaults as $field => $expectedValue) {
                $actualValue = $model->get($field);
                if ($actualValue !== $expectedValue) {
                    $defaultsMatch = false;
                    $mismatchFields[] = "{$field} (mong đợi: '{$expectedValue}', thực tế: '{$actualValue}')";
                }
            }
            
            $result = $defaultsMatch 
                ? "Tất cả giá trị mặc định đều đúng" 
                : "Các giá trị mặc định không đúng: " . implode(", ", $mismatchFields);
            
            $this->logResult($defaultsMatch, $result);
            $this->assertTrue($defaultsMatch, "Giá trị mặc định không đúng");
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test extendDefaults thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test update khi đặt lịch không tồn tại
     * 
     * Mã test case: BOOK_ERR_UPD_07
     * Mục tiêu: Kiểm tra kết quả khi cập nhật đặt lịch không tồn tại
     * Input: Model đặt lịch không tồn tại
     * Expected output: Hàm update trả về false
     */
    public function testUpdateNonExistentBooking()
    {
        $this->logSection("BOOK_ERR_UPD_07: Kiểm tra update đặt lịch không tồn tại");
        
        try {
            $this->logStep("Cập nhật thông tin đặt lịch không tồn tại", 
                "Hàm update trả về false");
            
            // Tạo model không khả dụng
            $model = new BookingModel();
            $this->assertFalse($model->isAvailable(), "Model mới không nên khả dụng");
            
            // Thiết lập dữ liệu
            $updateData = $this->createTestBooking();
            foreach ($updateData as $field => $value) {
                $model->set($field, $value);
            }
            
            // Thực hiện update
            $updateResult = $model->update();
            
            $result = "Update đặt lịch không tồn tại trả về: " . 
                ($updateResult === false ? "false (đúng)" : "không phải false (không đúng)");
            
            $this->logResult($updateResult === false, $result);
            $this->assertFalse($updateResult, "Update đặt lịch không tồn tại phải trả về false");
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test update đặt lịch không tồn tại thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test delete khi đặt lịch không tồn tại
     * 
     * Mã test case: BOOK_ERR_DEL_08
     * Mục tiêu: Kiểm tra kết quả khi xóa đặt lịch không tồn tại
     * Input: Model đặt lịch không tồn tại
     * Expected output: Hàm delete trả về false
     */
    public function testDeleteNonExistentBooking()
    {
        $this->logSection("BOOK_ERR_DEL_08: Kiểm tra delete đặt lịch không tồn tại");
        
        try {
            $this->logStep("Xóa đặt lịch không tồn tại", 
                "Hàm delete trả về false");
            
            // Tạo model không khả dụng
            $model = new BookingModel();
            $this->assertFalse($model->isAvailable(), "Model mới không nên khả dụng");
            
            // Thực hiện delete
            $deleteResult = $model->delete();
            
            $result = "Delete đặt lịch không tồn tại trả về: " . 
                ($deleteResult === false ? "false (đúng)" : "không phải false (không đúng)");
            
            $this->logResult($deleteResult === false, $result);
            $this->assertFalse($deleteResult, "Delete đặt lịch không tồn tại phải trả về false");
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test delete đặt lịch không tồn tại thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test insert khi model đã có sẵn
     * 
     * Mã test case: BOOK_DUP_09
     * Mục tiêu: Kiểm tra kết quả khi insert trên model đã khả dụng
     * Input: Model đặt lịch đã khả dụng
     * Expected output: Hàm insert trả về false
     */
    public function testInsertExistingBooking()
    {
        $this->logSection("BOOK_DUP_09: Kiểm tra insert đặt lịch đã tồn tại");
        
        try {
            // Tạo đặt lịch mẫu
            $data = $this->createTestBooking();
            foreach ($data as $field => $value) {
                $this->bookingModel->set($field, $value);
            }
            
            $bookingId = $this->bookingModel->insert();
            $this->assertGreaterThan(0, $bookingId, "Không thể tạo đặt lịch ban đầu");
            
            $this->logStep("Thực hiện insert trên model đã khả dụng", 
                "Hàm insert trả về false");
            
            // Thực hiện insert lần thứ hai
            $secondInsertResult = $this->bookingModel->insert();
            
            $result = "Insert đặt lịch đã tồn tại trả về: " . 
                ($secondInsertResult === false ? "false (đúng)" : "không phải false (không đúng)");
            
            $this->logResult($secondInsertResult === false, $result);
            $this->assertFalse($secondInsertResult, "Insert đặt lịch đã tồn tại phải trả về false");
            
            // Dọn dẹp
            $this->bookingModel->delete();
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test insert đặt lịch đã tồn tại thất bại: " . $e->getMessage());
        }
    }

    /**
     * Dọn dẹp sau khi tất cả các test hoàn thành
     */
    protected function tearDown()
    {
        // Gọi tear down của cha để thực hiện rollback
        if ($this->useTransaction) {
            parent::tearDown();
        }
        
        // In kết quả tổng quan ở test cuối cùng
        $this->printFinalSummary();
    }
    
    /**
     * In kết quả tổng hợp toàn bộ test
     */
    private function printFinalSummary()
    {
        // Chỉ in tổng kết nếu đây là lần gọi tearDown cuối cùng
        $bt = debug_backtrace();
        $caller = isset($bt[2]['function']) ? $bt[2]['function'] : '';
        if ($caller != '__call') {
            return;
        }
        
        // Đếm số lượng test thành công và thất bại
        $total = count(self::$allTestResults);
        $success = 0;
        $failed = 0;
        
        foreach (self::$allTestResults as $result) {
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        // Tính thời gian thực thi
        $executionTime = microtime(true) - self::$startTime;
        
        // In tổng kết
        fwrite(STDOUT, "\n" . str_repeat("=", 70) . "\n");
        fwrite(STDOUT, "📊 KẾT QUẢ TEST BOOKINGMODEL \n");
        fwrite(STDOUT, str_repeat("=", 70) . "\n");
        fwrite(STDOUT, sprintf("✅ Thành công: %d/%d (%.2f%%)\n", 
            $success, $total, ($total > 0 ? ($success / $total * 100) : 0)));
        fwrite(STDOUT, sprintf("❌ Thất bại: %d/%d (%.2f%%)\n", 
            $failed, $total, ($total > 0 ? ($failed / $total * 100) : 0)));
        fwrite(STDOUT, sprintf("⏱ Thời gian thực thi: %.4f giây\n", $executionTime));
        fwrite(STDOUT, "👤 Người thực hiện: " . self::CURRENT_USER . "\n");
        fwrite(STDOUT, "📅 Thời gian: " . date('Y-m-d H:i:s') . "\n");
        fwrite(STDOUT, str_repeat("=", 70) . "\n");
        
        // Reset lại biến static để test tiếp theo sạch
        self::$allTestResults = [];
        self::$startTime = null;
    }
} 