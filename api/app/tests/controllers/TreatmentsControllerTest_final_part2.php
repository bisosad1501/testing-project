<?php

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
        
        // Thiết lập mock cho Input
        $this->setInputMock();
    }
    
    /**
     * Thiết lập mock cho Input
     */
    protected function setInputMock()
    {
        // Ghi đè các phương thức của Input
        if (class_exists('Input')) {
            Input::$methodMock = 'GET';
            Input::$getMock = function($key) {
                return InputMock::get($key);
            };
            Input::$postMock = function($key) {
                return InputMock::post($key);
            };
        }
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
        
        // Thiết lập Input::method() để trả về GET
        if (class_exists('Input')) {
            Input::$methodMock = 'GET';
        }
        
        // Gọi phương thức process()
        $this->controller->process();
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }
    
    /**
     * Test case ID: TRTS_04
     * Kiểm tra phương thức process() với phương thức POST
     */
    public function testProcessWithPostMethod()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập Input::method() để trả về POST
        if (class_exists('Input')) {
            Input::$methodMock = 'POST';
        }
        
        // Gọi phương thức process()
        $this->controller->process();
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful creation');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }
    
    /**
     * Test case ID: TRTS_05
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
        $reflection = new ReflectionMethod('TreatmentsController', 'getAll');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }
    
    /**
     * Test case ID: TRTS_06
     * Kiểm tra phương thức getAll() với role member
     */
    public function testGetAllWithMemberRole()
    {
        // Thiết lập AuthUser với role member
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'member');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Gọi phương thức getAll() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsController', 'getAll');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }
}
