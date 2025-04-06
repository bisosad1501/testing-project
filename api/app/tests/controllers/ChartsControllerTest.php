<?php
/**
 * Unit tests cho ChartsController
 * 
 * File: api/app/tests/controllers/ChartsControllerTest.php
 * Class: ChartsControllerTest
 */
require_once __DIR__ . '/../ControllerTestCase.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

// Include Controller class
require_once __DIR__ . '/../../core/Controller.php';

// Định nghĩa hằng số PHPUNIT_TESTSUITE để ngăn Controller::jsonecho gọi exit()
if (!defined('PHPUNIT_TESTSUITE')) {
    define('PHPUNIT_TESTSUITE', true);
}

/**
 * Mock model cho AuthUser với phương thức get() có thể kiểm soát
 */
class MockAuthUser
{
    private $data;
    private $role;
    
    public function __construct($data, $role)
    {
        $this->data = $data;
        $this->role = $role;
    }
    
    public function get($key)
    {
        if ($key === 'role') {
            return $this->role;
        }
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}

class ChartsControllerTest extends ControllerTestCase
{
    /**
     * @var ChartsController Controller instance
     */
    protected $controller;
    
    /**
     * @var array Test data for fixtures
     */
    protected $testData;
    
    /**
     * Set up test environment before each test
     */
    protected function setUp()
    {
        parent::setUp();
        
        // Create controller
        $this->controller = $this->createController('ChartsController');
        
        // Reset các mock trước mỗi test
        InputMock::$methodMock = null;
        InputMock::$getMock = null;
        
        // Set up test data
        $this->testData = [
            'doctors' => [
                'admin' => [
                    'email' => 'admin_doctor@example.com',
                    'phone' => '0987654321',
                    'name' => 'Admin Doctor',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'role' => 'admin',
                    'active' => 1,
                    'speciality_id' => 1,
                    'room_id' => 1,
                    'description' => 'Test admin doctor',
                    'price' => 150000,
                    'avatar' => '',
                    'create_at' => date('Y-m-d H:i:s'),
                    'update_at' => date('Y-m-d H:i:s'),
                    'recovery_token' => ''
                ],
                'supporter' => [
                    'email' => 'supporter@example.com',
                    'phone' => '0987654322',
                    'name' => 'Supporter Doctor',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'role' => 'supporter',
                    'active' => 1,
                    'speciality_id' => 1,
                    'room_id' => 1,
                    'description' => 'Test supporter doctor',
                    'price' => 150000,
                    'avatar' => '',
                    'create_at' => date('Y-m-d H:i:s'),
                    'update_at' => date('Y-m-d H:i:s'),
                    'recovery_token' => ''
                ],
                'member' => [
                    'email' => 'member@example.com',
                    'phone' => '0987654323',
                    'name' => 'Member Doctor',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'role' => 'member',
                    'active' => 1,
                    'speciality_id' => 1,
                    'room_id' => 1,
                    'description' => 'Test member doctor',
                    'price' => 150000,
                    'avatar' => '',
                    'create_at' => date('Y-m-d H:i:s'),
                    'update_at' => date('Y-m-d H:i:s'),
                    'recovery_token' => ''
                ]
            ],
            'patients' => [
                'patient1' => [
                    'email' => 'patient1@example.com',
                    'phone' => '0987123456',
                    'name' => 'Test Patient',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'gender' => 1,
                    'birthday' => '1990-01-01',
                    'address' => 'Test Address',
                    'avatar' => '',
                    'create_at' => date('Y-m-d H:i:s'),
                    'update_at' => date('Y-m-d H:i:s')
                ]
            ]
        ];
        
        // Create fixtures for common test dependencies
        $this->createFixtures();
    }
    
