<?php

/**
 * Test case for TreatmentsController với mục tiêu tăng độ phủ code
 * Sử dụng cách tiếp cận kế thừa trực tiếp từ TreatmentsController
 */

// Định nghĩa các hằng số cần thiết cho test
if (!defined('APPURL')) {
    define('APPURL', 'http://localhost/app');
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../../uploads');
}

require_once __DIR__ . '/../ControllerTestCase.php';
require_once __DIR__ . '/../../controllers/TreatmentsController.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

// Định nghĩa các hằng số bảng nếu chưa tồn tại
if (!defined('TABLE_PREFIX')) {
    define('TABLE_PREFIX', 'tn_');
}
if (!defined('TABLE_TREATMENTS')) {
    define('TABLE_TREATMENTS', 'treatments');
}
if (!defined('TABLE_APPOINTMENTS')) {
    define('TABLE_APPOINTMENTS', 'appointments');
}

/**
 * Mock cho Model
 */
class MockModel
{
    private $data = [];
    private $available = true;

    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function isAvailable()
    {
        return $this->available;
    }

    public function setAvailable($available)
    {
        $this->available = $available;
        return $this;
    }

    public function save()
    {
        return $this;
    }

    public function delete()
    {
        return $this;
    }
}

/**
 * Mock cho Input
 */
class InputMock
{
    public static function get($key)
    {
        $mockData = [
            'search' => 'Test',
            'order' => ['column' => 'name', 'dir' => 'asc'],
            'length' => 10,
            'start' => 0,
            'appointment_id' => 1
        ];
        return isset($mockData[$key]) ? $mockData[$key] : null;
    }

    public static function post($key)
    {
        $mockData = [
            'appointment_id' => 1,
            'name' => 'Uống thuốc',
            'type' => 'Thuốc',
            'times' => '3',
            'purpose' => 'Giảm đau',
            'instruction' => 'Uống sau khi ăn',
            'repeat_days' => 'Thực hiện mỗi ngày',
            'repeat_time' => 'Sáng và chiều'
        ];
        return isset($mockData[$key]) ? $mockData[$key] : null;
    }

    public static function method()
    {
        return 'GET';
    }
}

/**
 * Mock cho DB
 */
class DBMock
{
    public static function table($table)
    {
        return new MockQuery();
    }

    public static function raw($expression)
    {
        return $expression;
    }
}

class MockQuery
{
    public function leftJoin($table, $first, $operator, $second)
    {
        return $this;
    }

    public function select($columns)
    {
        return $this;
    }

    public function where($column, $operator = null, $value = null)
    {
        if (is_callable($column)) {
            $column($this);
        }
        return $this;
    }

    public function orWhere($column, $operator, $value)
    {
        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        return $this;
    }

    public function limit($limit)
    {
        return $this;
    }

    public function offset($offset)
    {
        return $this;
    }

    public function get()
    {
        // Trả về dữ liệu mẫu
        $result = [];
        $result[] = (object)[
            'id' => 1,
            'appointment_id' => 1,
            'name' => 'Uống thuốc',
            'type' => 'Thuốc',
            'times' => 3,
            'purpose' => 'Giảm đau',
            'instruction' => 'Uống sau khi ăn',
            'repeat_days' => 'Thực hiện mỗi ngày',
            'repeat_time' => 'Sáng và chiều'
        ];
        $result[] = (object)[
            'id' => 2,
            'appointment_id' => 1,
            'name' => 'Tập vật lý trị liệu',
            'type' => 'Vật lý trị liệu',
            'times' => 2,
            'purpose' => 'Phục hồi chức năng',
            'instruction' => 'Tập 30 phút mỗi lần',
            'repeat_days' => 'Thực hiện mỗi ngày',
            'repeat_time' => 'Sáng và chiều'
        ];
        return $result;
    }
}

/**
 * TreatmentsController có thể test được
 */
