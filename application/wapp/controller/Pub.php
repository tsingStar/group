<?php
/**
 * 微信小程序公共模块
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-27
 * Time: 16:32
 */

namespace app\wapp\controller;


use app\common\model\ApplyHeaderRecord;
use app\common\model\WeiXinPay;
use think\Cache;
use think\Controller;
use think\Log;

class Pub extends Controller
{


    /**
     * 获取销售详情
     */
    public function getSaleRecord()
    {

        $list = model("HeaderGroup")->where("header_id", 1)->where("status", 2)->select();
        $data = [];
        $extraTitle = [];
        $header = [];
        foreach ($list as $item){
            //遍历每期结束军团
            $group_list = model("Order")->alias("a")->join("User b", "a.leader_id=b.id")->where("a.header_group_id", $item["id"])->group("a.leader_id")->order("sum(a.order_money-a.refund_money) desc")->field("b.residential, b.name, sum(a.order_money-a.refund_money) sale_money")->select();
            $temp = [];
            foreach ($group_list as $v){
                $temp[] = [$v["residential"], $v["name"], $v["sale_money"]];
            }
            $data[] = $temp;
            $extraTitle[] = substr($item['open_time'], 0, 10);
            $header[] = ["小区名称", "团长名称", "销售额"];
        }
        $file_name = "截止".date("Y-m-d")."军团销售记录";
        echo excel($header, $data, $file_name, count($data), $extraTitle);
        exit();

    }

    /**
     * 下载军团销售记录
     */
    public function getGroupData()
    {
        $group_id = input("group_id");
        //军团信息
        $data = model("OrderDet")->where("header_group_id", $group_id)->field("product_name, sum(num-back_num) product_num")->group("header_product_id")->select();
        $temp1 = [];
        foreach ($data as $val) {
            $temp1[] = [
                $val["product_name"],
                $val["product_num"]
            ];
        }
        $leaders = model("OrderDet")->where("header_group_id", $group_id)->field("leader_id")->group("leader_id")->select();
        //团长订单
        $data1 = [];
        foreach ($leaders as $value) {
            $temp = model("OrderDet")->alias("a")->join("User b", "a.leader_id=b.id", "left")->join("Group c", "c.id=a.group_id", "left")->where("a.leader_id", $value['leader_id'])->where("a.header_group_id", $group_id)->field("a.product_name, sum(a.num-a.back_num) product_num, a.leader_id, b.user_name, b.name, b.telephone, c.pick_address, c.pick_type")->group("product_id")->select();
            foreach ($temp as $item) {
                if ($item["pick_type"] == 2) {
                    $address = db("HeaderPickAddress")->where("id", $item["pick_address"])->find();
                    $item["pick_address"] = $address["address"] . " " . $address["address_det"];
                }
                $data1[] = [
                    $item["product_name"],
                    $item["product_num"],
                    $item["user_name"],
                    $item["name"],
                    $item["telephone"],
                    $item["pick_address"]
                ];
            }
        }
        $header = [
            ["商品名称", "商品数量"],
            ["商品名称", "商品数量", "团长昵称", "团长姓名", "联系方式", "取货地址"]
        ];
        $body = [
            $temp1,
            $data1
        ];
        $extraTitle = [
            "军团销售汇总",
            "团长销售汇总"
        ];
        $group = model("HeaderGroup")->where("id", $group_id)->find();
        echo excel($header, $body, $group["close_time"], count($header), $extraTitle);
        exit;
    }

    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 微信小程序授权
     */
    public function getOpenId()
    {
        $app_id = config('wapp.app_id');
        $app_secret = config('wapp.app_secret');
        $js_code = input('js_code');
        if (!$app_id || !$app_secret || !$js_code) {
            exit_json(-1, '参数错误');
        }
        $res = file_get_contents("https://api.weixin.qq.com/sns/jscode2session?appid=$app_id&secret=$app_secret&js_code=$js_code&grant_type=authorization_code");
        $res = json_decode($res, true);
        if (!isset($res['openid'])) {
            exit_json(-1, $res['errmsg']);
        }
        $session_key = $res['session_key'];
        $open_id = $res['openid'];
        cache($open_id, $session_key);
//        $os = db('os')->where('open_id', $open_id)->find();
//        if ($os) {
//            db('os')->where('open_id', $open_id)->update(['session_key' => $session_key]);
//        } else {
//            db('os')->insert(['open_id' => $open_id, 'session_key' => $session_key]);
//        }
        exit_json(1, '请求成功', [
            'open_id' => $open_id,
        ]);
    }

