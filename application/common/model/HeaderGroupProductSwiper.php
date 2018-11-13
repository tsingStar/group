<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-30
 * Time: 11:21
 */

namespace app\common\model;


use think\Cache;
use think\Model;

class HeaderGroupProductSwiper extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * @param int $pid 军团id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSwiper($pid)
    {
        if (!Cache::has($pid . ":swiper")) {
            $data = $this->where("header_group_product_id", $pid)->field('swiper_type types, swiper_url urlImg')->order("create_time")->select();
            $list = [];
            foreach ($data as $item){
                $list[] = $item->getData();
            }
            Cache::set($pid . ":swiper", $list);
        }
        return Cache::get($pid . ":swiper");
    }

}