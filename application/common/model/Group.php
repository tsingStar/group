<?php
/**
 * 团购信息
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-17
 * Time: 17:06
 */

namespace app\common\model;


use think\Cache;
use think\Model;

class Group extends Model
{

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取团购基础信息
     * @param $group_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGroupBaseInfo($group_id)
    {
        if(!Cache::has($group_id.":groupBaseInfo")){
            $data = $this->field('id group_id, header_group_id, header_id, leader_id, dispatch_type, dispatch_info, title, notice, pay_type, status, is_sec')->where("id", $group_id)->find();
            if(!$data){
                $this->error = "团购不存在";
                return false;
            }
            Cache::set($group_id.":groupBaseInfo", $data->getData());
        }
        return Cache::get($group_id.":groupBaseInfo");
    }

    /**
     * 格式化团购列表
     */
    public static function formatGroupList($list)
    {
        foreach ($list as $item) {
            $item['buyer_list'] = model('Order')->alias('a')->join('User b', "a.user_id=b.id")->where('a.group_id', $item['group_id'])->group("a.user_id")->field('b.avatar')->select();
            $item['product_list'] = model('HeaderGroupProduct')->getProductList($item["header_group_id"]);
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
        $num = $order_refund->where("group_id", $group_id)->count();
        if($num){
            $refund_list = $order_refund->where("group_id", $group_id)->field("sum(num) sum_num, product_name, group_price")->select();
        }else{
            $refund_list = [];
        }
        return $refund_list;
    }

}