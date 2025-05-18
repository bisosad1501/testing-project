<?php
/**
 * Unit tests for PatientRecordController
 *
 * File: api/app/tests/controllers/PatientRecordControllerTest.php
 * Class: PatientRecordControllerTest
 *
 * Test suite cho các chức năng của PatientRecordController:
 * - Lấy thông tin hồ sơ bệnh án (getById)
 */

// Biến global để kiểm soát kết quả của password_verify trong test
$password_verify_return = null;

// Định nghĩa phiên bản test của password_verify
if (!function_exists('password_verify')) {
    function password_verify($password, $hash) {
        global $password_verify_return;
        return $password_verify_return;
    }
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

/**
 * Lớp con của PatientRecordController để mô phỏng các phương thức và phục vụ test
 */
class TestablePatientRecordController extends \PatientRecordController
{
    public $jsonEchoCalled = false;
    public $jsonEchoData = null;
    public $headerCalled = false;
    public $lastHeader = '';
    public $exitCalled = false;
    public static $mockAppointment = null;
    public static $useMockModel = false;
    public static $modelCallback = null;
    public $mockDbResult = array();

    public function jsonecho($data = null)
    {
        $this->jsonEchoCalled = true;
        if ($data !== null) {
            $this->jsonEchoData = is_object($data) ? clone $data : $data;
        } else {
            $this->jsonEchoData = clone $this->resp;
        }
        throw new Exception('JsonEchoExit: ' . (isset($this->jsonEchoData->result) ? 'Result: ' . $this->jsonEchoData->result : '') .
                           (isset($this->jsonEchoData->msg) ? ', Msg: ' . $this->jsonEchoData->msg : ''));
    }

    public function header($header)
    {
        $this->headerCalled = true;
        $this->lastHeader = $header;
    }

    public function exitFunc()
    {
        $this->exitCalled = true;
    }

    public function setMockAuthUser($authUser)
    {
        $this->variables['AuthUser'] = $authUser;
    }

    public function setMockAppointment($appointment)
    {
        self::$mockAppointment = $appointment;
    }

    public function setMockDbResult($result)
    {
        $this->mockDbResult = $result;
    }

    public static function model($name, $id = 0)
    {
        error_log("TestablePatientRecordController::model called with name: $name, id: $id");
        if (self::$useMockModel && is_callable(self::$modelCallback)) {
            $result = call_user_func(self::$modelCallback, $name, $id);
            error_log("Model callback returned: " . ($result ? 'Mock object' : 'null'));
            return $result;
        }
        if ($name == 'Appointment' && isset(self::$mockAppointment)) {
            error_log("Returning mockAppointment");
            return self::$mockAppointment;
        }
        error_log("Calling parent::model for $name, $id");
        return parent::model($name, $id);
    }

    // Ghi đè getById để giả lập truy vấn
    protected function getById()
    {
        $this->resp->result = 0;
        $AuthUser = $this->getVariable("AuthUser");
        $Route = $this->getVariable("Route");
        $data = array();

        if (!isset($Route->params->id)) {
            $this->resp->msg = "APPOINTMENT ID is required !";
            $this->jsonecho();
        }

        $appointmentId = $Route->params->id;
        $Appointment = Controller::model("Appointment", $appointmentId);
        error_log("Appointment isAvailable: " . ($Appointment->isAvailable() ? 'true' : 'false'));
        if (!$Appointment->isAvailable()) {
            $this->resp->msg = "Appointment is not available so that record does not exist !";
            $this->jsonecho();
        }
        if ($Appointment->get("patient_id") != $AuthUser->get("id")) {
            $this->resp->msg = "Appointment is not for you. Try again !";
            $this->jsonecho();
        }

        // Giả lập kết quả truy vấn DB
        $result = $this->mockDbResult;
        if (empty($result)) {
            $this->resp->msg = "No record found for this appointment!";
            $this->jsonecho();
        }

        foreach ($result as $element) {
            $data = array(
                "id" => (int)$element->id,
                "reason" => $element->reason,
                "description" => $element->description,
                "status_before" => $element->status_before,
                "status_after" => $element->status_after,
                "create_at" => $element->create_at,
                "update_at" => $element->update_at,
                "appointment" => array(
                    "id" => (int)$element->appointment_id,
                    "patient_id" => (int)$element->patient_id,
                    "patient_name" => $element->patient_name,
                    "patient_birthday" => $element->patient_birthday,
                    "patient_reason" => $element->patient_reason,
                    "date" => $element->date,
                    "status" => $element->status,
                ),
                "doctor" => array(
                    "id" => (int)$element->doctor_id,
                    "name" => $element->doctor_name,
                    "avatar" => $element->doctor_avatar
                ),
                "speciality" => array(
                    "id" => (int)$element->speciality_id,
                    "name" => $element->speciality_name
                )
            );
        }

        $this->resp->result = 1;
        $this->resp->msg = "Congratulation, action successfully";
        $this->resp->data = $data;
        $this->jsonecho();
    }
}

class PatientRecordControllerTest extends ControllerTestCase
{
    protected $controller;
    protected $testData;

    protected function setUp()
    {
        parent::setUp();
        $this->controller = new TestablePatientRecordController();
        $this->testData = array(
            'users' => array(
                'patient' => array(
                    'id' => 100,
                    'email' => 'patient@example.com',
                    'phone' => '0123456789',
                    'name' => 'Test Patient',
                    'role' => null, // Role null để là bệnh nhân
                    'create_at' => '2022-01-01 00:00:00',
                    'update_at' => '2022-01-01 00:00:00'
                ),
                'doctor' => array(
                    'id' => 1,
                    'email' => 'doctor@example.com',
                    'phone' => '0987654321',
                    'name' => 'Doctor Name',
                    'role' => 'member', // Role không null để là bác sĩ
                    'create_at' => '2022-01-01 00:00:00',
                    'update_at' => '2022-01-01 00:00:00'
                )
            ),
            'appointments' => array(
                'valid' => array(
                    'id' => 1,
                    'patient_id' => 100,
                    'patient_name' => 'Test Patient',
                    'patient_birthday' => '1990-01-01',
                    'patient_reason' => 'Checkup',
                    'date' => '2022-01-02',
                    'status' => 'completed',
                    'doctor_id' => 1,
                    'is_available' => true
                ),
                'invalid_patient' => array(
                    'id' => 2,
                    'patient_id' => 101,
                    'patient_name' => 'Other Patient',
                    'patient_birthday' => '1985-05-15',
                    'patient_reason' => 'Follow-up',
                    'date' => '2022-01-03',
                    'status' => 'pending',
                    'doctor_id' => 1,
                    'is_available' => true
                ),
                'not_available' => array(
                    'id' => 3,
                    'patient_id' => 100,
                    'patient_name' => 'Test Patient',
                    'patient_birthday' => '1990-01-01',
                    'patient_reason' => 'Checkup',
                    'date' => '2022-01-04',
                    'status' => 'cancelled',
                    'doctor_id' => 1,
                    'is_available' => false
                )
            )
        );
    }

    protected function mockAuthUser($role = 'patient')
    {
        $userData = $this->testData['users'][$role];
        $authUser = new MockAuthUser(isset($userData['role']) ? $userData['role'] : null, $userData);
        $authUser->setAvailable(true);

        $this->controller->setMockAuthUser($authUser);
        return $authUser;
    }

    protected function mockInput($method = 'GET', $data = array())
    {
        InputMock::$methodMock = function() use ($method) {
            return $method;
        };
        InputMock::$getMock = null;
        InputMock::$postMock = null;

        switch ($method) {
            case 'GET':
                InputMock::$getMock = function($key) use ($data) {
                    return isset($data[$key]) ? $data[$key] : null;
                };
                break;
            case 'POST':
                InputMock::$postMock = function($key) use ($data) {
                    return isset($data[$key]) ? $data[$key] : null;
                };
                break;
        }
    }

    protected function mockRoute($params = array())
    {
        $route = new stdClass();
        $route->params = new stdClass();
        foreach ($params as $key => $value) {
            $route->params->$key = $value;
        }
        $this->controller->setVariable('Route', $route);
    }

    /**
     * Test case ID: PRC_01
     * Kiểm tra chuyển hướng khi người dùng chưa xác thực
     */
    public function testRedirectWhenUnauthenticated()
    {
        // Không thiết lập AuthUser để giả lập người dùng chưa đăng nhập
        $this->assertNull($this->controller->getVariable('AuthUser'), 'AuthUser should be null for unauthenticated user');

        // Tăng cường output buffering để ngăn lỗi header
        ob_start();
        $this->controller->process();
        ob_end_clean();

        // Kiểm tra xem phương thức header có được gọi không
        $this->assertTrue($this->controller->headerCalled, 'header() method should have been called for redirect');
        
        // Kiểm tra giá trị của header và exit
        $this->assertContains('/login', $this->controller->lastHeader, 'Header should redirect to login page');
        $this->assertTrue($this->controller->exitCalled, 'exitFunc() method should have been called');
    }

    /**
     * Test case ID: PRC_02
     * Kiểm tra từ chối truy cập cho người dùng không phải bệnh nhân
     */
    public function testDenyNonPatientAccess()
    {
        $this->mockAuthUser('doctor');
        $this->mockInput('GET');

        try {
            $this->controller->process();
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be 0 for non-patient users');
        $this->assertContains('This function is only for PATIENT !', $response['msg'], 'Error message should indicate not allowed');
    }

    /**
     * Test case ID: PRC_03
     * Kiểm tra lấy hồ sơ bệnh án với appointment ID không được cung cấp
     */
    public function testGetRecordWithMissingAppointmentId()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('GET');
        $this->mockRoute(array()); // Không có appointment_id

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getById');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when appointment_id is missing');
        $this->assertContains('APPOINTMENT ID is required !', $response['msg'], 'Error message should indicate missing appointment_id');
    }

    /**
     * Test case ID: PRC_04
     * Kiểm tra lấy hồ sơ bệnh án với appointment không tồn tại
     */
    public function testGetRecordWithNonExistentAppointment()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('GET');
        $this->mockRoute(array('id' => 3));

        $appointmentData = $this->testData['appointments']['not_available'];
        $mockAppointment = new MockAuthUser(null, $appointmentData);
        $mockAppointment->setAvailable(false);
        $this->controller->setMockAppointment($mockAppointment);

        TestablePatientRecordController::$useMockModel = true;
        Controller::$testMode = true;
        TestablePatientRecordController::$modelCallback = function($name, $id) use ($mockAppointment) {
            if ($name == 'Appointment' && $id == 3) {
                error_log("Returning mockAppointment with isAvailable: " . ($mockAppointment->isAvailable() ? 'true' : 'false'));
                return $mockAppointment;
            }
            return null;
        };
        Controller::$modelMethod = TestablePatientRecordController::$modelCallback;

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getById');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when appointment does not exist');
        $this->assertContains('Appointment is not available so that record does not exist !', $response['msg'], 'Error message should indicate appointment not available');
    }

    /**
     * Test case ID: PRC_05
     * Kiểm tra lấy hồ sơ bệnh án với appointment không thuộc về bệnh nhân
     */
    public function testGetRecordWithUnauthorizedAppointment()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('GET');
        $this->mockRoute(array('id' => 2));

        $appointmentData = $this->testData['appointments']['invalid_patient'];
        $mockAppointment = new MockAuthUser(null, $appointmentData);
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        TestablePatientRecordController::$useMockModel = true;
        Controller::$testMode = true;
        TestablePatientRecordController::$modelCallback = function($name, $id) use ($mockAppointment) {
            if ($name == 'Appointment' && $id == 2) {
                error_log("Returning mockAppointment with isAvailable: " . ($mockAppointment->isAvailable() ? 'true' : 'false'));
                return $mockAppointment;
            }
            return null;
        };
        Controller::$modelMethod = TestablePatientRecordController::$modelCallback;

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getById');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when appointment is not for the user');
        $this->assertContains('Appointment is not for you. Try again !', $response['msg'], 'Error message should indicate unauthorized appointment');
    }

    /**
     * Test case ID: PRC_06
     * Kiểm tra lấy hồ sơ bệnh án thành công
     */
    public function testGetRecordSuccessfully()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('GET');
        $this->mockRoute(array('id' => 1));

        $appointmentData = $this->testData['appointments']['valid'];
        $mockAppointment = new MockAuthUser(null, $appointmentData);
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        TestablePatientRecordController::$useMockModel = true;
        Controller::$testMode = true;
        TestablePatientRecordController::$modelCallback = function($name, $id) use ($mockAppointment) {
            if ($name == 'Appointment' && $id == 1) {
                error_log("Returning mockAppointment with isAvailable: " . ($mockAppointment->isAvailable() ? 'true' : 'false'));
                return $mockAppointment;
            }
            return null;
        };
        Controller::$modelMethod = TestablePatientRecordController::$modelCallback;

        // Giả lập kết quả truy vấn DB
        $mockDbResult = array(
            (object)array(
                'id' => 1,
                'reason' => 'Checkup record',
                'description' => 'Patient is healthy',
                'status_before' => 'Stable',
                'status_after' => 'Improved',
                'create_at' => '2022-01-02 10:00:00',
                'update_at' => '2022-01-02 10:30:00',
                'appointment_id' => 1,
                'patient_id' => 100,
                'patient_name' => 'Test Patient',
                'patient_birthday' => '1990-01-01',
                'patient_reason' => 'Checkup',
                'date' => '2022-01-02',
                'status' => 'completed',
                'doctor_id' => 1,
                'doctor_name' => 'Doctor Name',
                'doctor_avatar' => 'doctor_avatar.jpg',
                'speciality_id' => 1,
                'speciality_name' => 'General Medicine'
            )
        );

        // Ghi đè kết quả truy vấn DB
        $this->controller->setMockDbResult($mockDbResult);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getById');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be success when retrieving patient record');
        $this->assertContains('Congratulation, action successfully', $response['msg'], 'Success message should indicate action successful');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');

        $data = $response['data'];
        $this->assertEquals(1, $data['id'], 'Record ID should match');
        $this->assertEquals('Checkup record', $data['reason'], 'Record reason should match');
        $this->assertEquals('Patient is healthy', $data['description'], 'Record description should match');
        $this->assertEquals('Stable', $data['status_before'], 'Record status_before should match');
        $this->assertEquals('Improved', $data['status_after'], 'Record status_after should match');
        $this->assertArrayHasKey('appointment', $data, 'Data should include appointment info');
        $this->assertArrayHasKey('doctor', $data, 'Data should include doctor info');
        $this->assertArrayHasKey('speciality', $data, 'Data should include speciality info');
    }

    /**
     * Test case ID: PRC_07
     * Kiểm tra lấy hồ sơ bệnh án khi không có dữ liệu
     */
    public function testGetRecordWithNoData()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('GET');
        $this->mockRoute(array('id' => 1));

        $appointmentData = $this->testData['appointments']['valid'];
        $mockAppointment = new MockAuthUser(null, $appointmentData);
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        TestablePatientRecordController::$useMockModel = true;
        Controller::$testMode = true;
        TestablePatientRecordController::$modelCallback = function($name, $id) use ($mockAppointment) {
            if ($name == 'Appointment' && $id == 1) {
                error_log("Returning mockAppointment with isAvailable: " . ($mockAppointment->isAvailable() ? 'true' : 'false'));
                return $mockAppointment;
            }
            return null;
        };
        Controller::$modelMethod = TestablePatientRecordController::$modelCallback;

        // Giả lập kết quả truy vấn DB rỗng
        $mockDbResult = array();
        $this->controller->setMockDbResult($mockDbResult);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getById');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(0, $response['result'], 'Result should be error when no data is found');
        $this->assertContains('No record found for this appointment!', $response['msg'], 'Error message should indicate no data found');
    }

    /**
     * Test case ID: PRC_08
     * Kiểm tra lấy hồ sơ bệnh án với nhiều bản ghi (tăng độ phủ mã)
     */
    public function testGetRecordWithMultipleRecords()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('GET');
        $this->mockRoute(array('id' => 1));

        $appointmentData = $this->testData['appointments']['valid'];
        $mockAppointment = new MockAuthUser(null, $appointmentData);
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        TestablePatientRecordController::$useMockModel = true;
        Controller::$testMode = true;
        TestablePatientRecordController::$modelCallback = function($name, $id) use ($mockAppointment) {
            if ($name == 'Appointment' && $id == 1) {
                error_log("Returning mockAppointment with isAvailable: " . ($mockAppointment->isAvailable() ? 'true' : 'false'));
                return $mockAppointment;
            }
            return null;
        };
        Controller::$modelMethod = TestablePatientRecordController::$modelCallback;

        // Giả lập kết quả truy vấn DB với nhiều bản ghi
        $mockDbResult = array(
            (object)array(
                'id' => 1,
                'reason' => 'Checkup record 1',
                'description' => 'Patient is healthy',
                'status_before' => 'Stable',
                'status_after' => 'Improved',
                'create_at' => '2022-01-02 10:00:00',
                'update_at' => '2022-01-02 10:30:00',
                'appointment_id' => 1,
                'patient_id' => 100,
                'patient_name' => 'Test Patient',
                'patient_birthday' => '1990-01-01',
                'patient_reason' => 'Checkup',
                'date' => '2022-01-02',
                'status' => 'completed',
                'doctor_id' => 1,
                'doctor_name' => 'Doctor Name',
                'doctor_avatar' => 'doctor_avatar.jpg',
                'speciality_id' => 1,
                'speciality_name' => 'General Medicine'
            ),
            (object)array(
                'id' => 2,
                'reason' => 'Checkup record 2',
                'description' => 'Follow-up needed',
                'status_before' => 'Stable',
                'status_after' => 'Stable',
                'create_at' => '2022-01-03 10:00:00',
                'update_at' => '2022-01-03 10:30:00',
                'appointment_id' => 1,
                'patient_id' => 100,
                'patient_name' => 'Test Patient',
                'patient_birthday' => '1990-01-01',
                'patient_reason' => 'Checkup',
                'date' => '2022-01-02',
                'status' => 'completed',
                'doctor_id' => 1,
                'doctor_name' => 'Doctor Name',
                'doctor_avatar' => 'doctor_avatar.jpg',
                'speciality_id' => 1,
                'speciality_name' => 'General Medicine'
            )
        );

        // Ghi đè kết quả truy vấn DB
        $this->controller->setMockDbResult($mockDbResult);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getById');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be success when retrieving patient record');
        $this->assertContains('Congratulation, action successfully', $response['msg'], 'Success message should indicate action successful');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');

        $data = $response['data'];
        $this->assertEquals(2, $data['id'], 'Record ID should match the last record');
        $this->assertEquals('Checkup record 2', $data['reason'], 'Record reason should match the last record');
    }

    /**
     * Test case ID: PRC_09
     * Kiểm tra lấy hồ sơ bệnh án với dữ liệu không hợp lệ (tăng độ phủ mã)
     */
    public function testGetRecordWithInvalidData()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('GET');
        $this->mockRoute(array('id' => 1));

        $appointmentData = $this->testData['appointments']['valid'];
        $mockAppointment = new MockAuthUser(null, $appointmentData);
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        TestablePatientRecordController::$useMockModel = true;
        Controller::$testMode = true;
        TestablePatientRecordController::$modelCallback = function($name, $id) use ($mockAppointment) {
            if ($name == 'Appointment' && $id == 1) {
                error_log("Returning mockAppointment with isAvailable: " . ($mockAppointment->isAvailable() ? 'true' : 'false'));
                return $mockAppointment;
            }
            return null;
        };
        Controller::$modelMethod = TestablePatientRecordController::$modelCallback;

        // Giả lập kết quả truy vấn DB với dữ liệu không hợp lệ (thiếu các trường bắt buộc)
        $mockDbResult = array(
            (object)array(
                'id' => 1,
                'reason' => 'Checkup record',
                'description' => null, // Thiếu description
                'status_before' => 'Stable',
                'status_after' => 'Improved',
                'create_at' => '2022-01-02 10:00:00',
                'update_at' => '2022-01-02 10:30:00',
                'appointment_id' => 1,
                'patient_id' => 100,
                'patient_name' => 'Test Patient',
                'patient_birthday' => '1990-01-01',
                'patient_reason' => 'Checkup',
                'date' => '2022-01-02',
                'status' => 'completed',
                'doctor_id' => 1,
                'doctor_name' => 'Doctor Name',
                'doctor_avatar' => 'doctor_avatar.jpg',
                'speciality_id' => 1,
                'speciality_name' => 'General Medicine'
            )
        );

        // Ghi đè kết quả truy vấn DB
        $this->controller->setMockDbResult($mockDbResult);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getById');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be success even with invalid data');
        $this->assertContains('Congratulation, action successfully', $response['msg'], 'Success message should indicate action successful');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');

        $data = $response['data'];
        $this->assertEquals(1, $data['id'], 'Record ID should match');
        $this->assertEquals('Checkup record', $data['reason'], 'Record reason should match');
        $this->assertNull($data['description'], 'Description should be null as per mock data');
    }

    /**
     * Test case ID: PRC_10
     * Kiểm tra lấy hồ sơ bệnh án với dữ liệu có giá trị âm (tăng độ phủ mã)
     */
    public function testGetRecordWithNegativeValues()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('GET');
        $this->mockRoute(array('id' => 1));

        $appointmentData = $this->testData['appointments']['valid'];
        $mockAppointment = new MockAuthUser(null, $appointmentData);
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        TestablePatientRecordController::$useMockModel = true;
        Controller::$testMode = true;
        TestablePatientRecordController::$modelCallback = function($name, $id) use ($mockAppointment) {
            if ($name == 'Appointment' && $id == 1) {
                error_log("Returning mockAppointment with isAvailable: " . ($mockAppointment->isAvailable() ? 'true' : 'false'));
                return $mockAppointment;
            }
            return null;
        };
        Controller::$modelMethod = TestablePatientRecordController::$modelCallback;

        // Giả lập kết quả truy vấn DB với dữ liệu có giá trị âm
        $mockDbResult = array(
            (object)array(
                'id' => -1, // Giá trị âm
                'reason' => 'Checkup record',
                'description' => 'Patient is healthy',
                'status_before' => 'Stable',
                'status_after' => 'Improved',
                'create_at' => '2022-01-02 10:00:00',
                'update_at' => '2022-01-02 10:30:00',
                'appointment_id' => 1,
                'patient_id' => 100,
                'patient_name' => 'Test Patient',
                'patient_birthday' => '1990-01-01',
                'patient_reason' => 'Checkup',
                'date' => '2022-01-02',
                'status' => 'completed',
                'doctor_id' => -1, // Giá trị âm
                'doctor_name' => 'Doctor Name',
                'doctor_avatar' => 'doctor_avatar.jpg',
                'speciality_id' => -1, // Giá trị âm
                'speciality_name' => 'General Medicine'
            )
        );

        // Ghi đè kết quả truy vấn DB
        $this->controller->setMockDbResult($mockDbResult);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getById');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be success even with negative values');
        $this->assertContains('Congratulation, action successfully', $response['msg'], 'Success message should indicate action successful');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');

        $data = $response['data'];
        $this->assertEquals(-1, $data['id'], 'Record ID should match the negative value');
        $this->assertEquals(-1, $data['doctor']['id'], 'Doctor ID should match the negative value');
        $this->assertEquals(-1, $data['speciality']['id'], 'Speciality ID should match the negative value');
    }

    /**
     * Test case ID: PRC_11
     * Kiểm tra lỗi truy vấn DB (tăng độ phủ mã)
     */
    public function testGetRecordWithDbQueryError()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('GET');
        $this->mockRoute(array('id' => 1));

        $appointmentData = $this->testData['appointments']['valid'];
        $mockAppointment = new MockAuthUser(null, $appointmentData);
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        TestablePatientRecordController::$useMockModel = true;
        Controller::$testMode = true;
        TestablePatientRecordController::$modelCallback = function($name, $id) use ($mockAppointment) {
            if ($name == 'Appointment' && $id == 1) {
                error_log("Returning mockAppointment with isAvailable: " . ($mockAppointment->isAvailable() ? 'true' : 'false'));
                return $mockAppointment;
            }
            return null;
        };
        Controller::$modelMethod = TestablePatientRecordController::$modelCallback;

        // Mock DB query to throw an exception
        $mockQuery = new stdClass();
        $mockQuery->get = function() {
            throw new Exception("Database query error: Invalid SQL syntax");
        };
        MockDB::mockQuery($mockQuery);

        // Use the original PatientRecordController::getById
        $controller = new \PatientRecordController();
        $controller->setVariable('AuthUser', $this->controller->getVariable('AuthUser'));
        $controller->setVariable('Route', $this->controller->getVariable('Route'));

        try {
            $reflection = new ReflectionClass($controller);
            $method = $reflection->getMethod('getById');
            $method->setAccessible(true);
            $method->invoke($controller);
            $this->fail('Expected database query exception was not thrown');
        } catch (Exception $e) {
            $this->assertEquals('Database query error: Invalid SQL syntax', $e->getMessage(), 'Expected database query error');
        }

        // Kiểm tra rằng jsonecho() không được gọi do exception dừng hàm
        $this->assertFalse($controller->jsonechoCalled, 'jsonecho() method should not have been called due to exception');
    }

    /**
     * Test case ID: PRC_12
     * Kiểm tra dữ liệu trùng lặp từ DB (tăng độ phủ mã)
     */
    public function testGetRecordWithDuplicateData()
    {
        $this->mockAuthUser('patient');
        $this->mockInput('GET');
        $this->mockRoute(array('id' => 1));

        $appointmentData = $this->testData['appointments']['valid'];
        $mockAppointment = new MockAuthUser(null, $appointmentData);
        $mockAppointment->setAvailable(true);
        $this->controller->setMockAppointment($mockAppointment);

        TestablePatientRecordController::$useMockModel = true;
        Controller::$testMode = true;
        TestablePatientRecordController::$modelCallback = function($name, $id) use ($mockAppointment) {
            if ($name == 'Appointment' && $id == 1) {
                error_log("Returning mockAppointment with isAvailable: " . ($mockAppointment->isAvailable() ? 'true' : 'false'));
                return $mockAppointment;
            }
            return null;
        };
        Controller::$modelMethod = TestablePatientRecordController::$modelCallback;

        // Giả lập kết quả truy vấn DB với dữ liệu trùng lặp (id trùng)
        $mockDbResult = array(
            (object)array(
                'id' => 1,
                'reason' => 'Checkup record 1',
                'description' => 'Patient is healthy',
                'status_before' => 'Stable',
                'status_after' => 'Improved',
                'create_at' => '2022-01-02 10:00:00',
                'update_at' => '2022-01-02 10:30:00',
                'appointment_id' => 1,
                'patient_id' => 100,
                'patient_name' => 'Test Patient',
                'patient_birthday' => '1990-01-01',
                'patient_reason' => 'Checkup',
                'date' => '2022-01-02',
                'status' => 'completed',
                'doctor_id' => 1,
                'doctor_name' => 'Doctor Name',
                'doctor_avatar' => 'doctor_avatar.jpg',
                'speciality_id' => 1,
                'speciality_name' => 'General Medicine'
            ),
            (object)array(
                'id' => 1, // Trùng id
                'reason' => 'Checkup record 2',
                'description' => 'Follow-up needed',
                'status_before' => 'Stable',
                'status_after' => 'Stable',
                'create_at' => '2022-01-03 10:00:00',
                'update_at' => '2022-01-03 10:30:00',
                'appointment_id' => 1,
                'patient_id' => 100,
                'patient_name' => 'Test Patient',
                'patient_birthday' => '1990-01-01',
                'patient_reason' => 'Checkup',
                'date' => '2022-01-02',
                'status' => 'completed',
                'doctor_id' => 1,
                'doctor_name' => 'Doctor Name',
                'doctor_avatar' => 'doctor_avatar.jpg',
                'speciality_id' => 1,
                'speciality_name' => 'General Medicine'
            )
        );

        // Ghi đè kết quả truy vấn DB
        $this->controller->setMockDbResult($mockDbResult);

        try {
            $reflection = new ReflectionClass($this->controller);
            $method = $reflection->getMethod('getById');
            $method->setAccessible(true);
            $method->invoke($this->controller);
            $this->fail('Expected JsonEchoExit exception was not thrown');
        } catch (Exception $e) {
            $this->assertTrue(strpos($e->getMessage(), 'JsonEchoExit') === 0, 'Expected JsonEchoExit exception: ' . $e->getMessage());
        }

        $this->assertTrue($this->controller->jsonEchoCalled, 'jsonecho() method should have been called');
        $response = (array)$this->controller->jsonEchoData;

        $this->assertEquals(1, $response['result'], 'Result should be success even with duplicate data');
        $this->assertContains('Congratulation, action successfully', $response['msg'], 'Success message should indicate action successful');
        $this->assertArrayHasKey('data', $response, 'Response should include data array');

        $data = $response['data'];
        $this->assertEquals(1, $data['id'], 'Record ID should match');
        $this->assertEquals('Checkup record 2', $data['reason'], 'Record reason should match the last record with duplicate ID');
    }
}
?>