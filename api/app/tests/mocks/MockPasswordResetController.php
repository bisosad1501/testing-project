<?php
/**
 * Mock cho PasswordResetController để test
 */

// Đảm bảo PasswordResetController đã được include
require_once __DIR__ . '/../../controllers/PasswordResetController.php';

class MockPasswordResetController extends PasswordResetController
{
    public $jsonEchoCalled = false;
    public $jsonEchoData = null;
    public $doctorModel = null;
    public $inputData = [];
    public $routeParams = null;
    
    /**
     * Override jsonecho để không thực sự kết thúc quá trình thực thi
     */
    public function jsonecho()
    {
        $this->jsonEchoCalled = true;
        $this->jsonEchoData = $this->resp;
        // Không gọi exit() để tiếp tục thực thi test
    }
    
    /**
     * Override model để trả về mock Doctor model
     */
    public static function model($name, $id = 0)
    {
        if ($name == "Doctor") {
            return $this->doctorModel;
        }
        return parent::model($name, $id);
    }
    
    /**
     * Gọi phương thức resetPassword() (vì nó là private)
     */
    public function callResetPassword()
    {
        return $this->resetPassword();
    }
    
    /**
     * Gọi phương thức resetpass() (vì nó là private)
     */
    public function callResetpass()
    {
        return $this->resetpass();
    }
    
    /**
     * Thiết lập mock input data
     */
    public function setInputData($data)
    {
        $this->inputData = $data;
    }
    
    /**
     * Thiết lập mock route params
     */
    public function setRouteParams($params)
    {
        $this->routeParams = $params;
    }
    
    /**
     * Thiết lập mock Doctor model
     */
    public function setDoctorModel($model)
    {
        $this->doctorModel = $model;
    }
}
