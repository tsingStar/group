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

class HeaderGroupProduct extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }
    protected $autoWriteTimestamp = true;

    /**
     * 获取商品库存
     * @param $header_product_id
     * @return mixed
     */
    public function getRemain($header_product_id)
    {
        if(!Cache::has($header_product_id.":stock")){
            $remain = $this->where("id", $header_product_id)->value("remain");
            Cache::set($header_product_id.":stock", $remain);
        }
        return Cache::get($header_product_id.":stock");

    }
    public function getSelfLimit($header_product_id)
    {
        if(!Cache::has($header_product_id.":self_limit")){
            $remain = $this->where("id", $header_product_id)->value("self_limit");
            Cache::set($header_product_id.":self_limit", $remain);
        }
        return Cache::get($header_product_id.":self_limit");

    }
    public function getGroupLimit($header_product_id)
    {
        if(!Cache::has($header_product_id.":group_limit")){
            $remain = $this->where("id", $header_product_id)->value("group_limit");
            Cache::set($header_product_id.":group_limit", $remain);
        }
        return Cache::get($header_product_id.":group_limit");

    }

}