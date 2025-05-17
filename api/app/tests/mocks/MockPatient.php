<?php
/**
 * Mock cho Patient model
 *
 * Class: MockPatient
 * File: api/app/tests/mocks/MockPatient.php
 *
 */

class MockPatient {
    public $id;
    public $phone;
    public $password;
    public $fb_id;
    public $name;
    public $email;
    public $avatar;
    public $create_at;
    public $update_at;
    private $data = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 0;
        $this->phone = null;
        $this->password = null;
        $this->fb_id = null;
        $this->name = null;
        $this->email = null;
        $this->avatar = null;
        $this->create_at = null;
        $this->update_at = null;
    }
    
    /**
     * Lấy giá trị của thuộc tính
     *
     * @param string $key Tên thuộc tính
     * @return mixed Giá trị của thuộc tính
     */
    public function get($key) {
        if (property_exists($this, $key)) {
            return $this->$key;
        }
        
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
    
    /**
     * Thiết lập giá trị cho thuộc tính
     *
     * @param string $key Tên thuộc tính
     * @param mixed $value Giá trị của thuộc tính
     * @return $this
     */
    public function set($key, $value) {
        if (property_exists($this, $key)) {
            $this->$key = $value;
        } else {
            $this->data[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Lưu patient
     *
     * @return $this
     */
    public function save() {
        // Giả lập lưu patient
        if (empty($this->id)) {
            $this->id = 1; // Giả sử ID = 1
        }
        
        return $this;
    }
    
    /**
     * Cập nhật patient
     *
     * @param array $data Dữ liệu cần cập nhật
     * @return boolean
     */
    public function update($data = []) {
        // Cập nhật các thuộc tính
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
        
        return true;
    }
}
