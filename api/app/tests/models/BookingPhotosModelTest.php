<?php
/**
 * Lớp kiểm thử BookingPhotosModel
 *
 * File: api/app/tests/models/BookingPhotosModelTest.php
 * Class: BookingPhotosModelTest
 *
 * Mô tả: Kiểm thử đầy đủ các chức năng của lớp BookingPhotosModel, bao gồm:
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

class BookingPhotosModelTest extends DatabaseTestCase
{
    /**
     * @var BookingPhotosModel Đối tượng model danh sách ảnh đặt lịch dùng trong test
     */
    protected $bookingPhotosModel;

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
     * Khởi tạo BookingPhotosModel và ghi lại thời gian bắt đầu
     */
    protected function setUp()
    {
        parent::setUp();
        require_once APP_PATH . '/models/BookingPhotosModel.php';
        $this->bookingPhotosModel = new BookingPhotosModel();

        if (!isset(self::$startTime)) {
            self::$startTime = microtime(true);
        }

        // Tên bảng đầy đủ với prefix
        $fullTableName = TABLE_PREFIX.TABLE_BOOKING_PHOTOS;

        // Khởi tạo bảng test - sử dụng tên bảng đầy đủ
        $this->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS `{$fullTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `booking_id` int(11) NOT NULL,
                `url` varchar(255) NOT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `booking_id` (`booking_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Xóa dữ liệu cũ từ bảng test (nếu có)
        $this->executeQuery("TRUNCATE TABLE `{$fullTableName}`");

        // Tạo bảng bookings tạm thời để đảm bảo ràng buộc khóa ngoại
        $bookingsTableName = TABLE_PREFIX.TABLE_BOOKINGS;
        $this->executeQuery("
            CREATE TEMPORARY TABLE IF NOT EXISTS `{$bookingsTableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `doctor_id` int(11) DEFAULT NULL,
                `patient_id` int(11) DEFAULT NULL,
                `service_id` int(11) DEFAULT NULL,
                `booking_name` varchar(255) DEFAULT NULL,
                `booking_phone` varchar(20) DEFAULT NULL,
                `name` varchar(255) DEFAULT NULL,
                `gender` tinyint(1) DEFAULT NULL,
                `birthday` date DEFAULT NULL,
                `address` text,
                `reason` text,
                `appointment_date` date DEFAULT NULL,
                `appointment_time` varchar(10) DEFAULT NULL,
                `status` varchar(20) DEFAULT NULL,
                `create_at` datetime DEFAULT NULL,
                `update_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Xóa dữ liệu cũ từ bảng bookings (nếu có)
        $this->executeQuery("TRUNCATE TABLE `{$bookingsTableName}`");

        // Tạo một booking mẫu để sử dụng trong test
        $this->createTestBooking();
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
     * Tạo một booking mẫu để sử dụng trong test
     *
     * @return int ID của booking đã tạo
     */
    private function createTestBooking()
    {
        $currentDate = date('Y-m-d');
        $currentDatetime = date('Y-m-d H:i:s');

        $bookingsTableName = TABLE_PREFIX.TABLE_BOOKINGS;

        $sql = "INSERT INTO `{$bookingsTableName}`
                (doctor_id, patient_id, service_id, booking_name, booking_phone, name, gender, birthday,
                address, reason, appointment_date, appointment_time, status, create_at, update_at)
                VALUES
                (1, 1, 1, 'Test Booking', '0987654321', 'Test Patient', 0, '1990-01-01',
                'Test Address', 'Test Reason', '{$currentDate}', '10:00', 'pending', '{$currentDatetime}', '{$currentDatetime}')";

        $this->executeQuery($sql);
        return $this->pdo->lastInsertId();
    }

    /**
     * Tạo dữ liệu ảnh đặt lịch mẫu cho test
     *
     * @param array $override Dữ liệu ghi đè
     * @return array Dữ liệu ảnh đặt lịch mẫu
     */
    private function createTestBookingPhoto($override = [])
    {
        $currentDatetime = date('Y-m-d H:i:s');

        return array_merge([
            'booking_id' => 1, // ID của booking đã tạo trong setUp
            'url' => 'http://example.com/photos/test_' . time() . '.jpg',
            'created_at' => $currentDatetime,
            'updated_at' => $currentDatetime
        ], $override);
    }

    /**
     * Tạo nhiều ảnh đặt lịch mẫu cho test
     *
     * @param int $count Số lượng ảnh đặt lịch cần tạo
     * @return array Mảng các ID của ảnh đặt lịch đã tạo
     */
    private function createMultipleTestBookingPhotos($count = 10)
    {
        $tableName = TABLE_PREFIX.TABLE_BOOKING_PHOTOS;
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $photoData = $this->createTestBookingPhoto([
                'url' => 'http://example.com/photos/test_' . time() . '_' . $i . '.jpg',
            ]);

            $columns = implode(', ', array_keys($photoData));
            $placeholders = implode(', ', array_fill(0, count($photoData), '?'));

            $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($photoData));

            $ids[] = $this->pdo->lastInsertId();
        }

        return $ids;
    }

    /**
     * Test case TC-01: Kiểm tra khởi tạo đối tượng BookingPhotosModel
     * Test case BPHOTOS_CONS_01: Kiểm tra khởi tạo đối tượng BookingPhotosModel
     */
    public function testConstructor()
    {
        $this->logSection("BPHOTOS_CONS_01: Kiểm tra khởi tạo đối tượng");
        $this->logStep("Khởi tạo đối tượng BookingPhotosModel", "Đối tượng được tạo thành công");

        // Kiểm tra đối tượng được khởi tạo
        $isInstanceOfBookingPhotos = $this->bookingPhotosModel instanceof BookingPhotosModel;
        $isInstanceOfDataList = $this->bookingPhotosModel instanceof DataList;

        $this->logResult($isInstanceOfBookingPhotos && $isInstanceOfDataList,
            "Instance created: " . ($isInstanceOfBookingPhotos ? "Yes" : "No") .
            ", Extends DataList: " . ($isInstanceOfDataList ? "Yes" : "No"));

        $this->assertInstanceOf(BookingPhotosModel::class, $this->bookingPhotosModel);
        $this->assertInstanceOf(DataList::class, $this->bookingPhotosModel);

        // Kiểm tra query đã được thiết lập
        $query = $this->bookingPhotosModel->getQuery();
        $hasQuery = !is_null($query);

        $this->logResult($hasQuery,
            "Query initialized: " . ($hasQuery ? "Yes" : "No"));

        $this->assertNotNull($query);
    }

    /**
     * Test case TC-02: Kiểm tra phương thức fetchData
     * Test case BPHOTOS_FETCH_02: Kiểm tra phương thức fetchData
     */
    public function testFetchData()
    {
        $this->logSection("BPHOTOS_FETCH_02: Kiểm tra phương thức fetchData");
        $this->logStep("Tạo dữ liệu test và lấy danh sách ảnh đặt lịch", "Danh sách ảnh đặt lịch được lấy thành công");

        // Tạo dữ liệu test
        $photoIds = $this->createMultipleTestBookingPhotos(5);
        $this->assertCount(5, $photoIds);

        // Lấy danh sách ảnh đặt lịch
        $this->bookingPhotosModel->fetchData();
        $data = $this->bookingPhotosModel->getData();

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
     * Test case BPHOTOS_PAGE_03: Kiểm tra phương thức paginate
     */
    public function testPaginate()
    {
        $this->logSection("BPHOTOS_PAGE_03: Kiểm tra phương thức paginate");
        $this->logStep("Tạo dữ liệu test và phân trang", "Dữ liệu được phân trang thành công");

        // Tạo dữ liệu test
        $photoIds = $this->createMultipleTestBookingPhotos(10);
        $this->assertCount(10, $photoIds);

        // Thiết lập phân trang
        $this->bookingPhotosModel->setPageSize(3);
        $this->bookingPhotosModel->setPage(2);

        // Lấy danh sách ảnh đặt lịch
        $this->bookingPhotosModel->fetchData();
        $data = $this->bookingPhotosModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $totalCount = $this->bookingPhotosModel->getTotalCount();
        $pageCount = $this->bookingPhotosModel->getPageCount();
        $currentPage = $this->bookingPhotosModel->getPage();

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
     * Test case BPHOTOS_DATA_04: Kiểm tra phương thức getData
     */
    public function testGetData()
    {
        $this->logSection("BPHOTOS_DATA_04: Kiểm tra phương thức getData");
        $this->logStep("Lấy dữ liệu từ model", "Dữ liệu được lấy thành công");

        // Tạo dữ liệu test
        $photoIds = $this->createMultipleTestBookingPhotos(3);
        $this->assertCount(3, $photoIds);

        // Lấy danh sách ảnh đặt lịch
        $this->bookingPhotosModel->fetchData();
        $data = $this->bookingPhotosModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $hasData = $dataCount > 0;
        $hasCorrectFields = false;

        if ($hasData) {
            $record = $data[0];
            $hasCorrectFields = isset($record->booking_id) &&
                               isset($record->url);
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
     * Test case BPHOTOS_WHERE_05: Kiểm tra phương thức where
     */
    public function testWhere()
    {
        $this->logSection("BPHOTOS_WHERE_05: Kiểm tra phương thức where");
        $this->logStep("Lọc dữ liệu theo booking_id", "Dữ liệu được lọc thành công");

        // Tạo một booking mới để sử dụng làm điều kiện lọc
        $bookingId = $this->createTestBooking();

        // Tạo 5 ảnh đặt lịch với booking_id mặc định
        $this->createMultipleTestBookingPhotos(5);

        // Tạo 3 ảnh đặt lịch với booking_id mới
        for ($i = 0; $i < 3; $i++) {
            $photoData = $this->createTestBookingPhoto([
                'booking_id' => $bookingId,
                'url' => 'http://example.com/photos/special_' . time() . '_' . $i . '.jpg',
            ]);

            $tableName = TABLE_PREFIX.TABLE_BOOKING_PHOTOS;

            $columns = implode(', ', array_keys($photoData));
            $placeholders = implode(', ', array_fill(0, count($photoData), '?'));

            $sql = "INSERT INTO `{$tableName}` ({$columns}) VALUES ({$placeholders})";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($photoData));
        }

        // Lọc dữ liệu theo booking_id
        $this->bookingPhotosModel->where("booking_id", "=", $bookingId);
        $this->bookingPhotosModel->fetchData();
        $data = $this->bookingPhotosModel->getData();

        // Kiểm tra kết quả
        $dataCount = count($data);
        $allMatch = true;

        foreach ($data as $record) {
            if ($record->booking_id != $bookingId) {
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
     * Test case BPHOTOS_ORDER_06: Kiểm tra phương thức orderBy
     */
    public function testOrderBy()
    {
        $this->logSection("BPHOTOS_ORDER_06: Kiểm tra phương thức orderBy");
        $this->logStep("Sắp xếp dữ liệu theo ID", "Dữ liệu được sắp xếp thành công");

        // Tạo dữ liệu test
        $photoIds = $this->createMultipleTestBookingPhotos(5);
        $this->assertCount(5, $photoIds);

        // Sắp xếp dữ liệu theo ID giảm dần
        $this->bookingPhotosModel->orderBy("id", "DESC");
        $this->bookingPhotosModel->fetchData();
        $data = $this->bookingPhotosModel->getData();

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
     * Test case BPHOTOS_SEARCH_07: Kiểm tra phương thức search
     *
     * Lưu ý: Phương thức search trong DataList không thực hiện tìm kiếm trực tiếp
     * mà chỉ lưu trữ từ khóa tìm kiếm. Các lớp con phải ghi đè phương thức này
     * để thực hiện tìm kiếm thực tế. Vì BookingPhotosModel không ghi đè phương thức này,
     * nên chúng ta chỉ kiểm tra xem từ khóa tìm kiếm có được lưu trữ đúng không.
     */
    public function testSearch()
    {
        $this->logSection("BPHOTOS_SEARCH_07: Kiểm tra phương thức search");
        $this->logStep("Kiểm tra lưu trữ từ khóa tìm kiếm", "Từ khóa tìm kiếm được lưu trữ thành công");

        // Tạo từ khóa tìm kiếm
        $searchKeyword = "UNIQUE_KEYWORD";

        // Gọi phương thức search
        $this->bookingPhotosModel->search($searchKeyword);

        // Kiểm tra xem từ khóa tìm kiếm có được lưu trữ không
        $storedKeyword = $this->bookingPhotosModel->getSearchQuery();
        $searchPerformed = $this->bookingPhotosModel->isSearchPerformed();

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
        fwrite(STDOUT, "📊 TỔNG KẾT KIỂM THỬ BOOKINGPHOTOSMODEL\n");
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