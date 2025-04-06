<?php
/**
 * Base class for Controller testing
 * 
 * Provides functionality for testing controllers including:
 * - Request simulation (GET, POST, PUT, DELETE)
 * - Response capturing and validation
 * - Session simulation
 * - JSON response testing
 */
require_once __DIR__ . '/DatabaseTestCase.php';

class ControllerTestCase extends DatabaseTestCase 
{
    /**
     * Controller being tested
     * @var Controller
     */
    protected $controller;
    
    /**
     * Current request data
     * @var array
     */
    protected $requestData = [];
    
    /**
     * Store original server and request variables
     * @var array
     */
    private $originalServer;
    private $originalGet;
    private $originalPost;
    
    /**
     * Setup environment before each test
     */
    protected function setUp()
    {
        // Store original values
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        
        // Initialize parent (database connection etc.)
        parent::setUp();
    }
    
    /**
     * Clean up after each test
     */
    protected function tearDown()
    {
        // Restore original values
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        
        // Restore input mocks to default state
        if (class_exists('InputMock')) {
            InputMock::$methodMock = null;
            InputMock::$getMock = null;
            InputMock::$postMock = null;
            InputMock::$putMock = null;
            InputMock::$patchMock = null;
        }
        
        parent::tearDown();
    }
    
    
    /**
     * Mock HTTP request with method and data
     * 
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param array $data Request data
     */
    protected function mockRequest($method, $data = [])
    {
        // Set server request method
        $_SERVER['REQUEST_METHOD'] = $method;
        
        // Mock Input class method calls
        InputMock::$methodMock = function() use ($method) {
            return $method;
        };
        
        if ($method === 'POST') {
            InputMock::$postMock = function($key = null) use ($data) {
                if ($key === null) {
                    return $data;
                }
                return isset($data[$key]) ? $data[$key] : null;
            };
        } else if ($method === 'PUT') {
            InputMock::$putMock = function($key = null) use ($data) {
                if ($key === null) {
                    return $data;
                }
                return isset($data[$key]) ? $data[$key] : null;
            };
        } else if ($method === 'PATCH') {
            InputMock::$patchMock = function($key = null) use ($data) {
                if ($key === null) {
                    return $data;
                }
                return isset($data[$key]) ? $data[$key] : null;
            };
            
            // Thêm debug để xác nhận function mock đã được thiết lập
            $func = InputMock::$patchMock;
            if ($func && isset($data['status'])) {
                $status = $func('status');
                if ($status != $data['status']) {
                    error_log("WARNING: PATCH mock not working correctly for status!");
                }
            }
        }
    }
    
    /**
     * Mock session data
     * 
     * @param array $sessionData Session data to set
     * @return self For method chaining
     */
    protected function mockSession($sessionData = [])
    {
        // Initialize session
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set session data
        foreach ($sessionData as $key => $value) {
            $_SESSION[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Get controller response from the resp property
     *
     * @return array Response data
     */
    protected function getControllerResponse()
{
    $reflection = new ReflectionClass($this->controller);
    $property = $reflection->getProperty('resp');
    $property->setAccessible(true);
    
    $response = $property->getValue($this->controller);
    
    // Sửa: Xử lý đúng đối tượng resp từ controller
    if (is_object($response)) {
        return [
            'result' => property_exists($response, 'result') ? $response->result : 0,
            'msg' => property_exists($response, 'msg') ? $response->msg : '',
            'data' => property_exists($response, 'data') ? $response->data : null
        ];
    }
    
    return $response;
}
    
    /**
     * Assert model fields match database record
     *
     * @param array $expected Expected field values
     * @param string $table Table name
     * @param array $where Where conditions for lookup
     * @param array $fields Các trường cần so sánh (mặc định là tất cả)
     */
    protected function assertModelMatchesDatabase(array $expected, $table, array $where, array $fields = [])
    {
        // Sử dụng PDO đã thiết lập trong DatabaseTestCase thay vì Database::instance()
        $whereClause = [];
        $params = [];
        
        foreach ($where as $field => $value) {
            $whereClause[] = "$field = ?";
            $params[] = $value;
        }
        
        $whereStr = implode(' AND ', $whereClause);
        
        $query = "SELECT * FROM $table WHERE $whereStr LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotEmpty($record, "Record not found in $table");
        
        foreach ($expected as $field => $value) {
            $this->assertEquals($value, $record[$field], "Field '$field' does not match expected value");
        }
    }
    
    /**
     * Assert response status is success
     *
     * @param array $response Controller response
     * @param int $expectedResult Expected result value (default 1 for success)
     */
    protected function assertResponseStatus($response, $expectedResult = 1)
    {
        $this->assertArrayHasKey('result', $response, 'Response should have result key');
        $this->assertEquals($expectedResult, $response['result'], 'Response result should be ' . $expectedResult);
        
        if ($expectedResult === 1) {
            $this->assertArrayHasKey('msg', $response, 'Success response should have msg key');
            $this->assertEquals('Action successfully !', $response['msg'], 'Success message should be "Action successfully !"');
        }
    }
    
    /**
     * Assert that response contains expected data structure
     * 
     * @param array $response Controller response
     * @param array $expectedData Data structure to verify
     * @param bool $exact Whether to check for exact match or subset
     */
    protected function assertResponseData($response, $expectedData, $exact = false)
    {
        $this->assertArrayHasKey('data', $response, 'Response should have data property');
        
        if ($exact) {
            $this->assertEquals($expectedData, $response['data'], 'Response data should exactly match expected data');
        } else {
            foreach ($expectedData as $key => $value) {
                $this->assertArrayHasKey($key, $response['data'], "Response data should contain key '$key'");
                $this->assertEquals($value, $response['data'][$key], "Value for '$key' should match expected value");
            }
        }
    }
    
    /**
     * Create a test controller instance
     * 
     * @param string $className Controller class name
     * @return mixed Controller instance
     */
    protected function createController($className)
    {
        $controller = new $className();
        $this->controller = $controller;
        return $controller;
    }
}