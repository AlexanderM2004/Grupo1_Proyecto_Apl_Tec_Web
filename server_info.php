<?php
header('Content-Type: application/json');
$hostname = gethostname();
$ip = $_SERVER['SERVER_ADDR'];
echo json_encode(['hostname' => $hostname, 'ip' => $ip]);
?>