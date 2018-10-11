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
use think\Controller;
use think\Log;

class Pub extends Controller
{
    /**
     * 下载军团销售记录
     */
    public function getGroupData()
    {
        $group_id = input("group_id");
        //军团信息
        $data = model("OrderDet")->where("header_group_id", $group_id)->field("product_name, sum(num-back_num) product_num")->group("header_product_id")->select();
        $temp1 = [];
        foreach ($data as $val){
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
        Log::error($open_id);


        $os = db('os')->where('open_id', $open_id)->find();
        if ($os) {
            db('os')->where('open_id', $open_id)->update(['session_key' => $session_key]);
        } else {
            db('os')->insert(['open_id' => $open_id, 'session_key' => $session_key]);
        }
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
        Log::error($data);
        if (!$open_id) {
            exit_json(-1, '登陆失败');
        }
        $user = model('User')->where('open_id', $open_id)->find();
        if ($user) {
            $data['update_time'] = time();
            $res = $user->save($data);
            $user_id = $user['id'];
        } else {
            $res = model('User')->save($data);
            $user_id = model('User')->getLastInsID();
        }
        if ($res) {
            exit_json(1, '登陆成功', model('User')->find($user_id));
        } else {
            exit_json(-1, '登陆失败asdfghj');
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
            if($order_no){
                $saveName = $order_no;
                $path = __URL__ . "/upload/" . $saveName.$ext;
                if(file_exists($path)){
                    exit_json(1, '操作成功', ["img_url" => $path]);
                }
            }else{
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
        $token = md5(time().rand_string());
        cache($user_id."token".$role, $token);
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
        try{
            $list = model("OrderRemainPre")->where("status", 0)->where("create_time", "lt", time()-301)->select();
            foreach ($list as $value){
                $weixin = new WeiXinPay();
                $order_no = $value["order_no"];
                $res = $weixin->orderQuery($order_no);
                if($res === true){
                    //订单已支付或在支付过程中,库存锁定

                }else if($res === false){
                    //订单已超时或已取消支付或未发起支付
                    //订单做关闭处理
                    $weixin->closeOrder($order_no);
                    $value->save(["status"=>2]);
                    $pro_list = json_decode($value["product_info"], true);
                    foreach ($pro_list as $item){
                        model("HeaderGroupProduct")->where("id", $item["header_product_id"])->setInc("remain", $item["num"]);
                    }
                }else{
                    exit();
                }
            }
        }catch (\Exception $e){
            exit();
        }
        exit("ok");
    }



}