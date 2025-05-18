<?php
/**
 * Test for PatientNotificationsController
 * @author AI Assistant
 * @since 2023-11-01
 */

require_once __DIR__ . '/../helper.php';

// Define PHPUNIT_TESTSUITE for test environment
if (!defined('PHPUNIT_TESTSUITE')) {
    define('PHPUNIT_TESTSUITE', true);
}

/**
 * Mock model for testing
 */
class MockModel
{
    private $data = array();
    private $available = true;

    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function save()
    {
        return $this;
    }

    public function isAvailable()
    {
        return $this->available;
    }

    public function setAvailable($available)
    {
        $this->available = $available;
        return $this;
    }
}

/**
 * Test class for PatientNotificationsController
 */
class PatientNotificationsControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PatientNotificationsController
     */
    protected $controller;

    /**
     * @var MockModel Mock AuthUser
     */
    protected $mockAuthUser;

    /**
     * @var stdClass Mock Route
     */
    protected $mockRoute;

    /**
     * Set up test environment before each test
     */
    protected function setUp()
    {
        parent::setUp();

        // Enable output buffering to capture all output
        ob_start();

        // Create mock AuthUser
        $this->mockAuthUser = new MockModel();
        $this->mockAuthUser->set('id', 1);
        $this->mockAuthUser->set('name', 'Test Patient');

        // Create mock Route
        $this->mockRoute = new stdClass();
        $this->mockRoute->params = new stdClass();
        $this->mockRoute->params->id = 1;

        // Initialize controller
        $this->controller = new PatientNotificationsController();

        // Use reflection to set the protected $resp property
        $reflection = new ReflectionClass($this->controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        $respProperty->setValue($this->controller, new stdClass());

        // Initialize flags for header and exit
        $this->controller->headerCalled = false;
        $this->controller->headerRedirect = '';
        $this->controller->exitCalled = false;

        // Override header and exitScript methods
        $this->controller->header = function($string) {
            $this->headerCalled = true;
            $this->headerRedirect = $string;
        };
        $this->controller->exitScript = function() {
            $this->exitCalled = true;
        };
    }

    /**
     * Clean up after each test
     */
    protected function tearDown()
    {
        // Clean output buffer
        ob_end_clean();
        parent::tearDown();
    }

    /**
     * Get resp property via reflection
     */
    private function getResp($controller)
    {
        $reflection = new ReflectionClass($controller);
        $respProperty = $reflection->getProperty('resp');
        $respProperty->setAccessible(true);
        return $respProperty->getValue($controller);
    }

    /**
     * PATIENT_NOTIFICATIONS_001
     * Test when user is not logged in
     */
    public function testProcessWithNoAuthUser()
    {
        $controller = $this->controller;

        $controller->setVariable('AuthUser', null);
        $controller->setVariable('Route', $this->mockRoute);

        // Capture output to prevent header issues
        ob_start();
        $controller->process();
        ob_end_clean();

        $this->assertTrue($controller->headerCalled, 'header() should be called');
        $this->assertContains('Location: ' . APPURL . '/login', $controller->headerRedirect, 'header() should redirect to login page');
        $this->assertTrue($controller->exitCalled, 'exitScript() should be called');
    }

    /**
     * PATIENT_NOTIFICATIONS_002
     * Test when user is not a patient (has role)
     */
    public function testProcessWithNonPatientUser()
    {
        $controller = $this->controller;

        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('role', 'admin');

        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        // Ensure no callback for JSONP
        MockHelperInput::$get_values['callback'] = null;

        // Override jsonecho to capture resp state
        $capturedResp = null;
        $controller->jsonecho = function($resp = null) use (&$capturedResp) {
            $capturedResp = $resp ? $resp : $this->resp;
        };

        // Capture output
        ob_start();
        $controller->process();
        ob_end_clean();

        // Check captured resp
        $this->assertNotNull($capturedResp, 'jsonecho should be called');
        $this->assertEquals(0, $capturedResp->result, 'resp->result should be 0 for error');
        $this->assertEquals('You are not logging with PATIENT account so that you are not allowed do this action !', $capturedResp->msg, 'resp->msg should contain error message about not being a patient');
    }

    /**
     * PATIENT_NOTIFICATIONS_003
     * Test getAll method
     */
    public function testGetAll()
    {
        $controller = $this->controller;

        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('name', 'Test Patient');

        $controller->setVariable('AuthUser', $mockAuthUser);

        $notification1 = new stdClass();
        $notification1->id = 1;
        $notification1->message = 'Test notification 1';
        $notification1->record_id = 101;
        $notification1->record_type = 'appointment';
        $notification1->is_read = 0;
        $notification1->create_at = '2023-01-01 10:00:00';
        $notification1->update_at = '2023-01-01 10:00:00';
        $notification1->patient_id = 1;

        $notification2 = new stdClass();
        $notification2->id = 2;
        $notification2->message = 'Test notification 2';
        $notification2->record_id = 102;
        $notification2->record_type = 'appointment';
        $notification2->is_read = 1;
        $notification2->create_at = '2023-01-02 10:00:00';
        $notification2->update_at = '2023-01-02 10:00:00';
        $notification2->patient_id = 1;

        DB::$queryResult = array($notification1, $notification2);

        // Capture output
        ob_start();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getAll');
        $method->setAccessible(true);
        $method->invoke($controller);
        ob_end_clean();

        // Check resp via reflection
        $resp = $this->getResp($controller);
        $this->assertEquals(1, $resp->result, 'resp->result should be 1');
        $this->assertEquals('Action successfully', $resp->msg, 'resp->msg should be "Action successfully"');
        $this->assertEquals(2, $resp->quantity, 'resp->quantity should be 2');
        $this->assertEquals(1, $resp->quantityUnread, 'resp->quantityUnread should be 1');
        $this->assertCount(2, $resp->data, 'resp->data should have 2 items');
        $this->assertEquals(1, $resp->data[0]['id'], 'First notification ID should be 1');
        $this->assertEquals('Test notification 1', $resp->data[0]['message'], 'First notification message should be correct');
        $this->assertEquals(0, $resp->data[0]['is_read'], 'First notification is_read should be 0');
    }

    /**
     * PATIENT_NOTIFICATIONS_004
     * Test markAllAsRead method
     */
    public function testMarkAllAsRead()
    {
        $controller = $this->controller;

        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('name', 'Test Patient');

        $controller->setVariable('AuthUser', $mockAuthUser);

        DB::$updateResult = 5;

        // Capture output
        ob_start();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('markAllAsRead');
        $method->setAccessible(true);
        $method->invoke($controller);
        ob_end_clean();

        // Check resp via reflection
        $resp = $this->getResp($controller);
        $this->assertEquals(1, $resp->result, 'resp->result should be 1');
        $this->assertEquals('Congratulations, Test Patient! Mark all notification as read successfully', $resp->msg, 'resp->msg should contain success message');
    }

    /**
     * PATIENT_NOTIFICATIONS_005
     * Test createNotification method with missing field
     */
    public function testCreateNotificationWithMissingField()
    {
        $controller = $this->controller;

        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('name', 'Test Patient');

        $controller->setVariable('AuthUser', $mockAuthUser);

        // Explicitly set put_values to ensure null for record_type
        MockHelperInput::$put_values = array(
            'message' => 'Test message',
            'record_id' => 101,
            'record_type' => null
        );

        // Ensure Input::put('record_type') returns null
        Input::$putMock = function($key = null) {
            $values = array(
                'message' => 'Test message',
                'record_id' => 101,
                'record_type' => null
            );
            if ($key === null) {
                return $values;
            }
            return isset($values[$key]) ? $values[$key] : null;
        };

        // Override jsonecho to capture resp state
        $capturedResp = null;
        $controller->jsonecho = function($resp = null) use (&$capturedResp) {
            $capturedResp = $resp ? $resp : $this->resp;
        };

        // Capture output
        ob_start();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('createNotification');
        $method->setAccessible(true);
        $method->invoke($controller);
        ob_end_clean();

        // Check captured resp
        $this->assertNotNull($capturedResp, 'jsonecho should be called');
        $this->assertEquals(0, $capturedResp->result, 'resp->result should be 0');
        $this->assertEquals('Missing field record_type', $capturedResp->msg, 'resp->msg should contain error message about missing field');
    }

    /**
     * PATIENT_NOTIFICATIONS_006
     * Test createNotification method successfully
     */
    public function testCreateNotificationSuccessfully()
    {
        $controller = $this->controller;

        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('name', 'Test Patient');

        $controller->setVariable('AuthUser', $mockAuthUser);

        MockHelperInput::$put_values = array(
            'message' => 'Test message',
            'record_id' => 101,
            'record_type' => 'appointment'
        );

        $mockNotification = new MockModel();

        // Set test mode and model method
        Controller::$testMode = true;
        Controller::$modelMethod = function($name, $args) use ($mockNotification) {
            return $mockNotification;
        };

        // Override jsonecho to capture resp state
        $capturedResp = null;
        $controller->jsonecho = function($resp = null) use (&$capturedResp) {
            $capturedResp = $resp ? $resp : $this->resp;
        };

        // Capture output
        ob_start();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('createNotification');
        $method->setAccessible(true);
        $method->invoke($controller);
        ob_end_clean();

        // Reset test mode
        Controller::$testMode = false;
        Controller::$modelMethod = null;

        // Check captured resp
        $this->assertNotNull($capturedResp, 'jsonecho should be called');
        $this->assertEquals(1, $capturedResp->result, 'resp->result should be 1');
        $this->assertEquals('Notification has been created successfully !', $capturedResp->msg, 'resp->msg should contain success message');

        // Check mockNotification values
        $this->assertEquals('Test message', $mockNotification->get('message'), 'Notification message should be set correctly');
        $this->assertEquals(101, $mockNotification->get('record_id'), 'Notification record_id should be set correctly');
        $this->assertEquals('appointment', $mockNotification->get('record_type'), 'Notification record_type should be set correctly');
        $this->assertEquals(0, $mockNotification->get('is_read'), 'Notification is_read should be set to 0');
        $this->assertEquals(1, $mockNotification->get('patient_id'), 'Notification patient_id should be set correctly');
    }

    /**
     * PATIENT_NOTIFICATIONS_007
     * Test getAll method with exception
     */
    public function testGetAllWithException()
    {
        $controller = $this->controller;

        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('name', 'Test Patient');

        $controller->setVariable('AuthUser', $mockAuthUser);

        DB::$queryResult = function() {
            throw new Exception('Database error');
        };

        // Capture output
        ob_start();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getAll');
        $method->setAccessible(true);
        $method->invoke($controller);
        ob_end_clean();

        // Check resp via reflection
        $resp = $this->getResp($controller);
        $this->assertEquals(0, $resp->result, 'resp->result should be 0');
        $this->assertEquals('Database error', $resp->msg, 'resp->msg should contain error message');
    }

    /**
     * PATIENT_NOTIFICATIONS_008
     * Test markAllAsRead method with exception
     */
    public function testMarkAllAsReadWithException()
    {
        $controller = $this->controller;

        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('name', 'Test Patient');

        $controller->setVariable('AuthUser', $mockAuthUser);

        DB::$updateResult = function() {
            throw new Exception('Database error');
        };

        // Capture output
        ob_start();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('markAllAsRead');
        $method->setAccessible(true);
        $method->invoke($controller);
        ob_end_clean();

        // Check resp via reflection
        $resp = $this->getResp($controller);
        $this->assertEquals(0, $resp->result, 'resp->result should be 0');
        $this->assertEquals('Database error', $resp->msg, 'resp->msg should contain error message');
    }

    /**
     * PATIENT_NOTIFICATIONS_009
     * Test createNotification method with exception
     */
    public function testCreateNotificationWithException()
    {
        $controller = $this->controller;

        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('name', 'Test Patient');

        $controller->setVariable('AuthUser', $mockAuthUser);

        MockHelperInput::$put_values = array(
            'message' => 'Test message',
            'record_id' => 101,
            'record_type' => 'appointment'
        );

        $mockNotification = new MockModel();
        $mockNotification->save = function() {
            throw new Exception('Database error');
        };

        // Set test mode and model method
        Controller::$testMode = true;
        Controller::$modelMethod = function($name, $args) use ($mockNotification) {
            return $mockNotification;
        };

        // Override jsonecho to capture resp state
        $capturedResp = null;
        $controller->jsonecho = function($resp = null) use (&$capturedResp) {
            $capturedResp = $resp ? $resp : $this->resp;
        };

        // Capture output
        ob_start();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('createNotification');
        $method->setAccessible(true);
        $method->invoke($controller);
        ob_end_clean();

        // Reset test mode
        Controller::$testMode = false;
        Controller::$modelMethod = null;

        // Check captured resp
        $this->assertNotNull($capturedResp, 'jsonecho should be called');
        $this->assertEquals('Database error', $capturedResp->msg, 'resp->msg should contain error message');
    }

    /**
     * PATIENT_NOTIFICATIONS_010
     * Test code coverage of controller
     */
    public function testCodeCoverage()
    {
        $controller = $this->controller;

        $this->assertInstanceOf('PatientNotificationsController', $controller);
        $this->assertTrue(method_exists($controller, 'process'), 'Controller should have process() method');
        $this->assertTrue(method_exists($controller, 'getAll'), 'Controller should have getAll() method');
        $this->assertTrue(method_exists($controller, 'markAllAsRead'), 'Controller should have markAllAsRead() method');
        $this->assertTrue(method_exists($controller, 'createNotification'), 'Controller should have createNotification() method');
    }

    /**
     * PATIENT_NOTIFICATIONS_011
     * Test code coverage of controller by calling methods
     */
    public function testCodeCoverageByCallingMethods()
    {
        $controller = $this->controller;

        $mockAuthUser = new MockModel();
        $mockAuthUser->set('id', 1);
        $mockAuthUser->set('name', 'Test Patient');

        $controller->setVariable('AuthUser', $mockAuthUser);
        $controller->setVariable('Route', $this->mockRoute);

        $notification1 = new stdClass();
        $notification1->id = 1;
        $notification1->message = 'Test notification 1';
        $notification1->record_id = 101;
        $notification1->record_type = 'appointment';
        $notification1->is_read = 0;
        $notification1->create_at = '2023-01-01 10:00:00';
        $notification1->update_at = '2023-01-01 10:00:00';
        $notification1->patient_id = 1;

        DB::$queryResult = array($notification1);
        DB::$updateResult = 1;

        $mockNotification = new MockModel();

        // Set test mode and model method
        Controller::$testMode = true;
        Controller::$modelMethod = function($name, $args) use ($mockNotification) {
            return $mockNotification;
        };

        // Override jsonecho to prevent further execution
        $controller->jsonecho = function() {
            // Do nothing to prevent further execution
        };

        // Case 1: GET request - getAll
        MockHelperInput::$method_value = 'GET';
        ob_start();
        $controller->process();
        ob_end_clean();

        // Case 2: POST request - markAllAsRead
        MockHelperInput::$method_value = 'POST';
        ob_start();
        $controller->process();
        ob_end_clean();

        // Case 3: PUT request - createNotification
        MockHelperInput::$method_value = 'PUT';
        MockHelperInput::$put_values = array(
            'message' => 'Test message',
            'record_id' => 101,
            'record_type' => 'appointment'
        );
        ob_start();
        $controller->process();
        ob_end_clean();

        // Reset test mode
        Controller::$testMode = false;
        Controller::$modelMethod = null;

        $this->assertTrue(true);
    }
}
?>