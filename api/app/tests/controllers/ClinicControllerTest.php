<?php
/**
 * Unit tests cho ClinicController
 * 
 * Class: ClinicControllerTest
 * File: api/app/tests/controllers/ClinicControllerTest.php
 * 
 */
require_once __DIR__ . '/../ControllerTestCase.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

// Định nghĩa các hàm validator nếu chưa có
if (!function_exists('isVietnameseHospital')) {
    function isVietnameseHospital($string) {
        // Giả lập hàm kiểm tra tên bệnh viện
        if (empty($string)) return 0;
        if (preg_match('/[^a-zA-Z0-9\s\pL]/u', $string)) return 0;
        return 1;
    }
}

if (!function_exists('isAddress')) {
    function isAddress($string) {
        // Giả lập hàm kiểm tra địa chỉ
        if (empty($string)) return 0;
        if (preg_match('/[^a-zA-Z0-9\s,\pL]/u', $string)) return 0;
        return 1;
    }
}

// Định nghĩa hằng số PHPUNIT_TESTSUITE để ngăn Controller::jsonecho gọi exit()
if (!defined('PHPUNIT_TESTSUITE')) {
    define('PHPUNIT_TESTSUITE', true);
}

/**
 * Mock model cho AuthUser với phương thức get() có thể kiểm soát
 */
class MockAuthUser
{
    private $data;
    private $role;
    
    public function __construct($data, $role)
    {
        $this->data = $data;
        $this->role = $role;
    }
    
    public function get($key)
    {
        if ($key === 'role') {
            return $this->role;
        }
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}

class ClinicControllerTest extends ControllerTestCase
{
    /**
     * @var ClinicController Controller instance
     */
    protected $controller;
    
    /**
     * @var array Test data for fixtures
     */
    protected $testData;
    
    /**
     * Set up test environment before each test
     */
    protected function setUp()
    {
        parent::setUp();
        
        // Khởi tạo controller
        $this->controller = $this->createController('ClinicController');
        
        // Khởi tạo test data
        $this->testData = [
            'users' => [
                'admin' => [
                    'email' => 'admin@example.com',
                    'phone' => '0123456789',
                    'name' => 'Admin User',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'role' => 'admin',
                    'active' => 1,
                    'speciality_id' => 1,
                    'room_id' => 1,
                    'description' => 'Test admin user',
                    'price' => 150000,
                    'avatar' => '',
                    'create_at' => date('Y-m-d H:i:s'),
                    'update_at' => date('Y-m-d H:i:s'),
                    'recovery_token' => ''
                ],
                'member' => [
                    'email' => 'member@example.com',
                    'phone' => '0123456788',
                    'name' => 'Member User',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'role' => 'member',
                    'active' => 1,
                    'speciality_id' => 1,
                    'room_id' => 1,
                    'description' => 'Test member user',
                    'price' => 150000,
                    'avatar' => '',
                    'create_at' => date('Y-m-d H:i:s'),
                    'update_at' => date('Y-m-d H:i:s'),
                    'recovery_token' => ''
                ]
            ],
            'clinics' => [
                'default' => [
                    'id' => 1,
                    'name' => 'Bệnh viện Đại học Y Dược',
                    'address' => '215 Hồng Bàng, Phường 11, Quận 5, TP Hồ Chí Minh'
                ],
                'clinic2' => [
                    'name' => 'Bệnh viện Chợ Rẫy',
                    'address' => '201B Nguyễn Chí Thanh, Phường 12, Quận 5, TP Hồ Chí Minh'
                ],
                'clinic3' => [
                    'name' => 'Bệnh viện Đa khoa Tâm Anh',
                    'address' => '2B Phổ Quang, Phường 2, Quận Tân Bình, TP Hồ Chí Minh'
                ]
            ]
        ];
        
        // Tạo dữ liệu mẫu
        $this->createFixtures();
    }
    
