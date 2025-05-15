<?php
/**
 * Lớp kiểm thử RoomsModel
 *
 * File: api/app/tests/models/RoomsModelTest.php
 * Class: RoomsModelTest
 *
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp RoomsModel, bao gồm:
 * - Khởi tạo đối tượng
 * - Phân trang dữ liệu
 * - Tìm kiếm dữ liệu
 * - Lọc dữ liệu
 * - Sắp xếp dữ liệu
 *
 * Phiên bản này sử dụng trực tiếp bảng trong cơ sở dữ liệu test thay vì tạo bảng tạm thời.
 *
 * @package    UnitTest
 * @subpackage Models
 * @author     B21DCDT205-Lê Đức Thắng
 * @version    1.0
 */
require_once __DIR__ . '/../DatabaseTestCase.php';

class RoomsModelTest extends DatabaseTestCase
{
    /**
     * @var RoomsModel Đối tượng model danh sách phòng dùng trong test
     */
    protected $roomsModel;

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
     * Khởi tạo RoomsModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/RoomsModel.php';
        $this->roomsModel = new RoomsModel();

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
        $timestamp = time();
        return array_merge([
            'name' => 'R' . substr($timestamp, -4), // Tên ngắn hơn
            'location' => 'Loc' . rand(100, 999) // Vị trí ngắn hơn
        ], $override);
    }

    /**
     * Tạo nhiều phòng mẫu cho test
     *
     * @param int $count Số lượng phòng cần tạo
     * @return array Mảng các ID của phòng đã tạo
     */
    private function createMultipleTestRooms($count = 10)
    {
        $tableName = TABLE_PREFIX.TABLE_ROOMS;
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $roomData = $this->createTestRoom([
                'name' => 'R' . $i . substr(time(), -3)
            ]);

            $columns = implode(', ', array_keys($roomData));
            $placeholders = implode(', ', array_fill(0, count($roomData), '?'));

            $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($roomData));

            $ids[] = $this->pdo->lastInsertId();
        }

        return $ids;
    }

    /**
     * Test case TC-01: Kiểm tra khởi tạo đối tượng RoomsModel
     * Test case ROOMS_CONS_01: Kiểm tra khởi tạo đối tượng RoomsModel
     */
    public function testConstructor()
    {
        $this->logSection("ROOMS_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng RoomsModel", "Đối tượng được tạo thành công");

        // Kiểm tra đối tượng được khởi tạo
        $isInstanceOfRooms = $this->roomsModel instanceof RoomsModel;
        $isInstanceOfDataList = $this->roomsModel instanceof DataList;

        $this->logResult($isInstanceOfRooms && $isInstanceOfDataList,
            "Instance created: " . ($isInstanceOfRooms ? "Yes" : "No") .
            ", Extends DataList: " . ($isInstanceOfDataList ? "Yes" : "No"));

        $this->assertInstanceOf(RoomsModel::class, $this->roomsModel);
        $this->assertInstanceOf(DataList::class, $this->roomsModel);

        // Kiểm tra query đã được thiết lập
        $query = $this->roomsModel->getQuery();
        $hasQuery = !is_null($query);

        $this->logResult($hasQuery,
            "Query initialized: " . ($hasQuery ? "Yes" : "No"));

        $this->assertNotNull($query);
    }

    /**
     * Test case TC-02: Kiểm tra phương thức fetchData
     * Test case ROOMS_FETCH_02: Kiểm tra phương thức fetchData
     */
    public function testFetchData()
    {
        $this->logSection("ROOMS_FETCH_02: Kiểm tra phương thức fetchData");
        $this->logStep("Tạo dữ liệu test và lấy danh sách phòng", "Danh sách phòng được lấy thành công");

        // Tạo dữ liệu test
        $roomIds = $this->createMultipleTestRooms(5);
        $this->assertCount(5, $roomIds);

        // Lấy danh sách phòng
        $this->roomsModel->fetchData();
        $data = $this->roomsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $hasData = $dataCount > 0;

        $this->logResult($hasData,
            "Data fetched: " . ($hasData ? "Yes" : "No") .
            ", Record count: " . $dataCount);

        $this->assertTrue($hasData);
        $this->assertGreaterThanOrEqual(5, $dataCount); // Có thể có dữ liệu khác trong DB
    }

    /**
     * Test case TC-03: Kiểm tra phương thức paginate
     * Test case ROOMS_PAGE_03: Kiểm tra phương thức paginate
     */
    public function testPaginate()
    {
        $this->logSection("ROOMS_PAGE_03: Kiểm tra phương thức paginate");
        $this->logStep("Tạo dữ liệu test và phân trang", "Dữ liệu được phân trang thành công");

        // Tạo dữ liệu test
        $roomIds = $this->createMultipleTestRooms(10);
        $this->assertCount(10, $roomIds);

        // Thiết lập phân trang
        $this->roomsModel->setPageSize(3);
        $this->roomsModel->setPage(2);

        // Lấy danh sách phòng
        $this->roomsModel->fetchData();
        $data = $this->roomsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $totalCount = $this->roomsModel->getTotalCount();
        $pageCount = $this->roomsModel->getPageCount();
        $currentPage = $this->roomsModel->getPage();

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
     * Test case ROOMS_DATA_04: Kiểm tra phương thức getData
     */
    public function testGetData()
    {
        $this->logSection("ROOMS_DATA_04: Kiểm tra phương thức getData");
        $this->logStep("Lấy dữ liệu từ model", "Dữ liệu được lấy thành công");

        // Tạo dữ liệu test
        $roomIds = $this->createMultipleTestRooms(3);
        $this->assertCount(3, $roomIds);

        // Lấy danh sách phòng
        $this->roomsModel->fetchData();
        $data = $this->roomsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $hasData = $dataCount > 0;
        $hasCorrectFields = false;

        if ($hasData) {
            $record = $data[0];
            $hasCorrectFields = isset($record->name) && isset($record->location);
        }

        $this->logResult($hasData && $hasCorrectFields,
            "Data retrieved: " . ($hasData ? "Yes" : "No") .
            ", Record count: " . $dataCount .
            ", Has correct fields: " . ($hasCorrectFields ? "Yes" : "No"));

        $this->assertTrue($hasData);
        $this->assertTrue($hasCorrectFields);
        $this->assertGreaterThanOrEqual(3, $dataCount);
    }

    /**
     * Test case TC-05: Kiểm tra phương thức where
     * Test case ROOMS_WHERE_05: Kiểm tra phương thức where
     */
    public function testWhere()
    {
        $this->logSection("ROOMS_WHERE_05: Kiểm tra phương thức where");
        $this->logStep("Lọc dữ liệu theo vị trí", "Dữ liệu được lọc thành công");

        // Tạo vị trí đặc biệt
        $specialLocation = "SpecLoc" . rand(100, 999);

        // Tạo 5 phòng thông thường
        $this->createMultipleTestRooms(5);

        // Tạo 3 phòng với vị trí đặc biệt
        for ($i = 0; $i < 3; $i++) {
            $roomData = $this->createTestRoom([
                'location' => $specialLocation
            ]);

            $tableName = TABLE_PREFIX.TABLE_ROOMS;

            $columns = implode(', ', array_keys($roomData));
            $placeholders = implode(', ', array_fill(0, count($roomData), '?'));

            $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($roomData));
        }

        // Lọc dữ liệu theo vị trí
        $this->roomsModel->where("location", "=", $specialLocation);
        $this->roomsModel->fetchData();
        $data = $this->roomsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $allMatch = true;

        foreach ($data as $record) {
            if ($record->location !== $specialLocation) {
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
     * Test case ROOMS_ORDER_06: Kiểm tra phương thức orderBy
     */
    public function testOrderBy()
    {
        $this->logSection("ROOMS_ORDER_06: Kiểm tra phương thức orderBy");
        $this->logStep("Sắp xếp dữ liệu theo ID", "Dữ liệu được sắp xếp thành công");

        // Tạo dữ liệu test
        $roomIds = $this->createMultipleTestRooms(5);
        $this->assertCount(5, $roomIds);

        // Sắp xếp dữ liệu theo ID giảm dần
        $this->roomsModel->orderBy("id", "DESC");
        $this->roomsModel->fetchData();
        $data = $this->roomsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $isDescending = true;

        for ($i = 0; $i < $dataCount - 1; $i++) {
            if ($data[$i]->id < $data[$i + 1]->id) {
                $isDescending = false;
                break;
            }
        }

        $this->logResult($isDescending && $dataCount >= 5,
            "Ordering successful: " . ($isDescending ? "Yes" : "No") .
            ", Order: Descending, Record count: " . $dataCount);

        $this->assertTrue($isDescending);
        $this->assertGreaterThanOrEqual(5, $dataCount);
    }

    /**
     * Test case TC-07: Kiểm tra phương thức search
     * Test case ROOMS_SEARCH_07: Kiểm tra phương thức search
     *
     * Lưu ý: Phương thức search trong DataList không thực hiện tìm kiếm trực tiếp
     * mà chỉ lưu trữ từ khóa tìm kiếm. Các lớp con phải ghi đè phương thức này
     * để thực hiện tìm kiếm thực tế. Vì RoomsModel không ghi đè phương thức này,
     * nên chúng ta chỉ kiểm tra xem từ khóa tìm kiếm có được lưu trữ đúng không.
     */
    public function testSearch()
    {
        $this->logSection("ROOMS_SEARCH_07: Kiểm tra phương thức search");
        $this->logStep("Kiểm tra lưu trữ từ khóa tìm kiếm", "Từ khóa tìm kiếm được lưu trữ thành công");

        // Tạo từ khóa tìm kiếm
        $searchKeyword = "UNIQUE_KEYWORD";

        // Gọi phương thức search
        $this->roomsModel->search($searchKeyword);

        // Kiểm tra xem từ khóa tìm kiếm có được lưu trữ không
        $storedKeyword = $this->roomsModel->getSearchQuery();
        $searchPerformed = $this->roomsModel->isSearchPerformed();

        $keywordMatches = $storedKeyword === $searchKeyword;

        $this->logResult($keywordMatches && $searchPerformed,
            "Search keyword stored: " . ($keywordMatches ? "Yes" : "No") .
            ", Search performed: " . ($searchPerformed ? "Yes" : "No") .
            ", Stored keyword: " . $storedKeyword);

        $this->assertEquals($searchKeyword, $storedKeyword);
        $this->assertTrue($searchPerformed);
    }

    /**
     * Test case TC-08: Kiểm tra schema của bảng
     * Test case ROOMS_SCHEMA_08: Kiểm tra schema của bảng
     *
     * Lưu ý: Test case này chỉ có thể thực hiện khi sử dụng bảng thật trong cơ sở dữ liệu test,
     * không thể thực hiện khi sử dụng bảng tạm thời.
     */
    public function testTableSchema()
    {
        $this->logSection("ROOMS_SCHEMA_08: Kiểm tra schema của bảng");
        $this->logStep("Kiểm tra cấu trúc bảng ROOMS", "Bảng có đúng cấu trúc");

        $tableName = TABLE_PREFIX.TABLE_ROOMS;

        // Lấy thông tin về các cột trong bảng
        $sql = "DESCRIBE `{$tableName}`";
        $columns = $this->executeQuery($sql);

        // Kiểm tra số lượng cột
        $columnCount = count($columns);

        // Kiểm tra các cột cần thiết
        $hasIdColumn = false;
        $hasNameColumn = false;
        $hasLocationColumn = false;

        foreach ($columns as $column) {
            if ($column['Field'] === 'id') {
                $hasIdColumn = true;
                // Kiểm tra kiểu dữ liệu và thuộc tính của cột id
                $isAutoIncrement = strpos($column['Extra'], 'auto_increment') !== false;
                $isPrimaryKey = $column['Key'] === 'PRI';
                $this->logResult($isAutoIncrement && $isPrimaryKey,
                    "ID column: Auto Increment = " . ($isAutoIncrement ? "Yes" : "No") .
                    ", Primary Key = " . ($isPrimaryKey ? "Yes" : "No"));
            }

            if ($column['Field'] === 'name') {
                $hasNameColumn = true;
            }

            if ($column['Field'] === 'location') {
                $hasLocationColumn = true;
            }
        }

        $allColumnsExist = $hasIdColumn && $hasNameColumn && $hasLocationColumn;

        $this->logResult($allColumnsExist,
            "Table schema: Column count = " . $columnCount .
            ", All required columns exist = " . ($allColumnsExist ? "Yes" : "No"));

        $this->assertTrue($allColumnsExist);
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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ ROOMSMODEL\n");
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
