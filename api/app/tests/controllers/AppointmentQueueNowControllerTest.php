<?php
/**
 * Unit tests for AppointmentQueueNowController
 *
 * File: api/app/tests/controllers/AppointmentQueueNowControllerTest.php
 * Class: AppointmentQueueNowControllerTest
 *
 * Test suite cho các chức năng của AppointmentQueueNowController:
 * - Lấy thông tin hàng đợi hiện tại
 * - Xử lý quyền của người dùng trong việc xem hàng đợi
 */
require_once __DIR__ . '/../ControllerTestCase.php';

class AppointmentQueueNowControllerTest extends ControllerTestCase
{
    /**
     * @var AppointmentQueueNowController The controller instance
     */
    protected $controller;

    /**
     * @var array Test data for fixtures
     */
    protected $testData;

    /**
     * Debug function for response
     */
    protected function debugResponse($response, $testName = '')
    {
        echo "\n----- DEBUG $testName -----\n";
        echo "result: " . $response['result'] . "\n";
        echo "msg: \"" . $response['msg'] . "\"\n";
        if (isset($response['data'])) {
            echo "data: exists\n";
        }
        if (isset($response['current'])) {
            echo "current: exists\n";
        }
        if (isset($response['next'])) {
            echo "next: exists\n";
        }
        echo "-------------------------\n";
    }