    /**
     * Tạo dữ liệu mẫu cho các test
     */
    private function createFixtures()
    {
        try {
            // Tạo clinic mặc định (id = 1)
            $this->insertFixture(TABLE_PREFIX.TABLE_CLINICS, [
                'id' => 1,
                'name' => $this->testData['clinics']['default']['name'],
                'address' => $this->testData['clinics']['default']['address']
            ]);
            
            // Tạo các clinic khác
            $clinic2Id = $this->insertFixture(TABLE_PREFIX.TABLE_CLINICS, [
                'name' => $this->testData['clinics']['clinic2']['name'],
                'address' => $this->testData['clinics']['clinic2']['address']
            ]);
            
            $clinic3Id = $this->insertFixture(TABLE_PREFIX.TABLE_CLINICS, [
                'name' => $this->testData['clinics']['clinic3']['name'],
                'address' => $this->testData['clinics']['clinic3']['address']
            ]);
            
            // Cập nhật ID trong test data
            $this->testData['clinics']['clinic2']['id'] = $clinic2Id;
            $this->testData['clinics']['clinic3']['id'] = $clinic3Id;
            
            // Tạo bác sĩ liên kết với clinic
            $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, [
                'email' => 'doctor@example.com',
                'phone' => '0987654321',
                'name' => 'Doctor Test',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'role' => 'member',
                'active' => 1,
                'speciality_id' => 1,
                'room_id' => 1,
                'clinic_id' => $clinic3Id,
                'description' => 'Test doctor',
                'price' => 150000,
                'avatar' => '',
                'create_at' => date('Y-m-d H:i:s'),
                'update_at' => date('Y-m-d H:i:s'),
                'recovery_token' => ''
            ]);
        } catch (Exception $e) {
            $this->fail("Failed to create test fixtures: " . $e->getMessage());
        }
    }
    
    /**
     * Thiết lập mock cho AuthUser
     * 
     * @param string $role Role của người dùng (admin, member, v.v.)
     */
    protected function mockAuthUser($role = 'admin')
    {
        // Tạo auth user
        $userData = $this->testData['users'][$role];
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
     * Thiết lập tham số Route cho controller
     * 
     * @param array $params Các tham số route
     */
    protected function mockRoute($params = [])
    {
        // Tạo đối tượng Route
        $route = new stdClass();
        $route->params = (object)$params;
        
        // Thiết lập biến Route trong controller
        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('variables');
        $property->setAccessible(true);
        $variables = $property->getValue($this->controller);
        $variables['Route'] = $route;
        $property->setValue($this->controller, $variables);
    }
    
    /**
     * Thiết lập Input method và các tham số
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param array $data Dữ liệu đầu vào
     */
    protected function mockInput($method = 'GET', $data = [])
    {
        // Mock Input::method()
        InputMock::$methodMock = function() use ($method) {
            return $method;
        };
        
        // Mock Input::get() và các method khác dựa vào $method
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
            case 'PUT':
                InputMock::$putMock = function($key) use ($data) {
                    return isset($data[$key]) ? $data[$key] : null;
                };
                break;
            case 'DELETE':
                InputMock::$deleteMock = function($key) use ($data) {
                    return isset($data[$key]) ? $data[$key] : null;
                };
                break;
        }
    }
    
    /**
     * Gọi controller và bắt response
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
     * CTRL_CLINIC_AUTH_001
     * Kiểm tra khi người dùng chưa đăng nhập
     */
    public function testNoAuthentication()
    {
        // Đánh dấu test này là incomplete vì không thể test header redirects
        $this->markTestIncomplete(
          'This test cannot verify header redirects in PHPUnit CLI environment'
        );
    }
    
