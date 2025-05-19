<?php

/**
 * Phần tiếp theo của test case cho TreatmentsController
 * Sẽ được merge vào file chính sau
 */

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
        
        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];
        
        // Gọi phương thức process() với phương thức POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        try {
            $this->controller->process();
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }
        
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
        
        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
        $this->assertObjectHasAttribute('quantity', $this->controller->jsonEchoData, 'Response should have quantity attribute');
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
        
        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful retrieval');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }
    
    /**
     * Test case ID: TRTS_07
     * Kiểm tra phương thức getAll() khi xảy ra ngoại lệ
     */
    public function testGetAllWithException()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập DB::table để ném ngoại lệ
        DB::$tableMock = function($table) {
            throw new Exception('Database error');
        };
        
        // Gọi phương thức getAll() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsController', 'getAll');
        $reflection->setAccessible(true);
        
        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when exception occurs');
        $this->assertContains('Database error', $this->controller->jsonEchoData->msg, 'Error message should contain exception message');
        
        // Khôi phục DB::table
        DB::$tableMock = function($table) {
            return new MockQuery();
        };
    }
    
    /**
     * Test case ID: TRTS_08
     * Kiểm tra phương thức save() trực tiếp
     */
    public function testSaveDirectly()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];
        
        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsController', 'save');
        $reflection->setAccessible(true);
        
        try {
            $reflection->invoke($this->controller);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Kiểm tra nếu exception chứa thông tin về jsonecho
            $this->assertContains('JsonEchoExit', $e->getMessage(), 'JsonEcho should be called');
        }
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful creation');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }
