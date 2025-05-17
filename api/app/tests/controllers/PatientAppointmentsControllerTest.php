<?php
/**
 * Test cho PatientAppointmentsController
 *
 * Class: PatientAppointmentsControllerTest
 * File: api/app/tests/controllers/PatientAppointmentsControllerTest.php
 *
 */

// Định nghĩa các hằng số cần thiết cho test
if (!defined('EC_SALT')) {
    define('EC_SALT', 'test_salt_for_unit_tests');
}
if (!defined('APPURL')) {
    define('APPURL', 'http://localhost/app');
}

// Định nghĩa các hằng số cho tên bảng
if (!defined('TABLE_PREFIX')) {
    define('TABLE_PREFIX', 'tn_');
}
if (!defined('TABLE_APPOINTMENTS')) {
    define('TABLE_APPOINTMENTS', 'booking');
}
if (!defined('TABLE_DOCTORS')) {
    define('TABLE_DOCTORS', 'doctors');
}
if (!defined('TABLE_SPECIALITIES')) {
    define('TABLE_SPECIALITIES', 'specialities');
}
if (!defined('TABLE_ROOMS')) {
    define('TABLE_ROOMS', 'rooms');
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
 * Mock cho lớp DB
 */
class MockDB
{
    public static $queryResult = [];
    public static $queryBuilder = null;

    /**
     * Reset mock DB
     */
    public static function reset()
    {
        self::$queryResult = [];
        self::$queryBuilder = null;
    }

    /**
     * Thiết lập kết quả truy vấn
     */
    public static function setQueryResult($result)
    {
        self::$queryResult = $result;
    }

    /**
     * Phương thức table
     */
    public static function table($table)
    {
        self::$queryBuilder = new MockQueryBuilder($table);
        return self::$queryBuilder;
    }

    /**
     * Phương thức raw
     */
    public static function raw($raw)
    {
        return $raw;
    }
}

/**
 * Mock cho lớp QueryBuilder
 */
class MockQueryBuilder
{
    private $table;
    private $conditions = [];
    private $joins = [];
    private $selects = [];
    private $orders = [];
    private $limit = null;
    private $offset = null;

    /**
     * Constructor
     */
    public function __construct($table)
    {
        $this->table = $table;
    }

    /**
     * Phương thức where
     */
    public function where($column, $operator = null, $value = null)
    {
        if (is_callable($column)) {
            $this->conditions[] = $column;
        } else {
            $this->conditions[] = [$column, $operator, $value];
        }
        return $this;
    }

    /**
     * Phương thức leftJoin
     */
    public function leftJoin($table, $first, $operator, $second)
    {
        $this->joins[] = ['left', $table, $first, $operator, $second];
        return $this;
    }

    /**
     * Phương thức select
     */
    public function select($columns)
    {
        $this->selects = $columns;
        return $this;
    }

    /**
     * Phương thức orderBy
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = [$column, $direction];
        return $this;
    }

    /**
     * Phương thức limit
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Phương thức offset
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Phương thức get
     */
    public function get()
    {
        return MockDB::$queryResult;
    }
}

/**
 * Mock cho lớp Input
 */
class InputMockPatientAppointments
{
    /**
     * @var array Dữ liệu GET
     */
    public static $getData = [];

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

    /**
     * Phương thức get
     */
    public static function get($key = null)
    {
        if ($key === null) {
            return self::$getData;
        }

        return isset(self::$getData[$key]) ? self::$getData[$key] : null;
    }

    /**
     * Thiết lập dữ liệu GET
     */
    public static function setGetData($data)
    {
        self::$getData = $data;
    }
}

/**
 * Lớp test cho PatientAppointmentsController
 */
class PatientAppointmentsControllerTest extends ControllerTestCase
{
    /**
     * @var PatientAppointmentsController Controller instance
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

        // Reset mock DB
        MockDB::reset();

        // Khởi tạo controller
        $this->controller = $this->createController('PatientAppointmentsController');
    }

    /**
     * PATIENTAPPOINTMENTS_001
     * Kiểm tra khi người dùng không đăng nhập
     * Test when user is not logged in
     */
    public function testProcessWithNoAuthUser()
    {
        // Tạo controller thật
        $controller = new PatientAppointmentsController();

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
     * PATIENTAPPOINTMENTS_002
     * Kiểm tra khi người dùng là doctor
     * Test when user is a doctor
     */
    public function testProcessWithDoctorRole()
    {
        // Thiết lập AuthUser là doctor
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', 'doctor'); // Thiết lập role là doctor

        // Thiết lập mock cho controller
        $controller = $this->getMockBuilder('PatientAppointmentsController')
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
     * PATIENTAPPOINTMENTS_003
     * Kiểm tra khi request method là GET
     * Test when request method is GET
     */
    public function testProcessWithGetMethod()
    {
        // Thiết lập AuthUser là patient (không có role)
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', null); // Patient không có role

        // Tạo một controller mới với phương thức getAll() được mock
        $controller = $this->getMockBuilder('PatientAppointmentsController')
            ->setMethods(['getVariable', 'getAll', 'getInputMethod'])
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

        // Thiết lập mock cho getAll - kiểm tra xem getAll được gọi không
        $controller->expects($this->once())
            ->method('getAll');

        // Gọi phương thức process()
        $controller->process();

        // Lỗi trong code gốc: Không có return sau khi gọi $this->jsonecho() ở dòng 24
        // Điều này có thể dẫn đến việc code tiếp tục thực thi ngay cả khi đã gửi response
    }

    /**
     * PATIENTAPPOINTMENTS_004
     * Kiểm tra phương thức getAll() với dữ liệu hợp lệ
     * Test getAll() method with valid data
     */
    public function testGetAllWithValidData()
    {
        // Tạo dữ liệu mẫu cho kết quả truy vấn
        $mockQueryResult = [
            (object)[
                'id' => 1,
                'date' => '2023-05-15',
                'numerical_order' => 1,
                'position' => 2,
                'patient_id' => 1,
                'patient_name' => 'Test Patient',
                'patient_phone' => '0123456789',
                'patient_birthday' => '1990-01-01',
                'patient_reason' => 'Test reason',
                'appointment_time' => '09:00',
                'status' => 'pending',
                'create_at' => '2023-05-10 10:00:00',
                'update_at' => '2023-05-10 10:00:00',
                'doctor_id' => 2,
                'doctor_email' => 'doctor@example.com',
                'doctor_phone' => '0987654321',
                'doctor_name' => 'Test Doctor',
                'doctor_description' => 'Test description',
                'doctor_price' => 100000,
                'doctor_role' => 'doctor',
                'doctor_avatar' => 'avatar.jpg',
                'doctor_active' => 1,
                'doctor_create_at' => '2023-01-01 00:00:00',
                'doctor_update_at' => '2023-01-01 00:00:00',
                'speciality_id' => 1,
                'speciality_name' => 'Test Speciality',
                'speciality_description' => 'Test speciality description',
                'room_id' => 1,
                'room_name' => 'Test Room',
                'room_location' => 'Test location'
            ]
        ];

        // Thiết lập mock cho DB::table để trả về mock query builder
        $mockDB = $this->getMockBuilder('DB')
            ->setMethods(['table'])
            ->getMock();

        // Thiết lập kết quả truy vấn
        MockDB::setQueryResult($mockQueryResult);

        // Thiết lập mock cho controller
        $controller = $this->getMockBuilder('PatientAppointmentsController')
            ->setMethods(['getVariable', 'jsonecho'])
            ->getMock();

        $controller->method('getVariable')
            ->will($this->returnCallback(function($name) {
                if ($name === 'AuthUser') {
                    return $this->mockAuthUser; // AuthUser có id = 1
                } elseif ($name === 'Route') {
                    return $this->mockRoute;
                }
                return null;
            }));

        // Thiết lập mock cho Input::get
        InputMockPatientAppointments::setGetData([
            'order' => ['column' => 'id', 'dir' => 'desc'],
            'search' => '',
            'length' => 5,
            'start' => 0,
            'doctor_id' => 0,
            'room_id' => 0,
            'date' => '',
            'status' => '',
            'speciality_id' => 0
        ]);

        // Thiết lập mock cho jsonecho - kiểm tra xem jsonecho được gọi với đúng tham số không
        $controller->expects($this->once())
            ->method('jsonecho')
            ->with($this->callback(function($response) {
                return $response->result === 1 &&
                       $response->quantity === 1 &&
                       is_array($response->data) &&
                       count($response->data) === 1;
            }));

        // Gọi phương thức getAll() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getAll');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Lỗi trong code gốc: Không kiểm tra $AuthUser tồn tại trước khi truy cập
        // Nên sử dụng if ($AuthUser && $AuthUser->get("id"))
    }

    /**
     * PATIENTAPPOINTMENTS_005
     * Kiểm tra phương thức getAll() khi xảy ra exception
     * Test getAll() method when exception occurs
     */
    public function testGetAllWithException()
    {
        // Thiết lập mock cho DB::table để ném exception
        $mockDB = $this->getMockBuilder('DB')
            ->setMethods(['table'])
            ->getMock();

        $mockDB->method('table')
            ->will($this->throwException(new Exception('Test exception')));

        // Thiết lập mock cho controller
        $controller = $this->getMockBuilder('PatientAppointmentsController')
            ->setMethods(['getVariable', 'jsonecho'])
            ->getMock();

        $controller->method('getVariable')
            ->will($this->returnCallback(function($name) {
                if ($name === 'AuthUser') {
                    return $this->mockAuthUser; // AuthUser có id = 1
                } elseif ($name === 'Route') {
                    return $this->mockRoute;
                }
                return null;
            }));

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

        // Gọi phương thức getAll() thông qua reflection
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getAll');
        $method->setAccessible(true);
        $method->invoke($controller);

        // Lỗi trong code gốc: Không có xử lý exception chi tiết
        // Nên ghi log hoặc xử lý lỗi một cách chi tiết hơn
    }
}