    /**
     * CTRL_CLINIC_PERM_002
     * Kiểm tra quyền - Chỉ admin mới có quyền thao tác với clinic
     */
    public function testNonAdminPermissionGetById()
    {
        // Thiết lập user không phải admin
        $this->mockAuthUser('member');
        
        // Thiết lập route với ID hợp lệ
        $this->mockRoute(['id' => $this->testData['clinics']['default']['id']]);
        
        // Thiết lập HTTP method
        $this->mockInput('GET');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when user is not admin');
        $this->assertContains('admin', strtolower($response['msg']), 'Error message should indicate admin permission required');
    }
    
    /**
     * CTRL_CLINIC_PERM_003
     * Kiểm tra quyền - Chỉ admin mới có quyền cập nhật clinic
     */
    public function testNonAdminPermissionUpdate()
    {
        // Thiết lập user không phải admin
        $this->mockAuthUser('member');
        
        // Thiết lập route với ID hợp lệ
        $this->mockRoute(['id' => $this->testData['clinics']['default']['id']]);
        
        // Thiết lập HTTP method và dữ liệu PUT
        $this->mockInput('PUT', [
            'name' => 'Bệnh viện Test',
            'address' => 'Địa chỉ test'
        ]);
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when user is not admin');
        $this->assertContains('admin', strtolower($response['msg']), 'Error message should indicate admin permission required');
    }
    
    /**
     * CTRL_CLINIC_PERM_004
     * Kiểm tra quyền - Chỉ admin mới có quyền xóa clinic
     */
    public function testNonAdminPermissionDelete()
    {
        // Thiết lập user không phải admin
        $this->mockAuthUser('member');
        
        // Thiết lập route với ID hợp lệ (khác ID mặc định)
        $this->mockRoute(['id' => $this->testData['clinics']['clinic2']['id']]);
        
        // Thiết lập HTTP method
        $this->mockInput('DELETE');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when user is not admin');
        $this->assertContains('admin', strtolower($response['msg']), 'Error message should indicate admin permission required');
    }
    
    /**
     * CTRL_CLINIC_GET_005
     * Kiểm tra getById - Trường hợp thiếu ID
     */
    public function testGetByIdMissingId()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Không thiết lập ID trong route
        $this->mockRoute();
        
