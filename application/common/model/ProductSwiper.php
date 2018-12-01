<?php
/**
 * 商品轮播
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-25
 * Time: 14:41
 */

namespace app\common\model;


use think\Cache;
use think\Model;

class ProductSwiper extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    protected $autoWriteTimestamp = false;

    /**
     * @param int $pid 商品库id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSwiper($pid)
    {
        if (!Cache::has($pid . ":swiper")) {
            $data = $this->where("product_id", $pid)->field('type types, url urlImg')->order("id")->select();
            $list = [];
            foreach ($data as $item){
                $list[] = $item->getData();
            }
            Cache::set($pid . ":swiper", $list);
        }
        return Cache::get($pid . ":swiper");
    }

}