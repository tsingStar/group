<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-21
 * Time: 16:23
 */

namespace app\wapp\controller;


use app\common\model\WeiXinPay;
use think\Cache;
use think\Controller;
use think\Log;

class Temp extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    public function test()
    {
        $list = model("ProductSwiper")->getSwiper(348);
        print_r($list);
        exit;


    }

    public function test1()
    {
        $sql = "select sum(order_money-refund_money) sum_money, count(*) order_num, user_id, b.user_name, b.name, b.role_status from ts_order a left join ts_user b on a.user_id=b.id where a.header_id=1 group by user_id order by sum_money desc, order_num desc";
        $res = db()->query($sql);

    }

}