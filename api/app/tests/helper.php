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
    function __($text, $params = array()) {
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
        public $to = array();
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
            $this->set('data', json_encode(array('site_name' => 'Test Site')));
            $this->is_available = true;
        }
    }
}

// Define MockHelperInput class for testing if it doesn't exist
if (!class_exists('MockHelperInput')) {
    class MockHelperInput {
        /**
         * @var string Mock method value for Input::method()
         */
        public static $method_value = 'GET';
        
        /**
         * @var array Mock values for Input::post()
         */
        public static $post_values = array();
        
        /**
         * @var array Mock values for Input::get()
         */
        public static $get_values = array();
        
        /**
         * @var array Mock values for Input::put()
         */
        public static $put_values = array();
        
        /**
         * @var array Mock values for Input::patch()
         */
        public static $patch_values = array();
    }
}

// Override Input class methods if it exists
if (class_exists('Input')) {
    // Override method()
    Input::$methodMock = function() {
        return MockHelperInput::$method_value;
    };
    
    // Override post()
    Input::$postMock = function($key = null) {
        if ($key === null) {
            return MockHelperInput::$post_values;
        }
        return isset(MockHelperInput::$post_values[$key]) ? MockHelperInput::$post_values[$key] : null;
    };
    
    // Override get()
    Input::$getMock = function($key = null) {
        if ($key === null) {
            return MockHelperInput::$get_values;
        }
        return isset(MockHelperInput::$get_values[$key]) ? MockHelperInput::$get_values[$key] : null;
    };
    
    // Override put()
    Input::$putMock = function($key = null) {
        if ($key === null) {
            return MockHelperInput::$put_values;
        }
        return isset(MockHelperInput::$put_values[$key]) ? MockHelperInput::$put_values[$key] : null;
    };
    
    // Override patch()
    Input::$patchMock = function($key = null) {
        if ($key === null) {
            return MockHelperInput::$patch_values;
        }
        return isset(MockHelperInput::$patch_values[$key]) ? MockHelperInput::$patch_values[$key] : null;
    };
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
            return array();
        }
    }
}

