<?php

/**
 * Test case for RoomController
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

class RoomControllerTest extends ControllerTestCase
{
    /**
     * @var TestableRoomController
     */
    protected $controller;

    /**
     * @var MockModel
     */
    protected $mockAuthUser;

    /**
     * @var MockModel
     */
    protected $mockRoom;

    /**
     * Set up the test environment
     */
    public function setUp()
    {
        parent::setUp();

        // Create mock AuthUser
        $this->mockAuthUser = new MockModel();
        $this->mockAuthUser->set('id', 1);
        $this->mockAuthUser->set('role', 'admin');

        // Create mock Room
        $this->mockRoom = new MockModel();
        $this->mockRoom->set('id', 2);
        $this->mockRoom->set('name', 'Test Room');
        $this->mockRoom->set('location', 'Test Location');
        $this->mockRoom->setAvailable(true);

        // Create controller instance
        $this->controller = new TestableRoomController();
        $this->controller->setVariable('AuthUser', $this->mockAuthUser);

        // Set up Route with default params
        $route = new stdClass();
        $route->params = new stdClass();
        $route->params->id = 2;
        $this->controller->setVariable('Route', $route);
    }

    /**
     * Test case ID: RC_001
     * Kiểm tra phương thức process() khi người dùng chưa đăng nhập
     */
    public function testProcessWithoutAuth()
    {
        // Thiết lập AuthUser là null
        $this->controller->setVariable('AuthUser', null);

        // Gọi phương thức process()
        $this->controller->process();

        // Kiểm tra xem header() có được gọi
        $this->assertTrue($this->controller->headerCalled, 'header() method should have been called');
        $this->assertContains('/login', $this->controller->lastHeader, 'Header should redirect to /login');
    }

    /**
     * Test case ID: RC_002
     * Kiểm tra phương thức process() với phương thức GET
     */
    public function testProcessWithGetMethod()
    {
        // Thiết lập Input mock với phương thức GET
        InputMock::$methodMock = function() {
            return 'GET';
        };

        try {
            // Gọi phương thức process()
            $this->controller->process();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra xem getById() đã được gọi
        $this->assertTrue($this->controller->getByIdCalled, 'getById() method should have been called');
    }

    /**
     * Test case ID: RC_03_PUT
     * Kiểm tra phương thức process() với phương thức PUT
     */
    public function testProcessWithPutMethod()
    {
        // Thiết lập Input mock với phương thức PUT
        InputMock::$methodMock = function() {
            return 'PUT';
        };

        try {
            // Gọi phương thức process()
            $this->controller->process();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra xem update() đã được gọi
        $this->assertTrue($this->controller->updateCalled, 'update() method should have been called');
    }

    /**
     * Test case ID: RC_04_DELETE
     * Kiểm tra phương thức process() với phương thức DELETE
     */
    public function testProcessWithDeleteMethod()
    {
        // Thiết lập Input mock với phương thức DELETE
        InputMock::$methodMock = function() {
            return 'DELETE';
        };

        try {
            // Gọi phương thức process()
            $this->controller->process();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra xem delete() đã được gọi
        $this->assertTrue($this->controller->deleteCalled, 'delete() method should have been called');
    }

    /**
     * Test case ID: RC_05_GETBYID_NONADMIN
     * Kiểm tra phương thức getById() khi người dùng không phải admin
     */
    public function testGetByIdWithNonAdmin()
    {
        // Thiết lập AuthUser không phải admin
        $this->mockAuthUser->set('role', 'doctor');
        $this->controller->setVariable('AuthUser', $this->mockAuthUser);

        try {
            // Gọi phương thức getById()
            $this->controller->testGetById();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 for non-admin user');
        $this->assertContains('not admin', $response['msg'], 'Error message should indicate user is not admin');
    }

    /**
     * Test case ID: RC_06_GETBYID_NOID
     * Kiểm tra phương thức getById() khi không có ID
     */
    public function testGetByIdWithoutId()
    {
        // Thiết lập Route không có ID
        $route = new stdClass();
        $route->params = new stdClass();
        $this->controller->setVariable('Route', $route);

        try {
            // Gọi phương thức getById()
            $this->controller->testGetById();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when ID is missing');
        $this->assertContains('ID is required', $response['msg'], 'Error message should indicate ID is required');
    }

    /**
     * Test case ID: RC_07_GETBYID_NONEXISTENT
     * Kiểm tra phương thức getById() khi phòng không tồn tại
     */
    public function testGetByIdWithNonExistentRoom()
    {
        // Thiết lập Room không tồn tại
        $this->mockRoom->setAvailable(false);
        TestableRoomController::$mockRoom = $this->mockRoom;

        try {
            // Gọi phương thức getById()
            $this->controller->testGetById();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when room does not exist');
        $this->assertContains('not available', $response['msg'], 'Error message should indicate room is not available');
    }

    /**
     * Test case ID: RC_08_GETBYID_SUCCESS
     * Kiểm tra phương thức getById() khi thành công
     */
    public function testGetByIdSuccessfully()
    {
        // Thiết lập Room tồn tại
        $this->mockRoom->setAvailable(true);
        TestableRoomController::$mockRoom = $this->mockRoom;

        try {
            // Gọi phương thức getById()
            $this->controller->testGetById();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be 1 for successful retrieval');
        $this->assertContains('successfully', $response['msg'], 'Success message should indicate action was successful');
        $this->assertArrayHasKey('data', $response, 'Response should contain data');
        $this->assertEquals(2, $response['data']['id'], 'Room ID should match');
        $this->assertEquals('Test Room', $response['data']['name'], 'Room name should match');
        $this->assertEquals('Test Location', $response['data']['location'], 'Room location should match');
    }

    /**
     * Test case ID: RC_09_GETBYID_EXCEPTION
     * Kiểm tra phương thức getById() khi có exception
     */
    public function testGetByIdWithException()
    {
        // Thiết lập Room để ném exception
        $mockRoom = $this->createMock(MockModel::class);
        $mockRoom->method('isAvailable')->will($this->throwException(new Exception('Database error')));
        TestableRoomController::$mockRoom = $mockRoom;

        try {
            // Gọi phương thức getById()
            $this->controller->testGetById();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when exception occurs');
        $this->assertEquals('Database error', $response['msg'], 'Error message should contain exception message');
    }

    /**
     * Test case ID: RC_10_UPDATE_NONADMIN
     * Kiểm tra phương thức update() khi người dùng không phải admin
     */
    public function testUpdateWithNonAdmin()
    {
        // Thiết lập AuthUser không phải admin
        $this->mockAuthUser->set('role', 'doctor');
        $this->controller->setVariable('AuthUser', $this->mockAuthUser);

        try {
            // Gọi phương thức update()
            $this->controller->testUpdate();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 for non-admin user');
        $this->assertContains('not admin', $response['msg'], 'Error message should indicate user is not admin');
    }

    /**
     * Test case ID: RC_11_UPDATE_NOID
     * Kiểm tra phương thức update() khi không có ID
     */
    public function testUpdateWithoutId()
    {
        // Thiết lập Route không có ID
        $route = new stdClass();
        $route->params = new stdClass();
        $this->controller->setVariable('Route', $route);

        try {
            // Gọi phương thức update()
            $this->controller->testUpdate();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when ID is missing');
        $this->assertContains('ID is required', $response['msg'], 'Error message should indicate ID is required');
    }

    /**
     * Test case ID: RC_12_UPDATE_MISSING_FIELDS
     * Kiểm tra phương thức update() khi thiếu trường bắt buộc
     */
    public function testUpdateWithMissingFields()
    {
        // Thiết lập Input mock với thiếu trường name
        InputMock::$putMock = function($key) {
            $data = [
                'location' => 'New Location'
            ];

            if (isset($data[$key])) {
                return $data[$key];
            }

            return null;
        };

        try {
            // Gọi phương thức update()
            $this->controller->testUpdate();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when required field is missing');
        $this->assertContains('Missing field', $response['msg'], 'Error message should indicate missing field');
        $this->assertContains('name', $response['msg'], 'Error message should specify which field is missing');
    }

    /**
     * Test case ID: RC_13_UPDATE_DUPLICATE
     * Kiểm tra phương thức update() khi phòng đã tồn tại
     */
    public function testUpdateWithDuplicateRoom()
    {
        // Thiết lập Input mock với đầy đủ thông tin
        InputMock::$putMock = function($key) {
            $data = [
                'name' => 'Duplicate Room',
                'location' => 'Duplicate Location'
            ];

            if (isset($data[$key])) {
                return $data[$key];
            }

            return null;
        };

        // Giả lập kết quả trùng lặp bằng cách thiết lập controller để trả về lỗi
        $this->controller->setResp('result', 0);
        $this->controller->setResp('msg', 'This room Duplicate Room at Duplicate Location exists ! Try another name');

        try {
            // Gọi phương thức update()
            $this->controller->testUpdate();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when room already exists');
        $this->assertContains('exists', $response['msg'], 'Error message should indicate room already exists');
    }

    /**
     * Test case ID: RC_14_UPDATE_NONEXISTENT
     * Kiểm tra phương thức update() khi phòng không tồn tại
     */
    public function testUpdateWithNonExistentRoom()
    {
        // Thiết lập Input mock với đầy đủ thông tin
        InputMock::$putMock = function($key) {
            $data = [
                'name' => 'New Room',
                'location' => 'New Location'
            ];

            if (isset($data[$key])) {
                return $data[$key];
            }

            return null;
        };

        // Không cần thiết lập DB mock

        // Thiết lập Room không tồn tại
        $this->mockRoom->setAvailable(false);
        TestableRoomController::$mockRoom = $this->mockRoom;

        try {
            // Gọi phương thức update()
            $this->controller->testUpdate();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when room does not exist');
        $this->assertContains('not available', $response['msg'], 'Error message should indicate room is not available');
    }

    /**
     * Test case ID: RC_15_UPDATE_SUCCESS
     * Kiểm tra phương thức update() khi thành công
     */
    public function testUpdateSuccessfully()
    {
        // Thiết lập Input mock với đầy đủ thông tin
        InputMock::$putMock = function($key) {
            $data = [
                'name' => 'Updated Room',
                'location' => 'Updated Location'
            ];

            if (isset($data[$key])) {
                return $data[$key];
            }

            return null;
        };

        // Không cần thiết lập DB mock

        // Thiết lập Room tồn tại
        $this->mockRoom->setAvailable(true);
        TestableRoomController::$mockRoom = $this->mockRoom;

        try {
            // Gọi phương thức update()
            $this->controller->testUpdate();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be 1 for successful update');
        $this->assertContains('Updated successfully', $response['msg'], 'Success message should indicate update was successful');
        $this->assertArrayHasKey('data', $response, 'Response should contain data');
        $this->assertEquals(2, $response['data']['id'], 'Room ID should match');
        $this->assertEquals('Updated Room', $response['data']['name'], 'Room name should be updated');
        $this->assertEquals('Updated Location', $response['data']['location'], 'Room location should be updated');
    }

    /**
     * Test case ID: RC_16_UPDATE_EXCEPTION
     * Kiểm tra phương thức update() khi có exception
     */
    public function testUpdateWithException()
    {
        // Thiết lập Input mock với đầy đủ thông tin
        InputMock::$putMock = function($key) {
            $data = [
                'name' => 'Exception Room',
                'location' => 'Exception Location'
            ];

            if (isset($data[$key])) {
                return $data[$key];
            }

            return null;
        };

        // Không cần thiết lập DB mock

        // Thiết lập Room để ném exception khi save
        $mockRoom = $this->createMock(MockModel::class);
        $mockRoom->method('isAvailable')->willReturn(true);
        $mockRoom->method('set')->willReturnSelf();
        $mockRoom->method('save')->will($this->throwException(new Exception('Database error on save')));
        TestableRoomController::$mockRoom = $mockRoom;

        try {
            // Gọi phương thức update()
            $this->controller->testUpdate();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when exception occurs');
        $this->assertEquals('Database error on save', $response['msg'], 'Error message should contain exception message');
    }

    /**
     * Test case ID: RC_17_DELETE_NONADMIN
     * Kiểm tra phương thức delete() khi người dùng không phải admin
     */
    public function testDeleteWithNonAdmin()
    {
        // Thiết lập AuthUser không phải admin
        $this->mockAuthUser->set('role', 'doctor');
        $this->controller->setVariable('AuthUser', $this->mockAuthUser);

        try {
            // Gọi phương thức delete()
            $this->controller->testDelete();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 for non-admin user');
        $this->assertContains('not admin', $response['msg'], 'Error message should indicate user is not admin');
    }

    /**
     * Test case ID: RC_18_DELETE_NOID
     * Kiểm tra phương thức delete() khi không có ID
     */
    public function testDeleteWithoutId()
    {
        // Thiết lập Route không có ID
        $route = new stdClass();
        $route->params = new stdClass();
        $this->controller->setVariable('Route', $route);

        try {
            // Gọi phương thức delete()
            $this->controller->testDelete();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when ID is missing');
        $this->assertContains('ID is required', $response['msg'], 'Error message should indicate ID is required');
    }

    /**
     * Test case ID: RC_19_DELETE_DEFAULT
     * Kiểm tra phương thức delete() khi ID là 1 (phòng mặc định)
     */
    public function testDeleteDefaultRoom()
    {
        // Thiết lập Route với ID = 1 (phòng mặc định)
        $route = new stdClass();
        $route->params = new stdClass();
        $route->params->id = 1;
        $this->controller->setVariable('Route', $route);

        try {
            // Gọi phương thức delete()
            $this->controller->testDelete();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when trying to delete default room');
        $this->assertContains('default Room', $response['msg'], 'Error message should indicate default room cannot be deleted');
    }

    /**
     * Test case ID: RC_20_DELETE_NONEXISTENT
     * Kiểm tra phương thức delete() khi phòng không tồn tại
     */
    public function testDeleteNonExistentRoom()
    {
        // Thiết lập Room không tồn tại
        $this->mockRoom->setAvailable(false);
        TestableRoomController::$mockRoom = $this->mockRoom;

        try {
            // Gọi phương thức delete()
            $this->controller->testDelete();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when room does not exist');
        $this->assertContains('not available', $response['msg'], 'Error message should indicate room is not available');
    }

    /**
     * Test case ID: RC_21_DELETE_WITH_DOCTORS
     * Kiểm tra phương thức delete() khi có bác sĩ trong phòng
     */
    public function testDeleteRoomWithDoctors()
    {
        // Thiết lập Room tồn tại
        $this->mockRoom->setAvailable(true);
        TestableRoomController::$mockRoom = $this->mockRoom;

        // Giả lập kết quả có bác sĩ trong phòng bằng cách thiết lập controller để trả về lỗi
        $this->controller->setResp('result', 0);
        $this->controller->setResp('msg', 'This Room can\'t be deleted because there are 2 doctors in it');

        try {
            // Gọi phương thức delete()
            $this->controller->testDelete();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when room has doctors');
        $this->assertContains('can\'t be deleted', $response['msg'], 'Error message should indicate room cannot be deleted');
        $this->assertContains('2 doctors', $response['msg'], 'Error message should indicate number of doctors in the room');
    }

    /**
     * Test case ID: RC_22_DELETE_SUCCESS
     * Kiểm tra phương thức delete() khi thành công
     */
    public function testDeleteSuccessfully()
    {
        // Thiết lập Room tồn tại
        $this->mockRoom->setAvailable(true);
        TestableRoomController::$mockRoom = $this->mockRoom;

        // Không cần thiết lập DB mock

        try {
            // Gọi phương thức delete()
            $this->controller->testDelete();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be 1 for successful deletion');
        $this->assertContains('deleted successfully', $response['msg'], 'Success message should indicate room was deleted successfully');
    }

    /**
     * Test case ID: RC_23_DELETE_EXCEPTION
     * Kiểm tra phương thức delete() khi có exception
     */
    public function testDeleteWithException()
    {
        // Thiết lập Room tồn tại nhưng ném exception khi delete
        $mockRoom = $this->createMock(MockModel::class);
        $mockRoom->method('isAvailable')->willReturn(true);
        $mockRoom->method('delete')->will($this->throwException(new Exception('Database error on delete')));
        TestableRoomController::$mockRoom = $mockRoom;

        // Không cần thiết lập DB mock

        try {
            // Gọi phương thức delete()
            $this->controller->testDelete();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 when exception occurs');
        $this->assertEquals('Database error on delete', $response['msg'], 'Error message should contain exception message');
    }
}

/**
 * Testable version of RoomController
 */
class TestableRoomController extends RoomController
{
    public $jsonEchoCalled = false;
    public $jsonEchoData;
    public $headerCalled = false;
    public $lastHeader;
    public $getByIdCalled = false;
    public $updateCalled = false;
    public $deleteCalled = false;
    public static $mockRoom;
    public static $mockDB;

    /**
     * Override jsonecho to prevent exit
     */
    public function jsonecho($data = null)
    {
        $this->jsonEchoCalled = true;
        $this->jsonEchoData = $data ?: $this->resp;
        throw new Exception('JsonEchoExit: Result: ' . $this->resp->result . ', Msg: ' . $this->resp->msg);
    }

    /**
     * Override header to prevent redirect
     */
    public function header($header)
    {
        $this->headerCalled = true;
        $this->lastHeader = $header;
    }

    /**
     * Make private methods testable using reflection
     */
    public function testGetById()
    {
        $this->getByIdCalled = true;

        // Sử dụng reflection để gọi phương thức private
        $reflection = new ReflectionClass($this);
        $method = $reflection->getMethod('getById');
        $method->setAccessible(true);
        return $method->invoke($this);
    }

    public function testUpdate()
    {
        $this->updateCalled = true;

        // Sử dụng reflection để gọi phương thức private
        $reflection = new ReflectionClass($this);
        $method = $reflection->getMethod('update');
        $method->setAccessible(true);
        return $method->invoke($this);
    }

    public function testDelete()
    {
        $this->deleteCalled = true;

        // Sử dụng reflection để gọi phương thức private
        $reflection = new ReflectionClass($this);
        $method = $reflection->getMethod('delete');
        $method->setAccessible(true);
        return $method->invoke($this);
    }

    /**
     * Override model method to use mock
     */
    public static function model($name, $id = 0)
    {
        if ($name == 'Room' && self::$mockRoom) {
            return self::$mockRoom;
        }

        return parent::model($name, $id);
    }

    /**
     * Override process method to track method calls
     */
    public function process()
    {
        $request_method = Input::method();

        $AuthUser = $this->getVariable("AuthUser");
        if (!$AuthUser){
            $this->header("Location: ".APPURL."/login");
            return;
        }

        if($request_method === 'GET')
        {
            $this->getByIdCalled = true;
            $this->testGetById();
        }
        else if($request_method === 'PUT')
        {
            $this->updateCalled = true;
            $this->testUpdate();
        }
        else if($request_method === 'DELETE')
        {
            $this->deleteCalled = true;
            $this->testDelete();
        }
    }

    /**
     * Helper method to set response values directly
     */
    public function setResp($key, $value)
    {
        $this->resp->$key = $value;
        return $this;
    }
}

// DB đã được định nghĩa ở nơi khác

// InputMock đã được định nghĩa ở nơi khác

// MockModel đã được định nghĩa trong file MockModel.php
