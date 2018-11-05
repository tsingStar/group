<?php
/**
 * 普通团员
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-03
 * Time: 15:26
 */

namespace app\wapp\controller;


use app\common\model\ApplyLeaderRecord;
use app\common\model\Group;
use app\common\model\WeiXinPay;
use think\Controller;
use think\Log;

class User extends Controller
{
    private $user;

    protected function _initialize()
    {
        parent::_initialize();
        if (request()->action() != "getPickQrcode") {
            $user_id = input('user_id');
            if (!$user_id) {
                exit_json(-1, '用户不存在，请重新登陆');
            } else {
                $this->user = \app\common\model\User::get($user_id);
                if (!$this->user) {
                    exit_json(-1, '用户不存在，请重新登陆');
                }
            }
        }

    }

    /**
     * 申请成为团长
     */
    public function applyLeader()
    {
        //城主id
        $data = [
            'header_id' => input('header_id'),
            'user_id' => input('user_id'),
            'name' => input('name'),
            'telephone' => input('telephone'),
            'address' => input('address'),
            'residential' => input('residential'),
            'neighbours' => input('neighbours'),
            'have_group' => input('have_group'),
            'have_sale' => input('have_sale'),
            'work_time' => input('work_time'),
            'other' => input('other'),
        ];
        $header = model('Header')->where('id', $data['header_id'])->find();
        if (!$header) {
            exit_json(-1, '参数错误');
        }
        if ($this->user['role_status'] == 2) {
            exit_json(-1, '你已经是团长无需重复申请');
        }
        $apply_leader = ApplyLeaderRecord::get(['user_id' => $data['user_id'], 'status' => 0]);
        if ($apply_leader) {
            exit_json(-1, '已提交申请，等待城主申请');
        }

        $res = ApplyLeaderRecord::create($data);
        if ($res) {
            exit_json(1, '申请成功');
        } else {
            exit_json(-1, '申请失败');
        }
    }

    /**
     * 获取城主地址
     */
    public function getHeaderAddress()
    {
        $header_id = input('header_id');
        $header = \app\common\model\Header::get($header_id);
        $data = [
            'header_id' => $header['id'],
            'nick_name' => $header['nick_name'],
            'address' => $header['address']
        ];
        exit_json(1, '请求成功', $data);
    }

    /**
     * 获取团购列表
     */
    public function getGroupList()
    {
        $leader_id = db("LeaderRecord")->where("user_id", $this->user["id"])->value("leader_id");
        $page = input('page');
        $page_num = input('page_num');
        $list = model('Group')->alias('a')->join('User b', "a.leader_id=b.id")->where('a.leader_id', $leader_id)->field('a.id group_id, a.status, a.open_time, a.title, a.notice, b.user_name, b.avatar')->order('a.create_time desc')->limit($page * $page_num, $page_num)->select();
        $data = Group::formatGroupList($list);
        exit_json(1, '请求成功', $data);
    }

