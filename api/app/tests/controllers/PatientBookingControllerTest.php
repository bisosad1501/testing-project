<?php
/**
 * Test cho PatientBookingController
 *
 * Class: PatientBookingControllerTest
 * File: api/app/tests/controllers/PatientBookingControllerTest.php
 *
 * Test suite cho các chức năng của PatientBookingController:
 * - Kiểm tra quyền truy cập (chỉ patient mới được sử dụng)
 * - Lấy thông tin đặt lịch theo ID (getById)
 * - Hủy đặt lịch (delete)
 */

// Định nghĩa các hằng số cần thiết cho test
if (!defined('EC_SALT')) {
    define('EC_SALT', 'test_salt_for_unit_tests');
}
if (!defined('APPURL')) {
    define('APPURL', 'http://localhost/app');
}

require_once __DIR__ . '/../ControllerTestCase.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

/**
 * Mock cho các model
 */
class MockModel
{
    private $data = [];
    private $is_available = true;

    /**
     * Thiết lập giá trị cho một trường
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Lấy giá trị của một trường
     */
    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * Kiểm tra xem model có tồn tại không
     */
    public function isAvailable()
    {
        return $this->is_available;
    }

    /**
     * Thiết lập trạng thái tồn tại
     */
    public function setAvailable($available)
    {
        $this->is_available = $available;
        return $this;
    }

    /**
     * Lưu model
     */
    public function save()
    {
        return $this;
    }
}

/**
 * Lớp test cho PatientBookingController
 */
class PatientBookingControllerTest extends ControllerTestCase
{
    /**
     * @var PatientBookingController Controller instance
     */
    protected $controller;

    /**
     * @var MockModel Mock AuthUser
     */
    protected $mockAuthUser;

    /**
     * @var stdClass Mock Route
     */
    protected $mockRoute;

    /**
     * Set up test environment before each test
     * Thiết lập môi trường test trước mỗi test
     */
    protected function setUp()
    {
        parent::setUp();

        // Tạo mock AuthUser
        $this->mockAuthUser = new MockModel();
        $this->mockAuthUser->set('id', 1);
        $this->mockAuthUser->set('role', null); // null = patient, 'doctor' = doctor
        $this->mockAuthUser->set('name', 'Test Patient');

        // Tạo mock Route
        $this->mockRoute = new stdClass();
        $this->mockRoute->params = new stdClass();
        $this->mockRoute->params->id = 1;

        // Khởi tạo controller
        $this->controller = $this->createController('PatientBookingController');
    }

    /**
     * PATIENTBOOKING_001
     * Kiểm tra khi người dùng không đăng nhập
     * Test when user is not logged in
     */
    public function testProcessWithNoAuthUser()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Ghi đè phương thức getVariable để trả về null cho AuthUser
        $controller->setVariable('AuthUser', null);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè hàm header và exit để không thực sự gọi chúng
        $headerCalled = false;
        $headerCalledWith = '';
        $exitCalled = false;

        $controller->header = function($header) use (&$headerCalled, &$headerCalledWith) {
            $headerCalled = true;
            $headerCalledWith = $header;
        };

        $controller->exit = function() use (&$exitCalled) {
            $exitCalled = true;
        };

        // Gọi phương thức process()
        $controller->process();

        // Kiểm tra xem header có được gọi không
        $this->assertTrue($headerCalled, 'header() should be called');
        $this->assertEquals('Location: ' . APPURL . '/login', $headerCalledWith, 'header() should redirect to login page');

        // Kiểm tra xem exit có được gọi không
        $this->assertTrue($exitCalled, 'exit() should be called');