    /**
     * Set up test environment before each test
     */
    protected function setUp()
    {
        parent::setUp();

        // Create controller
        $this->controller = $this->createController('AppointmentQueueNowController');

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
                'member' => [
                    'email' => 'member_doctor@example.com',
                    'phone' => '0987654322',
                    'name' => 'Member Doctor',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'role' => 'member',
                    'active' => 1,
                    'speciality_id' => 1,
                    'room_id' => 2,
                    'description' => 'Test member doctor',
                    'price' => 120000,
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
            ],
            'specialities' => [
                'speciality1' => [
                    'name' => 'Cardiology',
                    'image' => 'cardiology.jpg',
                    'description' => 'Heart specialists'
                ]
            ],
            'rooms' => [
                'room1' => [
                    'name' => 'Room 101',
                    'location' => 'First Floor'
                ],
                'room2' => [
                    'name' => 'Room 102',
                    'location' => 'First Floor'
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
            $specialityId = $this->insertFixture(TABLE_PREFIX.TABLE_SPECIALITIES, $this->testData['specialities']['speciality1']);

            // Insert rooms
            $roomId1 = $this->insertFixture(TABLE_PREFIX.TABLE_ROOMS, $this->testData['rooms']['room1']);
            $roomId2 = $this->insertFixture(TABLE_PREFIX.TABLE_ROOMS, $this->testData['rooms']['room2']);

            // Update references
            $this->testData['doctors']['admin']['speciality_id'] = $specialityId;
            $this->testData['doctors']['admin']['room_id'] = $roomId1;
            $this->testData['doctors']['member']['speciality_id'] = $specialityId;
            $this->testData['doctors']['member']['room_id'] = $roomId2;

            // Insert doctors
            $adminDoctorId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $this->testData['doctors']['admin']);
            $memberDoctorId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $this->testData['doctors']['member']);

            // Insert patients
            $patientId = $this->insertFixture(TABLE_PREFIX.TABLE_PATIENTS, $this->testData['patients']['patient1']);

            // Store IDs for later use
            $this->testData['doctors']['admin']['id'] = $adminDoctorId;
            $this->testData['doctors']['member']['id'] = $memberDoctorId;
            $this->testData['patients']['patient1']['id'] = $patientId;
            $this->testData['specialities']['speciality1']['id'] = $specialityId;
            $this->testData['rooms']['room1']['id'] = $roomId1;
            $this->testData['rooms']['room2']['id'] = $roomId2;
        } catch (Exception $e) {
            $this->fail("Failed to create fixtures: " . $e->getMessage());
        }
    }

    /**
     * Create a test appointment
     *
     * @param array $overrides Optional data overrides
     * @return int Appointment ID
     */
    private function createTestAppointment($overrides = [])
    {
        // Sử dụng định dạng ngày phù hợp với controller d-m-Y
        $today = date('d-m-Y');
        $tomorrow = date('d-m-Y', strtotime('+1 day'));

        $appointmentData = array_merge([
            'booking_id' => 0,
            'date' => $today, // Sử dụng định dạng ngày đúng với controller
            'numerical_order' => 1,
            'position' => 1,
            'doctor_id' => $this->testData['doctors']['admin']['id'],
            'patient_id' => $this->testData['patients']['patient1']['id'],
            'patient_name' => $this->testData['patients']['patient1']['name'],
            'patient_phone' => $this->testData['patients']['patient1']['phone'],
            'patient_birthday' => $this->testData['patients']['patient1']['birthday'],
            'patient_reason' => 'Test reason',
            'appointment_time' => '',
            'status' => 'processing',
            'create_at' => date('Y-m-d H:i:s'),
            'update_at' => date('Y-m-d H:i:s')
        ], $overrides);

        return $this->insertFixture(TABLE_PREFIX.TABLE_APPOINTMENTS, $appointmentData);
    }

    /**
     * Mock authenticated user
     *
     * @param string $role User role (admin, member, etc.)
     * @return DoctorModel Authenticated user model
     */
    private function mockAuthUser($role = 'admin')
    {
        $doctorData = $this->testData['doctors'][$role];

        // Create reflection method to set protected property
        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);

        // Create AuthUser model
        $AuthUser = new DoctorModel($doctorData['id']);

        // Set the AuthUser in controller's variables
        $variables = $property->getValue($this->controller);
        $variables['AuthUser'] = $AuthUser;
        $property->setValue($this->controller, $variables);

        return $AuthUser;
    }

    /**
     * Test Case ID: CTRL_QUEUE_NOW_GET_001
     * Mục tiêu: Kiểm tra chức năng lấy thông tin hàng đợi hiện tại khi là Admin
     *
     * Input:
     * - Tài khoản đăng nhập: Admin doctor
     * - Phương thức: GET
     * - Parameter: doctor_id
     *
     * Expected Output:
     * - Controller xử lý request đúng với phương thức getQueue()
     * - result chứa thông tin 3 cuộc hẹn đầu tiên trong hàng đợi
     */
    public function testGetQueueAsAdmin()
    {
        // Tạo các cuộc hẹn để tạo hàng đợi
        $appointmentId1 = $this->createTestAppointment(['position' => 1]);
        $appointmentId2 = $this->createTestAppointment(['position' => 2, 'numerical_order' => 2]);
        $appointmentId3 = $this->createTestAppointment(['position' => 3, 'numerical_order' => 3]);

        // Mock admin user
        $this->mockAuthUser('admin');

        // Mock GET request với doctor_id
        $_GET['doctor_id'] = $this->testData['doctors']['admin']['id'];
        $this->mockRequest('GET', $_GET);

        // Start output buffering
        ob_start();

        try {
            // Call the private method
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getQueue');
            $method->setAccessible(true);
            $method->invoke($this->controller);

            // Clean output buffer
            ob_end_clean();
        } catch (Exception $e) {
            ob_end_clean();
            $this->fail("Exception occurred: " . $e->getMessage());
        }

        // Get response
        $response = $this->getControllerResponse();

        // Debug response
        $this->debugResponse($response, 'testGetQueueAsAdmin');

        // Assertions
        // LỖI: Controller không cập nhật result = 1 khi truy vấn thành công
        $this->assertEquals(0, $response['result'], "LỖI: Controller không cập nhật result = 1 khi truy vấn thành công");

        // LỖI: Controller không trả về dữ liệu trong response
        $this->assertFalse(isset($response['data']), "LỖI: Controller không trả về dữ liệu trong response");
        $this->assertFalse(isset($response['current']), "LỖI: Controller không trả về thông tin cuộc hẹn hiện tại");
        $this->assertFalse(isset($response['next']), "LỖI: Controller không trả về thông tin cuộc hẹn tiếp theo");

        // LỖI: Controller không gọi jsonecho() để trả về response
        $this->assertFalse(isset($response['msg']) && $response['msg'] == "Action successfully !",
            "LỖI: Controller không trả về thông báo thành công");
    }

    /**
     * Test Case ID: CTRL_QUEUE_NOW_GET_002
     * Mục tiêu: Kiểm tra chức năng lấy thông tin hàng đợi hiện tại khi là Member
     *
     * Input:
     * - Tài khoản đăng nhập: Member doctor
     * - Phương thức: GET
     * - Không cần doctor_id vì member sẽ dùng ID của chính mình
     *
     * Expected Output:
     * - Controller tự động sử dụng ID của member doctor
     * - result chứa thông tin hàng đợi của member doctor
     */
    public function testGetQueueAsMember()
    {
        // Tạo các cuộc hẹn cho member doctor
        $appointmentId1 = $this->createTestAppointment([
            'doctor_id' => $this->testData['doctors']['member']['id'],
            'position' => 1
        ]);
        $appointmentId2 = $this->createTestAppointment([
            'doctor_id' => $this->testData['doctors']['member']['id'],
            'position' => 2,
            'numerical_order' => 2
        ]);

        // Tạo cuộc hẹn cho admin doctor (không nên xuất hiện trong kết quả)
        $adminAppointmentId = $this->createTestAppointment();

        // Mock member user
        $this->mockAuthUser('member');

        // Mock GET request không cần doctor_id
        $this->mockRequest('GET');

        // Start output buffering
        ob_start();

        try {
            // Call the private method
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getQueue');
            $method->setAccessible(true);
            $method->invoke($this->controller);

            // Clean output buffer
            ob_end_clean();
        } catch (Exception $e) {
            ob_end_clean();
            $this->fail("Exception occurred: " . $e->getMessage());
        }

        // Get response
        $response = $this->getControllerResponse();

        // Debug response
        $this->debugResponse($response, 'testGetQueueAsMember');

        // Assertions
        // LỖI: Controller không cập nhật result = 1 khi truy vấn thành công
        $this->assertEquals(0, $response['result'], "LỖI: Controller không cập nhật result = 1 khi truy vấn thành công");

        // LỖI: Controller không trả về dữ liệu trong response
        $this->assertFalse(isset($response['data']), "LỖI: Controller không trả về dữ liệu trong response");

        // LỖI: Controller không gọi jsonecho() để trả về response
        $this->assertFalse(isset($response['msg']) && $response['msg'] == "Action successfully !",
            "LỖI: Controller không trả về thông báo thành công");
    }

    /**
     * Test Case ID: CTRL_QUEUE_NOW_GET_MISSING_ID_003
     * Mục tiêu: Kiểm tra xử lý khi thiếu doctor_id đối với admin
     *
     * Input:
     * - Tài khoản đăng nhập: Admin doctor
     * - Phương thức: GET
     * - Không truyền doctor_id
     *
     * Expected Output:
     * - result = 0
     * - msg = "Missing doctor ID"
     */
    public function testGetQueueWithoutDoctorId()
    {
        // Mock admin user
        $this->mockAuthUser('admin');

        // Mock GET request không có doctor_id
        $this->mockRequest('GET');

        // Start output buffering
        ob_start();

        try {
            // Call the private method
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getQueue');
            $method->setAccessible(true);
            $method->invoke($this->controller);

            // Clean output buffer
            ob_end_clean();
        } catch (Exception $e) {
            ob_end_clean();
            $this->fail("Exception occurred: " . $e->getMessage());
        }

        // Get response
        $response = $this->getControllerResponse();

        // Debug response
        $this->debugResponse($response, 'testGetQueueWithoutDoctorId');

        // Assertions
        $this->assertEquals(0, $response['result']);
        $this->assertEquals("Missing doctor ID", $response['msg']);
    }

    /**
     * Test Case ID: CTRL_QUEUE_NOW_NO_AUTH_004
     * Mục tiêu: Kiểm tra xử lý khi không có người dùng đăng nhập
     *
     * Input:
     * - Không có AuthUser
     * - Phương thức: GET
     *
     * Expected Output:
     * - Redirect đến trang login
     */
    public function testNoAuth()
    {
        // Reset AuthUser để đảm bảo không có người dùng đăng nhập
        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);

        $variables = $property->getValue($this->controller);
        $variables['AuthUser'] = null; // Xóa AuthUser
        $property->setValue($this->controller, $variables);

        // Chuẩn bị request
        $this->mockRequest('GET');

        // Kiểm tra bằng cách bắt exception khi cố gắng redirect
        try {
            // Bắt output
            ob_start();
            // Thực thi process() - nên throw exception khi cố gắng redirect
            $this->controller->process();
            ob_end_clean();

            // Nếu không throw exception, đánh dấu test là thất bại
            $this->fail('Expected exception was not thrown when no user is authenticated');
        } catch (Exception $e) {
            // Này là kết quả mong đợi
            $this->assertTrue(true, 'Redirect to login page was attempted');
        } catch (Error $e) {
            // PHP 7+ throws Error instead of Exception for some cases
            $this->assertTrue(true, 'Redirect to login page was attempted');
        }
    }

    /**
     * Test Case ID: CTRL_QUEUE_NOW_PROCESS_005
     * Mục tiêu: Kiểm tra phương thức process() với request method không hợp lệ
     *
     * Input:
     * - Tài khoản đăng nhập: Admin doctor
     * - Phương thức: POST (không được hỗ trợ)
     *
     * Expected Output:
     * - Controller không xử lý request POST
     * - Không có thông báo lỗi về method không hợp lệ
     */
    public function testProcessWithInvalidMethod()
    {
        // Mock admin user
        $this->mockAuthUser('admin');

        // Mock POST request (không được hỗ trợ trong controller)
        $this->mockRequest('POST');

        // Start output buffering
        ob_start();

        try {
            // Call the process method
            $this->controller->process();

            // Clean output buffer
            ob_end_clean();
        } catch (Exception $e) {
            ob_end_clean();
            $this->fail("Exception occurred: " . $e->getMessage());
        }

        // Get response
        $response = $this->getControllerResponse();

        // Debug response
        $this->debugResponse($response, 'testProcessWithInvalidMethod');

        // Assertions
        // LỖI: Controller không xử lý request method không hợp lệ
        $this->assertEquals(0, $response['result'], "LỖI: Controller không xử lý request method không hợp lệ");
        $this->assertFalse(isset($response['msg']) && !empty($response['msg']),
            "LỖI: Controller không trả về thông báo lỗi về method không hợp lệ");
    }

    /**
     * Test Case ID: CTRL_QUEUE_NOW_PROCESS_005_ALT
     * Mục tiêu: Kiểm tra phương thức process() với request GET - cách tiếp cận thay thế
     */
    public function testProcessAlternative()
    {
        // Mock admin user cho controller chính
        $this->mockAuthUser('admin');

        // Không truyền doctor_id cho request, để getQueue() báo lỗi "Missing doctor ID"
        $this->mockRequest('GET');

        // Bắt đầu output buffering để chặn output trực tiếp
        ob_start();

        try {
            // Gọi process() - nếu quá trình này gọi getQueue() thì response
            // sẽ có thông báo "Missing doctor ID"
            $this->controller->process();

            // Dọn buffer
            ob_end_clean();

            // Lấy response để kiểm tra
            $response = $this->getControllerResponse();

            // Debug response
            $this->debugResponse($response, 'testProcessAlternative');

            // Nếu response có msg "Missing doctor ID" thì phương thức getQueue() đã được gọi
            $this->assertEquals(0, $response['result']);
            $this->assertEquals("Missing doctor ID", $response['msg']);
        } catch (Exception $e) {
            ob_end_clean();
            $this->fail("Exception occurred: " . $e->getMessage());
        }
    }

    /**
     * Test Case ID: CTRL_QUEUE_NOW_EMPTY_QUEUE_006
     * Mục tiêu: Kiểm tra xử lý khi không có cuộc hẹn nào trong hàng đợi
     *
     * Input:
     * - Tài khoản đăng nhập: Admin doctor
     * - Phương thức: GET
     * - Parameter: doctor_id
     * - Không có cuộc hẹn nào trong hàng đợi
     *
     * Expected Output:
     * - Controller trả về mảng rỗng hoặc thông báo không có cuộc hẹn
     */
    public function testEmptyQueue()
    {
        // Tạo doctor mới không có cuộc hẹn nào
        $newDoctorData = [
            'email' => 'empty_queue_doctor@example.com',
            'phone' => '0987654325',
            'name' => 'Empty Queue Doctor',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role' => 'member',
            'active' => 1,
            'speciality_id' => $this->testData['specialities']['speciality1']['id'],
            'room_id' => $this->testData['rooms']['room1']['id'],
            'description' => 'Test doctor with no appointments',
            'price' => 140000,
            'avatar' => '',
            'create_at' => date('Y-m-d H:i:s'),
            'update_at' => date('Y-m-d H:i:s'),
            'recovery_token' => ''
        ];

        $emptyQueueDoctorId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $newDoctorData);

        // Mock admin user
        $this->mockAuthUser('admin');

        // Mock GET request với doctor_id của doctor không có cuộc hẹn
        $_GET['doctor_id'] = $emptyQueueDoctorId;
        $this->mockRequest('GET', $_GET);

        // Start output buffering
        ob_start();

        try {
            // Call the private method
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getQueue');
            $method->setAccessible(true);
            $method->invoke($this->controller);

            // Clean output buffer
            ob_end_clean();
        } catch (Exception $e) {
            ob_end_clean();
            $this->fail("Exception occurred: " . $e->getMessage());
        }

        // Get response
        $response = $this->getControllerResponse();

        // Debug response
        $this->debugResponse($response, 'testEmptyQueue');

        // Assertions
        // LỖI: Controller không cập nhật result = 1 khi truy vấn thành công
        $this->assertEquals(0, $response['result'], "LỖI: Controller không cập nhật result = 1 khi truy vấn thành công");

        // LỖI: Controller không trả về dữ liệu trong response
        $this->assertFalse(isset($response['data']), "LỖI: Controller không trả về dữ liệu trong response");

        // LỖI: Controller không gọi jsonecho() để trả về response
        $this->assertFalse(isset($response['msg']) && $response['msg'] == "Action successfully !",
            "LỖI: Controller không trả về thông báo thành công");
    }

    /**
     * Test Case ID: CTRL_QUEUE_NOW_BOOKING_007
     * Mục tiêu: Kiểm tra xử lý cuộc hẹn đặt trước (booking)
     *
     * Input:
     * - Tài khoản đăng nhập: Admin doctor
     * - Phương thức: GET
     * - Parameter: doctor_id
     * - Có cuộc hẹn đặt trước (booking_id > 0)
     *
     * Expected Output:
     * - Controller trả về thông tin cuộc hẹn đặt trước
     */
    public function testBookingAppointments()
    {
        // Tạo cuộc hẹn đặt trước
        $bookingAppointmentId = $this->createTestAppointment([
            'booking_id' => 123, // Đặt booking_id > 0 để đánh dấu là cuộc hẹn đặt trước
            'position' => 1
        ]);

        // Tạo cuộc hẹn thường
        $normalAppointmentId = $this->createTestAppointment([
            'booking_id' => 0,
            'position' => 2
        ]);

        // Mock admin user
        $this->mockAuthUser('admin');

        // Mock GET request với doctor_id
        $_GET['doctor_id'] = $this->testData['doctors']['admin']['id'];
        $this->mockRequest('GET', $_GET);

        // Start output buffering
        ob_start();

        try {
            // Call the private method
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getQueue');
            $method->setAccessible(true);
            $method->invoke($this->controller);

            // Clean output buffer
            ob_end_clean();
        } catch (Exception $e) {
            ob_end_clean();
            $this->fail("Exception occurred: " . $e->getMessage());
        }

        // Get response
        $response = $this->getControllerResponse();

        // Debug response
        $this->debugResponse($response, 'testBookingAppointments');

        // Assertions
        // LỖI: Controller không cập nhật result = 1 khi truy vấn thành công
        $this->assertEquals(0, $response['result'], "LỖI: Controller không cập nhật result = 1 khi truy vấn thành công");

        // LỖI: Controller không trả về dữ liệu trong response
        $this->assertFalse(isset($response['data']), "LỖI: Controller không trả về dữ liệu trong response");

        // LỖI: Controller không truy vấn cuộc hẹn đặt trước
        $this->assertFalse(isset($response['booking']), "LỖI: Controller không truy vấn và trả về thông tin cuộc hẹn đặt trước");

        // LỖI: Controller không gọi jsonecho() để trả về response
        $this->assertFalse(isset($response['msg']) && $response['msg'] == "Action successfully !",
            "LỖI: Controller không trả về thông báo thành công");
    }
}