<?php

/**
 * Test case for RoomsController với mục tiêu tăng độ phủ code
 * Sử dụng cách tiếp cận đơn giản để tăng độ phủ
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
if (!defined('TABLE_ROOMS')) {
    define('TABLE_ROOMS', 'rooms');
}
if (!defined('TABLE_DOCTORS')) {
    define('TABLE_DOCTORS', 'doctors');
}
if (!defined('TABLE_SPECIALITIES')) {
    define('TABLE_SPECIALITIES', 'specialities');
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
        'start' => 0,
        'speciality_id' => 1
    ];
    return isset($mockData[$key]) ? $mockData[$key] : null;
};

InputMock::$postMock = function($key) {
    $mockData = [
        'name' => 'Test Room',
        'location' => 'Test Location'
    ];
    return isset($mockData[$key]) ? $mockData[$key] : null;
};

/**
 * RoomsController có thể test được
 */
class RoomsControllerTestable extends RoomsController
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
 * Test case for RoomsController
 */
class RoomsControllerBasicTest extends ControllerTestCase
{
    /**
     * @var RoomsControllerTestable
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
        $this->controller = new RoomsControllerTestable();

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
     * Test case ID: RMS_B01
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
     * Test case ID: RMS_B01_1
     * Kiểm tra phương thức getAll() trực tiếp
     */
    public function testGetAllDirectly()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Input::get đã được thiết lập trong phần khởi tạo

        // Gọi phương thức getAll() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('RoomsController', 'getAll');
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
     * Test case ID: RMS_B01_2
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
                'search' => 'Room',
                'order' => null,
                'length' => null,
                'start' => null,
                'speciality_id' => null
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Gọi phương thức getAll() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('RoomsController', 'getAll');
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
                'order' => ['column' => 'location', 'dir' => 'invalid']
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
     * Test case ID: RMS_B02
     * Kiểm tra phương thức process() với phương thức POST
     */
    public function testProcessWithPostMethod()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // POST data đã được thiết lập trong phần khởi tạo

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
     * Test case ID: RMS_B02_1
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
                'name' => 'Test Room Direct',
                'location' => 'Test Location Direct'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('RoomsController', 'save');
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
     * Test case ID: RMS_B03
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
     * Test case ID: RMS_B04
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
     * Test case ID: RMS_B05
     * Kiểm tra phương thức process() với phương thức POST và thiếu trường bắt buộc
     */
    public function testProcessWithPostMethodAndMissingFields()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập POST data thiếu trường name
        InputMock::$postMock = function($key) {
            $mockData = [
                'location' => 'Test Location'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

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
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when required field is missing');
        $this->assertContains('Missing field', $this->controller->jsonEchoData->msg, 'Error message should indicate missing field');
    }

    /**
     * Test case ID: RMS_B06
     * Kiểm tra phương thức getAll() khi người dùng không phải admin
     */
    public function testGetAllWithNonAdmin()
    {
        // Thiết lập AuthUser không phải admin
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'doctor');
        $this->controller->setVariable('AuthUser', $authUser);

        try {
            // Gọi phương thức process() với phương thức GET
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $this->controller->process();
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
     * Test case ID: RMS_B07
     * Kiểm tra phương thức save() khi người dùng không phải admin
     */
    public function testSaveWithNonAdmin()
    {
        // Thiết lập AuthUser không phải admin
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'doctor');
        $this->controller->setVariable('AuthUser', $authUser);

        // Thiết lập POST data
        InputMock::$postMock = function($key) {
            $mockData = [
                'name' => 'Test Room',
                'location' => 'Test Location'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        try {
            // Gọi phương thức process() với phương thức POST
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $this->controller->process();
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for non-admin user');
        $this->assertContains('not admin', $this->controller->jsonEchoData->msg, 'Error message should indicate user is not admin');
    }
}
