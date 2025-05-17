<?php
/**
 * Test cho PatientBookingsController
 *
 * Class: PatientBookingsControllerTest
 * File: api/app/tests/controllers/PatientBookingsControllerTest.php
 *
 * Test suite cho các chức năng của PatientBookingsController:
 * - Kiểm tra quyền truy cập (chỉ patient mới được sử dụng)
 * - Lấy danh sách đặt lịch (getAll)
 * - Tạo đặt lịch mới (save)
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

// Định nghĩa các hàm validation nếu chưa tồn tại
if (!function_exists('isVietnameseName')) {
    function isVietnameseName($name) {
        return preg_match('/^[a-zA-ZÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂưăạảấầẩẫậắằẳẵặẹẻẽềềểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]+$/', $name) ? 1 : 0;
    }
}

if (!function_exists('isNumber')) {
    function isNumber($number) {
        return preg_match('/^[0-9]+$/', $number) ? true : false;
    }
}

if (!function_exists('isAddress')) {
    function isAddress($address) {
        return preg_match('/^[a-zA-Z0-9ÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂĐĨŨƠàáâãèéêìíòóôõùúăđĩũơƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂưăạảấầẩẫậắằẳẵặẹẻẽềềểỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s,.-]+$/', $address) ? 1 : 0;
    }
}

if (!function_exists('isBirthdayValid')) {
    function isBirthdayValid($birthday) {
        $date = DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$date || $date->format('Y-m-d') !== $birthday) {
            return "Birthday is not valid. Format must be YYYY-MM-DD";
        }

        $now = new DateTime();
        if ($date > $now) {
            return "Birthday cannot be in the future";
        }

        return "";
    }
}

if (!function_exists('isAppointmentTimeValid')) {
    function isAppointmentTimeValid($datetime) {
        $date = DateTime::createFromFormat('Y-m-d H:i', $datetime);
        if (!$date || $date->format('Y-m-d H:i') !== $datetime) {
            return "Appointment time is not valid. Format must be YYYY-MM-DD HH:MM";
        }

        $now = new DateTime();
        if ($date < $now) {
            return "Appointment time cannot be in the past";
        }

        return "";
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
 * Lớp test cho PatientBookingsController
 */
