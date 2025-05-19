<?php

    /**
     * Test case ID: TRTS_07
     * Kiểm tra phương thức save() trực tiếp
     */
    public function testSaveDirectly()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsController', 'save');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(1, $this->controller->jsonEchoData->result, 'Result should be 1 for successful creation');
        $this->assertObjectHasAttribute('data', $this->controller->jsonEchoData, 'Response should have data attribute');
    }
    
    /**
     * Test case ID: TRTS_08
     * Kiểm tra phương thức save() khi thiếu trường bắt buộc
     */
    public function testSaveWithMissingRequiredField()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập Input::post() để trả về null cho trường name
        if (class_exists('Input')) {
            Input::$postMock = function($key) {
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
        }
        
        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsController', 'save');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when required field is missing');
        $this->assertContains('Missing field', $this->controller->jsonEchoData->msg, 'Error message should indicate missing field');
    }
    
    /**
     * Test case ID: TRTS_09
     * Kiểm tra phương thức save() khi appointment không tồn tại
     */
    public function testSaveWithNonExistentAppointment()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập mock model để trả về appointment không tồn tại
        $appointmentModel = new MockModel();
        $appointmentModel->setAvailable(false);
        
        // Ghi đè phương thức model
        TreatmentsControllerTestable::$modelMethod = function($name, $id = null) use ($appointmentModel) {
            if ($name == "Appointment") {
                return $appointmentModel;
            }
            return new MockModel();
        };
        
        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsController', 'save');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when appointment does not exist');
        $this->assertContains('Appointment is not available', $this->controller->jsonEchoData->msg, 'Error message should indicate appointment is not available');
    }
    
    /**
     * Test case ID: TRTS_10
     * Kiểm tra phương thức save() khi appointment có trạng thái không hợp lệ
     */
    public function testSaveWithInvalidAppointmentStatus()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập mock model để trả về appointment với trạng thái không hợp lệ
        $appointmentModel = new MockModel();
        $appointmentModel->set('id', 1);
        $appointmentModel->set('doctor_id', 1);
        $appointmentModel->set('status', 'done'); // Trạng thái không hợp lệ
        $appointmentModel->set('date', date('d-m-Y'));
        
        // Ghi đè phương thức model
        TreatmentsControllerTestable::$modelMethod = function($name, $id = null) use ($appointmentModel) {
            if ($name == "Appointment") {
                return $appointmentModel;
            }
            return new MockModel();
        };
        
        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsController', 'save');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when appointment status is invalid');
        $this->assertContains('The status of appointment is', $this->controller->jsonEchoData->msg, 'Error message should indicate appointment status issue');
    }
    
    /**
     * Test case ID: TRTS_11
     * Kiểm tra phương thức save() khi appointment đã qua ngày
     */
    public function testSaveWithPastAppointment()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập mock model để trả về appointment với ngày đã qua
        $appointmentModel = new MockModel();
        $appointmentModel->set('id', 1);
        $appointmentModel->set('doctor_id', 1);
        $appointmentModel->set('status', 'processing');
        // Ngày hẹn là ngày hôm qua
        $yesterday = date('d-m-Y', strtotime('-1 day'));
        $appointmentModel->set('date', $yesterday);
        
        // Ghi đè phương thức model
        TreatmentsControllerTestable::$modelMethod = function($name, $id = null) use ($appointmentModel) {
            if ($name == "Appointment") {
                return $appointmentModel;
            }
            return new MockModel();
        };
        
        // Gọi phương thức save() trực tiếp bằng Reflection
        $reflection = new ReflectionMethod('TreatmentsController', 'save');
        $reflection->setAccessible(true);
        $reflection->invoke($this->controller);
        
        // Kiểm tra kết quả
        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when appointment date is in the past');
        $this->assertContains('Today is', $this->controller->jsonEchoData->msg, 'Error message should indicate date issue');
    }
}
