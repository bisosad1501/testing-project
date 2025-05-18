<?php
/**
 * Mock class for AuthUser
 *
 * File: api/app/tests/mocks/MockAuthUser.php
 */

/**
 * Mock model cho AuthUser với phương thức get() và set() có thể kiểm soát
 */
class MockAuthUser
{
    private $data;
    private $role;
    private $available = true;

    public function __construct($role = null, $data = [])
    {
        $this->data = is_array($data) ? $data : [];
        $this->role = $role;
    }

    public function get($key)
    {
        if ($key === 'role') {
            return $this->role;
        }
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function save()
    {
        // Mock save method - giả lập lưu thành công
        return $this;
    }

    public function isAvailable()
    {
        // Trả về trạng thái available đã thiết lập
        return $this->available;
    }

    public function setAvailable($available)
    {
        $this->available = $available;
        return $this;
    }
}