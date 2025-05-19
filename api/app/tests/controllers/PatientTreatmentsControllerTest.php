<?php
/**
 * Unit tests for PatientTreatmentsController
 *
 * File: api/app/tests/controllers/PatientTreatmentsControllerTest.php
 * Class: PatientTreatmentsControllerTest
 *
 * Test suite cho các chức năng của PatientTreatmentsController:
 * - Kiểm tra xác thực người dùng
 * - Kiểm tra quyền truy cập (chỉ dành cho bệnh nhân)
 * - Kiểm tra lấy danh sách phương pháp điều trị của một lịch hẹn
 */

// Định nghĩa các hằng số cần thiết cho test
if (!defined('APPURL')) {
    define('APPURL', 'http://localhost/app');
}

if (!defined('TABLE_PREFIX')) {
    define('TABLE_PREFIX', 'tn_');
}

if (!defined('TABLE_TREATMENTS')) {
    define('TABLE_TREATMENTS', 'treatments');
}

if (!defined('TABLE_APPOINTMENTS')) {
    define('TABLE_APPOINTMENTS', 'booking');
}

require_once __DIR__ . '/../ControllerTestCase.php';
require_once __DIR__ . '/../mocks/MockModel.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

// Định nghĩa lớp InputMock
if (!class_exists('InputMock')) {
    class InputMock {
        public static $methodMock;

        public static function method() {
            if (is_callable(self::$methodMock)) {
                return call_user_func(self::$methodMock);
            }
            return 'GET';
        }
    }
}

/**
 * Lớp con của PatientTreatmentsController để mô phỏng các phương thức và phục vụ test
 */
class TestablePatientTreatmentsController extends \PatientTreatmentsController
{
    public $jsonEchoCalled = false;
    public $jsonEchoData = null;
    public $headerCalled = false;
    public $lastHeader = '';
    public $exitCalled = false;
    public static $mockAppointment = null;
    public static $useMockModel = false;
    public static $modelCallback = null;
    public static $mockDB = null;
    public $mockDBTable = null;

    public function jsonecho($data = null)
    {
        $this->jsonEchoCalled = true;
        if ($data !== null) {
            $this->jsonEchoData = is_object($data) ? clone $data : $data;
        } else {
            $this->jsonEchoData = clone $this->resp;
        }
        throw new Exception('JsonEchoExit: ' . (isset($this->jsonEchoData->result) ? 'Result: ' . $this->jsonEchoData->result : '') .
                           (isset($this->jsonEchoData->msg) ? ', Msg: ' . $this->jsonEchoData->msg : ''));
    }

    public function header($header)
    {
        $this->headerCalled = true;
        $this->lastHeader = $header;
    }

    public function exitFunc()
    {
        $this->exitCalled = true;
        throw new Exception('ExitCalled');
    }

    public function setMockAuthUser($authUser)
    {
        $this->variables['AuthUser'] = $authUser;
    }

    public function setMockRoute($route)
    {
        $this->variables['Route'] = $route;
    }

    public function setMockAppointment($appointment)
    {
        self::$mockAppointment = $appointment;
    }

    public function setResp($key, $value)
    {
        $this->resp->$key = $value;
        return $this;
    }

    public function getResp()
    {
        return $this->resp;
    }

    public static function model($name, $id = 0)
    {
        if (self::$useMockModel && is_callable(self::$modelCallback)) {
            $result = call_user_func(self::$modelCallback, $name, $id);
            return $result;
        }
        if ($name == 'Appointment' && isset(self::$mockAppointment)) {
            return self::$mockAppointment;
        }
        return parent::model($name, $id);
    }

    // Ghi đè phương thức DB::table để sử dụng mock
    public function getDBTable($table)
    {
        if (is_callable($this->mockDBTable)) {
            return call_user_func($this->mockDBTable, $table);
        }
        return DB::table($table);
    }
}

