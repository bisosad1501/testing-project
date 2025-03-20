<?php
require_once __DIR__ . '/DatabaseTestCase.php';

class DatabaseConnectionTest extends DatabaseTestCase
{
    public function testConnection()
    {
        // Test kết nối PDO
        $this->assertNotNull($this->pdo, 'PDO connection should be established');
        fwrite(STDOUT, "\nDatabase connection successful!\n");
        
        // Test thực thi câu query đơn giản
        try {
            $stmt = $this->pdo->query('SELECT 1');
            $result = $stmt->fetch(PDO::FETCH_NUM);
            $this->assertEquals(1, $result[0], 'Should be able to execute a simple query');
            fwrite(STDOUT, "Query execution successful!\n");
        } catch (PDOException $e) {
            $this->fail('Database query failed: ' . $e->getMessage());
        }
    }

    public function testDatabaseName()
    {
        $stmt = $this->pdo->query("SELECT DATABASE()");
        $dbName = $stmt->fetchColumn();
        $this->assertEquals('doantotnghiep_test', $dbName, 'Should connect to test database');
        fwrite(STDOUT, sprintf("\nConnected to database: %s\n", $dbName));
    }
}