    /**
     * Create database fixtures for testing
     */
    private function createFixtures()
    {
        try {
            // Insert specialities
            $specialityId = $this->insertFixture(TABLE_PREFIX.TABLE_SPECIALITIES, [
                'name' => 'Cardiology',
                'image' => 'cardiology.jpg', 
                'description' => 'Heart specialists'
            ]);
            
            // Insert rooms
            $roomId = $this->insertFixture(TABLE_PREFIX.TABLE_ROOMS, [
                'name' => 'Room 101',
                'location' => 'First Floor'
            ]);
            
            // Update references
            $this->testData['doctors']['admin']['speciality_id'] = $specialityId;
            $this->testData['doctors']['admin']['room_id'] = $roomId;
            $this->testData['doctors']['supporter']['speciality_id'] = $specialityId;
            $this->testData['doctors']['supporter']['room_id'] = $roomId;
            $this->testData['doctors']['member']['speciality_id'] = $specialityId;
            $this->testData['doctors']['member']['room_id'] = $roomId;
            
            // Insert doctors
            $adminDoctorId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $this->testData['doctors']['admin']);
            $supporterDoctorId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $this->testData['doctors']['supporter']);
            $memberDoctorId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $this->testData['doctors']['member']);
            
            // Update ID references
            $this->testData['doctors']['admin']['id'] = $adminDoctorId;
            $this->testData['doctors']['supporter']['id'] = $supporterDoctorId;
            $this->testData['doctors']['member']['id'] = $memberDoctorId;
            
            // Insert patients
            $patientId = $this->insertFixture(TABLE_PREFIX.TABLE_PATIENTS, $this->testData['patients']['patient1']);
            $this->testData['patients']['patient1']['id'] = $patientId;
            
            // Tạo booking cần thiết cho appointments
            $date = new \Moment\Moment("now", 'Asia/Ho_Chi_Minh');
            
            // Create some bookings and appointments for today and past days
            $this->createAppointmentsForDate($date->format('Y-m-d'), 3, $patientId, $adminDoctorId);
            
