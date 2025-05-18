<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// 1. Cấu hình error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Định nghĩa đường dẫn cơ bản
define('APP_PATH', realpath(__DIR__ . '/../'));
define('APPPATH', APP_PATH);
define('PHPUNIT_TESTSUITE', true);

// 3. Autoload các classes (từ Composer)
require APP_PATH . '/vendor/autoload.php';

// 4. Load config database test
require APP_PATH . '/config/db.test.config.php';

// 5. Định nghĩa các hằng số cần thiết
if (!defined('APPURL')) define('APPURL', 'http://localhost/api');
    if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
    if (!defined('BASEPATH')) define('BASEPATH', dirname(dirname(__DIR__)));
    if (!defined('TABLE_PREFIX')) define('TABLE_PREFIX', 'tn_');
    if (!defined('TABLE_DOCTORS')) define('TABLE_DOCTORS', 'doctors');
    if (!defined('TABLE_PATIENTS')) define('TABLE_PATIENTS', 'patients');
    if (!defined('TABLE_APPOINTMENTS')) define('TABLE_APPOINTMENTS', 'appointments');
    if (!defined('TABLE_SPECIALITIES')) define('TABLE_SPECIALITIES', 'specialities');
    if (!defined('TABLE_ROOMS')) define('TABLE_ROOMS', 'rooms');
    if (!defined('TABLE_NOTIFICATIONS')) define('TABLE_NOTIFICATIONS', 'notifications');
    if (!defined('TABLE_SERVICES')) define('TABLE_SERVICES', 'services');
    if (!defined('TABLE_DOCTOR_AND_SERVICE')) define('TABLE_DOCTOR_AND_SERVICE', 'doctor_and_service');

// 6. Load các class core
require_once APP_PATH . '/core/DataEntry.php';
require_once APP_PATH . '/core/DataList.php';
require_once APP_PATH . '/core/Controller.php';

// 7. Load các model và controller cần thiết cho test
// Models
require_once APP_PATH . '/models/AppointmentModel.php';
require_once APP_PATH . '/models/AppointmentRecordModel.php';
require_once APP_PATH . '/models/AppointmentRecordsModel.php';
require_once APP_PATH . '/models/AppointmentsModel.php';
require_once APP_PATH . '/models/BookingModel.php';
require_once APP_PATH . '/models/BookingPhotoModel.php';
require_once APP_PATH . '/models/BookingPhotosModel.php';
require_once APP_PATH . '/models/BookingsModel.php';
require_once APP_PATH . '/models/ClinicModel.php';
require_once APP_PATH . '/models/ClinicsModel.php';
require_once APP_PATH . '/models/DoctorAndServiceModel.php';
require_once APP_PATH . '/models/DoctorModel.php';
require_once APP_PATH . '/models/DoctorsModel.php';
require_once APP_PATH . '/models/DrugModel.php';
require_once APP_PATH . '/models/DrugsModel.php';
require_once APP_PATH . '/models/NotificationModel.php';
require_once APP_PATH . '/models/NotificationsModel.php';
require_once APP_PATH . '/models/PatientModel.php';
require_once APP_PATH . '/models/PatientsModel.php';
require_once APP_PATH . '/models/RoomModel.php';
require_once APP_PATH . '/models/RoomsModel.php';
require_once APP_PATH . '/models/ServiceModel.php';
require_once APP_PATH . '/models/ServicesModel.php';
require_once APP_PATH . '/models/SpecialitiesModel.php';
require_once APP_PATH . '/models/SpecialityModel.php';
require_once APP_PATH . '/models/TreatmentModel.php';
require_once APP_PATH . '/models/TreatmentsModel.php';

// Controllers
require_once APP_PATH . '/controllers/AppointmentController.php';
require_once APP_PATH . '/controllers/AppointmentQueueController.php';
require_once APP_PATH . '/controllers/AppointmentQueueNowController.php';
require_once APP_PATH . '/controllers/AppointmentRecordController.php';
require_once APP_PATH . '/controllers/AppointmentRecordsController.php';
require_once APP_PATH . '/controllers/AppointmentsController.php';
require_once APP_PATH . '/controllers/BookingController.php';
require_once APP_PATH . '/controllers/BookingPhotoController.php';
require_once APP_PATH . '/controllers/BookingPhotosController.php';
require_once APP_PATH . '/controllers/BookingPhotoUploadController.php';
require_once APP_PATH . '/controllers/BookingsController.php';
require_once APP_PATH . '/controllers/ChartsController.php';
require_once APP_PATH . '/controllers/ClinicController.php';
require_once APP_PATH . '/controllers/ClinicsController.php';
require_once APP_PATH . '/controllers/DoctorController.php';
require_once APP_PATH . '/controllers/DoctorProfileController.php';
require_once APP_PATH . '/controllers/DoctorsAndServicesController.php';
require_once APP_PATH . '/controllers/DoctorsAndServicesReadyController.php';
require_once APP_PATH . '/controllers/DoctorsController.php';
require_once APP_PATH . '/controllers/DrugController.php';
require_once APP_PATH . '/controllers/DrugsController.php';
require_once APP_PATH . '/controllers/IndexController.php';
require_once APP_PATH . '/controllers/isDoctorBusyController.php';
require_once APP_PATH . '/controllers/LoginController.php';
require_once APP_PATH . '/controllers/LoginWithGoogleController.php';
require_once APP_PATH . '/controllers/LogoutController.php';
require_once APP_PATH . '/controllers/PasswordResetController.php';
require_once APP_PATH . '/controllers/PatientAppointmentController.php';
require_once APP_PATH . '/controllers/PatientAppointmentsController.php';
require_once APP_PATH . '/controllers/PatientBookingController.php';
require_once APP_PATH . '/controllers/PatientBookingsController.php';
require_once APP_PATH . '/controllers/PatientController.php';
require_once APP_PATH . '/controllers/PatientNotificationController.php';
require_once APP_PATH . '/controllers/PatientNotificationsController.php';
require_once APP_PATH . '/controllers/PatientProfileController.php';
require_once APP_PATH . '/controllers/PatientRecordController.php';
require_once APP_PATH . '/controllers/PatientRecordsController.php';
require_once APP_PATH . '/controllers/PatientsController.php';
require_once APP_PATH . '/controllers/PatientTreatmentController.php';
require_once APP_PATH . '/controllers/PatientTreatmentsController.php';
require_once APP_PATH . '/controllers/RecoveryController.php';
require_once APP_PATH . '/controllers/RoomController.php';
require_once APP_PATH . '/controllers/RoomsController.php';
require_once APP_PATH . '/controllers/ServiceController.php';
require_once APP_PATH . '/controllers/ServicesController.php';
require_once APP_PATH . '/controllers/SignupController.php';
require_once APP_PATH . '/controllers/SpecialitiesController.php';
require_once APP_PATH . '/controllers/SpecialityController.php';
require_once APP_PATH . '/controllers/TreatmentController.php';
require_once APP_PATH . '/controllers/TreatmentsController.php';




