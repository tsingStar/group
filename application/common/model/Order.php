<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-08
 * Time: 16:20
 */

namespace app\common\model;


use think\Model;

class Order extends Model
{

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 订单处理
     */
    public function orderSolve($order_info)
    {
        if(!$order_info){
            return false;
        }
        $product_list = $order_info['order_det'];

        //军团商品处理



        //团购商品处理

        //团长佣金计算

        //城主佣金计算

        return true;
    }

}