// Define MockDB class for testing
if (!class_exists('MockDB')) {
    /**
     * Mock DB class for testing
     *
     * This is a mock implementation for testing purposes only.
     */
    class MockDB {
        /**
         * @var mixed The result to return from query methods
         */
        public static $queryResult = array();
        
        /**
         * @var mixed The result to return from update methods
         */
        public static $updateResult = 0;
        
        /**
         * @var MockDB|null The mock query builder instance
         */
        public static $mockQuery = null;
        
        /**
         * @var array Store the query parts for later inspection
         */
        private $query = array(
            'table' => '',
            'where' => array(),
            'limit' => null,
            'offset' => null,
            'orderBy' => array(),
            'joins' => array(),
            'select' => array(),
        );
        
        /**
         * @var mixed The result or exception to return from get()
         */
        private $result = null;
        
        /**
         * @var Exception|null The exception to throw from get()
         */
        private $exception = null;

        /**
         * Static factory method to start building a query
         *
         * @param string $table The table name
         * @return MockDB Instance of MockDB for method chaining
         */
        public static function table($table) {
            if (self::$mockQuery) {
                self::$mockQuery->query['table'] = $table;
                return self::$mockQuery;
            }
            $instance = new self();
            $instance->query['table'] = $table;
            return $instance;
        }
        
        /**
         * Add a raw SQL expression
         *
         * @param string $expression The raw SQL expression
         * @return stdClass Mock object for chaining
         */
        public static function raw($expression) {
            $raw = new stdClass();
            $raw->expression = $expression;
            return $raw;
        }
        
        /**
         * Add a where condition to the query
         *
         * @param string|callable $column The column name or a callable for subquery
         * @param string|null $operator The comparison operator
         * @param mixed|null $value The value to compare
         * @return MockDB Instance of MockDB for method chaining
         */
        public function where($column, $operator = null, $value = null) {
            if (is_callable($column)) {
                $this->query['where'][] = array('subquery' => true);
            } else {
                $this->query['where'][] = array(
                    'column' => $column,
                    'operator' => $operator,
                    'value' => $value,
                );
            }
            return $this;
        }
        
        /**
         * Add a limit to the query
         *
         * @param int $limit The maximum number of rows to return
         * @return MockDB Instance of MockDB for method chaining
         */
        public function limit($limit) {
            $this->query['limit'] = $limit;
            return $this;
        }
        
        /**
         * Add an offset to the query
         *
         * @param int $offset The number of rows to skip
         * @return MockDB Instance of MockDB for method chaining
         */
        public function offset($offset) {
            $this->query['offset'] = $offset;
            return $this;
        }
        
        /**
         * Add an order by clause to the query
         *
         * @param string $column The column to sort by
         * @param string $direction The sort direction
         * @return MockDB Instance of MockDB for method chaining
         */
        public function orderBy($column, $direction = 'asc') {
            $this->query['orderBy'][] = array(
                'column' => $column,
                'direction' => $direction,
            );
            return $this;
        }
        
        /**
         * Add columns to select in the query
         *
         * @param string|array $columns The columns to select
         * @return MockDB Instance of MockDB for method chaining
         */
        public function select($columns = '*') {
            if (!is_array($columns)) {
                $columns = array($columns);
            }
            $this->query['select'] = array_merge($this->query['select'], $columns);
            return $this;
        }
        
        /**
         * Add a left join to the query
         *
         * @param string $table The table to join
         * @param string $first The first column
         * @param string $operator The comparison operator
         * @param string $second The second column
         * @return MockDB Instance of MockDB for method chaining
         */
        public function leftJoin($table, $first, $operator, $second) {
            $this->query['joins'][] = array(
                'type' => 'left',
                'table' => $table,
                'first' => $first,
                'operator' => $operator,
                'second' => $second,
            );
            return $this;
        }
        
        /**
         * Count the number of results
         *
         * @return int Number of results
         */
        public function count() {
            if (is_array($this->result)) {
                return count($this->result);
            } elseif ($this->exception) {
                return 0;
            }
            return 0;
        }
        
        /**
         * Execute the query and return the result
         *
         * @return array The query result
         * @throws Exception
         */
        public function get() {
            if ($this->exception) {
                throw $this->exception;
            }
            return $this->result ?: array();
        }
        
        /**
         * Execute an update query and return the number of affected rows
         *
         * @param array $data The data to update
         * @return int The number of affected rows
         */
        public function update($data) {
            if (is_callable(self::$updateResult)) {
                return call_user_func(self::$updateResult);
            }
            return self::$updateResult;
        }
        
        /**
         * Insert data and return the ID of the inserted record
         *
         * @param array $data The data to insert
         * @return int The ID of the inserted record
         */
        public function insert($data) {
            return 1;
        }
        
        /**
         * Set the result or exception for the query
         *
         * @param mixed $result The result to return
         * @param Exception|null $exception The exception to throw
         * @return void
         */
        public function setResult($result = null, $exception = null) {
            $this->result = $result;
            $this->exception = $exception;
        }
        
        /**
         * Mock a query result or behavior
         *
         * @param object $mockQuery Mock query object with a get() method
         * @return void
         */
        public static function mockQuery($mockQuery) {
            self::$queryResult = function() use ($mockQuery) {
                return $mockQuery->get();
            };
        }
    }
}

// Define Controller class if it doesn't exist
if (!class_exists('Controller')) {
    /**
     * Mock Controller class for testing
     *
     * This is a mock implementation for testing purposes only.
     */
    class Controller {
        protected $variables = array();
        public $resp;
        public $jsonechoCalled = false;
        public $headerCalled = false;
        public $headerRedirect = '';
        public $exitCalled = false;
        public static $testMode = false;
        public static $modelMocks = [];
        public static $modelMethod = null;

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
        public static function model($name, $id = 0) {
            if (self::$testMode && isset(self::$modelMocks[$name])) {
                $mock = self::$modelMocks[$name];
                return is_callable($mock) ? call_user_func($mock, $id) : $mock;
            }
            if (self::$testMode && self::$modelMethod && is_callable(self::$modelMethod)) {
                $method = self::$modelMethod;
                return call_user_func($method, $name, $id);
            }
            $className = $name . 'Model';
            if (class_exists($className)) {
                return new $className($id);
            }
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
    public static $put_values = array();
    public static $get_values = array();
    public static $post_values = array();
    public static $patch_values = array();

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
?>