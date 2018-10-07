<?php
/**
 * 团购信息
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-17
 * Time: 17:06
 */

namespace app\common\model;


use think\Model;

class Group extends Model
{

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 格式化团购列表
     */
    public static function formatGroupList($list)
    {
        foreach ($list as $item) {
            $item['buyer_list'] = model('Order')->alias('a')->join('User b', "a.user_id=b.id")->where('a.group_id', $item['group_id'])->group("a.user_id")->field('b.avatar')->select();
            $product_list = model('GroupProduct')->where('group_id', $item['group_id'])->field("id, leader_id, header_group_id, group_id, header_product_id, product_name, commission, market_price, group_price, group_limit, self_limit, product_desc")->order('ord')->select();
            foreach ($product_list as $value) {
                $value['product_img'] = model('HeaderGroupProductSwiper')->where('header_group_product_id', $value['header_product_id'])->field('swiper_type types, swiper_url urlImg')->select();
            }
            $item['product_list'] = $product_list;
        }
        return $list;

    }

    /**
     * 获取团购取货详情
     */
    public function getGroupDetail($group_id)
    {
        $item = [];
        $temp = model("OrderDet")->where('group_id', $group_id)->group("product_id")->field("sum(num-back_num) sum_num, group_price, product_name")->select();
        $item['product_list'] = $temp;
        $t = model("Order")->where('group_id', $group_id)->field("sum(order_money) sum_money, sum(refund_money) sum_refund")->find();
        $item['sum_money'] = is_null($t['sum_money'])?0:$t['sum_money'];
        $item['sum_refund'] = is_null($t['sum_refund'])?0:$t['sum_refund'];
        return $item;
    }

    /**
     * 获取退货列表
     */
    public function getRefundList($group_id)
    {
        $order_refund = new OrderRefund();
        $refund_list = $order_refund->where("group_id", $group_id)->field("sum(num) sum_num, product_name, group_price")->select();
        return $refund_list;
    }

}