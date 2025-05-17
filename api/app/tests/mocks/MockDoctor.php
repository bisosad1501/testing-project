<?php
/**
 * Mock cho Doctor model
 *
 * Class: MockDoctor
 * File: api/app/tests/mocks/MockDoctor.php
 *
 */

class MockDoctor {
    public $id;
    public $email;
    public $password;
    public $name;
    public $avatar;
    public $phone;
    public $address;
    public $clinic_id;
    public $active;
    public $create_at;
    public $update_at;
    private $data = [];

    /**
     * Constructor
     *
     * @param string $email Email của doctor
     * @param array $data Dữ liệu khởi tạo
     */
    public function __construct($email = null, $data = []) {
        $this->id = isset($data['id']) ? $data['id'] : 1;
        $this->email = $email;
        $this->password = isset($data['password']) ? $data['password'] : null;
        $this->name = isset($data['name']) ? $data['name'] : 'Doctor Name';
        $this->avatar = isset($data['avatar']) ? $data['avatar'] : null;
        $this->phone = isset($data['phone']) ? $data['phone'] : null;
        $this->address = isset($data['address']) ? $data['address'] : null;
        $this->clinic_id = isset($data['clinic_id']) ? $data['clinic_id'] : null;
        $this->active = isset($data['active']) ? $data['active'] : 1;
        $this->create_at = isset($data['create_at']) ? $data['create_at'] : date('Y-m-d H:i:s');
        $this->update_at = isset($data['update_at']) ? $data['update_at'] : date('Y-m-d H:i:s');
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
     * Kiểm tra xem doctor có tồn tại không
     *
     * @return boolean
     */
    public function isAvailable() {
        return !empty($this->id) && !empty($this->email);
    }

    /**
     * Kiểm tra xem doctor có active không
     *
     * @return boolean
     */
    public function isActive() {
        return $this->active == 1;
    }

    /**
     * Mock cho save()
     */
    public function save() {
        return $this;
    }

    /**
     * Mock cho setEmailAsVerified()
     */
    public function setEmailAsVerified() {
        $this->data['email_verified'] = true;
        return $this;
    }
}
