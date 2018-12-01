<?php
/**
 * 商品模型
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-25
 * Time: 13:55
 */

namespace app\common\model;


use think\Log;
use think\Model;

class Product extends Model
{

    protected function initialize()
    {
        parent::initialize();
    }

    protected $autoWriteTimestamp = true;
    /**
     * 获取商品库列表
     * @param int $header_id 城主id
     * @return mixed
     */
    public function getProductList($header_id)
    {
        //获取商品入库最后一次商品信息
        $sql = "select * from ts_product_stock_record where id in (select max(id) id from ts_product_stock_record where type=1 group by product_id)";
        $sql1 = "select a.*, b.purchase_price from ts_product a left join ($sql) b on a.id=b.product_id where a.header_id=$header_id";
        $list = db()->query($sql1);
        return $list;
    }

}