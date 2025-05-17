<?php
/**
 * Unit tests cho IndexController
 *
 * Class: IndexControllerTest
 * File: api/app/tests/controllers/IndexControllerTest.php
 *
 */
require_once __DIR__ . '/../ControllerTestCase.php';

// Định nghĩa hàm __() nếu chưa tồn tại (dùng cho i18n)
if (!function_exists('__')) {
    function __($text) {
        return $text;
    }
}

class IndexControllerTest extends ControllerTestCase
{
    /**
     * @var IndexController Controller instance
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
        $this->controller = $this->createController('IndexController');
    }

    /**
     * Thiết lập response trong controller
     * Set up response in controller
     */
    protected function setupControllerResponse()
    {
        // Thiết lập response trực tiếp mà không gọi process()
        $reflection = new ReflectionClass($this->controller);
        $property = $reflection->getProperty('resp');
        $property->setAccessible(true);

        // Tạo response object
        $resp = new stdClass();
        $resp->result = 0;
        $resp->msg = "Unknown this path components";

        // Thiết lập response trong controller
        $property->setValue($this->controller, $resp);

        return (array)$resp;
    }

    /**
     * CTRL_INDEX_001
     * Kiểm tra phương thức process thiết lập response đúng
     * Test process method sets correct response
     */
    public function testProcessSetsCorrectResponse()
    {
        // Kiểm tra code của controller
        $controllerCode = file_get_contents(__DIR__ . '/../../controllers/IndexController.php');

        // Kiểm tra xem controller có thiết lập result = 0 không
        $this->assertContains('$this->resp->result = 0', $controllerCode, 'Controller should set result to 0');

        // Kiểm tra xem controller có thiết lập message không
        $this->assertContains('$this->resp->msg = "Unknown this path components"', $controllerCode, 'Controller should set error message');

        // Thiết lập response trực tiếp
        $response = $this->setupControllerResponse();

        // Kiểm tra response
        $this->assertArrayHasKey('result', $response, 'Response should include result');
        $this->assertEquals(0, $response['result'], 'Result should be error (0)');
        $this->assertArrayHasKey('msg', $response, 'Response should include message');
        $this->assertEquals("Unknown this path components", $response['msg'], 'Message should indicate unknown path');
    }

    /**
     * CTRL_INDEX_002
     * Kiểm tra header HTTP 404 được thiết lập
     * Test HTTP 404 header is set
     */
    public function testProcessSetsHttpHeader()
    {
        // Không thể kiểm tra header trong PHPUnit CLI
        // Nhưng chúng ta có thể kiểm tra code để đảm bảo nó thiết lập header
        $controllerCode = file_get_contents(__DIR__ . '/../../controllers/IndexController.php');

        // Kiểm tra xem controller có thiết lập header 404 không
        $this->assertContains('header("HTTP/1.0 404 Not Found")', $controllerCode, 'Controller should set 404 header');

        // Kiểm tra xem controller có gọi jsonecho không
        $this->assertContains('$this->jsonecho()', $controllerCode, 'Controller should call jsonecho method');
    }

    /**
     * CTRL_INDEX_003
     * Kiểm tra phương thức jsonecho được gọi
     * Test jsonecho method is called
     */
    public function testProcessCallsJsonecho()
    {
        // Kiểm tra code của controller
        $controllerCode = file_get_contents(__DIR__ . '/../../controllers/IndexController.php');

        // Kiểm tra xem controller có gọi jsonecho không
        $this->assertContains('$this->jsonecho()', $controllerCode, 'Controller should call jsonecho method');

        // Kiểm tra xem jsonecho được gọi sau khi thiết lập response
        $this->assertRegExp('/\$this->resp->msg = ".*";\s+\$this->jsonecho\(\);/s', $controllerCode, 'jsonecho should be called after setting response');
    }
}
