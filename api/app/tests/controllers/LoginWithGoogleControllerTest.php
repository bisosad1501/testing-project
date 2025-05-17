<?php
/**
 * Test cho LoginWithGoogleController
 *
 * Class: LoginWithGoogleControllerTest
 * File: api/app/tests/controllers/LoginWithGoogleControllerTest.php
 *
 */

// Định nghĩa các hằng số cần thiết cho test
if (!defined('EC_SALT')) {
    define('EC_SALT', 'test_salt_for_unit_tests');
}
require_once __DIR__ . '/../ControllerTestCase.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

// Include MockAuthUser
require_once __DIR__ . '/../mocks/MockAuthUser.php';

// Mock cho Auth
class AuthMock {
    public static $authUserMock;

    public static function user() {
        return self::$authUserMock;
    }
}

// Sử dụng InputMock từ ControllerTestCase

// Sử dụng MockDB từ helper.php
// Đổi tên để tránh xung đột
class GoogleLoginDBMock extends MockDB {
    public static $tableMock;
    public static $whereConditions = [];
    public static $data = [];

    public static function table($table) {
        if (isset(self::$tableMock) && is_callable(self::$tableMock)) {
            $func = self::$tableMock;
            return $func($table);
        }

        // Reset where conditions
        self::$whereConditions = [];

        // Trả về mock mặc định nếu không có mock cụ thể
        $mock = new stdClass();

        // Mock cho where()
        $mock->where = function($field, $operator = null, $value = null) use ($mock, $table) {
            // Nếu chỉ có 2 tham số, giả sử operator là '=' và value là tham số thứ 2
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }

            // Lưu điều kiện where
            GoogleLoginDBMock::$whereConditions[] = [
                'table' => $table,
                'field' => $field,
                'operator' => $operator,
                'value' => $value
            ];

            return $mock;
        };

        // Mock cho whereIn()
        $mock->whereIn = function($field, $values) use ($mock, $table) {
            // Lưu điều kiện whereIn
            GoogleLoginDBMock::$whereConditions[] = [
                'table' => $table,
                'field' => $field,
                'operator' => 'IN',
                'value' => $values
            ];

            return $mock;
        };

        // Mock cho get()
        $mock->get = function() use ($table) {
            // Nếu có data được thiết lập cho bảng này, trả về data đó
            if (isset(GoogleLoginDBMock::$data[$table])) {
                return GoogleLoginDBMock::$data[$table];
            }

            // Mặc định trả về mảng rỗng
            return [];
        };

        // Mock cho insert()
        $mock->insert = function($data) use ($table) {
            // Thêm data vào bảng
            if (!isset(GoogleLoginDBMock::$data[$table])) {
                GoogleLoginDBMock::$data[$table] = [];
            }

            // Thêm ID nếu chưa có
            if (!isset($data['id'])) {
                $data['id'] = count(GoogleLoginDBMock::$data[$table]) + 1;
            }

            // Thêm data vào bảng
            GoogleLoginDBMock::$data[$table][] = (object)$data;

            return $data['id']; // Trả về ID
        };

        // Mock cho update()
        $mock->update = function($data) use ($table) {
            // Nếu không có data trong bảng, trả về false
            if (!isset(GoogleLoginDBMock::$data[$table])) {
                return false;
            }

            // Cập nhật data trong bảng
            foreach (GoogleLoginDBMock::$data[$table] as &$row) {
                foreach ($data as $key => $value) {
                    $row->$key = $value;
                }
            }

            return true; // Giả sử update thành công
        };

        return $mock;
    }

    // Thiết lập data cho bảng
    public static function setTableData($table, $data) {
        self::$data[$table] = $data;
    }

    // Reset mock
    public static function reset() {
        self::$tableMock = null;
        self::$whereConditions = [];
        self::$data = [];
    }
}

// Định nghĩa lớp Patient mock
class MockPatient
{
    private $data = [];

    public function __construct($id = null, $data = [])
    {
        $this->data = $data;

        if (!isset($this->data['id']) && $id) {
            $this->data['id'] = $id;
        }
    }

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function save()
    {
        // Giả lập lưu dữ liệu
        if (!isset($this->data['id'])) {
            $this->data['id'] = rand(1, 1000);
        }
        return $this;
    }
}

