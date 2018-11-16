<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-21
 * Time: 16:23
 */

namespace app\wapp\controller;


use app\common\model\WeiXinPay;
use think\Cache;
use think\Controller;
use think\Log;

class Temp extends Controller
{

    private $user;
    protected function _initialize()
    {
        parent::_initialize();
        $this->user = [
            "id"=>522
        ];
    }

    public function test()
    {
        $redis = new \Redis2();
        print_r($redis->set("num", 1));
        exit();

    }

    public function test1()
    {
        $l = fopen(__PUBLIC__."/lock.txt", "w");
        echo time();
        if(flock($l, LOCK_EX)){
            echo time();
        }
        exit;
    }







    /**
     * 检测商品库存
     */
    public function checkProductRemain()
    {
//        $product_id = input('product_id');
        $product_id = 6089;
//        $group_id = input('group_id');
        $group_id = 311;
        $group = model("Group")->getGroupBaseInfo($group_id);
        if ($group["status"] != 1) {
            exit_json(-1, "团购未开启或已结束");
        }
        $num = input('num');
        if (Cache::has($product_id . ":groupProduct")) {
            $product = Cache::get($product_id . ":groupProduct");
        } else {
            $product = model('GroupProduct')->where('id', $product_id)->find();
            Cache::set($product_id . ":groupProduct", $product);
        }
        $header_product_id = $product['header_product_id'];
        $header_product_id = "454";
        $num = 1;

        //校验商品库存数量是否正常
        $redis = new \Redis2();
        if ($redis->get($header_product_id . ":remain") == 1) {
            //如若为库存商品
            if ($num > $redis->llen($header_product_id . ":stock")) {
                logs("我选的时候库存不足了");

                exit_json(-1, "商品剩余库存不足");
            }
        }


        $self_limit = model("HeaderGroupProduct")->getSelfLimit($header_product_id);
        $group_limit = model("HeaderGroupProduct")->getGroupLimit($header_product_id);


        //TODO  商品购买数量待优化
        $group_num = model('OrderDet')->where('group_id', $group_id)->where('product_id', $product_id)->sum('num-back_num');
        $self_num = model("OrderDet")->where('group_id', $group_id)->where('product_id', $product_id)->where("user_id", $this->user["id"])->sum('num-back_num');

        //团员限购
        if ($self_limit > 0 && $self_limit < $self_num + $num) {
            exit_json(-1, '商品个人限购' . $self_limit . '件');
        }

        //团限购
        if ($group_limit > 0 && $group_num + $num > $group_limit) {
            exit_json(-1, '该商品团限购' . $group_limit . '件，还剩' . ($num - 1));
        }
//        }
        logs("我选的时候库存还是足的");
        exit_json(1, '库存充足');
    }

