<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-18
 * Time: 10:08
 */

namespace app\header\controller;


use app\common\model\ProductSwiper;
use think\Cache;
use think\Exception;
use think\Log;

class Product extends ShopBase
{

    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 商品列表
     */
    public function index()
    {
        $param = input("get.");
        $model = model('Product')->where('header_id', session(config('headerKey')));
        if (isset($param["product_name"])) {
            $model->whereLike("product_name", "%" . $param["product_name"] . "%");
        }
        if(isset($param["cate_id"]) && $param["cate_id"] != ""){
            $model->where("cate_id", $param["cate_id"]);
        }
        $list = $model->order("stock, id desc")->paginate(10);
        $this->assign('list', $list);
        $this->assign("param", $param);
        $cate_list = model("Cate")->where("header_id", HEADER_ID)->column("cate_name", "id");
        $this->assign("cate_list", $cate_list);
        return $this->fetch();
    }

    /**
     * 分类更改
     */
    public function changeCate()
    {
        $id = input("id");
        $cate_id = input("cate_id");
        $product = model("Product")->where("id", $id)->find();
        if($product){
            $res = $product->save(["cate_id"=>$cate_id]);
            if($res){
                exit_json();
            }else{
                exit_json(-1, "分类变更失败");
            }
        }else{
            exit_json(-1, "商品不存在");
        }
    }
    /**
     * 添加商品
     */
    public function add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $data["product_name"] = '【'.$data["product_name"].'】';
            $swiper = input("swiper/a");
            $data['header_id'] = session(config('headerKey'));
            $res = model('Product')->allowField(['product_name', 'desc', 'header_id', 'unit', 'is_bulk', 'attr', 'cate_id'])->save($data);
            if (!$res) {
                exit_json(-1, "添加失败，刷新后重试");
            }
            $product_id = model('Product')->getLastInsID();
            if (count($swiper) > 0) {
                foreach ($swiper as $item) {
                    $arr = pathinfo($item);
                    $ext = in_array($arr['extension'], ["jpg", "jpeg", "png", "bmp"]) ? 1 : 2;
                    $swiper_list[] = [
                        'type' => $ext,
                        'url' => $item,
                        'product_id' => $product_id
                    ];
                }
                model('ProductSwiper')->saveAll($swiper_list);
            }
            exit_json();
        }
        $cate_list = model("Cate")->where("header_id", HEADER_ID)->column("cate_name", "id");
        $this->assign("cate_list", $cate_list);
        $unit_list = model("Unit")->where("header_id", HEADER_ID)->column("unit_name");
        $this->assign("unit_list", $unit_list);
        return $this->fetch();
    }

    /**
     * 商品入库
     */
    public function stockAdd()
    {
        $product_id = input("product_id");
        $product = model("Product")->where("id", $product_id)->find();
        $this->assign("item", $product);
        return $this->fetch();
    }

    /**
     * 添加入库记录
     */
    public function stockRecord()
    {
        $product_id = input("product_id");
        $product = model("Product")->where("id", $product_id)->find();
        if(!$product){
            exit_json(-1, "商品不存在，请先添加商品在进行入库操作");
        }
        $product->startTrans();
        model("ProductStockRecord")->startTrans();
        try{
            $data = [
                "product_id"=>$product_id,
                "purchase_price"=>input("purchase_price"),
                "market_price"=>input("market_price"),
                "num"=>input("num"),
                "type"=>input("type"),
                "stock_before"=>input("stock"),
                "stock_after"=>input("stock")+input("num")
            ];
            $res1 = model("ProductStockRecord")->save($data);
            if(!$res1){
                throw new Exception("添加入库记录失败");
            }

            $res2 = $product->setInc("stock", $data["num"]);
            if(!$res2){
                throw new Exception("增加商品库存失败");
            }
            $product->commit();
            model("ProductStockRecord")->commit();
            exit_json();
        } catch (\Exception $e){
            $product->rollback();
            model("ProductStockRecord")->rollback();
            exit_json(-1, $e->getMessage());
        }
    }

    /**
     * 商品出入库记录
     */
    public function stockList()
    {
        $product = model("Product")->where("id", input("product_id"))->find();
        $this->assign("product", $product);
        $list = model("ProductStockRecord")->where("product_id", input("product_id"))->order("create_time desc")->paginate(10);
        $this->assign("list", $list);
        return $this->fetch();
    }

    /**
     * 商品编辑
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        $product_id = input('product_id');
        $product = model('Product')->where('id', $product_id)->find();
        if (request()->isAjax()) {
            $data = input('post.');
            $swiper = input("swiper/a");
            if (count($swiper) > 0) {
                foreach ($swiper as $item) {
                    $arr = pathinfo($item);
                    $ext = in_array($arr['extension'], ["jpg", "jpeg", "png", "bmp"]) ? 1 : 2;
                    $swiper_list[] = [
                        'type' => $ext,
                        'url' => $item,
                        'product_id' => $product_id
                    ];
                }
                model('ProductSwiper')->where('product_id', $product_id)->delete();
                model('ProductSwiper')->saveAll($swiper_list);
                Cache::rm($product_id.":swiper");
            }
            $product->allowField(['product_name', 'cate_id', 'attr', 'unit', 'desc'])->save($data);
            exit_json();
        }
        $swiper_list = model('ProductSwiper')->where('product_id', $product_id)->select();
        $this->assign('item', $product);
        $this->assign('swiper_list', $swiper_list);
        $cate_list = model("Cate")->where("header_id", HEADER_ID)->column("cate_name", "id");
        $this->assign("cate_list", $cate_list);
        $unit_list = model("Unit")->where("header_id", HEADER_ID)->column("unit_name");
        $this->assign("unit_list", $unit_list);
        return $this->fetch();
    }

    /**
     * 删除商品
     */
    public function delData()
    {
        $product_id = input("idstr");
        $res = \app\common\model\Product::destroy(["id" => ["in", $product_id]]);
        if ($res) {
            ProductSwiper::destroy(["product_id" => ["in", $product_id]]);
            exit_json();
        } else {
            exit_json(-1, "删除失败");
        }
    }

    /**
     * 产品库商品_废弃
     */
    public function getHistory_back()
    {
        $pro_list = model("Product")->where("header_id", HEADER_ID)->select();
        foreach ($pro_list as $item) {
            $item["record_list"] = model("HeaderGroupProduct")->where("base_id", $item["id"])->select();
        }
        $this->assign("list", $pro_list);
        return $this->fetch("history");
    }

    /**
     * 获取历史商品
     */
    public function showHistory()
    {
        $pid = input("id");
        $record_list = model("HeaderGroupProduct")->where("base_id", $pid)->select();
        exit_json(1, "请求成功", $record_list);
    }

    /**
     * 商品标签列表
     */
    public function tagList()
    {
        $list = model("ProductTag")->where("header_id", session(config("headerKey")))->select();
        $this->assign("list", $list);
        return $this->fetch();
    }

    /**
     * 添加编辑标签
     */
    public function addTag()
    {
        $data = input("post.");
        $id = input("id");
        $item = model("ProductTag")->where("id", $id)->find();
        if (request()->isAjax()) {
            if ($item) {
                $res = $item->save($data);
            } else {
                $data["header_id"] = HEADER_ID;
                $res = model("ProductTag")->save($data);
            }
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, '保存失败');
            }
        }
        $this->assign("item", $item);
        return $this->fetch();

    }

    /**
     * 删除标签
     */
    public function delTag()
    {
        $id = input("idstr");
        $res = model("ProductTag")->where("id", $id)->delete();
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, '删除失败，刷新后重试');
        }

    }

    /**
     * 商品预热图
     */
    public function readyImg()
    {

        $list = model("PreImage")->where("header_id", HEADER_ID)->select();
        $this->assign("list", $list);
        return $this->fetch();

    }

    public function saveReadyImg()
    {
        $img_str = input("img_str");
        if ($img_str == "") {
            exit_json(-1, "未选择图片");
        }else{
            $img_arr = explode(",", $img_str);
            $data = [];
            foreach ($img_arr as $item){
                 $temp = [
                    "header_id"=>HEADER_ID,
                    "image_url"=>$item
                 ];
                 $r = model("PreImage")->where($temp)->find();
                 if(!$r){
                     $data[] = $temp;
                 }
            }
            if(count($data)>0){
                $res = model("PreImage")->saveAll($data);
            }else{
                $res = true;
            }
            if($res){
                exit_json();
            }else{
                exit_json(-1, "保存失败");
            }
        }
    }

    public function delReadyImg()
    {
        $ids = input("idstr");
        $res = model("PreImage")->whereIn("id", $ids)->delete();
        if($res){
            exit_json();
        }else{
            exit_json(-1, "操作失败");
        }

    }

    /**
     * 分类列表
     */
    public function cateIndex()
    {
        $list = model("Cate")->where("header_id", HEADER_ID)->order("create_time desc")->paginate(10);
        $this->assign("list", $list);
        return $this->fetch();
    }

    /**
     * 编辑商品分类
     */
    public function editCate()
    {
        $cate_id = input("cate_id");
        $data = input("post.");
        $cate = model("Cate")->where("id", $cate_id)->find();
        if(request()->isAjax()){
            if($cate){
                $res = $cate->save($data);
            }else{
                $data["header_id"] = HEADER_ID;
                $res = model("Cate")->save($data);
            }
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, '保存失败');
            }
        }
        $this->assign("item", $cate);
        return $this->fetch();
    }

    /**
     * 删除分类
     */
    public function delCate()
    {
        $item = model("Cate")->where("id", input("id"))->find();
        if($item){
            $p = model("Product")->where("cate_id", input('id'))->find();
            if($p){
                exit_json(-1, "当前分类下存在商品，不可删除分类");
            }
            if($item->delete()){
                exit_json();
            }else{
                exit_json(-1, "删除失败");
            }
        }else{
            exit_json(-1, "记录不存在");
        }

    }
    /**
     * 单位列表
     */
    public function unitIndex()
    {
        $list = model("Unit")->where("header_id", HEADER_ID)->order("create_time desc")->paginate(10);
        $this->assign("list", $list);
        return $this->fetch();
    }

    /**
     * 编辑单位
     */
    public function editUnit()
    {
        $cate_id = input("id");
        $data = input("post.");
        $cate = model("Unit")->where("id", $cate_id)->find();
        if(request()->isAjax()){
            if($cate){
                $res = $cate->save($data);
            }else{
                $data["header_id"] = HEADER_ID;
                $res = model("Unit")->save($data);
            }
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, '保存失败');
            }
        }
        $this->assign("item", $cate);
        return $this->fetch();
    }

    /**
     * 删除单位
     */
    public function delUnit()
    {
        $item = model("Unit")->where("id", input("id"))->find();
        if($item){
            if($item->delete()){
                exit_json();
            }else{
                exit_json(-1, "删除失败");
            }
        }else{
            exit_json(-1, "记录不存在");
        }

    }

}