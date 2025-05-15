<?php
/**
 * Mock class for AuthUser
 * 
 * File: api/app/tests/mocks/MockAuthUser.php
 */

/**
 * Mock model cho AuthUser với phương thức get() có thể kiểm soát
 */
class MockAuthUser
{
    private $data;
    private $role;
    
    public function __construct($data, $role)
    {
        $this->data = $data;
        $this->role = $role;
    }
    
    public function get($key)
    {
        if ($key === 'role') {
            return $this->role;
        }
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }
}
