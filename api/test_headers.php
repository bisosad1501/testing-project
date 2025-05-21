<?php
// Hiển thị tất cả các header
echo "Headers từ getallheaders():\n";
var_dump(getallheaders());

echo "\n\nHeaders từ _SERVER:\n";
var_dump($_SERVER);

// Kiểm tra header Authorization cụ thể
echo "\n\nHeader Authorization:\n";
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    echo "HTTP_AUTHORIZATION: " . $_SERVER['HTTP_AUTHORIZATION'] . "\n";
} else {
    echo "HTTP_AUTHORIZATION không tồn tại\n";
}

// Kiểm tra các biến thể khác của header Authorization
$authHeaders = [
    'REDIRECT_HTTP_AUTHORIZATION',
    'PHP_AUTH_USER',
    'PHP_AUTH_PW',
    'PHP_AUTH_DIGEST'
];

foreach ($authHeaders as $header) {
    if (isset($_SERVER[$header])) {
        echo $header . ": " . $_SERVER[$header] . "\n";
    }
}
?>
