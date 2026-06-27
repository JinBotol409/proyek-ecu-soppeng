<?php
header("X-Robots-Tag: noindex, nofollow", true);

$log = date("Y-m-d H:i:s") . "\n";
$log .= "POST:\n";
$log .= print_r($_POST, true);
$log .= "\nRAW:\n";
$log .= file_get_contents("php://input");
$log .= "\n------------------------\n";

file_put_contents(__DIR__ . "/callback_log.txt", $log, FILE_APPEND);

http_response_code(200);
echo "OK";