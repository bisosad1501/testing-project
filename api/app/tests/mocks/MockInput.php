<?php
/**
 * Mock cho Input để test
 */

class MockInput
{
    private static $postData = [];
    private static $requestMethod = 'GET';
    
    /**
     * Thiết lập mock POST data
     */
    public static function setPostData($data)
    {
        self::$postData = $data;
    }
    
    /**
     * Thiết lập mock request method
     */
    public static function setRequestMethod($method)
    {
        self::$requestMethod = $method;
    }
    
    /**
     * Mock cho Input::post()
     */
    public static function post($key = null)
    {
        if ($key === null) {
            return self::$postData;
        }
        
        return isset(self::$postData[$key]) ? self::$postData[$key] : null;
    }
    
    /**
     * Mock cho Input::method()
     */
    public static function method()
    {
        return self::$requestMethod;
    }
    
    /**
     * Reset mock data
     */
    public static function reset()
    {
        self::$postData = [];
        self::$requestMethod = 'GET';
    }
}
