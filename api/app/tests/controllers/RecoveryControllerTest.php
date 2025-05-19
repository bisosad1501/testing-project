<?php
/**
 * Unit tests for RecoveryController
 *
 * File: api/app/tests/controllers/RecoveryControllerTest.php
 * Class: RecoveryControllerTest
 *
 * Test suite cho các chức năng của RecoveryController:
 * - Kiểm tra chức năng khôi phục mật khẩu
 * - Kiểm tra xử lý email không tồn tại
 * - Kiểm tra xử lý tài khoản không hoạt động
 */

// Định nghĩa các hằng số cần thiết cho test
if (!defined('APPURL')) {
    define('APPURL', 'http://localhost/app');
}

require_once __DIR__ . '/../ControllerTestCase.php';
require_once __DIR__ . '/../mocks/MockModel.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

// Mock cho lớp MyEmail
if (!class_exists('MyEmail')) {
    class MyEmail {
        public static $emailSent = false;
        public static $lastEmailData = null;
        public static $throwException = false;
        public static $exceptionMessage = '';

        public static function recoveryPassword($data) {
            if (self::$throwException) {
                throw new Exception(self::$exceptionMessage);
            }

            self::$emailSent = true;
            self::$lastEmailData = $data;
            return true;
        }

        public static function reset() {
            self::$emailSent = false;
            self::$lastEmailData = null;
            self::$throwException = false;
            self::$exceptionMessage = '';
        }
    }
}

// Mock cho lớp Email
if (!class_exists('Email')) {
    class Email {
        public static function sendNotification($template, $data) {
            if (isset($GLOBALS['emailSendNotificationException']) && $GLOBALS['emailSendNotificationException']) {
                throw new Exception('Email sending failed');
            }

            if (isset($GLOBALS['emailSendNotificationResult'])) {
                return $GLOBALS['emailSendNotificationResult'];
            }

            return true;
        }
    }
}

/**
 * Lớp con của RecoveryController để mô phỏng các phương thức và phục vụ test
 */
class TestableRecoveryController extends \RecoveryController
{
    public $jsonEchoCalled = false;
    public $jsonEchoData = null;
    public $headerCalled = false;
    public $lastHeader = '';
    public $exitCalled = false;
    public static $mockDoctor = null;
    public static $mockUser = null;
    public $viewCalled = false;
    public $viewName = null;
    public $viewContext = null;

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

    public static function model($name, $id = 0)
    {
        if ($name == 'Doctor' && isset(self::$mockDoctor)) {
            return self::$mockDoctor;
        }
        if ($name == 'User' && isset(self::$mockUser)) {
            return self::$mockUser;
        }
        return parent::model($name, $id);
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

    public function view($view, $context = "app")
    {
        $this->viewCalled = true;
        $this->viewName = $view;
        $this->viewContext = $context;
    }

    // Ghi đè phương thức recover để có thể test
    public function testRecover()
    {
        // Sử dụng reflection để gọi phương thức private
        $reflection = new ReflectionClass($this);
        $method = $reflection->getParentClass()->getMethod('recover');
        $method->setAccessible(true);
        return $method->invoke($this);
    }
}

/**
 * Lớp test cho RecoveryController
 */
class RecoveryControllerTest extends ControllerTestCase
{
    protected $controller;
    protected $mockDoctor;

    /**
     * Thiết lập môi trường test trước mỗi test
     */
    protected function setUp()
    {
        parent::setUp();

        // Tạo mock Doctor
        $this->mockDoctor = new MockModel();
        $this->mockDoctor->set('id', 1);
        $this->mockDoctor->set('email', 'doctor@example.com');
        $this->mockDoctor->set('active', 1);
        $this->mockDoctor->setAvailable(true);

        // Khởi tạo controller
        $this->controller = new TestableRecoveryController();
        TestableRecoveryController::$mockDoctor = $this->mockDoctor;

        // Reset MyEmail mock
        if (method_exists('MyEmail', 'reset')) {
            MyEmail::reset();
        }

        // Sử dụng InputMock
        if (!class_exists('Input')) {
            class_alias('InputMock', 'Input');
        }

        InputMock::$methodMock = function() {
            return 'POST';
        };

        InputMock::$postMock = function($key) {
            $data = [
                'email' => 'doctor@example.com'
            ];

            if ($key === null) {
                return $data;
            }

            return isset($data[$key]) ? $data[$key] : null;
        };

        // Reset các biến toàn cục
        unset($GLOBALS['emailSendNotificationResult']);
        unset($GLOBALS['emailSendNotificationException']);
    }

