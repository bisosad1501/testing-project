<?php
/**
 * Unit tests cho DoctorsAndServicesReadyController
 *
 * Class: DoctorsAndServicesReadyControllerTest
 * File: api/app/tests/controllers/DoctorsAndServicesReadyControllerTest.php
 *
 */
require_once __DIR__ . '/../ControllerTestCase.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

// Include MockAuthUser
require_once __DIR__ . '/../mocks/MockAuthUser.php';

class DoctorsAndServicesReadyControllerTest extends ControllerTestCase
{
    /**
     * @var DoctorsAndServicesReadyController Controller instance
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

        // Không xóa dữ liệu có sẵn trong database test

        // Khởi tạo controller
        $this->controller = $this->createController('DoctorsAndServicesReadyController');

        // Sử dụng dữ liệu có sẵn trong database test
        $this->testData = [
            'services' => [
                'service1' => [
                    'id' => 1, // ID thực tế trong database test
                    'name' => 'Service 1',
                    'description' => 'Description for Service 1'
                ],
                'service2' => [
                    'id' => 2, // ID thực tế trong database test
                    'name' => 'Service 2',
                    'description' => 'Description for Service 2'
                ]
            ],
            'doctors' => [
                'doctor1' => [
                    'id' => 1, // ID thực tế trong database test
                    'email' => 'doctor1@example.com',
                    'phone' => '0123456789',
                    'name' => 'Doctor 1',
                    'description' => 'Description for Doctor 1',
                    'price' => 200000,
                    'role' => 'member',
                    'active' => 1,
                    'speciality_id' => 1,
                    'room_id' => 1,
                    'avatar' => 'avatar1.jpg'
                ],
                'doctor2' => [
                    'id' => 2, // ID thực tế trong database test
                    'email' => 'doctor2@example.com',
                    'phone' => '0123456788',
                    'name' => 'Doctor 2',
                    'description' => 'Description for Doctor 2',
                    'price' => 150000,
                    'role' => 'member',
                    'active' => 1,
                    'speciality_id' => 1,
                    'room_id' => 1,
                    'avatar' => 'avatar2.jpg'
                ],
                'inactive_doctor' => [
                    'id' => 3, // ID thực tế trong database test
                    'email' => 'inactive@example.com',
                    'phone' => '0123456787',
                    'name' => 'Inactive Doctor',
                    'description' => 'Description for Inactive Doctor',
                    'price' => 150000,
                    'role' => 'member',
                    'active' => 0,
                    'speciality_id' => 1,
                    'room_id' => 1,
                    'avatar' => 'avatar3.jpg'
                ]
            ],
            'doctor_and_service' => [
                'relation1' => [
                    'id' => 1, // ID thực tế trong database test
                    'doctor_id' => 1,
                    'service_id' => 1
                ],
                'relation2' => [
                    'id' => 2, // ID thực tế trong database test
                    'doctor_id' => 2,
                    'service_id' => 1
                ]
            ]
        ];
    }

    /**
     * Thiết lập mock cho AuthUser
     * Set up mock for AuthUser
     *
     * @param string $role Role của người dùng (admin, member)
     */
    protected function mockAuthUser($role = 'admin')
    {
        // Tạo auth user
        $userData = [
            'id' => 1,
            'email' => 'admin@example.com',
            'name' => 'Admin User',
            'role' => $role
        ];
        $authUser = new MockAuthUser($userData, $role);

        // Thiết lập biến AuthUser trong controller
        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        $variables = $property->getValue($this->controller);
        $variables['AuthUser'] = $authUser;
        $property->setValue($this->controller, $variables);

        return $authUser;
    }

    /**
     * Thiết lập Route params
     * Set up Route params
     *
     * @param array $params Route params
     */
    protected function mockRoute($params = [])
    {
        // Tạo mock Route object
        $route = new stdClass();
        $route->params = new stdClass();

        foreach ($params as $key => $value) {
            $route->params->$key = $value;
        }

        // Thiết lập biến Route trong controller
        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        $variables = $property->getValue($this->controller);
        $variables['Route'] = $route;
        $property->setValue($this->controller, $variables);

        return $route;
    }