        // Thiết lập HTTP method
        $this->mockInput('GET');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when ID is missing');
        $this->assertContains('id is required', strtolower($response['msg']), 'Error message should indicate ID is required');
    }
    
    /**
     * CTRL_CLINIC_GET_006
     * Kiểm tra getById - Trường hợp ID không tồn tại
     */
    public function testGetByIdClinicNotExist()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID không tồn tại
        $this->mockRoute(['id' => 9999]);
        
        // Thiết lập HTTP method
        $this->mockInput('GET');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when clinic does not exist');
        $this->assertContains('not available', strtolower($response['msg']), 'Error message should indicate clinic is not available');
    }
    
    /**
     * CTRL_CLINIC_GET_007
     * Kiểm tra getById - Trường hợp thành công
     */
    public function testGetByIdSuccess()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID hợp lệ
        $this->mockRoute(['id' => $this->testData['clinics']['default']['id']]);
        
        // Thiết lập HTTP method
        $this->mockInput('GET');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(1, $response['result'], 'Result should be success when getting clinic by ID');
        $this->assertContains('successfully', strtolower($response['msg']), 'Success message should be returned');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');
        $this->assertEquals($this->testData['clinics']['default']['id'], $response['data']['id'], 'Returned ID should match');
        $this->assertEquals($this->testData['clinics']['default']['name'], $response['data']['name'], 'Returned name should match');
        $this->assertEquals($this->testData['clinics']['default']['address'], $response['data']['address'], 'Returned address should match');
    }
    
    /**
     * CTRL_CLINIC_UPD_008
     * Kiểm tra update - Trường hợp thiếu ID
     */
    public function testUpdateMissingId()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Không thiết lập ID trong route
        $this->mockRoute();
        
        // Thiết lập HTTP method và dữ liệu PUT
        $this->mockInput('PUT', [
            'name' => 'Bệnh viện Test',
            'address' => 'Địa chỉ test'
        ]);
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when ID is missing');
        $this->assertContains('id is required', strtolower($response['msg']), 'Error message should indicate ID is required');
    }
    
    /**
     * CTRL_CLINIC_UPD_009
     * Kiểm tra update - Trường hợp thiếu dữ liệu bắt buộc (name)
     */
    public function testUpdateMissingName()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID hợp lệ
        $this->mockRoute(['id' => $this->testData['clinics']['default']['id']]);
        
        // Thiết lập HTTP method và dữ liệu PUT thiếu name
        $this->mockInput('PUT', [
            'address' => 'Địa chỉ test'
        ]);
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when name is missing');
        $this->assertContains('missing field: name', strtolower($response['msg']), 'Error message should indicate name is missing');
    }
    
    /**
     * CTRL_CLINIC_UPD_010
     * Kiểm tra update - Trường hợp thiếu dữ liệu bắt buộc (address)
     */
    public function testUpdateMissingAddress()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID hợp lệ
        $this->mockRoute(['id' => $this->testData['clinics']['default']['id']]);
        
        // Thiết lập HTTP method và dữ liệu PUT thiếu address
        $this->mockInput('PUT', [
            'name' => 'Bệnh viện Test'
        ]);
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when address is missing');
        $this->assertContains('missing field: address', strtolower($response['msg']), 'Error message should indicate address is missing');
    }
    
    /**
     * CTRL_CLINIC_UPD_011
     * Kiểm tra update - Trường hợp tên không hợp lệ
     */
    public function testUpdateInvalidName()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID hợp lệ
        $this->mockRoute(['id' => $this->testData['clinics']['default']['id']]);
        
        // Thiết lập HTTP method và dữ liệu PUT với tên không hợp lệ
        $this->mockInput('PUT', [
            'name' => 'Bệnh viện @#$%',
            'address' => 'Địa chỉ test'
        ]);
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when name is invalid');
        $this->assertContains('name only has letters', strtolower($response['msg']), 'Error message should indicate name format is incorrect');
    }
    
    /**
     * CTRL_CLINIC_UPD_012
     * Kiểm tra update - Trường hợp địa chỉ không hợp lệ
     */
    public function testUpdateInvalidAddress()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID hợp lệ
        $this->mockRoute(['id' => $this->testData['clinics']['default']['id']]);
        
        // Thiết lập HTTP method và dữ liệu PUT với địa chỉ không hợp lệ
        $this->mockInput('PUT', [
            'name' => 'Bệnh viện Test',
            'address' => 'Địa chỉ @#$%'
        ]);
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when address is invalid');
        $this->assertContains('address only accepts', strtolower($response['msg']), 'Error message should indicate address format is incorrect');
    }
    
    /**
     * CTRL_CLINIC_UPD_013
     * Kiểm tra update - Trường hợp clinic không tồn tại
     */
    public function testUpdateClinicNotExist()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID không tồn tại
        $this->mockRoute(['id' => 9999]);
        
        // Thiết lập HTTP method và dữ liệu PUT hợp lệ
        $this->mockInput('PUT', [
            'name' => 'Bệnh viện Test',
            'address' => 'Địa chỉ test'
        ]);
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when clinic does not exist');
        $this->assertContains('not available', strtolower($response['msg']), 'Error message should indicate clinic is not available');
    }
    
    /**
     * CTRL_CLINIC_UPD_014
     * Kiểm tra update - Trường hợp thành công
     */
    public function testUpdateSuccess()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID hợp lệ
        $this->mockRoute(['id' => $this->testData['clinics']['clinic2']['id']]);
        
        // Thiết lập HTTP method và dữ liệu PUT hợp lệ
        $newName = 'Bệnh viện Cập nhật';
        $newAddress = 'Địa chỉ đã cập nhật';
        $this->mockInput('PUT', [
            'name' => $newName,
            'address' => $newAddress
        ]);
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(1, $response['result'], 'Result should be success when updating clinic');
        $this->assertContains('updated successfully', strtolower($response['msg']), 'Success message should be returned');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');
        $this->assertEquals($this->testData['clinics']['clinic2']['id'], $response['data']['id'], 'Returned ID should match');
        $this->assertEquals($newName, $response['data']['name'], 'Returned name should be updated');
        $this->assertEquals($newAddress, $response['data']['address'], 'Returned address should be updated');
        
        // Kiểm tra dữ liệu đã cập nhật trong database
        $query = DB::table(TABLE_PREFIX.TABLE_CLINICS)
                    ->where('id', '=', $this->testData['clinics']['clinic2']['id'])
                    ->first();
        $this->assertEquals($newName, $query->name, 'Name should be updated in database');
        $this->assertEquals($newAddress, $query->address, 'Address should be updated in database');
    }
    
    /**
     * CTRL_CLINIC_DEL_015
     * Kiểm tra delete - Trường hợp thiếu ID
     */
    public function testDeleteMissingId()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Không thiết lập ID trong route
        $this->mockRoute();
        
        // Thiết lập HTTP method
        $this->mockInput('DELETE');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when ID is missing');
        $this->assertContains('id is required', strtolower($response['msg']), 'Error message should indicate ID is required');
    }
    
    /**
     * CTRL_CLINIC_DEL_016
     * Kiểm tra delete - Trường hợp xóa clinic mặc định (ID = 1)
     */
    public function testDeleteDefaultClinic()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID mặc định (1)
        $this->mockRoute(['id' => 1]);
        
        // Thiết lập HTTP method
        $this->mockInput('DELETE');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when trying to delete default clinic');
        $this->assertContains('default clinic', strtolower($response['msg']), 'Error message should indicate default clinic cannot be deleted');
    }
    
    /**
     * CTRL_CLINIC_DEL_017
     * Kiểm tra delete - Trường hợp clinic không tồn tại
     */
    public function testDeleteClinicNotExist()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID không tồn tại
        $this->mockRoute(['id' => 9999]);
        
        // Thiết lập HTTP method
        $this->mockInput('DELETE');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when clinic does not exist');
        $this->assertContains('not available', strtolower($response['msg']), 'Error message should indicate clinic is not available');
    }
    
    /**
     * CTRL_CLINIC_DEL_018
     * Kiểm tra delete - Trường hợp clinic đang có bác sĩ
     */
    public function testDeleteClinicWithDoctors()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID của clinic có bác sĩ
        $this->mockRoute(['id' => $this->testData['clinics']['clinic3']['id']]);
        
        // Thiết lập HTTP method
        $this->mockInput('DELETE');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(0, $response['result'], 'Result should be error when clinic has doctors');
        $this->assertContains('doctors working', strtolower($response['msg']), 'Error message should indicate clinic has doctors');
    }
    
    /**
     * CTRL_CLINIC_DEL_019
     * Kiểm tra delete - Trường hợp thành công
     */
    public function testDeleteSuccess()
    {
        // Thiết lập user admin
        $this->mockAuthUser('admin');
        
        // Thiết lập route với ID hợp lệ (khác ID mặc định và không có bác sĩ)
        $this->mockRoute(['id' => $this->testData['clinics']['clinic2']['id']]);
        
        // Thiết lập HTTP method
        $this->mockInput('DELETE');
        
        // Gọi controller và lấy response
        $response = $this->callControllerWithCapture();
        
        // Kiểm tra response
        $this->assertEquals(1, $response['result'], 'Result should be success when deleting clinic');
        $this->assertContains('deleted successfully', strtolower($response['msg']), 'Success message should be returned');
        
        // Kiểm tra dữ liệu đã bị xóa trong database
        $query = DB::table(TABLE_PREFIX.TABLE_CLINICS)
                    ->where('id', '=', $this->testData['clinics']['clinic2']['id'])
                    ->first();
        $this->assertNull($query, 'Clinic should be deleted from database');
    }
} 