class TreatmentsControllerTestable extends TreatmentsController
{
    public $jsonEchoCalled = false;
    public $jsonEchoData;
    public $headerCalled = false;
    public $lastHeader;
    public $resp;
    public static $modelMethod;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->resp = new stdClass();
        $this->resp->result = 0;
    }

    /**
     * Override jsonecho để ngăn exit
     */
    public function jsonecho($data = null)
    {
        $this->jsonEchoCalled = true;
        $this->jsonEchoData = $data ?: $this->resp;
    }

    /**
     * Override header để ngăn redirect
     */
    public function header($header)
    {
        $this->headerCalled = true;
        $this->lastHeader = $header;
    }

    /**
     * Override exit để ngăn thoát
     */
    protected function exit()
    {
        // Không làm gì
    }

    /**
     * Override các phương thức tương tác với DB
     */
    protected function getDB()
    {
        return 'DBMock';
    }

    /**
     * Override Input để sử dụng InputMock
     */
    protected function getInput()
    {
        return 'InputMock';
    }

    /**
     * Override model để trả về mock model
     */
    public static function model($name, $id = null)
    {
        if (is_callable(self::$modelMethod)) {
            return call_user_func(self::$modelMethod, $name, $id);
        }

        $model = new MockModel();
        if ($name == "Appointment") {
            $model->set('id', $id ?: 1);
            $model->set('doctor_id', 1);
            $model->set('status', 'processing');
            $model->set('date', date('d-m-Y'));
        } else if ($name == "Treatment") {
            $model->set('id', $id ?: 1);
            $model->set('appointment_id', 1);
            $model->set('name', 'Uống thuốc');
            $model->set('type', 'Thuốc');
            $model->set('times', 3);
            $model->set('purpose', 'Giảm đau');
            $model->set('instruction', 'Uống sau khi ăn');
            $model->set('repeat_days', 'Thực hiện mỗi ngày');
            $model->set('repeat_time', 'Sáng và chiều');
        }
        return $model;
    }
}

/**
 * Test case for TreatmentsController
 */
class TreatmentsControllerTest extends ControllerTestCase
{
    /**
     * @var TreatmentsControllerTestable
     */
    protected $controller;

    /**
     * Set up the test environment
     */
    public function setUp()
    {
        parent::setUp();

        // Tạo controller instance
        $this->controller = new TreatmentsControllerTestable();
    }

    /**
     * Test case ID: TRTS_01
     * Kiểm tra phương thức process() khi người dùng chưa đăng nhập
     */
    public function testProcessWithoutAuth()
    {
        // Thiết lập AuthUser là null
        $this->controller->setVariable('AuthUser', null);

        // Gọi phương thức process()
        $this->controller->process();

        // Kiểm tra nếu header đã được gọi
        $this->assertTrue($this->controller->headerCalled, 'header() method should have been called');
        $this->assertContains('/login', $this->controller->lastHeader, 'Header should redirect to login page');
    }

