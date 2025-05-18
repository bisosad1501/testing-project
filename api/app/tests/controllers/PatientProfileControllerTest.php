<?php
/**
 * Unit tests for PatientProfileController
 *
 * File: api/app/tests/controllers/PatientProfileControllerTest.php
 * Class: PatientProfileControllerTest
 *
 * Test suite cho các chức năng của PatientProfileController:
 * - Lấy thông tin cá nhân bệnh nhân (getInformation)
 * - Cập nhật thông tin cá nhân (changeInformation)
 * - Đổi mật khẩu (changePassword)
 * - Đổi ảnh đại diện (changeAvatar)
 */

// Biến global để kiểm soát kết quả của password_verify trong test
$password_verify_return = null;

// Định nghĩa phiên bản test của password_verify
if (!function_exists('password_verify')) {
    function password_verify($password, $hash) {
        global $password_verify_return;
        return $password_verify_return;
    }
}

require_once __DIR__ . '/../ControllerTestCase.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

// Định nghĩa các hàm validator để tránh phụ thuộc vào triển khai thực tế
if (!function_exists('isVietnameseName')) {
    function isVietnameseName($string) {
        if (empty($string)) return 0;
        if (strpos($string, '@') !== false || strpos($string, '$') !== false) return 0;
        return 1;
    }
}

if (!function_exists('isAddress')) {
    function isAddress($string) {
        if (empty($string)) return 0;
        if (strpos($string, '@') !== false || strpos($string, '<') !== false || strpos($string, '>') !== false) return 0;
        return 1;
    }
}

// Include MockAuthUser
require_once __DIR__ . '/../mocks/MockAuthUser.php';

/**
 * Lớp con của PatientProfileController để mô phỏng các phương thức và phục vụ test
 */
class TestablePatientProfileController extends \PatientProfileController
{
    public $jsonEchoCalled = false;
    public $jsonEchoData = null;
    public $headerCalled = false;
    public $lastHeader = '';
    public $exitCalled = false;
    public $moveUploadedFileResult = true; // Giả lập kết quả của move_uploaded_file
    public static $mockPatient = null;
    public static $useMockModel = false;
    public static $modelCallback = null;

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

    public function setMockPatient($patient)
    {
        self::$mockPatient = $patient;
    }

    public function setMoveUploadedFileResult($result)
    {
        $this->moveUploadedFileResult = $result;
    }

    public function move_uploaded_file($tmp_name, $destination)
    {
        error_log("Mock move_uploaded_file called: Moving $tmp_name to $destination, returning: " . ($this->moveUploadedFileResult ? 'true' : 'false'));
        return $this->moveUploadedFileResult;
    }

    public static function model($name, $id = 0)
    {
        error_log("TestablePatientProfileController::model called with name: $name, id: $id");
        if (self::$useMockModel && is_callable(self::$modelCallback)) {
            $result = call_user_func(self::$modelCallback, $name, $id);
            error_log("Model callback returned: " . ($result ? 'Mock object' : 'null'));
            return $result;
        }
        if ($name == 'Patient' && isset(self::$mockPatient)) {
            error_log("Returning mockPatient");
            return self::$mockPatient;
        }
        error_log("Calling parent::model for $name, $id");
        return parent::model($name, $id);
    }
}

class PatientProfileControllerTest extends ControllerTestCase
{
    protected $controller;
    protected $testData;

