<?php

/**
 * Test case for SignupController với mục tiêu tăng độ phủ code
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

// Định nghĩa các hàm kiểm tra tên và số điện thoại nếu chưa tồn tại
if (!function_exists('isVietnameseName')) {
    function isVietnameseName($name) {
        // Kiểm tra tên tiếng Việt chỉ chứa chữ cái và khoảng trắng
        if (empty($name)) return 0;
        return preg_match('/^[a-zA-Z\s]+$/u', $name) ? 1 : 0;
    }
}

if (!function_exists('isNumber')) {
    function isNumber($number) {
        // Kiểm tra chuỗi chỉ chứa số
        if (empty($number)) return false;
        return preg_match('/^[0-9]+$/', $number) ? true : false;
    }
}

/**
 * Mock cho Model
 */
class MockModel
{
    private $data = [];
    private $available = false;

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
 * Mock cho MyEmail
 */
class MyEmail
{
    public static $signupCalled = false;
    public static $signupData = null;

    public static function signup($data)
    {
        self::$signupCalled = true;
        self::$signupData = $data;
        return true;
    }

    public static function reset()
    {
        self::$signupCalled = false;
        self::$signupData = null;
    }
}

/**
 * Thiết lập các hàm mock cho Input
 */
InputMock::$getMock = function($key) {
    return null;
};

InputMock::$postMock = function($key) {
    $mockData = [
        'email' => 'test@example.com',
        'phone' => '0123456789',
        'password' => 'password123',
        'passwordConfirm' => 'password123',
        'name' => 'Test User',
        'description' => 'Test Description',
        'price' => '200000',
        'avatar' => 'avatar.jpg'
    ];
    return isset($mockData[$key]) ? $mockData[$key] : null;
};

/**
 * SignupController có thể test được
 */
class SignupControllerTestable extends SignupController
{
    public $jsonEchoCalled = false;
    public $jsonEchoData;
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
}

/**
 * Test case for SignupController
 */
class SignupControllerTest extends ControllerTestCase
{
    /**
     * @var SignupControllerTestable
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
     * @var MockModel
     */
    protected $mockDoctor;

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
        $this->controller = new SignupControllerTestable();

        // Khởi tạo resp
        $this->controller->resp->result = 0;

        // Tạo mock cho Doctor model
        $this->mockDoctor = new MockModel();
        $this->mockDoctor->setAvailable(false);

        // Reset MyEmail static variables
        MyEmail::reset();
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
        if ($name == "Doctor") {
            // Nếu có id, kiểm tra xem có phải là email không
            if ($id !== null && filter_var($id, FILTER_VALIDATE_EMAIL)) {
                // Nếu là email, trả về model với isAvailable() = true để giả lập email đã tồn tại
                $model = clone $this->mockDoctor;
                $model->setAvailable(true);
                return $model;
            }
            return $this->mockDoctor;
        }
        return new MockModel();
    }

