<?php

/**
 * Test case for ServiceController với mục tiêu tăng độ phủ code
 */

// Định nghĩa các hằng số cần thiết cho test
if (!defined('APPURL')) {
    define('APPURL', 'http://localhost/app');
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../../uploads');
}

require_once __DIR__ . '/../ControllerTestCase.php';

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
if (!defined('TABLE_BOOKINGS')) {
    define('TABLE_BOOKINGS', 'booking');
}
if (!defined('TABLE_DOCTOR_AND_SERVICE')) {
    define('TABLE_DOCTOR_AND_SERVICE', 'doctor_and_service');
}

/**
 * Mock cho Model
 */
class MockModel
{
    private $data = [];

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
        return true;
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
 * Test case for ServiceController
 */
class ServiceControllerBasicTest extends ControllerTestCase
{
    /**
     * Test case ID: SVC_B01
     * Kiểm tra phương thức process() với phương thức GET
     */
    public function testProcessWithGetMethod()
    {
        // Tạo một instance của ServiceController
        $controller = new ServiceController();

        // Thiết lập AuthUser
        $authUser = Controller::model('User', 1);
        $authUser->set('role', 'admin');
        $controller->setVariable('AuthUser', $authUser);

        // Thiết lập Route với ID
        $route = new stdClass();
        $route->params = new stdClass();
        $route->params->id = 1;
        $controller->setVariable('Route', $route);

        // Gọi phương thức process() với phương thức GET
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Bắt output để tránh echo
        ob_start();
        $controller->process();
        $output = ob_get_clean();

        // Kiểm tra kết quả
        $this->assertNotEmpty($output, 'Output should not be empty');
        $this->assertContains('"result"', $output, 'Output should contain result field');
    }

    /**
     * Test case ID: SVC_B02
     * Kiểm tra phương thức process() với phương thức PUT
     */
    public function testProcessWithPutMethod()
    {
        // Tạo một instance của ServiceController
        $controller = new ServiceController();

        // Thiết lập AuthUser
        $authUser = Controller::model('User', 1);
        $authUser->set('role', 'admin');
        $controller->setVariable('AuthUser', $authUser);

        // Thiết lập Route với ID
        $route = new stdClass();
        $route->params = new stdClass();
        $route->params->id = 1;
        $controller->setVariable('Route', $route);

        // Gọi phương thức process() với phương thức PUT
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        // Bắt output để tránh echo
        ob_start();
        $controller->process();
        $output = ob_get_clean();

        // Kiểm tra kết quả
        $this->assertNotEmpty($output, 'Output should not be empty');
        $this->assertContains('"result"', $output, 'Output should contain result field');
    }

    /**
     * Test case ID: SVC_B03
     * Kiểm tra phương thức process() với phương thức DELETE
     */
    public function testProcessWithDeleteMethod()
    {
        // Tạo một instance của ServiceController
        $controller = new ServiceController();

        // Thiết lập AuthUser
        $authUser = Controller::model('User', 1);
        $authUser->set('role', 'admin');
        $controller->setVariable('AuthUser', $authUser);

        // Thiết lập Route với ID
        $route = new stdClass();
        $route->params = new stdClass();
        $route->params->id = 1;
        $controller->setVariable('Route', $route);

        // Gọi phương thức process() với phương thức DELETE
        $_SERVER['REQUEST_METHOD'] = 'DELETE';

        // Bắt output để tránh echo
        ob_start();
        $controller->process();
        $output = ob_get_clean();

        // Kiểm tra kết quả
        $this->assertNotEmpty($output, 'Output should not be empty');
        $this->assertContains('"result"', $output, 'Output should contain result field');
    }

    /**
     * Test case ID: SVC_B04
     * Kiểm tra phương thức process() với phương thức POST và action avatar
     */
    public function testProcessWithPostMethodAndAvatarAction()
    {
        // Bỏ qua test này vì nó yêu cầu $_FILES
        $this->markTestSkipped('Skipping test that requires $_FILES');
    }

    /**
     * Test case ID: SVC_B05
     * Kiểm tra phương thức process() với phương thức POST và action không hợp lệ
     */
    public function testProcessWithPostMethodAndInvalidAction()
    {
        // Tạo một instance của ServiceController
        $controller = new ServiceController();

        // Thiết lập AuthUser
        $authUser = Controller::model('User', 1);
        $authUser->set('role', 'admin');
        $controller->setVariable('AuthUser', $authUser);

        // Thiết lập Route với ID
        $route = new stdClass();
        $route->params = new stdClass();
        $route->params->id = 1;
        $controller->setVariable('Route', $route);

        // Gọi phương thức process() với phương thức POST và action không hợp lệ
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = 'invalid';

        // Bắt output để tránh echo
        ob_start();
        $controller->process();
        $output = ob_get_clean();

        // Kiểm tra kết quả
        $this->assertNotEmpty($output, 'Output should not be empty');
        $this->assertContains('"result":0', $output, 'Output should contain result=0');
        $this->assertContains('not valid', $output, 'Output should contain error message');
    }
}
