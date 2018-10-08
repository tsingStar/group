<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-27
 * Time: 08:22
 */

namespace app\wapp\controller;


use app\common\model\Group;
use think\Controller;
use think\Exception;
use think\Log;

class Interval extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 主定时入口
     */
    public function index()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        echo "启动成功";
        $i = 1;
        while ($i<20){
            file_get_contents("https://www.ybt9.com/wapp/Interval/closeGroup");
            ob_flush();
            flush();
            sleep(1);
            $i++;
        }
        exit();
    }



    /**
     * 定时结束军团
     */
    public function closeGroup()
    {
        ignore_user_abort(true);
        set_time_limit(0);
        $list = model("HeaderGroup")->where("UNIX_TIMESTAMP(close_time)<" . time())->where("status", 1)->select();
        try {
            foreach ($list as $key=>$value) {
                $group_id = $value["id"];
                $res = $value->save(["status" => 2]);
                if ($res) {
//                    model("Group")->isUpdate(true)->save(["status" => 2, "close_time" => date("Y-m-d H:i")], ["header_group_id" => $group_id, "status" => ["neq", 2]]);
                    Group::update(["status" => 2, "close_time" => date("Y-m-d H:i")], ["header_group_id" => $group_id, "status" => ["neq", 2]]);
                    //军团销售汇总
                    $sum_money = model("HeaderGroupProduct")->where("header_group_id", $group_id)->sum("sell_num*group_price*(1-commission/100)");
                    //团购销售汇总
                    $group_list = model("GroupProduct")->where("header_group_id", $group_id)->group("group_id")->field("sum(sell_num*group_price*commission/100) sum_money, leader_id, group_id")->select();
                    model("Header")->where("id", $value["header_id"])->setInc("amount_lock", $sum_money);
                    model("HeaderMoneyLog")->data([
                        "header_id" => $value["header_id"],
                        "type" => 1,
                        "money" => $sum_money,
                        "order_no" => $group_id
                    ])->isUpdate(false)->save();
                    foreach ($group_list as $item) {
                        model("User")->where("id", $item["leader_id"])->setInc("amount_lock", $item["sum_money"]);
                        model("LeaderMoneyLog")->data([
                            "leader_id" => $item["leader_id"],
                            "type" => 1,
                            "money" => $item["sum_money"],
                            "order_no" => $item["group_id"]
                        ])->isUpdate(false)->save();
                    }
                } else {
                    throw new Exception("团购状态处理失败");
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        exit("ok");
    }
}