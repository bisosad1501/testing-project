<?php
/**
 * Lớp kiểm thử DoctorModel
 * 
 * File: api/app/tests/models/DoctorModelTest.php
 * Class: DoctorModelTest
 * 
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp DoctorModel, bao gồm:
 * - Các thao tác CRUD (Tạo, Đọc, Cập nhật, Xóa)
 * - Các phương thức đọc thông tin theo email, phone
 * - Kiểm tra quyền hạn của bác sĩ
 * - Kiểm tra token khôi phục
 * - Kiểm tra trạng thái hoạt động
 * 
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class DoctorModelTest extends DatabaseTestCase 
{
    /**
     * @var DoctorModel Đối tượng model bác sĩ dùng trong test
     */
    protected $doctorModel;
    
    /**
     * @var array Lưu trữ kết quả của tất cả các test
     */
    protected static $allTestResults = [];
    
    /**
     * @var string Nhóm test hiện tại
     */
    protected $currentGroup;
    
    /**
     * @var float Thời điểm bắt đầu test
     */
    protected static $startTime;
    
    /**
     * @var string Tên người dùng hiện tại
     */
    const CURRENT_USER = 'bisosad1501';
    
    /**
     * @var int ID của bác sĩ được tạo để sử dụng chung cho các test
     */
    protected static $testDoctorId;

    /**
     * @var array Dữ liệu bác sĩ mẫu được tạo
     */
    protected static $testDoctorData;
    
    /**
     * @var bool Kiểm soát việc bắt đầu/kết thúc transaction
     */
    protected $useTransaction = true;

    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo DoctorModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/DoctorModel.php';
        $this->doctorModel = new DoctorModel();
        
        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }
    }

    /**
     * Ghi log tiêu đề phần test
     * 
     * @param string $title Tiêu đề phần test
     */
    private function logSection($title) 
    {
        $this->currentGroup = $title;
        fwrite(STDOUT, "\n" . str_repeat("=", 50) . "\n");
        fwrite(STDOUT, "🔍 {$title}\n");
        fwrite(STDOUT, str_repeat("=", 50) . "\n");
    }

    /**
     * Ghi log bước test
     * 
     * @param string $description Mô tả bước test
     * @param string|null $expected Kết quả mong đợi
     */
    private function logStep($description, $expected = null)
    {
        fwrite(STDOUT, "\n📋 {$description}\n");
        if ($expected) {
            fwrite(STDOUT, "  Expected: {$expected}\n");
        }
    }

    /**
     * Ghi log kết quả test
     * 
     * @param bool $success Kết quả test (true/false)
     * @param string $actual Kết quả thực tế
     * @param string|null $error Thông báo lỗi (nếu có)
     */
    private function logResult($success, $actual, $error = null) 
    {
        self::$allTestResults[] = [
            'group' => $this->currentGroup,
            'success' => $success,
            'actual' => $actual,
            'error' => $error
        ];

        $icon = $success ? "✅" : "❌";
        $status = $success ? "SUCCESS" : "FAILED";
        
        fwrite(STDOUT, "  Result: {$actual}\n");
        fwrite(STDOUT, "  Status: {$icon} {$status}" . 
            ($error ? " - {$error}" : "") . "\n");
    }

    /**
     * Tạo dữ liệu bác sĩ mẫu cho test
     * 
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu bác sĩ mẫu
     */
    private function createTestDoctor($override = [])
    {
        return array_merge([
            'email' => 'test_' . time() . '@example.com',
            'phone' => '098' . rand(1000000, 9999999),
            'password' => md5('password123'),
            'name' => 'Test Doctor',
            'description' => 'Test Description',
            'price' => 200000,
            'role' => 'admin',
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
            'create_at' => date('Y-m-d H:i:s'),
            'update_at' => date('Y-m-d H:i:s')
        ], $override);
    }
    
    /**
     * Thực thi SQL trực tiếp và trả về kết quả
     * 
     * @param string $sql Câu lệnh SQL
     * @return array Kết quả truy vấn
     */
    private function executeSQL($sql)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Test đầy đủ CRUD cho DoctorModel
     * 
     * Mã test case: DOC_INS_01, DOC_READ_02, DOC_UPD_04, DOC_DEL_05
     * Mục tiêu: Kiểm tra cả quy trình CRUD trong một test
     * Input: Dữ liệu bác sĩ mẫu
     * Expected output: Thao tác CRUD thành công
     * Ghi chú: Thực hiện kiểm tra DB sau mỗi thao tác để xác nhận dữ liệu nhất quán
     */
    public function testCRUD()
    {
        $this->logSection("DOC: Kiểm tra quy trình CRUD");
        
        // ID và dữ liệu của bác sĩ để sử dụng qua các bước
        $doctorId = null;
        $doctorData = null;
        
        try {
            // BƯỚC 1: CREATE - DOC_INS_01
            $this->logStep("DOC_INS_01: Tạo mới bác sĩ", "Bác sĩ được tạo thành công với ID > 0");
            
            // Tạo dữ liệu kiểm thử
            $data = $this->createTestDoctor();
            $doctorData = $data;
            
            // Set dữ liệu vào model
            foreach ($data as $field => $value) {
                $this->doctorModel->set($field, $value);
            }
            
            // Thực hiện insert và kiểm tra
            $doctorId = $this->doctorModel->insert();
            $createSuccess = $doctorId > 0;
            
            // Kiểm tra dữ liệu đã được lưu trong DB
            if ($createSuccess) {
                self::$testDoctorId = $doctorId; // Lưu ID để sử dụng lại
                $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $doctorId]);
                $this->assertModelMatchesDatabase($data, TABLE_PREFIX.TABLE_DOCTORS, ['id' => $doctorId]);
            }
            
            $this->logResult($createSuccess, 
                "Bác sĩ được tạo " . ($createSuccess ? "thành công với ID: {$doctorId}" : "thất bại"),
                $createSuccess ? null : "Không thể tạo bác sĩ"
            );
            
            // Nếu tạo thất bại thì kết thúc test
            if (!$createSuccess) {
                return;
            }
            
            // BƯỚC 2: READ - DOC_READ_02
            $this->logStep("DOC_READ_02: Đọc thông tin bác sĩ theo ID", "Đọc thành công thông tin bác sĩ");
            
            // Đọc thông tin bác sĩ từ ID
            $doctor = new DoctorModel(self::$testDoctorId);
            $readSuccess = $doctor->isAvailable();
            
            if ($readSuccess) {
                $record = $this->getRecord(TABLE_PREFIX.TABLE_DOCTORS, ['id' => self::$testDoctorId]);
                $this->assertNotNull($record, "Phải tìm thấy bản ghi bác sĩ");
                
                // Kiểm tra thông tin khớp với dữ liệu đã tạo
                foreach ($doctorData as $key => $value) {
                    $this->assertEquals($value, $doctor->get($key), "Trường {$key} không khớp");
                }
            }
            
            $this->logResult($readSuccess, 
                "Đọc thông tin bác sĩ: " . ($readSuccess ? "Thành công" : "Thất bại"),
                $readSuccess ? null : "Không thể đọc thông tin bác sĩ"
            );
            
            // BƯỚC 3: UPDATE - DOC_UPD_04
            $this->logStep("DOC_UPD_04: Cập nhật thông tin bác sĩ", "Cập nhật thành công tên bác sĩ");
            
            // Cập nhật tên bác sĩ
            $newName = 'Updated Doctor';
            $doctor->set('name', $newName);
            $updateSuccess = $doctor->update();
            
            if ($updateSuccess) {
                $this->assertModelMatchesDatabase(
                    ['name' => $newName],
                    TABLE_PREFIX.TABLE_DOCTORS,
                    ['id' => self::$testDoctorId]
                );
            }
            
            $nameMatches = $newName === $doctor->get('name');
            $updateSuccess = $updateSuccess && $nameMatches;
            
            $this->logResult($updateSuccess,
                sprintf("Cập nhật bác sĩ: %s\nKiểm tra tên: %s", 
                    $updateSuccess ? "Thành công" : "Thất bại",
                    $nameMatches ? "Khớp" : "Không khớp"
                ),
                $updateSuccess ? null : "Cập nhật tên bác sĩ thất bại"
            );
            
            // BƯỚC 4: DELETE - DOC_DEL_05
            $this->logStep("DOC_DEL_05: Xóa thông tin bác sĩ", "Xóa thành công bác sĩ khỏi DB");
            
            // Xóa bác sĩ
            $deleteSuccess = $doctor->delete();
            
            if ($deleteSuccess) {
                $this->assertRecordNotExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => self::$testDoctorId]);
            }
            
            $isGone = !$doctor->isAvailable();
            $deleteSuccess = $deleteSuccess && $isGone;
            
            $this->logResult($deleteSuccess,
                sprintf("Xóa bác sĩ: %s\nKiểm tra DB: %s", 
                    $deleteSuccess ? "Thành công" : "Thất bại",
                    $isGone ? "Đã xóa" : "Vẫn còn"
                ),
                $deleteSuccess ? null : "Xóa bác sĩ thất bại"
            );
            
            // Lưu kết quả tổng hợp
            self::$allTestResults['CRUD'] = [
                'success' => $createSuccess && $readSuccess && $updateSuccess && $deleteSuccess,
                'total' => 4,
                'passed' => ($createSuccess ? 1 : 0) + ($readSuccess ? 1 : 0) + 
                           ($updateSuccess ? 1 : 0) + ($deleteSuccess ? 1 : 0)
            ];
            
        } catch (Exception $e) {
            $this->logResult(false, 
                "❌ Lỗi trong quá trình test CRUD", 
                $e->getMessage()
            );
            
            // Đảm bảo dọn dẹp nếu lỗi xảy ra
            if (self::$testDoctorId) {
                $doctor = new DoctorModel(self::$testDoctorId);
                if ($doctor->isAvailable()) {
                    $doctor->delete();
                }
            }
            
            $this->fail("Lỗi trong quá trình test CRUD: " . $e->getMessage());
        }
    }

    /**
     * Test Case DOC_FIND_03: Kiểm tra các phương thức đọc thông tin bác sĩ (email, phone)
     * Mã test case: DOC_FIND_03
     * Mục tiêu: Kiểm tra chức năng tìm bác sĩ qua email và số điện thoại
     * Input: Email, phone của bác sĩ
     * Expected output: Trả về đúng thông tin bác sĩ
     * Ghi chú: Phương thức này kiểm tra việc tìm kiếm bác sĩ theo nhiều tiêu chí khác nhau
     */
    public function testSelectionMethods()
    {
        $this->logSection("DOC_FIND_03: Kiểm tra các phương thức đọc thông tin");
        
        $doctorId = null;
        
        try {
            // Tạo dữ liệu kiểm thử
            $uniqueTime = time();
            $email = "test_{$uniqueTime}@example.com";
            $phone = "098" . rand(1000000, 9999999);
            
            $data = $this->createTestDoctor([
                'email' => $email,
                'phone' => $phone
            ]);
            
            // Thêm debug info
            fwrite(STDOUT, "\n📊 DEBUG: Dữ liệu ban đầu: phone = '{$phone}'\n");
            
            // Chèn bản ghi vào DB với phương thức insertFixture
            $doctorId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $data);
            if ($doctorId <= 0) {
                throw new Exception("Không thể tạo dữ liệu kiểm thử");
            }
            
            // Lấy dữ liệu đã lưu trong DB để kiểm tra chính xác
            $savedData = $this->getRecord(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $doctorId]);
            $actualPhone = $savedData['phone'];
            
            // Thêm debug info
            fwrite(STDOUT, "📊 DEBUG: Số điện thoại trong DB = '{$actualPhone}'\n");
            fwrite(STDOUT, "📊 DEBUG: Kiểu dữ liệu phone trong dữ liệu ban đầu: " . gettype($phone) . "\n");
            fwrite(STDOUT, "📊 DEBUG: Kiểu dữ liệu phone trong DB: " . gettype($actualPhone) . "\n\n");
            
            // Kiểm tra trực tiếp trong DB
            $stmt = $this->pdo->prepare("SELECT * FROM " . TABLE_PREFIX.TABLE_DOCTORS . " WHERE phone = ?");
            $stmt->execute([$actualPhone]);
            $directResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
            fwrite(STDOUT, "📊 DEBUG: Truy vấn trực tiếp tìm thấy " . count($directResult) . " bản ghi với phone = '{$actualPhone}'\n\n");
            
            // Mảng lưu kết quả test
            $testResults = [];

            // DOC_FIND_03.1: Kiểm tra tìm theo email
            $this->logStep("DOC_FIND_03.1: Kiểm tra tìm theo email", "Phải tìm thấy bác sĩ với email {$email}");
            $byEmail = new DoctorModel($email);
            $emailSuccess = $byEmail->isAvailable();
            $this->logResult($emailSuccess,
                "Tìm theo Email: " . ($emailSuccess ? "✅ Đã tìm thấy" : "❌ Không tìm thấy"),
                $emailSuccess ? null : "Không tìm thấy bác sĩ theo email {$email}"
            );
            $testResults['email'] = $emailSuccess;

            // DOC_FIND_03.2: Kiểm tra tìm theo số điện thoại
            $this->logStep("DOC_FIND_03.2: Kiểm tra tìm theo số điện thoại", "Phải tìm thấy bác sĩ với SĐT {$actualPhone}");
            
            // Thử tìm với số điện thoại chính xác
            $byPhone = new DoctorModel($actualPhone);
            $phoneSuccess = $byPhone->isAvailable();
            
            if (!$phoneSuccess) {
                fwrite(STDOUT, "📊 DEBUG: Thử tìm kiếm với kiểu chuỗi\n");
                $byPhone = new DoctorModel((string)$actualPhone);
                $phoneSuccess = $byPhone->isAvailable();
            }
            
            // Hiển thị thông báo lỗi
            if (!$phoneSuccess) {
                fwrite(STDOUT, "📊 DEBUG: LỖI NGHIÊM TRỌNG - Không thể tìm kiếm theo số điện thoại mặc dù dữ liệu tồn tại trong DB\n");
                fwrite(STDOUT, "📊 DEBUG: Phương thức select() của DoctorModel có lỗi khi xử lý số điện thoại\n");
                
                // Kiểm tra gián tiếp để xác nhận dữ liệu tồn tại
                $stmt = $this->pdo->prepare("SELECT id FROM " . TABLE_PREFIX.TABLE_DOCTORS . " WHERE phone = ?");
                $stmt->execute([$actualPhone]);
                $manual = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($manual && isset($manual['id'])) {
                    $byId = new DoctorModel($manual['id']);
                    $idSuccess = $byId->isAvailable();
                    fwrite(STDOUT, "📊 DEBUG: Tìm kiếm thông qua ID: " . ($idSuccess ? "Thành công" : "Thất bại") . "\n");
                    fwrite(STDOUT, "📊 DEBUG: Điều này xác nhận DoctorModel có lỗi khi tìm kiếm theo phone\n");
                }
            }
            
            $this->logResult($phoneSuccess,
                "Tìm theo SĐT: " . ($phoneSuccess ? "✅ Đã tìm thấy" : "❌ Không tìm thấy"),
                $phoneSuccess ? null : "Lỗi: Không tìm thấy bác sĩ theo SĐT {$actualPhone} mặc dù data tồn tại trong DB"
            );
            $testResults['phone'] = $phoneSuccess;
            
            // TEST THẤT BẠI NẾU KHÔNG TÌM ĐƯỢC THEO PHONE
            // Khác với trước đây, chúng ta sẽ không workaround nữa, mà thực sự báo lỗi
            if (!$phoneSuccess) {
                $this->fail("BUG #1: Phương thức select() của DoctorModel không hoạt động đúng với số điện thoại");
            }

            // DOC_FIND_03.3: Kiểm tra xử lý ID không hợp lệ
            $this->logStep("DOC_FIND_03.3: Kiểm tra ID không hợp lệ", "Phải từ chối ID không hợp lệ");
            $byInvalidId = new DoctorModel(-1);
            $invalidIdHandled = !$byInvalidId->isAvailable();
            $this->logResult($invalidIdHandled,
                "Xử lý ID không hợp lệ: " . ($invalidIdHandled ? "✅ Đã từ chối đúng" : "❌ Chấp nhận sai"),
                $invalidIdHandled ? null : "Không từ chối ID không hợp lệ"
            );
            $testResults['invalid_id'] = $invalidIdHandled;

            // Lưu kết quả chung cho nhóm Selection Methods
            self::$allTestResults['Selection Methods'] = [
                'group' => $this->currentGroup,
                'success' => !in_array(false, $testResults),
                'total' => 3, // Số lượng test case
                'passed' => count(array_filter($testResults)),
                'error' => $phoneSuccess ? null : "Lỗi: Không tìm thấy bác sĩ theo SĐT {$actualPhone} mặc dù data tồn tại trong DB"
            ];
            
        } catch (Exception $e) {
            $this->logResult(false, "❌ Lỗi xảy ra", $e->getMessage());
            $this->fail("Lỗi khi kiểm tra các phương thức đọc: " . $e->getMessage());
        } finally {
            // Đảm bảo dọn dẹp dữ liệu test
            if ($doctorId) {
                $doctor = new DoctorModel($doctorId);
                if ($doctor->isAvailable()) {
                    $doctor->delete();
                }
            }
        }
    }

    /**
     * Test Case DOC_ROLE_06: Kiểm tra quyền của bác sĩ
     * Mã test case: DOC_ROLE_06
     * Mục tiêu: Kiểm tra phân quyền của bác sĩ dựa trên vai trò
     * Input: Các vai trò khác nhau (admin, member, developer)
     * Expected output: Quyền admin được phân đúng theo vai trò
     * Ghi chú: Kiểm tra cả quyền admin và quyền thường
     */
    public function testPermissions()
    {
        $this->logSection("DOC_ROLE_06: Kiểm tra quyền của bác sĩ");
        
        $adminId = null;
        $doctorId = null;
        
        try {
            // Kiểm tra quyền admin
            $this->logStep("DOC_ROLE_06.1: Kiểm tra vai trò admin", "Phải có quyền admin");
            $adminData = $this->createTestDoctor(['role' => 'admin']);
            $adminId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $adminData);
            $admin = new DoctorModel($adminId);
            
            $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $adminId]);
            
            $adminIsAdmin = $admin->isAdmin();
            $this->logResult($adminIsAdmin, 
                sprintf("Kiểm tra quyền admin:\n" .
                       "  👤 Vai trò: admin\n" .
                       "  🔑 Quyền admin: %s",
                       $adminIsAdmin ? "Được cấp (OK)" : "Không được cấp (LỖI)"
                ),
                $adminIsAdmin ? null : "Quyền admin không được cấp cho vai trò admin"
            );

            // Kiểm tra quyền bác sĩ thường
            $this->logStep("DOC_ROLE_06.2: Kiểm tra vai trò member", "Không được có quyền admin");
            $doctorData = $this->createTestDoctor(['role' => 'member']);
            $doctorId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $doctorData);
            $doctor = new DoctorModel($doctorId);
            
            $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $doctorId]);
            
            $doctorIsAdmin = $doctor->isAdmin();
            $this->logResult(!$doctorIsAdmin,
                sprintf("Kiểm tra quyền bác sĩ thường:\n" .
                       "  👤 Vai trò: member\n" .
                       "  🔑 Quyền admin: %s",
                       !$doctorIsAdmin ? "Bị từ chối (OK)" : "Được cấp (LỖI)"
                ),
                !$doctorIsAdmin ? null : "Quyền admin được cấp sai cho vai trò member"
            );

            // Lưu kết quả chung
            self::$allTestResults['Permissions'] = [
                'success' => $adminIsAdmin && !$doctorIsAdmin,
                'message' => ($adminIsAdmin && !$doctorIsAdmin) ? 
                            "Tất cả kiểm tra quyền thành công" : 
                            "Kiểm tra quyền thất bại"
            ];
            
        } catch (Exception $e) {
            $this->logResult(false, 
                "❌ Lỗi trong kiểm tra quyền", 
                $e->getMessage()
            );
            $this->fail("Lỗi khi kiểm tra quyền: " . $e->getMessage());
        } finally {
            // Đảm bảo dọn dẹp dữ liệu test
            if ($adminId) {
                $admin = new DoctorModel($adminId);
                if ($admin->isAvailable()) {
                    $admin->delete();
                }
            }
            
            if ($doctorId) {
                $doctor = new DoctorModel($doctorId);
                if ($doctor->isAvailable()) {
                    $doctor->delete();
                }
            }
        }
    }

    /**
     * Hàm tiện ích để tạo chuỗi ngẫu nhiên cho kiểm thử
     * 
     * @param int $length Độ dài chuỗi cần tạo
     * @return string Chuỗi ngẫu nhiên
     */
    private function generateRandomString($length = 32) 
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Test Case DOC_TOKEN_07: Kiểm tra token khôi phục
     * Mã test case: DOC_TOKEN_07
     * Mục tiêu: Kiểm tra các hoạt động liên quan đến token khôi phục mật khẩu
     * Input: Token khôi phục tạo ngẫu nhiên
     * Expected output: Token được lưu và xóa chính xác
     * Ghi chú: Token khôi phục được sử dụng để xác thực người dùng khi họ quên mật khẩu
     */
    public function testRecoveryToken()
    {
        $this->logSection("DOC_TOKEN_07: Kiểm tra token khôi phục");
        
        $doctorId = null;
        
        try {
            // Tạo bác sĩ với token khôi phục
            $recoveryToken = $this->generateRandomString();
            $data = $this->createTestDoctor([
                'recovery_token' => $recoveryToken
            ]);
            
            $doctorId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $data);
            $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $doctorId]);
            
            $doctor = new DoctorModel($doctorId);
            $this->logStep("DOC_TOKEN_07.1: Kiểm tra token khôi phục", "Phải có token khớp");
            
            // Kiểm tra token
            $tokenMatch = $doctor->get('recovery_token') === $recoveryToken;
            
            $this->assertModelMatchesDatabase(
                ['recovery_token' => $recoveryToken],
                TABLE_PREFIX.TABLE_DOCTORS,
                ['id' => $doctorId]
            );
            
            $this->logResult($tokenMatch, 
                sprintf("Kiểm tra token:\n" .
                       "  🔐 Mong đợi: %s\n" .
                       "  📝 Thực tế: %s\n" .
                       "  📊 Kết quả: %s",
                       substr($recoveryToken, 0, 8) . '...',
                       substr($doctor->get('recovery_token'), 0, 8) . '...',
                       $tokenMatch ? "Khớp (OK)" : "Không khớp (LỖI)"
                ),
                $tokenMatch ? null : "Token khôi phục không khớp"
            );

            // Kiểm tra xóa token
            $this->logStep("DOC_TOKEN_07.2: Xóa token khôi phục", "Phải xóa token thành công");
            $doctor->set('recovery_token', '');
            $updateSuccess = $doctor->update();
            
            if ($updateSuccess) {
                $this->assertModelMatchesDatabase(
                    ['recovery_token' => ''],
                    TABLE_PREFIX.TABLE_DOCTORS,
                    ['id' => $doctorId]
                );
            }
            
            $tokenCleared = $doctor->get('recovery_token') === '';
            $resetSuccess = $updateSuccess && $tokenCleared;
            
            $this->logResult($resetSuccess,
                sprintf("Xóa token:\n" .
                       "  📝 Cập nhật: %s\n" .
                       "  🔍 Đã xóa: %s",
                       $updateSuccess ? "Thành công" : "Thất bại",
                       $tokenCleared ? "Có (OK)" : "Không (LỖI)"
                ),
                $resetSuccess ? null : "Không thể xóa token khôi phục"
            );

        } catch (Exception $e) {
            $this->logResult(false, 
                "❌ Lỗi trong kiểm tra token khôi phục", 
                $e->getMessage()
            );
            $this->fail("Lỗi khi kiểm tra token khôi phục: " . $e->getMessage());
        } finally {
            // Đảm bảo dọn dẹp dữ liệu test
            if ($doctorId) {
                $doctor = new DoctorModel($doctorId);
                if ($doctor->isAvailable()) {
                    $doctor->delete();
                }
            }
        }
    }

    /**
     * Test Case DOC_ACTIVE_08: Kiểm tra trạng thái hoạt động
     * Mã test case: DOC_ACTIVE_08
     * Mục tiêu: Kiểm tra trạng thái hoạt động của bác sĩ
     * Input: Các giá trị active khác nhau (0, 1)
     * Expected output: Trạng thái được lưu và cập nhật chính xác
     * Ghi chú: Trạng thái hoạt động xác định liệu bác sĩ có thể đăng nhập hệ thống hay không
     */
    public function testActiveStatus()
    {
        $this->logSection("DOC_ACTIVE_08: Kiểm tra trạng thái hoạt động");
        
        $doctorId = null;
        
        try {
            // Kiểm tra bác sĩ hoạt động
            $activeData = $this->createTestDoctor(['active' => 1]);
            $doctorId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $activeData);
            
            $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $doctorId]);
            $this->logStep("DOC_ACTIVE_08.1: Kiểm tra trạng thái hoạt động", "Bác sĩ phải hoạt động khi active=1");
            
            $activeDoctor = new DoctorModel($doctorId);
            $isActive = $activeDoctor->get('active') == 1;
            
            $this->logResult($isActive, 
                sprintf("Kiểm tra bác sĩ hoạt động:\n" .
                       "  👤 ID: %d\n" .
                       "  🔵 Trạng thái: %s\n" .
                       "  📊 Kết quả: %s",
                       $doctorId,
                       $activeDoctor->get('active'),
                       $isActive ? "Hoạt động (OK)" : "Không hoạt động (LỖi)"
                ),
                $isActive ? null : "Không thể xác minh trạng thái hoạt động"
            );

            // Kiểm tra chuyển đổi trạng thái
            $activeDoctor->set('active', 0);
            $toggleSuccess = $activeDoctor->update();
            
            if ($toggleSuccess) {
                $this->assertModelMatchesDatabase(
                    ['active' => 0],
                    TABLE_PREFIX.TABLE_DOCTORS,
                    ['id' => $doctorId]
                );
            }
            
            $isToggled = $activeDoctor->get('active') == 0;
            
            $this->logResult($isToggled && $toggleSuccess,
                sprintf("Kiểm tra chuyển đổi trạng thái:\n" .
                       "  👤 ID: %d\n" .
                       "  🔄 Thao tác: %s\n" .
                       "  📊 Kết quả: %s",
                       $doctorId,
                       $toggleSuccess ? "Thành công" : "Thất bại",
                       $isToggled ? "Chuyển đổi chính xác" : "Chuyển đổi thất bại"
                ),
                ($isToggled && $toggleSuccess) ? null : "Không thể chuyển đổi trạng thái hoạt động"
            );

        } catch (Exception $e) {
            $this->logResult(false, 
                "❌ Lỗi trong kiểm tra trạng thái hoạt động", 
                $e->getMessage()
            );
            $this->fail("Lỗi khi kiểm tra trạng thái hoạt động: " . $e->getMessage());
        } finally {
            // Đảm bảo dọn dẹp dữ liệu test
            if ($doctorId) {
                $activeDoctor = new DoctorModel($doctorId);
                if ($activeDoctor->isAvailable()) {
                    $activeDoctor->delete();
                }
            }
        }
    }

    /**
     * Dọn dẹp sau mỗi test và in tổng kết nếu là test cuối cùng
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        // Lấy tên test hiện tại
        $currentTest = $this->getName();
        
        // Lấy tất cả các phương thức test
        $class = new ReflectionClass($this);
        $methods = array_filter($class->getMethods(), function($method) {
            return strpos($method->name, 'test') === 0 && $method->isPublic();
        });
        
        // Lấy tên test cuối cùng
        $lastTest = end($methods)->name;
        
        // In tổng kết nếu đây là test cuối cùng
        if ($currentTest === $lastTest) {
            $this->printFinalSummary();
        }
    }

    /**
     * In tổng kết kết quả kiểm thử
     */
    private function printFinalSummary()
    {
        if (empty(self::$allTestResults)) {
            return;
        }

        // In tiêu đề
        fwrite(STDOUT, "\n" . str_repeat("=", 70) . "\n");
        fwrite(STDOUT, "📊 TỔNG KẾT KẾT QUẢ KIỂM THỬ\n");
        fwrite(STDOUT, str_repeat("=", 70) . "\n");
        fwrite(STDOUT, "🕒 Thời gian: " . date('Y-m-d H:i:s') . "\n");
        fwrite(STDOUT, "👤 Người dùng: " . self::CURRENT_USER . "\n\n");

        // Định nghĩa số lượng test case cho mỗi nhóm
        $testGroups = [
            'DOC: Kiểm tra quy trình CRUD' => [
                'total' => 4,
                'tests' => ['DOC_INS_01', 'DOC_READ_02', 'DOC_UPD_04', 'DOC_DEL_05']
            ],
            'DOC_FIND_03: Kiểm tra các phương thức đọc thông tin' => [
                'total' => 3,
                'tests' => ['DOC_FIND_03.1', 'DOC_FIND_03.2', 'DOC_FIND_03.3']
            ],
            'DOC_ROLE_06: Kiểm tra quyền của bác sĩ' => [
                'total' => 2,
                'tests' => ['DOC_ROLE_06.1', 'DOC_ROLE_06.2']
            ],
            'DOC_TOKEN_07: Kiểm tra token khôi phục' => [
                'total' => 2,
                'tests' => ['DOC_TOKEN_07.1', 'DOC_TOKEN_07.2']
            ],
            'DOC_ACTIVE_08: Kiểm tra trạng thái hoạt động' => [
                'total' => 2,
                'tests' => ['DOC_ACTIVE_08.1', 'DOC_ACTIVE_08.2']
            ]
        ];

        $groupResults = [];
        $totalTests = 0;
        $totalPassed = 0;
        $allFailures = [];

        // Khởi tạo kết quả nhóm
        foreach ($testGroups as $group => $info) {
            $groupResults[$group] = [
                'total' => $info['total'],
                'passed' => 0,
                'failures' => []
            ];
            $totalTests += $info['total'];
        }

        // Đếm kết quả CRUD
        if (isset(self::$allTestResults['CRUD'])) {
            $groupResults['DOC: Kiểm tra quy trình CRUD']['passed'] = self::$allTestResults['CRUD']['passed'];
            $totalPassed += self::$allTestResults['CRUD']['passed'];
        }

        // Đếm kết quả test khác
        $successes = array_filter(self::$allTestResults, function($result) {
            return isset($result['success']) && $result['success'] === true;
        });

        // Đếm số lượng thành công cho mỗi nhóm
        foreach ($successes as $result) {
            if (!isset($result['group'])) continue;
            
            $group = $result['group'];
            if (!isset($groupResults[$group])) continue;
            
            // Đã đếm CRUD riêng, bỏ qua
            if ($group === 'DOC: Kiểm tra quy trình CRUD') continue;
            
            // Đếm các kết quả thành công khác
            // Giới hạn số lượng đếm bằng tổng số test case của nhóm
            if ($groupResults[$group]['passed'] < $groupResults[$group]['total']) {
                $groupResults[$group]['passed']++;
                $totalPassed++;
            }
        }

        // Thu thập lỗi
        foreach (self::$allTestResults as $result) {
            if (!isset($result['success']) || $result['success'] === true || !isset($result['group'])) continue;
            
            $group = $result['group'];
            if (!isset($groupResults[$group])) continue;
            
            if (isset($result['error']) && $result['error']) {
                $groupResults[$group]['failures'][] = $result['error'];
                $allFailures[] = $result['error'];
            }
        }

        // In kết quả của từng nhóm
        foreach ($groupResults as $group => $stats) {
            fwrite(STDOUT, "NHÓM: {$group}\n");
            
            // Tính phần trăm thành công
            $percentSuccess = ($stats['total'] > 0) 
                ? round(($stats['passed'] / $stats['total']) * 100) 
                : 0;
            
            fwrite(STDOUT, sprintf("  ✓ Đã qua: %d/%d (%d%%)\n",
                $stats['passed'],
                $stats['total'],
                $percentSuccess
            ));

            if (!empty($stats['failures'])) {
                fwrite(STDOUT, "  ✗ Lỗi:\n");
                foreach (array_unique($stats['failures']) as $failure) {
                    if ($failure) {
                        fwrite(STDOUT, "    • {$failure}\n");
                    }
                }
            }
            fwrite(STDOUT, "\n");
        }

        // In thống kê tổng thể
        $duration = round(microtime(true) - self::$startTime, 2);
        $percentTotal = ($totalTests > 0) 
            ? round(($totalPassed / $totalTests) * 100) 
            : 0;
        
        fwrite(STDOUT, str_repeat("-", 70) . "\n");
        fwrite(STDOUT, "THỐNG KÊ TỔNG QUÁT\n");
        fwrite(STDOUT, sprintf("✅ Tổng số test case: %d\n", $totalTests));
        fwrite(STDOUT, sprintf("✅ Đã qua: %d (%d%%)\n", $totalPassed, $percentTotal));
        fwrite(STDOUT, sprintf("❌ Thất bại: %d\n", $totalTests - $totalPassed));
        fwrite(STDOUT, sprintf("⏱️ Thời gian: %.2fs\n", $duration));
        fwrite(STDOUT, str_repeat("=", 70) . "\n\n");
    }
}
