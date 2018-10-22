<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-30
 * Time: 11:21
 */

namespace app\common\model;


use think\Model;

class HeaderGroupProductSwiper extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    public function getSwiper($pid)
    {
        return $this->where("header_group_product_id", $pid)->field('swiper_type types, swiper_url urlImg')->cache(true)->order("create_time")->select();
    }

}