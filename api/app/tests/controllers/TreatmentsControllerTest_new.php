<?php

/**
 * Test case for TreatmentsController với mục tiêu tăng độ phủ code
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
 * Mock cho Input
 */
if (!class_exists('Input')) {
    class Input
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
}

/**
 * Mock cho DB
 */

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
 * TreatmentsController có thể test được
 */
class TreatmentsControllerTestable extends TreatmentsController
{
    public $jsonEchoCalled = false;
    public $jsonEchoData;
    public $headerCalled = false;
    public $lastHeader;
    public $resp;

    /**
     * Override jsonecho để ngăn exit
     */
    public function jsonecho($data = null)
    {
        $this->jsonEchoCalled = true;
        $this->jsonEchoData = $data ?: $this->resp;
        return;
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
     * Override model để trả về mock model
     */
    public static function model($name, $id = null)
    {
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

        // Khởi tạo resp
        $this->controller->resp = new stdClass();
        $this->controller->resp->result = 0;
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

        // Gọi phương thức process() với phương thức GET
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
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'getAll');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }

    /**
     * Test case ID: TRTS_05
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
        $reflection = new ReflectionMethod('TreatmentsControllerTestable', 'save');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful creation');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }
}