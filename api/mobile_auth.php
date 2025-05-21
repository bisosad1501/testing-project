<?php
/**
 * Script trung gian để xử lý xác thực cho mobile
 * 
 * Cách sử dụng:
 * 1. Thêm ?mobile=1 vào URL khi gọi API từ mobile
 * 2. Script này sẽ xử lý xác thực và chuyển tiếp yêu cầu đến index.php
 */

// Kiểm tra xem yêu cầu có phải từ mobile không
$is_mobile = isset($_GET['mobile']) && $_GET['mobile'] == '1';

if ($is_mobile) {
    // Xử lý Authorization header
    $authorization = null;
    
    // Kiểm tra Authorization header
    foreach (getallheaders() as $name => $value) {
        if (strtolower($name) === 'authorization') {
            $authorization = $value;
            break;
        }
    }
    
    // Kiểm tra cookie
    if (!$authorization && isset($_COOKIE['accessToken'])) {
        $authorization = 'JWT ' . $_COOKIE['accessToken'];
    }
    
    // Thiết lập biến môi trường
    if ($authorization) {
        $_SERVER['HTTP_AUTHORIZATION'] = $authorization;
    }
}

// Chuyển tiếp yêu cầu đến index.php
require_once 'index.php';
