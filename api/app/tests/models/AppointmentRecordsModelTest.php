<?php
/**
 * Lớp kiểm thử AppointmentRecordsModel
 *
 * File: api/app/tests/models/AppointmentRecordsModelTest.php
 * Class: AppointmentRecordsModelTest
 *
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp AppointmentRecordsModel, bao gồm:
 * - Khởi tạo đối tượng
 * - Phân trang dữ liệu
 * - Tìm kiếm dữ liệu
 * - Lấy dữ liệu dưới dạng model
 *
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class AppointmentRecordsModelTest extends DatabaseTestCase
{
    /**
     * @var AppointmentRecordsModel Đối tượng model danh sách bản ghi cuộc hẹn dùng trong test
     */
    protected $appointmentRecordsModel;

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
     * @var bool Kiểm soát việc bắt đầu/kết thúc transaction
     */
    protected $useTransaction = true;

    /**
     * Thiết lập trước mỗi test case
     * Khởi tạo AppointmentRecordsModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/AppointmentRecordsModel.php';
        $this->appointmentRecordsModel = new AppointmentRecordsModel();

        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }

        // Tên bảng đầy đủ với prefix
        $fullTableName = TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS;

        // Khởi tạo bảng test - sử dụng tên bảng đầy đủ
        $this->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS `{$fullTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `appointment_id` varchar(255) NOT NULL,
                `reason` varchar(255) NOT NULL,
                `description` text NOT NULL,
                `status_before` varchar(255) NOT NULL,
                `status_after` varchar(255) NOT NULL,
                `create_at` varchar(255) NOT NULL,
                `update_at` varchar(255) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Xóa dữ liệu cũ từ bảng test (nếu có)
        $this->executeQuery("TRUNCATE TABLE `{$fullTableName}`");
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
     * Tạo dữ liệu bản ghi cuộc hẹn mẫu cho test
     *
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu bản ghi cuộc hẹn mẫu
     */
    private function createTestAppointmentRecord($override = [])
    {
        $timestamp = time();
        return array_merge([
            'appointment_id' => 'AP_' . $timestamp,
            'reason' => 'Reason_' . $timestamp,
            'description' => 'Description for appointment record ' . $timestamp,
            'status_before' => 'pending',
            'status_after' => 'confirmed',
            'create_at' => date('Y-m-d H:i:s'),
            'update_at' => date('Y-m-d H:i:s')
        ], $override);
    }

    /**
     * Tạo nhiều bản ghi cuộc hẹn mẫu cho test
     *
     * @param int $count Số lượng bản ghi cần tạo
     * @return array Mảng các ID của bản ghi đã tạo
     */
    private function createMultipleTestRecords($count = 10)
    {
        $tableName = TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS;
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $recordData = $this->createTestAppointmentRecord([
                'appointment_id' => 'AP_BATCH_' . time() . '_' . $i,
                'reason' => 'Test Reason ' . $i,
                'description' => 'Test Description ' . $i,
            ]);

            $sql = "INSERT INTO `{$tableName}` (appointment_id, reason, description, status_before, status_after, create_at, update_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $recordData['appointment_id'],
                $recordData['reason'],
                $recordData['description'],
                $recordData['status_before'],
                $recordData['status_after'],
                $recordData['create_at'],
                $recordData['update_at']
            ]);

            $ids[] = $this->pdo->lastInsertId();
        }

        return $ids;
    }

    /**
     * Test case TC-01: Kiểm tra khởi tạo đối tượng AppointmentRecordsModel
     * Test case ARECS_CONS_01: Kiểm tra khởi tạo đối tượng AppointmentRecordsModel
     */
    public function testConstructor()
    {
        $this->logSection("ARECS_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng AppointmentRecordsModel", "Đối tượng được tạo thành công");

        // Kiểm tra đối tượng được khởi tạo
        $isInstanceOfRecords = $this->appointmentRecordsModel instanceof AppointmentRecordsModel;
        $isInstanceOfDataList = $this->appointmentRecordsModel instanceof DataList;

        $this->logResult($isInstanceOfRecords && $isInstanceOfDataList,
            "Instance created: " . ($isInstanceOfRecords ? "Yes" : "No") .
            ", Extends DataList: " . ($isInstanceOfDataList ? "Yes" : "No"));

        $this->assertInstanceOf(AppointmentRecordsModel::class, $this->appointmentRecordsModel);
        $this->assertInstanceOf(DataList::class, $this->appointmentRecordsModel);

        // Kiểm tra query đã được thiết lập
        $query = $this->appointmentRecordsModel->getQuery();
        $hasQuery = !is_null($query);

        $this->logResult($hasQuery,
            "Query initialized: " . ($hasQuery ? "Yes" : "No"));

        $this->assertNotNull($query);
    }

    /**
     * Test case TC-02: Kiểm tra phương thức fetchData
     * Test case ARECS_FETCH_02: Kiểm tra phương thức fetchData
     */
    public function testFetchData()
    {
        $this->logSection("ARECS_FETCH_02: Kiểm tra phương thức fetchData");
        $this->logStep("Tạo dữ liệu test và lấy danh sách bản ghi", "Danh sách bản ghi được lấy thành công");

        // Tạo dữ liệu test
        $recordIds = $this->createMultipleTestRecords(5);
        $this->assertCount(5, $recordIds);

        // Lấy danh sách bản ghi
        $this->appointmentRecordsModel->fetchData();
        $data = $this->appointmentRecordsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $hasData = $dataCount > 0;

        $this->logResult($hasData,
            "Data fetched: " . ($hasData ? "Yes" : "No") .
            ", Record count: " . $dataCount);

        $this->assertTrue($hasData);
        $this->assertEquals(5, $dataCount);
    }

    /**
     * Test case TC-03: Kiểm tra phương thức paginate
     * Test case ARECS_PAGE_03: Kiểm tra phương thức paginate
     */
    public function testPaginate()
    {
        $this->logSection("ARECS_PAGE_03: Kiểm tra phương thức paginate");
        $this->logStep("Tạo dữ liệu test và phân trang", "Dữ liệu được phân trang thành công");

        // Tạo dữ liệu test
        $recordIds = $this->createMultipleTestRecords(10);
        $this->assertCount(10, $recordIds);

        // Thiết lập phân trang
        $this->appointmentRecordsModel->setPageSize(3);
        $this->appointmentRecordsModel->setPage(2);

        // Lấy danh sách bản ghi
        $this->appointmentRecordsModel->fetchData();
        $data = $this->appointmentRecordsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $totalCount = $this->appointmentRecordsModel->getTotalCount();
        $pageCount = $this->appointmentRecordsModel->getPageCount();
        $currentPage = $this->appointmentRecordsModel->getPage();

        // Kiểm tra xem có dữ liệu không
        $hasData = $dataCount > 0;

        $this->logResult($hasData,
            "Pagination successful: " .
            "Items on page: " . $dataCount . ", " .
            "Total items: " . $totalCount . ", " .
            "Total pages: " . $pageCount . ", " .
            "Current page: " . $currentPage);

        $this->assertTrue($hasData);
        $this->assertGreaterThan(0, $totalCount);
        $this->assertGreaterThan(0, $pageCount);
        $this->assertEquals(2, $currentPage);
    }

    /**
     * Test case TC-04: Kiểm tra phương thức getData
     * Test case ARECS_MODEL_04: Kiểm tra phương thức getData
     */
    public function testGetDataAs()
    {
        $this->logSection("ARECS_MODEL_04: Kiểm tra phương thức getData");
        $this->logStep("Lấy dữ liệu từ model", "Dữ liệu được lấy thành công");

        // Tạo dữ liệu test
        $recordIds = $this->createMultipleTestRecords(3);
        $this->assertCount(3, $recordIds);

        // Lấy danh sách bản ghi
        $this->appointmentRecordsModel->fetchData();
        $data = $this->appointmentRecordsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $hasData = $dataCount > 0;
        $hasCorrectFields = false;

        if ($hasData) {
            $record = $data[0];
            $hasCorrectFields = isset($record->appointment_id) &&
                               isset($record->reason) &&
                               isset($record->description) &&
                               isset($record->status_before) &&
                               isset($record->status_after);
        }

        $this->logResult($hasData && $hasCorrectFields,
            "Data retrieved: " . ($hasData ? "Yes" : "No") .
            ", Record count: " . $dataCount .
            ", Has correct fields: " . ($hasCorrectFields ? "Yes" : "No"));

        $this->assertTrue($hasData);
        $this->assertTrue($hasCorrectFields);
        $this->assertEquals(3, $dataCount);
    }

    /**
     * Test case TC-05: Kiểm tra phương thức where
     * Test case ARECS_WHERE_05: Kiểm tra phương thức where
     */
    public function testWhere()
    {
        $this->logSection("ARECS_WHERE_05: Kiểm tra phương thức where");
        $this->logStep("Lọc dữ liệu theo điều kiện", "Dữ liệu được lọc thành công");

        // Tạo dữ liệu test
        $timestamp = time();
        $specialStatus = "special_status_" . $timestamp;

        // Tạo 5 bản ghi thông thường
        $this->createMultipleTestRecords(5);

        // Tạo 3 bản ghi với status_after đặc biệt
        for ($i = 0; $i < 3; $i++) {
            $recordData = $this->createTestAppointmentRecord([
                'appointment_id' => 'AP_SPECIAL_' . $timestamp . '_' . $i,
                'status_after' => $specialStatus
            ]);

            $tableName = TABLE_PREFIX.TABLE_APPOINTMENT_RECORDS;

            $sql = "INSERT INTO `{$tableName}` (appointment_id, reason, description, status_before, status_after, create_at, update_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $recordData['appointment_id'],
                $recordData['reason'],
                $recordData['description'],
                $recordData['status_before'],
                $recordData['status_after'],
                $recordData['create_at'],
                $recordData['update_at']
            ]);
        }

        // Lọc dữ liệu theo status_after
        $this->appointmentRecordsModel->where("status_after", "=", $specialStatus);
        $this->appointmentRecordsModel->fetchData();
        $data = $this->appointmentRecordsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $allMatch = true;

        foreach ($data as $record) {
            if ($record->status_after !== $specialStatus) {
                $allMatch = false;
                break;
            }
        }

        $this->logResult($allMatch && $dataCount === 3,
            "Filtering successful: " . ($allMatch ? "Yes" : "No") .
            ", Filtered record count: " . $dataCount . " (expected: 3)");

        $this->assertTrue($allMatch);
        $this->assertEquals(3, $dataCount);
    }

    /**
     * Test case TC-06: Kiểm tra phương thức orderBy
     * Test case ARECS_ORDER_06: Kiểm tra phương thức orderBy
     */
    public function testOrderBy()
    {
        $this->logSection("ARECS_ORDER_06: Kiểm tra phương thức orderBy");
        $this->logStep("Sắp xếp dữ liệu theo ID", "Dữ liệu được sắp xếp thành công");

        // Tạo dữ liệu test
        $recordIds = $this->createMultipleTestRecords(5);
        $this->assertCount(5, $recordIds);

        // Sắp xếp dữ liệu theo ID giảm dần
        $this->appointmentRecordsModel->orderBy("id", "DESC");
        $this->appointmentRecordsModel->fetchData();
        $data = $this->appointmentRecordsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $isDescending = true;

        for ($i = 0; $i < $dataCount - 1; $i++) {
            if ($data[$i]->id < $data[$i + 1]->id) {
                $isDescending = false;
                break;
            }
        }

        $this->logResult($isDescending && $dataCount === 5,
            "Ordering successful: " . ($isDescending ? "Yes" : "No") .
            ", Order: Descending, Record count: " . $dataCount);

        $this->assertTrue($isDescending);
        $this->assertEquals(5, $dataCount);
    }

    /**
     * Dọn dẹp sau khi tất cả các test được chạy xong
     */
    protected function tearDown()
    {
        // In tổng kết nếu là test cuối cùng
        $reflection = new ReflectionClass($this);
        $currentTest = $this->getName();
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $testMethods = array_filter($methods, function($method) {
            return strpos($method->name, 'test') === 0;
        });

        $lastMethod = end($testMethods);
        if ($currentTest === $lastMethod->name) {
            $this->printFinalSummary();
        }

        if ($this->useTransaction) {
            parent::tearDown();
        }
    }

    /**
     * In tổng kết cuối cùng sau khi tất cả các test hoàn thành
     */
    private function printFinalSummary()
    {
        // Đếm số lượng test case dựa trên các phương thức test
        $reflection = new ReflectionClass($this);
        $testMethods = array_filter($reflection->getMethods(ReflectionMethod::IS_PUBLIC), function($method) {
            return strpos($method->name, 'test') === 0;
        });
        $totalTestCases = count($testMethods);

        // Nhóm kết quả theo test case
        $testResults = [];
        foreach (self::$allTestResults as $result) {
            $group = $result['group'];
            if (!isset($testResults[$group])) {
                $testResults[$group] = [
                    'success' => true,
                    'results' => []
                ];
            }

            $testResults[$group]['results'][] = $result;

            // Nếu có bất kỳ kết quả nào thất bại, đánh dấu test case là thất bại
            if (!$result['success']) {
                $testResults[$group]['success'] = false;
            }
        }

        // Đếm số lượng test case thành công/thất bại
        $passedTestCases = count(array_filter($testResults, function($result) {
            return $result['success'];
        }));
        $failedTestCases = count($testResults) - $passedTestCases;

        $executionTime = round(microtime(true) - self::$startTime, 2);

        fwrite(STDOUT, "\n" . str_repeat("=", 50) . "\n");
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ APPOINTMENTRECORDSMODEL\n");
        fwrite(STDOUT, str_repeat("=", 50) . "\n");

        fwrite(STDOUT, "Tổng số test case: {$totalTestCases}\n");
        fwrite(STDOUT, "✅ Thành công: {$passedTestCases}\n");
        fwrite(STDOUT, "❌ Thất bại: {$failedTestCases}\n");
        fwrite(STDOUT, "⏱️ Thời gian thực thi: {$executionTime}s\n");

        if ($failedTestCases > 0) {
            fwrite(STDOUT, "\n🔍 CHI TIẾT CÁC TEST CASE THẤT BẠI:\n");
            fwrite(STDOUT, str_repeat("-", 50) . "\n");

            foreach ($testResults as $group => $result) {
                if (!$result['success']) {
                    fwrite(STDOUT, "❌ {$group}\n");

                    foreach ($result['results'] as $subResult) {
                        if (!$subResult['success']) {
                            fwrite(STDOUT, "   Kết quả: {$subResult['actual']}\n");
                            if ($subResult['error']) {
                                fwrite(STDOUT, "   Lỗi: {$subResult['error']}\n");
                            }
                        }
                    }

                    fwrite(STDOUT, "\n");
                }
            }
        }

        fwrite(STDOUT, str_repeat("=", 50) . "\n");
    }
}
