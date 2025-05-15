<?php
/**
 * Lớp kiểm thử DoctorsModel
 *
 * File: api/app/tests/models/DoctorsModelTest.php
 * Class: DoctorsModelTest
 *
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp DoctorsModel, bao gồm:
 * - Khởi tạo đối tượng
 * - Phân trang dữ liệu
 * - Tìm kiếm dữ liệu
 * - Lọc dữ liệu
 * - Sắp xếp dữ liệu
 *
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class DoctorsModelTest extends DatabaseTestCase
{
    /**
     * @var DoctorsModel Đối tượng model danh sách bác sĩ dùng trong test
     */
    protected $doctorsModel;

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
     * Khởi tạo DoctorsModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/DoctorsModel.php';
        $this->doctorsModel = new DoctorsModel();

        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }

        // Tên bảng đầy đủ với prefix
        $fullTableName = TABLE_PREFIX.TABLE_DOCTORS;

        // Khởi tạo bảng test - sử dụng tên bảng đầy đủ
        $this->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS `{$fullTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `email` varchar(255) NOT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `password` varchar(255) NOT NULL,
                `name` varchar(255) NOT NULL,
                `description` text,
                `price` int(11) DEFAULT NULL,
                `role` varchar(20) DEFAULT 'member',
                `active` tinyint(1) DEFAULT '1',
                `speciality_id` int(11) DEFAULT NULL,
                `room_id` int(11) DEFAULT NULL,
                `recovery_token` varchar(255) DEFAULT NULL,
                `create_at` datetime DEFAULT NULL,
                `update_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `email` (`email`),
                UNIQUE KEY `phone` (`phone`)
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
     * Tạo dữ liệu bác sĩ mẫu cho test
     *
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu bác sĩ mẫu
     */
    private function createTestDoctor($override = [])
    {
        $timestamp = time();
        return array_merge([
            'email' => 'test_' . $timestamp . '@example.com',
            'phone' => '098' . rand(1000000, 9999999),
            'password' => md5('password123'),
            'name' => 'Test Doctor ' . $timestamp,
            'description' => 'Test Description ' . $timestamp,
            'price' => 200000,
            'role' => 'admin',
            'active' => 1,
            'speciality_id' => 1,
            'room_id' => 1,
            'recovery_token' => null,
            'create_at' => date('Y-m-d H:i:s'),
            'update_at' => date('Y-m-d H:i:s')
        ], $override);
    }

    /**
     * Tạo nhiều bác sĩ mẫu cho test
     *
     * @param int $count Số lượng bác sĩ cần tạo
     * @return array Mảng các ID của bác sĩ đã tạo
     */
    private function createMultipleTestDoctors($count = 10)
    {
        $tableName = TABLE_PREFIX.TABLE_DOCTORS;
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $doctorData = $this->createTestDoctor([
                'name' => 'Test Doctor ' . $i . '_' . time(),
                'email' => 'test_' . $i . '_' . time() . '@example.com',
                'phone' => '098' . rand(1000000, 9999999)
            ]);

            $columns = implode(', ', array_keys($doctorData));
            $placeholders = implode(', ', array_fill(0, count($doctorData), '?'));

            $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($doctorData));

            $ids[] = $this->pdo->lastInsertId();
        }

        return $ids;
    }

    /**
     * Test case TC-01: Kiểm tra khởi tạo đối tượng DoctorsModel
     * Test case DOCS_CONS_01: Kiểm tra khởi tạo đối tượng DoctorsModel
     */
    public function testConstructor()
    {
        $this->logSection("DOCS_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng DoctorsModel", "Đối tượng được tạo thành công");

        // Kiểm tra đối tượng được khởi tạo
        $isInstanceOfDoctors = $this->doctorsModel instanceof DoctorsModel;
        $isInstanceOfDataList = $this->doctorsModel instanceof DataList;

        $this->logResult($isInstanceOfDoctors && $isInstanceOfDataList,
            "Instance created: " . ($isInstanceOfDoctors ? "Yes" : "No") .
            ", Extends DataList: " . ($isInstanceOfDataList ? "Yes" : "No"));

        $this->assertInstanceOf(DoctorsModel::class, $this->doctorsModel);
        $this->assertInstanceOf(DataList::class, $this->doctorsModel);

        // Kiểm tra query đã được thiết lập
        $query = $this->doctorsModel->getQuery();
        $hasQuery = !is_null($query);

        $this->logResult($hasQuery,
            "Query initialized: " . ($hasQuery ? "Yes" : "No"));

        $this->assertNotNull($query);
    }

    /**
     * Test case TC-02: Kiểm tra phương thức fetchData
     * Test case DOCS_FETCH_02: Kiểm tra phương thức fetchData
     */
    public function testFetchData()
    {
        $this->logSection("DOCS_FETCH_02: Kiểm tra phương thức fetchData");
        $this->logStep("Tạo dữ liệu test và lấy danh sách bác sĩ", "Danh sách bác sĩ được lấy thành công");

        // Tạo dữ liệu test
        $doctorIds = $this->createMultipleTestDoctors(5);
        $this->assertCount(5, $doctorIds);

        // Lấy danh sách bác sĩ
        $this->doctorsModel->fetchData();
        $data = $this->doctorsModel->getData();

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
     * Test case DOCS_PAGE_03: Kiểm tra phương thức paginate
     */
    public function testPaginate()
    {
        $this->logSection("DOCS_PAGE_03: Kiểm tra phương thức paginate");
        $this->logStep("Tạo dữ liệu test và phân trang", "Dữ liệu được phân trang thành công");

        // Tạo dữ liệu test
        $doctorIds = $this->createMultipleTestDoctors(10);
        $this->assertCount(10, $doctorIds);

        // Thiết lập phân trang
        $this->doctorsModel->setPageSize(3);
        $this->doctorsModel->setPage(2);

        // Lấy danh sách bác sĩ
        $this->doctorsModel->fetchData();
        $data = $this->doctorsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $totalCount = $this->doctorsModel->getTotalCount();
        $pageCount = $this->doctorsModel->getPageCount();
        $currentPage = $this->doctorsModel->getPage();

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
     * Test case DOCS_DATA_04: Kiểm tra phương thức getData
     */
    public function testGetData()
    {
        $this->logSection("DOCS_DATA_04: Kiểm tra phương thức getData");
        $this->logStep("Lấy dữ liệu từ model", "Dữ liệu được lấy thành công");

        // Tạo dữ liệu test
        $doctorIds = $this->createMultipleTestDoctors(3);
        $this->assertCount(3, $doctorIds);

        // Lấy danh sách bác sĩ
        $this->doctorsModel->fetchData();
        $data = $this->doctorsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $hasData = $dataCount > 0;
        $hasCorrectFields = false;

        if ($hasData) {
            $record = $data[0];
            $hasCorrectFields = isset($record->name) &&
                               isset($record->email) &&
                               isset($record->phone) &&
                               isset($record->role);
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
     * Test case DOCS_WHERE_05: Kiểm tra phương thức where
     */
    public function testWhere()
    {
        $this->logSection("DOCS_WHERE_05: Kiểm tra phương thức where");
        $this->logStep("Lọc dữ liệu theo vai trò", "Dữ liệu được lọc thành công");

        // Tạo dữ liệu test
        $specialRole = "developer";

        // Tạo 5 bác sĩ thông thường
        $this->createMultipleTestDoctors(5);

        // Tạo 3 bác sĩ với vai trò đặc biệt
        for ($i = 0; $i < 3; $i++) {
            $doctorData = $this->createTestDoctor([
                'name' => 'Developer ' . $i,
                'email' => 'dev_' . $i . '_' . time() . '@example.com',
                'phone' => '097' . rand(1000000, 9999999),
                'role' => $specialRole
            ]);

            $tableName = TABLE_PREFIX.TABLE_DOCTORS;

            $columns = implode(', ', array_keys($doctorData));
            $placeholders = implode(', ', array_fill(0, count($doctorData), '?'));

            $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($doctorData));
        }

        // Lọc dữ liệu theo vai trò
        $this->doctorsModel->where("role", "=", $specialRole);
        $this->doctorsModel->fetchData();
        $data = $this->doctorsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $allMatch = true;

        foreach ($data as $record) {
            if ($record->role !== $specialRole) {
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
     * Test case DOCS_ORDER_06: Kiểm tra phương thức orderBy
     */
    public function testOrderBy()
    {
        $this->logSection("DOCS_ORDER_06: Kiểm tra phương thức orderBy");
        $this->logStep("Sắp xếp dữ liệu theo ID", "Dữ liệu được sắp xếp thành công");

        // Tạo dữ liệu test
        $doctorIds = $this->createMultipleTestDoctors(5);
        $this->assertCount(5, $doctorIds);

        // Sắp xếp dữ liệu theo ID giảm dần
        $this->doctorsModel->orderBy("id", "DESC");
        $this->doctorsModel->fetchData();
        $data = $this->doctorsModel->getData();

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
     * Test case TC-07: Kiểm tra phương thức search
     * Test case DOCS_SEARCH_07: Kiểm tra phương thức search
     *
     * Lưu ý: Phương thức search trong DataList không thực hiện tìm kiếm trực tiếp
     * mà chỉ lưu trữ từ khóa tìm kiếm. Các lớp con phải ghi đè phương thức này
     * để thực hiện tìm kiếm thực tế. Vì DoctorsModel không ghi đè phương thức này,
     * nên chúng ta chỉ kiểm tra xem từ khóa tìm kiếm có được lưu trữ đúng không.
     */
    public function testSearch()
    {
        $this->logSection("DOCS_SEARCH_07: Kiểm tra phương thức search");
        $this->logStep("Kiểm tra lưu trữ từ khóa tìm kiếm", "Từ khóa tìm kiếm được lưu trữ thành công");

        // Tạo từ khóa tìm kiếm
        $searchKeyword = "UNIQUE_KEYWORD";

        // Gọi phương thức search
        $this->doctorsModel->search($searchKeyword);

        // Kiểm tra xem từ khóa tìm kiếm có được lưu trữ không
        $storedKeyword = $this->doctorsModel->getSearchQuery();
        $searchPerformed = $this->doctorsModel->isSearchPerformed();

        $keywordMatches = $storedKeyword === $searchKeyword;

        $this->logResult($keywordMatches && $searchPerformed,
            "Search keyword stored: " . ($keywordMatches ? "Yes" : "No") .
            ", Search performed: " . ($searchPerformed ? "Yes" : "No") .
            ", Stored keyword: " . $storedKeyword);

        $this->assertEquals($searchKeyword, $storedKeyword);
        $this->assertTrue($searchPerformed);
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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ DOCTORSMODEL\n");
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