    /**
     * Test case ID: TRTS_02
     * Kiểm tra phương thức process() khi người dùng không có quyền hợp lệ
     */
    public function testProcessWithInvalidRole()
    {
        // Thiết lập AuthUser với role không hợp lệ
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'patient'); // Role không hợp lệ
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức process()
        $this->controller->process();

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for invalid role');
        $this->assertContains("Only Doctor's role", $this->controller->jsonEchoData->msg, 'Error message should indicate invalid role');
    }

    /**
     * Test case ID: TRTS_03
     * Kiểm tra phương thức process() với phương thức GET
     */
    public function testProcessWithGetMethod()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức process()
        $this->controller->process();

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Test case ID: TRTS_04
     * Kiểm tra phương thức getAll() trực tiếp
     */
    public function testGetAllDirectly()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức getAll() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsController', 'getAll');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Test case ID: TRTS_05
     * Kiểm tra phương thức getAll() với role member
     */
    public function testGetAllWithMemberRole()
    {
        // Thiết lập AuthUser với role member
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'member');
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức getAll() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsController', 'getAll');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Test case ID: TRTS_06
     * Kiểm tra phương thức save() trực tiếp
     */
    public function testSaveDirectly()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsController', 'save');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful creation');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Tear down the test environment
     */
    public function tearDown()
    {
        parent::tearDown();

        // Khôi phục giá trị gốc
        $_POST = $this->originalPost;
        $_SERVER['REQUEST_METHOD'] = $this->originalRequestMethod;
    }

    /**
     * Override Controller::model để trả về mock model
     */
    public function mockControllerModel($name, $id = null)
    {
        $model = new MockModel();
        if ($name == "Appointment") {
            $model->set('id', $id ?: 1);
            $model->set('doctor_id', 1);
            $model->set('status', 'processing');
            $model->set('date', date('d-m-Y')); // Ngày hiện tại
        } else if ($name == "Treatment") {
            $model->set('id', $id ?: 1);
            $model->set('appointment_id', 1);
            $model->set('name', 'Uống thuốc');
            $model->set('type', 'Thuốc');
            $model->set('times', 3);
            $model->set('purpose', 'Giảm đau');
            $model->set('instruction', 'Uống sau khi ăn');
            $model->set('repeat_days', 'Thực hiện mỗi ngày');
            $model->set('repeat_time', 'Sáng và chiều');
        }
        return $model;
    }

    /**
     * Test case ID: TRTS_01
     * Kiểm tra phương thức process() khi người dùng chưa đăng nhập
     */
    public function testProcessWithoutAuth()
    {
        // Thiết lập AuthUser là null
        $this->controller->setVariable('AuthUser', null);

        // Gọi phương thức process()
        try {
            $this->controller->process();
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception là do exit
            $this->assertContains('Exit called', $e->getMessage(), 'Exit should be called after header redirect');
        }

        // Kiểm tra nếu header đã được gọi
        $this->assertTrue($this->controller->headerCalled, 'header() method should have been called');
        $this->assertContains('/login', $this->controller->lastHeader, 'Header should redirect to login page');
    }

    /**
     * Test case ID: TRTS_02
     * Kiểm tra phương thức process() khi người dùng không có quyền hợp lệ
     */
    public function testProcessWithInvalidRole()
    {
        // Thiết lập AuthUser với role không hợp lệ
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'patient'); // Role không hợp lệ
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức process()
        try {
            $this->controller->process();
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for invalid role');
        $this->assertContains("Only Doctor's role", $this->controller->jsonEchoData->msg, 'Error message should indicate invalid role');
    }

    /**
     * Test case ID: TRTS_03
     * Kiểm tra phương thức process() với phương thức GET
     */
    public function testProcessWithGetMethod()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức process() với phương thức GET
        $_SERVER['REQUEST_METHOD'] = 'GET';

        try {
            $this->controller->process();
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Test case ID: TRTS_04
     * Kiểm tra phương thức process() với phương thức POST
     */
    public function testProcessWithPostMethod()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Gọi phương thức process() với phương thức POST
        $_SERVER['REQUEST_METHOD'] = 'POST';

        try {
            $this->controller->process();
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful creation');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Test case ID: TRTS_05
     * Kiểm tra phương thức getAll() trực tiếp
     */
    public function testGetAllDirectly()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức getAll() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'getAll');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
        $this->assertObjectHasAttribute('quantity', $this->controller->jsonEchoData, 'Response should have quantity attribute');
    }

    /**
     * Test case ID: TRTS_06
     * Kiểm tra phương thức getAll() với role member
     */
    public function testGetAllWithMemberRole()
    {
        // Thiết lập AuthUser với role member
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'member');
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức getAll() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'getAll');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Test case ID: TRTS_07
     * Kiểm tra phương thức getAll() khi xảy ra ngoại lệ
     */
    public function testGetAllWithException()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập DB::table để ném ngoại lệ
        DB::$tableMock = function($table) {
            throw new Exception('Database error');
        };

        // Gọi phương thức getAll() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'getAll');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when exception occurs');
        $this->assertContains('Database error', $this->controller->jsonEchoData->msg, 'Error message should contain exception message');

        // Khôi phục DB::table
        DB::$tableMock = function($table) {
            return new MockQuery();
        };
    }

    /**
     * Test case ID: TRTS_08
     * Kiểm tra phương thức save() trực tiếp
     */
    public function testSaveDirectly()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful creation');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Test case ID: TRTS_09
     * Kiểm tra phương thức save() khi thiếu trường bắt buộc
     */
    public function testSaveWithMissingRequiredField()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập POST data thiếu trường name
        InputMock::$postMock = function($key) {
            $mockData = [
                'appointment_id' => 1,
                // Thiếu trường 'name'
                'type' => 'Thuốc',
                'times' => '3',
                'purpose' => 'Giảm đau',
                'instruction' => 'Uống sau khi ăn'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when required field is missing');
        $this->assertContains('Missing field', $this->controller->jsonEchoData->msg, 'Error message should indicate missing field');
    }

    /**
     * Test case ID: TRTS_10
     * Kiểm tra phương thức save() khi appointment không tồn tại
     */
    public function testSaveWithNonExistentAppointment()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập Controller::model để trả về mock model không có sẵn
        Controller::$modelMethod = function($name, $id = null) {
            $model = new MockModel();
            if ($name == "Appointment") {
                $model->setAvailable(false); // Appointment không tồn tại
            }
            return $model;
        };

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when appointment does not exist');
        $this->assertContains('Appointment is not available', $this->controller->jsonEchoData->msg, 'Error message should indicate appointment is not available');
    }

    /**
     * Test case ID: TRTS_11
     * Kiểm tra phương thức save() khi appointment có trạng thái không hợp lệ
     */
    public function testSaveWithInvalidAppointmentStatus()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập Controller::model để trả về mock model với trạng thái không hợp lệ
        Controller::$modelMethod = function($name, $id = null) {
            $model = new MockModel();
            if ($name == "Appointment") {
                $model->set('id', $id ?: 1);
                $model->set('doctor_id', 1);
                $model->set('status', 'done'); // Trạng thái không hợp lệ
                $model->set('date', date('d-m-Y'));
            }
            return $model;
        };

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when appointment status is invalid');
        $this->assertContains("The status of appointment is", $this->controller->jsonEchoData->msg, 'Error message should indicate appointment status issue');
    }

    /**
     * Test case ID: TRTS_12
     * Kiểm tra phương thức save() khi appointment đã qua ngày
     */
    public function testSaveWithPastAppointment()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập Controller::model để trả về mock model với ngày đã qua
        Controller::$modelMethod = function($name, $id = null) {
            $model = new MockModel();
            if ($name == "Appointment") {
                $model->set('id', $id ?: 1);
                $model->set('doctor_id', 1);
                $model->set('status', 'processing');
                // Ngày hẹn là ngày hôm qua
                $yesterday = date('d-m-Y', strtotime('-1 day'));
                $model->set('date', $yesterday);
            }
            return $model;
        };

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when appointment date is in the past');
        $this->assertContains('Today is', $this->controller->jsonEchoData->msg, 'Error message should indicate date issue');
    }

    /**
     * Test case ID: TRTS_13
     * Kiểm tra phương thức save() khi name không hợp lệ
     */
    public function testSaveWithInvalidName()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Thiết lập POST data với name không hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'appointment_id' => 1,
                'name' => '@#$%^&*', // Name không hợp lệ
                'type' => 'Thuốc',
                'times' => '3',
                'purpose' => 'Giảm đau',
                'instruction' => 'Uống sau khi ăn'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Thiết lập mock cho Controller::model để trả về mock model
        Controller::$modelMethod = function($name, $id = null) {
            $model = new MockModel();
            if ($name == "Appointment") {
                $model->set('id', $id ?: 1);
                $model->set('doctor_id', 1);
                $model->set('status', 'processing');
                $model->set('date', date('d-m-Y'));
            }
            return $model;
        };

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful creation');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Test case ID: TRTS_14
     * Kiểm tra phương thức save() khi type không hợp lệ
     */
    public function testSaveWithInvalidType()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Thiết lập POST data với type không hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'appointment_id' => 1,
                'name' => 'Uống thuốc',
                'type' => '@#$%^&*', // Type không hợp lệ
                'times' => '3',
                'purpose' => 'Giảm đau',
                'instruction' => 'Uống sau khi ăn'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Thiết lập mock cho Controller::model để trả về mock model
        Controller::$modelMethod = function($name, $id = null) {
            $model = new MockModel();
            if ($name == "Appointment") {
                $model->set('id', $id ?: 1);
                $model->set('doctor_id', 1);
                $model->set('status', 'processing');
                $model->set('date', date('d-m-Y'));
            }
            return $model;
        };

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful creation');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Test case ID: TRTS_15
     * Kiểm tra phương thức save() khi times không hợp lệ
     */
    public function testSaveWithInvalidTimes()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Thiết lập POST data với times không hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'appointment_id' => 1,
                'name' => 'Uống thuốc',
                'type' => 'Thuốc',
                'times' => 'abc', // Times không hợp lệ
                'purpose' => 'Giảm đau',
                'instruction' => 'Uống sau khi ăn'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Thiết lập mock cho Controller::model để trả về mock model
        Controller::$modelMethod = function($name, $id = null) {
            $model = new MockModel();
            if ($name == "Appointment") {
                $model->set('id', $id ?: 1);
                $model->set('doctor_id', 1);
                $model->set('status', 'processing');
                $model->set('date', date('d-m-Y'));
            }
            return $model;
        };

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful creation');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Test case ID: TRTS_16
     * Kiểm tra phương thức save() khi xảy ra ngoại lệ
     */
    public function testSaveWithException()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập Controller::model để trả về mock model với save() ném ngoại lệ
        Controller::$modelMethod = function($name, $id = null) {
            if ($name == "Appointment") {
                $model = new MockModel();
                $model->set('id', $id ?: 1);
                $model->set('doctor_id', 1);
                $model->set('status', 'processing');
                $model->set('date', date('d-m-Y'));
                return $model;
            } else if ($name == "Treatment") {
                $model = new MockModel();
                $model->save = function() {
                    throw new Exception('Database error during save');
                };
                return $model;
            }
            return new MockModel();
        };

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when exception occurs');
        $this->assertContains('Database error', $this->controller->jsonEchoData->msg, 'Error message should contain exception message');
    }
}