    /**
     * 获取团购详情
     */
    public function getGroupDetail()
    {
        $group_id = input('group_id');
        $group = model('Group')->where("id", $group_id)->field('id group_id, header_group_id, header_id, leader_id, dispatch_type, dispatch_info, title, notice, pay_type, status, scan_num')->find();
        if (!$group) {
            exit_json(-1, '当前团购不存在');
        }
        $lr = db("LeaderRecord")->where("user_id", $this->user["id"])->find();
        if ($lr) {
            db("LeaderRecord")->where(["user_id" => $this->user["id"]])->update(["leader_id" => $group["leader_id"]]);
        } else {
            db("LeaderRecord")->insert(["user_id" => $this->user["id"], "leader_id" => $group["leader_id"]]);
        }
        $uv = db("Uv")->where("user_id", $this->user["id"])->where("group_id", $group_id)->find();
        if (!$uv) {
            db("Uv")->insert(["user_id" => $this->user["id"], "group_id" => $group_id]);
            $group->save(["scan_num" => $group["scan_num"] + 1]);
        }
        $product_list = model('GroupProduct')->alias("a")->join("HeaderGroupProduct b", "a.header_product_id=b.id")->where('a.group_id', $group_id)->field('a.id, a.leader_id, a.header_group_id, a.group_id, a.header_product_id, a.product_name, a.product_desc, a.commission, a.market_price, a.group_price, a.tag_name, b.remain')->order('a.ord')->select();
        foreach ($product_list as $value) {
//            $value['product_img'] = model('HeaderGroupProductSwiper')->where('header_group_product_id', $value['header_product_id'])->field('swiper_type types, swiper_url urlImg')->cache(true)->order("create_time")->select();
            $value['product_img'] = model('HeaderGroupProductSwiper')->getSwiper($value["header_product_id"]);
        }

        //获取显示状态
        $config = db("HeaderConfig")->where("header_id", $group["header_id"])->find();
        if (!$config) {
            db("HeaderConfig")->insert(["header_id" => $group["header_id"]]);
            $show = 1;
        } else {
            $show = $config["sale_detail_show"];
        }
        $order_money = model("Order")->where("group_id", $group_id)->sum("order_money");
        $order_num = model("Order")->where("group_id", $group_id)->count();
        $group['sale_detail'] = [
            "is_show" => $show,
            "detail" => [
                "scan_number" => $group["scan_num"],
                "order_number" => $order_num,
                "order_money" => $order_money
            ]
        ];
        $group['product_list'] = $product_list;
        $leader = model("User")->where("id", $group["leader_id"])->find();
        $group["user_name"] = $leader["user_name"];
        $group["avatar"] = $leader["avatar"];
        exit_json(1, '请求成功', $group);
    }

    /**
     * 获取团购自提点
     */
    public function getDispatchSites()
    {
        $dispatch_info = input('dispatch_info');
        $address_list = db('LeaderPickAddress')->where('id', "in", $dispatch_info)->field('id, name, address, address_det')->select();
        exit_json(1, '请求成功', $address_list);
    }

    /**
     * 获取团购记录
     */
    public function getGroupRecord()
    {
        $group_id = input('group_id');
        $group = model("Group")->where("id", $group_id)->find();
        if (!$group) {
            exit_json(-1, "团购不存在");
        }
        $page = input('page');
        $page_num = input('page_num');
        //是否显示这个军团
        $sale_record = db("HeaderConfig")->where("header_id", $group["header_id"])->value("sale_record_show");
        if ($sale_record == 1) {
            $record_list = model('Order')->alias('a')->join('User b', 'a.user_id=b.id')->where('a.header_group_id', $group["header_group_id"])->field('a.id, a.create_time, b.avatar, b.user_name, a.order_no, a.is_replace, a.num')->limit($page * $page_num, $page_num)->order("a.create_time desc")->select();
            $order_sum = model("Order")->where("header_group_id", $group["header_group_id"])->count();
            foreach ($record_list as $l) {
                $l["product_num"] = model('OrderDet')->where('order_no', $l['order_no'])->value('sum(num) num');
//                $l["product_num"] = model('OrderDet')->where('order_no', $l['order_no'])->value('sum(num-back_num) num');
                $l["product_list"] = model("OrderDet")->where("order_no", $l["order_no"])->field("product_name, num sum_num")->select();
            }
        } else {
            $record_list = model('Order')->alias('a')->join('User b', 'a.user_id=b.id')->where('a.group_id', $group_id)->field('a.id, a.create_time, b.avatar, b.user_name, a.order_no, a.is_replace, a.num')->limit($page * $page_num, $page_num)->order("a.create_time desc")->select();
            $order_sum = model("Order")->where("group_id", $group_id)->count();
            foreach ($record_list as $l) {
//                $l["product_num"] = model('OrderDet')->where('order_no', $l['order_no'])->value('sum(num-back_num) num');
                $l["product_num"] = model('OrderDet')->where('order_no', $l['order_no'])->value('sum(num) num');
                $l["product_list"] = model("OrderDet")->where("order_no", $l["order_no"])->field("product_name, num sum_num")->select();
            }

        }
        $data = [
            "order_sum" => $order_sum,
            "record_list" => $record_list
        ];
        exit_json(1, '请求成功', $data);
    }