    protected function setUp()
    {
        parent::setUp();
        $this->controller = new TestablePatientProfileController();
        $this->testData = [
            'users' => [
                'patient' => [
                    'id' => 100,
                    'email' => 'patient@example.com',
                    'phone' => '0123456789',
                    'name' => 'Test Patient',
                    'gender' => 1,
                    'birthday' => '1990-01-01',
                    'address' => '123 Test Street',
                    'avatar' => 'default_avatar.jpg',
                    'create_at' => '2022-01-01 00:00:00',
                    'update_at' => '2022-01-01 00:00:00',
                    'password' => password_hash('password123', PASSWORD_DEFAULT)
                ],
                'inactive_patient' => [
                    'id' => 101,
                    'email' => 'inactive@example.com',
                    'phone' => '0123456788',
                    'name' => 'Inactive Patient',
                    'gender' => 0,
                    'birthday' => '1985-05-15',
                    'address' => '456 Inactive Street',
                    'avatar' => 'default_avatar.jpg',
                    'create_at' => '2022-01-01 00:00:00',
                    'update_at' => '2022-01-01 00:00:00',
                    'password' => password_hash('password123', PASSWORD_DEFAULT)
                ],
                'doctor' => [
                    'id' => 1,
                    'email' => 'doctor@example.com',
                    'phone' => '0987654321',
                    'name' => 'Doctor Name',
                    'gender' => 1,
                    'role' => 'member',
                    'birthday' => '1980-01-01',
                    'address' => '789 Doctor Street',
                    'avatar' => 'default_avatar.jpg',
                    'create_at' => '2022-01-01 00:00:00',
                    'update_at' => '2022-01-01 00:00:00',
                    'password' => password_hash('password123', PASSWORD_DEFAULT)
                ]
            ]
        ];

        // Định nghĩa hằng số UPLOAD_PATH để tránh lỗi
        if (!defined('UPLOAD_PATH')) {
            define('UPLOAD_PATH', '/tmp/uploads');
        }

        // Tạo thư mục UPLOAD_PATH nếu chưa tồn tại
        if (!file_exists(UPLOAD_PATH)) {
            mkdir(UPLOAD_PATH, 0777, true);
        }
    }

    protected function mockAuthUser($role = 'patient')
    {
        $userData = $this->testData['users'][$role];
        $authUser = new MockAuthUser(isset($userData['role']) ? $userData['role'] : null, $userData);
        $authUser->setAvailable(true);

        $mockPatient = new MockAuthUser(isset($userData['role']) ? $userData['role'] : null, $userData);
        $mockPatient->setAvailable(true);
        $mockPatient->set('password', $userData['password']);

        $this->controller->setMockAuthUser($authUser);
        $this->controller->setMockPatient($mockPatient);

        // Bật chế độ test và thiết lập modelMethod cho Controller
        Controller::$testMode = true;
        TestablePatientProfileController::$useMockModel = true;
        Controller::$modelMethod = TestablePatientProfileController::$modelCallback = function($name, $id) use ($mockPatient) {
            error_log("Model callback called for name: $name, id: $id, returning mockPatient");
            if ($name == 'Patient') {
                return $mockPatient;
            }
            return null;
        };

        return $authUser;
    }

    protected function mockInput($method = 'GET', $data = [])
    {
        InputMock::$methodMock = function() use ($method) {
            return $method;
        };
        InputMock::$getMock = null;
        InputMock::$postMock = null;

        switch ($method) {
            case 'GET':
                InputMock::$getMock = function($key) use ($data) {
                    return isset($data[$key]) ? $data[$key] : null;
                };
                break;
            case 'POST':
                InputMock::$postMock = function($key) use ($data) {
                    return isset($data[$key]) ? $data[$key] : null;
                };
                break;
        }
    }

