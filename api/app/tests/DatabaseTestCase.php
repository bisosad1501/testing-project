<?php
use PHPUnit\Framework\TestCase;
use Pixie\Connection;
use Viocon\Container;
use Pixie\QueryBuilder\QueryBuilderHandler;

class DatabaseTestCase extends TestCase
{
    protected static $connection;
    protected $pdo;

    protected function setUp()
    {
        parent::setUp();

        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            require_once __DIR__ . '/../config/db.test.config.php';

            // PDO connection setup
            $dsn = sprintf(
                "mysql:unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname=%s",
                DB_NAME
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Initialize Pixie only once
            if (!self::$connection) {
                $config = [
                    'driver'   => 'mysql',
                    'unix_socket' => '/Applications/MAMP/tmp/mysql/mysql.sock',
                    'database' => DB_NAME,
                    'username' => DB_USER,
                    'password' => DB_PASS,
                    'prefix'   => ''
                ];

                $container = new Container();
                self::$connection = new Connection('mysql', $config, 'DB', $container);
            }

            // Always set PDO instance for current test
            self::$connection->setPdoInstance($this->pdo);

            $this->pdo->beginTransaction();

        } catch (Exception $e) {
            $this->fail("Setup failed: " . $e->getMessage());
        }
    }
    /**
     * Dọn dẹp môi trường sau mỗi test
     */
    protected function tearDown()
    {
        // Rollback sau mỗi test
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        parent::tearDown();
    }

    /**
     * Thực thi một câu SQL và trả về tất cả kết quả
     *
     * @param string $sql Câu lệnh SQL
     * @param array $params Tham số cho câu lệnh
     * @return array Kết quả trả về
     */
    protected function executeQuery($sql, array $params = array())
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Thực thi một câu SQL và trả về một dòng kết quả
     *
     * @param string $sql Câu lệnh SQL
     * @param array $params Tham số cho câu lệnh
     * @return array|bool Một dòng kết quả hoặc false nếu không tìm thấy
     */
    protected function executeSingleQuery($sql, array $params = array())
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Kiểm tra xem bản ghi có tồn tại trong database không
     *
     * @param string $table Tên bảng
     * @param array $conditions Điều kiện tìm kiếm
     */
    protected function assertRecordExists($table, array $conditions)
    {
        $sql = "SELECT COUNT(*) as count FROM $table WHERE ";
        $whereClauses = array();
        $params = array();

        foreach ($conditions as $key => $value) {
            $whereClauses[] = "$key = ?";
            $params[] = $value;
        }

        $sql .= implode(' AND ', $whereClauses);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertGreaterThan(0, $result['count'], "Không tìm thấy bản ghi trong bảng $table.");
    }

    /**
     * Kiểm tra xem bản ghi không tồn tại trong database
     *
     * @param string $table Tên bảng
     * @param array $conditions Điều kiện tìm kiếm
     */
    protected function assertRecordNotExists($table, array $conditions)
    {
        $sql = "SELECT COUNT(*) as count FROM $table WHERE ";
        $whereClauses = array();
        $params = array();

        foreach ($conditions as $key => $value) {
            $whereClauses[] = "$key = ?";
            $params[] = $value;
        }

        $sql .= implode(' AND ', $whereClauses);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals(0, $result['count'], "Bản ghi vẫn tồn tại trong bảng $table.");
    }

    /**
     * Lấy một bản ghi từ database
     *
     * @param string $table Tên bảng
     * @param array $conditions Điều kiện tìm kiếm
     * @return array|bool Bản ghi hoặc false nếu không tìm thấy
     */
    protected function getRecord($table, array $conditions)
    {
        $sql = "SELECT * FROM $table WHERE ";
        $whereClauses = array();
        $params = array();

        foreach ($conditions as $key => $value) {
            $whereClauses[] = "$key = ?";
            $params[] = $value;
        }

        $sql .= implode(' AND ', $whereClauses);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Chèn một bản ghi vào database như một fixture
     *
     * @param string $table Tên bảng
     * @param array $data Dữ liệu cần chèn
     * @return string|int ID của bản ghi vừa chèn
     */
    protected function insertFixture($table, array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->pdo->lastInsertId();
    }

    /**
     * Chèn nhiều bản ghi fixture vào database
     *
     * @param array $fixtures Mảng dữ liệu fixture
     */
    protected function loadFixtures(array $fixtures)
    {
        foreach ($fixtures as $table => $records) {
            foreach ($records as $record) {
                $this->insertFixture($table, $record);
            }
        }
    }

    /**
     * So sánh kết quả từ Model với dữ liệu trong database
     *
     * @param array $expectedData Dữ liệu mong đợi
     * @param string $table Tên bảng
     * @param array $conditions Điều kiện tìm kiếm
     * @param array $fields Các trường cần so sánh (mặc định là tất cả)
     */
    protected function assertModelMatchesDatabase(array $expectedData, $table, array $conditions, array $fields = array())
    {
        $record = $this->getRecord($table, $conditions);
        $this->assertNotFalse($record, "Không tìm thấy bản ghi trong bảng $table");

        if (empty($fields)) {
            $fields = array_keys($expectedData);
        }

        foreach ($fields as $field) {
            if (isset($expectedData[$field])) {
                $this->assertEquals(
                    $expectedData[$field],
                    $record[$field],
                    "Trường '$field' không khớp với giá trị trong database"
                );
            }
        }
    }
}