    /**
     * Test case ID: SGN_01
     * Kiểm tra phương thức process() khi người dùng đã đăng nhập
     */
    public function testProcessWithAuthUser()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'member');
        $this->controller->setVariable('AuthUser', $authUser);

        try {
            $this->controller->process();
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertContains('You logged in', $this->controller->jsonEchoData->msg, 'Message should indicate user is logged in');
    }

    /**
     * Test case ID: SGN_02
     * Kiểm tra phương thức process() với phương thức POST
     */
    public function testProcessWithPostMethod()
    {
        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];

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
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful signup');
        $this->assertTrue(MyEmail::$signupCalled, 'MyEmail::signup() should have been called');
    }

    /**
     * Test case ID: SGN_03
     * Kiểm tra phương thức signup() trực tiếp
     */
    public function testSignupDirectly()
    {
        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful signup');
        $this->assertTrue(MyEmail::$signupCalled, 'MyEmail::signup() should have been called');
    }

    /**
     * Test case ID: SGN_04
     * Kiểm tra phương thức signup() khi thiếu trường bắt buộc
     */
    public function testSignupWithMissingRequiredField()
    {
        // Thiết lập POST data thiếu trường email
        InputMock::$postMock = function($key) {
            $mockData = [
                'phone' => '0123456789',
                'password' => 'password123',
                'passwordConfirm' => 'password123',
                'name' => 'Test User'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
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
     * Test case ID: SGN_05
     * Kiểm tra phương thức signup() với email không hợp lệ
     */
    public function testSignupWithInvalidEmail()
    {
        // Thiết lập POST data với email không hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'email' => 'invalid-email',
                'phone' => '0123456789',
                'password' => 'password123',
                'passwordConfirm' => 'password123',
                'name' => 'Test User',
                'description' => 'Test Description',
                'price' => '200000'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for invalid email');
        $this->assertContains('Email is not correct format', $this->controller->jsonEchoData->msg, 'Error message should indicate invalid email format');
    }

    /**
     * Test case ID: SGN_06
     * Kiểm tra phương thức signup() với email đã tồn tại
     */
    public function testSignupWithExistingEmail()
    {
        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Thiết lập POST data với email hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'email' => 'test@example.com',
                'phone' => '0123456789',
                'password' => 'password123',
                'passwordConfirm' => 'password123',
                'name' => 'Test User',
                'description' => 'Test Description',
                'price' => '200000'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for existing email');
        $this->assertContains('This email is used', $this->controller->jsonEchoData->msg, 'Error message should indicate email is already used');
    }

    /**
     * Test case ID: SGN_07
     * Kiểm tra phương thức signup() với mật khẩu quá ngắn
     */
    public function testSignupWithShortPassword()
    {
        // Thiết lập POST data với mật khẩu ngắn
        InputMock::$postMock = function($key) {
            $mockData = [
                'email' => 'short_password@example.com', // Email mới để tránh trùng lặp
                'phone' => '0123456789',
                'password' => '12345',
                'passwordConfirm' => '12345',
                'name' => 'Test User',
                'description' => 'Test Description',
                'price' => '200000'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Thiết lập Controller::model để trả về mock model với isAvailable = false
        $this->mockDoctor->setAvailable(false);
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for short password');
        $this->assertContains('Password must be at least 6 character', $this->controller->jsonEchoData->msg, 'Error message should indicate password is too short');
    }

    /**
     * Test case ID: SGN_08
     * Kiểm tra phương thức signup() với mật khẩu xác nhận không khớp
     */
    public function testSignupWithMismatchedPasswords()
    {
        // Thiết lập POST data với mật khẩu xác nhận không khớp
        InputMock::$postMock = function($key) {
            $mockData = [
                'email' => 'mismatched_password@example.com', // Email mới để tránh trùng lặp
                'phone' => '0123456789',
                'password' => 'password123',
                'passwordConfirm' => 'password456',
                'name' => 'Test User',
                'description' => 'Test Description',
                'price' => '200000'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Thiết lập Controller::model để trả về mock model với isAvailable = false
        $this->mockDoctor->setAvailable(false);
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for mismatched passwords');
        $this->assertContains('Password confirmation does not equal', $this->controller->jsonEchoData->msg, 'Error message should indicate passwords do not match');
    }

    /**
     * Test case ID: SGN_09
     * Kiểm tra phương thức signup() với tên không hợp lệ
     */
    public function testSignupWithInvalidName()
    {
        // Thiết lập POST data với tên không hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'email' => 'invalid_name@example.com', // Email mới để tránh trùng lặp
                'phone' => '0123456789',
                'password' => 'password123',
                'passwordConfirm' => 'password123',
                'name' => 'Test User 123', // Tên có số, không hợp lệ
                'description' => 'Test Description',
                'price' => '200000'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Thiết lập Controller::model để trả về mock model với isAvailable = false
        $this->mockDoctor->setAvailable(false);
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for invalid name');
        $this->assertContains('Vietnamese name only has letters and space', $this->controller->jsonEchoData->msg, 'Error message should indicate invalid name format');
    }

    /**
     * Test case ID: SGN_10
     * Kiểm tra phương thức signup() với số điện thoại quá ngắn
     */
    public function testSignupWithShortPhoneNumber()
    {
        // Thiết lập POST data với số điện thoại ngắn
        InputMock::$postMock = function($key) {
            $mockData = [
                'email' => 'short_phone@example.com', // Email mới để tránh trùng lặp
                'phone' => '12345',
                'password' => 'password123',
                'passwordConfirm' => 'password123',
                'name' => 'Test User',
                'description' => 'Test Description',
                'price' => '200000'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Thiết lập Controller::model để trả về mock model với isAvailable = false
        $this->mockDoctor->setAvailable(false);
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for short phone number');
        $this->assertContains('Phone number has at least 10 number', $this->controller->jsonEchoData->msg, 'Error message should indicate phone number is too short');
    }

    /**
     * Test case ID: SGN_11
     * Kiểm tra phương thức signup() với số điện thoại không hợp lệ
     */
    public function testSignupWithInvalidPhoneNumber()
    {
        // Thiết lập POST data với số điện thoại không hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'email' => 'invalid_phone@example.com', // Email mới để tránh trùng lặp
                'phone' => '0123456789a', // Số điện thoại có chữ cái
                'password' => 'password123',
                'passwordConfirm' => 'password123',
                'name' => 'Test User',
                'description' => 'Test Description',
                'price' => '200000'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Thiết lập Controller::model để trả về mock model với isAvailable = false
        $this->mockDoctor->setAvailable(false);
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for invalid phone number');
        $this->assertContains('This is not a valid phone number', $this->controller->jsonEchoData->msg, 'Error message should indicate invalid phone number format');
    }

    /**
     * Test case ID: SGN_12
     * Kiểm tra phương thức signup() với giá quá thấp
     */
    public function testSignupWithLowPrice()
    {
        // Thiết lập POST data với giá thấp
        InputMock::$postMock = function($key) {
            $mockData = [
                'email' => 'low_price@example.com', // Email mới để tránh trùng lặp
                'phone' => '0123456789',
                'password' => 'password123',
                'passwordConfirm' => 'password123',
                'name' => 'Test User',
                'description' => 'Test Description',
                'price' => '50000' // Giá thấp hơn 100.000
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Thiết lập Controller::model để trả về mock model với isAvailable = false
        $this->mockDoctor->setAvailable(false);
        Controller::$modelMethod = [$this, 'mockControllerModel'];

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 for low price');
        $this->assertContains('Price must greater than 100.000', $this->controller->jsonEchoData->msg, 'Error message should indicate price is too low');
    }

    /**
     * Test case ID: SGN_13
     * Kiểm tra phương thức signup() khi xảy ra ngoại lệ trong quá trình lưu
     */
    public function testSignupWithSaveException()
    {
        // Thiết lập POST data với email hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'email' => 'save_exception@example.com', // Email mới để tránh trùng lặp
                'phone' => '0123456789',
                'password' => 'password123',
                'passwordConfirm' => 'password123',
                'name' => 'Test User',
                'description' => 'Test Description',
                'price' => '200000'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Tạo mock Doctor với phương thức save() ném ngoại lệ
        $mockDoctor = $this->getMockBuilder('MockModel')
            ->setMethods(['save', 'isAvailable'])
            ->getMock();
        $mockDoctor->method('isAvailable')
            ->willReturn(false);
        $mockDoctor->method('save')
            ->will($this->throwException(new Exception('Database error')));

        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = function($name, $id = null) use ($mockDoctor) {
            if ($name == "Doctor") {
                return $mockDoctor;
            }
            return new MockModel();
        };

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when exception occurs');
        $this->assertContains('Database error', $this->controller->jsonEchoData->msg, 'Error message should contain exception message');
    }

    /**
     * Test case ID: SGN_14
     * Kiểm tra phương thức signup() với dữ liệu hợp lệ
     */
    public function testSignupWithValidData()
    {
        // Thiết lập POST data với dữ liệu hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'email' => 'valid_signup@example.com', // Email mới để tránh trùng lặp
                'phone' => '0123456789',
                'password' => 'password123',
                'passwordConfirm' => 'password123',
                'name' => 'Test User',
                'description' => 'Test Description',
                'price' => '200000'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };

        // Tạo mock Doctor với phương thức save() trả về thành công
        $mockDoctor = $this->getMockBuilder('MockModel')
            ->setMethods(['save', 'isAvailable', 'get'])
            ->getMock();
        $mockDoctor->method('isAvailable')
            ->willReturn(false);
        $mockDoctor->method('save')
            ->willReturnSelf();
        $mockDoctor->method('get')
            ->willReturn(1); // Trả về id = 1 khi gọi get('id')

        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = function($name, $id = null) use ($mockDoctor) {
            if ($name == "Doctor") {
                return $mockDoctor;
            }
            return new MockModel();
        };

        // Gọi phương thức signup() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('SignupController', 'signup');
        $reflection->setAccessible(true);

        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Chấp nhận bất kỳ exception nào
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful signup');
        $this->assertTrue(MyEmail::$signupCalled, 'MyEmail::signup() should have been called');
    }
}
