<?php

/**
 * Test case for ServicesController với mục tiêu tăng độ phủ code
 * Sử dụng cách tiếp cận Reflection để truy cập các phương thức private
 */

// Định nghĩa các hằng số cần thiết cho test
if (!defined('APPURL')) {
    define('APPURL', 'http://localhost/app');
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../../uploads');
}

require_once __DIR__ . '/../ControllerTestCase.php';

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
if (!defined('TABLE_SERVICES')) {
    define('TABLE_SERVICES', 'services');
}

/**
 * Mock cho Model
 */
class MockModel
{
    private $data = [];

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
        return true;
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
 * Thiết lập các hàm mock cho Input
 */
InputMock::$getMock = function($key) {
    $mockData = [
        'search' => 'Test',
        'order' => ['column' => 'name', 'dir' => 'asc'],
        'length' => 10,
        'start' => 0
    ];
    return isset($mockData[$key]) ? $mockData[$key] : null;
};

InputMock::$postMock = function($key) {
    $mockData = [
        'name' => 'Test Service',
        'description' => 'Test Description'
    ];
    return isset($mockData[$key]) ? $mockData[$key] : null;
};

/**
 * ServicesController có thể test được
 */
class ServicesControllerTestable extends ServicesController
{
    public $jsonEchoCalled = false;
    public $jsonEchoData;
    public $headerCalled = false;
    public $lastHeader;
    public $resp;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->resp = new stdClass();
    }

    /**
     * Override jsonecho để ngăn exit
     */
    public function jsonecho($data = null)
    {
        $this->jsonEchoCalled = true;
        $this->jsonEchoData = $data ?: $this->resp;
        throw new Exception('JsonEchoExit: ' . json_encode($this->jsonEchoData));
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
     * Phương thức để bắt exit
     */
    public function handleExit()
    {
        throw new Exception('Exit called');
    }
}

/**
 * Test case for ServicesController
 */
class ServicesControllerTest extends ControllerTestCase
{
    /**
     * @var ServicesControllerTestable
     */
    protected $controller;

    /**
     * @var array
     */
    protected $originalPost;

    /**
     * @var string
     */
    protected $originalRequestMethod;

    /**
     * Set up the test environment
     */
    public function setUp()
    {
        parent::setUp();

        // Lưu trữ giá trị gốc
        $this->originalPost = $_POST;
        $this->originalRequestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        // Tạo controller instance
        $this->controller = new ServicesControllerTestable();

        // Khởi tạo resp
        $this->controller->resp->result = 0;
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
     * Test case ID: SVC_01
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
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
    }

    /**
     * Test case ID: SVC_02
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
        $reflection = new ReflectionMethod('ServicesController', 'getAll');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
    }

    /**
     * Test case ID: SVC_03
     * Kiểm tra phương thức getAll() với các tham số khác nhau
     */
    public function testGetAllWithDifferentParams()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập Input::get với order không hợp lệ
        InputMock::$getMock = function($key) {
            $mockData = [
                'search' => 'Service',
                'order' => null,
                'length' => null,
                'start' => null
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Gọi phương thức getAll() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('ServicesController', 'getAll');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');

        // Reset
        $this->controller->jsonEchoCalled = false;
        $this->controller->jsonEchoData = null;

        // Thiết lập Input::get với order không hợp lệ
        InputMock::$getMock = function($key) {
            $mockData = [
                'search' => '',
                'order' => ['column' => 'name', 'dir' => 'invalid']
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
    }

    /**
     * Test case ID: SVC_04
     * Kiểm tra phương thức process() với phương thức POST
     */
    public function testProcessWithPostMethod()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức process() với phương thức POST
        $_SERVER['REQUEST_METHOD'] = 'POST';

        try {
            $this->controller->process();
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful save');
    }

    /**
     * Test case ID: SVC_05
     * Kiểm tra phương thức save() trực tiếp
     */
    public function testSaveDirectly()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập POST data
        InputMock::$postMock = function($key) {
            $mockData = [
                'name' => 'Test Service Direct',
                'description' => 'Test Description Direct'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('ServicesController', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful save');
    }

    /**
     * Test case ID: SVC_06
     * Kiểm tra phương thức process() khi người dùng chưa đăng nhập
     */
    public function testProcessWithoutAuth()
    {
        // Thiết lập AuthUser là null
        $this->controller->setVariable('AuthUser', null);

        // Gọi phương thức process()
        $this->controller->process();

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->headerCalled, 'header() method should have been called');
        $this->assertContains('/login', $this->controller->lastHeader, 'Header should redirect to /login');
    }

    /**
     * Test case ID: SVC_07
     * Kiểm tra phương thức process() với phương thức không hợp lệ
     */
    public function testProcessWithInvalidMethod()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức process() với phương thức không hợp lệ
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        // Không có xử lý cho PUT, nên không có exception
        $this->controller->process();

        // Kiểm tra kết quả
        $this->assertFalse($this->controller->jsonEchoCalled, 'jsonecho() method should not have been called');
    }

    /**
     * Test case ID: SVC_08
     * Kiểm tra phương thức save() khi người dùng không phải admin
     */
    public function testSaveWithNonAdmin()
    {
        // Thiết lập AuthUser không phải admin
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'doctor');
        $this->controller->setVariable('AuthUser', $authUser);

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('ServicesController', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for non-admin user');
        $this->assertContains('not admin', $this->controller->jsonEchoData->msg, 'Error message should indicate user is not admin');
    }

    /**
     * Test case ID: SVC_09
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
                'description' => 'Test Description'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('ServicesController', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when required field is missing');
        $this->assertContains('Missing field', $this->controller->jsonEchoData->msg, 'Error message should indicate missing field');
    }

    /**
     * Test case ID: SVC_10
     * Kiểm tra phương thức save() khi tên dịch vụ đã tồn tại
     */
    public function testSaveWithDuplicateName()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập POST data
        InputMock::$postMock = function($key) {
            $mockData = [
                'name' => 'Existing Service',
                'description' => 'Test Description'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Mock DB::table để trả về kết quả có dịch vụ trùng lặp
        $mockResult = [
            (object)[
                'id' => 1,
                'name' => 'Existing Service',
                'description' => 'Existing Description'
            ]
        ];

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('ServicesController', 'save');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception là do jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for duplicate service name');
        $this->assertContains('exists', $this->controller->jsonEchoData->msg, 'Error message should indicate service exists');
    }
}