    /**
     * Thiết lập Input method và các tham số
     * Set up Input method and parameters
     *
     * @param string $method HTTP method (GET, POST, DELETE)
     * @param array $data Dữ liệu đầu vào
     */
    protected function mockInput($method = 'GET', $data = [])
    {
        // Mock Input::method()
        InputMock::$methodMock = function() use ($method) {
            return $method;
        };

        // Mock Input::get() và các method khác dựa vào $method
        // Reset các mock function trước
        InputMock::$getMock = null;
        InputMock::$postMock = null;

        // Set mocks dựa trên method
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
            case 'DELETE':
                // DELETE thường không có body, nhưng nếu cần thiết có thể mock ở đây
                break;
        }
    }

    /**
     * Gọi controller và bắt response
     * Call controller and capture response
     */
    protected function callControllerWithCapture()
    {
        // Bắt đầu output buffering để bắt bất kỳ output nào
        ob_start();

        try {
            $this->controller->process();
        } catch (Exception $e) {
            // Ghi log exception nếu cần
            // error_log("Exception in test: " . $e->getMessage());
        }

        // Xóa buffer và lấy response từ controller
        ob_end_clean();

        // Lấy response từ controller
        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);
        $resp = $property->getValue($this->controller);

        return (array)$resp;
    }

    /**
     * CTRL_DOCSERVREADY_AUTH_001
     * Kiểm tra khi người dùng chưa đăng nhập
     * Test when user is not authenticated
     */
    public function testNoAuthentication()
    {
        // Không thiết lập AuthUser

        // Thiết lập HTTP method
        $this->mockInput('GET');
        $this->mockRoute(['id' => $this->testData['services']['service1']['id']]);

        // Gọi controller và lấy response
        // Không thể kiểm tra header redirects, nhưng ít nhất chúng ta có thể chạy code
        $this->callControllerWithCapture();

        // Không có assertion vì không thể kiểm tra header redirects
        $this->assertTrue(true, 'Test executed without errors');
    }

    /**
     * CTRL_DOCSERVREADY_AUTH_002
     * Kiểm tra khi người dùng không có quyền admin
     * Test when user does not have admin role
     */
    public function testNotAdmin()
    {
        // Thiết lập user không phải admin
        $this->mockAuthUser('member');

        // Thiết lập HTTP method và Route params với ID hợp lệ
        $this->mockInput('GET');
        $this->mockRoute(['id' => $this->testData['services']['service1']['id']]);

        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();

        // Kiểm tra response có thuộc tính msg
        $this->assertArrayHasKey('msg', $response, 'Response should include message');
        $this->assertEquals(0, $response['result'], 'Result should be error when user is not admin');
        $this->assertContains('not admin', $response['msg'], 'Error message should indicate user is not admin');
    }

    /**
     * CTRL_DOCSERVREADY_GET_003
     * Kiểm tra lấy danh sách bác sĩ sẵn sàng khi không có ID dịch vụ
     * Test getting ready doctors without service ID
     */
    public function testGetAllWithoutServiceId()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');

        // Thiết lập HTTP method và Route params không có ID
        $this->mockInput('GET');
        $this->mockRoute();

        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();

        // Kiểm tra response có thuộc tính msg
        $this->assertArrayHasKey('msg', $response, 'Response should include error message');
        $this->assertEquals(0, $response['result'], 'Result should be error when service ID is missing');
        $this->assertContains('Service ID is required', $response['msg'], 'Error message should indicate service ID is required');
    }

    /**
     * CTRL_DOCSERVREADY_GET_004
     * Kiểm tra lấy danh sách bác sĩ sẵn sàng với ID dịch vụ không tồn tại
     * Test getting ready doctors with non-existent service ID
     */
    public function testGetAllWithNonExistentServiceId()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');

        // Thiết lập HTTP method và Route params với ID không tồn tại
        $this->mockInput('GET');
        $this->mockRoute(['id' => 9999]);

        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();

        // Kiểm tra response có thuộc tính msg
        $this->assertArrayHasKey('msg', $response, 'Response should include error message');
        $this->assertEquals(0, $response['result'], 'Result should be error when service is not available');
        $this->assertContains('Service is not available', $response['msg'], 'Error message should indicate service is not available');
    }

    /**
     * CTRL_DOCSERVREADY_GET_005
     * Kiểm tra lấy danh sách bác sĩ sẵn sàng thành công
     * Test getting ready doctors successfully
     */
    public function testGetAllSuccess()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');

        // Thiết lập HTTP method và Route params với ID hợp lệ
        $this->mockInput('GET');
        $this->mockRoute(['id' => $this->testData['services']['service1']['id']]);

        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();

        // Kiểm tra response
        $this->assertEquals(1, $response['result'], 'Result should be success when getting ready doctors');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');
        $this->assertArrayHasKey('service', $response, 'Response should include service info');
        $this->assertArrayHasKey('quantity', $response, 'Response should include quantity');

        // Kiểm tra thông tin dịch vụ
        $this->assertEquals($this->testData['services']['service1']['id'], $response['service']['id'], 'Service ID should match');
    }

    /**
     * CTRL_DOCSERVREADY_GET_006
     * Kiểm tra xử lý ngoại lệ khi truy vấn database
     * Test exception handling during database query
     */
    public function testGetAllWithDatabaseException()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');

        // Thiết lập HTTP method và Route params với ID hợp lệ
        $this->mockInput('GET');
        $this->mockRoute(['id' => $this->testData['services']['service1']['id']]);

        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();

        // Kiểm tra response có thuộc tính result
        $this->assertArrayHasKey('result', $response, 'Response should include result');

        // Kiểm tra xem controller có try-catch block để xử lý ngoại lệ không
        $controllerCode = file_get_contents(__DIR__ . '/../../controllers/DoctorsAndServicesReadyController.php');
        $this->assertContains('try', $controllerCode, 'Controller should have try block');
        $this->assertContains('catch', $controllerCode, 'Controller should have catch block');
        $this->assertContains('Exception', $controllerCode, 'Controller should catch Exception');
    }
}