// 8. Cấu hình timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// 9. Thiết lập môi trường test
$_SERVER['REQUEST_METHOD'] = 'GET';

// 10. Thêm các hàm phụ trợ cho việc test
require_once __DIR__ . '/helper.php';
function isVietnameseName($name)
{
    // Thực hiện kiểm tra tên tiếng Việt
    if (empty($name)) return 0;
    return 1;
}

function isBirthdayValid($birthday)
{
    // Thực hiện kiểm tra ngày sinh
    if (empty($birthday)) return "Birthday is required";
    return '';
}

function isNumber($number)
{
    // Kiểm tra xem có phải là số không
    return is_numeric($number);
}

function isAppointmentTimeValid($time)
{
    // Kiểm tra thời gian hẹn
    if (empty($time)) return "Appointment time is required";
    return '';
}

// 11. Tạo class Input giả lập
class InputMock
{
    public static $methodMock;
    public static $putMock;
    public static $patchMock;
    public static $getMock;
    public static $postMock;

    public static function method()
    {
        if (isset(self::$methodMock)) {
            $func = self::$methodMock;
            return $func();
        }
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function get($key = null)
    {
        if (isset(self::$getMock)) {
            $func = self::$getMock;
            return $func($key);
        }
        return isset($_GET[$key]) ? $_GET[$key] : null;
    }

    public static function post($key = null)
    {
        if (isset(self::$postMock)) {
            $func = self::$postMock;
            return $func($key);
        }
        return isset($_POST[$key]) ? $_POST[$key] : null;
    }

    public static function put($key = null)
    {
        if (isset(self::$putMock)) {
            $func = self::$putMock;
            return $func($key);
        }
        global $_PUT;
        return isset($_PUT[$key]) ? $_PUT[$key] : null;
    }

    public static function patch($key = null)
    {
        if (isset(self::$patchMock)) {
            $func = self::$patchMock;
            return $func($key);
        }
        global $_PATCH;
        // Debug code
        error_log("PATCH called for key: $key but no mock was set!");
        return isset($_PATCH[$key]) ? $_PATCH[$key] : null;
    }
}

// 12. Đăng ký class Input mock để sử dụng trong test
if (!class_exists('Input')) {
    class_alias('InputMock', 'Input');
}

// 13. Thay thế Controller::model để trả về các model thực hoặc mock
Controller::$testMode = true;
Controller::$modelMocks = [];

// Override static method model
function mockControllerModel($modelName, $id = null) {
    // Xử lý trường hợp đặc biệt cho GeneralData
    if ($modelName === 'GeneralData') {
        return new GeneralData();
    }
    
    // Xử lý trường hợp đặc biệt cho các model trong test
    if ($modelName === 'Notification') {
        // Check if NotificationModel class is already defined
        if (class_exists('NotificationModel')) {
            return new NotificationModel($id);
        } else {
            // Create a mock model instance
            $model = new MockModel();
            if ($id) {
                $model->set('id', $id);
            }
            return $model;
        }
    }

    // For other models, try to instantiate or fall back to MockModel
    $className = $modelName . 'Model';
    if (class_exists($className)) {
        return new $className($id);
    } else {
        $model = new MockModel();
        if ($id) {
            $model->set('id', $id);
        }
        return $model;
    }
}

// Gán hàm mock
Controller::$modelMethod = 'mockControllerModel';

// Định nghĩa hàm isAddress() để sử dụng trong controller
function isAddress($input) {
    // Kiểm tra xem chỉ chứa chữ cái, số, dấu gạch ngang và khoảng trắng
    if (empty($input)) return 0;
    return preg_match('/^[a-zA-Z0-9\s\-]+$/u', $input) ? 1 : 0;
}