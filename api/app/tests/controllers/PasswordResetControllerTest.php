<?php
/**
 * Test cho PasswordResetController
 *
 * Class: PasswordResetControllerTest
 * File: api/app/tests/controllers/PasswordResetControllerTest.php
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
require_once __DIR__ . '/TestablePasswordResetController.php';

class PasswordResetControllerTest extends ControllerTestCase
{
    /**
     * @var PasswordResetController Controller instance
     */
    protected $controller;

    /**
     * @var TestablePasswordResetController Testable controller instance
     */
    protected $testableController;

    /**
     * Set up test environment before each test
     * Thiết lập môi trường test trước mỗi test
     */
    protected function setUp()
    {
        parent::setUp();

        // Khởi tạo controller
        $this->controller = $this->createController('PasswordResetController');

        // Khởi tạo testable controller
        $this->testableController = new TestablePasswordResetController();

        // Reset mock data
        TestablePasswordResetController::resetMockData();
    }

    /**
     * PASSWORDRESET_001
     * Kiểm tra cấu trúc của PasswordResetController
     * Test PasswordResetController structure
     */
    public function testPasswordResetControllerStructure()
    {
        // Kiểm tra xem controller có phương thức process() không
        $this->assertTrue(method_exists($this->controller, 'process'), 'Controller should have process() method');

        // Kiểm tra xem controller có phương thức resetPassword() không
        $reflection = new ReflectionClass($this->controller);
        $this->assertTrue($reflection->hasMethod('resetPassword'), 'Controller should have resetPassword() method');

        // Kiểm tra xem phương thức resetPassword() có private không
        $method = $reflection->getMethod('resetPassword');
        $this->assertTrue($method->isPrivate(), 'resetPassword() method should be private');

        // Kiểm tra xem controller có phương thức resetpass() không
        $this->assertTrue($reflection->hasMethod('resetpass'), 'Controller should have resetpass() method');

        // Kiểm tra xem phương thức resetpass() có private không
        $method = $reflection->getMethod('resetpass');
        $this->assertTrue($method->isPrivate(), 'resetpass() method should be private');
    }

    /**
     * PASSWORDRESET_002
     * Kiểm tra phương thức process() với request method POST
     * Test process() method with POST request method
     */
    public function testProcessMethodWithPostRequest()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputMethod = 'POST';

        // Gọi phương thức process()
        $this->testableController->process();

        // Kiểm tra xem jsonecho() có được gọi không
        $this->assertTrue($this->testableController->jsonEchoCalled, 'jsonecho() should be called');
    }

    /**
     * PASSWORDRESET_003
     * Kiểm tra phương thức process() với request method GET
     * Test process() method with GET request method
     */
    public function testProcessMethodWithGetRequest()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputMethod = 'GET';

        // Gọi phương thức process()
        $this->testableController->process();

        // Kiểm tra xem jsonecho() không được gọi
        $this->assertFalse($this->testableController->jsonEchoCalled, 'jsonecho() should not be called');
    }

    /**
     * PASSWORDRESET_004
     * Kiểm tra phương thức resetPassword() với thiếu ID
     * Test resetPassword() method with missing ID
     */
    public function testResetPasswordWithMissingId()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputPost = [
            'recovery_token' => 'valid_token',
            'password' => 'password123',
            'passwordConfirm' => 'password123',
            'test_case' => 'missing_id'
        ];

        // Gọi phương thức resetPassword()
        $this->testableController->callResetPassword();

        // Kiểm tra xem jsonecho() có được gọi không
        $this->assertTrue($this->testableController->jsonEchoCalled, 'jsonecho() should be called');

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $this->testableController->jsonEchoData->result, 'resp->result should be 0 for failed password reset');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertContains('ID is required', $this->testableController->jsonEchoData->msg, 'resp->msg should contain error message about missing ID');
    }

    /**
     * PASSWORDRESET_005
     * Kiểm tra phương thức resetPassword() với thiếu trường bắt buộc
     * Test resetPassword() method with missing required field
     */
    public function testResetPasswordWithMissingRequiredField()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputPost = [
            'recovery_token' => 'valid_token',
            'password' => 'password123'
            // Thiếu passwordConfirm
        ];

        // Gọi phương thức resetPassword()
        $this->testableController->callResetPassword();

        // Kiểm tra xem jsonecho() có được gọi không
        $this->assertTrue($this->testableController->jsonEchoCalled, 'jsonecho() should be called');

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $this->testableController->jsonEchoData->result, 'resp->result should be 0 for failed password reset');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertContains('Missing field', $this->testableController->jsonEchoData->msg, 'resp->msg should contain error message about missing field');
    }

    /**
     * PASSWORDRESET_006
     * Kiểm tra phương thức resetPassword() với mật khẩu quá ngắn
     * Test resetPassword() method with too short password
     */
    public function testResetPasswordWithShortPassword()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputPost = [
            'recovery_token' => 'valid_token',
            'password' => 'short',
            'passwordConfirm' => 'short'
        ];

        // Thiết lập mock Doctor data
        TestablePasswordResetController::$mockDoctorData = [
            'recovery_token' => 'valid_token'
        ];

        // Gọi phương thức resetPassword()
        $this->testableController->callResetPassword();

        // Kiểm tra xem jsonecho() có được gọi không
        $this->assertTrue($this->testableController->jsonEchoCalled, 'jsonecho() should be called');

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $this->testableController->jsonEchoData->result, 'resp->result should be 0 for failed password reset');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertContains('Password must have at least 6 characters', $this->testableController->jsonEchoData->msg, 'resp->msg should contain error message about password length');
    }

    /**
     * PASSWORDRESET_007
     * Kiểm tra phương thức resetPassword() với mật khẩu xác nhận không khớp
     * Test resetPassword() method with mismatched password confirmation
     */
    public function testResetPasswordWithMismatchedPasswordConfirmation()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputPost = [
            'recovery_token' => 'valid_token',
            'password' => 'password123',
            'passwordConfirm' => 'password456'
        ];

        // Thiết lập mock Doctor data
        TestablePasswordResetController::$mockDoctorData = [
            'recovery_token' => 'valid_token'
        ];

        // Gọi phương thức resetPassword()
        $this->testableController->callResetPassword();

        // Kiểm tra xem jsonecho() có được gọi không
        $this->assertTrue($this->testableController->jsonEchoCalled, 'jsonecho() should be called');

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $this->testableController->jsonEchoData->result, 'resp->result should be 0 for failed password reset');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertContains('Confirmation password is not equal to password', $this->testableController->jsonEchoData->msg, 'resp->msg should contain error message about password confirmation');
    }

    /**
     * PASSWORDRESET_008
     * Kiểm tra phương thức resetPassword() với recovery token không hợp lệ
     * Test resetPassword() method with invalid recovery token
     */
    public function testResetPasswordWithInvalidRecoveryToken()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputPost = [
            'recovery_token' => 'invalid_token',
            'password' => 'password123',
            'passwordConfirm' => 'password123'
        ];

        // Thiết lập mock Doctor data
        TestablePasswordResetController::$mockDoctorData = [
            'recovery_token' => 'valid_token'
        ];

        // Gọi phương thức resetPassword()
        $this->testableController->callResetPassword();

        // Kiểm tra xem jsonecho() có được gọi không
        $this->assertTrue($this->testableController->jsonEchoCalled, 'jsonecho() should be called');

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $this->testableController->jsonEchoData->result, 'resp->result should be 0 for failed password reset');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertContains('Recovery token is not correct', $this->testableController->jsonEchoData->msg, 'resp->msg should contain error message about recovery token');
    }

    /**
     * PASSWORDRESET_009
     * Kiểm tra phương thức resetPassword() với Doctor không tồn tại
     * Test resetPassword() method with non-existent Doctor
     */
    public function testResetPasswordWithNonExistentDoctor()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputPost = [
            'recovery_token' => 'valid_token',
            'password' => 'password123',
            'passwordConfirm' => 'password123'
        ];

        // Thiết lập mock Doctor data không tồn tại
        TestablePasswordResetController::$mockDoctorData = [
            'id' => null,
            'email' => null
        ];

        // Gọi phương thức resetPassword()
        $this->testableController->callResetPassword();

        // Kiểm tra xem jsonecho() có được gọi không
        $this->assertTrue($this->testableController->jsonEchoCalled, 'jsonecho() should be called');

        // Kiểm tra xem resp->result có được đặt thành 0 không
        $this->assertEquals(0, $this->testableController->jsonEchoData->result, 'resp->result should be 0 for failed password reset');

        // Kiểm tra xem resp->msg có chứa thông báo lỗi không
        $this->assertContains('This account is not available', $this->testableController->jsonEchoData->msg, 'resp->msg should contain error message about non-existent account');
    }

    /**
     * PASSWORDRESET_010
     * Kiểm tra phương thức resetPassword() với dữ liệu hợp lệ
     * Test resetPassword() method with valid data
     */
    public function testResetPasswordWithValidData()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputPost = [
            'recovery_token' => 'valid_token',
            'password' => 'password123',
            'passwordConfirm' => 'password123'
        ];

        // Thiết lập mock Doctor data
        TestablePasswordResetController::$mockDoctorData = [
            'recovery_token' => 'valid_token'
        ];

        // Gọi phương thức resetPassword()
        $this->testableController->callResetPassword();

        // Kiểm tra xem jsonecho() có được gọi không
        $this->assertTrue($this->testableController->jsonEchoCalled, 'jsonecho() should be called');

        // Kiểm tra xem resp->result có được đặt thành 1 không
        $this->assertEquals(1, $this->testableController->jsonEchoData->result, 'resp->result should be 1 for successful password reset');

        // Kiểm tra xem resp->msg có chứa thông báo thành công không
        $this->assertContains('Password is recovered successfully', $this->testableController->jsonEchoData->msg, 'resp->msg should contain success message');
    }

    /**
     * PASSWORDRESET_011
     * Kiểm tra phương thức resetpass() với thiếu trường bắt buộc
     * Test resetpass() method with missing required field
     */
    public function testResetpassWithMissingRequiredField()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputPost = [
            'password' => 'password123'
            // Thiếu password-confirm
        ];

        // Gọi phương thức resetpass()
        $result = $this->testableController->callResetpass();

        // Kiểm tra xem phương thức có trả về controller không
        $this->assertSame($this->testableController, $result, 'resetpass() method should return $this');

        // Kiểm tra xem biến error có được đặt không
        $reflection = new ReflectionClass($this->testableController);
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        $variables = $property->getValue($this->testableController);

        $this->assertArrayHasKey('error', $variables, 'error variable should be set');
        $this->assertContains('All fields are required', $variables['error'], 'error should contain message about required fields');
    }

    /**
     * PASSWORDRESET_012
     * Kiểm tra phương thức resetpass() với mật khẩu quá ngắn
     * Test resetpass() method with too short password
     */
    public function testResetpassWithShortPassword()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputPost = [
            'password' => 'short',
            'password-confirm' => 'short'
        ];

        // Gọi phương thức resetpass()
        $result = $this->testableController->callResetpass();

        // Kiểm tra xem phương thức có trả về controller không
        $this->assertSame($this->testableController, $result, 'resetpass() method should return $this');

        // Kiểm tra xem biến error có được đặt không
        $reflection = new ReflectionClass($this->testableController);
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        $variables = $property->getValue($this->testableController);

        $this->assertArrayHasKey('error', $variables, 'error variable should be set');
        $this->assertContains('Password must be at least 6 character length', $variables['error'], 'error should contain message about password length');
    }

    /**
     * PASSWORDRESET_013
     * Kiểm tra phương thức resetpass() với mật khẩu xác nhận không khớp
     * Test resetpass() method with mismatched password confirmation
     */
    public function testResetpassWithMismatchedPasswordConfirmation()
    {
        // Thiết lập mock data
        TestablePasswordResetController::$mockInputPost = [
            'password' => 'password123',
            'password-confirm' => 'password456'
        ];

        // Gọi phương thức resetpass()
        $result = $this->testableController->callResetpass();

        // Kiểm tra xem phương thức có trả về controller không
        $this->assertSame($this->testableController, $result, 'resetpass() method should return $this');

        // Kiểm tra xem biến error có được đặt không
        $reflection = new ReflectionClass($this->testableController);
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        $variables = $property->getValue($this->testableController);

        $this->assertArrayHasKey('error', $variables, 'error variable should be set');
        $this->assertContains('Password confirmation didn\'t match', $variables['error'], 'error should contain message about password confirmation');
    }
}