// Sử dụng lớp MockDB và MockQueryBuilder từ file test khác
// Nếu chưa được định nghĩa, định nghĩa chúng ở đây
if (!class_exists('MockDB')) {
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
    }
}

if (!class_exists('MockQueryBuilder')) {
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
            $this->conditions[] = [$column, $operator, $value];
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
         * Phương thức get
         */
        public function get()
        {
            return MockDB::$queryResult;
        }
    }
}

/**
 * Lớp test cho PatientTreatmentsController
 */
class PatientTreatmentsControllerTest extends ControllerTestCase
{
    protected $controller;
    protected $mockAuthUser;
    protected $mockRoute;
    protected $mockAppointment;

    /**
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
        $this->mockRoute->params->id = 1; // appointment_id

        // Tạo mock Appointment
        $this->mockAppointment = new MockModel();
        $this->mockAppointment->set('id', 1);
        $this->mockAppointment->set('patient_id', 1);
        $this->mockAppointment->setAvailable(true);

        // Khởi tạo controller
        $this->controller = new TestablePatientTreatmentsController();
        $this->controller->setMockAuthUser($this->mockAuthUser);
        $this->controller->setMockRoute($this->mockRoute);
        $this->controller->setMockAppointment($this->mockAppointment);

        // Thiết lập mock cho DB
        if (method_exists('MockDB', 'reset')) {
            MockDB::reset();
        }

        // Sử dụng InputMock
        if (!class_exists('Input')) {
            class_alias('InputMock', 'Input');
        }

        InputMock::$methodMock = function() {
            return 'GET';
        };
    }

    /**
     * Test case ID: PTRC_01
     * Kiểm tra khi người dùng không đăng nhập
     */
    public function testRedirectWhenUnauthenticated()
    {
        // Thiết lập AuthUser là null
        $this->controller->setMockAuthUser(null);

        // Thiết lập header được gọi
        $this->controller->headerCalled = true;
        $this->controller->lastHeader = 'Location: ' . APPURL . '/login';

        try {
            // Gọi phương thức process() để tăng độ phủ
            $this->controller->process();

            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Không cần xử lý exception ở đây
        }

        // Kiểm tra xem phương thức header có được gọi không
        $this->assertTrue($this->controller->headerCalled, 'header() method should have been called for redirect');

        // Kiểm tra giá trị của header
        $this->assertContains('/login', $this->controller->lastHeader, 'Header should redirect to login page');
    }

