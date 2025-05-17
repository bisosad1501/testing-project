<?php
/**
 * Lớp kiểm thử bổ sung cho DoctorModel
 *
 * File: api/app/tests/models/DoctorModelAdditionalTest.php
 * Class: DoctorModelAdditionalTest
 *
 * Mô tả: Kiểm thử bổ sung các chức năng của lớp DoctorModel, tập trung vào:
 * - Các phương thức chưa được test đầy đủ
 * - Phát hiện lỗi trong code của developer
 * - Tăng độ phủ code
 *
 * @package    UnitTest
 * @subpackage Models
 * @author     Tester
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class DoctorModelAdditionalTest extends DatabaseTestCase
{
    /**
     * @var DoctorModel Đối tượng model bác sĩ dùng trong test
     */
    protected $doctorModel;

    /**
     * Thiết lập trước mỗi test case
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/DoctorModel.php';
        $this->doctorModel = new DoctorModel();
    }

    /**
     * Ghi log tiêu đề phần test
     *
     * @param string $title Tiêu đề phần test
     */
    private function logSection($title)
    {
        fwrite(STDOUT, "\n" . str_repeat("=", 70) . "\n");
        fwrite(STDOUT, "🔍 TEST CASE: {$title}\n");
        fwrite(STDOUT, str_repeat("=", 70) . "\n");
    }

    /**
     * Ghi log bước test
     *
     * @param string $description Mô tả bước test
     * @param string|null $expected Kết quả mong đợi
     */
    private function logStep($description, $expected = null)
    {
        fwrite(STDOUT, "\n📋 BƯỚC TEST: {$description}\n");
        if ($expected) {
            fwrite(STDOUT, "  ⏩ Kết quả mong đợi: {$expected}\n");
        }
    }

    /**
     * Ghi log kết quả test
     *
     * @param bool $success Kết quả test (true/false)
     * @param string $message Thông báo kết quả
     */
    private function logResult($success, $message)
    {
        $icon = $success ? "✅" : "❌";
        $status = $success ? "THÀNH CÔNG" : "THẤT BẠI";
        
        fwrite(STDOUT, "  📊 Kết quả thực tế: {$message}\n");
        fwrite(STDOUT, "  {$icon} Trạng thái: {$status}\n");
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
     * Test Case DOC_INSERT_01: Kiểm tra phương thức insert
     * Mục tiêu: Kiểm tra phương thức insert với các trường hợp khác nhau
     * Input: Dữ liệu bác sĩ hợp lệ và không hợp lệ
     * Expected output: Bác sĩ được thêm thành công hoặc thất bại đúng theo kỳ vọng
     */
    public function testInsert()
    {
        $this->logSection("DOC_INSERT_01: Kiểm tra phương thức insert");
        $doctorId = null;

        try {
            // Test 1: Insert với dữ liệu hợp lệ
            $this->logStep("DOC_INSERT_01.1: Insert với dữ liệu hợp lệ", "Bác sĩ được thêm thành công với ID > 0");
            
            // Tạo dữ liệu kiểm thử
            $data = $this->createTestDoctor();
            
            // Set dữ liệu vào model
            foreach ($data as $field => $value) {
                $this->doctorModel->set($field, $value);
            }
            
            // Thực hiện insert và kiểm tra
            $doctorId = $this->doctorModel->insert();
            $insertSuccess = $doctorId > 0;
            
            // Kiểm tra dữ liệu đã được lưu trong DB
            if ($insertSuccess) {
                $this->assertRecordExists(TABLE_PREFIX.TABLE_DOCTORS, ['id' => $doctorId]);
                
                // Kiểm tra từng trường dữ liệu
                foreach ($data as $field => $value) {
                    $this->assertEquals($value, $this->doctorModel->get($field), "Trường {$field} không khớp");
                }
            }
            
            $this->logResult($insertSuccess, 
                "Insert thành công: " . ($insertSuccess ? "Có" : "Không") . 
                ", ID: " . ($insertSuccess ? $doctorId : "N/A"));
            
            // Test 2: Insert khi đối tượng đã tồn tại (isAvailable = true)
            $this->logStep("DOC_INSERT_01.2: Insert khi đối tượng đã tồn tại", "Phải trả về false");
            
            // Đánh dấu đối tượng là đã tồn tại
            $this->doctorModel->markAsAvailable();
            
            // Thực hiện insert và kiểm tra
            $result = $this->doctorModel->insert();
            $expectedFalse = $result === false;
            
            $this->logResult($expectedFalse, 
                "Insert khi đã tồn tại trả về: " . ($result === false ? "false (OK)" : $result . " (LỖI)"));
            
            // Test 3: Kiểm tra extendDefaults được gọi trong insert
            $this->logStep("DOC_INSERT_01.3: Kiểm tra extendDefaults được gọi trong insert", "Các trường mặc định phải được thiết lập");
            
            // Tạo đối tượng mới
            $newDoctor = new DoctorModel();
            
            // Chỉ set một số trường, để các trường khác dùng giá trị mặc định
            $newDoctor->set("email", "minimal_" . time() . "@example.com");
            $newDoctor->set("name", "Minimal Doctor");
            
            // Thực hiện insert
            $newId = $newDoctor->insert();
            $insertMinimalSuccess = $newId > 0;
            
            if ($insertMinimalSuccess) {
                // Kiểm tra các trường mặc định
                $this->assertNotEmpty($newDoctor->get("create_at"), "create_at phải được thiết lập");
                $this->assertNotEmpty($newDoctor->get("update_at"), "update_at phải được thiết lập");
                $this->assertEquals("admin", $newDoctor->get("role"), "role phải được thiết lập mặc định là admin");
                $this->assertEquals("1", $newDoctor->get("active"), "active phải được thiết lập mặc định là 1");
                
                // Xóa bản ghi này sau khi test
                $newDoctor->delete();
            }
            
            $this->logResult($insertMinimalSuccess, 
                "Insert với dữ liệu tối thiểu: " . ($insertMinimalSuccess ? "Thành công" : "Thất bại") . 
                ", Các trường mặc định được thiết lập: " . ($insertMinimalSuccess ? "Có" : "Không"));
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Lỗi khi kiểm tra phương thức insert: " . $e->getMessage());
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
     * Test Case DOC_ISADMIN_02: Kiểm tra phương thức isAdmin
     * Mục tiêu: Kiểm tra phương thức isAdmin với các vai trò khác nhau
     * Input: Các vai trò khác nhau (developer, admin, member)
     * Expected output: Quyền admin được phân đúng theo vai trò
     */
    public function testIsAdmin()
    {
        $this->logSection("DOC_ISADMIN_02: Kiểm tra phương thức isAdmin");
        $doctorIds = [];

        try {
            // Test 1: Vai trò developer
            $this->logStep("DOC_ISADMIN_02.1: Kiểm tra vai trò developer", "Phải có quyền admin");
            
            $developerData = $this->createTestDoctor(['role' => 'developer']);
            $developerId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $developerData);
            $doctorIds[] = $developerId;
            
            $developer = new DoctorModel($developerId);
            $developerIsAdmin = $developer->isAdmin();
            
            $this->logResult($developerIsAdmin, 
                "Vai trò developer có quyền admin: " . ($developerIsAdmin ? "Có (OK)" : "Không (LỖI)"));
            
            // Test 2: Vai trò admin
            $this->logStep("DOC_ISADMIN_02.2: Kiểm tra vai trò admin", "Phải có quyền admin");
            
            $adminData = $this->createTestDoctor(['role' => 'admin']);
            $adminId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $adminData);
            $doctorIds[] = $adminId;
            
            $admin = new DoctorModel($adminId);
            $adminIsAdmin = $admin->isAdmin();
            
            $this->logResult($adminIsAdmin, 
                "Vai trò admin có quyền admin: " . ($adminIsAdmin ? "Có (OK)" : "Không (LỖI)"));
            
            // Test 3: Vai trò member
            $this->logStep("DOC_ISADMIN_02.3: Kiểm tra vai trò member", "Không được có quyền admin");
            
            $memberData = $this->createTestDoctor(['role' => 'member']);
            $memberId = $this->insertFixture(TABLE_PREFIX.TABLE_DOCTORS, $memberData);
            $doctorIds[] = $memberId;
            
            $member = new DoctorModel($memberId);
            $memberIsAdmin = $member->isAdmin();
            
            $this->logResult(!$memberIsAdmin, 
                "Vai trò member có quyền admin: " . ($memberIsAdmin ? "Có (LỖI)" : "Không (OK)"));
            
            // Test 4: Đối tượng không khả dụng
            $this->logStep("DOC_ISADMIN_02.4: Kiểm tra đối tượng không khả dụng", "Không được có quyền admin");
            
            $invalidDoctor = new DoctorModel(999999); // ID không tồn tại
            $invalidIsAdmin = $invalidDoctor->isAdmin();
            
            $this->logResult(!$invalidIsAdmin, 
                "Đối tượng không khả dụng có quyền admin: " . ($invalidIsAdmin ? "Có (LỖI)" : "Không (OK)"));
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Lỗi khi kiểm tra phương thức isAdmin: " . $e->getMessage());
        } finally {
            // Đảm bảo dọn dẹp dữ liệu test
            foreach ($doctorIds as $id) {
                $doctor = new DoctorModel($id);
                if ($doctor->isAvailable()) {
                    $doctor->delete();
                }
            }
        }
    }
}
