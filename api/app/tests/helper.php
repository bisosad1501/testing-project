<?php
/**
 * Helper functions for tests
 *
 * This file contains helper functions that are needed for tests but are missing in the original code.
 * These functions are only used for testing and should not be used in production code.
 */

// Define __ (gettext) function if it doesn't exist
if (!function_exists('__')) {
    /**
     * Mock gettext function
     *
     * @param string $text Text to translate
     * @param array $params Parameters to replace in the text
     * @return string Translated text
     */
    function __($text, $params = []) {
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $text = str_replace($key, $value, $text);
            }
        }
        return $text;
    }
}

// Define readableRandomString function if it doesn't exist
if (!function_exists('readableRandomString')) {
    /**
     * Generate a readable random string
     *
     * This is a mock implementation for testing purposes only.
     *
     * @param int $length Length of the random string
     * @return string Random string
     */
    function readableRandomString($length = 10) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

// Define Email class if it doesn't exist
if (!class_exists('Email')) {
    /**
     * Mock Email class for testing
     *
     * This is a mock implementation for testing purposes only.
     */
    class Email {
        public $to = [];
        public $subject = '';

        /**
         * Add an email address to the recipient list
         *
         * @param string $email Email address
         * @return void
         */
        public function addAddress($email) {
            $this->to[] = $email;
        }

        /**
         * Send an email
         *
         * @param string $body Email body
         * @return bool Always returns true for testing
         */
        public function sendmail($body) {
            // Just return true for testing
            return true;
        }
    }
}

// Define GeneralData class if it doesn't exist
if (!class_exists('GeneralData')) {
    /**
     * Mock GeneralData class for testing
     *
     * This is a mock implementation for testing purposes only.
     */
    class GeneralData extends DataEntry {
        /**
         * Constructor
         */
        public function __construct() {
            parent::__construct();
            $this->set('data', json_encode(['site_name' => 'Test Site']));
            $this->is_available = true;
        }
    }
}

// Define MockDBResult class if it doesn't exist
if (!class_exists('MockDBResult')) {
    /**
     * Mock DB result class for testing
     *
     * This is a mock implementation for testing purposes only.
     */
    class MockDBResult {
        /**
         * Count the number of results
         *
         * @return int Number of results
         */
        public function count() {
            return 0;
        }

        /**
         * Get the results
         *
         * @return array Results
         */
        public function get() {
            return [];
        }
    }
}

// Define MockDB class for testing
/**
 * Mock DB class for testing
 *
 * This is a mock implementation for testing purposes only.
 * We use MockDB instead of DB to avoid conflicts with the Pixie DB class.
 */
class MockDB {
    public static $queryResult = [];
    public static $updateResult = 0;
    public static $selectResult = [];

    /**
     * Get a table instance
     *
     * @param string $table Table name
     * @return MockDB Table instance
     */
    public static function table($table) {
        return new self();
    }

    /**
     * Select columns from a table
     *
     * @param string $query SQL query
     * @return object Results
     */
    public static function select($query) {
        $result = new MockDBResult();
        return $result;
    }

    /**
     * Insert a record
     *
     * @param string $table Table name
     * @param array $data Data to insert
     * @return int ID of the inserted record
     */
    public static function insert($table, $data) {
        return 1;
    }

    /**
     * Add a where clause
     *
     * @param string $column Column name
     * @param string $operator Operator
     * @param mixed $value Value
     * @return MockDB Self instance
     */
    public function where($column, $operator, $value) {
        return $this;
    }

    /**
     * Add a limit clause
     *
     * @param int $limit Limit
     * @return MockDB Self instance
     */
    public function limit($limit) {
        return $this;
    }

    /**
     * Add an order by clause
     *
     * @param string $column Column name
     * @param string $direction Direction (asc or desc)
     * @return MockDB Self instance
     */
    public function orderBy($column, $direction) {
        return $this;
    }

    /**
     * Get results
     *
     * @return array Results
     */
    public function get() {
        if (is_callable(self::$queryResult)) {
            $callback = self::$queryResult;
            return $callback();
        }
        return self::$queryResult;
    }

    /**
     * Update records
     *
     * @param array $data Data to update
     * @return int Number of affected rows
     */
    public function update($data) {
        if (is_callable(self::$updateResult)) {
            $callback = self::$updateResult;
            return $callback();
        }
        return self::$updateResult;
    }

    /**
     * Insert a record (instance method)
     *
     * @param array $data Data to insert
     * @return int ID of the inserted record
     */
    public function insertRecord($data) {
        return 1;
    }

    /**
     * Delete records
     *
     * @return int Number of affected rows
     */
    public function delete() {
        return 1;
    }
}

// Define DB class if it doesn't exist and we're not using Pixie
if (!class_exists('DB') && !class_exists('Pixie\\AliasFacade')) {
    class_alias('MockDB', 'DB');
}

// Define Controller class if it doesn't exist
if (!class_exists('Controller')) {
    /**
     * Mock Controller class for testing
     *
     * This is a mock implementation for testing purposes only.
     */
    class Controller {
        protected $variables = [];
        public $resp;
        public $jsonechoCalled = false;
        public $headerCalled = false;
        public $headerRedirect = '';
        public $exitCalled = false;

        /**
         * Constructor
         */
        public function __construct() {
            $this->resp = new stdClass();
        }

        /**
         * Set a variable
         *
         * @param string $name Variable name
         * @param mixed $value Variable value
         * @return void
         */
        public function setVariable($name, $value) {
            $this->variables[$name] = $value;
        }

        /**
         * Get a variable
         *
         * @param string $name Variable name
         * @return mixed Variable value
         */
        public function getVariable($name) {
            return isset($this->variables[$name]) ? $this->variables[$name] : null;
        }

        /**
         * Echo JSON response
         *
         * @param mixed $data Data to echo
         * @return void
         */
        public function jsonecho($data = null) {
            $this->jsonechoCalled = true;
            // Don't actually echo anything in tests
        }

        /**
         * Set a header
         *
         * @param string $header Header to set
         * @return void
         */
        public function header($header) {
            $this->headerCalled = true;
            $this->headerRedirect = $header;
            // Don't actually set headers in tests
        }

        /**
         * Exit the script
         *
         * @return void
         */
        public function exitScript() {
            $this->exitCalled = true;
            // Don't actually exit in tests
        }

        /**
         * Get a model
         *
         * @param string $name Model name
         * @param int $id Model ID
         * @return object Model instance
         */
        public function model($name, $id = 0) {
            return new MockModel();
        }
    }
}

// Define MockInput class for testing
/**
 * Mock Input class for testing
 *
 * This is a mock implementation for testing purposes only.
 * Note: This class is not used directly. Instead, InputMock from bootstrap.php is used.
 */
class MockHelperInput {
    public static $method_value = 'GET';
    public static $put_values = [];
    public static $get_values = [];
    public static $post_values = [];
    public static $patch_values = [];

    /**
     * Get the request method
     *
     * @return string Request method
     */
    public static function method() {
        return self::$method_value;
    }

    /**
     * Get a PUT parameter
     *
     * @param string $key Parameter key
     * @return mixed Parameter value
     */
    public static function put($key = null) {
        if ($key === null) {
            return self::$put_values;
        }
        return isset(self::$put_values[$key]) ? self::$put_values[$key] : null;
    }

    /**
     * Get a GET parameter
     *
     * @param string $key Parameter key
     * @return mixed Parameter value
     */
    public static function get($key = null) {
        if ($key === null) {
            return self::$get_values;
        }
        return isset(self::$get_values[$key]) ? self::$get_values[$key] : null;
    }

    /**
     * Get a POST parameter
     *
     * @param string $key Parameter key
     * @return mixed Parameter value
     */
    public static function post($key = null) {
        if ($key === null) {
            return self::$post_values;
        }
        return isset(self::$post_values[$key]) ? self::$post_values[$key] : null;
    }

    /**
     * Get a PATCH parameter
     *
     * @param string $key Parameter key
     * @return mixed Parameter value
     */
    public static function patch($key = null) {
        if ($key === null) {
            return self::$patch_values;
        }
        return isset(self::$patch_values[$key]) ? self::$patch_values[$key] : null;
    }
}
