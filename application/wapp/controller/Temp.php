<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-09-21
 * Time: 16:23
 */

namespace app\wapp\controller;


use think\Controller;
use think\Log;

class Temp extends Controller
{
    private $redis;

    protected function _initialize()
    {
        parent::_initialize();
//        $this->redis = new \Redis();
//        $this->redis->connect("127.0.0.1", "6379");
    }


    public function test()
    {
        exec("openssl rsa -RSAPublicKey_in -in ".__PUBLIC__."/public.pem"." -pubout", $public);
        print_r(file_get_contents(__PUBLIC__.'/public.pem'));
        var_dump(openssl_get_publickey(file_get_contents(__PUBLIC__.'/public.pem')));
        echo "1";
        exit;
    }

    public function psub()
    {
        $this->redis->setOption();
        $this->redis->psubscribe(array('__keyevent@0__:expired'), function ($redis, $pattern, $chan, $msg) {
            Log::error("1231213212312");
            Log::error("Pattern: $pattern\nChannel: $chan\nPayload: $msg\n\n");
        });
        exit();
    }

    /**
     * 超时回调
     */
    function keyCallback($redis, $pattern, $chan, $msg)
    {
        Log::error("1231213212312");
        Log::error("Pattern: $pattern\nChannel: $chan\nPayload: $msg\n\n");
    }


    public function show()
    {
        $ffmpeg = new \Ffmpeg();
        $info = $ffmpeg::getVideoInfo(__UPLOAD__ . "/20180927/d87a97bc377e9597ef7a9b3a0d7d9a85.mp4");
        var_dump($info);
        exit();
    }

    /**
     * 获取提货码
     */
    public function getPickQrcode()
    {
        $access_token = $this->getAccessToken();
        $ch = curl_init();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
        $post_data = [
            "scene" => "i343534",
            "page" => "pages/index/index"
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

        return $output;
    }

    private function getAccessToken()
    {

        if (cache("access_token")) {
            return cache("access_token");
        } else {
            $res = json_decode(file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . config("weixin.app_id") . "&secret=" . config("weixin.app_secret")), true);
            if (!isset($res["access_token"])) {
                return false;
            } else {
                cache("access_token", $res["access_token"]);
                return $res["access_token"];
            }
        }
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

}