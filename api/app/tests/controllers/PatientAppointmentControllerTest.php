<?php
/**
 * Test cho PatientAppointmentController
 *
 * Class: PatientAppointmentControllerTest
 * File: api/app/tests/controllers/PatientAppointmentControllerTest.php
 *
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

// Include các mock objects
require_once __DIR__ . '/../mocks/MockAuthUser.php';
require_once __DIR__ . '/../mocks/MockDoctor.php';

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
 * Mock cho lớp ControllerMock
 */
class ControllerMock
{
    /**
     * @var array Các mock model
     */
    public static $modelMocks = [];

    /**
     * @var callable Phương thức model
     */
    public static $modelMethod;

    /**
     * Phương thức model
     */
    public static function model($name, $id = 0)
    {
        if (isset(self::$modelMethod) && is_callable(self::$modelMethod)) {
            $func = self::$modelMethod;
            return $func($name, $id);
        }

        if (isset(self::$modelMocks[$name])) {
            return self::$modelMocks[$name];
        }

        return new MockModel();
    }
}

/**
 * Mock cho lớp Input
 */
class InputMockPatientAppointment
{
    /**
     * @var callable Mock cho phương thức method
     */
    public static $methodMock;

    /**
     * Phương thức method
     */
    public static function method()
    {
        if (isset(self::$methodMock) && is_callable(self::$methodMock)) {
            $func = self::$methodMock;
            return $func();
        }

        return 'GET';
    }
}

/**
 * Lớp test cho PatientAppointmentController
 */
class PatientAppointmentControllerTest extends ControllerTestCase
{
    /**
     * @var PatientAppointmentController Controller instance
     */
    protected $controller;

    /**
     * @var MockAuthUser Mock AuthUser
     */
    protected $mockAuthUser;

    /**
     * @var stdClass Mock Route
     */
    protected $mockRoute;

    /**
     * @var bool Flag để kiểm tra xem header() có được gọi không
     */
    protected $headerCalled = false;

    /**
     * @var string Header được gọi
     */
    protected $headerCalledWith = '';

    /**
     * @var bool Flag để kiểm tra xem exit() có được gọi không
     */
    protected $exitCalled = false;

    /**
     * Set up test environment before each test
     * Thiết lập môi trường test trước mỗi test
     */
    protected function setUp()
    {
        parent::setUp();

        // Tạo mock AuthUser
        $this->mockAuthUser = new MockAuthUser();
        $this->mockAuthUser->set('id', 1);
        $this->mockAuthUser->set('role', null); // null = patient, 'doctor' = doctor
        $this->mockAuthUser->set('name', 'Test Patient');

        // Tạo mock Route
        $this->mockRoute = new stdClass();
        $this->mockRoute->params = new stdClass();
        $this->mockRoute->params->id = 1;

        // Khởi tạo controller
        $this->controller = $this->createController('PatientAppointmentController');
    }

    /**
     * PATIENTAPPOINTMENT_001
     * Kiểm tra khi người dùng không đăng nhập
     * Test when user is not logged in
     */
    public function testProcessWithNoAuthUser()
    {
        // Tạo controller thật
        $controller = new PatientAppointmentController();

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
        // Lỗi này được phát hiện vì test đã thiết lập $AuthUser = null
    }

    /**
     * PATIENTAPPOINTMENT_002
     * Kiểm tra khi người dùng là doctor
     * Test when user is a doctor
     */
    public function testProcessWithDoctorRole()
    {
        // Thiết lập AuthUser là doctor
        $mockAuthUser = new MockAuthUser();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', 'doctor'); // Thiết lập role là doctor

        // Thiết lập mock cho controller
        $controller = $this->getMockBuilder('PatientAppointmentController')
            ->setMethods(['getVariable', 'jsonecho'])
            ->getMock();

        $controller->method('getVariable')
            ->will($this->returnCallback(function($name) use ($mockAuthUser) {
                if ($name === 'AuthUser') {
                    return $mockAuthUser;
                } elseif ($name === 'Route') {
                    return $this->mockRoute;
                }
                return null;
            }));

        // Tạo đối tượng response mong đợi
        $expectedResponse = new stdClass();
        $expectedResponse->result = 0;
        $expectedResponse->msg = 'This function is used by PATIENT ONLY!';

        // Thiết lập mock cho jsonecho - kiểm tra xem jsonecho được gọi với đúng tham số không
        $controller->expects($this->once())
            ->method('jsonecho')
            ->with($this->callback(function($response) {
                return $response->result === 0 &&
                       $response->msg === 'This function is used by PATIENT ONLY!';
            }));

        // Gọi phương thức process()
        $controller->process();

        // Lỗi trong code gốc: Điều kiện if($AuthUser->get("role")) không chính xác
        // Nó sẽ đúng với bất kỳ giá trị nào khác null, empty string, 0, v.v.
        // Nên sử dụng if($AuthUser->get("role") === "doctor") để kiểm tra chính xác
    }

