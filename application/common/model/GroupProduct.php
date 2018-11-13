<?php
/**
 * 团购产品
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-05
 * Time: 15:04
 */

namespace app\common\model;

use think\Cache;
use think\Model;

class GroupProduct extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }

    protected $autoWriteTimestamp = true;

    /**
     * 获取团购商品列表
     * @param $group_id
     */
    public function getProductList($group_id)
    {
        if(Cache::has($group_id.":product_list")){
            $product_list = Cache::get($group_id.":product_list");
        }else{
            $product_list = $this->field('id, leader_id, header_group_id, group_id, header_product_id, product_name, product_desc, commission, market_price, group_price, tag_name')->where("group_id", $group_id)->select();
            Cache::set($group_id.":product_list", $product_list);
        }
        return $product_list;
        
    }

}