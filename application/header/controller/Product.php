<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-18
 * Time: 10:08
 */

namespace app\header\controller;


use app\common\model\ProductSwiper;
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
        $list = $model->order("id")->paginate(10);
        $this->assign('list', $list);
        $this->assign("param", $param);
        return $this->fetch();
    }

    /**
     * 添加商品
     */
    public function add()
    {
        if (request()->isPost()) {
            $data = input('post.');
            $swiper = input("swiper/a");
            $data['header_id'] = session(config('headerKey'));
            $res = model('Product')->allowField(['product_name', 'desc', 'header_id'])->save($data);
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
            }
            $product->allowField(['product_name', 'desc'])->save($data);
            exit_json();
        }
        $swiper_list = model('ProductSwiper')->where('product_id', $product_id)->select();
        $this->assign('item', $product);
        $this->assign('swiper_list', $swiper_list);
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
     * 产品库商品
     */
    public function getHistory()
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
        if($res){
            exit_json();
        }else{
            exit_json(-1,'删除失败，刷新后重试');
        }

    }

}