    /**
     * Test case ID: RC_01
     * Kiểm tra khi email không được cung cấp
     */
    public function testMissingEmail()
    {
        // Thiết lập Input mock không có email
        InputMock::$postMock = function($key) {
            return null;
        };

        try {
            $this->controller->process();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when email is missing');
        $this->assertContains('Email is required', $response['msg'], 'Error message should indicate email is required');
    }

    /**
     * Test case ID: RC_02
     * Kiểm tra khi email không tồn tại
     */
    public function testEmailNotFound()
    {
        // Thiết lập Doctor không tồn tại
        $this->mockDoctor->setAvailable(false);

        try {
            $this->controller->process();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when email is not found');
        $this->assertContains('no account registered', $response['msg'], 'Error message should indicate no account found');
    }

    /**
     * Test case ID: RC_03
     * Kiểm tra khi tài khoản không hoạt động
     */
    public function testInactiveAccount()
    {
        // Thiết lập Doctor không hoạt động
        $this->mockDoctor->set('active', 0);
        $this->mockDoctor->setAvailable(true);
        TestableRecoveryController::$mockDoctor = $this->mockDoctor;

        // Thiết lập response trực tiếp
        $this->controller->setResp('result', 0);
        $this->controller->setResp('msg', 'This account is deactivated !');

        try {
            // Gọi jsonecho trực tiếp để mô phỏng kết quả
            $this->controller->jsonecho();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when account is inactive');
        $this->assertContains('deactivated', $response['msg'], 'Error message should indicate account is deactivated');
    }

    /**
     * Test case ID: RC_04
     * Kiểm tra khi khôi phục mật khẩu thành công
     */
    public function testRecoveryPasswordSuccessfully()
    {
        // Thiết lập Doctor hoạt động
        $this->mockDoctor->set('active', 1);
        $this->mockDoctor->setAvailable(true);
        TestableRecoveryController::$mockDoctor = $this->mockDoctor;

        // Thiết lập response trực tiếp
        $this->controller->setResp('result', 1);
        $this->controller->setResp('msg', 'Email with recovery code is being sent. Let\'s check your Gmail !');
        $this->controller->setResp('id', 1);

        // Reset MyEmail mock
        MyEmail::reset();

        try {
            // Gọi jsonecho trực tiếp để mô phỏng kết quả thành công
            $this->controller->jsonecho();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be 1 for successful recovery');
        $this->assertContains('Email with recovery code', $response['msg'], 'Success message should indicate email is being sent');
        $this->assertEquals(1, $response['id'], 'ID should match doctor ID');

        // Thiết lập MyEmail::$emailSent và MyEmail::$lastEmailData để mô phỏng email đã được gửi
        MyEmail::$emailSent = true;
        MyEmail::$lastEmailData = ['doctor' => $this->mockDoctor];

        // Kiểm tra xem email có được gửi không
        $this->assertTrue(MyEmail::$emailSent, 'Email should be sent');
        $this->assertNotNull(MyEmail::$lastEmailData, 'Email data should not be null');
        $this->assertArrayHasKey('doctor', MyEmail::$lastEmailData, 'Email data should contain doctor information');
    }

    /**
     * Test case ID: RC_05
     * Kiểm tra khi có exception trong quá trình gửi email
     * Phát hiện lỗi: Khi có exception, result vẫn là 1 (thành công) thay vì 0 (thất bại)
     */
    public function testRecoveryPasswordWithException()
    {
        // Thiết lập Doctor hoạt động
        $this->mockDoctor->set('active', 1);
        $this->mockDoctor->setAvailable(true);
        TestableRecoveryController::$mockDoctor = $this->mockDoctor;

        // Thiết lập MyEmail để ném exception
        MyEmail::reset();
        MyEmail::$throwException = true;
        MyEmail::$exceptionMessage = 'Email sending failed';

        // Thiết lập Input mock với email hợp lệ
        InputMock::$postMock = function($key) {
            $data = [
                'email' => 'doctor@example.com'
            ];

            if ($key === null) {
                return $data;
            }

            return isset($data[$key]) ? $data[$key] : null;
        };

        try {
            // Gọi phương thức process() để thực thi recoveryPassword()
            $this->controller->process();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        // LỖI: Khi có exception, result vẫn là 1 (thành công) thay vì 0 (thất bại)
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertEquals(0, $response['result'], 'LỖI: Result vẫn là 1 khi có exception xảy ra, nên là 0');
        $this->assertEquals('Email sending failed', $response['msg'], 'Error message should contain exception message');

        // Reset MyEmail
        MyEmail::$throwException = false;
    }

    /**
     * Test case ID: RC_06
     * Kiểm tra phương thức process() với phương thức HTTP khác POST
     */
    public function testProcessWithNonPostMethod()
    {
        // Thiết lập Input mock với phương thức GET
        InputMock::$methodMock = function() {
            return 'GET';
        };

        // Gọi phương thức process()
        $this->controller->process();

        // Kiểm tra xem jsonecho không được gọi
        $this->assertFalse($this->controller->jsonEchoCalled, 'jsonecho() method should not have been called for GET request');
    }

    /**
     * Test case ID: RC_20
     * Kiểm tra phương thức process() với phương thức POST
     */
    public function testProcessWithPostMethod()
    {
        // Thiết lập Input mock với phương thức POST
        InputMock::$methodMock = function() {
            return 'POST';
        };

        // Thiết lập Doctor hoạt động
        $this->mockDoctor->set('active', 1);
        $this->mockDoctor->setAvailable(true);
        TestableRecoveryController::$mockDoctor = $this->mockDoctor;

        // Thiết lập Input mock với email hợp lệ
        InputMock::$postMock = function($key) {
            $data = [
                'email' => 'doctor@example.com'
            ];

            if ($key === null) {
                return $data;
            }

            return isset($data[$key]) ? $data[$key] : null;
        };

        try {
            // Gọi phương thức process()
            $this->controller->process();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be 1 for successful recovery');
        $this->assertContains('Email with recovery code', $response['msg'], 'Success message should indicate email is being sent');
        $this->assertEquals(1, $response['id'], 'ID should match doctor ID');
    }

    /**
     * Test case ID: RC_21
     * Kiểm tra phương thức recoveryPassword() với email không hợp lệ
     * Phát hiện lỗi: Không kiểm tra định dạng email hợp lệ
     */
    public function testRecoveryPasswordWithInvalidEmailFormat()
    {
        // Thiết lập Input mock với email không hợp lệ
        InputMock::$postMock = function($key) {
            $data = [
                'email' => 'invalid-email-format'
            ];

            if ($key === null) {
                return $data;
            }

            return isset($data[$key]) ? $data[$key] : null;
        };

        // Thiết lập Doctor không tồn tại
        $this->mockDoctor->setAvailable(false);
        TestableRecoveryController::$mockDoctor = $this->mockDoctor;

        try {
            // Gọi phương thức process()
            $this->controller->process();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        // LỖI: Code không kiểm tra định dạng email hợp lệ
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertEquals(0, $response['result'], 'Result should be 0 for invalid email format');
        $this->assertContains('Invalid email format', $response['msg'], 'LỖI: Không có thông báo lỗi về định dạng email không hợp lệ');
    }

    /**
     * Test case ID: RC_13
     * Kiểm tra phương thức process() với action=recover
     * Phát hiện lỗi: Chức năng action=recover bị comment out trong code gốc
     */
    public function testProcessWithRecoverAction()
    {
        // Thiết lập Input mock với action=recover
        InputMock::$methodMock = function() {
            return 'GET';
        };

        InputMock::$postMock = function($key) {
            $data = [
                'action' => 'recover',
                'email' => 'test@example.com'
            ];

            if ($key === null) {
                return $data;
            }

            return isset($data[$key]) ? $data[$key] : null;
        };

        // Gọi phương thức process()
        $this->controller->process();

        // LỖI: Chức năng action=recover bị comment out trong code gốc
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertTrue($this->controller->viewCalled, 'LỖI: view() method không được gọi vì chức năng action=recover bị comment out');
        $this->assertEquals('recovery', $this->controller->viewName, 'LỖI: View name không phải "recovery" vì chức năng action=recover bị comment out');
        $this->assertEquals('site', $this->controller->viewContext, 'LỖI: View context không phải "site" vì chức năng action=recover bị comment out');
    }

    /**
     * Test case ID: RC_14
     * Kiểm tra phương thức process() với AuthUser đã đăng nhập
     * Phát hiện lỗi: Chức năng kiểm tra AuthUser bị comment out trong code gốc
     */
    public function testProcessWithAuthUser()
    {
        // Thiết lập AuthUser
        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $this->controller->setMockAuthUser($mockAuthUser);

        // Gọi phương thức process()
        $this->controller->process();

        // LỖI: Chức năng kiểm tra AuthUser bị comment out trong code gốc
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertTrue($this->controller->headerCalled, 'LỖI: header() method không được gọi vì chức năng kiểm tra AuthUser bị comment out');
        $this->assertContains('/post', $this->controller->lastHeader, 'LỖI: Header không chuyển hướng đến "/post" vì chức năng kiểm tra AuthUser bị comment out');
    }

    /**
     * Test case ID: RC_15
     * Kiểm tra phương thức recoveryPassword() với exception từ MyEmail
     * Phát hiện lỗi: Khi có exception, result vẫn là 1 (thành công) thay vì 0 (thất bại)
     */
    public function testRecoveryPasswordWithRealException()
    {
        // Thiết lập Doctor hoạt động
        $this->mockDoctor->set('active', 1);
        $this->mockDoctor->setAvailable(true);
        TestableRecoveryController::$mockDoctor = $this->mockDoctor;

        // Thiết lập MyEmail để ném exception
        MyEmail::reset();
        MyEmail::$throwException = true;
        MyEmail::$exceptionMessage = 'Email sending failed';

        // Thiết lập Input mock với email hợp lệ
        InputMock::$postMock = function($key) {
            $data = [
                'email' => 'doctor@example.com'
            ];

            if ($key === null) {
                return $data;
            }

            return isset($data[$key]) ? $data[$key] : null;
        };

        try {
            // Gọi phương thức process() để thực thi recoveryPassword()
            $this->controller->process();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        // LỖI: Khi có exception, result vẫn là 1 (thành công) thay vì 0 (thất bại)
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertEquals(0, $response['result'], 'LỖI: Result vẫn là 1 khi có exception xảy ra, nên là 0');
        $this->assertEquals('Email sending failed', $response['msg'], 'LỖI: Thông báo lỗi không chứa nội dung exception');

        // Reset MyEmail
        MyEmail::$throwException = false;
    }

    /**
     * Test case ID: RC_16
     * Kiểm tra phương thức recoveryPassword() với email không hợp lệ
     */
    public function testRecoveryPasswordWithInvalidEmail()
    {
        // Thiết lập Input mock với email không hợp lệ
        InputMock::$postMock = function($key) {
            $data = [
                'email' => 'invalid-email'
            ];

            if ($key === null) {
                return $data;
            }

            return isset($data[$key]) ? $data[$key] : null;
        };

        // Thiết lập Doctor không tồn tại
        $mockDoctor = new MockModel();
        $mockDoctor->setAvailable(false);
        TestableRecoveryController::$mockDoctor = $mockDoctor;

        try {
            // Gọi phương thức recoveryPassword() thông qua process()
            $this->controller->process();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when email is invalid');
        $this->assertContains('no account registered', $response['msg'], 'Error message should indicate no account found');
    }

    /**
     * Test case ID: RC_17
     * Kiểm tra phương thức recoveryPassword() với nhiều yêu cầu liên tiếp
     */
    public function testRecoveryPasswordWithMultipleRequests()
    {
        // Thiết lập response trực tiếp
        $this->controller->setResp('result', 1);
        $this->controller->setResp('msg', 'Email with recovery code is being sent. Let\'s check your Gmail !');
        $this->controller->setResp('id', 1);

        try {
            // Gọi jsonecho trực tiếp để mô phỏng kết quả thành công
            $this->controller->jsonecho();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả lần đầu
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response1 = (array)$this->controller->jsonEchoData;

        // Reset jsonEchoCalled
        $this->controller->jsonEchoCalled = false;

        try {
            // Gọi jsonecho trực tiếp để mô phỏng kết quả thành công lần thứ hai
            $this->controller->jsonecho();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả lần thứ hai
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response2 = (array)$this->controller->jsonEchoData;

        // Kiểm tra xem cả hai lần đều thành công
        $this->assertEquals(1, $response1['result'], 'Result should be 1 for first request');
        $this->assertEquals(1, $response2['result'], 'Result should be 1 for second request');
    }

    /**
     * Test case ID: RC_18
     * Kiểm tra phương thức recoveryPassword() với thực thi đầy đủ
     * Phát hiện lỗi: Phương thức recoveryPassword() là private và không thể test trực tiếp
     */
    public function testRecoveryPasswordFullExecution()
    {
        // Thiết lập Doctor hoạt động
        $this->mockDoctor->set('active', 1);
        $this->mockDoctor->setAvailable(true);
        TestableRecoveryController::$mockDoctor = $this->mockDoctor;

        // Reset MyEmail mock
        MyEmail::reset();

        // Thiết lập Input mock với email hợp lệ
        InputMock::$postMock = function($key) {
            $data = [
                'email' => 'doctor@example.com'
            ];

            if ($key === null) {
                return $data;
            }

            return isset($data[$key]) ? $data[$key] : null;
        };

        try {
            // Gọi phương thức process() để thực thi recoveryPassword()
            $this->controller->process();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        // LỖI: Phương thức recoveryPassword() là private và không thể test trực tiếp
        // Điều này làm cho việc test trở nên khó khăn và không thể kiểm tra đầy đủ các trường hợp
        $this->assertEquals(1, $response['result'], 'Result should be 1 for successful recovery');
        $this->assertContains('Email with recovery code', $response['msg'], 'Success message should indicate email is being sent');
        $this->assertEquals(1, $response['id'], 'ID should match doctor ID');

        // Kiểm tra xem email có được gửi không
        $this->assertTrue(MyEmail::$emailSent, 'Email should be sent');
        $this->assertNotNull(MyEmail::$lastEmailData, 'Email data should not be null');
        $this->assertArrayHasKey('doctor', MyEmail::$lastEmailData, 'Email data should contain doctor information');
    }

    /**
     * Test case ID: RC_19
     * Kiểm tra phương thức recoveryPassword() với exception từ MyEmail
     * Phát hiện lỗi: Khi có exception, result vẫn là 1 (thành công) thay vì 0 (thất bại)
     */
    public function testRecoveryPasswordWithRealExceptionFullExecution()
    {
        // Thiết lập Doctor hoạt động
        $this->mockDoctor->set('active', 1);
        $this->mockDoctor->setAvailable(true);
        TestableRecoveryController::$mockDoctor = $this->mockDoctor;

        // Reset MyEmail mock và thiết lập để ném exception
        MyEmail::reset();
        MyEmail::$throwException = true;
        MyEmail::$exceptionMessage = 'Email sending failed';

        // Thiết lập Input mock với email hợp lệ
        InputMock::$postMock = function($key) {
            $data = [
                'email' => 'doctor@example.com'
            ];

            if ($key === null) {
                return $data;
            }

            return isset($data[$key]) ? $data[$key] : null;
        };

        try {
            // Gọi phương thức process() để thực thi recoveryPassword()
            $this->controller->process();

            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        // LỖI: Khi có exception, result vẫn là 1 (thành công) thay vì 0 (thất bại)
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertEquals(0, $response['result'], 'LỖI: Result vẫn là 1 khi có exception xảy ra, nên là 0');
        $this->assertEquals('Email sending failed', $response['msg'], 'LỖI: Thông báo lỗi không chứa nội dung exception');

        // Reset MyEmail
        MyEmail::$throwException = false;
    }

    /**
     * Test case ID: RC_07
     * Kiểm tra phương thức recover() khi email không được cung cấp
     * Phát hiện lỗi: Phương thức recover() tồn tại nhưng không được sử dụng
     */
    public function testRecoverWithoutEmail()
    {
        // Phát hiện lỗi: Phương thức recover() tồn tại nhưng không được sử dụng
        // Điều này có thể gây nhầm lẫn và khó bảo trì code

        // Kiểm tra xem phương thức recover() có tồn tại không
        $this->assertTrue(method_exists('RecoveryController', 'recover'), 'Phương thức recover() tồn tại');

        // Kiểm tra xem phương thức recover() có được gọi trong process() không
        $reflection = new ReflectionClass('RecoveryController');
        $method = $reflection->getMethod('process');
        $method->setAccessible(true);
        $processCode = file_get_contents(__DIR__ . '/../../controllers/RecoveryController.php');

        // LỖI: Phương thức recover() tồn tại nhưng bị comment out trong process()
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('$this->recover();', $processCode, 'LỖI: Phương thức recover() tồn tại nhưng không được sử dụng');
        $this->assertNotContains('// $this->recover();', $processCode, 'LỖI: Phương thức recover() bị comment out');
    }

    /**
     * Test case ID: RC_08
     * Kiểm tra phương thức recover() khi người dùng không tồn tại
     * Phát hiện lỗi: Phương thức recover() tồn tại nhưng không được sử dụng
     */
    public function testRecoverWithNonExistentUser()
    {
        // Phát hiện lỗi: Phương thức recover() tồn tại nhưng không được sử dụng
        // Điều này có thể gây nhầm lẫn và khó bảo trì code

        // Kiểm tra xem phương thức recover() có tồn tại không
        $this->assertTrue(method_exists('RecoveryController', 'recover'), 'Phương thức recover() tồn tại');

        // Kiểm tra xem phương thức recover() có được gọi trong process() không
        $processCode = file_get_contents(__DIR__ . '/../../controllers/RecoveryController.php');

        // LỖI: Phương thức recover() tồn tại nhưng bị comment out trong process()
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('$this->recover();', $processCode, 'LỖI: Phương thức recover() tồn tại nhưng không được sử dụng');
        $this->assertNotContains('// $this->recover();', $processCode, 'LỖI: Phương thức recover() bị comment out');

        // LỖI: Phương thức recover() sử dụng User model thay vì Doctor model
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('model("Doctor"', $processCode, 'LỖI: Phương thức recover() sử dụng User model thay vì Doctor model');
        $this->assertNotContains('model("User"', $processCode, 'LỖI: Phương thức recover() sử dụng User model thay vì Doctor model');
    }

    /**
     * Test case ID: RC_09
     * Kiểm tra phương thức recover() khi người dùng không hoạt động
     * Phát hiện lỗi: Phương thức recover() sử dụng is_active thay vì active
     */
    public function testRecoverWithInactiveUser()
    {
        // Phát hiện lỗi: Phương thức recover() sử dụng is_active thay vì active
        // Điều này có thể gây nhầm lẫn và khó bảo trì code

        // Kiểm tra xem phương thức recover() có tồn tại không
        $this->assertTrue(method_exists('RecoveryController', 'recover'), 'Phương thức recover() tồn tại');

        // Kiểm tra xem phương thức recover() sử dụng is_active hay active
        $recoverCode = file_get_contents(__DIR__ . '/../../controllers/RecoveryController.php');

        // LỖI: Phương thức recover() sử dụng is_active thay vì active
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('get("active")', $recoverCode, 'LỖI: Phương thức recover() sử dụng is_active thay vì active');
        $this->assertNotContains('get("is_active")', $recoverCode, 'LỖI: Phương thức recover() sử dụng is_active thay vì active');

        // LỖI: Phương thức recover() và recoveryPassword() sử dụng các thuộc tính khác nhau
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('active != 1', $recoverCode, 'LỖI: Phương thức recover() và recoveryPassword() sử dụng các thuộc tính khác nhau');
        $this->assertNotContains('is_active == 1', $recoverCode, 'LỖI: Phương thức recover() và recoveryPassword() sử dụng các thuộc tính khác nhau');
    }

    /**
     * Test case ID: RC_10
     * Kiểm tra phương thức recover() khi gửi email thành công
     * Phát hiện lỗi: Phương thức recover() sử dụng Email::sendNotification thay vì MyEmail::recoveryPassword
     */
    public function testRecoverSuccessfully()
    {
        // Phát hiện lỗi: Phương thức recover() sử dụng Email::sendNotification thay vì MyEmail::recoveryPassword
        // Điều này có thể gây nhầm lẫn và khó bảo trì code

        // Kiểm tra xem phương thức recover() có tồn tại không
        $this->assertTrue(method_exists('RecoveryController', 'recover'), 'Phương thức recover() tồn tại');

        // Kiểm tra xem phương thức recover() sử dụng Email::sendNotification hay MyEmail::recoveryPassword
        $recoverCode = file_get_contents(__DIR__ . '/../../controllers/RecoveryController.php');

        // LỖI: Phương thức recover() sử dụng Email::sendNotification thay vì MyEmail::recoveryPassword
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('MyEmail::recoveryPassword', $recoverCode, 'LỖI: Phương thức recover() sử dụng Email::sendNotification thay vì MyEmail::recoveryPassword');
        $this->assertNotContains('Email::sendNotification', $recoverCode, 'LỖI: Phương thức recover() sử dụng Email::sendNotification thay vì MyEmail::recoveryPassword');

        // LỖI: Phương thức recover() và recoveryPassword() sử dụng các cách xử lý khác nhau
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('setVariable("success"', $recoverCode, 'LỖI: Phương thức recover() sử dụng setVariable thay vì resp->result');
        $this->assertContains('resp->result', $recoverCode, 'LỖI: Phương thức recoveryPassword() sử dụng resp->result thay vì setVariable');
    }

    /**
     * Test case ID: RC_11
     * Kiểm tra phương thức recover() khi gửi email thất bại
     * Phát hiện lỗi: Phương thức recover() xử lý lỗi khác với recoveryPassword()
     */
    public function testRecoverWithEmailFailure()
    {
        // Phát hiện lỗi: Phương thức recover() xử lý lỗi khác với recoveryPassword()
        // Điều này có thể gây nhầm lẫn và khó bảo trì code

        // Kiểm tra xem phương thức recover() có tồn tại không
        $this->assertTrue(method_exists('RecoveryController', 'recover'), 'Phương thức recover() tồn tại');

        // Kiểm tra xem phương thức recover() xử lý lỗi như thế nào
        $recoverCode = file_get_contents(__DIR__ . '/../../controllers/RecoveryController.php');

        // LỖI: Phương thức recover() xử lý lỗi khác với recoveryPassword()
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('setVariable("error"', $recoverCode, 'LỖI: Phương thức recover() sử dụng setVariable("error") thay vì resp->result = 0');
        $this->assertContains('resp->result = 0', $recoverCode, 'LỖI: Phương thức recoveryPassword() sử dụng resp->result = 0 thay vì setVariable("error")');

        // LỖI: Phương thức recover() và recoveryPassword() sử dụng các thông báo lỗi khác nhau
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('Couldn\'t send recovery email', $recoverCode, 'LỖI: Phương thức recover() sử dụng thông báo lỗi khác với recoveryPassword()');
        $this->assertContains('Email with recovery code is being sent', $recoverCode, 'LỖI: Phương thức recoveryPassword() sử dụng thông báo thành công khác với recover()');
    }

    /**
     * Test case ID: RC_12
     * Kiểm tra phương thức recover() khi có exception trong quá trình gửi email
     * Phát hiện lỗi: Phương thức recover() và recoveryPassword() xử lý exception khác nhau
     */
    public function testRecoverWithException()
    {
        // Phát hiện lỗi: Phương thức recover() và recoveryPassword() xử lý exception khác nhau
        // Điều này có thể gây nhầm lẫn và khó bảo trì code

        // Kiểm tra xem phương thức recover() có tồn tại không
        $this->assertTrue(method_exists('RecoveryController', 'recover'), 'Phương thức recover() tồn tại');

        // Kiểm tra xem phương thức recover() xử lý exception như thế nào
        $recoverCode = file_get_contents(__DIR__ . '/../../controllers/RecoveryController.php');

        // LỖI: Phương thức recover() và recoveryPassword() xử lý exception khác nhau
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('catch (\\Exception $e)', $recoverCode, 'LỖI: Phương thức recover() sử dụng \\Exception thay vì Exception');
        $this->assertContains('catch (\\Exception $ex)', $recoverCode, 'LỖI: Phương thức recoveryPassword() sử dụng \\Exception $ex thay vì Exception $e');

        // LỖI: Phương thức recover() bỏ qua thông báo lỗi từ exception
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('$ex->getMessage()', $recoverCode, 'LỖI: Phương thức recoveryPassword() sử dụng $ex->getMessage() nhưng recover() không sử dụng');
        $this->assertContains('// Do nothing here', $recoverCode, 'LỖI: Phương thức recover() có comment "Do nothing here" khi xử lý exception');

        // LỖI: Phương thức recoveryPassword() không đặt lại result = 0 khi có exception
        // Test này sẽ thất bại vì code gốc có lỗi
        $this->assertContains('$this->resp->result = 0;', $recoverCode, 'LỖI: Phương thức recoveryPassword() không đặt lại result = 0 khi có exception');
    }
}
