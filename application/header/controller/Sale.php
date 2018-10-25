<?php
/**
 * Created by PhpStorm.
 * User: tsing
 * Date: 2018/10/8
 * Time: 下午3:59
 */

namespace app\header\controller;

class Sale extends ShopBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 产品销量
     */
    public function productCount()
    {
        $param = input("get.");
        $model = model("HeaderGroupProduct");
        $model->field("sum(sell_num-refund_num) sum_num, base_id, product_name, product_desc")->where("header_id", HEADER_ID);
        if (isset($param["product_name"]) && $param["product_name"] != "") {
            $model->where("product_name", "like", "%" . $param["product_name"] . "%");
        }
        $list = $model->group("base_id")->order("sum(sell_num-refund_num) desc")->paginate(15);
        $this->assign("list", $list);
        $this->assign("param", $param);
        return $this->fetch();
    }

    /**
     * 每期销售额
     */
    public function saleAmount()
    {
//        $param = input("get.");
//        if(isset($param[""])){
//
//        }

        $model = model("HeaderGroup");
        $model->where("header_id", HEADER_ID);
        $list = $model->order("open_time desc")->paginate(15);
        $this->assign("list", $list);
        return $this->fetch();
    }

    /**
     * 销售详情
     */
    public function saleDetail()
    {
        $group_id = input("group_id");
        $group = model("HeaderGroup")->where("id", $group_id)->find();
        $list = model("HeaderGroupProduct")->where("header_group_id", $group_id)->select();
        $amount = model("HeaderGroupProduct")->where("header_group_id", $group_id)->sum("(group_price-purchase_price)*sell_num");
        $commission = model("HeaderGroupProduct")->where("header_group_id", $group_id)->sum("group_price*sell_num*commission/100");
        $sell_total = model("HeaderGroupProduct")->where("header_group_id", $group_id)->sum("group_price*sell_num");
        //获取当前军团红包使用费用
        $coupon_fee = db("CouponRecord")->where("header_group_id", $group_id)->where("status", 1)->sum("coupon");
        $coupon_fee = round($coupon_fee, 2);
        $this->assign("list", $list);
        $this->assign("group", $group);
        $this->assign("amount", $amount);
        $this->assign("commission", $commission);
        $this->assign("coupon_fee", $coupon_fee);
        $this->assign("sell_total", $sell_total);
        return $this->fetch();
    }

}