class LoginWithGoogleControllerTest extends ControllerTestCase
{
    /**
     * Test data
     */
    private $testData = [
        'patients' => [
            'patient1' => [
                'id' => 1,
                'email' => 'patient@example.com',
                'password' => 'password123',
                'phone' => '0987654321',
                'name' => 'Test Patient',
                'gender' => 1,
                'birthday' => '1990-01-01',
                'address' => 'Test Address',
                'avatar' => 'default_avatar.jpg',
                'create_at' => '2023-01-01 00:00:00',
                'update_at' => '2023-01-01 00:00:00'
            ]
        ]
    ];

    /**
     * Set up
     */
    public function setUp()
    {
        // Reset mock DB
        GoogleLoginDBMock::reset();
    }

    /**
     * Mock Input
     *
     * @param array $data
     */
    private function mockInput($data)
    {
        // Thiết lập mock cho Input::post()
        InputMock::$postMock = function($key = null) use ($data) {
            if ($key === null) {
                return $data;
            }
            return isset($data[$key]) ? $data[$key] : null;
        };
    }

    /**
     * GOOGLE_PROCESS_001
     * Kiểm tra xử lý khi người dùng đã đăng nhập
     * Test when user is already logged in
     */
    public function testProcessWithAuthUser()
    {
        // Thiết lập AuthUser
        AuthMock::$authUserMock = (object)$this->testData['patients']['patient1'];

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginWithGoogleController')
            ->setMethods(['jsonecho'])
            ->getMock();

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Kiểm tra response
                $this->assertEquals(1, $resp->result, 'Result should be success (1)');
                $this->assertEquals("You already logged in", $resp->msg, 'Message should indicate already logged in');

                // Giả lập exit bằng cách ném exception
                throw new Exception("Exit called");
            }));

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập AuthUser
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        $variables = [];
        $variables['AuthUser'] = AuthMock::$authUserMock;
        $property->setValue($mockController, $variables);

        // Thiết lập response
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = new stdClass();
        $resp->result = 0;
        $property->setValue($mockController, $resp);

        // Gọi phương thức process() trực tiếp
        $method = $reflection->getMethod('process');
        $method->setAccessible(true);

        try {
            $method->invoke($mockController);
            $this->fail('jsonecho() should have been called and thrown an exception');
        } catch (Exception $e) {
            // Kiểm tra xem exception có phải từ jsonecho() không
            $this->assertEquals("Exit called", $e->getMessage(), 'jsonecho() should have been called');
        }
    }

    /**
     * GOOGLE_PROCESS_002
     * Kiểm tra xử lý khi người dùng chưa đăng nhập
     * Test when user is not logged in
     */
    public function testProcessWithoutAuthUser()
    {
        // Thiết lập AuthUser là null
        AuthMock::$authUserMock = null;

        // Tạo mock controller để tránh gọi loginWithGoogle()
        $mockController = $this->getMockBuilder('LoginWithGoogleController')
            ->setMethods(['loginWithGoogle', 'jsonecho'])
            ->getMock();

        // Thiết lập mock để kiểm tra xem loginWithGoogle() có được gọi không
        $mockController->expects($this->once())
            ->method('loginWithGoogle');

        // Thiết lập mock để cho phép jsonecho() được gọi (vì code gốc thực sự gọi nó)
        $mockController->expects($this->any())
            ->method('jsonecho');

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập AuthUser = null
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        $variables = [];
        $variables['AuthUser'] = null;
        $property->setValue($mockController, $variables);

        // Gọi phương thức process() trực tiếp
        $method = $reflection->getMethod('process');
        $method->setAccessible(true);
        $method->invoke($mockController);

        // Nếu đến đây mà không có lỗi, test đã thành công
        $this->assertTrue(true, 'process() method without AuthUser works correctly');
    }

    /**
     * GOOGLE_LOGIN_001
     * Kiểm tra xác thực khi thiếu trường bắt buộc
     * Test when required field is missing
     */
    public function testLoginWithGoogleMissingField()
    {
        // Thiết lập Input thiếu trường bắt buộc
        $this->mockInput(['type' => 'patient', 'email' => 'patient@example.com']);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginWithGoogleController')
            ->setMethods(['jsonecho'])
            ->getMock();

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Kiểm tra response
                $this->assertEquals(0, $resp->result, 'Result should be error (0)');
                $this->assertEquals("Missing field password", $resp->msg, 'Message should indicate missing field');

                // Giả lập exit bằng cách ném exception
                throw new Exception("Exit called");
            }));

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập response
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = new stdClass();
        $resp->result = 0;
        $property->setValue($mockController, $resp);

        // Gọi phương thức loginWithGoogle() trực tiếp
        $method = $reflection->getMethod('loginWithGoogle');
        $method->setAccessible(true);

        try {
            $method->invoke($mockController);
            $this->fail('jsonecho() should have been called and thrown an exception');
        } catch (Exception $e) {
            // Kiểm tra xem exception có phải từ jsonecho() không
            $this->assertEquals("Exit called", $e->getMessage(), 'jsonecho() should have been called');
        }
    }

    /**
     * GOOGLE_LOGIN_002
     * Kiểm tra xác thực khi type không phải là patient
     * Test when type is not patient
     */
    public function testLoginWithGoogleInvalidType()
    {
        // Thiết lập Input với type không hợp lệ
        $this->mockInput(['type' => 'doctor', 'email' => 'patient@example.com', 'password' => 'password123']);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginWithGoogleController')
            ->setMethods(['jsonecho'])
            ->getMock();

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Kiểm tra response
                $this->assertEquals(0, $resp->result, 'Result should be error (0)');
                $this->assertEquals("Your request's type is doctor & it's not valid !", $resp->msg, 'Message should indicate invalid type');

                // Giả lập exit bằng cách ném exception
                throw new Exception("Exit called");
            }));

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập response
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = new stdClass();
        $resp->result = 0;
        $property->setValue($mockController, $resp);

        // Gọi phương thức loginWithGoogle() trực tiếp
        $method = $reflection->getMethod('loginWithGoogle');
        $method->setAccessible(true);

        try {
            $method->invoke($mockController);
            $this->fail('jsonecho() should have been called and thrown an exception');
        } catch (Exception $e) {
            // Kiểm tra xem exception có phải từ jsonecho() không
            $this->assertEquals("Exit called", $e->getMessage(), 'jsonecho() should have been called');
        }
    }

    /**
     * GOOGLE_LOGIN_003
     * Kiểm tra xác thực khi tài khoản không tồn tại (tạo mới tài khoản)
     * Test when account does not exist (create new account)
     */
    public function testLoginWithGoogleNewAccount()
    {
        // Thiết lập Input với email mới
        $email = 'new_patient@example.com';
        $password = 'password123';
        $this->mockInput(['type' => 'patient', 'email' => $email, 'password' => $password]);

        // Tạo mock controller để tránh gọi jsonecho() và model()
        $mockController = $this->getMockBuilder('LoginWithGoogleController')
            ->setMethods(['jsonecho', 'model'])
            ->getMock();

        // Thiết lập data cho bảng patient (rỗng)
        GoogleLoginDBMock::setTableData('tn_patient', []);

        // Tạo mock Patient
        $mockPatient = new MockPatient();
        $mockPatient->set('id', 1);
        $mockPatient->set('email', $email);
        $mockPatient->set('password', password_hash($password, PASSWORD_DEFAULT));

        // Thiết lập mock để trả về Patient mới
        $mockController->expects($this->any())
            ->method('model')
            ->will($this->returnValue($mockPatient));

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController, $email) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Kiểm tra response
                $this->assertEquals(1, $resp->result, 'Result should be success (1)');
                $this->assertEquals("Patient account has been CREATE successfully", $resp->msg, 'Message should indicate account creation');
                $this->assertObjectHasAttribute('accessToken', $resp, 'Response should include accessToken');
                $this->assertObjectHasAttribute('data', $resp, 'Response should include data');

                // Kiểm tra data
                $this->assertEquals($email, $resp->data['email'], 'Data should include correct email');

                // Giả lập exit bằng cách ném exception
                throw new Exception("Exit called");
            }));

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập response
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = new stdClass();
        $resp->result = 0;
        $property->setValue($mockController, $resp);

        // Gọi phương thức loginWithGoogle() trực tiếp
        $method = $reflection->getMethod('loginWithGoogle');
        $method->setAccessible(true);

        try {
            $method->invoke($mockController);
            $this->fail('jsonecho() should have been called and thrown an exception');
        } catch (Exception $e) {
            // Kiểm tra xem exception có phải từ jsonecho() không
            $this->assertEquals("Exit called", $e->getMessage(), 'jsonecho() should have been called');
        }
    }

    /**
     * GOOGLE_LOGIN_004
     * Kiểm tra xác thực khi tài khoản tồn tại nhưng password không đúng
     * Test when account exists but password is incorrect
     */
    public function testLoginWithGoogleIncorrectPassword()
    {
        // Thiết lập Input với email tồn tại nhưng password sai
        $email = 'patient@example.com';
        $password = 'wrong_password';
        $this->mockInput(['type' => 'patient', 'email' => $email, 'password' => $password]);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginWithGoogleController')
            ->setMethods(['jsonecho', 'model'])
            ->getMock();

        // Tạo patient với password đã hash
        $patient = (object)$this->testData['patients']['patient1'];
        $patient->id = 1;
        $patient->email = $email;
        $patient->password = password_hash('correct_password', PASSWORD_DEFAULT); // Không phải password đã nhập

        // Thiết lập mock để trả về Patient tồn tại
        $mockController->expects($this->any())
            ->method('model')
            ->will($this->returnValue($patient));

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Kiểm tra response
                $this->assertEquals(0, $resp->result, 'Result should be error (0)');
                $this->assertEquals("Your email or password is incorrect. Try again !", $resp->msg, 'Message should indicate incorrect password');

                // Giả lập exit bằng cách ném exception
                throw new Exception("Exit called");
            }));

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập response
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = new stdClass();
        $resp->result = 0;
        $property->setValue($mockController, $resp);

        // Gọi phương thức loginWithGoogle() trực tiếp
        $method = $reflection->getMethod('loginWithGoogle');
        $method->setAccessible(true);

        try {
            $method->invoke($mockController);
            $this->fail('jsonecho() should have been called and thrown an exception');
        } catch (Exception $e) {
            // Kiểm tra xem exception có phải từ jsonecho() không
            $this->assertEquals("Exit called", $e->getMessage(), 'jsonecho() should have been called');
        }
    }

    /**
     * GOOGLE_LOGIN_005
     * Kiểm tra xác thực khi tài khoản tồn tại và password đúng
     * Test when account exists and password is correct
     */
    public function testLoginWithGoogleCorrectPassword()
    {
        // Thiết lập Input với email tồn tại và password đúng
        $email = 'patient@example.com';
        $password = 'password123';
        $this->mockInput(['type' => 'patient', 'email' => $email, 'password' => $password]);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginWithGoogleController')
            ->setMethods(['jsonecho', 'model'])
            ->getMock();

        // Tạo patient với password đã hash
        $patient = (object)$this->testData['patients']['patient1'];
        $patient->id = 1;
        $patient->email = $email;
        $patient->password = password_hash($password, PASSWORD_DEFAULT); // Password khớp với input

        // Thiết lập mock để trả về Patient tồn tại
        $mockController->expects($this->any())
            ->method('model')
            ->will($this->returnValue($patient));

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController, $email) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Kiểm tra response
                $this->assertEquals(1, $resp->result, 'Result should be success (1)');
                $this->assertEquals("Patient has been LOGGED IN successfully !", $resp->msg, 'Message should indicate successful login');
                $this->assertObjectHasAttribute('accessToken', $resp, 'Response should include accessToken');
                $this->assertObjectHasAttribute('data', $resp, 'Response should include data');

                // Kiểm tra data
                $this->assertEquals($email, $resp->data['email'], 'Data should include correct email');

                // Giả lập exit bằng cách ném exception
                throw new Exception("Exit called");
            }));

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập response
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = new stdClass();
        $resp->result = 0;
        $property->setValue($mockController, $resp);

        // Gọi phương thức loginWithGoogle() trực tiếp
        $method = $reflection->getMethod('loginWithGoogle');
        $method->setAccessible(true);

        try {
            $method->invoke($mockController);
            $this->fail('jsonecho() should have been called and thrown an exception');
        } catch (Exception $e) {
            // Kiểm tra xem exception có phải từ jsonecho() không
            $this->assertEquals("Exit called", $e->getMessage(), 'jsonecho() should have been called');
        }
    }
}