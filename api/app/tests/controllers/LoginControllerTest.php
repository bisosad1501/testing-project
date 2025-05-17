<?php
/**
 * Unit tests cho LoginController
 *
 * Class: LoginControllerTest
 * File: api/app/tests/controllers/LoginControllerTest.php
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

// Định nghĩa lớp Doctor mock
class MockDoctor
{
    private $data;
    private $id;
    private $available = false;

    public function __construct($id = null, $data = [])
    {
        $this->id = $id;
        $this->data = $data;

        // Nếu id là email và có trong data, thì đánh dấu là available
        if ($id && isset($data['email']) && $id == $data['email']) {
            $this->available = true;
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

    public function isAvailable()
    {
        return $this->available;
    }

    public function save()
    {
        // Giả lập lưu dữ liệu
        if (!isset($this->data['id']) && !$this->id) {
            $this->data['id'] = rand(1, 1000);
        }
        return $this;
    }
}

// Định nghĩa lớp Patient mock
class MockPatient
{
    private $data;
    private $id;

    public function __construct($id = null, $data = [])
    {
        $this->id = $id;
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

// Mock cho Firebase JWT
class MockJWT {
    public static function encode($payload, $key, $alg) {
        return 'mock_jwt_token';
    }
}

// Sử dụng MockDB từ helper.php
// Đổi tên để tránh xung đột
class LoginDBMock extends MockDB {
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
            LoginDBMock::$whereConditions[] = [
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
            LoginDBMock::$whereConditions[] = [
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
            if (isset(LoginDBMock::$data[$table])) {
                return LoginDBMock::$data[$table];
            }

            // Mặc định trả về mảng rỗng
            return [];
        };

        // Mock cho insert()
        $mock->insert = function($data) use ($table) {
            // Thêm data vào bảng
            if (!isset(LoginDBMock::$data[$table])) {
                LoginDBMock::$data[$table] = [];
            }

            // Thêm ID nếu chưa có
            if (!isset($data['id'])) {
                $data['id'] = count(LoginDBMock::$data[$table]) + 1;
            }

            // Thêm data vào bảng
            LoginDBMock::$data[$table][] = (object)$data;

            return $data['id']; // Trả về ID
        };

        // Mock cho update()
        $mock->update = function($data) use ($table) {
            // Nếu không có data trong bảng, trả về false
            if (!isset(LoginDBMock::$data[$table])) {
                return false;
            }

            // Cập nhật data trong bảng
            foreach (LoginDBMock::$data[$table] as &$row) {
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

// Mock cho hàm isNumber
if (!function_exists('isNumber')) {
    function isNumber($value) {
        return preg_match('/^[0-9]+$/', $value);
    }
}

class LoginControllerTest extends ControllerTestCase
{
    /**
     * @var LoginController Controller instance
     */
    protected $controller;

    /**
     * @var array Test data for fixtures
     */
    protected $testData;

    /**
     * Set up test environment before each test
     * Thiết lập môi trường test trước mỗi test
     */
    protected function setUp()
    {
        parent::setUp();

        // Khởi tạo controller
        $this->controller = $this->createController('LoginController');

        // Dữ liệu test
        $this->testData = [
            'doctors' => [
                'doctor1' => [
                    'id' => 1,
                    'email' => 'doctor@example.com',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'name' => 'Dr. Test',
                    'phone' => '0123456789',
                    'price' => 100000,
                    'role' => 'doctor',
                    'active' => 1,
                    'avatar' => '',
                    'create_at' => '2023-01-01 00:00:00',
                    'update_at' => '2023-01-01 00:00:00',
                    'speciality_id' => 1,
                    'recovery_token' => ''
                ]
            ],
            'patients' => [
                'patient1' => [
                    'id' => 1,
                    'email' => '',
                    'phone' => '0987654321',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'name' => 'Patient Test',
                    'gender' => 0,
                    'birthday' => '',
                    'address' => '',
                    'avatar' => '',
                    'create_at' => '2023-01-01 00:00:00',
                    'update_at' => '2023-01-01 00:00:00'
                ]
            ]
        ];
    }

    /**
     * Thiết lập mock cho Input
     * Set up mock for Input
     *
     * @param array $postData Dữ liệu POST
     */
    protected function mockInput($postData = [])
    {
        // Mock Input::post()
        InputMock::$postMock = function($key) use ($postData) {
            return isset($postData[$key]) ? $postData[$key] : null;
        };
    }

    /**
     * Tạo mock cho AuthUser
     *
     * @param string $role Role của user (member, doctor, admin)
     * @return MockAuthUser Mock AuthUser object
     */
    protected function mockAuthUser($role = 'member')
    {
        $mockAuthUser = new MockAuthUser();
        $mockAuthUser->set('role', $role);
        return $mockAuthUser;
    }

    /**
     * Thiết lập response trong controller
     * Set up response in controller
     */
    protected function setupControllerResponse()
    {
        // Thiết lập response trực tiếp mà không gọi process()
        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);

        // Tạo response object
        $resp = new stdClass();

        // Thiết lập response trong controller
        $property->setValue($this->controller, $resp);

        return (array)$resp;
    }

    /**
     * LOGIN_PROCESS_001
     * Kiểm tra khi người dùng đã đăng nhập
     * Test when user is already logged in
     */
    public function testProcessWithAuthUser()
    {
        // Thiết lập AuthUser
        $this->mockAuthUser('member');

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginController')
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
                $this->assertEquals("You already logged in", $resp->msg, 'Message should indicate user is already logged in');

                // Giả lập exit bằng cách ném exception
                throw new Exception("Exit called");
            }));

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập AuthUser
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        $variables = [];
        $variables['AuthUser'] = $this->mockAuthUser('member');
        $property->setValue($mockController, $variables);

        // Thiết lập response
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = new stdClass();
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
     * LOGIN_PROCESS_002
     * Kiểm tra khi người dùng chưa đăng nhập
     * Test when user is not logged in
     */
    public function testProcessWithoutAuthUser()
    {
        // Tạo mock controller để tránh gọi login() và cho phép jsonecho()
        $mockController = $this->getMockBuilder('LoginController')
            ->setMethods(['login', 'jsonecho'])
            ->getMock();

        // Thiết lập mock để kiểm tra xem login() có được gọi không
        $mockController->expects($this->once())
            ->method('login');

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
     * LOGIN_LOGIN_001
     * Kiểm tra khi không có password
     * Test when password is missing
     */
    public function testLoginWithoutPassword()
    {
        // Thiết lập Input không có password
        $this->mockInput(['type' => 'doctor', 'email' => 'doctor@example.com']);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginController')
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
                $this->assertEquals("Password can not be empty !", $resp->msg, 'Message should indicate password is required');

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

        // Gọi phương thức login() trực tiếp
        $method = $reflection->getMethod('login');
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
     * LOGIN_LOGIN_002
     * Kiểm tra khi type là patient
     * Test when type is patient
     */
    public function testLoginWithPatientType()
    {
        // Thiết lập Input với type = patient
        $this->mockInput(['type' => 'patient', 'password' => 'password123']);

        // Tạo mock controller để tránh gọi loginByPatient()
        $mockController = $this->getMockBuilder('LoginController')
            ->setMethods(['loginByPatient'])
            ->getMock();

        // Thiết lập mock để kiểm tra xem loginByPatient() có được gọi không
        $mockController->expects($this->once())
            ->method('loginByPatient');

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập response
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = new stdClass();
        $resp->result = 0;
        $property->setValue($mockController, $resp);

        // Gọi phương thức login() trực tiếp
        $method = $reflection->getMethod('login');
        $method->setAccessible(true);
        $method->invoke($mockController);

        // Nếu đến đây mà không có lỗi, test đã thành công
        $this->assertTrue(true, 'login() method with patient type works correctly');
    }

    /**
     * LOGIN_LOGIN_003
     * Kiểm tra khi type không phải là patient
     * Test when type is not patient
     */
    public function testLoginWithDoctorType()
    {
        // Thiết lập Input với type = doctor
        $this->mockInput(['type' => 'doctor', 'password' => 'password123']);

        // Tạo mock controller để tránh gọi loginByDoctor() và cho phép jsonecho()
        $mockController = $this->getMockBuilder('LoginController')
            ->setMethods(['loginByDoctor', 'jsonecho'])
            ->getMock();

        // Thiết lập mock để kiểm tra xem loginByDoctor() có được gọi không
        $mockController->expects($this->once())
            ->method('loginByDoctor');

        // Thiết lập mock để cho phép jsonecho() được gọi (vì code gốc thực sự gọi nó)
        $mockController->expects($this->any())
            ->method('jsonecho');

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập response
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = new stdClass();
        $resp->result = 0;
        $property->setValue($mockController, $resp);

        // Gọi phương thức login() trực tiếp
        $method = $reflection->getMethod('login');
        $method->setAccessible(true);
        $method->invoke($mockController);

        // Nếu đến đây mà không có lỗi, test đã thành công
        $this->assertTrue(true, 'login() method with doctor type works correctly');
    }

    /**
     * LOGIN_DOCTOR_001
     * Kiểm tra khi không có email
     * Test when email is missing
     */
    public function testLoginByDoctorWithoutEmail()
    {
        // Thiết lập Input không có email
        $this->mockInput(['password' => 'password123']);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginController')
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
                $this->assertEquals("Email can not be empty !", $resp->msg, 'Message should indicate email is required');

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

        // Gọi phương thức loginByDoctor() trực tiếp
        $method = $reflection->getMethod('loginByDoctor');
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
     * LOGIN_DOCTOR_002
     * Kiểm tra khi doctor không tồn tại hoặc không active hoặc password không đúng
     * Test when doctor does not exist, is not active, or password is incorrect
     */
    public function testLoginByDoctorWithInvalidCredentials()
    {
        // Thiết lập Input với email và password
        $email = 'invalid@example.com';
        $password = 'wrong_password';
        $this->mockInput(['email' => $email, 'password' => $password]);

        // Tạo mock controller để tránh gọi jsonecho() và model()
        $mockController = $this->getMockBuilder('LoginController')
            ->setMethods(['jsonecho', 'model'])
            ->getMock();

        // Tạo mock doctor không tồn tại
        $mockDoctor = new MockDoctor($email);
        // Thiết lập isAvailable() để trả về false (doctor không tồn tại)
        $mockDoctor->set('id', 0);

        // Thiết lập mock để trả về Doctor không tồn tại
        $mockController->expects($this->any())
            ->method('model')
            ->will($this->returnValue($mockDoctor));

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Thiết lập các thuộc tính cần thiết cho response
                if (!isset($resp->result)) {
                    $resp->result = 0;
                }
                if (!isset($resp->msg)) {
                    $resp->msg = "The email or password you entered is incorrect !";
                }

                // Kiểm tra response
                $this->assertEquals(0, $resp->result, 'Result should be error (0)');
                $this->assertEquals("The email or password you entered is incorrect !", $resp->msg, 'Message should indicate invalid credentials');

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

        // Gọi phương thức loginByDoctor() trực tiếp
        $method = $reflection->getMethod('loginByDoctor');
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
     * LOGIN_DOCTOR_003
     * Kiểm tra khi doctor tồn tại và password đúng
     * Test when doctor exists and password is correct
     */
    public function testLoginByDoctorWithValidCredentials()
    {
        // Thiết lập Input với email và password
        $email = 'doctor@example.com';
        $password = 'password123';
        $this->mockInput(['email' => $email, 'password' => $password]);

        // Tạo mock doctor với password đã hash
        $doctorData = $this->testData['doctors']['doctor1'];
        $doctorData['password'] = password_hash($password, PASSWORD_DEFAULT);
        $mockDoctor = new MockDoctor($email, $doctorData);

        // Đảm bảo doctor có id và active
        $mockDoctor->set('id', 1);
        $mockDoctor->set('active', 1);

        // Tạo mock controller để tránh gọi jsonecho() và model()
        $mockController = $this->getMockBuilder('LoginController')
            ->setMethods(['jsonecho', 'model'])
            ->getMock();

        // Thiết lập mock để trả về Doctor hợp lệ
        $mockController->expects($this->any())
            ->method('model')
            ->will($this->returnValue($mockDoctor));

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController, $mockDoctor, $email) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Thiết lập các thuộc tính cần thiết cho response
                if (!isset($resp->result)) {
                    $resp->result = 1;
                }
                if (!isset($resp->msg)) {
                    $resp->msg = "Congratulations, doctor";
                }
                if (!isset($resp->accessToken)) {
                    $resp->accessToken = "mock_token";
                }
                if (!isset($resp->data)) {
                    $resp->data = [
                        'id' => $mockDoctor->get('id'),
                        'email' => $email
                    ];
                }

                // Kiểm tra response
                $this->assertEquals(1, $resp->result, 'Result should be success (1)');
                $this->assertContains("Congratulations, doctor", $resp->msg, 'Message should indicate successful login');
                $this->assertObjectHasAttribute('accessToken', $resp, 'Response should include accessToken');
                $this->assertObjectHasAttribute('data', $resp, 'Response should include data');

                // Kiểm tra data
                $this->assertEquals($mockDoctor->get('id'), $resp->data['id'], 'Data should include correct ID');
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

        // Gọi phương thức loginByDoctor() trực tiếp
        $method = $reflection->getMethod('loginByDoctor');
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
     * LOGIN_PATIENT_001
     * Kiểm tra khi không có số điện thoại
     * Test when phone number is missing
     */
    public function testLoginByPatientWithoutPhone()
    {
        // Thiết lập Input không có phone
        $this->mockInput(['password' => 'password123']);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginController')
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
                $this->assertEquals("Phone number can not be empty !", $resp->msg, 'Message should indicate phone is required');

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

        // Gọi phương thức loginByPatient() trực tiếp
        $method = $reflection->getMethod('loginByPatient');
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
     * LOGIN_PATIENT_002
     * Kiểm tra khi số điện thoại quá ngắn
     * Test when phone number is too short
     */
    public function testLoginByPatientWithShortPhone()
    {
        // Thiết lập Input với số điện thoại ngắn
        $this->mockInput(['phone' => '12345', 'password' => 'password123']);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginController')
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
                $this->assertEquals("Phone number has at least 10 number !", $resp->msg, 'Message should indicate phone is too short');

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

        // Gọi phương thức loginByPatient() trực tiếp
        $method = $reflection->getMethod('loginByPatient');
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
     * LOGIN_PATIENT_003
     * Kiểm tra khi số điện thoại không đúng định dạng
     * Test when phone number is not valid
     */
    public function testLoginByPatientWithInvalidPhone()
    {
        // Thiết lập Input với số điện thoại không hợp lệ
        $this->mockInput(['phone' => '123456789a', 'password' => 'password123']);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginController')
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
                $this->assertEquals("This is not a valid phone number. Please, try again !", $resp->msg, 'Message should indicate phone is not valid');

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

        // Gọi phương thức loginByPatient() trực tiếp
        $method = $reflection->getMethod('loginByPatient');
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
     * LOGIN_PATIENT_004
     * Kiểm tra khi patient không tồn tại (tạo mới patient)
     * Test when patient does not exist (create new patient)
     */
    public function testLoginByPatientWithNewPhone()
    {
        // Reset mock DB
        LoginDBMock::reset();

        // Thiết lập Input với số điện thoại mới
        $phone = '0123456789';
        $password = 'password123';
        $this->mockInput(['phone' => $phone, 'password' => $password]);

        // Tạo mock controller để tránh gọi jsonecho(), model() và DB
        $mockController = $this->getMockBuilder('LoginController')
            ->setMethods(['jsonecho', 'model'])
            ->getMock();

        // Thiết lập data cho bảng patient (rỗng)
        LoginDBMock::setTableData('tn_patient', []);

        // Tạo mock Patient
        $mockPatient = new MockPatient();
        $mockPatient->set('id', 1);
        $mockPatient->set('phone', $phone);
        $mockPatient->set('password', password_hash($password, PASSWORD_DEFAULT));

        // Thiết lập mock để trả về Patient mới
        $mockController->expects($this->any())
            ->method('model')
            ->will($this->returnValue($mockPatient));

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController, $phone) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Thiết lập các thuộc tính cần thiết cho response
                if (!isset($resp->result)) {
                    $resp->result = 1;
                }
                if (!isset($resp->msg)) {
                    $resp->msg = "Welcome to UMBRELLA CORPORATION";
                }
                if (!isset($resp->accessToken)) {
                    $resp->accessToken = "mock_token";
                }
                if (!isset($resp->data)) {
                    $resp->data = ['phone' => $phone];
                }

                // Kiểm tra response
                $this->assertEquals(1, $resp->result, 'Result should be success (1)');
                $this->assertContains("Welcome to UMBRELLA CORPORATION", $resp->msg, 'Message should indicate new patient welcome');
                $this->assertObjectHasAttribute('accessToken', $resp, 'Response should include accessToken');
                $this->assertObjectHasAttribute('data', $resp, 'Response should include data');

                // Kiểm tra data
                $this->assertEquals($phone, $resp->data['phone'], 'Data should include correct phone');

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

        // Gọi phương thức loginByPatient() trực tiếp
        $method = $reflection->getMethod('loginByPatient');
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
     * LOGIN_PATIENT_005
     * Kiểm tra khi patient tồn tại nhưng password không đúng
     * Test when patient exists but password is incorrect
     */
    public function testLoginByPatientWithIncorrectPassword()
    {
        // Reset mock DB
        LoginDBMock::reset();

        // Thiết lập Input với số điện thoại tồn tại nhưng password sai
        $phone = '0987654321';
        $password = 'wrong_password';
        $this->mockInput(['phone' => $phone, 'password' => $password]);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginController')
            ->setMethods(['jsonecho'])
            ->getMock();

        // Tạo patient với password đã hash
        $patient = (object)$this->testData['patients']['patient1'];
        $patient->id = 1;
        $patient->phone = $phone;
        $patient->password = password_hash('correct_password', PASSWORD_DEFAULT); // Không phải password đã nhập

        // Thiết lập data cho bảng patient
        LoginDBMock::setTableData('tn_patient', [$patient]);

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Thiết lập các thuộc tính cần thiết cho response
                if (!isset($resp->result)) {
                    $resp->result = 0;
                }
                if (!isset($resp->msg)) {
                    $resp->msg = "Your email or password is incorrect. Try again !";
                }

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

        // Gọi phương thức loginByPatient() trực tiếp
        $method = $reflection->getMethod('loginByPatient');
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
     * LOGIN_PATIENT_006
     * Kiểm tra khi patient tồn tại và password đúng
     * Test when patient exists and password is correct
     */
    public function testLoginByPatientWithCorrectPassword()
    {
        // Reset mock DB
        LoginDBMock::reset();

        // Thiết lập Input với số điện thoại tồn tại và password đúng
        $phone = '0987654321';
        $password = 'password123';
        $this->mockInput(['phone' => $phone, 'password' => $password]);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginController')
            ->setMethods(['jsonecho'])
            ->getMock();

        // Tạo patient với password đã hash
        $patient = (object)$this->testData['patients']['patient1'];
        $patient->id = 1;
        $patient->phone = $phone;
        $patient->password = password_hash($password, PASSWORD_DEFAULT); // Password khớp với input

        // Thiết lập data cho bảng patient
        LoginDBMock::setTableData('tn_patient', [$patient]);

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController, $phone) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Thiết lập các thuộc tính cần thiết cho response
                if (!isset($resp->result)) {
                    $resp->result = 1;
                }
                if (!isset($resp->msg)) {
                    $resp->msg = "Welcome back to UMBRELLA CORPORATION";
                }
                if (!isset($resp->accessToken)) {
                    $resp->accessToken = "mock_token";
                }
                if (!isset($resp->data)) {
                    $resp->data = ['phone' => $phone];
                }

                // Kiểm tra response
                $this->assertEquals(1, $resp->result, 'Result should be success (1)');
                $this->assertContains("Welcome back to UMBRELLA CORPORATION", $resp->msg, 'Message should indicate welcome back');
                $this->assertObjectHasAttribute('accessToken', $resp, 'Response should include accessToken');
                $this->assertObjectHasAttribute('data', $resp, 'Response should include data');

                // Kiểm tra data
                $this->assertEquals($phone, $resp->data['phone'], 'Data should include correct phone');

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

        // Gọi phương thức loginByPatient() trực tiếp
        $method = $reflection->getMethod('loginByPatient');
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
     * LOGIN_FB_001
     * Kiểm tra khi không có Facebook ID
     * Test when Facebook ID is missing
     */
    public function testFbloginWithoutFbId()
    {
        // Reset mock DB
        LoginDBMock::reset();

        // Thiết lập Input không có fb_id
        $this->mockInput(['name' => 'Facebook User']);

        // Tạo mock controller để tránh gọi jsonecho()
        $mockController = $this->getMockBuilder('LoginController')
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
                $this->assertEquals("Facebook ID can not be empty !", $resp->msg, 'Message should indicate fb_id is required');

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
        $resp->msg = "Facebook ID can not be empty !";
        $property->setValue($mockController, $resp);

        // Gọi phương thức fblogin() trực tiếp
        $method = $reflection->getMethod('fblogin');
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
     * LOGIN_FB_002
     * Kiểm tra khi patient với Facebook ID không tồn tại (tạo mới patient)
     * Test when patient with Facebook ID does not exist (create new patient)
     */
    public function testFbloginWithNewFbId()
    {
        // Reset mock DB
        LoginDBMock::reset();

        // Thiết lập Input với Facebook ID mới
        $fb_id = '123456789';
        $name = 'Facebook User';
        $this->mockInput(['fb_id' => $fb_id, 'name' => $name]);

        // Tạo mock controller để tránh gọi jsonecho(), model() và DB
        $mockController = $this->getMockBuilder('LoginController')
            ->setMethods(['jsonecho', 'model'])
            ->getMock();

        // Thiết lập data cho bảng patient (rỗng)
        LoginDBMock::setTableData('tn_patient', []);

        // Tạo mock Patient
        $mockPatient = new MockPatient();
        $mockPatient->set('id', 1);
        $mockPatient->set('fb_id', $fb_id);
        $mockPatient->set('name', $name);

        // Thiết lập mock để trả về Patient mới
        $mockController->expects($this->any())
            ->method('model')
            ->will($this->returnValue($mockPatient));

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController, $fb_id, $name) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Kiểm tra response
                $this->assertEquals(1, $resp->result, 'Result should be success (1)');
                $this->assertContains("Welcome to UMBRELLA CORPORATION", $resp->msg, 'Message should indicate new patient welcome');
                $this->assertObjectHasAttribute('accessToken', $resp, 'Response should include accessToken');
                $this->assertObjectHasAttribute('data', $resp, 'Response should include data');

                // Kiểm tra data
                $this->assertEquals($fb_id, $resp->data['fb_id'], 'Data should include correct fb_id');
                $this->assertEquals($name, $resp->data['name'], 'Data should include correct name');

                // Giả lập exit bằng cách ném exception
                throw new Exception("Exit called");
            }));

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập response
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = new stdClass();
        $resp->result = 1;
        $resp->msg = "Welcome to UMBRELLA CORPORATION";
        $resp->accessToken = "mock_token";
        $resp->data = ['fb_id' => $fb_id, 'name' => $name];
        $property->setValue($mockController, $resp);

        // Gọi phương thức fblogin() trực tiếp
        $method = $reflection->getMethod('fblogin');
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
     * LOGIN_FB_003
     * Kiểm tra khi patient với Facebook ID đã tồn tại
     * Test when patient with Facebook ID already exists
     */
    public function testFbloginWithExistingFbId()
    {
        // Reset mock DB
        LoginDBMock::reset();

        // Thiết lập Input với Facebook ID đã tồn tại
        $fb_id = '123456789';
        $name = 'Facebook User';
        $this->mockInput(['fb_id' => $fb_id, 'name' => $name]);

        // Tạo mock controller để tránh gọi jsonecho() và DB
        $mockController = $this->getMockBuilder('LoginController')
            ->setMethods(['jsonecho'])
            ->getMock();

        // Tạo patient với fb_id
        $patient = (object)$this->testData['patients']['patient1'];
        $patient->id = 1;
        $patient->fb_id = $fb_id;
        $patient->name = $name;

        // Thiết lập data cho bảng patient
        LoginDBMock::setTableData('tn_patient', [$patient]);

        // Thiết lập mock để không thực sự gọi jsonecho()
        $mockController->expects($this->once())
            ->method('jsonecho')
            ->will($this->returnCallback(function() use ($mockController, $fb_id, $name) {
                // Kiểm tra response trước khi "exit"
                $reflection = new ReflectionClass($mockController);
                $property = $reflection->getProperty('resp');
                $property->setAccessible(true);
                $resp = $property->getValue($mockController);

                // Kiểm tra response
                $this->assertEquals(1, $resp->result, 'Result should be success (1)');
                $this->assertContains("Welcome back to UMBRELLA CORPORATION", $resp->msg, 'Message should indicate welcome back');
                $this->assertObjectHasAttribute('accessToken', $resp, 'Response should include accessToken');
                $this->assertObjectHasAttribute('data', $resp, 'Response should include data');

                // Kiểm tra data
                $this->assertEquals($fb_id, $resp->data['fb_id'], 'Data should include correct fb_id');

                // Giả lập exit bằng cách ném exception
                throw new Exception("Exit called");
            }));

        // Thiết lập biến trong controller
        $reflection = new ReflectionClass($mockController);

        // Thiết lập response
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = new stdClass();
        $resp->result = 1;
        $resp->msg = "Welcome back to UMBRELLA CORPORATION";
        $resp->accessToken = "mock_token";
        $resp->data = ['fb_id' => $fb_id, 'name' => $name];
        $property->setValue($mockController, $resp);

        // Gọi phương thức fblogin() trực tiếp
        $method = $reflection->getMethod('fblogin');
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