            // Create appointments for past 6 days (total 7 days including today)
            for ($i = 1; $i <= 6; $i++) {
                $pastDate = $date->cloning()->subtractDays($i);
                $this->createAppointmentsForDate($pastDate->format('Y-m-d'), $i % 3 + 1, $patientId, $adminDoctorId);
            }
            
        } catch (Exception $e) {
            $this->fail("Failed to create test fixtures: " . $e->getMessage());
        }
    }
    
    /**
     * Helper function to create appointments for a specific date
     */
    private function createAppointmentsForDate($date, $count, $patientId, $doctorId)
    {
        for ($i = 0; $i < $count; $i++) {
            $isBooking = ($i % 2 == 0); // Every other appointment is a booking
            $time = $isBooking ? sprintf("%02d:00", 8 + $i) : ""; // Bookings have time, appointments don't
            
            // First create a booking if it's a booking-type appointment
            $bookingId = 0;
            if ($isBooking) {
                $bookingId = $this->insertFixture(TABLE_PREFIX.TABLE_BOOKINGS, [
                    'service_id' => null,
                    'doctor_id' => $doctorId,
                    'patient_id' => $patientId,
                    'booking_name' => 'Test Booking ' . ($i + 1),
                    'booking_phone' => '0987123456',
                    'name' => 'Test Patient ' . ($i + 1),
                    'gender' => 1,
                    'birthday' => '1990-01-01',
                    'address' => 'Test Address',
                    'reason' => 'Test Reason ' . ($i + 1),
                    'appointment_date' => $date,
                    'appointment_time' => $time,
                    'status' => 'processing',
                    'create_at' => date('Y-m-d H:i:s'),
                    'update_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Then create the appointment
            $this->insertFixture(TABLE_PREFIX.TABLE_APPOINTMENTS, [
                'booking_id' => $bookingId,
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'patient_name' => 'Test Patient ' . ($i + 1),
                'patient_birthday' => '1990-01-01',
                'patient_reason' => 'Test Reason ' . ($i + 1),
                'patient_phone' => '0987123456',
                'numerical_order' => $i + 1,
                'position' => $i + 1,
                'appointment_time' => $time,
                'date' => $date,
                'status' => 'processing',
                'create_at' => date('Y-m-d H:i:s'),
                'update_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Mock người dùng đã xác thực
     * 
     * @param string $type User type (doctor or patient)
     * @param string $role Role for doctor (admin, supporter, member)
     */
    protected function mockAuthUser($type = 'doctor', $role = 'admin')
    {
        // Tạo đối tượng mock cho AuthUser
        if ($type === 'doctor') {
            $userData = $this->testData['doctors'][$role];
            $authUser = new MockAuthUser($userData, $role);
        } else {
            $userData = $this->testData['patients']['patient1'];
            $authUser = new MockAuthUser($userData, 'patient');
        }
        
        // Thiết lập trong biến variables của controller
        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        $variables = $property->getValue($this->controller);
        $variables['AuthUser'] = $authUser;
        $property->setValue($this->controller, $variables);
    }
    
    /**
     * Thiết lập các tham số request
     */
    protected function setupInput($method = 'GET', $requestType = 'appointmentsinlast7days')
    {
        // Set Input::method
        InputMock::$methodMock = function() use ($method) {
            return $method;
        };
        
        // Set Input::get
        InputMock::$getMock = function($key) use ($requestType) {
            if ($key === 'request') {
                return $requestType;
            }
            return null;
        };
    }
    
    /**
     * Lấy response từ controller sau khi process
     */
    protected function getControllerResponse()
    {
        // Sử dụng Reflection API để truy cập thuộc tính protected
        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = $property->getValue($this->controller);
        
        return (array)$resp;
    }
    
    /**
     * Gọi controller với output capture
     */
    protected function callControllerWithCapture()
    {
        // Bắt đầu output buffering để bắt bất kỳ output nào
        ob_start();
        
        try {
            $this->controller->process();
        } catch (Exception $e) {
            // Ghi log exception nếu cần
            // error_log("Exception in test: " . $e->getMessage());
        }
        
        // Xóa buffer và lấy response từ controller
        ob_end_clean();
        
        return $this->getControllerResponse();
    }
    
    /**
     * CTRL_CHARTS_AUTH_001
     * Kiểm tra khi người dùng chưa đăng nhập
     */
    public function testNoAuthentication()
    {
        // Đánh dấu test này là incomplete vì không thể test header redirects
        $this->markTestIncomplete(
          'This test cannot verify header redirects in PHPUnit CLI environment'
        );
    }
    
    /**
     * CTRL_CHARTS_PERM_002
     * Kiểm tra quyền - patient không được phép truy cập
     */
    public function testPermissionForPatient()
    {
        // Auth user là patient
        $this->mockAuthUser('patient');
        
        // Thiết lập request
        $this->setupInput('GET', 'appointmentsinlast7days');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when user is patient');
        $this->assertContains('permission', strtolower($response['msg']), 'Error message should indicate permission issue');
    }
    
    /**
     * CTRL_CHARTS_PERM_003
     * Kiểm tra quyền - admin doctor được phép truy cập
     */
    public function testPermissionForAdminDoctor()
    {
        // Auth user là admin doctor
        $this->mockAuthUser('doctor', 'admin');
        
        // Thiết lập request
        $this->setupInput('GET', 'appointmentsinlast7days');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(1, $response['result'], 'Result should be success when user is admin doctor');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');
    }
    
    /**
     * CTRL_CHARTS_PERM_004
     * Kiểm tra quyền - supporter doctor được phép truy cập
     */
    public function testPermissionForSupporterDoctor()
    {
        // Auth user là supporter doctor
        $this->mockAuthUser('doctor', 'supporter');
        
        // Thiết lập request
        $this->setupInput('GET', 'appointmentsinlast7days');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(1, $response['result'], 'Result should be success when user is supporter doctor');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');
    }
    
    /**
     * CTRL_CHARTS_PERM_005
     * Kiểm tra quyền - member doctor được phép truy cập
     */
    public function testPermissionForMemberDoctor()
    {
        // Auth user là member doctor
        $this->mockAuthUser('doctor', 'member');
        
        // Thiết lập request
        $this->setupInput('GET', 'appointmentsinlast7days');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(1, $response['result'], 'Result should be success when user is member doctor');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');
    }
    
    /**
     * CTRL_CHARTS_REQ_006
     * Kiểm tra với phương thức không phải GET
     */
    public function testInvalidRequestMethod()
    {
        // Auth user là admin doctor
        $this->mockAuthUser('doctor', 'admin');
        
        // Thiết lập request với method POST
        $this->setupInput('POST', 'appointmentsinlast7days');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error for non-GET requests');
        $this->assertContains('invalid', $response['msg'], 'Error message should indicate invalid request');
    }
    
    /**
     * CTRL_CHARTS_REQ_007
     * Kiểm tra với request parameter không hợp lệ
     */
    public function testInvalidRequestParameter()
    {
        // Auth user là admin doctor
        $this->mockAuthUser('doctor', 'admin');
        
        // Thiết lập request với parameter không hợp lệ
        $this->setupInput('GET', 'invalidrequest');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error for invalid request parameter');
        $this->assertContains('invalid', $response['msg'], 'Error message should indicate invalid request');
    }
    
    /**
     * CTRL_CHARTS_DATA_008
     * Kiểm tra kết quả của phương thức appointmentsInLast7Days
     */
    public function testAppointmentsInLast7Days()
    {
        // Auth user là admin doctor
        $this->mockAuthUser('doctor', 'admin');
        
        // Thiết lập request appointmentsinlast7days
        $this->setupInput('GET', 'appointmentsinlast7days');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(1, $response['result'], 'Result should be success');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');
        $this->assertArrayHasKey('quantity', $response, 'Response should include quantity');
        $this->assertEquals(7, $response['quantity'], 'Should return data for 7 days');
        
        // Kiểm tra cấu trúc dữ liệu trả về
        $this->assertCount(7, $response['data'], 'Should return 7 items in data array');
        $this->assertArrayHasKey('date', $response['data'][0], 'Each item should have date field');
        $this->assertArrayHasKey('appointment', $response['data'][0], 'Each item should have appointment field');
    }
    
    /**
     * CTRL_CHARTS_DATA_009
     * Kiểm tra kết quả của phương thức appointmentsAndBookingInLast7days
     */
    public function testAppointmentsAndBookingInLast7days()
    {
        // Auth user là admin doctor
        $this->mockAuthUser('doctor', 'admin');
        
        // Thiết lập request appointmentandbookinginlast7days
        $this->setupInput('GET', 'appointmentandbookinginlast7days');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(1, $response['result'], 'Result should be success');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');
        $this->assertArrayHasKey('quantity', $response, 'Response should include quantity');
        $this->assertEquals(7, $response['quantity'], 'Should return data for 7 days');
        
        // Kiểm tra cấu trúc dữ liệu trả về
        $this->assertCount(7, $response['data'], 'Should return 7 items in data array');
        $this->assertArrayHasKey('date', $response['data'][0], 'Each item should have date field');
        $this->assertArrayHasKey('appointment', $response['data'][0], 'Each item should have appointment field');
        $this->assertArrayHasKey('booking', $response['data'][0], 'Each item should have booking field');
    }
    
    /**
     * CTRL_CHARTS_DATA_010
     * Kiểm tra logic quantityBookingInDate
     */
    public function testQuantityBookingInDate()
    {
        // Auth user là admin doctor
        $this->mockAuthUser('doctor', 'admin');
        
        // Thiết lập request appointmentandbookinginlast7days
        $this->setupInput('GET', 'appointmentandbookinginlast7days');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(1, $response['result'], 'Result should be success');
        
        // Kiểm tra tổng số lượng booking cho mỗi ngày
        foreach ($response['data'] as $data) {
            $this->assertGreaterThanOrEqual(0, $data['booking'], 'Booking count should be >= 0');
            $this->assertLessThanOrEqual($data['appointment'], $data['booking'], 'Booking count should be <= appointment count');
        }
    }
}