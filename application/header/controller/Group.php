<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-03
 * Time: 09:24
 */

namespace app\header\controller;


use app\common\model\HeaderGroup;
use app\common\model\HeaderGroupProduct;
use app\common\model\HeaderGroupProductSwiper;
use app\common\model\LeaderMoneyLog;
use think\Cache;
use think\Exception;
use think\Log;

class Group extends ShopBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 团购列表
     */
    public function index()
    {

        $param = input("get.");
        $where = [
            "header_id" => HEADER_ID
        ];
        if (isset($param["group_title"]) && $param["group_title"] != "") {
            $where["group_title"] = ['like', '%' . $param['group_title'] . '%'];
        }
        $list = HeaderGroup::where($where)->order('create_time desc')->paginate(10);
        $this->assign('list', $list);
        $this->assign("param", $param);
        return $this->fetch();
    }

    /**
     * 添加团购
     */
    public function add()
    {
        $this->assign("address", $this->getDispatchInfo());
        $tag_arr = model("ProductTag")->where("header_id", HEADER_ID)->column("tag_name");
        array_unshift($tag_arr, "");
        $this->assign("tag_arr", json_encode($tag_arr));
        return $this->fetch();
    }

    /**
     * 编辑团购
     */
    public function edit()
    {
        $group_id = input("group_id");
        $group = HeaderGroup::get($group_id);
        $product_list = model("HeaderGroupProduct")->where(["header_group_id" => $group_id])->order("ord")->select();
        $this->assign("group", $group);
        $this->assign("product_list", $product_list);
        $this->assign("address", $this->getDispatchInfo());
        $tag_arr = model("ProductTag")->where("header_id", HEADER_ID)->column("tag_name");
        array_unshift($tag_arr, "");
        $this->assign("tag_arr", json_encode($tag_arr));
        return $this->fetch();
    }

    public function productList()
    {
        $group_id = input("group_id");
        $list = model("HeaderGroupProduct")->where("header_group_id", $group_id)->order("ord")->select();
        $this->assign("list", $list);
        $tag_arr = model("ProductTag")->where("header_id", HEADER_ID)->column("tag_name");
        array_unshift($tag_arr, "");
        $this->assign("tag_arr", json_encode($tag_arr));
        $this->assign("group_id", $group_id);
        return $this->fetch();
    }


    /**
     * 产品库商品
     */
    public function getHistory()
    {
        $pro_list = model("Product")->getProductList(HEADER_ID);
        $cate_arr = model("Cate")->where("header_id", HEADER_ID)->column("cate_name", "id");
        $cate_arr[0] = "未分类";
        $this->assign("cate_arr", $cate_arr);
        $this->assign("list", $pro_list);
        return $this->fetch("history");
    }

    /**
     * 保存团购商品信息
     */
    public function saveProduct()
    {
        $product = input("product/a");
        $pid = input("pid");
        try{
            $pid = model("HeaderGroupProduct")->saveProduct($product, $pid, HEADER_ID);
        }catch (\Exception $e){
            exit_json(-1, $e->getMessage());
        }
        exit_json(1, "保存成功", $pid);
    }

    /**
     * 增加/减少商品库存---------方法废弃
     */
    public function addStock()
    {
        $pid = input("pid");
        $num = input("value");
        if(!is_numeric($num)){
            exit_json(-1, "请求参数错误");
        }
        $product = model("HeaderGroupProduct")->where("id", $pid)->find();
        if(!$product){
            exit_json(-1, "商品不存在");
        }
        $redis = new \Redis2();
        if($num>0){
            $res = $product->setInc("remain", $num);
            if($res){
                //处理商品库存
                for($i=0; $i<$num; $i++){
                    $redis->lpush($pid.":stock", 1);
                }
            }
        }else{
            $res = $product->setDec("remain", $num);
            if($res){
                //处理商品库存
                for($i=0; $i<-$num; $i++){
                    $redis->lpop($pid.":stock");
                }
            }
        }
    }

    /**
     * 开启团购
     */
    public function start()
    {
        $group_id = input("group_id");
        $group = model("HeaderGroup")->where("id", $group_id)->find();
        if ($group["status"] == 1) {
            exit_json(-1, "团购已开启");
        } else {
            if ($group["comp_status"]) {
                exit_json(-1, "军团已结算，不可二次开启");
            }
            $group->status = 1;
            $group->open_time = date("Y-m-d");
            $res = $group->save();
            if ($res) {
                Cache::rm($group_id . ":HeaderGroup");
                $g_list = model("Group")->where(["header_group_id" => $group_id])->select();
                foreach ($g_list as $item) {
                    $item->save(["status" => 1]);
                    Cache::rm($item["id"] . ":groupBaseInfo");
                }
                exit_json();
            } else {
                exit_json(-1, "开启失败，刷新后重试");
            }
        }
    }

    /**
     * 结束团购
     */
    public function closeGroup()
    {
        $group_id = input("group_id");
        $group = model("HeaderGroup")->where("id", $group_id)->where("header_id", HEADER_ID)->find();
        if (!$group || $group["status"] == 2) {
            exit_json(-1, "团购不存在或已结束");
        } else {
            model("GroupPush")->save(["status" => 1], ["group_id" => $group_id]);
            $res = $group->save(["status" => 2]);
            if ($res) {
//                model("Group")->save(["status" => 2, "close_time" => date("Y-m-d H:i")], ["header_group_id" => $group_id, "status" => ["neq", 2]]);
                Cache::rm($group_id . ":HeaderGroup");
                $g_list = model("Group")->where(["header_group_id" => $group_id, "status" => ["neq", 2]])->select();
                foreach ($g_list as $item) {
                    $item->save(["status" => 2, "close_time" => date("Y-m-d H:i")]);
                    Cache::rm($item["id"] . ":groupBaseInfo");
                }
                exit_json();
            } else {
                exit_json(-1, "操作失败");
            }
        }

    }

    /**
     * 结算团购
     */
    public function comp()
    {
        $group_id = input("group_id");
        $res = model("HeaderGroup")->comAccount($group_id, HEADER_ID);
        if ($res === false) {
            exit_json(-1, model("HeaderGroup")->getError());
        }
        exit_json();

    }

    /**
     * 新建团购/编辑团购
     */
    public function applyGroup()
    {
        //团购基础信息
        $data = [
            'group_title' => input('group_title'),
            'header_id' => HEADER_ID,
            'group_notice' => input('group_notice'),
            'dispatch_type' => input('dispatch_type'),
            'dispatch_info' => input('dispatch_info'),
            'is_close' => input('is_close'),
            'status' => input('status'),
            'close_time' => input("close_time"),
            "is_sec" => input("is_sec"),
            'sec_time' => input("sec_time") == 0?0:strtotime(input("sec_time"))
        ];
        $group_id = input('group_id');
        if ($group_id == 0 && $data["status"] == 1) {
            $data["open_time"] = date("Y-m-d");
        }
        model('HeaderGroup')->startTrans();
//        model('HeaderGroupProduct')->startTrans();
        try {
            if ($group_id > 0) {
                $group = HeaderGroup::get($group_id);
                if ($group["status"] == 0 && $data["status"] == 1) {
                    $data["open_time"] = date("Y-m-d");
                }
                $res1 = $group->save($data);
            } else {
                $res1 = model('HeaderGroup')->save($data);
                $group_id = model('HeaderGroup')->getLastInsID();
            }

            //编辑军团信息完成后，添加缓存信息
            $data["id"] = $group_id;
            Cache::set($group_id . ":HeaderGroup", $data);

            if (!$res1) {
                throw new Exception('创建团购失败');
            }
            //团购商品信息
//            $product_list = input('product_list/a');
//            foreach ($product_list as $key => $item) {
//                $base_id = $item['base_id'];
//                $pro = \app\common\model\Product::get($base_id);
//                $product_data = [
//                    'header_id' => HEADER_ID,
//                    'product_name' => $item['product_name'],
//                    'header_group_id' => $group_id,
//                    'base_id' => $base_id,
//                    'remain' => ($item['remain'] >= 0 && is_numeric($item['remain'])) ? $item['remain'] : -1,
//                    'commission' => $item['commission'],
//                    'purchase_price' => $item['purchase_price'],
//                    'market_price' => $item['market_price'],
//                    'group_price' => $item['group_price'],
//                    'group_limit' => $item['group_limit'],
//                    'self_limit' => $item['self_limit'],
//                    'tag_name' => $item["tag_name"],
//                    'ord' => $key,
//                    'product_desc' => $pro['desc'],
//                ];
//                if ($item['id']) {
//                    $res2 = HeaderGroupProduct::update($product_data, ['id' => $item['id']]);
//                } else {
//                    $res2 = model('HeaderGroupProduct')->data($product_data)->isUpdate(false)->save();
//                }
//                if (!$res2) {
//                    throw new Exception('商品添加失败');
//                }
//                if ($item['id']) {
//                    $product_id = $item['id'];
//                } else {
//                    $product_id = model('HeaderGroupProduct')->getLastInsID();
//                }
//                //更新每个商品库存
////                Cache::set($product_id . ":stock", $product_data["remain"]);
//                //库存写入redis缓存，做队列处理
//                $redis = new \Redis2();
//                //记录商品是否为限购库存商品
//                if ($product_data["remain"] != -1) {
//                    //有库存限制
//                    $redis->set($product_id . ":remain", 1);
//                    $redis->delKey($product_id . ":stock");
//                    for ($i = 0; $i < $product_data["remain"]; $i++) {
//                        $redis->lpush($product_id . ":stock", 1);
//                    }
//                } else {
//                    //无库存限制
//                    $redis->set($product_id . ":remain", 0);
//                }
//
//                //保存商品个人限购及团限购
//                Cache::set($product_id . ":self_limit", $product_data["self_limit"]);
//                Cache::set($product_id . ":group_limit", $product_data["group_limit"]);
//
//            }
            model('HeaderGroup')->commit();
//            model('HeaderGroupProduct')->commit();
            exit_json();
        } catch (\Exception $e) {
            model('HeaderGroup')->rollback();
//            model('HeaderGroupProduct')->rollback();
            exit_json(-1, $e->getMessage());
        }
    }

    /**
     * 删除商品
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delProduct()
    {
        $pid = input("id");
        $pro = model("HeaderGroupProduct")->where("id", $pid)->delete();
        if ($pro) {
            exit_json();
        } else {
            exit_json(-1, "操作失败");
        }
    }


    /**
     * 获取自提点信息
     */
    public function getDispatchInfo()
    {
        $address = db("HeaderPickAddress")->where("header_id", HEADER_ID)->select();
        return $address;
    }

    /**
     * 删除自提点
     */
    public function delAddress()
    {
        $aid = input("aid");
        $res = db("HeaderPickAddress")->where("id", $aid)->delete();
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, "删除失败");
        }

    }

    /**
     * 自提点信息
     */
    public function address()
    {
        $aid = input("aid");
        $address = db("HeaderPickAddress")->where("id", $aid)->find();
        if (request()->isAjax()) {
            $data = input("post.");
            if ($address) {
                $data["update_time"] = time();
                $res = db("HeaderPickAddress")->where("id", $aid)->update($data);
                $ad = db("HeaderPickAddress")->where("id", $aid)->find();
            } else {
                $data["header_id"] = HEADER_ID;
                $res = db("HeaderPickAddress")->insertGetId($data);
                $ad = db("HeaderPickAddress")->where("id", $res)->find();
            }
            if ($res) {
                exit_json(1, "保存成功", $ad);
            } else {
                exit_json(-1, "保存失败");
            }
        }
        $this->assign("address", $address);
        return $this->fetch();
    }

    /**
     * 团购推送
     */
    public function groupPush()
    {
        $header_id = HEADER_ID;
        $group_id = input("group_id");
        $list = model("User")->field("id, user_name, avatar,name,residential")->where("header_id", $header_id)->where("role_status", 2)->select();
        $leader = model("GroupPush")->where("header_id", $header_id)->where("group_id", $group_id)->column("leader_id");
        $this->assign("list", $list);
        $this->assign("leader", $leader);
        $this->assign("group_id", $group_id);
        return $this->fetch();
    }

    /**
     * 推送团购
     */
    public function addPush()
    {
        $group_id = input("group_id");
        $g = model("HeaderGroup")->where("id", $group_id)->find();
        if ($g && $g["status"] == 2) {
            exit_json(-1, "团购已结束");
        }
        $leader_list = explode(",", input("leader_id"));
        foreach ($leader_list as $item) {
            $r = model("GroupPush")->where("header_id", HEADER_ID)->where("group_id", $group_id)->where("leader_id", $item)->find();
            if ($r) {
                $r->save(["status" => 0]);
            } else {
                $res = model("GroupPush")->data([
                    "header_id" => HEADER_ID,
                    "group_id" => $group_id,
                    "leader_id" => $item
                ])->isUpdate(false)->save();
            }
        }
        exit_json();
    }

}