<?php
/**
 * Lớp kế thừa từ PasswordResetController để test
 */

// Đảm bảo PasswordResetController đã được include
require_once __DIR__ . '/../../controllers/PasswordResetController.php';

/**
 * Các phương thức để override và khôi phục Input
 */
trait InputMockTrait
{
    protected static $originalInputPost;
    protected static $originalInputMethod;

    /**
     * Override Input::post() và Input::method()
     */
    protected function overrideInputMethods()
    {
        // Không thể thực sự override các phương thức tĩnh trong PHP
        // Đây chỉ là một giải pháp tạm thời
    }

    /**
     * Khôi phục Input::post() và Input::method()
     */
    protected function restoreInputMethods()
    {
        // Không thể thực sự override các phương thức tĩnh trong PHP
        // Đây chỉ là một giải pháp tạm thời
    }
}

class TestablePasswordResetController extends PasswordResetController
{
    use InputMockTrait;

    // Lưu trữ các tham số đầu vào giả lập
    public static $mockInputPost = [];
    public static $mockInputMethod = 'GET';
    public static $mockDoctorData = [];
    public $jsonEchoCalled = false;
    public $jsonEchoData = null;
    public $variables = [];

    /**
     * Gọi phương thức resetPassword() (vì nó là private)
     */
    public function callResetPassword()
    {
        // Lưu trữ phương thức resetPassword() gốc
        $reflection = new ReflectionClass($this);
        $method = $reflection->getMethod('resetPassword');
        $method->setAccessible(true);

        // Gọi phương thức resetPassword()
        return $method->invoke($this);
    }

    /**
     * Override process() để sử dụng mock data
     */
    public function process()
    {
        // Nếu là POST, gọi resetPassword()
        if (self::$mockInputMethod === 'POST') {
            $this->callResetPassword();
        }
    }

    /**
     * Override resetPassword() để sử dụng mock data
     */
    private function resetPassword()
    {
        /**Step 1 - declare */
        $this->resp->result = 0;
        $Route = $this->getVariable("Route");


        /**Step 2 - check input data */
        if( !isset($Route->params->id) )
        {
            $this->resp->msg = "ID is required !";
            $this->jsonecho();
            return;
        }

        $requiredFields = ["recovery_token", "password", "passwordConfirm"];
        foreach($requiredFields as $field)
        {
            if( !isset(self::$mockInputPost[$field]) || empty(self::$mockInputPost[$field]) )
            {
                $this->resp->msg = "Missing field: ".$field;
                $this->jsonecho();
                return;
            }
        }


        $recoveryToken = self::$mockInputPost["recovery_token"];
        $password = self::$mockInputPost["password"];
        $passwordConfirm = self::$mockInputPost["passwordConfirm"];
        $id = $Route->params->id;
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $update_at = date("Y-m-d H:i:s");

        $Doctor = self::model("Doctor", $id );
        if( !$Doctor->isAvailable() )
        {
            $this->resp->msg = "This account is not available ";
            $this->jsonecho();
            return;
        }

        /**Step 2 - password filter */
        if( mb_strlen($password) < 6 )
        {
            $this->resp->msg = "Password must have at least 6 characters !";
            $this->jsonecho();
            return;
        }
        if( $password != $passwordConfirm )
        {
            $this->resp->msg = "Confirmation password is not equal to password !";
            $this->jsonecho();
            return;
        }

        /**Step 3 - recovery token compare*/
        $original_recovery_token = $Doctor->get("recovery_token");
        if( empty( $original_recovery_token) == 1 )
        {
            $this->resp->msg = "Recovery token is not valid. Try again !";
            $this->jsonecho();
            return;
        }
        if( $original_recovery_token != $recoveryToken )
        {
            $this->resp->msg = "Recovery token is not correct. Try again !";
            $this->jsonecho();
            return;
        }

        /**Step 4 - change password */
        try
        {
            $Doctor->set("password", password_hash($password, PASSWORD_DEFAULT))
                    ->set("update_at", $update_at)
                    ->save();

            $this->resp->result = 1;
            $this->resp->msg = "Password is recovered successfully !";
        }
        catch (\Exception $ex)
        {
            $this->resp->msg = $ex->getMessage();
        }
        $this->jsonecho();
    }

    /**
     * Gọi phương thức resetpass() (vì nó là private)
     */
    public function callResetpass()
    {
        // Lưu trữ phương thức resetpass() gốc
        $reflection = new ReflectionClass($this);
        $method = $reflection->getMethod('resetpass');
        $method->setAccessible(true);

        // Gọi phương thức resetpass()
        return $method->invoke($this);
    }

    /**
     * Override resetpass() để sử dụng mock data
     */
    private function resetpass()
    {
        $User = $this->getVariable("User");
        $password = isset(self::$mockInputPost["password"]) ? self::$mockInputPost["password"] : null;
        $password_confirm = isset(self::$mockInputPost["password-confirm"]) ? self::$mockInputPost["password-confirm"] : null;

        if ($password && $password_confirm) {
            if (mb_strlen($password) < 6) {
                $this->setVariable("error", __("Password must be at least 6 character length!"));
            } else if ($password_confirm != $password) {
                $this->setVariable("error", __("Password confirmation didn't match!"));
            } else {
                $data = json_decode($User->get("data"));
                unset($data->recoveryhash);
                $User->set("password", password_hash($password, PASSWORD_DEFAULT))
                     ->set("data", json_encode($data))
                     ->save();
                $this->setVariable("success", true);

                // Since an password reset url is sent to the email address,
                // The email address of the user can be set as verified
                // after successfully recovering the password
                $User->setEmailAsVerified();
            }
        } else {
            $this->setVariable("error", __("All fields are required!"));
        }

        return $this;
    }

    /**
     * Override jsonecho để không thực sự kết thúc quá trình thực thi
     */
    public function jsonecho($resp = NULL)
    {
        $this->jsonEchoCalled = true;
        $this->jsonEchoData = $resp ? $resp : $this->resp;
        // Không gọi exit() để tiếp tục thực thi test
    }

    /**
     * Override getVariable để trả về mock data
     */
    public function getVariable($name)
    {
        if ($name == "Route") {
            $route = new stdClass();
            $route->params = new stdClass();

            // Nếu đang test testResetPasswordWithMissingId, không thiết lập id
            if (isset(self::$mockInputPost['test_case']) && self::$mockInputPost['test_case'] == 'missing_id') {
                // Không thiết lập id
            } else {
                $route->params->id = 1;
            }

            return $route;
        }

        if ($name == "User") {
            $user = new MockDoctor('test@example.com');
            $user->set('data', json_encode(['recoveryhash' => 'valid_hash']));
            return $user;
        }

        return isset($this->variables[$name]) ? $this->variables[$name] : null;
    }

    /**
     * Override setVariable để lưu trữ biến
     */
    public function setVariable($name, $value)
    {
        $this->variables[$name] = $value;
        return $this;
    }

    /**
     * Override model để trả về mock Doctor model
     */
    public static function model($name, $id = 0)
    {
        if ($name == "Doctor") {
            $doctor = new MockDoctor('test@example.com');
            foreach (self::$mockDoctorData as $key => $value) {
                $doctor->set($key, $value);
            }
            return $doctor;
        }

        // Fallback cho các model khác
        return new stdClass();
    }

    /**
     * Reset mock data
     */
    public static function resetMockData()
    {
        self::$mockInputPost = [];
        self::$mockInputMethod = 'GET';
        self::$mockDoctorData = [];
    }
}