    /**
     * PATIENTAPPOINTMENT_003
     * Kiểm tra khi request method là GET
     * Test when request method is GET
     */
    public function testProcessWithGetMethod()
    {
        // Thiết lập AuthUser là patient (không có role)
        $mockAuthUser = new MockAuthUser();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Tạo một controller mới với phương thức getById() được mock
        $controller = $this->getMockBuilder('PatientAppointmentController')
            ->setMethods(['getVariable', 'getById', 'getInputMethod'])
            ->getMock();

        // Thiết lập mock cho getVariable
        $controller->method('getVariable')
            ->will($this->returnCallback(function($name) use ($mockAuthUser) {
                if ($name === 'AuthUser') {
                    return $mockAuthUser;
                } elseif ($name === 'Route') {
                    return $this->mockRoute;
                }
                return null;
            }));

        // Thiết lập mock cho getInputMethod để trả về 'GET'
        $controller->method('getInputMethod')
            ->willReturn('GET');

        // Thiết lập mock cho getById - kiểm tra xem getById được gọi không
        $controller->expects($this->once())
            ->method('getById');

        // Gọi phương thức process()
        $controller->process();

        // Lỗi trong code gốc: Không có return sau khi gọi $this->jsonecho() ở dòng 24
        // Điều này có thể dẫn đến việc code tiếp tục thực thi ngay cả khi đã gửi response
    }

    /**
     * PATIENTAPPOINTMENT_004
     * Kiểm tra phương thức getById() khi thiếu ID
     * Test getById() method with missing ID
     */
    public function testGetByIdWithMissingId()
    {
        // Tạo mock Route không có ID
        $mockRoute = new stdClass();
        $mockRoute->params = new stdClass();
        // Không thiết lập id

        // Thiết lập mock cho controller
        $controller = $this->getMockBuilder('PatientAppointmentController')
            ->setMethods(['getVariable', 'jsonecho'])
            ->getMock();

        $controller->method('getVariable')
            ->will($this->returnCallback(function($name) use ($mockRoute) {
                if ($name === 'AuthUser') {
                    return $this->mockAuthUser;
                } elseif ($name === 'Route') {
                    return $mockRoute; // Route không có ID
                }
                return null;
            }));

        // Tạo đối tượng response mong đợi
        $expectedResponse = new stdClass();
        $expectedResponse->result = 0;
        $expectedResponse->msg = 'ID is required !';

        // Thiết lập mock cho jsonecho - kiểm tra xem jsonecho được gọi với đúng tham số không
        $controller->expects($this->once())
            ->method('jsonecho')
            ->with($this->callback(function($response) {
                return $response->result === 0 &&
                       $response->msg === 'ID is required !';
            }));

        // Gọi phương thức getById() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getById');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Lỗi trong code gốc: Không kiểm tra $Route và $Route->params tồn tại trước khi truy cập
        // Nên sử dụng isset($Route) && isset($Route->params) && isset($Route->params->id)
    }



    /**
     * PATIENTAPPOINTMENT_005
     * Kiểm tra phương thức getById() khi appointment không tồn tại
     * Test getById() method with non-existent appointment
     */
    public function testGetByIdWithNonExistentAppointment()
    {
        // Tạo mock Appointment không tồn tại
        $mockAppointment = new MockModel();
        $mockAppointment->setAvailable(false);

        // Thiết lập mock cho controller
        $controller = $this->getMockBuilder('PatientAppointmentController')
            ->setMethods(['getVariable', 'jsonecho', 'model'])
            ->getMock();

        $controller->method('getVariable')
            ->will($this->returnCallback(function($name) {
                if ($name === 'AuthUser') {
                    return $this->mockAuthUser;
                } elseif ($name === 'Route') {
                    return $this->mockRoute; // Route có ID = 1
                }
                return null;
            }));

        // Thiết lập mock cho model để trả về mock Appointment
        $controller->method('model')
            ->with($this->equalTo('Appointment'), $this->equalTo(1))
            ->willReturn($mockAppointment);

        // Tạo đối tượng response mong đợi
        $expectedResponse = new stdClass();
        $expectedResponse->result = 0;
        $expectedResponse->msg = 'Appointment is not available';

        // Thiết lập mock cho jsonecho - kiểm tra xem jsonecho được gọi với đúng tham số không
        $controller->expects($this->once())
            ->method('jsonecho')
            ->with($this->callback(function($response) {
                return $response->result === 0 &&
                       $response->msg === 'Appointment is not available';
            }));

        // Gọi phương thức getById() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getById');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Lỗi trong code gốc: Không kiểm tra $Appointment tồn tại trước khi gọi isAvailable()
        // Nên sử dụng isset($Appointment) && $Appointment->isAvailable()
    }

