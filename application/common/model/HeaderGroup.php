<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-30
 * Time: 11:19
 */

namespace app\common\model;


use think\Log;
use think\Model;

class HeaderGroup extends Model
{

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 核算团购信息
     * @param int $group_id 军团id
     * @param int $header_id 城主id
     * @return bool
     * @throws \think\Exception
     */
    public function comAccount($group_id, $header_id)
    {
        $group = $this->where("id", $group_id)->where("header_id", $header_id)->find();
        if(!$group){
            $this->error = "军团不存在";
            return false;
        }
        if($group["status"] != 2){
            $this->error = "军团未结团";
            return false;
        }
        $group->save(["comp_status"=>1, "comp_time"=>date("Y-m-d H:i:s")]);
        $sum_money = model("GroupProduct")->where("header_group_id", $group_id)->sum("sell_num*group_price*(1-commission/100)");
        //团购销售汇总
        $group_list = model("GroupProduct")->where("header_group_id", $group_id)->group("group_id")->field("sum(sell_num*group_price*commission/100) sum_money, leader_id, group_id")->select();
        $sum_money = $sum_money * (1 - 0.006);

        //获取当前军团红包使用费用
        $coupon_fee = db("CouponRecord")->where("header_group_id", $group_id)->where("status", 1)->sum("coupon");
        $coupon_fee = round($coupon_fee, 2);
        model("Header")->where("id", $group["header_id"])->setInc("amount_lock", round($sum_money-$coupon_fee,2));
        model("HeaderMoneyLog")->saveAll([
            [
                "header_id" => $group["header_id"],
                "type" => 1,
                "money" => round($sum_money - $coupon_fee, 2),
                "order_no" => $group_id
            ],
            [
                "header_id" => $group["header_id"],
                "type" => 5,
                "money" => $coupon_fee,
                "order_no" => $group_id
            ],

        ]);
        foreach ($group_list as $item) {
            model("User")->where("id", $item["leader_id"])->setInc("amount_lock", $item["sum_money"] * (1 - 0.006));
            model("LeaderMoneyLog")->data([
                "leader_id" => $item["leader_id"],
                "type" => 1,
                "money" => round($item["sum_money"] * (1 - 0.006), 2),
                "order_no" => $item["group_id"]
            ])->isUpdate(false)->save();
        }
        return true;
    }



}