<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-30
 * Time: 11:21
 */

namespace app\common\model;


use think\Cache;
use think\Exception;
use think\Model;

class HeaderGroupProduct extends Model
{
    protected function initialize()
    {
        parent::initialize();
    }
    protected $autoWriteTimestamp = true;

    /**
     * 保存团购商品
     * @param $product
     * @param $pid
     * @param $header_id
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @return mixed
     */
    public function saveProduct($product, $pid, $header_id)
    {
        $group_id = $product["header_group_id"];
        Cache::rm($group_id.":ProductList");
        $redis = new \Redis2();
        if($pid>0){
            $p = model("HeaderGroupProduct")->where("id", $pid)->find();
            if(!$p){
                throw new Exception("商品不存在");
            }
            if($product["remain"]!=-1 && $p["sell_num"]>$product["remain"]){
                throw new Exception("商品限量不能小于当前售卖量");
            }
            $remain = $p["remain"];
            $res = $p->allowField(true)->save($product);
            if($res){
                if($product["remain"]>=0){
                    if($remain == -1){
                        $redis->set($pid.":remain", 1);
                        $remain = 0;
                    }
                    $stock = $product["remain"]-$remain;
                    if($stock>0){
                        for ($i=0; $i<$stock; $i++){
                            $redis->lpush($pid.":stock", 1);
                        }
                    }else{
                        for ($i=0; $i<-$stock; $i++){
                            $redis->lpop($pid.":stock");
                        }
                    }
                }else{
                    if($remain!=-1){
                        $redis->set($pid.":remain", 0);
                        $redis->delKey($pid.":stock");
                    }
                }
            }else{
                throw new Exception("商品更新失败");
            }
        }else{
            $product['header_id'] = $header_id;
            $pro = model("HeaderGroupProduct")->where([
                "header_group_id"=>$product["header_group_id"],
                "base_id"=>$product["base_id"],
                "product_name"=>$product["product_name"],
                "num"=>$product["num"]
            ])->find();
            if($pro){
                throw new Exception("同类规格商品已经存在");
            }
            $res = model("HeaderGroupProduct")->allowField(true)->save($product);
            if(!$res){
                throw new Exception("商品保存失败");
            }else{
                $pid = model("HeaderGroupProduct")->getLastInsID();
                if($product["remain"] == -1){
                    $redis->set($pid.":remain", 0);
                }else{
                    $redis->set($pid.":remain", 1);
                    for($i=0; $i<$product["remain"]; $i++){
                        $redis->lpush($pid.":stock", 1);
                    }
                }
            }
        }
        Cache::set($pid.":self_limit", $product["self_limit"]);
        Cache::set($pid.":group_limit", $product["group_limit"]);
        Cache::set($pid.":start_time", strtotime($product["start_time"]));
        return $pid;
    }

    /**
     * 获取商品库存  方法废弃
     * @param $header_product_id
     * @return mixed
     */
    public function getRemain($header_product_id)
    {
        if(!Cache::has($header_product_id.":stock")){
            $l = fopen(__PUBLIC__."/1.txt", "w");
            if(flock($l, LOCK_EX)){
                $remain = $this->where("id", $header_product_id)->value("remain");
                Cache::set($header_product_id.":stock", $remain);
                flock($l, LOCK_UN);
            }
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

    /**
     * 获取商品售卖开始时间
     */
    public function getProductStartTime($header_product_id)
    {
        if(!Cache::has($header_product_id.":start_time")){
            $remain = $this->where("id", $header_product_id)->value("start_time");
            Cache::set($header_product_id.":start_time", strtotime($remain));
        }
        return Cache::get($header_product_id.":start_time");
        
    }

    /**
     * 获取军团商品列表
     * @param int $gid 军团id
     * @return mixed
     */
    public function getProductList($gid)
    {
        if(Cache::has($gid.":ProductList")){
            return Cache::get($gid.":ProductList");
        }else{
            $product__list = model('HeaderGroupProduct')->where(['header_group_id' => $gid])->field('id, base_id, product_name, attr, num, unit, market_price, group_price, commission, group_limit, self_limit, product_desc, tag_name')->order("ord")->select();
            foreach ($product__list as $item) {
                //加载商品图片 废弃军团加载  直接读取商品库
                $item['product_img'] = model('ProductSwiper')->getSwiper($item["base_id"]);
            }
            Cache::set($gid.":ProductList", $product__list);
            return $product__list;
        }
    }

}