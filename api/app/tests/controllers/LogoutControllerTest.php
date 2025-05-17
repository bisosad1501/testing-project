<?php
/**
 * Test cho LogoutController
 *
 * Class: LogoutControllerTest
 * File: api/app/tests/controllers/LogoutControllerTest.php
 *
 */

// Định nghĩa các hằng số cần thiết cho test
if (!defined('EC_SALT')) {
    define('EC_SALT', 'test_salt_for_unit_tests');
}
if (!defined('APPURL')) {
    define('APPURL', 'http://localhost/app');
}
require_once __DIR__ . '/../ControllerTestCase.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

// Include MockAuthUser
require_once __DIR__ . '/../mocks/MockAuthUser.php';

class LogoutControllerTest extends ControllerTestCase
{
    /**
     * @var LogoutController Controller instance
     */
    protected $controller;

    /**
     * Set up test environment before each test
     * Thiết lập môi trường test trước mỗi test
     */
    protected function setUp()
    {
        parent::setUp();

        // Khởi tạo controller
        $this->controller = $this->createController('LogoutController');
    }

    /**
     * LOGOUT_001
     * Kiểm tra cấu trúc của LogoutController
     * Test LogoutController structure
     */
    public function testLogoutControllerStructure()
    {
        // Kiểm tra xem controller có phương thức process() không
        $this->assertTrue(method_exists($this->controller, 'process'), 'Controller should have process() method');

        // Kiểm tra xem controller có phương thức logout() không
        $reflection = new ReflectionClass($this->controller);
        $this->assertTrue($reflection->hasMethod('logout'), 'Controller should have logout() method');

        // Kiểm tra xem phương thức logout() có private không
        $method = $reflection->getMethod('logout');
        $this->assertTrue($method->isPrivate(), 'logout() method should be private');
    }

    /**
     * LOGOUT_002
     * Kiểm tra nội dung của phương thức process()
     * Test process() method content
     */
    public function testProcessMethodContent()
    {
        // Lấy nội dung của phương thức process()
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('process');
        $method->setAccessible(true);

        // Lấy code của phương thức
        $fileName = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $file = file($fileName);
        $code = '';
        for ($i = $startLine - 1; $i < $endLine; $i++) {
            $code .= $file[$i];
        }

        // Kiểm tra xem code có gọi $this->logout() không
        $this->assertContains('$this->logout()', $code, 'process() method should call logout() method');
    }

    /**
     * LOGOUT_003
     * Kiểm tra nội dung của phương thức logout()
     * Test logout() method content
     */
    public function testLogoutMethodContent()
    {
        // Lấy nội dung của phương thức logout()
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('logout');
        $method->setAccessible(true);

        // Lấy code của phương thức
        $fileName = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $file = file($fileName);
        $code = '';
        for ($i = $startLine - 1; $i < $endLine; $i++) {
            $code .= $file[$i];
        }

        // Kiểm tra xem code có các thành phần cần thiết không
        $this->assertContains('$AuthUser = $this->getVariable("AuthUser")', $code, 'logout() method should get AuthUser variable');
        $this->assertContains('setcookie("nplh", null', $code, 'logout() method should clear nplh cookie');
        $this->assertContains('setcookie("nplrmm", null', $code, 'logout() method should clear nplrmm cookie');
        $this->assertContains('Event::trigger("user.signout"', $code, 'logout() method should trigger user.signout event');
        $this->assertContains('header("Location: ".APPURL)', $code, 'logout() method should redirect to APPURL');
        $this->assertContains('exit', $code, 'logout() method should call exit');
    }
}
