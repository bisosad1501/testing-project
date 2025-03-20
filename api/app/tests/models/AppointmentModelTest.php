<?php
use PHPUnit\Framework\TestCase;

class AppointmentModelTest extends TestCase
{
    private $dbConnection;
    
    /**
     * Thiết lập trước mỗi test case
     */
    protected function setUp()
    {
        parent::setUp();
        
        // Tạo kết nối DB và bắt đầu transaction để có thể rollback sau khi test
        try {
            $this->dbConnection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_ENCODING,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Bắt đầu transaction
            $this->dbConnection->beginTransaction();
            
            echo "Database connection established successfully.\n";
        } catch (PDOException $e) {
            echo "Database connection failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Dọn dẹp sau mỗi test case
     */
    protected function tearDown()
    {
        // Rollback transaction để không ảnh hưởng đến dữ liệu thật
        if ($this->dbConnection && $this->dbConnection->inTransaction()) {
            $this->dbConnection->rollBack();
            echo "Database transaction rolled back.\n";
        }
        
        parent::tearDown();
    }
    
    /**
     * Test trường hợp tạo appointment mới
     */
    public function testCreateAppointment()
    {
        // Tạo dữ liệu test
        $currentDateTime = date('Y-m-d H:i:s');
        $testData = [
            'booking_id' => 123,
            'doctor_id' => 456,
            'patient_id' => 789,
            'patient_name' => 'Nguyen Van A',
            'patient_birthday' => '1990-01-01',
            'patient_reason' => 'Regular check-up',
            'patient_phone' => '0987654321',
            'numerical_order' => 1,
            'position' => 'Room A',
            'appointment_time' => '09:00:00',
            'date' => '2025-03-20',
            'status' => 'pending',
            'create_at' => $currentDateTime,
            'update_at' => $currentDateTime
        ];
        
        // Khởi tạo model và thêm dữ liệu
        $appointment = Controller::model('Appointment');
        
        // Thiết lập dữ liệu
        foreach ($testData as $key => $value) {
            $appointment->set($key, $value);
        }
        
        // Insert vào DB
        $insertId = $appointment->insert();
        
        // Kiểm tra xem ID được trả về không phải là false (insert thành công)
        $this->assertNotFalse($insertId, "Could not insert appointment");
        
        // Kiểm tra dữ liệu đã được lưu chính xác
        $savedAppointment = Controller::model('Appointment', $insertId);
        $this->assertTrue($savedAppointment->isAvailable(), "Could not find inserted appointment");
        
        // Kiểm tra từng trường dữ liệu
        foreach ($testData as $key => $value) {
            $this->assertEquals($value, $savedAppointment->get($key), "Field $key does not match");
        }
    }
    
    /**
     * Test cập nhật appointment
     */
    public function testUpdateAppointment()
    {
        // Tạo appointment mới
        $currentDateTime = date('Y-m-d H:i:s');
        $testData = [
            'booking_id' => 124,
            'doctor_id' => 456,
            'patient_id' => 789,
            'patient_name' => 'Nguyen Van B',
            'patient_birthday' => '1990-01-01',
            'patient_reason' => 'Flu symptoms',
            'patient_phone' => '0987654321',
            'numerical_order' => 2,
            'position' => 'Room B',
            'appointment_time' => '10:00:00',
            'date' => '2025-03-21',
            'status' => 'pending',
            'create_at' => $currentDateTime,
            'update_at' => $currentDateTime
        ];
        
        $appointment = Controller::model('Appointment');
        foreach ($testData as $key => $value) {
            $appointment->set($key, $value);
        }
        $insertId = $appointment->insert();
        
        // Cập nhật trạng thái
        $updateDateTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $appointment = Controller::model('Appointment', $insertId);
        $appointment->set('status', 'confirmed');
        $appointment->set('update_at', $updateDateTime);
        
        $result = $appointment->update();
        
        // Kiểm tra cập nhật thành công
        $this->assertNotFalse($result, "Failed to update appointment");
        
        // Kiểm tra dữ liệu đã được cập nhật
        $updatedAppointment = Controller::model('Appointment', $insertId);
        $this->assertEquals('confirmed', $updatedAppointment->get('status'), "Status was not updated");
        $this->assertEquals($updateDateTime, $updatedAppointment->get('update_at'), "Update timestamp was not updated");
    }
    
    /**
     * Test xóa appointment
     */
    public function testDeleteAppointment()
    {
        // Tạo appointment mới để test xóa
        $currentDateTime = date('Y-m-d H:i:s');
        $testData = [
            'booking_id' => 125,
            'doctor_id' => 456,
            'patient_id' => 789,
            'patient_name' => 'Nguyen Van C',
            'patient_birthday' => '1990-01-01',
            'patient_reason' => 'Delete test',
            'patient_phone' => '0987654321',
            'numerical_order' => 3,
            'position' => 'Room C',
            'appointment_time' => '11:00:00',
            'date' => '2025-03-22',
            'status' => 'pending',
            'create_at' => $currentDateTime,
            'update_at' => $currentDateTime
        ];
        
        $appointment = Controller::model('Appointment');
        foreach ($testData as $key => $value) {
            $appointment->set($key, $value);
        }
        $insertId = $appointment->insert();
        
        // Kiểm tra appointment tồn tại trước khi xóa
        $checkAppointment = Controller::model('Appointment', $insertId);
        $this->assertTrue($checkAppointment->isAvailable(), "Appointment should exist before deletion");
        
        // Xóa appointment
        $result = $checkAppointment->delete();
        
        // Kiểm tra xóa thành công
        $this->assertTrue($result, "Delete operation failed");
        
        // Kiểm tra appointment đã bị xóa
        $deletedAppointment = Controller::model('Appointment', $insertId);
        $this->assertFalse($deletedAppointment->isAvailable(), "Appointment should not exist after deletion");
    }
}