    /**
     * 校验库存合法
     */
    public function checkOrder()
    {

//        $product_list = input("product_list/a");

        $product_list = json_decode('[{"id":6089,"leader_id":2116,"header_group_id":41,"group_id":311,"header_product_id":454,"product_name":"豆上佳","product_desc":"可以吸的橙子别样鲜，富含多重维生素等营养成分，鲜美多汁，皮薄无核，含糖量高达15°，甜蜜鲜橙，🎊细腻化渣，甘甜爽口，果香浓郁，爽爆味蕾。","commission":"0.00","market_price":"10.00","group_price":"1.00","tag_name":"热销秒杀","product_img":[{"types":1,"urlImg":"http://phyf590if.bkt.clouddn.com/7cb03254cf50e0850e6e0c659a113159.jpg"}],"remain":10,"num":1},{"id":6090,"leader_id":2116,"header_group_id":41,"group_id":311,"header_product_id":455,"product_name":"豆上佳","product_desc":"可以吸的橙子别样鲜，富含多重维生素等营养成分，鲜美多汁，皮薄无核，含糖量高达15°，甜蜜鲜橙，🎊细腻化渣，甘甜爽口，果香浓郁，爽爆味蕾。","commission":"0.00","market_price":"10.00","group_price":"1.00","tag_name":"热销秒杀","product_img":[{"types":1,"urlImg":"http://phyf590if.bkt.clouddn.com/7cb03254cf50e0850e6e0c659a113159.jpg"}],"remain":10,"num":1}]', true);
        $lock = fopen(__PUBLIC__ . "/lock.txt", "w");
        if (flock($lock, LOCK_EX)) {
            $redis = new \Redis2();
            //判断上次请求是否有锁定库存
            $remain_order = model("OrderRemainPre")->where("user_id", $this->user['id'])->where("status", 0)->select();
            $weixin = new WeiXinPay();
            foreach ($remain_order as $value) {
                $r = $weixin->orderQuery($value["order_no"]);
                if (!$r) {
                    $value->save(["status" => 2]);
                    $p_list = json_decode($value["product_info"], true);
                    foreach ($p_list as $item) {
                        //添加库存缓存
                        for ($i = 0; $i < $item["num"]; $i++) {
                            $redis->lpush($item["header_product_id"] . ":stock", 1);
                        }
                    }
                }
            }
            $bol = false;
            $pro_name = "";
            $pro_arr = [];
            $temp = [];
            foreach ($product_list as $item) {
                $group_limit = model("HeaderGroupProduct")->getGroupLimit($item["header_product_id"]);
                if ($redis->get($item["header_product_id"] . ":remain") == 1) {
                    if ($redis->llen($item["header_product_id"].":stock") < $item["num"]) {
                        $bol = true;
                        $pro_name .= $item["product_name"] . "、";
                    } else {
                        if ($group_limit > 0) {
                            $pro_arr[] = [
                                "header_product_id" => $item["header_product_id"],
                                "num" => $item["num"],
                                "product_id" => $item["id"],
                                "is_group" => true
                            ];
                        } else {
                            $pro_arr[] = [
                                "header_product_id" => $item["header_product_id"],
                                "num" => $item["num"],
                                "product_id" => $item["id"],
                                "is_group" => false
                            ];
                        }
                        for ($j=0;$j<$item["num"];$j++){
                            $redis->lpop($item["header_product_id"].":stock");
                        }
                        $temp[] = [
                            "header_product_id"=>$item["header_product_id"],
                            "num"=>$item["num"]
                        ];
                    }
                }

            }
            if ($bol) {
                //回归库存
                foreach ($temp as $value){
                    for($i=0;$i<$value["num"];$i++){
                        $redis->lpush($value["header_product_id"].":stock", 1);
                    }
                }
                flock($lock, LOCK_UN);
                fclose($lock);
                logs1("好难过，我没抢着");
                exit_json(-1, $pro_name . "抱歉，商品已被抢光");
            }
            $order_no = getOrderNo();
            $res = model("OrderRemainPre")->insert([
                "user_id" => $this->user["id"],
                "order_no" => $order_no,
                "product_info" => json_encode($pro_arr),
                "create_time" => time(),
                "status" => 0
            ]);
            flock($lock, LOCK_UN);
            fclose($lock);
            if ($res) {
                logs1("我抢着了");
                exit_json(1, '请求成功', ["order_no" => $order_no]);
            } else {
                Log::error("库存订单处理失败");
                exit_json(-1, "订单生成失败");
            }
        } else {
            flock($lock, LOCK_UN);
            fclose($lock);
            exit_json(-1, "系统异常");
        }
    }
    /**
     * 校验库存合法
     */
    public function checkOrder1()
    {

//        $product_list = input("product_list/a");

        $product_list = json_decode('[{"id":6089,"leader_id":2116,"header_group_id":41,"group_id":311,"header_product_id":454,"product_name":"豆上佳","product_desc":"可以吸的橙子别样鲜，富含多重维生素等营养成分，鲜美多汁，皮薄无核，含糖量高达15°，甜蜜鲜橙，🎊细腻化渣，甘甜爽口，果香浓郁，爽爆味蕾。","commission":"0.00","market_price":"10.00","group_price":"1.00","tag_name":"热销秒杀","product_img":[{"types":1,"urlImg":"http://phyf590if.bkt.clouddn.com/7cb03254cf50e0850e6e0c659a113159.jpg"}],"remain":10,"num":1}]', true);
        $lock = fopen(__PUBLIC__ . "/lock.txt", "w");
        if (flock($lock, LOCK_EX)) {
            $redis = new \Redis2();
            //判断上次请求是否有锁定库存
//            $remain_order = model("OrderRemainPre")->where("user_id", $this->user['id'])->where("status", 0)->select();
//            $weixin = new WeiXinPay();
//            foreach ($remain_order as $value) {
//                $r = $weixin->orderQuery($value["order_no"]);
//                if (!$r) {
//                    $value->save(["status" => 2]);
//                    $p_list = json_decode($value["product_info"], true);
//                    foreach ($p_list as $item) {
//                        //添加库存缓存
//                        for ($i = 0; $i < $item["num"]; $i++) {
//                            $redis->lpush($item["header_product_id"] . ":stock", 1);
//                        }
//                    }
//                }
//            }
            $bol = false;
            $pro_name = "";
            $pro_arr = [];
            $temp = [];
            foreach ($product_list as $item) {
                $group_limit = model("HeaderGroupProduct")->getGroupLimit($item["header_product_id"]);
                if ($redis->get($item["header_product_id"] . ":remain") == 1) {
                    if ($redis->llen($item["header_product_id"].":stock") < $item["num"]) {
                        $bol = true;
                        $pro_name .= $item["product_name"] . "、";
                    } else {
                        if ($group_limit > 0) {
                            $pro_arr[] = [
                                "header_product_id" => $item["header_product_id"],
                                "num" => $item["num"],
                                "product_id" => $item["id"],
                                "is_group" => true
                            ];
                        } else {
                            $pro_arr[] = [
                                "header_product_id" => $item["header_product_id"],
                                "num" => $item["num"],
                                "product_id" => $item["id"],
                                "is_group" => false
                            ];
                        }
                        for ($j=0;$j<$item["num"];$j++){
                            $redis->lpop($item["header_product_id"].":stock");
                        }
                        $temp[] = [
                            "header_product_id"=>$item["header_product_id"],
                            "num"=>$item["num"]
                        ];
                    }
                }

            }
            if ($bol) {
                //回归库存
                foreach ($temp as $value){
                    for($i=0;$i<$value["num"];$i++){
                        $redis->lpush($value["header_product_id"].":stock", 1);
                    }
                }
                flock($lock, LOCK_UN);
                fclose($lock);
                logs1("好难过，我没抢着");
                exit_json(-1, $pro_name . "抱歉，商品已被抢光");
            }
            $order_no = getOrderNo();
            $res = model("OrderRemainPre")->insert([
                "user_id" => $this->user["id"],
                "order_no" => $order_no,
                "product_info" => json_encode($pro_arr),
                "create_time" => time(),
                "status" => 0
            ]);
            flock($lock, LOCK_UN);
            fclose($lock);
            if ($res) {
                logs1("我抢着了");
                exit_json(1, '请求成功', ["order_no" => $order_no]);
            } else {
                Log::error("库存订单处理失败");
                exit_json(-1, "订单生成失败");
            }
        } else {
            flock($lock, LOCK_UN);
            fclose($lock);
            exit_json(-1, "系统异常");
        }
    }

