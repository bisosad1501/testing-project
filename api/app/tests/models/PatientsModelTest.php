<?php
/**
 * Lớp kiểm thử PatientsModel
 *
 * File: api/app/tests/models/PatientsModelTest.php
 * Class: PatientsModelTest
 *
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp PatientsModel, bao gồm:
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

class PatientsModelTest extends DatabaseTestCase
{
    /**
     * @var PatientsModel Đối tượng model danh sách bệnh nhân dùng trong test
     */
    protected $patientsModel;

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
     * Khởi tạo PatientsModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/PatientsModel.php';
        $this->patientsModel = new PatientsModel();

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
     * Tạo dữ liệu bệnh nhân mẫu cho test
     *
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu bệnh nhân mẫu
     */
    private function createTestPatient($override = [])
    {
        $timestamp = time();
        return array_merge([
            'email' => 'patient_' . $timestamp . '@example.com',
            'phone' => '098' . rand(1000000, 9999999),
            'password' => md5('password123'),
            'name' => 'Test Patient ' . $timestamp,
            'gender' => 1, // 1 = male, 0 = female
            'birthday' => '1990-01-01',
            'address' => 'Test Address',
            'avatar' => 'avatar.jpg',
            'create_at' => date('Y-m-d H:i:s', $timestamp),
            'update_at' => date('Y-m-d H:i:s', $timestamp)
        ], $override);
    }

    /**
     * Tạo nhiều bệnh nhân mẫu cho test
     *
     * @param int $count Số lượng bệnh nhân cần tạo
     * @return array Mảng các ID của bệnh nhân đã tạo
     */
    private function createMultipleTestPatients($count = 10)
    {
        $tableName = TABLE_PREFIX.TABLE_PATIENTS;
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $patientData = $this->createTestPatient([
                'name' => 'Patient_Test_' . $i . '_' . time()
            ]);

            $sql = "INSERT INTO `{$tableName}` (email, phone, password, name, gender, birthday, address, avatar, create_at, update_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $patientData['email'],
                $patientData['phone'],
                $patientData['password'],
                $patientData['name'],
                $patientData['gender'],
                $patientData['birthday'],
                $patientData['address'],
                $patientData['avatar'],
                $patientData['create_at'],
                $patientData['update_at']
            ]);

            $ids[] = $this->pdo->lastInsertId();
        }

        return $ids;
    }

    /**
     * Test case TC-01: Kiểm tra khởi tạo đối tượng PatientsModel
     * Test case PATIENTS_CONS_01: Kiểm tra khởi tạo đối tượng PatientsModel
     */
    public function testConstructor()
    {
        $this->logSection("PATIENTS_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng PatientsModel", "Đối tượng được tạo thành công");

        // Kiểm tra đối tượng được khởi tạo
        $isInstanceOfPatients = $this->patientsModel instanceof PatientsModel;
        $isInstanceOfDataList = $this->patientsModel instanceof DataList;

        $this->logResult($isInstanceOfPatients && $isInstanceOfDataList,
            "Instance created: " . ($isInstanceOfPatients ? "Yes" : "No") .
            ", Extends DataList: " . ($isInstanceOfDataList ? "Yes" : "No"));

        $this->assertInstanceOf(PatientsModel::class, $this->patientsModel);
        $this->assertInstanceOf(DataList::class, $this->patientsModel);

        // Kiểm tra query đã được thiết lập
        $query = $this->patientsModel->getQuery();
        $hasQuery = !is_null($query);

        $this->logResult($hasQuery,
            "Query initialized: " . ($hasQuery ? "Yes" : "No"));

        $this->assertNotNull($query);
    }

    /**
     * Test case TC-02: Kiểm tra phương thức fetchData
     * Test case PATIENTS_FETCH_02: Kiểm tra phương thức fetchData
     */
    public function testFetchData()
    {
        $this->logSection("PATIENTS_FETCH_02: Kiểm tra phương thức fetchData");
        $this->logStep("Tạo dữ liệu test và lấy danh sách bệnh nhân", "Danh sách bệnh nhân được lấy thành công");

        // Tạo dữ liệu test
        $patientIds = $this->createMultipleTestPatients(5);
        $this->assertCount(5, $patientIds);

        // Lấy danh sách bệnh nhân
        $this->patientsModel->fetchData();
        $data = $this->patientsModel->getData();

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
     * Test case PATIENTS_PAGE_03: Kiểm tra phương thức paginate
     */
    public function testPaginate()
    {
        $this->logSection("PATIENTS_PAGE_03: Kiểm tra phương thức paginate");
        $this->logStep("Tạo dữ liệu test và phân trang", "Dữ liệu được phân trang thành công");

        // Tạo dữ liệu test
        $patientIds = $this->createMultipleTestPatients(10);
        $this->assertCount(10, $patientIds);

        // Thiết lập phân trang
        $this->patientsModel->setPageSize(3);
        $this->patientsModel->setPage(2);

        // Lấy danh sách bệnh nhân
        $this->patientsModel->fetchData();
        $data = $this->patientsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $totalCount = $this->patientsModel->getTotalCount();
        $pageCount = $this->patientsModel->getPageCount();
        $currentPage = $this->patientsModel->getPage();

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
     * Test case PATIENTS_DATA_04: Kiểm tra phương thức getData
     */
    public function testGetData()
    {
        $this->logSection("PATIENTS_DATA_04: Kiểm tra phương thức getData");
        $this->logStep("Lấy dữ liệu từ model", "Dữ liệu được lấy thành công");

        // Tạo dữ liệu test
        $patientIds = $this->createMultipleTestPatients(3);
        $this->assertCount(3, $patientIds);

        // Lấy danh sách bệnh nhân
        $this->patientsModel->fetchData();
        $data = $this->patientsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $hasData = $dataCount > 0;
        $hasCorrectFields = false;

        if ($hasData) {
            $record = $data[0];
            $hasCorrectFields = isset($record->name) && isset($record->email) &&
                               isset($record->phone) && isset($record->gender);
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
     * Test case PATIENTS_WHERE_05: Kiểm tra phương thức where
     */
    public function testWhere()
    {
        $this->logSection("PATIENTS_WHERE_05: Kiểm tra phương thức where");
        $this->logStep("Lọc dữ liệu theo giới tính", "Dữ liệu được lọc thành công");

        // Tạo 5 bệnh nhân nam
        $this->createMultipleTestPatients(5);

        // Tạo 3 bệnh nhân nữ
        for ($i = 0; $i < 3; $i++) {
            $patientData = $this->createTestPatient([
                'gender' => 0, // 0 = female
                'name' => 'Female_Patient_' . $i . '_' . time()
            ]);

            $tableName = TABLE_PREFIX.TABLE_PATIENTS;

            $sql = "INSERT INTO `{$tableName}` (email, phone, password, name, gender, birthday, address, avatar, create_at, update_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $patientData['email'],
                $patientData['phone'],
                $patientData['password'],
                $patientData['name'],
                $patientData['gender'],
                $patientData['birthday'],
                $patientData['address'],
                $patientData['avatar'],
                $patientData['create_at'],
                $patientData['update_at']
            ]);
        }

        // Lọc dữ liệu theo giới tính nữ
        $this->patientsModel->where("gender", "=", 0);
        $this->patientsModel->fetchData();
        $data = $this->patientsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $allMatch = true;

        foreach ($data as $record) {
            if ($record->gender != 0) {
                $allMatch = false;
                break;
            }
        }

        $this->logResult($allMatch && $dataCount >= 3,
            "Filtering successful: " . ($allMatch ? "Yes" : "No") .
            ", Filtered record count: " . $dataCount . " (expected: >= 3)");

        $this->assertTrue($allMatch);
        $this->assertGreaterThanOrEqual(3, $dataCount);
    }

    /**
     * Test case TC-06: Kiểm tra phương thức orderBy
     * Test case PATIENTS_ORDER_06: Kiểm tra phương thức orderBy
     */
    public function testOrderBy()
    {
        $this->logSection("PATIENTS_ORDER_06: Kiểm tra phương thức orderBy");
        $this->logStep("Sắp xếp dữ liệu theo ID", "Dữ liệu được sắp xếp thành công");

        // Tạo dữ liệu test
        $patientIds = $this->createMultipleTestPatients(5);
        $this->assertCount(5, $patientIds);

        // Sắp xếp dữ liệu theo ID giảm dần
        $this->patientsModel->orderBy("id", "DESC");
        $this->patientsModel->fetchData();
        $data = $this->patientsModel->getData();

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
     * Test case PATIENTS_SEARCH_07: Kiểm tra phương thức search
     *
     * Lưu ý: Phương thức search trong DataList không thực hiện tìm kiếm trực tiếp
     * mà chỉ lưu trữ từ khóa tìm kiếm. Các lớp con phải ghi đè phương thức này
     * để thực hiện tìm kiếm thực tế. Vì PatientsModel không ghi đè phương thức này,
     * nên chúng ta chỉ kiểm tra xem từ khóa tìm kiếm có được lưu trữ đúng không.
     */
    public function testSearch()
    {
        $this->logSection("PATIENTS_SEARCH_07: Kiểm tra phương thức search");
        $this->logStep("Kiểm tra lưu trữ từ khóa tìm kiếm", "Từ khóa tìm kiếm được lưu trữ thành công");

        // Tạo từ khóa tìm kiếm
        $searchKeyword = "UNIQUE_KEYWORD";

        // Gọi phương thức search
        $this->patientsModel->search($searchKeyword);

        // Kiểm tra xem từ khóa tìm kiếm có được lưu trữ không
        $storedKeyword = $this->patientsModel->getSearchQuery();
        $searchPerformed = $this->patientsModel->isSearchPerformed();

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
     * Test case PATIENTS_SCHEMA_08: Kiểm tra schema của bảng
     *
     * Lưu ý: Test case này chỉ có thể thực hiện khi sử dụng bảng thật trong cơ sở dữ liệu test,
     * không thể thực hiện khi sử dụng bảng tạm thời.
     */
    public function testTableSchema()
    {
        $this->logSection("PATIENTS_SCHEMA_08: Kiểm tra schema của bảng");
        $this->logStep("Kiểm tra cấu trúc bảng PATIENTS", "Bảng có đúng cấu trúc");

        $tableName = TABLE_PREFIX.TABLE_PATIENTS;

        // Lấy thông tin về các cột trong bảng
        $sql = "DESCRIBE `{$tableName}`";
        $columns = $this->executeQuery($sql);

        // Kiểm tra số lượng cột
        $columnCount = count($columns);

        // Kiểm tra các cột cần thiết
        $hasIdColumn = false;
        $hasEmailColumn = false;
        $hasPhoneColumn = false;
        $hasPasswordColumn = false;
        $hasNameColumn = false;
        $hasGenderColumn = false;
        $hasBirthdayColumn = false;
        $hasAddressColumn = false;
        $hasAvatarColumn = false;
        $hasCreateAtColumn = false;
        $hasUpdateAtColumn = false;

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

            if ($column['Field'] === 'email') {
                $hasEmailColumn = true;
            }

            if ($column['Field'] === 'phone') {
                $hasPhoneColumn = true;
            }

            if ($column['Field'] === 'password') {
                $hasPasswordColumn = true;
            }

            if ($column['Field'] === 'name') {
                $hasNameColumn = true;
            }

            if ($column['Field'] === 'gender') {
                $hasGenderColumn = true;
            }

            if ($column['Field'] === 'birthday') {
                $hasBirthdayColumn = true;
            }

            if ($column['Field'] === 'address') {
                $hasAddressColumn = true;
            }

            if ($column['Field'] === 'avatar') {
                $hasAvatarColumn = true;
            }

            if ($column['Field'] === 'create_at') {
                $hasCreateAtColumn = true;
            }

            if ($column['Field'] === 'update_at') {
                $hasUpdateAtColumn = true;
            }
        }

        $allColumnsExist = $hasIdColumn && $hasEmailColumn && $hasPhoneColumn &&
                          $hasPasswordColumn && $hasNameColumn && $hasGenderColumn &&
                          $hasBirthdayColumn && $hasAddressColumn && $hasAvatarColumn &&
                          $hasCreateAtColumn && $hasUpdateAtColumn;

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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ PATIENTSMODEL\n");
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
