<?php
/**
 * Lớp kiểm thử TreatmentsModel
 *
 * File: api/app/tests/models/TreatmentsModelTest.php
 * Class: TreatmentsModelTest
 *
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp TreatmentsModel, bao gồm:
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

class TreatmentsModelTest extends DatabaseTestCase
{
    /**
     * @var TreatmentsModel Đối tượng model danh sách phương pháp điều trị dùng trong test
     */
    protected $treatmentsModel;

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
     * Khởi tạo TreatmentsModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/TreatmentsModel.php';
        $this->treatmentsModel = new TreatmentsModel();

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
     * Tạo cuộc hẹn mẫu cho test
     *
     * @return int ID của cuộc hẹn đã tạo
     */
    private function createTestAppointment()
    {
        $tableName = TABLE_PREFIX.TABLE_APPOINTMENTS;
        $timestamp = time();

        // Lấy thông tin về các cột trong bảng
        $sql = "DESCRIBE `{$tableName}`";
        $columns = $this->executeQuery($sql);

        // Tạo dữ liệu cuộc hẹn dựa trên các cột thực tế
        $appointmentData = [];

        foreach ($columns as $column) {
            $field = $column['Field'];

            // Bỏ qua cột ID vì nó sẽ tự động tăng
            if ($field === 'id') {
                continue;
            }

            // Thiết lập giá trị cho từng cột
            switch ($field) {
                case 'patient_id':
                case 'doctor_id':
                    $appointmentData[$field] = 1; // Giả sử có bản ghi với ID = 1
                    break;
                case 'date':
                    $appointmentData[$field] = date('Y-m-d', $timestamp);
                    break;
                case 'time':
                    $appointmentData[$field] = date('H:i:s', $timestamp);
                    break;
                case 'status':
                    $appointmentData[$field] = 1;
                    break;
                case 'create_at':
                case 'update_at':
                    $appointmentData[$field] = date('Y-m-d H:i:s', $timestamp);
                    break;
                default:
                    // Đối với các cột khác, thiết lập giá trị mặc định
                    if (strpos($column['Type'], 'int') !== false) {
                        $appointmentData[$field] = 1;
                    } elseif (strpos($column['Type'], 'varchar') !== false ||
                              strpos($column['Type'], 'text') !== false) {
                        // Xử lý trường hợp đặc biệt cho patient_birthday
                        if ($field === 'patient_birthday') {
                            $appointmentData[$field] = date('Y-m-d', $timestamp);
                        } else {
                            // Giới hạn độ dài của chuỗi để tránh lỗi
                            $maxLength = 10; // Độ dài mặc định an toàn

                            // Trích xuất độ dài từ định nghĩa cột (ví dụ: varchar(255))
                            if (preg_match('/\((\d+)\)/', $column['Type'], $matches)) {
                                $maxLength = min((int)$matches[1], 20); // Giới hạn tối đa 20 ký tự
                            }

                            $appointmentData[$field] = substr('Test_' . $field, 0, $maxLength);
                        }
                    } elseif (strpos($column['Type'], 'date') !== false) {
                        $appointmentData[$field] = date('Y-m-d', $timestamp);
                    } elseif (strpos($column['Type'], 'time') !== false) {
                        $appointmentData[$field] = date('H:i:s', $timestamp);
                    } elseif (strpos($column['Type'], 'datetime') !== false) {
                        $appointmentData[$field] = date('Y-m-d H:i:s', $timestamp);
                    }
                    break;
            }
        }

        // Tạo câu lệnh SQL
        $columns = implode(', ', array_keys($appointmentData));
        $placeholders = implode(', ', array_fill(0, count($appointmentData), '?'));

        $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($appointmentData));

        return $this->pdo->lastInsertId();
    }

    /**
     * Tạo dữ liệu phương pháp điều trị mẫu cho test
     *
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu phương pháp điều trị mẫu
     */
    private function createTestTreatment($override = [])
    {
        $timestamp = time();

        // Tạo cuộc hẹn mẫu và lấy ID
        $appointmentId = isset($override['appointment_id']) ? $override['appointment_id'] : $this->createTestAppointment();

        return array_merge([
            'appointment_id' => $appointmentId,
            'name' => 'Tr' . substr($timestamp, -4),
            'type' => 'Ty' . rand(100, 999),
            'times' => '3', // Sử dụng giá trị đơn giản hơn
            'purpose' => 'Pu' . rand(100, 999),
            'instruction' => 'In' . rand(100, 999),
            'repeat_days' => '7',
            'repeat_time' => '3'
        ], $override);
    }

    /**
     * Tạo nhiều phương pháp điều trị mẫu cho test
     *
     * @param int $count Số lượng phương pháp điều trị cần tạo
     * @return array Mảng các ID của phương pháp điều trị đã tạo
     */
    private function createMultipleTestTreatments($count = 10)
    {
        $tableName = TABLE_PREFIX.TABLE_TREATMENTS;
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $treatmentData = $this->createTestTreatment([
                'name' => 'Tr' . $i . substr(time(), -3)
            ]);

            $columns = implode(', ', array_keys($treatmentData));
            $placeholders = implode(', ', array_fill(0, count($treatmentData), '?'));

            $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($treatmentData));

            $ids[] = $this->pdo->lastInsertId();
        }

        return $ids;
    }

    /**
     * Test case TC-01: Kiểm tra khởi tạo đối tượng TreatmentsModel
     * Test case TREATMENTS_CONS_01: Kiểm tra khởi tạo đối tượng TreatmentsModel
     */
    public function testConstructor()
    {
        $this->logSection("TREATMENTS_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng TreatmentsModel", "Đối tượng được tạo thành công");

        // Kiểm tra đối tượng được khởi tạo
        $isInstanceOfTreatments = $this->treatmentsModel instanceof TreatmentsModel;
        $isInstanceOfDataList = $this->treatmentsModel instanceof DataList;

        $this->logResult($isInstanceOfTreatments && $isInstanceOfDataList,
            "Instance created: " . ($isInstanceOfTreatments ? "Yes" : "No") .
            ", Extends DataList: " . ($isInstanceOfDataList ? "Yes" : "No"));

        $this->assertInstanceOf(TreatmentsModel::class, $this->treatmentsModel);
        $this->assertInstanceOf(DataList::class, $this->treatmentsModel);

        // Kiểm tra query đã được thiết lập
        $query = $this->treatmentsModel->getQuery();
        $hasQuery = !is_null($query);

        $this->logResult($hasQuery,
            "Query initialized: " . ($hasQuery ? "Yes" : "No"));

        $this->assertNotNull($query);
    }

    /**
     * Test case TC-02: Kiểm tra phương thức fetchData
     * Test case TREATMENTS_FETCH_02: Kiểm tra phương thức fetchData
     */
    public function testFetchData()
    {
        $this->logSection("TREATMENTS_FETCH_02: Kiểm tra phương thức fetchData");
        $this->logStep("Tạo dữ liệu test và lấy danh sách phương pháp điều trị", "Danh sách phương pháp điều trị được lấy thành công");

        // Tạo dữ liệu test
        $treatmentIds = $this->createMultipleTestTreatments(5);
        $this->assertCount(5, $treatmentIds);

        // Lấy danh sách phương pháp điều trị
        $this->treatmentsModel->fetchData();
        $data = $this->treatmentsModel->getData();

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
     * Test case TREATMENTS_PAGE_03: Kiểm tra phương thức paginate
     */
    public function testPaginate()
    {
        $this->logSection("TREATMENTS_PAGE_03: Kiểm tra phương thức paginate");
        $this->logStep("Tạo dữ liệu test và phân trang", "Dữ liệu được phân trang thành công");

        // Tạo dữ liệu test
        $treatmentIds = $this->createMultipleTestTreatments(10);
        $this->assertCount(10, $treatmentIds);

        // Thiết lập phân trang
        $this->treatmentsModel->setPageSize(3);
        $this->treatmentsModel->setPage(2);

        // Lấy danh sách phương pháp điều trị
        $this->treatmentsModel->fetchData();
        $data = $this->treatmentsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $totalCount = $this->treatmentsModel->getTotalCount();
        $pageCount = $this->treatmentsModel->getPageCount();
        $currentPage = $this->treatmentsModel->getPage();

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
     * Test case TREATMENTS_DATA_04: Kiểm tra phương thức getData
     */
    public function testGetData()
    {
        $this->logSection("TREATMENTS_DATA_04: Kiểm tra phương thức getData");
        $this->logStep("Lấy dữ liệu từ model", "Dữ liệu được lấy thành công");

        // Tạo dữ liệu test
        $treatmentIds = $this->createMultipleTestTreatments(3);
        $this->assertCount(3, $treatmentIds);

        // Lấy danh sách phương pháp điều trị
        $this->treatmentsModel->fetchData();
        $data = $this->treatmentsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $hasData = $dataCount > 0;
        $hasCorrectFields = false;

        if ($hasData) {
            $record = $data[0];
            $hasCorrectFields = isset($record->name) && isset($record->appointment_id) &&
                               isset($record->type) && isset($record->times) &&
                               isset($record->purpose) && isset($record->instruction) &&
                               isset($record->repeat_days) && isset($record->repeat_time);
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
     * Test case TREATMENTS_WHERE_05: Kiểm tra phương thức where
     */
    public function testWhere()
    {
        $this->logSection("TREATMENTS_WHERE_05: Kiểm tra phương thức where");
        $this->logStep("Lọc dữ liệu theo loại", "Dữ liệu được lọc thành công");

        // Tạo loại đặc biệt
        $specialType = "SpecialType" . rand(100, 999);

        // Tạo 5 phương pháp điều trị thông thường
        $this->createMultipleTestTreatments(5);

        // Tạo 3 phương pháp điều trị với loại đặc biệt
        for ($i = 0; $i < 3; $i++) {
            $treatmentData = $this->createTestTreatment([
                'type' => $specialType
            ]);

            $tableName = TABLE_PREFIX.TABLE_TREATMENTS;

            $columns = implode(', ', array_keys($treatmentData));
            $placeholders = implode(', ', array_fill(0, count($treatmentData), '?'));

            $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($treatmentData));
        }

        // Lọc dữ liệu theo loại
        $this->treatmentsModel->where("type", "=", $specialType);
        $this->treatmentsModel->fetchData();
        $data = $this->treatmentsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $allMatch = true;

        foreach ($data as $record) {
            if ($record->type !== $specialType) {
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
     * Test case TREATMENTS_ORDER_06: Kiểm tra phương thức orderBy
     */
    public function testOrderBy()
    {
        $this->logSection("TREATMENTS_ORDER_06: Kiểm tra phương thức orderBy");
        $this->logStep("Sắp xếp dữ liệu theo ID", "Dữ liệu được sắp xếp thành công");

        // Tạo dữ liệu test
        $treatmentIds = $this->createMultipleTestTreatments(5);
        $this->assertCount(5, $treatmentIds);

        // Sắp xếp dữ liệu theo ID giảm dần
        $this->treatmentsModel->orderBy("id", "DESC");
        $this->treatmentsModel->fetchData();
        $data = $this->treatmentsModel->getData();

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
     * Test case TREATMENTS_SEARCH_07: Kiểm tra phương thức search
     *
     * Lưu ý: Phương thức search trong DataList không thực hiện tìm kiếm trực tiếp
     * mà chỉ lưu trữ từ khóa tìm kiếm. Các lớp con phải ghi đè phương thức này
     * để thực hiện tìm kiếm thực tế. Vì TreatmentsModel không ghi đè phương thức này,
     * nên chúng ta chỉ kiểm tra xem từ khóa tìm kiếm có được lưu trữ đúng không.
     */
    public function testSearch()
    {
        $this->logSection("TREATMENTS_SEARCH_07: Kiểm tra phương thức search");
        $this->logStep("Kiểm tra lưu trữ từ khóa tìm kiếm", "Từ khóa tìm kiếm được lưu trữ thành công");

        // Tạo từ khóa tìm kiếm
        $searchKeyword = "UNIQUE_KEYWORD";

        // Gọi phương thức search
        $this->treatmentsModel->search($searchKeyword);

        // Kiểm tra xem từ khóa tìm kiếm có được lưu trữ không
        $storedKeyword = $this->treatmentsModel->getSearchQuery();
        $searchPerformed = $this->treatmentsModel->isSearchPerformed();

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
     * Test case TREATMENTS_SCHEMA_08: Kiểm tra schema của bảng
     *
     * Lưu ý: Test case này chỉ có thể thực hiện khi sử dụng bảng thật trong cơ sở dữ liệu test,
     * không thể thực hiện khi sử dụng bảng tạm thời.
     */
    public function testTableSchema()
    {
        $this->logSection("TREATMENTS_SCHEMA_08: Kiểm tra schema của bảng");
        $this->logStep("Kiểm tra cấu trúc bảng TREATMENTS", "Bảng có đúng cấu trúc");

        $tableName = TABLE_PREFIX.TABLE_TREATMENTS;

        // Lấy thông tin về các cột trong bảng
        $sql = "DESCRIBE `{$tableName}`";
        $columns = $this->executeQuery($sql);

        // Kiểm tra số lượng cột
        $columnCount = count($columns);

        // Kiểm tra các cột cần thiết
        $hasIdColumn = false;
        $hasAppointmentIdColumn = false;
        $hasNameColumn = false;
        $hasTypeColumn = false;
        $hasTimesColumn = false;
        $hasPurposeColumn = false;
        $hasInstructionColumn = false;
        $hasRepeatDaysColumn = false;
        $hasRepeatTimeColumn = false;

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

            if ($column['Field'] === 'appointment_id') {
                $hasAppointmentIdColumn = true;
            }

            if ($column['Field'] === 'name') {
                $hasNameColumn = true;
            }

            if ($column['Field'] === 'type') {
                $hasTypeColumn = true;
            }

            if ($column['Field'] === 'times') {
                $hasTimesColumn = true;
            }

            if ($column['Field'] === 'purpose') {
                $hasPurposeColumn = true;
            }

            if ($column['Field'] === 'instruction') {
                $hasInstructionColumn = true;
            }

            if ($column['Field'] === 'repeat_days') {
                $hasRepeatDaysColumn = true;
            }

            if ($column['Field'] === 'repeat_time') {
                $hasRepeatTimeColumn = true;
            }
        }

        $allColumnsExist = $hasIdColumn && $hasAppointmentIdColumn && $hasNameColumn &&
                          $hasTypeColumn && $hasTimesColumn && $hasPurposeColumn &&
                          $hasInstructionColumn && $hasRepeatDaysColumn && $hasRepeatTimeColumn;

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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ TREATMENTSMODEL\n");
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
