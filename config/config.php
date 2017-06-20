<?php

date_default_timezone_set("UTC");
require_once dirname(__FILE__) . "/database.php";
require_once dirname(__FILE__) . "/redis.php";

$db = NULL;
try {
    $db = new PDO("mysql:host=" . $config['database']['hostname'] . ";dbname=" . $config['database']['database'], $config['database']['username'], $config['database']['password']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $log = "Connection to mysql failed: " . $e->getMessage();
    echo "[ERROR] - - " . $log . PHP_EOL;
    die();
}

$redis = new Redis();
$connect = $redis->connect($config['redis']['hostname'], $config['redis']['port']);
if ($connect === FALSE) {
    echo "[ERROR] - - Connection to redis failed" . PHP_EOL;
    die;
}