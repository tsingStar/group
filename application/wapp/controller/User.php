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

class User extends Controller
{
    private $user;

    protected function _initialize()
    {
        parent::_initialize();
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
        //TODO 获取团长id
        $leader_id = 3;
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
        $group = model('Group')->where("id", $group_id)->field('id group_id, header_group_id, header_id, leader_id, dispatch_type, dispatch_info, title, notice, pay_type, status')->find();
        if (!$group) {
            exit_json(-1, '当前团购不存在');
        }
        $product_list = model('GroupProduct')->where('group_id', $group_id)->field('id, leader_id, header_group_id, group_id, header_product_id, product_name, product_desc, commission, market_price, group_price')->order('ord')->select();
        foreach ($product_list as $value) {
            $value['product_img'] = model('GroupProductSwiper')->where('group_product_id', $value['id'])->field('swiper_type types, swiper_url urlImg')->find();
        }
        //TODO 添加团购销售情况
        $group['sale_detail'] = [
            "is_show" => 1,
            "detail" => [
                "scan_number" => 10,
                "order_number" => 100,
                "order_money" => 1000
            ]
        ];
        $group['product_list'] = $product_list;
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
        $page = input('page');
        $page_num = input('page_num');
        $record_list = model('Order')->alias('a')->join('User b', 'a.user_id=b.id')->where('a.group_id', $group_id)->field('a.id, a.create_time, b.avatar, b.user_name')->limit($page * $page_num, $page_num)->select();
        foreach ($record_list as $l) {
            $l['product_num'] = model('OrderDet')->where('order_no', $l['order_no'])->sum('num');
        }
        exit_json(1, '请求成功', $record_list);
    }

    /**
     * 检测商品库存
     */
    public function checkProductRemain()
    {
        $product_id = input('product_id');
        $group_id = input('group_id');
        $num = input('num');
        $product = model('GroupProduct')->where('id', $product_id)->find();
        $header_product = model('HeaderGroupProduct')->where('id', $product['header_product_id'])->find();
        $group_num = model('OrderDet')->where('group_id', $group_id)->where('product_id', $product_id)->sum('num-back_num');

        //军团库存数量
        if ($header_product['remain'] > 0 && $header_product['remain'] < $header_product['sell_num'] + $num) {
            exit_json(-1, '商品库存不足');
        }

        //团员限购
        if ($product['self_limit'] && $product['self_limit'] < $num) {
            exit_json(-1, '商品个人限购' . $product['self_limit'] . '件');
        }

        //团限购
        if ($product['group_limit'] > 0 && $group_num + $num > $product['group_limit']) {
            exit_json(-1, '该商品团限购' . $product['group_limit'] . '件，还剩' . ($num - 1));
        }

        exit_json(1, '库存充足');
    }

    /**
     * 获取立即下单
     */
    public function makeOrder()
    {
        $order_no = getOrderNo();
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
            "open_id"=>$this->user['open_id']
        ];
        $notify_url = config('notify_url');
        model('OrderPre')->startTrans();
        $res = model('OrderPre')->save(['order_no' => $order_no, "order_det" => json_encode($data)]);
//        $order_info = $weixin->createPrePayOrder($order_info, $notify_url);
        $order_info = true;
        if ($res && $order_info) {
            model('OrderPre')->commit();
            exit_json(1, '请求成功', $order_info);
        } else {
            exit_json(-1, '系统错误');
        }
    }


}