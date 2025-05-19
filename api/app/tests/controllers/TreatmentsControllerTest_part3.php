<?php

/**
 * Phần tiếp theo của test case cho TreatmentsController
 * Sẽ được merge vào file chính sau
 */

    /**
     * Test case ID: TRTS_09
     * Kiểm tra phương thức save() khi thiếu trường bắt buộc
     */
    public function testSaveWithMissingRequiredField()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập POST data thiếu trường name
        InputMock::$postMock = function($key) {
            $mockData = [
                'appointment_id' => 1,
                // Thiếu trường 'name'
                'type' => 'Thuốc',
                'times' => '3',
                'purpose' => 'Giảm đau',
                'instruction' => 'Uống sau khi ăn'
            ];
            return isset($mockData[$key]) ? $mockData[$key] : null;
        };
        
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
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when required field is missing');
        $this->assertContains('Missing field', $this->controller->jsonEchoData->msg, 'Error message should indicate missing field');
    }
    
    /**
     * Test case ID: TRTS_10
     * Kiểm tra phương thức save() khi appointment không tồn tại
     */
    public function testSaveWithNonExistentAppointment()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập Controller::model để trả về mock model không có sẵn
        Controller::$modelMethod = function($name, $id = null) {
            $model = new MockModel();
            if ($name == "Appointment") {
                $model->setAvailable(false); // Appointment không tồn tại
            }
            return $model;
        };
        
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
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when appointment does not exist');
        $this->assertContains('Appointment is not available', $this->controller->jsonEchoData->msg, 'Error message should indicate appointment is not available');
    }
    
    /**
     * Test case ID: TRTS_11
     * Kiểm tra phương thức save() khi appointment có trạng thái không hợp lệ
     */
    public function testSaveWithInvalidAppointmentStatus()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập Controller::model để trả về mock model với trạng thái không hợp lệ
        Controller::$modelMethod = function($name, $id = null) {
            $model = new MockModel();
            if ($name == "Appointment") {
                $model->set('id', $id ?: 1);
                $model->set('doctor_id', 1);
                $model->set('status', 'done'); // Trạng thái không hợp lệ
                $model->set('date', date('d-m-Y'));
            }
            return $model;
        };
        
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
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when appointment status is invalid');
        $this->assertContains("The status of appointment is", $this->controller->jsonEchoData->msg, 'Error message should indicate appointment status issue');
    }
    
    /**
     * Test case ID: TRTS_12
     * Kiểm tra phương thức save() khi appointment đã qua ngày
     */
    public function testSaveWithPastAppointment()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập Controller::model để trả về mock model với ngày đã qua
        Controller::$modelMethod = function($name, $id = null) {
            $model = new MockModel();
            if ($name == "Appointment") {
                $model->set('id', $id ?: 1);
                $model->set('doctor_id', 1);
                $model->set('status', 'processing');
                // Ngày hẹn là ngày hôm qua
                $yesterday = date('d-m-Y', strtotime('-1 day'));
                $model->set('date', $yesterday);
            }
            return $model;
        };
        
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
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when appointment date is in the past');
        $this->assertContains('Today is', $this->controller->jsonEchoData->msg, 'Error message should indicate date issue');
    }
