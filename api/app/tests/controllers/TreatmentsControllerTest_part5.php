<?php

/**
 * Phần tiếp theo của test case cho TreatmentsController
 * Sẽ được merge vào file chính sau
 */

    /**
     * Test case ID: TRTS_16
     * Kiểm tra phương thức save() khi xảy ra ngoại lệ
     */
    public function testSaveWithException()
    {
        // Thiết lập AuthUser
        $authUser = new MockModel();
        $authUser->set('id', 1);
        $authUser->set('role', 'admin');
        $this->controller->setVariable('AuthUser', $authUser);
        
        // Thiết lập Controller::model để trả về mock model với save() ném ngoại lệ
        Controller::$modelMethod = function($name, $id = null) {
            if ($name == "Appointment") {
                $model = new MockModel();
                $model->set('id', $id ?: 1);
                $model->set('doctor_id', 1);
                $model->set('status', 'processing');
                $model->set('date', date('d-m-Y'));
                return $model;
            } else if ($name == "Treatment") {
                $model = new MockModel();
                $model->save = function() {
                    throw new Exception('Database error during save');
                };
                return $model;
            }
            return new MockModel();
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
        $this->assertEquals(0, $this->controller->jsonEchoData->result, 'Result should be 0 when exception occurs');
        $this->assertContains('Database error', $this->controller->jsonEchoData->msg, 'Error message should contain exception message');
    }
