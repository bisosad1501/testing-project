<?php
/**
 * Lớp kiểm thử NotificationsModel
 *
 * File: api/app/tests/models/NotificationsModelTest.php
 * Class: NotificationsModelTest
 *
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp NotificationsModel, bao gồm:
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

class NotificationsModelTest extends DatabaseTestCase
{
    /**
     * @var NotificationsModel Đối tượng model danh sách thông báo dùng trong test
     */
    protected $notificationsModel;

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
     * Khởi tạo NotificationsModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/NotificationsModel.php';
        $this->notificationsModel = new NotificationsModel();

        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }

        // Tên bảng đầy đủ với prefix
        $fullTableName = TABLE_PREFIX.TABLE_NOTIFICATIONS;

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
     * Tạo dữ liệu thông báo mẫu cho test
     *
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu thông báo mẫu
     */
    private function createTestNotification($override = [])
    {
        $timestamp = time();
        $datetime = date('Y-m-d H:i:s', $timestamp);
        return array_merge([
            'message' => 'Test notification message ' . $timestamp,
            'record_id' => $timestamp, // Sử dụng số nguyên thay vì chuỗi
            'record_type' => 'appointment',
            'is_read' => 0,
            'create_at' => $datetime,
            'update_at' => $datetime,
            'patient_id' => $timestamp // Sử dụng số nguyên thay vì chuỗi
        ], $override);
    }

    /**
     * Tạo bệnh nhân mẫu cho test
     *
     * @return int ID của bệnh nhân đã tạo
     */
    private function createTestPatient()
    {
        $tableName = TABLE_PREFIX.TABLE_PATIENTS;
        $timestamp = time();

        // Tạo dữ liệu bệnh nhân
        $patientData = [
            'name' => 'Test Patient ' . $timestamp,
            'email' => 'patient' . $timestamp . '@example.com',
            'phone' => '0123456789',
            'address' => 'Test Address',
            'gender' => 1, // 1 = male, 0 = female
            'birthday' => date('Y-m-d', $timestamp - 3600 * 24 * 365 * 30), // 30 years ago
            'password' => md5('password'),
            'create_at' => date('Y-m-d H:i:s', $timestamp),
            'update_at' => date('Y-m-d H:i:s', $timestamp)
        ];

        // Tạo câu lệnh SQL
        $columns = implode(', ', array_keys($patientData));
        $placeholders = implode(', ', array_fill(0, count($patientData), '?'));

        $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($patientData));

        return $this->pdo->lastInsertId();
    }

    /**
     * Tạo nhiều thông báo mẫu cho test
     *
     * @param int $count Số lượng thông báo cần tạo
     * @return array Mảng các ID của thông báo đã tạo
     */
    private function createMultipleTestNotifications($count = 10)
    {
        $tableName = TABLE_PREFIX.TABLE_NOTIFICATIONS;
        $ids = [];

        // Tạo bệnh nhân trước
        $patientId = $this->createTestPatient();

        for ($i = 0; $i < $count; $i++) {
            $notificationData = $this->createTestNotification([
                'message' => 'Notification_Test_' . $i . '_' . time(),
                'patient_id' => $patientId // Sử dụng ID bệnh nhân đã tạo
            ]);

            $sql = "INSERT INTO `{$tableName}` (message, record_id, record_type, is_read, create_at, update_at, patient_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $notificationData['message'],
                $notificationData['record_id'],
                $notificationData['record_type'],
                $notificationData['is_read'],
                $notificationData['create_at'],
                $notificationData['update_at'],
                $notificationData['patient_id']
            ]);

            $ids[] = $this->pdo->lastInsertId();
        }

        return $ids;
    }

    /**
     * Test case TC-01: Kiểm tra khởi tạo đối tượng NotificationsModel
     * Test case NOTIFS_CONS_01: Kiểm tra khởi tạo đối tượng NotificationsModel
     */
    public function testConstructor()
    {
        $this->logSection("NOTIFS_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng NotificationsModel", "Đối tượng được tạo thành công");

        // Kiểm tra đối tượng được khởi tạo
        $isInstanceOfNotifications = $this->notificationsModel instanceof NotificationsModel;
        $isInstanceOfDataList = $this->notificationsModel instanceof DataList;

        $this->logResult($isInstanceOfNotifications && $isInstanceOfDataList,
            "Instance created: " . ($isInstanceOfNotifications ? "Yes" : "No") .
            ", Extends DataList: " . ($isInstanceOfDataList ? "Yes" : "No"));

        $this->assertInstanceOf(NotificationsModel::class, $this->notificationsModel);
        $this->assertInstanceOf(DataList::class, $this->notificationsModel);

        // Kiểm tra query đã được thiết lập
        $query = $this->notificationsModel->getQuery();
        $hasQuery = !is_null($query);

        $this->logResult($hasQuery,
            "Query initialized: " . ($hasQuery ? "Yes" : "No"));

        $this->assertNotNull($query);
    }

    /**
     * Test case TC-02: Kiểm tra phương thức fetchData
     * Test case NOTIFS_FETCH_02: Kiểm tra phương thức fetchData
     */
    public function testFetchData()
    {
        $this->logSection("NOTIFS_FETCH_02: Kiểm tra phương thức fetchData");
        $this->logStep("Tạo dữ liệu test và lấy danh sách thông báo", "Danh sách thông báo được lấy thành công");

        // Tạo dữ liệu test
        $notificationIds = $this->createMultipleTestNotifications(5);
        $this->assertCount(5, $notificationIds);

        // Lấy danh sách thông báo
        $this->notificationsModel->fetchData();
        $data = $this->notificationsModel->getData();

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
     * Test case NOTIFS_PAGE_03: Kiểm tra phương thức paginate
     */
    public function testPaginate()
    {
        $this->logSection("NOTIFS_PAGE_03: Kiểm tra phương thức paginate");
        $this->logStep("Tạo dữ liệu test và phân trang", "Dữ liệu được phân trang thành công");

        // Tạo dữ liệu test
        $notificationIds = $this->createMultipleTestNotifications(10);
        $this->assertCount(10, $notificationIds);

        // Thiết lập phân trang
        $this->notificationsModel->setPageSize(3);
        $this->notificationsModel->setPage(2);

        // Lấy danh sách thông báo
        $this->notificationsModel->fetchData();
        $data = $this->notificationsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $totalCount = $this->notificationsModel->getTotalCount();
        $pageCount = $this->notificationsModel->getPageCount();
        $currentPage = $this->notificationsModel->getPage();

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
     * Test case NOTIFS_DATA_04: Kiểm tra phương thức getData
     */
    public function testGetData()
    {
        $this->logSection("NOTIFS_DATA_04: Kiểm tra phương thức getData");
        $this->logStep("Lấy dữ liệu từ model", "Dữ liệu được lấy thành công");

        // Tạo dữ liệu test
        $notificationIds = $this->createMultipleTestNotifications(3);
        $this->assertCount(3, $notificationIds);

        // Lấy danh sách thông báo
        $this->notificationsModel->fetchData();
        $data = $this->notificationsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $hasData = $dataCount > 0;
        $hasCorrectFields = false;

        if ($hasData) {
            $record = $data[0];
            $hasCorrectFields = isset($record->message) && isset($record->record_id) &&
                               isset($record->record_type) && isset($record->is_read) &&
                               isset($record->create_at) && isset($record->update_at) &&
                               isset($record->patient_id);
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
     * Test case NOTIFS_WHERE_05: Kiểm tra phương thức where
     */
    public function testWhere()
    {
        $this->logSection("NOTIFS_WHERE_05: Kiểm tra phương thức where");
        $this->logStep("Lọc dữ liệu theo trạng thái đã đọc", "Dữ liệu được lọc thành công");

        // Tạo bệnh nhân cho các thông báo
        $patientId = $this->createTestPatient();

        // Tạo 5 thông báo chưa đọc
        $tableName = TABLE_PREFIX.TABLE_NOTIFICATIONS;

        for ($i = 0; $i < 5; $i++) {
            $notificationData = $this->createTestNotification([
                'is_read' => 0,
                'patient_id' => $patientId
            ]);

            $sql = "INSERT INTO `{$tableName}` (message, record_id, record_type, is_read, create_at, update_at, patient_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $notificationData['message'],
                $notificationData['record_id'],
                $notificationData['record_type'],
                $notificationData['is_read'],
                $notificationData['create_at'],
                $notificationData['update_at'],
                $notificationData['patient_id']
            ]);
        }

        // Tạo 3 thông báo đã đọc
        for ($i = 0; $i < 3; $i++) {
            $notificationData = $this->createTestNotification([
                'is_read' => 1,
                'patient_id' => $patientId
            ]);

            $sql = "INSERT INTO `{$tableName}` (message, record_id, record_type, is_read, create_at, update_at, patient_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $notificationData['message'],
                $notificationData['record_id'],
                $notificationData['record_type'],
                $notificationData['is_read'],
                $notificationData['create_at'],
                $notificationData['update_at'],
                $notificationData['patient_id']
            ]);
        }

        // Lọc dữ liệu theo trạng thái đã đọc
        $this->notificationsModel->where("is_read", "=", 1);
        $this->notificationsModel->fetchData();
        $data = $this->notificationsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $allMatch = true;

        foreach ($data as $record) {
            if ($record->is_read != 1) {
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
     * Test case NOTIFS_ORDER_06: Kiểm tra phương thức orderBy
     */
    public function testOrderBy()
    {
        $this->logSection("NOTIFS_ORDER_06: Kiểm tra phương thức orderBy");
        $this->logStep("Sắp xếp dữ liệu theo ID", "Dữ liệu được sắp xếp thành công");

        // Tạo dữ liệu test
        $notificationIds = $this->createMultipleTestNotifications(5);
        $this->assertCount(5, $notificationIds);

        // Sắp xếp dữ liệu theo ID giảm dần
        $this->notificationsModel->orderBy("id", "DESC");
        $this->notificationsModel->fetchData();
        $data = $this->notificationsModel->getData();

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
     * Test case NOTIFS_SEARCH_07: Kiểm tra phương thức search
     *
     * Lưu ý: Phương thức search trong DataList không thực hiện tìm kiếm trực tiếp
     * mà chỉ lưu trữ từ khóa tìm kiếm. Các lớp con phải ghi đè phương thức này
     * để thực hiện tìm kiếm thực tế. Vì NotificationsModel không ghi đè phương thức này,
     * nên chúng ta chỉ kiểm tra xem từ khóa tìm kiếm có được lưu trữ đúng không.
     */
    public function testSearch()
    {
        $this->logSection("NOTIFS_SEARCH_07: Kiểm tra phương thức search");
        $this->logStep("Kiểm tra lưu trữ từ khóa tìm kiếm", "Từ khóa tìm kiếm được lưu trữ thành công");

        // Tạo từ khóa tìm kiếm
        $searchKeyword = "UNIQUE_KEYWORD";

        // Gọi phương thức search
        $this->notificationsModel->search($searchKeyword);

        // Kiểm tra xem từ khóa tìm kiếm có được lưu trữ không
        $storedKeyword = $this->notificationsModel->getSearchQuery();
        $searchPerformed = $this->notificationsModel->isSearchPerformed();

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
     * Test case NOTIFS_SCHEMA_08: Kiểm tra schema của bảng
     *
     * Lưu ý: Test case này chỉ có thể thực hiện khi sử dụng bảng thật trong cơ sở dữ liệu test,
     * không thể thực hiện khi sử dụng bảng tạm thời.
     */
    public function testTableSchema()
    {
        $this->logSection("NOTIFS_SCHEMA_08: Kiểm tra schema của bảng");
        $this->logStep("Kiểm tra cấu trúc bảng NOTIFICATIONS", "Bảng có đúng cấu trúc");

        $tableName = TABLE_PREFIX.TABLE_NOTIFICATIONS;

        // Lấy thông tin về các cột trong bảng
        $sql = "DESCRIBE `{$tableName}`";
        $columns = $this->executeQuery($sql);

        // Kiểm tra số lượng cột
        $columnCount = count($columns);

        // Kiểm tra các cột cần thiết
        $hasIdColumn = false;
        $hasMessageColumn = false;
        $hasRecordIdColumn = false;
        $hasRecordTypeColumn = false;
        $hasIsReadColumn = false;
        $hasCreateAtColumn = false;
        $hasUpdateAtColumn = false;
        $hasPatientIdColumn = false;

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

            if ($column['Field'] === 'message') {
                $hasMessageColumn = true;
                // Kiểm tra kiểu dữ liệu của cột message
                $isVarchar = strpos($column['Type'], 'varchar') !== false;
                $isNotNull = $column['Null'] === 'NO';
                $this->logResult($isVarchar,
                    "Message column: Varchar = " . ($isVarchar ? "Yes" : "No") .
                    ", Not Null = " . ($isNotNull ? "Yes" : "No"));
            }

            if ($column['Field'] === 'record_id') {
                $hasRecordIdColumn = true;
            }

            if ($column['Field'] === 'record_type') {
                $hasRecordTypeColumn = true;
            }

            if ($column['Field'] === 'is_read') {
                $hasIsReadColumn = true;
            }

            if ($column['Field'] === 'create_at') {
                $hasCreateAtColumn = true;
            }

            if ($column['Field'] === 'update_at') {
                $hasUpdateAtColumn = true;
            }

            if ($column['Field'] === 'patient_id') {
                $hasPatientIdColumn = true;
            }
        }

        $allColumnsExist = $hasIdColumn && $hasMessageColumn && $hasRecordIdColumn &&
                          $hasRecordTypeColumn && $hasIsReadColumn && $hasCreateAtColumn &&
                          $hasUpdateAtColumn && $hasPatientIdColumn;

        $this->logResult($allColumnsExist,
            "Table schema: Column count = " . $columnCount .
            ", All required columns exist = " . ($allColumnsExist ? "Yes" : "No"));

        $this->assertTrue($allColumnsExist);
    }

    /**
     * Test case TC-09: Kiểm tra phương thức getByPatientId
     * Test case NOTIFS_PATIENT_09: Kiểm tra phương thức getByPatientId
     */
    public function testGetByPatientId()
    {
        $this->logSection("NOTIFS_PATIENT_09: Kiểm tra phương thức getByPatientId");
        $this->logStep("Lấy thông báo theo patient_id", "Thông báo được lấy thành công");

        // Tạo bệnh nhân đặc biệt
        $specialPatientId = $this->createTestPatient();

        // Tạo 5 thông báo thông thường (với bệnh nhân khác)
        $this->createMultipleTestNotifications(5);

        // Tạo 3 thông báo với patient_id đặc biệt
        for ($i = 0; $i < 3; $i++) {
            $notificationData = $this->createTestNotification([
                'message' => 'Special Notification ' . $i . '_' . time(),
                'patient_id' => $specialPatientId
            ]);

            $tableName = TABLE_PREFIX.TABLE_NOTIFICATIONS;

            $sql = "INSERT INTO `{$tableName}` (message, record_id, record_type, is_read, create_at, update_at, patient_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $notificationData['message'],
                $notificationData['record_id'],
                $notificationData['record_type'],
                $notificationData['is_read'],
                $notificationData['create_at'],
                $notificationData['update_at'],
                $notificationData['patient_id']
            ]);
        }

        // Lấy thông báo theo patient_id
        $this->notificationsModel->where("patient_id", "=", $specialPatientId);
        $this->notificationsModel->fetchData();
        $data = $this->notificationsModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $allMatch = true;

        foreach ($data as $record) {
            if ($record->patient_id != $specialPatientId) { // Sử dụng != thay vì !== vì có thể có chuyển đổi kiểu
                $allMatch = false;
                break;
            }
        }

        $this->logResult($allMatch && $dataCount === 3,
            "Get by patient_id successful: " . ($allMatch ? "Yes" : "No") .
            ", Record count: " . $dataCount . " (expected: 3)");

        $this->assertTrue($allMatch);
        $this->assertEquals(3, $dataCount);
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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ NOTIFICATIONSMODEL\n");
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
