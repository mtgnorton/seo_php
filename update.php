<?php
error_reporting(1);
$token = 'shangtukeji';
$json  = json_decode(file_get_contents('php://input'), true);
if ($json['password'] != $token && $_GET['password'] != $token) {
    exit('error request');
}

file_put_contents("/home/seo_master/git_pull.log", "日志记录开始 时间为:" . date("Y-m-d H:i:s") . PHP_EOL, time());
$rs = exec("sudo /home/seo_master/git-update.sh 2>&1", $output, $returnVal);
var_dump($rs, $output, $returnVal);
file_put_contents("/home/seo_master/git_pull.log", "日志记录完成 时间为:" . date("Y-m-d H:i:s") . PHP_EOL, time());
