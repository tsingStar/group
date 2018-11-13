<?php
/**
 *
 * 异步处理类
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/11/9
 * Time: 16:37
 */

namespace app\wapp\controller;


use think\Controller;
use think\Log;

class Async extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
        ignore_user_abort(true);
    }
    //保存团员访问团长记录
    public function saveLeaderRecord()
    {
        $user_id = input("user_id");
        $leader_id = input("leader_id");
        $lr = db("LeaderRecord")->where("user_id", $user_id)->find();
        if ($lr) {
            db("LeaderRecord")->where("user_id", $user_id)->update(["leader_id" => $leader_id]);
        } else {
            db("LeaderRecord")->insert(["user_id" => $user_id, "leader_id" => $leader_id]);
        }
        exit();
    }

    /**
     * 保存浏览数量
     */
    public function saveScanNum()
    {
        $group_id = input("group_id");
        model("Group")->where("id", $group_id)->setInc("scan_num");
        exit;
    }

    /**
     * 减少商品库存
     */
    public function decRemain()
    {
        $f = fopen(__PUBLIC__."/remain.lock", "w");
        if(flock($f, LOCK_EX)){
            $header_product_id = input("header_product_id");
            $num = input("num");
            model("HeaderGroupProduct")->where("id", $header_product_id)->setDec("remain", $num);
            flock($f, LOCK_UN);
            fclose($f);
        }

    }




}