    /**
     * PATIENTAPPOINTMENT_006
     * Kiểm tra phương thức getById() khi appointment không thuộc về patient
     * Test getById() method when appointment does not belong to patient
     */
    public function testGetByIdWithAppointmentNotBelongToPatient()
    {
        // Tạo mock Appointment với patient_id khác với AuthUser id
        $mockAppointment = new MockModel();
        $mockAppointment->set('id', 1);
        $mockAppointment->set('patient_id', 999); // Khác với AuthUser id (1)
        $mockAppointment->set('doctor_id', 2);
        $mockAppointment->setAvailable(true);

        // Thiết lập mock cho controller
        $controller = $this->getMockBuilder('PatientAppointmentController')
            ->setMethods(['getVariable', 'jsonecho', 'model'])
            ->getMock();

        $controller->method('getVariable')
            ->will($this->returnCallback(function($name) {
                if ($name === 'AuthUser') {
                    return $this->mockAuthUser; // AuthUser có id = 1
                } elseif ($name === 'Route') {
                    return $this->mockRoute; // Route có ID = 1
                }
                return null;
            }));

        // Thiết lập mock cho model để trả về mock Appointment
        $controller->method('model')
            ->with($this->equalTo('Appointment'), $this->equalTo(1))
            ->willReturn($mockAppointment);

        // Tạo đối tượng response mong đợi
        $expectedResponse = new stdClass();
        $expectedResponse->result = 0;
        $expectedResponse->msg = 'This appointment does not belong you';

        // Thiết lập mock cho jsonecho - kiểm tra xem jsonecho được gọi với đúng tham số không
        $controller->expects($this->once())
            ->method('jsonecho')
            ->with($this->callback(function($response) {
                return $response->result === 0 &&
                       $response->msg === 'This appointment does not belong you';
            }));

        // Gọi phương thức getById() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getById');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Lỗi trong code gốc: Không kiểm tra $Appointment->get("patient_id") và $AuthUser->get("id") trả về null
        // Nên sử dụng $Appointment->get("patient_id") !== null && $AuthUser->get("id") !== null && $Appointment->get("patient_id") == $AuthUser->get("id")
    }

