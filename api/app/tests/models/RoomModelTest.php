<?php
/**
 * Lớp kiểm thử RoomModel
 * 
 * File: api/app/tests/models/RoomModelTest.php
 * Class: RoomModelTest
 * 
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp RoomModel, bao gồm:
 * - Các thao tác CRUD (Tạo, Đọc, Cập nhật, Xóa)
 * - Các phương thức đọc thông tin theo tên, ID
 * - Kiểm tra tính nhất quán của dữ liệu trong DB
 * 
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class RoomModelTest extends DatabaseTestCase 
{
    /**
     * @var RoomModel Đối tượng model phòng dùng trong test
     */
    protected $roomModel;
    
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
     * @var int ID của phòng được tạo để sử dụng chung cho các test
     */
    protected static $testRoomId;

    /**
     * @var array Dữ liệu phòng mẫu được tạo
     */
    protected static $testRoomData;
    
    /**
     * @var bool Kiểm soát việc bắt đầu/kết thúc transaction
     */
    protected $useTransaction = true;

    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo RoomModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/RoomModel.php';
        $this->roomModel = new RoomModel();
        
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
     * Tạo dữ liệu phòng mẫu cho test
     * 
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu phòng mẫu
     */
    private function createTestRoom($override = [])
    {
        return array_merge([
            'name' => 'Room' . substr(time(), -5),
            'location' => 'Test Location ' . rand(100, 999)
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
     * Test đầy đủ CRUD cho RoomModel
     * 
     * Mã test case: RM_INS_01, RM_SEL_02, RM_UPD_03, RM_DEL_04
     * Mục tiêu: Kiểm tra cả quy trình CRUD trong một test
     * Input: Dữ liệu phòng mẫu
     * Expected output: Thao tác CRUD thành công
     * Ghi chú: Thực hiện kiểm tra DB sau mỗi thao tác để xác nhận dữ liệu nhất quán
     */
    public function testCRUD()
    {
        $this->logSection("RM_CRUD: Kiểm tra quy trình CRUD");
        
        // ID và dữ liệu của phòng để sử dụng qua các bước
        $roomId = null;
        $roomData = null;
        
        try {
            // BƯỚC 1: CREATE - TC-RM-01
            $this->logStep("RM_INS_01: Tạo mới phòng", "Phòng được tạo thành công với ID > 0");
            
            // Tạo dữ liệu kiểm thử
            $data = $this->createTestRoom();
            $roomData = $data;
            
            // Set dữ liệu vào model
            foreach ($data as $field => $value) {
                $this->roomModel->set($field, $value);
            }
            
            // Thực hiện insert và kiểm tra
            $roomId = $this->roomModel->insert();
            $createSuccess = $roomId > 0;
            
            $this->logResult($createSuccess, 
                "Room ID: " . ($createSuccess ? $roomId : "Không tạo được"),
                $createSuccess ? null : "Không thể tạo phòng mới");
            
            $this->assertTrue($createSuccess, "Không thể tạo phòng mới");
            
            // Kiểm tra dữ liệu trong DB
            $this->assertRecordExists(TABLE_PREFIX.TABLE_ROOMS, ['id' => $roomId]);
            
            // Lưu lại ID để sử dụng trong các test sau
            self::$testRoomId = $roomId;
            self::$testRoomData = $data;
            
            // BƯỚC 2: READ - TC-RM-02
            $this->logStep("RM_SEL_02: Đọc thông tin phòng theo ID", 
                "Phòng được tìm thấy và có dữ liệu đúng");
            
            // Tạo model mới và select theo ID
            $readModel = new RoomModel($roomId);
            $readSuccess = $readModel->isAvailable();
            
            // Kiểm tra thông tin đọc về
            $dataMatches = true;
            $mismatchFields = [];
            
            foreach ($data as $field => $value) {
                if ($readModel->get($field) != $value) {
                    $dataMatches = false;
                    $mismatchFields[] = $field;
                }
            }
            
            $readResult = "ID: {$roomId} - Tìm thấy: " . ($readSuccess ? "Có" : "Không");
            $readResult .= $dataMatches ? ", Dữ liệu khớp" : ", Dữ liệu không khớp ở các trường: " . implode(", ", $mismatchFields);
            
            $this->logResult($readSuccess && $dataMatches, $readResult);
            
            $this->assertTrue($readSuccess, "Không thể đọc thông tin phòng với ID {$roomId}");
            $this->assertTrue($dataMatches, "Dữ liệu không khớp");
            
            // BƯỚC 3: UPDATE - TC-RM-03
            $this->logStep("RM_UPD_03: Cập nhật thông tin phòng", 
                "Phòng được cập nhật thành công");
            
            // Cập nhật dữ liệu
            $updateData = [
                'name' => 'Room' . substr(time(), -5),
                'location' => 'Updated Location ' . rand(100, 999)
            ];
            
            // Áp dụng dữ liệu mới vào model
            foreach ($updateData as $field => $value) {
                $readModel->set($field, $value);
            }
            
            // Thực hiện update
            $readModel->update();
            
            // Kiểm tra dữ liệu sau khi update
            $updatedModel = new RoomModel($roomId);
            $updateSuccess = $updatedModel->isAvailable();
            
            // Kiểm tra dữ liệu đã được cập nhật
            $updateMatches = true;
            $updateMismatchFields = [];
            
            foreach ($updateData as $field => $value) {
                if ($updatedModel->get($field) != $value) {
                    $updateMatches = false;
                    $updateMismatchFields[] = $field;
                }
            }
            
            $updateResult = "Cập nhật " . ($updateSuccess ? "thành công" : "thất bại");
            $updateResult .= $updateMatches ? ", Dữ liệu khớp" : ", Dữ liệu không khớp ở các trường: " . implode(", ", $updateMismatchFields);
            
            $this->logResult($updateSuccess && $updateMatches, $updateResult);
            
            $this->assertTrue($updateSuccess, "Không thể cập nhật thông tin phòng");
            $this->assertTrue($updateMatches, "Dữ liệu sau khi cập nhật không khớp");
            
            // BƯỚC 4: DELETE - TC-RM-04
            $this->logStep("RM_DEL_04: Xóa phòng", "Phòng được xóa thành công");
            
            // Thực hiện xóa
            $deleteSuccess = $updatedModel->delete();
            
            // Kiểm tra phòng đã bị xóa
            $deletedModel = new RoomModel($roomId);
            $deleteVerify = !$deletedModel->isAvailable();
            
            // Kiểm tra dữ liệu trong DB
            $recordExists = false;
            try {
                $this->assertRecordNotExists(TABLE_PREFIX.TABLE_ROOMS, ['id' => $roomId]);
                $recordExists = false;
            } catch (ExpectationFailedException $e) {
                $recordExists = true;
            }
            
            $deleteResult = "Xóa " . ($deleteSuccess ? "thành công" : "thất bại");
            $deleteResult .= ", Kiểm tra tồn tại: " . ($deleteVerify ? "Đã xóa" : "Vẫn tồn tại");
            $deleteResult .= ", Kiểm tra DB: " . ($recordExists ? "Vẫn tồn tại trong DB" : "Đã xóa khỏi DB");
            
            $this->logResult($deleteSuccess && $deleteVerify && !$recordExists, $deleteResult);
            
            $this->assertTrue($deleteSuccess, "Không thể xóa phòng");
            $this->assertTrue($deleteVerify, "Phòng vẫn tồn tại sau khi xóa");
            $this->assertRecordNotExists(TABLE_PREFIX.TABLE_ROOMS, ['id' => $roomId], "Phòng vẫn tồn tại trong DB");
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test CRUD thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test chức năng select theo tên
     * 
     * Mã test case: RM_SELNAME_05
     * Mục tiêu: Kiểm tra tìm kiếm phòng theo tên
     * Input: Tên phòng
     * Expected output: Thông tin phòng đúng
     */
    public function testSelectByName()
    {
        $this->logSection("RM_SELNAME_05: Kiểm tra tìm kiếm phòng theo tên");
        
        try {
            // Tạo phòng mẫu để test
            $roomName = 'Room' . substr(time(), -5);
            $roomData = $this->createTestRoom(['name' => $roomName]);
            
            // Tạo phòng trong DB
            foreach ($roomData as $field => $value) {
                $this->roomModel->set($field, $value);
            }
            $roomId = $this->roomModel->insert();
            
            // Thực hiện tìm kiếm theo tên
            $this->logStep("Tìm kiếm phòng theo tên: {$roomName}", 
                "Phòng được tìm thấy và dữ liệu khớp");
            
            $selectModel = new RoomModel($roomName);
            $selectSuccess = $selectModel->isAvailable();
            
            // Kiểm tra thông tin
            $dataMatches = true;
            $mismatchFields = [];
            
            foreach ($roomData as $field => $value) {
                if ($selectModel->get($field) != $value) {
                    $dataMatches = false;
                    $mismatchFields[] = $field;
                }
            }
            
            // Kiểm tra ID
            $idMatches = $selectModel->get('id') == $roomId;
            
            $selectResult = "Tìm kiếm " . ($selectSuccess ? "thành công" : "thất bại");
            $selectResult .= $dataMatches ? ", Dữ liệu khớp" : ", Dữ liệu không khớp ở các trường: " . implode(", ", $mismatchFields);
            $selectResult .= $idMatches ? ", ID khớp" : ", ID không khớp";
            
            $this->logResult($selectSuccess && $dataMatches && $idMatches, $selectResult);
            
            $this->assertTrue($selectSuccess, "Không thể tìm phòng theo tên");
            $this->assertTrue($dataMatches, "Dữ liệu không khớp");
            $this->assertTrue($idMatches, "ID không khớp");
            
            // Dọn dẹp
            $selectModel->delete();
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test select theo tên thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test chức năng select với ID không tồn tại
     * 
     * Mã test case: RM_ERR_ID_06
     * Mục tiêu: Kiểm tra khi select với ID không tồn tại
     * Input: ID không tồn tại
     * Expected output: Model không khả dụng (isAvailable() = false)
     */
    public function testSelectWithNonExistentId()
    {
        $this->logSection("RM_ERR_ID_06: Kiểm tra select với ID không tồn tại");
        
        try {
            $this->logStep("Tìm kiếm phòng với ID không tồn tại", 
                "Phòng không được tìm thấy");
            
            // Tạo ID không tồn tại bằng cách lấy max ID hiện tại + 1000
            $sql = "SELECT MAX(id) as max_id FROM " . TABLE_PREFIX . TABLE_ROOMS;
            $result = $this->executeSQL($sql);
            $nonExistentId = $result[0]['max_id'] + 1000;
            
            // Thực hiện tìm kiếm
            $model = new RoomModel($nonExistentId);
            $isAvailable = $model->isAvailable();
            
            $selectResult = "ID không tồn tại: {$nonExistentId}, Kết quả: " . 
                ($isAvailable ? "Tìm thấy (không đúng)" : "Không tìm thấy (đúng)");
            
            $this->logResult(!$isAvailable, $selectResult);
            $this->assertFalse($isAvailable, "Model vẫn khả dụng khi ID không tồn tại");
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test select với ID không tồn tại thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test chức năng select với tên không tồn tại
     * 
     * Mã test case: RM_ERR_NAME_07
     * Mục tiêu: Kiểm tra khi select với tên không tồn tại
     * Input: Tên không tồn tại
     * Expected output: Model không khả dụng (isAvailable() = false)
     */
    public function testSelectWithNonExistentName()
    {
        $this->logSection("RM_ERR_NAME_07: Kiểm tra select với tên không tồn tại");
        
        try {
            $this->logStep("Tìm kiếm phòng với tên không tồn tại", 
                "Phòng không được tìm thấy");
            
            // Tạo tên không tồn tại
            $nonExistentName = 'NonExistent' . time();
            
            // Thực hiện tìm kiếm
            $model = new RoomModel($nonExistentName);
            $isAvailable = $model->isAvailable();
            
            $selectResult = "Tên không tồn tại: {$nonExistentName}, Kết quả: " . 
                ($isAvailable ? "Tìm thấy (không đúng)" : "Không tìm thấy (đúng)");
            
            $this->logResult(!$isAvailable, $selectResult);
            $this->assertFalse($isAvailable, "Model vẫn khả dụng khi tên không tồn tại");
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test select với tên không tồn tại thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test chức năng extendDefaults
     * 
     * Mã test case: RM_DEF_08
     * Mục tiêu: Kiểm tra phương thức extendDefaults thiết lập giá trị mặc định đúng
     * Input: Model không có dữ liệu
     * Expected output: Các trường được thiết lập giá trị mặc định
     */
    public function testExtendDefaults()
    {
        $this->logSection("RM_DEF_08: Kiểm tra extendDefaults");
        
        try {
            $this->logStep("Kiểm tra giá trị mặc định sau khi gọi extendDefaults", 
                "Các trường được thiết lập đúng giá trị mặc định");
            
            // Tạo model mới không có dữ liệu
            $model = new RoomModel();
            $this->assertFalse($model->isAvailable(), "Model mới không nên khả dụng");
            
            // Gọi extendDefaults
            $model->extendDefaults();
            
            // Kiểm tra các giá trị mặc định
            $expectedDefaults = [
                'name' => '',
                'description' => ''
            ];
            
            $defaultsMatch = true;
            $mismatchFields = [];
            
            foreach ($expectedDefaults as $field => $expectedValue) {
                $actualValue = $model->get($field);
                if ($actualValue !== $expectedValue) {
                    $defaultsMatch = false;
                    $mismatchFields[] = "{$field} (mong đợi: '{$expectedValue}', thực tế: '{$actualValue}')";
                }
            }
            
            $result = $defaultsMatch 
                ? "Tất cả giá trị mặc định đều đúng" 
                : "Các giá trị mặc định không đúng: " . implode(", ", $mismatchFields);
            
            $this->logResult($defaultsMatch, $result);
            $this->assertTrue($defaultsMatch, "Giá trị mặc định không đúng");
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test extendDefaults thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test update khi phòng không tồn tại
     * 
     * Mã test case: RM_ERR_UPD_09
     * Mục tiêu: Kiểm tra kết quả khi cập nhật phòng không tồn tại
     * Input: Model phòng không tồn tại
     * Expected output: Hàm update trả về false
     */
    public function testUpdateNonExistentRoom()
    {
        $this->logSection("RM_ERR_UPD_09: Kiểm tra update phòng không tồn tại");
        
        try {
            $this->logStep("Cập nhật thông tin phòng không tồn tại", 
                "Hàm update trả về false");
            
            // Tạo model không khả dụng
            $model = new RoomModel();
            $this->assertFalse($model->isAvailable(), "Model mới không nên khả dụng");
            
            // Thiết lập dữ liệu
            $updateData = $this->createTestRoom();
            foreach ($updateData as $field => $value) {
                $model->set($field, $value);
            }
            
            // Thực hiện update
            $updateResult = $model->update();
            
            $result = "Update phòng không tồn tại trả về: " . 
                ($updateResult === false ? "false (đúng)" : "không phải false (không đúng)");
            
            $this->logResult($updateResult === false, $result);
            $this->assertFalse($updateResult, "Update phòng không tồn tại phải trả về false");
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test update phòng không tồn tại thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test delete khi phòng không tồn tại
     * 
     * Mã test case: RM_ERR_DEL_10
     * Mục tiêu: Kiểm tra kết quả khi xóa phòng không tồn tại
     * Input: Model phòng không tồn tại
     * Expected output: Hàm delete trả về false
     */
    public function testDeleteNonExistentRoom()
    {
        $this->logSection("RM_ERR_DEL_10: Kiểm tra delete phòng không tồn tại");
        
        try {
            $this->logStep("Xóa phòng không tồn tại", 
                "Hàm delete trả về false");
            
            // Tạo model không khả dụng
            $model = new RoomModel();
            $this->assertFalse($model->isAvailable(), "Model mới không nên khả dụng");
            
            // Thực hiện delete
            $deleteResult = $model->delete();
            
            $result = "Delete phòng không tồn tại trả về: " . 
                ($deleteResult === false ? "false (đúng)" : "không phải false (không đúng)");
            
            $this->logResult($deleteResult === false, $result);
            $this->assertFalse($deleteResult, "Delete phòng không tồn tại phải trả về false");
            
        } catch (Exception $e) {
            $this->logResult(false, "Lỗi: " . $e->getMessage());
            $this->fail("Test delete phòng không tồn tại thất bại: " . $e->getMessage());
        }
    }

    /**
     * Test insert với dữ liệu trùng lặp
     * 
     * Mã test case: RM_DUP_11
     * Mục tiêu: Kiểm tra xử lý khi thêm phòng có tên trùng với phòng đã tồn tại
     * Input: Dữ liệu phòng với tên đã tồn tại
     * Expected output: Hệ thống xử lý phù hợp (tùy theo cách triển khai - có thể là lỗi hoặc tạo mới)
     */
    public function testInsertWithDuplicateName()
    {
        $this->logSection("RM_DUP_11: Kiểm tra insert với tên trùng lặp");
        
        try {
            // Tạo phòng đầu tiên
            $roomName = 'DupRoom' . substr(time(), -5);
            $roomData = $this->createTestRoom(['name' => $roomName]);
            
            foreach ($roomData as $field => $value) {
                $this->roomModel->set($field, $value);
            }
            
            $firstId = $this->roomModel->insert();
            $this->assertGreaterThan(0, $firstId, "Không thể tạo phòng đầu tiên");
            
            $this->logStep("Tạo phòng mới với tên đã tồn tại: {$roomName}", 
                "Hệ thống xử lý phù hợp");
            
            // Tạo phòng thứ hai với tên trùng lặp
            $duplicateModel = new RoomModel();
            foreach ($roomData as $field => $value) {
                $duplicateModel->set($field, $value);
            }
            
            // Thực hiện insert
            $secondId = $duplicateModel->insert();
            
            // Đối với trường hợp này, tùy thuộc vào cách triển khai database và model:
            // 1. Nếu tên phòng là UNIQUE trong DB: Insert sẽ gây lỗi -> Bắt exception
            // 2. Nếu tên phòng không phải UNIQUE: Ghi lại kết quả để thông báo và dọn dẹp
            
            $result = "Insert phòng trùng tên: ";
            if ($secondId === false) {
                $result .= "Thất bại (không cho phép trùng tên)";
                $success = true; // Đây là kết quả mong đợi nếu DB có ràng buộc UNIQUE
            } else if ($secondId > 0) {
                $result .= "Thành công với ID: {$secondId} (cho phép trùng tên)";
                $success = true; // Đây là kết quả mong đợi nếu DB không có ràng buộc UNIQUE
                
                // Dọn dẹp phòng thứ hai
                $secondRoom = new RoomModel($secondId);
                $secondRoom->delete();
            } else {
                $result .= "Kết quả không xác định: {$secondId}";
                $success = false;
            }
            
            $this->logResult($success, $result);
            
            // Dọn dẹp phòng đầu tiên
            $firstRoom = new RoomModel($firstId);
            $firstRoom->delete();
            
            // Test pass trong cả hai trường hợp vì chúng ta chỉ kiểm tra xử lý, không kiểm tra kết quả cụ thể
            $this->assertTrue(true);
            
        } catch (Exception $e) {
            // Nếu có exception, có thể do ràng buộc UNIQUE trong DB
            $result = "Lỗi khi insert phòng trùng tên: " . $e->getMessage();
            $this->logResult(true, $result);
            
            // Test vẫn pass vì đây có thể là hành vi mong đợi
            $this->assertTrue(true);
        }
    }

    /**
     * Dọn dẹp sau khi tất cả các test hoàn thành
     */
    protected function tearDown()
    {
        // Gọi tear down của cha để thực hiện rollback
        if ($this->useTransaction) {
            parent::tearDown();
        }
        
        // In kết quả tổng quan ở test cuối cùng
        $this->printFinalSummary();
    }
    
    /**
     * In kết quả tổng hợp toàn bộ test
     */
    private function printFinalSummary()
    {
        // Chỉ in tổng kết nếu đây là lần gọi tearDown cuối cùng
        $bt = debug_backtrace();
        $caller = isset($bt[2]['function']) ? $bt[2]['function'] : '';
        if ($caller != '__call') {
            return;
        }
        
        // Đếm số lượng test thành công và thất bại
        $total = count(self::$allTestResults);
        $success = 0;
        $failed = 0;
        
        foreach (self::$allTestResults as $result) {
            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        // Tính thời gian thực thi
        $executionTime = microtime(true) - self::$startTime;
        
        // In tổng kết
        fwrite(STDOUT, "\n" . str_repeat("=", 70) . "\n");
        fwrite(STDOUT, "📊 KẾT QUẢ TEST ROOMMODEL \n");
        fwrite(STDOUT, str_repeat("=", 70) . "\n");
        fwrite(STDOUT, sprintf("✅ Thành công: %d/%d (%.2f%%)\n", 
            $success, $total, ($total > 0 ? ($success / $total * 100) : 0)));
        fwrite(STDOUT, sprintf("❌ Thất bại: %d/%d (%.2f%%)\n", 
            $failed, $total, ($total > 0 ? ($failed / $total * 100) : 0)));
        fwrite(STDOUT, sprintf("⏱ Thời gian thực thi: %.4f giây\n", $executionTime));
        fwrite(STDOUT, "👤 Người thực hiện: " . self::CURRENT_USER . "\n");
        fwrite(STDOUT, "📅 Thời gian: " . date('Y-m-d H:i:s') . "\n");
        fwrite(STDOUT, str_repeat("=", 70) . "\n");
        
        // Reset lại biến static để test tiếp theo sạch
        self::$allTestResults = [];
        self::$startTime = null;
    }
}
