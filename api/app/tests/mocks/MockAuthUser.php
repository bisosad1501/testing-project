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

    public function __construct($role = 'member', $data = [])
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
        // Mock save method - không thực sự lưu vào database
        return $this;
    }

    public function isAvailable()
    {
        // Mock isAvailable method - luôn trả về true trừ khi id không tồn tại
        return isset($this->data['id']) && $this->data['id'] > 0;
    }
}
