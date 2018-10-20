<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-21
 * Time: 16:23
 */

namespace app\wapp\controller;


use think\Controller;
use think\Log;

class Temp extends Controller
{

    protected function _initialize()
    {
        parent::_initialize();

    }

    public function subcribe()
    {
        $redis = new \Redis();
        $redis->connect("127.0.0.1", "6379");
        $redis->auth("tsing");
        $redis->subscribe(["chat1"], "subcall");
    }

    function subcall($redis, $chan, $msg){
        Log::error("123123123123123");
    }

    public function pubs()
    {
        $redis = new \Redis();
        $redis->connect("127.0.0.1", "6379");
        $redis->auth("tsing");
        $redis->publish("chat1", "woshi chat");

    }





    /**
     * 测试方法
     */
    public function test()
    {
        Log::error(input("msg"));
    }

    /**
     *
     */
    public function getZSet()
    {
        $redis = new \Redis();
        $redis->connect("127.0.0.1", "6379");
        $redis->auth("tsing");
        $list = $redis->zRevRange("group_1", 0, -1);
        foreach ($list as $value){
            $value = json_decode($value, true);
            echo $value["user_name"];
        }
    }



}