    protected function callControllerWithCapture()
    {
        ob_start();
        try {
            $this->controller->process();
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'JsonEchoExit') !== 0) {
                echo "Unexpected exception in test: " . $e->getMessage();
            }
        }
        ob_end_clean();

        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = $property->getValue($this->controller);

        return (array)$resp;
    }

    /**
     * Test case ID: PCC_01
     * Test chuyển hướng khi người dùng chưa xác thực
     */
    public function testRedirectWhenUnauthenticated()
    {
        // Không thiết lập AuthUser để giả lập người dùng chưa đăng nhập
        $this->assertNull($this->controller->getVariable('AuthUser'), 'AuthUser should be null for unauthenticated user');

        // Kiểm tra logic redirect trực tiếp thay vì gọi process()
        ob_start();
        try {
            // Gọi process() nhưng chỉ kiểm tra kết quả của header
            $this->controller->process();
        } catch (Exception $e) {
            $this->assertEquals('ExitCalled', $e->getMessage(), 'Expected ExitCalled exception');
        }
        ob_end_clean();

        // Kiểm tra xem phương thức header có được gọi không
        $this->assertTrue($this->controller->headerCalled, 'header() method should have been called for redirect');
        
        // Kiểm tra giá trị của header
        $this->assertContains('/login', $this->controller->lastHeader, 'Header should redirect to login page');
    }

    /**
     * Test case ID: PCC_02
     * Test khi người dùng không phải là bệnh nhân
     */
    public function testDenyNonPatientAccess()
    {
        $this->mockAuthUser('doctor');
        $this->mockInput('GET');

        try {
            $this->controller->process();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 for non-patient users');
        $this->assertContains('not allowed', $response['msg'], 'Error message should indicate not allowed');
    }

    /**
     * Test case ID: PCC_03
     * Test lấy thông tin hồ sơ bệnh nhân
     */
    public function testRetrievePatientProfile()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('GET');

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getInformation');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be success when getting patient profile');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');
        $this->assertArrayHasKey('id', $response['data'], 'Response data should include id');
        $this->assertArrayHasKey('name', $response['data'], 'Response data should include name');
        $this->assertArrayHasKey('gender', $response['data'], 'Response data should include gender');
        $this->assertArrayHasKey('phone', $response['data'], 'Response data should include phone');
        $this->assertArrayHasKey('email', $response['data'], 'Response data should include email');
        $this->assertArrayHasKey('birthday', $response['data'], 'Response data should include birthday');
        $this->assertArrayHasKey('address', $response['data'], 'Response data should include address');
        $this->assertArrayHasKey('avatar', $response['data'], 'Response data should include avatar');
    }

    /**
     * Test case ID: PCC_04
     * Test cập nhật hồ sơ bệnh nhân thành công
     */
    public function testUpdateProfileSuccessfully()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('POST', [
            'action' => 'personal',
            'name' => 'New Name',
            'gender' => 1,
            'birthday' => '01-01-1990',
            'address' => 'New Address Street 123'
        ]);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changeInformation');
            $method->setAccessible(true);
            $method->invoke($this->controller);
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be success when updating patient profile: ' . (isset($response['msg']) ? $response['msg'] : 'No message'));
        $this->assertContains('updated successfully', $response['msg'], 'Success message should indicate profile updated');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');
        $this->assertEquals('New Name', $response['data']['name'], 'Name should be updated');
        $this->assertEquals('New Address Street 123', $response['data']['address'], 'Address should be updated');
    }

    /**
     * Test case ID: PCC_05
     * Test cập nhật hồ sơ với trường dữ liệu thiếu
     */
    public function testUpdateProfileWithMissingFields()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('POST', [
            'action' => 'personal',
            'gender' => 1,
            'birthday' => '01-01-1990',
            'address' => 'New Address Street 123'
        ]);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changeInformation');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when missing fields');
        $this->assertContains('Missing field', $response['msg'], 'Error message should indicate missing field');
    }

    /**
     * Test case ID: PCC_06
     * Test cập nhật hồ sơ với tên không hợp lệ
     */
    public function testUpdateProfileWithInvalidName()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('POST', [
            'action' => 'personal',
            'name' => 'Invalid@#$%^&*()',
            'gender' => 1,
            'birthday' => '01-01-1990',
            'address' => 'Valid Address 123'
        ]);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changeInformation');
            $method->setAccessible(true);
            $method->invoke($this->controller);
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when name is invalid: ' . (isset($response['msg']) ? $response['msg'] : 'No message'));
        $this->assertContains('Vietnamese name', $response['msg'], 'Error message should indicate invalid name format');
    }

    /**
     * Test case ID: PCC_07
     * Test cập nhật hồ sơ với giới tính không hợp lệ
     */
    public function testUpdateProfileWithInvalidGender()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('POST', [
            'action' => 'personal',
            'name' => 'Valid Name',
            'gender' => 2,
            'birthday' => '01-01-1990',
            'address' => 'New Address Street 123'
        ]);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changeInformation');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when gender is invalid');
        $this->assertContains('Gender value is not correct', $response['msg'], 'Error message should indicate invalid gender');
    }

    /**
     * Test case ID: PCC_08
     * Test cập nhật hồ sơ với ngày sinh không hợp lệ
     */
    public function testUpdateProfileWithInvalidBirthday()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('POST', [
            'action' => 'personal',
            'name' => 'Valid Name',
            'gender' => 1,
            'birthday' => '01-01-2030',
            'address' => 'New Address Street 123'
        ]);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changeInformation');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when birthday is invalid');
        $this->assertContains('birthday is not valid', $response['msg'], 'Error message should indicate invalid birthday');
    }

    /**
     * Test case ID: PCC_09
     * Test cập nhật hồ sơ với địa chỉ không hợp lệ
     */
    public function testUpdateProfileWithInvalidAddress()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('POST', [
            'action' => 'personal',
            'name' => 'Valid Name',
            'gender' => 1,
            'birthday' => '01-01-1990',
            'address' => 'Invalid<Address>@#'
        ]);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changeInformation');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when address is invalid');
        $this->assertContains('Address only accepts', $response['msg'], 'Error message should indicate invalid address');
    }

    /**
     * Test case ID: PCC_10
     * Test đổi mật khẩu thành công
     */
    public function testChangePasswordSuccessfully()
    {
        $this->mockAuthUser('patient');
        global $password_verify_return;
        $password_verify_return = true;

        $this->mockInput('POST', [
            'action' => 'password',
            'currentPassword' => 'password123',
            'newPassword' => 'newpassword123',
            'confirmPassword' => 'newpassword123'
        ]);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changePassword');
            $method->setAccessible(true);
            $method->invoke($this->controller);
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $password_verify_return = null;
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be success when changing password: ' . (isset($response['msg']) ? $response['msg'] : 'No message'));
        $this->assertContains('updated successfully', $response['msg'], 'Success message should indicate password updated');
    }

    /**
     * Test case ID: PCC_11
     * Test đổi mật khẩu với mật khẩu hiện tại không đúng
     */
    public function testChangePasswordWithIncorrectCurrentPassword()
    {
        $this->mockAuthUser('patient');
        global $password_verify_return;
        $password_verify_return = false;

        $this->mockInput('POST', [
            'action' => 'password',
            'currentPassword' => 'wrongpassword',
            'newPassword' => 'newpassword123',
            'confirmPassword' => 'newpassword123'
        ]);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changePassword');
            $method->setAccessible(true);
            $method->invoke($this->controller);
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $password_verify_return = null;
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when current password is incorrect: ' . (isset($response['msg']) ? $response['msg'] : 'No message'));
        $this->assertContains('current password is incorrect', $response['msg'], 'Error message should indicate incorrect current password');
    }

    /**
     * Test case ID: PCC_12
     * Test đổi mật khẩu với mật khẩu mới quá ngắn
     */
    public function testChangePasswordWithTooShortNewPassword()
    {
        $this->mockAuthUser('patient');
        global $password_verify_return;
        $password_verify_return = true;

        $this->mockInput('POST', [
            'action' => 'password',
            'currentPassword' => 'password123',
            'newPassword' => 'short',
            'confirmPassword' => 'short'
        ]);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changePassword');
            $method->setAccessible(true);
            $method->invoke($this->controller);
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $password_verify_return = null;
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when new password is too short: ' . (isset($response['msg']) ? $response['msg'] : 'No message'));
        $this->assertContains('at least 6 character', $response['msg'], 'Error message should indicate password too short');
    }

    /**
     * Test case ID: PCC_13
     * Test đổi mật khẩu với mật khẩu xác nhận không khớp
     */
    public function testChangePasswordWithMismatchedConfirmation()
    {
        $this->mockAuthUser('patient');
        global $password_verify_return;
        $password_verify_return = true;

        $this->mockInput('POST', [
            'action' => 'password',
            'currentPassword' => 'password123',
            'newPassword' => 'newpassword123',
            'confirmPassword' => 'different123'
        ]);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changePassword');
            $method->setAccessible(true);
            $method->invoke($this->controller);
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $password_verify_return = null;
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when confirmation password doesn\'t match: ' . (isset($response['msg']) ? $response['msg'] : 'No message'));
        $this->assertContains('confirmation does not equal', $response['msg'], 'Error message should indicate password mismatch');
    }

    /**
     * Test case ID: PCC_14
     * Test cập nhật ảnh đại diện thành công
     */
    public function testUpdateAvatarSuccessfully()
    {
        $this->mockAuthUser('patient');

        // Giả lập $_FILES với một file hợp lệ
        $_FILES = [
            'file' => [
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/test_avatar.jpg',
                'error' => 0, // UPLOAD_ERR_OK
                'size' => 1024
            ]
        ];

        // Giả lập move_uploaded_file trả về true
        $this->controller->setMoveUploadedFileResult(true);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changeAvatar');
            $method->setAccessible(true);
            $method->invoke($this->controller);
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be success when changing avatar: ' . (isset($response['msg']) ? $response['msg'] : 'No message'));
        $this->assertContains('Avatar has been updated successfully', $response['msg'], 'Success message should indicate avatar updated');
        
        // Dọn dẹp $_FILES
        $_FILES = [];
    }

    /**
     * Test case ID: PCC_15
     * Test cập nhật ảnh đại diện với định dạng không hợp lệ
     */
    public function testUpdateAvatarWithInvalidFormat()
    {
        $this->mockAuthUser('patient');

        // Giả lập $_FILES với một file không hợp lệ (định dạng .txt)
        $_FILES = [
            'file' => [
                'name' => 'avatar.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/test_avatar.txt',
                'error' => 0, // UPLOAD_ERR_OK
                'size' => 1024
            ]
        ];

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changeAvatar');
            $method->setAccessible(true);
            $method->invoke($this->controller);
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when file format is invalid: ' . (isset($response['msg']) ? $response['msg'] : 'No message'));
        $this->assertContains('Only jpeg,jpg,png files are allowed', $response['msg'], 'Error message should indicate invalid file format');
        
        // Dọn dẹp $_FILES
        $_FILES = [];
    }

    /**
     * Test case ID: PCC_16
     * Test xử lý hành động không hợp lệ
     */
    public function testHandleInvalidAction()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('POST', [
            'action' => 'invalid_action'
        ]);

        try {
            $this->controller->process();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error for invalid action');
        $this->assertContains('not valid', $response['msg'], 'Error message should indicate invalid action');
    }

    /**
     * Test case ID: PCC_17
     * Test cập nhật ảnh đại diện thất bại khi upload file
     */
    public function testUpdateAvatarWithUploadFailure()
    {
        $this->mockAuthUser('patient');

        // Giả lập $_FILES với một file hợp lệ
        $_FILES = [
            'file' => [
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/test_avatar.jpg',
                'error' => 0, // UPLOAD_ERR_OK
                'size' => 1024
            ]
        ];

        // Giả lập move_uploaded_file trả về false
        $this->controller->setMoveUploadedFileResult(false);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changeAvatar');
            $method->setAccessible(true);
            $method->invoke($this->controller);
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when upload fails: ' . (isset($response['msg']) ? $response['msg'] : 'No message'));
        $this->assertContains('Oops! An error occurred. Please try again later!', $response['msg'], 'Error message should indicate upload failure');
        
        // Dọn dẹp $_FILES
        $_FILES = [];
    }

    /**
     * Test case ID: PCC_18
     * Test cập nhật ảnh đại diện với file rỗng
     */
    public function testUpdateAvatarWithEmptyFile()
    {
        $this->mockAuthUser('patient');

        // Giả lập $_FILES với file rỗng
        $_FILES = [
            'file' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => 4, // UPLOAD_ERR_NO_FILE
                'size' => 0
            ]
        ];

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('changeAvatar');
            $method->setAccessible(true);
            $method->invoke($this->controller);
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when file is empty: ' . (isset($response['msg']) ? $response['msg'] : 'No message'));
        $this->assertContains('Photo is not received !', $response['msg'], 'Error message should indicate no file received');
        
        // Dọn dẹp $_FILES
        $_FILES = [];
    }
}