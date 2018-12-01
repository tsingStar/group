<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/11/30
 * Time: 15:23
 */

namespace app\index\controller;


use think\Controller;

class Test extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    public function addCount()
    {
        set_time_limit(0);
//        model("Test")->startTrans();
        $f = fopen(__PUBLIC__."/lock.txt", "w");
        if(flock($f, LOCK_EX)){
            $test = model("Test")->where("id", 1)->find();
            $test->setDec("num");
            model("Test1")->save(["num"=>1, "before"=>$test["num"]+1, "after"=>$test["num"]]);
            flock($f, LOCK_UN);
            fclose($f);
        }
//        model("Test")->commit();
        exit("ok");
    }

    public function decCount()
    {
        $test = model("Test")->where("id", 1)->find();
        $test->setDec("num");
        exit("ok");
    }

}