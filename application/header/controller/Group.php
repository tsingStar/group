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

    /**
     * 开启团购
     */
    public function start()
    {
        $group_id = input("group_id");
        $group = model("HeaderGroup")->where("id", $group_id)->find();
        if ($group["status"] != 0) {
            exit_json(-1, "团购已开启");
        } else {
            if($group["comp_status"]){
                exit_json(-1, "军团已结算，不可二次开启");
            }
            $group->status = 1;
            $group->open_time = date("Y-m-d");
            $res = $group->save();
            if ($res) {
                $g_list = model("Group")->where(["header_group_id" => $group_id])->select();
                foreach ($g_list as $item){
                    $item->save(["status" => 1]);
                    Cache::rm($item["id"].":groupBaseInfo");
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
            model("GroupPush")->save(["status"=>1], ["group_id"=>$group_id]);
            $res = $group->save(["status" => 2]);
            if ($res) {
//                model("Group")->save(["status" => 2, "close_time" => date("Y-m-d H:i")], ["header_group_id" => $group_id, "status" => ["neq", 2]]);
                $g_list = model("Group")->where(["header_group_id" => $group_id, "status" => ["neq", 2]])->select();
                foreach ($g_list as $item){
                    $item->save(["status" => 2, "close_time" => date("Y-m-d H:i")]);
                    Cache::rm($item["id"].":groupBaseInfo");
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
        if($res === false){
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
            'close_time' => input("close_time")
        ];
        $group_id = input('group_id');
        if ($group_id == 0 && $data["status"] == 1) {
            $data["open_time"] = date("Y-m-d");
        }
        model('HeaderGroup')->startTrans();
        model('HeaderGroupProduct')->startTrans();
        model('HeaderGroupProductSwiper')->startTrans();
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
            Cache::set($group_id.":HeaderGroup", $data);

            if (!$res1) {
                throw new Exception('创建团购失败');
            }
            //团购商品信息
            $product_list = input('product_list/a');
            foreach ($product_list as $key => $item) {
                $base_id = $item['base_id'];
                $pro = \app\common\model\Product::get($base_id);
                $product_data = [
                    'header_id' => HEADER_ID,
                    'product_name' => $item['product_name'],
                    'header_group_id' => $group_id,
                    'base_id' => $base_id,
                    'remain' => ($item['remain'] >= 0 && is_numeric($item['remain'])) ? $item['remain'] : -1,
                    'commission' => $item['commission'],
                    'purchase_price' => $item['purchase_price'],
                    'market_price' => $item['market_price'],
                    'group_price' => $item['group_price'],
                    'group_limit' => $item['group_limit'],
                    'self_limit' => $item['self_limit'],
                    'tag_name' => $item["tag_name"],
                    'ord' => $key,
                    'product_desc' => $pro['desc'],
                ];
                if ($item['id']) {
                    $res2 = HeaderGroupProduct::update($product_data, ['id' => $item['id']]);
                } else {
                    $res2 = model('HeaderGroupProduct')->data($product_data)->isUpdate(false)->save();
                }
                if (!$res2) {
                    throw new Exception('商品添加失败');
                }
                if ($item['id']) {
                    $product_id = $item['id'];
                    HeaderGroupProductSwiper::destroy(['header_group_product_id' => $product_id]);
                } else {
                    $product_id = model('HeaderGroupProduct')->getLastInsID();
                }
                //更新每个商品库存
                Cache::set($product_id.":stock", $product_data["remain"]);
                Cache::set($product_id.":self_limit", $product_data["self_limit"]);
                Cache::set($product_id.":group_limit", $product_data["group_limit"]);

                //二次添加商品处理加入团购商品列表
//                $group_list = db()->query("select * from (SELECT * FROM ts_group_product WHERE  header_group_id = $group_id ORDER BY ord desc) a GROUP BY a.group_id");
                $group_list = model("Group")->field("leader_id, id group_id")->where("header_group_id", $group_id)->select();
                foreach ($group_list as $val) {
                    $g_product = model("GroupProduct")->where([
                        'header_group_id' => $group_id,
                        'header_product_id' => $product_id,
                        "leader_id" => $val["leader_id"]
                    ])->find();
                    if ($g_product) {
                        $g_product->allowField(true)->save($product_data);
                    } else {
                        $data_temp = [
                            "leader_id" => $val['leader_id'],
                            'group_id' => $val['group_id'],
                            'product_name' => $product_data['product_name'],
                            'header_group_id' => $product_data['header_group_id'],
                            'commission' => $product_data['commission'],
                            'market_price' => $product_data['market_price'],
                            'group_price' => $product_data['group_price'],
                            'group_limit' => $product_data['group_limit'],
                            'self_limit' => $product_data['self_limit'],
                            'ord' => $product_data['ord'],
                            'product_desc' => $product_data['product_desc'],
                            'tag_name' => $product_data['tag_name'],
                            'header_product_id' => $product_id
                        ];
                        model("GroupProduct")->data($data_temp)->isUpdate(false)->save();
                    }
                    //删除团购商品列表
                    if(Cache::has($val["group_id"].":product_list")){
                        Cache::rm($val["group_id"].":product_list");
                    }
                }
                //二次编辑添加商品处理结束

                $item['img_list'] = model("ProductSwiper")->where("product_id", $base_id)->select();
                $product_swiper = $item['img_list'];
                $swiper = [];
                $swiper_data = [];
                foreach ($product_swiper as $value) {
                    $swiper[] = [
                        'header_group_product_id' => $product_id,
                        'swiper_type' => $value['type'],
                        'swiper_url' => $value['url']
                    ];
                    $swiper_data[] = [
                        'header_group_product_id' => $product_id,
                        'types' => $value['type'],
                        'urlImg' => $value['url']
                    ];
                }
                $res3 = model('HeaderGroupProductSwiper')->saveAll($swiper);
                //更新商品图片信息
                Cache::set($product_id.":swiper", $swiper_data);
                if (!$res3) {
                    throw new Exception('商品轮播保存失败');
                }
            }
            model('HeaderGroup')->commit();
            model('HeaderGroupProduct')->commit();
            model('HeaderGroupProductSwiper')->commit();
            exit_json();
        } catch (\Exception $e) {
            model('HeaderGroup')->rollback();
            model('HeaderGroupProduct')->rollback();
            model('HeaderGroupProductSwiper')->rollback();
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
        if($g && $g["status"] == 2){
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