    /**
     * 检测商品库存
     */
    public function checkProductRemain()
    {
        $product_id = input('product_id');
        $group_id = input('group_id');
        $group = model("Group")->where("id", $group_id)->find();
        if ($group["status"] != 1) {
            exit_json(-1, "团购未开启或已结束");
        }
        $num = input('num');
        $lock1 = fopen(__PUBLIC__ . "/lock1.txt", "w");
        if(flock($lock1, LOCK_EX)){

            $product = model('GroupProduct')->where('id', $product_id)->find();
            $header_product = model('HeaderGroupProduct')->where('id', $product['header_product_id'])->find();
            $group_num = model('OrderDet')->where('group_id', $group_id)->where('product_id', $product_id)->sum('num-back_num');
            $self_num = model("OrderDet")->where('group_id', $group_id)->where('product_id', $product_id)->where("user_id", $this->user["id"])->sum('num-back_num');

            flock($lock1, LOCK_UN);
            fclose($lock1);
            //军团库存数量
            if ($header_product['remain'] != -1 && $header_product['remain'] < $num) {
                exit_json(-1, '抱歉，商品已被抢光');
            }

            //团员限购
            if ($product['self_limit'] > 0 && $product['self_limit'] < $self_num + $num) {
                exit_json(-1, '商品个人限购' . $product['self_limit'] . '件');
            }

            //团限购
//            if ($product['group_limit'] > 0 && $group_num + $num > $product['group_limit']) {
//                exit_json(-1, '该商品团限购' . $product['group_limit'] . '件，还剩' . ($num - 1));
//            }
            if ($header_product['group_limit'] > 0 && $num > $product['group_limit']) {
                exit_json(-1, '该商品团限购' . $header_product['group_limit'] . '件，还剩' . ($num - 1));
            }
        }

        exit_json(1, '库存充足');
    }