    /**
     * PATIENTAPPOINTMENT_007
     * Kiểm tra phương thức getById() với dữ liệu hợp lệ
     * Test getById() method with valid data
     */
    public function testGetByIdWithValidData()
    {
        // Tạo mock Appointment với dữ liệu hợp lệ
        $mockAppointment = new MockModel();
        $mockAppointment->set('id', 1);
        $mockAppointment->set('patient_id', 1); // Khớp với AuthUser id (1)
        $mockAppointment->set('doctor_id', 2);
        $mockAppointment->set('date', '2023-05-15');
        $mockAppointment->set('numerical_order', 1);
        $mockAppointment->set('position', 2);
        $mockAppointment->set('patient_name', 'Test Patient');
        $mockAppointment->set('patient_phone', '0123456789');
        $mockAppointment->set('patient_birthday', '1990-01-01');
        $mockAppointment->set('patient_reason', 'Test reason');
        $mockAppointment->set('appointment_time', '09:00');
        $mockAppointment->set('status', 'pending');
        $mockAppointment->set('create_at', '2023-05-10 10:00:00');
        $mockAppointment->set('update_at', '2023-05-10 10:00:00');
        $mockAppointment->setAvailable(true);

        // Tạo mock Doctor
        $mockDoctor = new MockModel();
        $mockDoctor->set('id', 2);
        $mockDoctor->set('email', 'doctor@example.com');
        $mockDoctor->set('phone', '0987654321');
        $mockDoctor->set('name', 'Test Doctor');
        $mockDoctor->set('description', 'Test description');
        $mockDoctor->set('price', 100000);
        $mockDoctor->set('role', 'doctor');
        $mockDoctor->set('avatar', 'avatar.jpg');
        $mockDoctor->set('active', 1);
        $mockDoctor->set('speciality_id', 1);
        $mockDoctor->set('room_id', 1);
        $mockDoctor->set('create_at', '2023-01-01 00:00:00');
        $mockDoctor->set('update_at', '2023-01-01 00:00:00');

        // Tạo mock Speciality
        $mockSpeciality = new MockModel();
        $mockSpeciality->set('id', 1);
        $mockSpeciality->set('name', 'Test Speciality');
        $mockSpeciality->set('description', 'Test speciality description');

        // Tạo mock Room
        $mockRoom = new MockModel();
        $mockRoom->set('id', 1);
        $mockRoom->set('name', 'Test Room');
        $mockRoom->set('location', 'Test location');

        // Thiết lập mock cho model
        $controller = $this->getMockBuilder('PatientAppointmentController')
            ->setMethods(['getVariable', 'jsonecho', 'model'])
            ->getMock();

        $controller->method('model')
            ->will($this->returnCallback(function($name, $id = 0) use ($mockAppointment, $mockDoctor, $mockSpeciality, $mockRoom) {
                if ($name === 'Appointment' && $id === 1) {
                    return $mockAppointment;
                } elseif ($name === 'Doctor' && $id === 2) {
                    return $mockDoctor;
                } elseif ($name === 'Speciality' && $id === 1) {
                    return $mockSpeciality;
                } elseif ($name === 'Room' && $id === 1) {
                    return $mockRoom;
                }
                return null;
            }));

        // Đã tạo controller ở trên, không cần tạo lại

        $controller->method('getVariable')
            ->will($this->returnCallback(function($name) {
                if ($name === 'AuthUser') {
                    return $this->mockAuthUser;
                } elseif ($name === 'Route') {
                    return $this->mockRoute;
                }
                return null;
            }));

        // Thiết lập mock cho jsonecho - kiểm tra xem jsonecho được gọi với đúng tham số không
        $controller->expects($this->once())
            ->method('jsonecho')
            ->with($this->callback(function($response) {
                // Kiểm tra cấu trúc cơ bản của response
                if ($response->result !== 1 || $response->msg !== 'Action successfully !') {
                    return false;
                }

                // Kiểm tra các thuộc tính cơ bản của appointment
                $requiredFields = [
                    'id', 'date', 'numerical_order', 'position', 'patient_id',
                    'patient_name', 'patient_phone', 'patient_birthday', 'patient_reason',
                    'appointment_time', 'status', 'create_at', 'update_at',
                    'doctor', 'speciality', 'room'
                ];

                foreach ($requiredFields as $field) {
                    if (!property_exists($response->data, $field)) {
                        return false;
                    }
                }

                // Kiểm tra các thuộc tính của doctor
                $doctorFields = ['id', 'email', 'phone', 'name'];
                foreach ($doctorFields as $field) {
                    if (!property_exists($response->data->doctor, $field)) {
                        return false;
                    }
                }

                // Kiểm tra các thuộc tính của speciality
                $specialityFields = ['id', 'name'];
                foreach ($specialityFields as $field) {
                    if (!property_exists($response->data->speciality, $field)) {
                        return false;
                    }
                }

                // Kiểm tra các thuộc tính của room
                $roomFields = ['id', 'name'];
                foreach ($roomFields as $field) {
                    if (!property_exists($response->data->room, $field)) {
                        return false;
                    }
                }

                return true;
            }));

        // Gọi phương thức getById() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getById');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Lỗi trong code gốc: Không kiểm tra $Doctor, $Speciality và $Room tồn tại trước khi truy cập
        // Nên sử dụng isset($Doctor) && isset($Speciality) && isset($Room) trước khi truy cập thuộc tính
    }

    /**
     * PATIENTAPPOINTMENT_008
     * Kiểm tra phương thức getById() khi xảy ra exception
     * Test getById() method when exception occurs
     */
    public function testGetByIdWithException()
    {
        // Thiết lập mock cho controller
        $controller = $this->getMockBuilder('PatientAppointmentController')
            ->setMethods(['getVariable', 'jsonecho', 'model'])
            ->getMock();

        $controller->method('getVariable')
            ->will($this->returnCallback(function($name) {
                if ($name === 'AuthUser') {
                    return $this->mockAuthUser; // AuthUser có id = 1
                } elseif ($name === 'Route') {
                    return $this->mockRoute; // Route có ID = 1
                }
                return null;
            }));

        // Thiết lập mock cho model để ném exception
        $controller->method('model')
            ->will($this->throwException(new Exception('Test exception')));

        // Tạo đối tượng response mong đợi
        $expectedResponse = new stdClass();
        $expectedResponse->result = 0;
        $expectedResponse->msg = 'Test exception';

        // Thiết lập mock cho jsonecho - kiểm tra xem jsonecho được gọi với đúng tham số không
        $controller->expects($this->once())
            ->method('jsonecho')
            ->with($this->callback(function($response) {
                return $response->result === 0 &&
                       $response->msg === 'Test exception';
            }));

        // Gọi phương thức getById() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getById');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Lỗi trong code gốc: Không có xử lý exception chi tiết
        // Nên ghi log hoặc xử lý lỗi một cách chi tiết hơn
    }
}
