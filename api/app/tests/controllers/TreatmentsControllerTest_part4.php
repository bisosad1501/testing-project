<?php

/**
 * Phần tiếp theo của test case cho TreatmentsController
 * Sẽ được merge vào file chính sau
 */

    /**
     * Test case ID: TRTS_13
     * Kiểm tra phương thức save() khi name không hợp lệ
     */
    public function testSaveWithInvalidName()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];
        
        // Thiết lập POST data với name không hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'appointment_id' => 1,
                'name' => '@#$%^&*', // Name không hợp lệ
                'type' => 'Thuốc',
                'times' => '3',
                'purpose' => 'Giảm đau',
                'instruction' => 'Uống sau khi ăn'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };
        
        // Override hàm isAddress để trả về 0 cho name không hợp lệ
        $originalIsAddress = null;
        if (function_exists('isAddress')) {
            $originalIsAddress = 'isAddress';
        }
        
        function isAddress($address) {
            return 0; // Luôn trả về không hợp lệ
        }
        
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
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when name is invalid');
        $this->assertContains('Name only has letters and space', $this->controller->jsonEchoData->msg, 'Error message should indicate name validation issue');
        
        // Khôi phục hàm isAddress
        if ($originalIsAddress) {
            function isAddress($address) {
                return preg_match('/^[a-zA-Z0-9\s\.,\-\/]+$/', $address) ? 1 : 0;
            }
        }
    }
    
    /**
     * Test case ID: TRTS_14
     * Kiểm tra phương thức save() khi type không hợp lệ
     */
    public function testSaveWithInvalidType()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];
        
        // Thiết lập POST data với type không hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'appointment_id' => 1,
                'name' => 'Uống thuốc',
                'type' => '@#$%^&*', // Type không hợp lệ
                'times' => '3',
                'purpose' => 'Giảm đau',
                'instruction' => 'Uống sau khi ăn'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };
        
        // Override hàm isVietnameseName để trả về 0 cho type không hợp lệ
        $originalIsVietnameseName = null;
        if (function_exists('isVietnameseName')) {
            $originalIsVietnameseName = 'isVietnameseName';
        }
        
        function isVietnameseName($name) {
            return 0; // Luôn trả về không hợp lệ
        }
        
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
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when type is invalid');
        $this->assertContains('Type only has letters and space', $this->controller->jsonEchoData->msg, 'Error message should indicate type validation issue');
        
        // Khôi phục hàm isVietnameseName
        if ($originalIsVietnameseName) {
            function isVietnameseName($name) {
                return preg_match('/^[a-zA-Z\s]+$/', $name) ? 1 : 0;
            }
        }
    }
    
    /**
     * Test case ID: TRTS_15
     * Kiểm tra phương thức save() khi times không hợp lệ
     */
    public function testSaveWithInvalidTimes()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập Controller::model để trả về mock model
        Controller::$modelMethod = [$this, 'mockControllerModel'];
        
        // Thiết lập POST data với times không hợp lệ
        InputMock::$postMock = function($key) {
            $mockData = [
                'appointment_id' => 1,
                'name' => 'Uống thuốc',
                'type' => 'Thuốc',
                'times' => 'abc', // Times không hợp lệ
                'purpose' => 'Giảm đau',
                'instruction' => 'Uống sau khi ăn'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };
        
        // Override hàm isNumber để trả về false cho times không hợp lệ
        $originalIsNumber = null;
        if (function_exists('isNumber')) {
            $originalIsNumber = 'isNumber';
        }
        
        function isNumber($number) {
            return false; // Luôn trả về không hợp lệ
        }
        
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
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when times is invalid');
        $this->assertContains("Treatment's times is not valid", $this->controller->jsonEchoData->msg, 'Error message should indicate times validation issue');
        
        // Khôi phục hàm isNumber
        if ($originalIsNumber) {
            function isNumber($number) {
                return is_numeric($number);
            }
        }
    }