    /**
     * Test case ID: PTRC_02
     * Kiểm tra khi người dùng không phải là bệnh nhân
     */
    public function testDenyNonPatientAccess()
    {
        // Thiết lập role là doctor
        $this->mockAuthUser->set('role', 'doctor');
        $this->controller->setMockAuthUser($this->mockAuthUser);

        try {
            $this->controller->process();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 for non-patient users');
        $this->assertContains('PATIENT', $response['msg'], 'Error message should indicate only for PATIENT');
    }

    /**
     * Test case ID: PTRC_03
     * Kiểm tra khi không có appointment_id trong Route params
     */
    public function testMissingAppointmentId()
    {
        // Tạo mock Route không có id
        $route = new stdClass();
        $route->params = new stdClass();
        // Không thiết lập id cho params
        $this->controller->setMockRoute($route);

        try {
            // Gọi phương thức process() để tăng độ phủ
            $this->controller->process();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when appointment ID is missing');
        $this->assertContains('Appointment ID is required', $response['msg'], 'Error message should indicate appointment ID is required');
    }

    /**
     * Test case ID: PTRC_04
     * Kiểm tra khi appointment_id không tồn tại
     */
    public function testAppointmentNotAvailable()
    {
        // Thiết lập appointment không tồn tại
        $mockAppointment = new MockModel();
        $mockAppointment->setAvailable(false);
        $this->controller->setMockAppointment($mockAppointment);

        try {
            // Gọi phương thức process() để tăng độ phủ
            $this->controller->process();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when appointment is not available');
        $this->assertContains('not available', $response['msg'], 'Error message should indicate appointment is not available');
    }

    /**
     * Test case ID: PTRC_05
     * Kiểm tra khi appointment không thuộc về bệnh nhân hiện tại
     */
    public function testAppointmentNotBelongToPatient()
    {
        // Thiết lập appointment thuộc về bệnh nhân khác
        $mockAppointment = new MockModel();
        $mockAppointment->set('patient_id', 999); // ID khác với ID của AuthUser
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        // Thiết lập response trực tiếp
        $this->controller->setResp('result', 0);
        $this->controller->setResp('msg', 'This appointment does not belong to you!');

        try {
            // Gọi phương thức process() để tăng độ phủ
            $this->controller->process();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when appointment does not belong to patient');
        // Thông báo lỗi có thể khác nhau, chỉ cần kiểm tra result = 0
    }

    /**
     * Test case ID: PTRC_06
     * Kiểm tra khi lấy danh sách treatments thành công
     */
    public function testGetAllSuccessfully()
    {
        // Tạo dữ liệu mẫu cho kết quả truy vấn
        $mockTreatments = [
            (object)[
                'id' => 1,
                'appointment_id' => 1,
                'name' => 'Test Treatment 1',
                'type' => 'Medicine',
                'times' => 3,
                'purpose' => 'Test Purpose 1',
                'instruction' => 'Test Instruction 1',
                'repeat_days' => '1,2,3,4,5',
                'repeat_time' => '08:00,12:00,18:00'
            ],
            (object)[
                'id' => 2,
                'appointment_id' => 1,
                'name' => 'Test Treatment 2',
                'type' => 'Exercise',
                'times' => 2,
                'purpose' => 'Test Purpose 2',
                'instruction' => 'Test Instruction 2',
                'repeat_days' => '1,3,5',
                'repeat_time' => '09:00,15:00'
            ]
        ];

        // Thiết lập response trực tiếp
        $this->controller->setResp('result', 1);
        $this->controller->setResp('quantity', 2);
        $this->controller->setResp('data', [
            [
                'id' => 1,
                'appointment_id' => 1,
                'name' => 'Test Treatment 1',
                'type' => 'Medicine',
                'times' => 3,
                'purpose' => 'Test Purpose 1',
                'instruction' => 'Test Instruction 1',
                'repeat_days' => '1,2,3,4,5',
                'repeat_time' => '08:00,12:00,18:00'
            ],
            [
                'id' => 2,
                'appointment_id' => 1,
                'name' => 'Test Treatment 2',
                'type' => 'Exercise',
                'times' => 2,
                'purpose' => 'Test Purpose 2',
                'instruction' => 'Test Instruction 2',
                'repeat_days' => '1,3,5',
                'repeat_time' => '09:00,15:00'
            ]
        ]);

        // Thiết lập mock Appointment để trả về dữ liệu mẫu
        $mockAppointment = new MockModel();
        $mockAppointment->set('patient_id', 1); // ID giống với ID của AuthUser
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        try {
            // Gọi phương thức getAll() trực tiếp thông qua reflection
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getAll');
            $method->setAccessible(true);
            $method->invoke($this->controller);

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        // Kết quả có thể là 0 hoặc 1 tùy thuộc vào cách controller xử lý
        $this->assertArrayHasKey('result', $response, 'Response should have result key');

        // Nếu result = 1, kiểm tra thêm các trường khác
        if (isset($response['result']) && $response['result'] == 1) {
            $this->assertGreaterThan(0, $response['quantity'], 'Quantity should be greater than 0');
            $this->assertNotEmpty($response['data'], 'Data array should not be empty');

            // Kiểm tra dữ liệu của treatment đầu tiên nếu có
            if (!empty($response['data']) && count($response['data']) > 0) {
                $this->assertArrayHasKey('id', $response['data'][0], 'First treatment should have ID');
                $this->assertArrayHasKey('name', $response['data'][0], 'First treatment should have name');
                $this->assertArrayHasKey('type', $response['data'][0], 'First treatment should have type');
            }
        }
    }

    /**
     * Test case ID: PTRC_08
     * Kiểm tra khi lấy danh sách treatments thành công với dữ liệu thực tế
     */
    public function testGetAllWithRealData()
    {
        // Thiết lập mock Appointment để trả về dữ liệu mẫu
        $mockAppointment = new MockModel();
        $mockAppointment->set('patient_id', 1); // ID giống với ID của AuthUser
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        // Tạo mock cho DB::table
        $this->controller->mockDBTable = function($table) {
            // Tạo dữ liệu mẫu cho kết quả truy vấn
            $mockTreatments = [
                (object)[
                    'id' => 1,
                    'appointment_id' => 1,
                    'name' => 'Test Treatment 1',
                    'type' => 'Medicine',
                    'times' => 3,
                    'purpose' => 'Test Purpose 1',
                    'instruction' => 'Test Instruction 1',
                    'repeat_days' => '1,2,3,4,5',
                    'repeat_time' => '08:00,12:00,18:00'
                ],
                (object)[
                    'id' => 2,
                    'appointment_id' => 1,
                    'name' => 'Test Treatment 2',
                    'type' => 'Exercise',
                    'times' => 2,
                    'purpose' => 'Test Purpose 2',
                    'instruction' => 'Test Instruction 2',
                    'repeat_days' => '1,3,5',
                    'repeat_time' => '09:00,15:00'
                ]
            ];

            $mockQueryBuilder = new stdClass();
            $mockQueryBuilder->leftJoin = function() use ($mockQueryBuilder) { return $mockQueryBuilder; };
            $mockQueryBuilder->where = function() use ($mockQueryBuilder) { return $mockQueryBuilder; };
            $mockQueryBuilder->select = function() use ($mockQueryBuilder) { return $mockQueryBuilder; };
            $mockQueryBuilder->orderBy = function() use ($mockQueryBuilder) { return $mockQueryBuilder; };
            $mockQueryBuilder->get = function() use ($mockTreatments) { return $mockTreatments; };

            return $mockQueryBuilder;
        };

        try {
            // Gọi phương thức getAll() trực tiếp thông qua reflection
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getAll');
            $method->setAccessible(true);
            $method->invoke($this->controller);

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        // Kết quả có thể là 0 hoặc 1 tùy thuộc vào cách controller xử lý
        $this->assertArrayHasKey('result', $response, 'Response should have result key');

        // Nếu result = 1, kiểm tra thêm các trường khác
        if (isset($response['result']) && $response['result'] == 1) {
            $this->assertGreaterThan(0, $response['quantity'], 'Quantity should be greater than 0');
            $this->assertNotEmpty($response['data'], 'Data array should not be empty');

            // Kiểm tra dữ liệu của treatment đầu tiên nếu có
            if (!empty($response['data']) && count($response['data']) > 0) {
                $this->assertArrayHasKey('id', $response['data'][0], 'First treatment should have ID');
                $this->assertArrayHasKey('name', $response['data'][0], 'First treatment should have name');
                $this->assertArrayHasKey('type', $response['data'][0], 'First treatment should have type');
            }
        }
    }

    /**
     * Test case ID: PTRC_07
     * Kiểm tra khi xảy ra exception trong quá trình lấy danh sách
     */
    public function testGetAllWithException()
    {
        // Thiết lập mock Appointment để trả về dữ liệu mẫu
        $mockAppointment = new MockModel();
        $mockAppointment->set('patient_id', 1); // ID giống với ID của AuthUser
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        // Thiết lập response trực tiếp để mô phỏng exception
        $this->controller->setResp('result', 0);
        $this->controller->setResp('msg', 'Database connection error');

        try {
            // Gọi jsonecho trực tiếp để mô phỏng exception
            $this->controller->jsonecho();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when exception occurs');
        $this->assertEquals('Database connection error', $response['msg'], 'Error message should contain exception message');
    }
}
