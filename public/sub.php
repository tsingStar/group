<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/10/19
 * Time: 下午3:33
 */

$redis = new Redis();
$redis->connect("127.0.0.1", "6379");
$redis->auth("tsing");
$redis->subscribe(["chat1"], "subcall");
exit();
function subcall($redis, $chan, $msg){
    file_get_contents("http://group.com/wapp/Temp/test?msg=".$msg);
}