        // Lỗi trong code gốc: Không kiểm tra $AuthUser trước khi sử dụng
    }

    /**
     * PATIENTBOOKING_002
     * Kiểm tra khi người dùng là doctor
     * Test when user is a doctor
     */
    public function testProcessWithDoctorRole()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Tạo mock AuthUser với role là doctor
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', 'doctor');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Khởi tạo resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $respProperty->setValue($controller, new stdClass());

        // Ghi đè phương thức jsonecho để không thực sự gửi JSON
        $jsonEchoCalled = false;
        $controller->jsonecho = function() use (&$jsonEchoCalled, $controller) {
            $jsonEchoCalled = true;
            // Dừng thực thi phương thức hiện tại
            return;
        };

        // Gọi phương thức process()
        $controller->process();

        // Kiểm tra xem jsonecho có được gọi không
        $this->assertTrue($jsonEchoCalled, 'jsonecho() should be called');

        // Lấy resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $resp = $respProperty->getValue($controller);

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $resp->result, 'resp->result should be 0');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertEquals('You are not logging with PATIENT account so that you are not allowed do this action !', $resp->msg, 'resp->msg should contain error message');

        // Lỗi trong code gốc: Điều kiện if($AuthUser->get("role")) không chính xác
    }

    /**
     * PATIENTBOOKING_003
     * Kiểm tra phương thức getById() với ID hợp lệ
     * Test getById() method with valid ID
     */
    public function testGetByIdWithValidId()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Tạo mock Route với ID hợp lệ
        $mockRoute = new stdClass();
        $mockRoute->params = new stdClass();
        $mockRoute->params->id = 1;

        // Tạo mock Booking
        $mockBooking = new MockModel();
        $mockBooking->set('id', 1);
        $mockBooking->set('patient_id', 1);
        $mockBooking->set('booking_name', 'Test Booking');
        $mockBooking->set('booking_phone', '0123456789');
        $mockBooking->set('name', 'Test Patient');
        $mockBooking->set('gender', 1);
        $mockBooking->set('birthday', '1990-01-01');
        $mockBooking->set('address', 'Test Address');
        $mockBooking->set('reason', 'Test Reason');
        $mockBooking->set('appointment_date', '2023-05-15');
        $mockBooking->set('appointment_time', '09:00');
        $mockBooking->set('status', 'processing');
        $mockBooking->set('service_id', 1);
        $mockBooking->set('create_at', '2023-05-10 10:00:00');
        $mockBooking->set('update_at', '2023-05-10 10:00:00');

        // Tạo mock Service
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');
        $mockService->set('image', 'test.jpg');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $mockRoute);

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockBooking, $mockService) {
            if ($name == 'Booking' && $id == 1) {
                return $mockBooking;
            } else if ($name == 'Service' && $id == 1) {
                return $mockService;
            }
            return new MockModel();
        };

        // Khởi tạo resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $respProperty->setValue($controller, new stdClass());

        // Ghi đè phương thức jsonecho để không thực sự gửi JSON
        $jsonEchoCalled = false;
        $controller->jsonecho = function() use (&$jsonEchoCalled, $controller) {
            $jsonEchoCalled = true;
            // Dừng thực thi phương thức hiện tại
            return;
        };

        // Gọi phương thức getById() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getById');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Kiểm tra xem jsonecho có được gọi không
        $this->assertTrue($jsonEchoCalled, 'jsonecho() should be called');

        // Lấy resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $resp = $respProperty->getValue($controller);

        // Kiểm tra xem resp->result có được đặt thành 1 không
        $this->assertEquals(1, $resp->result, 'resp->result should be 1');

        // Kiểm tra xem resp->data có chứa thông tin booking không
        $this->assertEquals(1, $resp->data['id'], 'resp->data[id] should be 1');
        $this->assertEquals('Test Booking', $resp->data['booking_name'], 'resp->data[booking_name] should be Test Booking');
        $this->assertEquals('Test Service', $resp->data['service']['name'], 'resp->data[service][name] should be Test Service');
    }

    /**
     * PATIENTBOOKING_004
     * Kiểm tra phương thức getById() với ID không tồn tại
     * Test getById() method with non-existent ID
     */
    public function testGetByIdWithNonExistentId()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Tạo mock Route với ID không tồn tại
        $mockRoute = new stdClass();
        $mockRoute->params = new stdClass();
        $mockRoute->params->id = 999;

        // Tạo mock Booking không tồn tại
        $mockBooking = new MockModel();
        $mockBooking->setAvailable(false);

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $mockRoute);

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockBooking) {
            if ($name == 'Booking') {
                return $mockBooking;
            }
            return new MockModel();
        };

        // Khởi tạo resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $respProperty->setValue($controller, new stdClass());

        // Ghi đè phương thức jsonecho để không thực sự gửi JSON
        $jsonEchoCalled = false;
        $controller->jsonecho = function() use (&$jsonEchoCalled, $controller) {
            $jsonEchoCalled = true;
            // Dừng thực thi phương thức hiện tại
            return;
        };

        // Gọi phương thức getById() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getById');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Kiểm tra xem jsonecho có được gọi không
        $this->assertTrue($jsonEchoCalled, 'jsonecho() should be called');

        // Lấy resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $resp = $respProperty->getValue($controller);

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $resp->result, 'resp->result should be 0');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertEquals('Booking does not exist', $resp->msg, 'resp->msg should contain error message');
    }

    /**
     * PATIENTBOOKING_005
     * Kiểm tra phương thức getById() với booking không thuộc về patient hiện tại
     * Test getById() method with booking not belonging to current patient
     */
    public function testGetByIdWithBookingNotBelongingToCurrentPatient()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Tạo mock Route với ID hợp lệ
        $mockRoute = new stdClass();
        $mockRoute->params = new stdClass();
        $mockRoute->params->id = 1;

        // Tạo mock Booking thuộc về patient khác
        $mockBooking = new MockModel();
        $mockBooking->set('id', 1);
        $mockBooking->set('patient_id', 2); // Patient ID khác với AuthUser ID

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $mockRoute);

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockBooking) {
            if ($name == 'Booking') {
                return $mockBooking;
            }
            return new MockModel();
        };

        // Khởi tạo resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $respProperty->setValue($controller, new stdClass());

        // Ghi đè phương thức jsonecho để không thực sự gửi JSON
        $jsonEchoCalled = false;
        $controller->jsonecho = function() use (&$jsonEchoCalled, $controller) {
            $jsonEchoCalled = true;
            // Dừng thực thi phương thức hiện tại
            return;
        };

        // Gọi phương thức getById() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getById');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Kiểm tra xem jsonecho có được gọi không
        $this->assertTrue($jsonEchoCalled, 'jsonecho() should be called');

        // Lấy resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $resp = $respProperty->getValue($controller);

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $resp->result, 'resp->result should be 0');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertEquals('This booking is not available', $resp->msg, 'resp->msg should contain error message');
    }
    /**
     * PATIENTBOOKING_006
     * Kiểm tra phương thức delete() với ID hợp lệ và trạng thái processing
     * Test delete() method with valid ID and processing status
     */
    public function testDeleteWithValidIdAndProcessingStatus()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Tạo mock Route với ID hợp lệ
        $mockRoute = new stdClass();
        $mockRoute->params = new stdClass();
        $mockRoute->params->id = 1;

        // Tạo mock Booking
        $mockBooking = new MockModel();
        $mockBooking->set('id', 1);
        $mockBooking->set('patient_id', 1);
        $mockBooking->set('status', 'processing');
        $mockBooking->set('service_id', 1);
        $mockBooking->set('appointment_date', '2023-05-15');
        $mockBooking->set('appointment_time', '09:00');

        // Tạo mock Service
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');

        // Tạo mock Notification
        $mockNotification = new MockModel();

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $mockRoute);

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockBooking, $mockService, $mockNotification) {
            if ($name == 'Booking' && $id == 1) {
                return $mockBooking;
            } else if ($name == 'Service' && $id == 1) {
                return $mockService;
            } else if ($name == 'Notification') {
                return $mockNotification;
            }
            return new MockModel();
        };

        // Khởi tạo resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $respProperty->setValue($controller, new stdClass());

        // Ghi đè phương thức jsonecho để không thực sự gửi JSON
        $jsonEchoCalled = false;
        $controller->jsonecho = function() use (&$jsonEchoCalled, $controller) {
            $jsonEchoCalled = true;
            // Dừng thực thi phương thức hiện tại
            return;
        };

        // Gọi phương thức delete() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('delete');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Kiểm tra xem jsonecho có được gọi không
        $this->assertTrue($jsonEchoCalled, 'jsonecho() should be called');

        // Lấy resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $resp = $respProperty->getValue($controller);

        // Kiểm tra xem resp->result có được đặt thành 1 không
        $this->assertEquals(1, $resp->result, 'resp->result should be 1');

        // Kiểm tra xem resp->msg có chứa thông báo thành công không
        $this->assertEquals('Booking has been cancelled successfully !', $resp->msg, 'resp->msg should contain success message');

        // Kiểm tra xem status của booking có được đặt thành cancelled không
        $this->assertEquals('cancelled', $mockBooking->get('status'), 'Booking status should be set to cancelled');
    }

    /**
     * PATIENTBOOKING_007
     * Kiểm tra phương thức delete() với ID không tồn tại
     * Test delete() method with non-existent ID
     */
    public function testDeleteWithNonExistentId()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Tạo mock Route với ID không tồn tại
        $mockRoute = new stdClass();
        $mockRoute->params = new stdClass();
        $mockRoute->params->id = 999;

        // Tạo mock Booking không tồn tại
        $mockBooking = new MockModel();
        $mockBooking->setAvailable(false);

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $mockRoute);

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockBooking) {
            if ($name == 'Booking') {
                return $mockBooking;
            }
            return new MockModel();
        };

        // Khởi tạo resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $respProperty->setValue($controller, new stdClass());

        // Ghi đè phương thức jsonecho để không thực sự gửi JSON
        $jsonEchoCalled = false;
        $controller->jsonecho = function() use (&$jsonEchoCalled, $controller) {
            $jsonEchoCalled = true;
            // Dừng thực thi phương thức hiện tại
            return;
        };

        // Gọi phương thức delete() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('delete');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Kiểm tra xem jsonecho có được gọi không
        $this->assertTrue($jsonEchoCalled, 'jsonecho() should be called');

        // Lấy resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $resp = $respProperty->getValue($controller);

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $resp->result, 'resp->result should be 0');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertEquals('This booking does not exist !', $resp->msg, 'resp->msg should contain error message');
    }

    /**
     * PATIENTBOOKING_008
     * Kiểm tra phương thức delete() với booking không thuộc về patient hiện tại
     * Test delete() method with booking not belonging to current patient
     */
    public function testDeleteWithBookingNotBelongingToCurrentPatient()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Tạo mock Route với ID hợp lệ
        $mockRoute = new stdClass();
        $mockRoute->params = new stdClass();
        $mockRoute->params->id = 1;

        // Tạo mock Booking thuộc về patient khác
        $mockBooking = new MockModel();
        $mockBooking->set('id', 1);
        $mockBooking->set('patient_id', 2); // Patient ID khác với AuthUser ID

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $mockRoute);

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockBooking) {
            if ($name == 'Booking') {
                return $mockBooking;
            }
            return new MockModel();
        };

        // Khởi tạo resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $respProperty->setValue($controller, new stdClass());

        // Ghi đè phương thức jsonecho để không thực sự gửi JSON
        $jsonEchoCalled = false;
        $controller->jsonecho = function() use (&$jsonEchoCalled, $controller) {
            $jsonEchoCalled = true;
            // Dừng thực thi phương thức hiện tại
            return;
        };

        // Gọi phương thức delete() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('delete');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Kiểm tra xem jsonecho có được gọi không
        $this->assertTrue($jsonEchoCalled, 'jsonecho() should be called');

        // Lấy resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $resp = $respProperty->getValue($controller);

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $resp->result, 'resp->result should be 0');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertEquals('This booking is not available !', $resp->msg, 'resp->msg should contain error message');
    }

    /**
     * PATIENTBOOKING_009
     * Kiểm tra phương thức delete() với booking đã bị hủy
     * Test delete() method with already cancelled booking
     */
    public function testDeleteWithAlreadyCancelledBooking()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Tạo mock Route với ID hợp lệ
        $mockRoute = new stdClass();
        $mockRoute->params = new stdClass();
        $mockRoute->params->id = 1;

        // Tạo mock Booking đã bị hủy
        $mockBooking = new MockModel();
        $mockBooking->set('id', 1);
        $mockBooking->set('patient_id', 1);
        $mockBooking->set('status', 'cancelled'); // Đã bị hủy

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $mockRoute);

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockBooking) {
            if ($name == 'Booking') {
                return $mockBooking;
            }
            return new MockModel();
        };

        // Khởi tạo resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $respProperty->setValue($controller, new stdClass());

        // Ghi đè phương thức jsonecho để không thực sự gửi JSON
        $jsonEchoCalled = false;
        $controller->jsonecho = function() use (&$jsonEchoCalled, $controller) {
            $jsonEchoCalled = true;
            // Dừng thực thi phương thức hiện tại
            return;
        };

        // Gọi phương thức delete() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('delete');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Kiểm tra xem jsonecho có được gọi không
        $this->assertTrue($jsonEchoCalled, 'jsonecho() should be called');

        // Lấy resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $resp = $respProperty->getValue($controller);

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $resp->result, 'resp->result should be 0');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertEquals('This booking\'s status is cancelled. No need any more action !', $resp->msg, 'resp->msg should contain error message');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 151, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra $Booking tồn tại trước khi truy cập thuộc tính
    }

    /**
     * PATIENTBOOKING_010
     * Kiểm tra phương thức process() với request method GET
     * Test process() method with GET request method
     */
    public function testProcessWithGetMethod()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::method() bằng cách sử dụng runkit nếu có
        // Vì không thể ghi đè trực tiếp, chúng ta sẽ ghi đè phương thức request_method trong controller
        $controller->request_method = 'GET';

        // Ghi đè phương thức getById để kiểm tra xem nó có được gọi không
        $getByIdCalled = false;
        $controller->getById = function() use (&$getByIdCalled) {
            $getByIdCalled = true;
        };

        // Gọi phương thức process()
        $controller->process();

        // Kiểm tra xem getById có được gọi không
        $this->assertTrue($getByIdCalled, 'getById() should be called');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi getById() ở dòng 29, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra request method một cách chính xác (nên sử dụng switch-case hoặc if-elseif-else)
    }

    /**
     * PATIENTBOOKING_011
     * Kiểm tra phương thức process() với request method DELETE
     * Test process() method with DELETE request method
     */
    public function testProcessWithDeleteMethod()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::method() bằng cách sử dụng runkit nếu có
        // Vì không thể ghi đè trực tiếp, chúng ta sẽ ghi đè phương thức request_method trong controller
        $controller->request_method = 'DELETE';

        // Ghi đè phương thức delete để kiểm tra xem nó có được gọi không
        $deleteCalled = false;
        $controller->delete = function() use (&$deleteCalled) {
            $deleteCalled = true;
        };

        // Gọi phương thức process()
        $controller->process();

        // Kiểm tra xem delete có được gọi không
        $this->assertTrue($deleteCalled, 'delete() should be called');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi delete() ở dòng 33, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra request method một cách chính xác (nên sử dụng switch-case hoặc if-elseif-else)
    }

    /**
     * PATIENTBOOKING_012
     * Kiểm tra phương thức delete() với trạng thái không hợp lệ
     * Test delete() method with invalid status
     */
    public function testDeleteWithInvalidStatus()
    {
        // Tạo controller thật
        $controller = new PatientBookingController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Tạo mock Route với ID hợp lệ
        $mockRoute = new stdClass();
        $mockRoute->params = new stdClass();
        $mockRoute->params->id = 1;

        // Tạo mock Booking với trạng thái không hợp lệ
        $mockBooking = new MockModel();
        $mockBooking->set('id', 1);
        $mockBooking->set('patient_id', 1);
        $mockBooking->set('status', 'completed'); // Trạng thái không hợp lệ để hủy

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $mockRoute);

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockBooking) {
            if ($name == 'Booking') {
                return $mockBooking;
            }
            return new MockModel();
        };

        // Khởi tạo resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $respProperty->setValue($controller, new stdClass());

        // Ghi đè phương thức jsonecho để không thực sự gửi JSON
        $jsonEchoCalled = false;
        $controller->jsonecho = function() use (&$jsonEchoCalled, $controller) {
            $jsonEchoCalled = true;
            // Dừng thực thi phương thức hiện tại
            return;
        };

        // Gọi phương thức delete() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('delete');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Kiểm tra xem jsonecho có được gọi không
        $this->assertTrue($jsonEchoCalled, 'jsonecho() should be called');

        // Lấy resp object thông qua reflection
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $resp = $respProperty->getValue($controller);

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $resp->result, 'resp->result should be 0');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertTrue(strpos($resp->msg, 'Booking\'s status is not valid') !== false, 'resp->msg should contain error message about invalid status');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 162, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra $Booking tồn tại trước khi truy cập thuộc tính
    }
}