    /**
     * 小程序登陆
     */
    public function loginByWapp()
    {
        $open_id = input('open_id');
        $user_name = input('user_name');
        $avatar = input('avatar');
        $gender = input('gender');
        $city = input('city');
        $province = input('province');
        $country = input('country');
        $data = [
            'open_id' => $open_id,
            'user_name' => $user_name,
            'avatar' => $avatar,
            'gender' => $gender,
            'city' => $city,
            'province' => $province,
            'country' => $country
        ];
        if (!$open_id) {
            exit_json(-1, '登陆失败');
        }
        if(Cache::has($open_id.":user")){
            $res = Cache::get($open_id.":user");
            exit_json(1, "登录成功", $res);
        }else{
            $user = model('User')->field("id, open_id, user_name, avatar, name, role_status, header_id")->where('open_id', $open_id)->find();
            if (!$user) {
                $res = model('User')->insertGetId($data);
                if($res){
                    exit_json(1, "登录成功", array_merge($data, ["id"=>$res, "role_status"=>0, "header_id"=>0]));
                }else{
                    exit_json(-1, "登录失败");
                }
            }else{
                Cache::set($open_id.":user", $user->getData());
                exit_json(1, "登录成功",$user->getData());
            }
        }
    }

    /**
     * 申请城主
     */
    public function applyHeader()
    {
        $data = [
            'user_id' => input('user_id'),
            'name' => input('name'),
            'telephone' => input('telephone'),
            'address' => input('address'),
            'profession' => input('profession'),
            'is_leader' => input('is_leader'),
            'have_group' => input('have_group'),
            'other' => input('other'),
        ];
        $header = ApplyHeaderRecord::get(['user_id' => $data['user_id'], 'status' => 0]);
        if ($header) {
            exit_json(-1, '你有新的申请正在等待处理');
        } else {
            $res = ApplyHeaderRecord::create($data);
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, '申请失败');
            }
        }
    }

    /**
     * 城主登陆
     */
    public function headerLogin()
    {
        $name = input('name');
        $password = input('password');
        $header = model('Header')->where(['name' => $name, 'password' => md5($password)])->field('id header_id, name, nick_name, head_image, amount_able+amount_lock amount, amount_able')->find();//where(['name' => $name, 'password' => md5($password)])
        if ($header) {
            exit_json(1, '登陆成功', $header);
        } else {
            exit_json(-1, '账号或密码错误');
        }
    }


    /**
     * 上传图片
     */
    public function uploadImg()
    {
        $file = request()->file('file');
        $order_no = input("order_no");
        if ($file) {
            $ext_array = ['jpg', 'jpeg', 'png', 'mp4', '3gp', 'avi'];
            $ext = $file->checkExt($ext_array);
            if (!in_array($ext, $ext_array)) {
                exit_json(-1, '请上传有效格式的文件');
            }
            if ($order_no) {
                $saveName = $order_no;
                $path = __URL__ . "/upload/" . $saveName . $ext;
                if (file_exists($path)) {
                    exit_json(1, '操作成功', ["img_url" => $path]);
                }
            } else {
                $saveName = true;
            }
            $info = $file->move(__UPLOAD__, $saveName);
            if ($info) {
                $saveName = $info->getSaveName();
                $path = __URL__ . "/upload/" . $saveName;
                exit_json(1, '操作成功', ["img_url" => $path]);
            } else {
                // 上传失败获取错误信息
                exit_json(-1, $file->getError());
            }
        } else {
            exit_json(-1, '文件不存在');
        }
    }

    /**
     * 删除文件
     */
    public function delImg()
    {
        $path = input('img_url');
        $file_info = parse_url($path);
        $path = $file_info['path'];
        $real_path = __PUBLIC__ . $path;
        if (file_exists($real_path)) {
            @unlink($real_path);
            exit_json(1, '操作成功', ['msg' => '已删除']);
        } else {
            exit_json(-1, '文件不存在');
        }

    }

    /**
     * 获取当前请求token
     */
    public function getToken()
    {
        $user_id = input("user_id");
        $role = input("type");
        $token = md5(time() . rand_string());
        cache($user_id . "token" . $role, $token);
        exit_json(1, "请求成功", $token);
    }

    /**
     * 获取银行列表
     */
    public function getBankList()
    {
        $bank_list = config("bank_list");
        exit_json(1, "请求成功", $bank_list);
    }

    /**
     * 获取RSA公钥
     */
    public function getPubKey()
    {
        echo WeiXinPay::getPublicKey();
        exit;

    }

    /**
     * 释放商品库存
     */
    public function releaseRemain()
    {
        set_time_limit(0);
        $lock = fopen(__PUBLIC__ . "/lock_remain.txt", "w");
        try {
            if(flock($lock, LOCK_EX|LOCK_NB)){
                $list = model("OrderRemainPre")->where("status", 0)->where("create_time", "lt", time() - 301)->select();
                foreach ($list as $value) {
                    $weixin = new WeiXinPay();
                    $order_no = $value["order_no"];
                    $res = $weixin->orderQuery($order_no);
                    if ($res === true) {
                        //订单已支付或在支付过程中,库存锁定

                    } else if ($res === false) {
                        //订单已超时或已取消支付或未发起支付
                        //订单做关闭处理
                        $weixin->closeOrder($order_no);
                        $value->save(["status" => 2]);
                        $pro_list = json_decode($value["product_info"], true);
                        $redis = new \Redis2();
                        foreach ($pro_list as $item) {
                            for ($i=0;$i<$item["num"];$i++){
                                $redis->lpush($item["header_product_id"].":stock", 1);
                            }
//                            model("HeaderGroupProduct")->where("id", $item["header_product_id"])->setInc("remain", $item["num"]);
//                            Cache::inc($item["header_product_id"].":stock", $item["num"]);
//                            if(isset($item["is_group"]) && $item["is_group"] == true){
//                                model("GroupProduct")->where("id", $item["product_id"])->setInc("group_limit", $item["num"]);
//                            }
                        }
                    }
                }
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        } catch (\Exception $e) {
            flock($lock, LOCK_UN);
            fclose($lock);
            Log::error("库存释放异常:".$e->getMessage());
        }
        exit("ok");
    }

    /**
     * 记录团购访问记录
     */
    public function getOpenGid()
    {
        vendor("WeiXin.wxBizDataCrypt");
        $open_id = input("open_id");
        $group_id = input("group_id");
        //0 群 1 好友分享 2 二维码
        $src_id = input("src_id");
        $group = model("Group")->where("id", $group_id)->find();
        if (!$group) {
            exit();
        }
//        $session_key = db("os")->where("open_id", $open_id)->value("session_key");
        $session_key = \cache($open_id);
        $encry = new \wxBizDataCrypt(config("weixin.app_id"), $session_key);
        $vi = input("vi");
        $encry_data = input("encry_data");
        $encry->decryptData($encry_data, $vi, $data);
        $data = json_decode($data, true);
        $user = model("User")->getUserInfo(0, $open_id);
        if (!$user) {
            exit();
        }
        $data1 = [
            "open_id" => $open_id,
            "group_id" => $group_id,
            "openGId" => $data["openGId"],
            "user_id" => $user["id"],
            "user_name" => $user["user_name"],
            "src_id" => $src_id,
            "avatar" => $user["avatar"]
        ];
        $record = model("GroupRecord")->where("open_id", $open_id)->where("group_id", $group_id)->find();
        if ($record) {
//            $record->save($data1);
        } else {
            model("GroupRecord")->save($data1);
        }
        exit();
    }

    /**
     * 退款成功回调
     */
    public function orderRefund()
    {
        $xml = file_get_contents("php://input");
        $res_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if($res_data['return_code'] == "SUCCESS"){
            $req_info = $res_data["req_info"];
            //解密加密信息获取退款通知详情
            $key_str = md5(config("weixin.api_key"));
            $refund_data = openssl_decrypt(base64_decode($req_info, true), "AES-256-ECB", $key_str, OPENSSL_RAW_DATA);
            $refund_info = json_decode(json_encode(simplexml_load_string($refund_data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            $refund_no = $refund_info["out_refund_no"];
            $refund_money = $refund_info["refund_fee"];
            $refund = model("OrderRefund")->where("refund_no", $refund_no)->find();
            if($refund["status"] != 0){
                Log::error("退款已处理");
            }else{
                if($refund_money != $refund["num"]*$refund["group_price"]){
                    Log::error("微信退款异常");
                }else{
                    //军团退货商品处理
                    $header_product = model("HeaderGroupProduct")->where("id", $refund["header_product_id"])->find();
                    //团购
                    $group = model("Group")->where("id", $refund["group_id"])->find();
                    $group_product = model("GroupProduct")->where("id", $refund["product_id"])->find();
                    //订单商品详情
                    $product = model("OrderDet")->where("product_id", $refund["product_id"])->where("order_no", $refund["order_no"])->find();

                    //军团商品处理
                    if ($header_product["remain"] == -1) {
                        $header_product->save([
                            "refund_num" => $header_product["refund_num"] + $refund["num"],
                            "sell_num" => $header_product["sell_num"] - $refund["num"]
                        ]);
                    } else {
                        $header_product->save([
                            "refund_num" => $header_product["refund_num"] + $refund["num"],
                            "remain" => $header_product["remain"] + $refund["num"],
                            "sell_num" => $header_product["sell_num"] - $refund["num"]
                        ]);
                    }
                    //团购商品处理
                    $group_product->save([
                        "refund_num" => $group_product["refund_num"] + $refund["num"],
                        "sell_num" => $group_product["sell_num"] - $refund["num"]
                    ]);
                    //订单处理
                    $order = model("Order")->where("order_no", $refund["order_no"])->find();
                    $order->save(["refund_money" => $order["refund_money"] + $refund_money]);
                    $product->save(["back_num" => $refund["num"], "status" => 2]);

                    //佣金
                    $commission = $group_product["commission"] * ($refund["num"] * $refund["group_price"]) / 100;
                    //城主
                    $money = $refund["num"] * $refund["group_price"] - $commission;

                    $header_money = model("HeaderMoneyLog");
                    $leader_money = model("LeaderMoneyLog");
                    //军团是否已结束
                    if ($group["status"] == 2) {
                        $header_money->save([
                            "header_id" => $refund["header_id"],
                            "type" => 2,
                            "money" => -$money,
                            "order_no" => $refund["id"]
                        ]);

                        $leader_money->save([
                            "leader_id" => $refund["leader_id"],
                            "type" => 2,
                            "money" => -$commission,
                            "order_no" => $refund["id"]
                        ]);
                        if ($order["commission_status"] == 1) {
                            //佣金已经处理过，退款同时减佣金及城主收入
                            model("Header")->where("id", $refund["header_id"])->setDec("amount_able", $money);
                            model("User")->where("id", $refund["leader_id"])->setDec("amount_able", $commission);
                        } else {
                            model("Header")->where("id", $refund["header_id"])->setDec("amount_lock", $money);
                            model("User")->where("id", $refund["leader_id"])->setDec("amount_lock", $commission);
                        }
                    }
                    $refund->save("status", 1);
                }
            }
        }else{
            Log::error("微信退款失败:".$res_data["return_msg"]);
        }
        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        exit;
    }
}