    /**
     * 校验库存合法
     */
    public function checkOrder()
    {

        $product_list = input("product_list/a");


        $lock = fopen(__PUBLIC__ . "/lock.txt", "w");
        if (flock($lock, LOCK_EX)) {
            //TODO 待处理，上次锁定库存未释放,已处理，不确定是否有未处理bug
            $remain_order = model("OrderRemainPre")->where("user_id", $this->user['id'])->where("status", 0)->select();
            $weixin = new WeiXinPay();
            foreach ($remain_order as $value) {
                $r = $weixin->orderQuery($value["order_no"]);
//            $r = false;
                if (!$r) {
                    $value->save(["status" => 2]);
                    $p_list = json_decode($value["product_info"], true);
                    foreach ($p_list as $item) {
                        model("HeaderGroupProduct")->where("id", $item["header_product_id"])->setInc("remain", $item["num"]);
                    }
                }
            }
            $bol = false;
            $pro_name = "";
            $pro_arr = [];
            foreach ($product_list as $item) {
                $pro = model("HeaderGroupProduct")->where("id", $item["header_product_id"])->find();
                if ($pro["remain"] != -1) {
                    if ($pro["remain"] < $item["num"]) {
                        $bol = true;
                        $pro_name .= $item["product_name"] . "、";
                    } else {
                        if($pro["group_limit"]>0){
                            $pro_arr[] = [
                                "header_product_id" => $item["header_product_id"],
                                "num" => $item["num"],
                                "product_id"=>$item["id"],
                                "is_group"=>true
                            ];
                        }else{
                            $pro_arr[] = [
                                "header_product_id" => $item["header_product_id"],
                                "num" => $item["num"],
                                "product_id"=>$item["id"],
                                "is_group"=>false
                            ];
                        }
                    }
                }

            }
            if ($bol) {
                flock($lock, LOCK_UN);
                fclose($lock);
                exit_json(-1, $pro_name . "抱歉，商品已被抢光");
            }
            $order_no = getOrderNo();
            foreach ($pro_arr as $val) {
                model("HeaderGroupProduct")->where("id", $val["header_product_id"])->setDec("remain", $val["num"]);
                if($val["is_group"]){
                    model("GroupProduct")->where("id", $val["product_id"])->setDec("group_limit", $val["num"]);
                }
            }

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
                exit_json(1, '请求成功', ["order_no" => $order_no]);
            } else {
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
//        $order_no = getOrderNo();
        $order_no = input("order_no");
        if (!$order_no) {
//            $order_no = getOrderNo();
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
//        $pay_status = input('pay_status');
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
//        $bol = true;
//        $product_name = "";
        foreach ($product_list as $item) {
//            $header_product = model('HeaderGroupProduct')->where('id', $item['header_product_id'])->find();
//            if ($header_product["remain"] != -1 && $header_product["remain"] < $item["num"]) {
//                $bol = false;
//                $product_name .= $header_product["product_name"] . "、";
//
//            }
            $order_money += $item['group_price'] * $item['num'];
        }
//        if (!$bol) {
//            exit_json(-1, $product_name . "商品库存不足");
//        }
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
//        $order_pre = true;
        if ($res && $order_pre) {
            model('OrderPre')->commit();
            exit_json(1, '请求成功', $order_pre);
        } else {
            model('OrderPre')->rollback();
            exit_json(-1, '系统错误');
        }
    }

    /**
     * 获取订单列表
     */
    public function getOrderList()
    {
        $list = model('Order')->alias('a')->join('Group b', 'a.group_id=b.id')->where('user_id', $this->user['id'])->field("b.title, a.create_time, a.pick_status, a.order_no, a.order_money-a.refund_money final_cost")->select();
        exit_json(1, '请求成功', $list);
    }

    /**
     * 获取订单详情
     */
    public function getOrderDetail()
    {
        $order_no = input('order_no');
        $order = model("Order")->where("order_no", $order_no)->where('user_id', $this->user['id'])->find();
        $product_list = model('OrderDet')->where('order_no', $order_no)->select();
        foreach ($product_list as $item) {
//            $item['product_swiper'] = model('HeaderGroupProductSwiper')->where('header_group_product_id', $item['header_product_id'])->field('swiper_type types, swiper_url urlImg')->cache(true)->order("create_time")->select();
            $item['product_swiper'] = model('HeaderGroupProductSwiper')->getSwiper($item["header_product_id"]);
        }
        $order['product_list'] = $product_list;
        exit_json(1, '请求成功', $order);
    }

    /**
     * 获取提货码
     */
    public function getPickQrcode()
    {
        $scene = input("scene");
        $page = input("page");
        $access_token = $this->getAccessToken();
        $ch = curl_init();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $post_data = [
            "scene" => $scene,
            "page" => $page
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        // 执行后不直接打印出来
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求方式为post
        curl_setopt($ch, CURLOPT_POST, true);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        // 请求头，可以传数组
//        curl_setopt($ch, CURLOPT_HEADER, $header);
        // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // 不从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $output = curl_exec($ch);
        curl_close($ch);
        if (!is_null($output)) {
            cache("access_token", null);
            $access_token = $this->getAccessToken();
            $ch = curl_init();
            $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
            $post_data = [
                "scene" => $scene,
                "page" => $page
            ];
            curl_setopt($ch, CURLOPT_URL, $url);
            // 执行后不直接打印出来
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // 设置请求方式为post
            curl_setopt($ch, CURLOPT_POST, true);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            // 请求头，可以传数组
            //        curl_setopt($ch, CURLOPT_HEADER, $header);
            // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // 不从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $output = curl_exec($ch);
            curl_close($ch);
        }
        $path = __UPLOAD__ . '/erweima/';
        is_dir($path) or mkdir($path, "0777");
        $file = md5(get_millisecond()) . ".png";
        $f = fopen($path . $file, "w");
        fwrite($f, $output);
        fclose($f);
        $output = __URL__ . "/upload/erweima/" . $file;
        exit_json(1, "请求成功", ["data" => $output]);

    }

    /**
     * 获取access_token
     * @return bool|mixed
     */
    private function getAccessToken()
    {
        if (cache("access_token")) {
            return cache("access_token");
        } else {
            $res = json_decode(file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . config("weixin.app_id") . "&secret=" . config("weixin.app_secret")), true);
            if (!isset($res["access_token"])) {
                return false;
            } else {
                cache("access_token", $res["access_token"], $res["expires_in"]);
                return $res["access_token"];
            }
        }
    }

    /**
     * 获取上次购买地址
     */
    public function getLastAddress()
    {
        $order = model("Order")->where("user_id", $this->user["id"])->order("create_time desc")->find();
        $address = [
            "user_name" => is_null($order["user_name"]) ? "" : $order["user_name"],
            "user_telephone" => is_null($order["user_telephone"]) ? "" : $order["user_telephone"]
        ];
        exit_json(1, "请求成功", $address);

    }

    /**
     * 异步保存form_id
     */
    public function setFormId()
    {
        $form_id = input("form_id");
        $res = db("form_id")->insert([
            "user_id" => $this->user['id'],
            "form_id" => $form_id
        ]);
        if ($res) {
            exit_json();
        } else {
            exit_json(-1);
        }
    }

    /**
     * 分享得红包
     */
    public function getCoupon()
    {
        $open_id = $this->user["open_id"];
        $order_no = input("order_no");
        $order = model("Order")->where("order_no", $order_no)->where("user_id", $this->user["id"])->find();
        if (!$order) {
            exit_json(-1, "分享订单异常");
        }
        $group = model("Group")->where("id", $order["group_id"])->find();
        if ($group["status"] != 1) {
            exit_json(-1, "您与红包擦肩而过，下次努力");
        }
        if ($order["order_money"] - $order["refund_money"] < 9) {
            exit_json(-1, "您与红包擦肩而过，下次努力");
        } else {
            $cr = db("CouponRecord")->where("order_no", $order_no)->find();
            if ($cr) {
                exit_json(-1, "抱歉，每个订单只有一次拆红包机会");
            }
//
//            //计算红包金额
            $amount = 0.3;
            $coupon_no = getOrderNo();
            $id = db("CouponRecord")->insertGetId([
                "coupon_no" => $coupon_no,
                "order_no" => $order_no,
                "header_group_id" => $order["header_group_id"],
                "create_time" => date("Y-m-d H:i:s"),
                "coupon" => $amount
            ]);
            $rand = rand(0, 300);
            if ($rand < 100) {
                if ($id > 0) {
                    $weixin = new WeiXinPay();
                    $order_info = [
                        "open_id" => $open_id,
                        "amount" => $amount,
                        "check_name" => "NO_CHECK",
                        "desc" => "分享得红包",
                        "order_no" => $coupon_no
                    ];
                    $res = $weixin->withdraw($order_info);
//                $res = true;
                    if ($res) {
                        db("CouponRecord")->where("id", $id)->update(["status" => 1]);
                    }
                    exit_json(1, "恭喜您获得" . $amount . "元红包，红包会以微信零钱方式发到您手中");
                } else {
                    exit_json(-1, "您与红包擦肩而过，下次努力");
                }
            } else {
                exit_json(-1, "您与红包擦肩而过，下次努力");
            }

        }
    }

}