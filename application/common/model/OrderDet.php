<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-10
 * Time: 10:26
 */

namespace app\common\model;


use think\Model;

class OrderDet extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    protected $autoWriteTimestamp = false;

    /**
     * 获取订单商品详情
     * @param $order_no
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderPro($order_no)
    {
        $pro_list = $this->where("order_no", $order_no)->field("leader_id, user_id, product_name, num, market_price, group_price, back_num, status, header_group_id, group_id, product_id, header_product_id")->select();
        foreach ($pro_list as $value) {
            $pid = $value['header_product_id'];
            $value['product_img'] = HeaderGroupProductSwiper::all(function ($query) use ($pid) {
                $query->where("header_group_product_id", $pid)->field("swiper_type types, swiper_url urlImg");
            });
        }
        return $pro_list;
    }


}