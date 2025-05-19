<?php

/**
 * Test case for TreatmentsController với mục tiêu tăng độ phủ code
 * Sử dụng cách tiếp cận kế thừa trực tiếp từ TreatmentsController
 */

// Định nghĩa các hằng số cần thiết cho test
if (!defined('APPURL')) {
    define('APPURL', 'http://localhost/app');
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../../uploads');
}

require_once __DIR__ . '/../ControllerTestCase.php';
require_once __DIR__ . '/../../controllers/TreatmentsController.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

// Định nghĩa các hằng số bảng nếu chưa tồn tại
if (!defined('TABLE_PREFIX')) {
    define('TABLE_PREFIX', 'tn_');
}
if (!defined('TABLE_TREATMENTS')) {
    define('TABLE_TREATMENTS', 'treatments');
}
if (!defined('TABLE_APPOINTMENTS')) {
    define('TABLE_APPOINTMENTS', 'appointments');
}

/**
 * Mock cho Model
 */
class MockModel
{
    private $data = [];
    private $available = true;
    
    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }
    
    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
    
    public function isAvailable()
    {
        return $this->available;
    }
    
    public function setAvailable($available)
    {
        $this->available = $available;
        return $this;
    }
    
    public function save()
    {
        return $this;
    }
    
    public function delete()
    {
        return $this;
    }
}

/**
 * Mock cho Input
 */
class InputMock
{
    public static function get($key)
    {
        $mockData = [
            'search' => 'Test',
            'order' => ['column' => 'name', 'dir' => 'asc'],
            'length' => 10,
            'start' => 0,
            'appointment_id' => 1
        ];
        return isset($mockData[$key]) ? $mockData[$key] : null;
    }
    
    public static function post($key)
    {
        $mockData = [
            'appointment_id' => 1,
            'name' => 'Uống thuốc',
            'type' => 'Thuốc',
            'times' => '3',
            'purpose' => 'Giảm đau',
            'instruction' => 'Uống sau khi ăn',
            'repeat_days' => 'Thực hiện mỗi ngày',
            'repeat_time' => 'Sáng và chiều'
        ];
        return isset($mockData[$key]) ? $mockData[$key] : null;
    }
    
    public static function method()
    {
        return 'GET';
    }
}

/**
 * Mock cho DB Query
 */
class MockQuery
{
    public function leftJoin($table, $first, $operator, $second)
    {
        return $this;
    }
    
    public function select($columns)
    {
        return $this;
    }
    
    public function where($column, $operator = null, $value = null)
    {
        if (is_callable($column)) {
            $column($this);
        }
        return $this;
    }
    
    public function orWhere($column, $operator, $value)
    {
        return $this;
    }
    
    public function orderBy($column, $direction = 'asc')
    {
        return $this;
    }
    
    public function limit($limit)
    {
        return $this;
    }
    
    public function offset($offset)
    {
        return $this;
    }
    
    public function get()
    {
        // Trả về dữ liệu mẫu
        $result = [];
        $result[] = (object)[
            'id' => 1,
            'appointment_id' => 1,
            'name' => 'Uống thuốc',
            'type' => 'Thuốc',
            'times' => 3,
            'purpose' => 'Giảm đau',
            'instruction' => 'Uống sau khi ăn',
            'repeat_days' => 'Thực hiện mỗi ngày',
            'repeat_time' => 'Sáng và chiều'
        ];
        $result[] = (object)[
            'id' => 2,
            'appointment_id' => 1,
            'name' => 'Tập vật lý trị liệu',
            'type' => 'Vật lý trị liệu',
            'times' => 2,
            'purpose' => 'Phục hồi chức năng',
            'instruction' => 'Tập 30 phút mỗi lần',
            'repeat_days' => 'Thực hiện mỗi ngày',
            'repeat_time' => 'Sáng và chiều'
        ];
        return $result;
    }
}

/**
 * Mock cho DB
 */
class DBMock
{
    public static function table($table)
    {
        return new MockQuery();
    }
    
    public static function raw($expression)
    {
        return $expression;
    }
}

/**
 * TreatmentsController có thể test được
 */
class TreatmentsControllerTestable extends TreatmentsController
{
    public $jsonEchoCalled = false;
    public $jsonEchoData;
    public $headerCalled = false;
    public $lastHeader;
    public $resp;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->resp = new stdClass();
        $this->resp->result = 0;
    }
    
    /**
     * Override jsonecho để ngăn exit
     */
    public function jsonecho($data = null)
    {
        $this->jsonEchoCalled = true;
        $this->jsonEchoData = $data ?: $this->resp;
    }
    
    /**
     * Override header để ngăn redirect
     */
    public function header($header)
    {
        $this->headerCalled = true;
        $this->lastHeader = $header;
    }
    
    /**
     * Override exit để ngăn thoát
     */
    protected function exit()
    {
        // Không làm gì
    }
    
    /**
     * Override các phương thức tương tác với DB
     */
    protected function getDB()
    {
        return 'DBMock';
    }
    
    /**
     * Override model để trả về mock model
     */
    public static function model($name, $id = null)
    {
        $model = new MockModel();
        if ($name == "Appointment") {
            $model->set('id', $id ?: 1);
            $model->set('doctor_id', 1);
            $model->set('status', 'processing');
            $model->set('date', date('d-m-Y'));
        } else if ($name == "Treatment") {
            $model->set('id', $id ?: 1);
            $model->set('appointment_id', 1);
            $model->set('name', 'Uống thuốc');
            $model->set('type', 'Thuốc');
            $model->set('times', 3);
            $model->set('purpose', 'Giảm đau');
            $model->set('instruction', 'Uống sau khi ăn');
            $model->set('repeat_days', 'Thực hiện mỗi ngày');
            $model->set('repeat_time', 'Sáng và chiều');
        }
        return $model;
    }
}
