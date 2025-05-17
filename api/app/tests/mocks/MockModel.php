<?php
/**
 * Mock cho các model
 */
class MockModel
{
    private $data = [];
    private $is_available = true;
    
    /**
     * Thiết lập giá trị cho một trường
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }
    
    /**
     * Lấy giá trị của một trường
     */
    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
    
    /**
     * Kiểm tra xem model có tồn tại không
     */
    public function isAvailable()
    {
        return $this->is_available;
    }
    
    /**
     * Thiết lập trạng thái tồn tại
     */
    public function setAvailable($available)
    {
        $this->is_available = $available;
        return $this;
    }
    
    /**
     * Lưu model
     */
    public function save()
    {
        return $this;
    }
}
