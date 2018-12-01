<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-08
 * Time: 16:20
 */

namespace app\common\model;


use think\Log;
use think\Model;

class Order extends Model
{

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 订单商品处理
     */
    public function orderSolve($product_list)
    {
        if(!$product_list){
            return false;
        }

        foreach ($product_list as $item){
            //军团商品处理
            $hgp = \model("HeaderGroupProduct")->where("id", $item['header_product_id'])->setInc('sell_num', $item['num']);
//            $sql = "update ts_header_group_product set remain=remain-".$item["num"]." where remain-".$item["num"].">=0 and id=".$item["header_product_id"];
//            $res = db()->execute($sql);
//            if(!$res){
//                Log::error("军团商品处理失败".$item["header_product_id"]);
//            }

            //处理商品库存
            $product = model("HeaderGroupProduct")->where("id", $item["header_product_id"])->find();
            $base_pro = model("Product")->where("id", $product['base_id'])->find();
//            model("ProductStockRecord")->data([
//                "type"=>2,
//                "product_id"=>$product["base_id"],
//                "group_price"=>$item["group_price"],
//                "num"=>$item["num"]*$product["num"],
//                "stock_before"=>$base_pro["stock"],
//                "stock_after"=>$base_pro["stock"]-$item["num"]*$product["num"]
//            ])->isUpdate(false)->save();
            $base_pro->setDec("stock", $item["num"]*$product["num"]);

            //团购商品处理
            \model('GroupProduct')->where('id', $item['product_id'])->setInc('sell_num', $item['num']);
        }

        //团长佣金计算

        //城主佣金计算

        return true;
    }

    /**
     * 获取当前团购下订单数量
     */
    public function getLastNum($group_id)
    {
        $num = $this->where("group_id", $group_id)->count();
        return $num+1;
    }

}