class PatientBookingsControllerTest extends ControllerTestCase
{
    /**
     * @var PatientBookingsController Controller instance
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
        $this->mockAuthUser->set('phone', '0123456789');

        // Tạo mock Route
        $this->mockRoute = new stdClass();
        $this->mockRoute->params = new stdClass();
        $this->mockRoute->params->id = 1;

        // Khởi tạo controller
        $this->controller = $this->createController('PatientBookingsController');
    }

    /**
     * PATIENTBOOKINGS_001
     * Kiểm tra khi người dùng không đăng nhập
     * Test when user is not logged in
     */
    public function testProcessWithNoAuthUser()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

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

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có lỗi trong phần này, code đã kiểm tra $AuthUser trước khi sử dụng (dòng 12)
    }

    /**
     * PATIENTBOOKINGS_002
     * Kiểm tra khi người dùng là doctor
     * Test when user is a doctor
     */
    public function testProcessWithDoctorRole()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

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

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Điều kiện if($AuthUser->get("role")) không chính xác (dòng 19)
        // - Nên sử dụng if($AuthUser->get("role") === "doctor") để kiểm tra chính xác
        // - Không có return sau khi gọi jsonecho() ở dòng 23, có thể dẫn đến việc code tiếp tục thực thi
    }

    /**
     * PATIENTBOOKINGS_003
     * Kiểm tra phương thức process() với request method GET
     * Test process() method with GET request method
     */
    public function testProcessWithGetMethod()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::method() bằng cách sử dụng request_method
        $controller->request_method = 'GET';

        // Ghi đè phương thức getAll để kiểm tra xem nó có được gọi không
        $getAllCalled = false;
        $controller->getAll = function() use (&$getAllCalled) {
            $getAllCalled = true;
        };

        // Gọi phương thức process()
        $controller->process();

        // Kiểm tra xem getAll có được gọi không
        $this->assertTrue($getAllCalled, 'getAll() should be called');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi getAll() ở dòng 29, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra request method một cách chính xác (nên sử dụng switch-case hoặc if-elseif-else)
    }

    /**
     * PATIENTBOOKINGS_004
     * Kiểm tra phương thức process() với request method POST
     * Test process() method with POST request method
     */
    public function testProcessWithPostMethod()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::method() bằng cách sử dụng request_method
        $controller->request_method = 'POST';

        // Ghi đè phương thức save để kiểm tra xem nó có được gọi không
        $saveCalled = false;
        $controller->save = function() use (&$saveCalled) {
            $saveCalled = true;
        };

        // Gọi phương thức process()
        $controller->process();

        // Kiểm tra xem save có được gọi không
        $this->assertTrue($saveCalled, 'save() should be called');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi save() ở dòng 33, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra request method một cách chính xác (nên sử dụng switch-case hoặc if-elseif-else)
    }

    /**
     * PATIENTBOOKINGS_005
     * Kiểm tra phương thức getAll() với kết quả trống
     * Test getAll() method with empty result
     */
    public function testGetAllWithEmptyResult()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::get để trả về giá trị mặc định
        $controller->getInputGet = function($key, $default = null) {
            return $default;
        };

        // Ghi đè phương thức DB::table để trả về mock query builder
        $mockQueryBuilder = new stdClass();
        $mockQueryBuilder->where = function() use (&$mockQueryBuilder) { return $mockQueryBuilder; };
        $mockQueryBuilder->leftJoin = function() use (&$mockQueryBuilder) { return $mockQueryBuilder; };
        $mockQueryBuilder->select = function() use (&$mockQueryBuilder) { return $mockQueryBuilder; };
        $mockQueryBuilder->orderBy = function() use (&$mockQueryBuilder) { return $mockQueryBuilder; };
        $mockQueryBuilder->limit = function() use (&$mockQueryBuilder) { return $mockQueryBuilder; };
        $mockQueryBuilder->offset = function() use (&$mockQueryBuilder) { return $mockQueryBuilder; };
        $mockQueryBuilder->get = function() { return []; }; // Trả về mảng rỗng

        $controller->DB = new stdClass();
        $controller->DB->table = function() use ($mockQueryBuilder) {
            return $mockQueryBuilder;
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

        // Gọi phương thức getAll() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getAll');
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

        // Kiểm tra xem resp->quantity có được đặt thành 0 không
        $this->assertEquals(0, $resp->quantity, 'resp->quantity should be 0');

        // Kiểm tra xem resp->data có phải là mảng rỗng không
        $this->assertEmpty($resp->data, 'resp->data should be empty');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 150, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra nếu $result là null trước khi sử dụng trong vòng lặp foreach
        // - Không có xử lý exception chi tiết khi truy vấn cơ sở dữ liệu
    }

    /**
     * PATIENTBOOKINGS_006
     * Kiểm tra phương thức save() với thiếu trường bắt buộc
     * Test save() method with missing required field
     */
    public function testSaveWithMissingRequiredField()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về null cho trường bắt buộc
        $controller->getInputPost = function($key) {
            // Trả về null cho trường booking_name để tạo lỗi thiếu trường bắt buộc
            if ($key == 'booking_name') {
                return null;
            }
            return 'test_value';
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Missing field: booking_name', $resp->msg, 'resp->msg should contain error message about missing field');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 178, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
    }

    /**
     * PATIENTBOOKINGS_007
     * Kiểm tra phương thức save() với service không tồn tại
     * Test save() method with non-existent service
     */
    public function testSaveWithNonExistentService()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '0123456789',
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 999, // ID không tồn tại
                'doctor_id' => 0,
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Tạo mock Service không tồn tại
        $mockService = new MockModel();
        $mockService->setAvailable(false);

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService) {
            if ($name == 'Service') {
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Service is not available', $resp->msg, 'resp->msg should contain error message about service not available');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 218, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra $Service tồn tại trước khi truy cập thuộc tính
        // - Không có xử lý exception chi tiết khi kiểm tra service
    }

    /**
     * PATIENTBOOKINGS_008
     * Kiểm tra phương thức save() với dữ liệu hợp lệ
     * Test save() method with valid data
     */
    public function testSaveWithValidData()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '0123456789',
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 1,
                'doctor_id' => 1,
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Tạo mock Service hợp lệ
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');
        $mockService->set('image', 'test.jpg');

        // Tạo mock Doctor hợp lệ
        $mockDoctor = new MockModel();
        $mockDoctor->set('id', 1);
        $mockDoctor->set('name', 'Test Doctor');

        // Tạo mock Booking
        $mockBooking = new MockModel();
        $mockBooking->set('id', 1);
        $mockBooking->set('doctor_id', 1);
        $mockBooking->set('service_id', 1);
        $mockBooking->set('patient_id', 1);
        $mockBooking->set('booking_name', 'Test Booking');
        $mockBooking->set('booking_phone', '0123456789');
        $mockBooking->set('name', 'Test Patient');
        $mockBooking->set('gender', 1);
        $mockBooking->set('birthday', '1990-01-01');
        $mockBooking->set('address', 'Test Address');
        $mockBooking->set('reason', 'Test Reason');
        $mockBooking->set('appointment_time', '09:00');
        $mockBooking->set('appointment_date', '2023-05-15');
        $mockBooking->set('status', 'processing');
        $mockBooking->set('create_at', '2023-05-10 10:00:00');
        $mockBooking->set('update_at', '2023-05-10 10:00:00');

        // Tạo mock Notification
        $mockNotification = new MockModel();

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService, $mockDoctor, $mockBooking, $mockNotification) {
            if ($name == 'Service') {
                return $mockService;
            } else if ($name == 'Doctor' && $id == 1) {
                return $mockDoctor;
            } else if ($name == 'Booking') {
                return $mockBooking;
            } else if ($name == 'Notification') {
                return $mockNotification;
            }
            return new MockModel();
        };

        // Ghi đè phương thức DB::table để trả về mock query builder
        $mockQueryBuilder = new stdClass();
        $mockQueryBuilder->where = function() use (&$mockQueryBuilder) { return $mockQueryBuilder; };
        $mockQueryBuilder->get = function() { return []; }; // Trả về mảng rỗng để không có lịch hẹn trùng

        $controller->DB = new stdClass();
        $controller->DB->table = function() use ($mockQueryBuilder) {
            return $mockQueryBuilder;
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertTrue(strpos($resp->msg, 'Congratulation') !== false, 'resp->msg should contain success message');

        // Kiểm tra xem resp->data có chứa thông tin booking không
        $this->assertEquals(1, $resp->data['id'], 'resp->data[id] should be 1');
        $this->assertEquals('Test Booking', $resp->data['booking_name'], 'resp->data[booking_name] should be Test Booking');
        $this->assertEquals('Test Service', $resp->data['service']['name'], 'resp->data[service][name] should be Test Service');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 392, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra $Service tồn tại trước khi truy cập thuộc tính
        // - Không kiểm tra $Booking tồn tại trước khi truy cập thuộc tính
        // - Không có xử lý exception chi tiết khi lưu booking
    }

    /**
     * PATIENTBOOKINGS_009
     * Kiểm tra phương thức save() với service_id và doctor_id đều bằng 0
     * Test save() method with both service_id and doctor_id equal to 0
     */
    public function testSaveWithBothServiceAndDoctorIdZero()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '0123456789',
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 0, // ID bằng 0
                'doctor_id' => 0, // ID bằng 0
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Bạn cần chọn bác sĩ hoặc nhu cầu khám bệnh để tạo lịch hẹn !', $resp->msg, 'resp->msg should contain error message about missing doctor or service');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 214, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
    }

    /**
     * PATIENTBOOKINGS_010
     * Kiểm tra phương thức save() với booking_name không hợp lệ
     * Test save() method with invalid booking_name
     */
    public function testSaveWithInvalidBookingName()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test123', // Tên không hợp lệ (có số)
                'booking_phone' => '0123456789',
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 1,
                'doctor_id' => 1,
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Ghi đè phương thức isVietnameseName để trả về false cho booking_name không hợp lệ
        $controller->isVietnameseName = function($name) {
            return false;
        };

        // Tạo mock Service hợp lệ
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService) {
            if ($name == 'Service') {
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('( Booking name ) Vietnamese name only has letters and space', $resp->msg, 'resp->msg should contain error message about invalid booking name');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 257, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
        // - Không có xử lý exception chi tiết khi kiểm tra booking_name
    }

    /**
     * PATIENTBOOKINGS_011
     * Kiểm tra phương thức save() với booking_phone quá ngắn
     * Test save() method with booking_phone too short
     */
    public function testSaveWithBookingPhoneTooShort()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '12345', // Số điện thoại quá ngắn
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 1,
                'doctor_id' => 1,
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Ghi đè phương thức isVietnameseName để trả về true cho booking_name hợp lệ
        $controller->isVietnameseName = function($name) {
            return true;
        };

        // Tạo mock Service hợp lệ
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService) {
            if ($name == 'Service') {
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Booking number has at least 10 number !', $resp->msg, 'resp->msg should contain error message about booking phone too short');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 263, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
        // - Không có xử lý exception chi tiết khi kiểm tra booking_phone
    }

    /**
     * PATIENTBOOKINGS_012
     * Kiểm tra phương thức save() với booking_phone không phải số
     * Test save() method with booking_phone not a number
     */
    public function testSaveWithBookingPhoneNotNumber()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => 'abc1234567', // Số điện thoại không phải số
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 1,
                'doctor_id' => 1,
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Ghi đè phương thức isVietnameseName để trả về true cho booking_name hợp lệ
        $controller->isVietnameseName = function($name) {
            return true;
        };

        // Ghi đè phương thức isNumber để trả về false cho booking_phone không phải số
        $controller->isNumber = function($number) {
            return false;
        };

        // Tạo mock Service hợp lệ
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService) {
            if ($name == 'Service') {
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Booking phone is not a valid phone number. Please, try again !', $resp->msg, 'resp->msg should contain error message about booking phone not a number');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 269, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
        // - Không có xử lý exception chi tiết khi kiểm tra booking_phone
    }

    /**
     * PATIENTBOOKINGS_013
     * Kiểm tra phương thức save() với name không hợp lệ
     * Test save() method with invalid name
     */
    public function testSaveWithInvalidName()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '0123456789',
                'name' => 'Test123', // Tên không hợp lệ (có số)
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 1,
                'doctor_id' => 1,
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Ghi đè phương thức isVietnameseName để trả về true cho booking_name hợp lệ
        $controller->isVietnameseName = function($name) use (&$controller) {
            // Trả về true cho booking_name, false cho name
            if ($name == 'Test Booking') {
                return true;
            }
            return false;
        };

        // Tạo mock Service hợp lệ
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService) {
            if ($name == 'Service') {
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('( Name ) Vietnamese name only has letters and space', $resp->msg, 'resp->msg should contain error message about invalid name');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 276, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
        // - Không có xử lý exception chi tiết khi kiểm tra name
    }

    /**
     * PATIENTBOOKINGS_014
     * Kiểm tra phương thức save() với gender không hợp lệ
     * Test save() method with invalid gender
     */
    public function testSaveWithInvalidGender()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '0123456789',
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 1,
                'doctor_id' => 1,
                'gender' => 3, // Gender không hợp lệ (chỉ chấp nhận 0 hoặc 1)
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Ghi đè phương thức isVietnameseName để trả về true cho booking_name và name hợp lệ
        $controller->isVietnameseName = function($name) {
            return true;
        };

        // Tạo mock Service hợp lệ
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService) {
            if ($name == 'Service') {
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Gender is not valid. There are 2 values: 0 is female & 1 is men', $resp->msg, 'resp->msg should contain error message about invalid gender');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 285, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
        // - Không có xử lý exception chi tiết khi kiểm tra gender
    }

    /**
     * PATIENTBOOKINGS_015
     * Kiểm tra phương thức save() với birthday không hợp lệ
     * Test save() method with invalid birthday
     */
    public function testSaveWithInvalidBirthday()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '0123456789',
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 1,
                'doctor_id' => 1,
                'gender' => 1,
                'birthday' => '2050-01-01', // Birthday không hợp lệ (trong tương lai)
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Ghi đè phương thức isVietnameseName để trả về true cho booking_name và name hợp lệ
        $controller->isVietnameseName = function($name) {
            return true;
        };

        // Ghi đè phương thức isBirthdayValid để trả về thông báo lỗi cho birthday không hợp lệ
        $controller->isBirthdayValid = function($birthday) {
            return "Birthday cannot be in the future";
        };

        // Tạo mock Service hợp lệ
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService) {
            if ($name == 'Service') {
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Birthday cannot be in the future', $resp->msg, 'resp->msg should contain error message about invalid birthday');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 296, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
        // - Không có xử lý exception chi tiết khi kiểm tra birthday
    }

    /**
     * PATIENTBOOKINGS_016
     * Kiểm tra phương thức save() với address không hợp lệ
     * Test save() method with invalid address
     */
    public function testSaveWithInvalidAddress()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '0123456789',
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 1,
                'doctor_id' => 1,
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test@Address#', // Address không hợp lệ (có ký tự đặc biệt)
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Ghi đè phương thức isVietnameseName để trả về true cho booking_name và name hợp lệ
        $controller->isVietnameseName = function($name) {
            return true;
        };

        // Ghi đè phương thức isBirthdayValid để trả về chuỗi rỗng cho birthday hợp lệ
        $controller->isBirthdayValid = function($birthday) {
            return "";
        };

        // Ghi đè phương thức isAddress để trả về false cho address không hợp lệ
        $controller->isAddress = function($address) {
            return false;
        };

        // Tạo mock Service hợp lệ
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService) {
            if ($name == 'Service') {
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Address only accepts letters, space & number', $resp->msg, 'resp->msg should contain error message about invalid address');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 305, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
        // - Không có xử lý exception chi tiết khi kiểm tra address
    }

    /**
     * PATIENTBOOKINGS_017
     * Kiểm tra phương thức save() với appointment_time không hợp lệ
     * Test save() method with invalid appointment_time
     */
    public function testSaveWithInvalidAppointmentTime()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '0123456789',
                'name' => 'Test Patient',
                'appointment_time' => '25:00', // Appointment time không hợp lệ (giờ > 24)
                'appointment_date' => '2023-05-15',
                'service_id' => 1,
                'doctor_id' => 1,
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Ghi đè phương thức isVietnameseName để trả về true cho booking_name và name hợp lệ
        $controller->isVietnameseName = function($name) {
            return true;
        };

        // Ghi đè phương thức isBirthdayValid để trả về chuỗi rỗng cho birthday hợp lệ
        $controller->isBirthdayValid = function($birthday) {
            return "";
        };

        // Ghi đè phương thức isAddress để trả về true cho address hợp lệ
        $controller->isAddress = function($address) {
            return true;
        };

        // Ghi đè phương thức isAppointmentTimeValid để trả về thông báo lỗi cho appointment_time không hợp lệ
        $controller->isAppointmentTimeValid = function($datetime) {
            return "Appointment time is not valid. Format must be YYYY-MM-DD HH:MM";
        };

        // Tạo mock Service hợp lệ
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService) {
            if ($name == 'Service') {
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Appointment time is not valid. Format must be YYYY-MM-DD HH:MM', $resp->msg, 'resp->msg should contain error message about invalid appointment time');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 316, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
        // - Không có xử lý exception chi tiết khi kiểm tra appointment_time
    }

    /**
     * PATIENTBOOKINGS_018
     * Kiểm tra phương thức save() với status không hợp lệ
     * Test save() method with invalid status
     */
    public function testSaveWithInvalidStatus()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '0123456789',
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 1,
                'doctor_id' => 1,
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason',
                'status' => 'invalid_status' // Status không hợp lệ
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Ghi đè phương thức isVietnameseName để trả về true cho booking_name và name hợp lệ
        $controller->isVietnameseName = function($name) {
            return true;
        };

        // Ghi đè phương thức isBirthdayValid để trả về chuỗi rỗng cho birthday hợp lệ
        $controller->isBirthdayValid = function($birthday) {
            return "";
        };

        // Ghi đè phương thức isAddress để trả về true cho address hợp lệ
        $controller->isAddress = function($address) {
            return true;
        };

        // Ghi đè phương thức isAppointmentTimeValid để trả về chuỗi rỗng cho appointment_time hợp lệ
        $controller->isAppointmentTimeValid = function($datetime) {
            return "";
        };

        // Tạo mock Service hợp lệ
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService) {
            if ($name == 'Service') {
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Status is not valid. There are 3 values: processing, completed & cancelled', $resp->msg, 'resp->msg should contain error message about invalid status');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 329, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
        // - Không có xử lý exception chi tiết khi kiểm tra status
    }

    /**
     * PATIENTBOOKINGS_019
     * Kiểm tra phương thức save() với lịch hẹn trùng
     * Test save() method with duplicate booking
     */
    public function testSaveWithDuplicateBooking()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '0123456789',
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 1,
                'doctor_id' => 1,
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Ghi đè phương thức isVietnameseName để trả về true cho booking_name và name hợp lệ
        $controller->isVietnameseName = function($name) {
            return true;
        };

        // Ghi đè phương thức isBirthdayValid để trả về chuỗi rỗng cho birthday hợp lệ
        $controller->isBirthdayValid = function($birthday) {
            return "";
        };

        // Ghi đè phương thức isAddress để trả về true cho address hợp lệ
        $controller->isAddress = function($address) {
            return true;
        };

        // Ghi đè phương thức isAppointmentTimeValid để trả về chuỗi rỗng cho appointment_time hợp lệ
        $controller->isAppointmentTimeValid = function($datetime) {
            return "";
        };

        // Tạo mock Service hợp lệ
        $mockService = new MockModel();
        $mockService->set('id', 1);
        $mockService->set('name', 'Test Service');

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockService) {
            if ($name == 'Service') {
                return $mockService;
            }
            return new MockModel();
        };

        // Ghi đè phương thức DB::table để trả về mock query builder
        $mockQueryBuilder = new stdClass();
        $mockQueryBuilder->where = function() use (&$mockQueryBuilder) { return $mockQueryBuilder; };
        $mockQueryBuilder->get = function() {
            // Trả về một mảng có một phần tử để giả lập lịch hẹn trùng
            return [
                (object)[
                    'id' => 1,
                    'doctor_id' => 1,
                    'service_id' => 1,
                    'patient_id' => 1,
                    'booking_name' => 'Existing Booking',
                    'appointment_time' => '09:00',
                    'appointment_date' => '2023-05-15'
                ]
            ];
        };

        $controller->DB = new stdClass();
        $controller->DB->table = function() use ($mockQueryBuilder) {
            return $mockQueryBuilder;
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Bác sĩ đã có lịch hẹn vào thời gian này. Vui lòng chọn thời gian khác !', $resp->msg, 'resp->msg should contain error message about duplicate booking');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 234, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra đầy đủ các trường bắt buộc trước khi xử lý
        // - Không có xử lý exception chi tiết khi kiểm tra lịch hẹn trùng
    }

    /**
     * PATIENTBOOKINGS_020
     * Kiểm tra phương thức save() với doctor không tồn tại
     * Test save() method with non-existent doctor
     */
    public function testSaveWithNonExistentDoctor()
    {
        // Tạo controller thật
        $controller = new PatientBookingsController();

        // Tạo mock AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role
        $mockAuthUser->set('name', 'Test Patient');
        $mockAuthUser->set('phone', '0123456789');

        // Ghi đè phương thức getVariable
        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ghi đè phương thức Input::post để trả về giá trị cho các trường
        $controller->getInputPost = function($key) {
            $values = [
                'booking_name' => 'Test Booking',
                'booking_phone' => '0123456789',
                'name' => 'Test Patient',
                'appointment_time' => '09:00',
                'appointment_date' => '2023-05-15',
                'service_id' => 0,
                'doctor_id' => 999, // Doctor ID không tồn tại
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'reason' => 'Test Reason'
            ];
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Ghi đè phương thức isVietnameseName để trả về true cho booking_name và name hợp lệ
        $controller->isVietnameseName = function($name) {
            return true;
        };

        // Ghi đè phương thức isBirthdayValid để trả về chuỗi rỗng cho birthday hợp lệ
        $controller->isBirthdayValid = function($birthday) {
            return "";
        };

        // Ghi đè phương thức isAddress để trả về true cho address hợp lệ
        $controller->isAddress = function($address) {
            return true;
        };

        // Ghi đè phương thức isAppointmentTimeValid để trả về chuỗi rỗng cho appointment_time hợp lệ
        $controller->isAppointmentTimeValid = function($datetime) {
            return "";
        };

        // Tạo mock Doctor không tồn tại
        $mockDoctor = new MockModel();
        $mockDoctor->setAvailable(false);

        // Ghi đè phương thức model để trả về mock model
        $controller->model = function($name, $id = 0) use ($mockDoctor) {
            if ($name == 'Doctor') {
                return $mockDoctor;
            }
            return new MockModel();
        };

        // Ghi đè phương thức DB::table để trả về mock query builder
        $mockQueryBuilder = new stdClass();
        $mockQueryBuilder->where = function() use (&$mockQueryBuilder) { return $mockQueryBuilder; };
        $mockQueryBuilder->get = function() { return []; }; // Trả về mảng rỗng để không có lịch hẹn trùng

        $controller->DB = new stdClass();
        $controller->DB->table = function() use ($mockQueryBuilder) {
            return $mockQueryBuilder;
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

        // Gọi phương thức save() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('save');
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
        $this->assertEquals('Doctor is not available', $resp->msg, 'resp->msg should contain error message about doctor not available');

        // Ghi chú: Test này phát hiện lỗi trong code gốc:
        // - Không có return sau khi gọi jsonecho() ở dòng 248, có thể dẫn đến việc code tiếp tục thực thi
        // - Không kiểm tra $Doctor tồn tại trước khi truy cập thuộc tính
        // - Không có xử lý exception chi tiết khi kiểm tra doctor
    }
}
