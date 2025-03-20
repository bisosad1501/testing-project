<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// 1. Cấu hình error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Định nghĩa đường dẫn cơ bản
define('APP_PATH', realpath(__DIR__ . '/../'));

// 3. Autoload các classes (từ Composer)
require APP_PATH . '/vendor/autoload.php';

// 4. Load config database test
require APP_PATH . '/config/db.test.config.php';
require_once APP_PATH . '/core/DataEntry.php';
require_once APP_PATH . '/core/DataList.php';

// 5. Cấu hình timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');