    /**
     * 获取立即下单
     */
    public function makeOrder()
    {
        $order_no = input("order_no");
        if (!$order_no) {
            exit_json(-1, "订单参数错误");
        }
        $remain_pre = model("OrderRemainPre")->where("order_no", $order_no)->find();
        if ($remain_pre["status"] == 2) {
            exit_json(-1, "订单已超时， 请重新下单");
        }
        $header_id = input('header_id');
        $leader_id = input('leader_id');
        $header_group_id = input('header_group_id');
        $group_id = input('group_id');
        $user_id = $this->user['id'];
        $pick_type = input('pick_type');
        $pick_address = input('pick_address');
        $pay_type = input('pay_type');
        $user_name = input('user_name');
        $user_telephone = input('user_telephone');
        $remarks = input('remarks');
        $product_list = input('product_list/a');
        $product_list = array_filter($product_list, function ($item) {
            if ($item["num"] == 0) {
                return false;
            } else {
                return true;
            }
        });
        $order_money = 0;
        foreach ($product_list as $item) {
            $order_money += $item['group_price'] * $item['num'];
        }
        $order_money = round($order_money, 2);
        $group = model("Group")->where('id', $group_id)->find();
        if ($group['status'] != 1) {
            exit_json(-1, '当前团购已结束');
        }
        $data = [
            'order_no' => $order_no,
            'header_id' => $header_id,
            'leader_id' => $leader_id,
            'header_group_id' => $header_group_id,
            'group_id' => $group_id,
            'user_id' => $user_id,
            'pick_type' => $pick_type,
            'pick_address' => $pick_address,
            'pay_type' => $pay_type,
//            'pay_status' => $pay_status,
            'user_name' => $user_name,
            'user_telephone' => $user_telephone,
            'remarks' => $remarks,
            'order_money' => $order_money,
            'product_list' => $product_list
        ];
        $weixin = new WeiXinPay();
        $order_info = [
            "subject" => "易贝通团购-订单支付",
            "body" => "订单支付",
            "out_trade_no" => $data['order_no'],
            "total_amount" => $data['order_money'],
            "trade_type" => "JSAPI",
            "open_id" => $this->user['open_id'],
            "time_start" => date("YmdHis", $remain_pre->getData("create_time")),
            "time_expire" => date("YmdHis", $remain_pre->getData("create_time") + 300)
        ];
        $notify_url = config('notify_url');
        model('OrderPre')->startTrans();
        $res = model('OrderPre')->save(['order_no' => $order_no, "order_det" => json_encode($data)]);
        $order_pre = $weixin->createPrePayOrder($order_info, $notify_url);
        $order_pre["order_no"] = $order_no;
        if ($res && $order_pre) {
            model('OrderPre')->commit();
            exit_json(1, '请求成功', $order_pre);
        } else {
            model('OrderPre')->rollback();
            exit_json(-1, '系统错误');
        }
    }
    


}