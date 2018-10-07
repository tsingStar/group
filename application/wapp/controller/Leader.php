<?php
/**
 * 团长控制器
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-28
 * Time: 15:12
 */

namespace app\wapp\controller;


use app\common\model\Group;
use app\common\model\SendSms;
use app\common\model\WeiXinPay;
use think\Controller;
use think\Exception;
use think\Log;

class Leader extends Controller
{
    private $leader_id;
    private $leader;

    protected function _initialize()
    {
        parent::_initialize();
        $this->leader_id = input('leader_id');
        $this->leader = \app\common\model\User::get($this->leader_id);
        if (!$this->leader) {
            exit_json(-1, '团长不存在');
        }
        if ($this->leader['role_status'] != 2) {
            exit_json(-1, '抱歉，你还不是团长');
        }
    }

    /**
     * 获取军团详情
     */
    public function getGroupDetail()
    {
        $group_id = input('group_id');
        $group = model('HeaderGroup')->field('id, header_id, group_title, group_notice, dispatch_type, dispatch_info, is_close, close_time, status')->where('id', $group_id)->find();
        if (!$group) {
            exit_json(-1, '当前团购不存在');
        }
        if ($group['status'] == 2) {
            exit_json(-1, '当前团购已结束');
        }
        if ($this->leader['header_id'] != $group['header_id']) {
            exit_json(-1, '你不是当前城主下的团长');
        }
        $product__list = model('HeaderGroupProduct')->where(['header_group_id' => $group_id])->field('id, product_name, market_price, group_price, commission, group_limit, self_limit, product_desc')->select();
        foreach ($product__list as $item) {
            $item['product_img'] = model('HeaderGroupProductSwiper')->where('header_group_product_id', $item['id'])->field('swiper_type types, swiper_url urlImg')->select();
        }
        $group['product_list'] = $product__list;
        //若为自提团长选择自提点
        $leader_address = [];
        if ($group['dispatch_type'] == 2) {
            $leader_address = db('header_pick_address')->where('id', 'in', $group['dispatch_info'])->field('id, name, address, address_det')->select();
        }
        $group['leader_address'] = $leader_address;
        exit_json(1, '请求成功', $group);
    }

    /**
     * 校验团购是否已创建
     */
    public function checkIsGroup()
    {
        $group_id = input('group_id');
        $leader_group = model('Group')->where('header_group_id', $group_id)->where('leader_id', $this->leader_id)->find();
        if ($leader_group) {
            exit_json(1, '团购已创建', ['group_id' => $leader_group['id'], 'status' => 1]);
        } else {
            exit_json(1, '团购未创建', ['group_id' => 0, 'status' => -1]);
        }

    }


    /**
     * 团长自提点
     */
    public function getAddressList()
    {
        $address_list = db("leader_pick_address")->where('leader_id', $this->leader_id)->field('id, name, address, address_det')->select();
        exit_json(1, '请求成功', $address_list);
    }

    /**
     * 添加自提点
     */
    public function addAddress()
    {
        $data = [
            'name' => input('name'),
            'address' => input('address'),
            'address_det' => input('address_det'),
            'leader_id' => $this->leader_id
        ];
        $id = db('leader_pick_address')->insertGetId($data);
        $res = [
            'id' => $id,
            'name' => $data['name'],
            'address' => $data['address'],
            'address_det' => $data['address_det']
        ];
        exit_json(1, '操作成功', $res);
    }

    /**
     * 编辑自提点
     */
    public function editAddress()
    {
        $id = input('id');
        $name = input('name');
        $address = input('address');
        $address_det = input('address_det');
        $res = db('leader_pick_address')->where('id', $id)->update([
            'name' => $name,
            'address' => $address,
            'address_det' => $address_det,
            'update_time' => time()
        ]);
        if ($res) {
            exit_json(1, '修改成功', [
                'id' => $id,
                'name' => $name,
                'address' => $address,
                'address_det' => $address_det,
            ]);
        } else {
            exit_json(-1, '编辑失败');
        }
    }

    /**
     * 删除自提点
     */
    public function delAddress()
    {
        $address_id = input('address_id');
        $res = db('LeaderPickAddress')->where('id', $address_id)->delete();
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, '删除失败');
        }

    }

    /**
     * 保存/开启团购
     */
    public function saveGroup()
    {
        $data = [
            //军团id
            'header_group_id' => input('header_group_id'),
            'leader_id' => input('leader_id'),
            'header_id' => input('header_id'),
            'title' => input('title'),
            'notice' => input('notice'),
            'pay_type' => input('pay_type'),
            'dispatch_type' => input('dispatch_type'),
            'dispatch_info' => input('dispatch_info'),
            'pick_type' => input('pick_type'),
            'pick_address' => input('pick_address')
        ];
        if (input('status') == 1) {
            $data['status'] = 1;
            $data['open_time'] = date('Y-m-d H:i');
        } else {
            $data['status'] = 0;
        }
        model('Group')->startTrans();
        model('GroupProduct')->startTrans();
        model('GroupProductSwiper')->startTrans();
        try {
            //团购id
            $group_id = input('group_id');
            $group = model('Group')->where(["header_group_id" => $data['header_group_id'],
                "leader_id" => $data['leader_id']])->find();
            if ($group) {
                //团购已存在更新团购信息
//                $group = model('Group')->where('id', $group_id)->find();
//                if (!$group) {
//                    exit_json(-1, '团购不存在');
//                }
                $group_id = $group['id'];
                $data['update_time'] = time();
                $res1 = $group->isUpdate(true)->save($data);
            } else {
                //团购不存在根据军团信息填充团购信息
                $res1 = model('Group')->save($data);
                $group_id = model('Group')->getLastInsID();
            }
            if (!$res1) {
                throw new Exception("创建团购失败");
            }

            $product_list = input('product_list/a');
            foreach ($product_list as $key => $item) {
                if (!isset($item["header_product_id"])) {
                    $header_product_id = $item['id'];
                } else {
                    $header_product_id = $item['header_product_id'];
                }
                $pro_data = [
                    'leader_id' => $data['leader_id'],
                    'header_group_id' => $data['header_group_id'],
                    'group_id' => $group_id,
                    'header_product_id' => $header_product_id,
                    'product_name' => $item['product_name'],
                    'commission' => $item['commission'],
                    'market_price' => $item['market_price'],
                    'group_price' => $item['group_price'],
                    'group_limit' => $item['group_limit'],
                    'self_limit' => $item['self_limit'],
                    'ord' => $key,
                    'product_desc' => $item['product_desc'],
                ];
                $group_product = model('GroupProduct')->where(["leader_id" => $pro_data['leader_id'], "group_id" => $pro_data['group_id'], "header_product_id" => $pro_data['header_product_id']])->find();
                if ($group_product) {
                    $pro_data['update_time'] = time();
                    $res2 = $group_product->save($pro_data, $group_product['id']);
//                    $product_id = $item['id'];
//                    model('GroupProductSwiper')->where('group_product_id', $product_id)->delete();
                } else {
                    $res2 = model('GroupProduct')->data($pro_data)->isUpdate(false)->save();
//                    $product_id = model('GroupProduct')->getLastInsID();
                }
                if (!$res2) {
                    throw new Exception("添加商品失败");
                }
//                $swiper = [];
//                foreach ($item['product_img'] as $val) {
//                    $swiper[] = [
//                        'swiper_type' => $val['types'],
//                        'swiper_url' => $val['urlImg'],
//                        'group_product_id' => $product_id
//                    ];
//                }
//                $res3 = model('GroupProductSwiper')->saveAll($swiper);
//                if (!$res3) {
//                    throw new Exception("轮播图添加失败");
//                }
            }
            model('Group')->commit();
            model('GroupProduct')->commit();
            model('GroupProductSwiper')->commit();
            exit_json();
        } catch (\Exception $e) {
            model('Group')->rollback();
            model('GroupProduct')->rollback();
            model('GroupProductSwiper')->rollback();
            exit_json(-1, $e->getMessage());
        }
    }

    /**
     * 编辑团购
     */
    public function editGroup()
    {
        $group_id = input('group_id');
        $group = model('Group')->alias('a')->join('HeaderGroup b', 'a.header_group_id=b.id')->where('a.id', $group_id)->field('a.id group_id, a.header_group_id, a.header_id, a.leader_id, a.title, a.notice, a.pay_type, a.pick_type, a.pick_address, a.dispatch_type, a.dispatch_info, b.group_title header_group_title, b.group_notice header_group_notice,b.dispatch_info header_dispatch')->find();
        if (!$group || $group['leader_id'] != $this->leader_id) {
            exit_json(-1, '团购信息不存在');
        }
        $product_list = model('GroupProduct')->where('group_id', $group_id)->field("id, leader_id, header_group_id, group_id, header_product_id, product_name, commission, market_price, group_price, group_limit, self_limit, product_desc")->order('ord')->select();
        foreach ($product_list as $item) {
//            $item['product_img'] = model('GroupProductSwiper')->where('group_product_id', $item['id'])->field('swiper_type types, swiper_url urlImg')->select();
            $item['product_img'] = model('HeaderGroupProductSwiper')->where('header_group_product_id', $item['header_product_id'])->field('swiper_type types, swiper_url urlImg')->select();
        }
        $group['product_list'] = $product_list;
        //若为自提团长选择自提点
        $leader_address = [];
        if ($group['pick_type'] == 2) {
            $leader_address = db('header_pick_address')->where('id', 'in', $group['header_dispatch'])->field('id, name, address, address_det')->select();
        }
        $group['leader_address'] = $leader_address;
        exit_json(1, '请求成功', $group);
    }

    /**
     * 获取团购列表
     */
    public function getGroupList()
    {


        $where = "leader_id='$this->leader_id'";
        $title = trim(input('title'));
        $page = input('page');
        $page_num = input('page_num');
        if ($title) {
            //条件搜索
            for ($i = 0; $i < mb_strlen($title, 'utf-8'); $i++) {
                $where .= " and title like '%" . mb_substr($title, $i, 1, 'utf-8') . "%' ";
            }
        }
        $list = model('Group')->where($where)->field('id group_id, status, open_time, title, notice')->order('create_time desc')->limit($page * $page_num, $page_num)->select();
        $data = Group::formatGroupList($list);
        exit_json(1, '请求成功', $data);
    }

    /**
     * 获取团购详情
     */
    public function groupDetail()
    {
        $group_id = input('group_id');
        $group = model('Group')->alias("a")->join("HeaderGroup b", "a.header_group_id=b.id")->where("a.id", $group_id)->field('a.id group_id, a.title, a.notice, a.pay_type, a.status, a.header_id')->find();
        if (!$group) {
            exit_json(-1, '当前团购不存在');
        }
        $product_list = model('GroupProduct')->where('group_id', $group_id)->field('id, product_name, product_desc, commission, market_price, group_price, header_product_id')->select();
        foreach ($product_list as $value) {
//            $value['product_img'] = model('GroupProductSwiper')->where('group_product_id', $value['id'])->field('swiper_type types, swiper_url urlImg')->find();
            $value['product_img'] = model('HeaderGroupProductSwiper')->where('header_group_product_id', $value['header_product_id'])->field('swiper_type types, swiper_url urlImg')->select();
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
        $sale_num = model("OrderDet")->where("group_id", $group_id)->sum("num-back_num");
        $group['sale_detail'] = [
            "is_show" => $show,
            "detail" => [
                "total_order" => $order_num,
                "total_sale" => $sale_num,
                "total_money" => $order_money
            ]
        ];
        $group['product_list'] = $product_list;
        exit_json(1, '请求成功', $group);
    }

    /**
     * 开启团购
     */
    public function startGroup()
    {
        $group_id = input('group_id');
        $group = model('Group')->where('id', $group_id)->where('leader_id', $this->leader_id)->find();
        if (!$group) {
            exit_json(-1, "团购不存在");
        }
        if ($group['status'] != 0) {
            exit_json(-1, '团购已开启，无需再次开启');
        }
        if ($group->save(['status' => 1, 'open_time' => date('Y-m-d H:i')])) {
            exit_json(1, '开启成功');
        } else {
            exit_json(-1, '开启失败，刷新后重试');
        }
    }

    /**
     * 关闭团购
     */
    public function closeGroup()
    {
        $group_id = input('group_id');
        $group = model('Group')->where('id', $group_id)->where('leader_id', $this->leader_id)->find();
        $is_close = model("HeaderGroup")->where("id", $group["header_group_id"])->value("is_close");
        if ($is_close == 0) {
            exit_json(-1, "当前团购不允许团长结束");
        }
        if (!$group) {
            exit_json(-1, '团购不存在');
        }
        if ($group['status'] != 1) {
            exit_json(-1, '团购不可关闭');
        }
        if ($group->save(['status' => 2, 'close_time' => date('Y-m-d H:i')])) {
            exit_json(1, '操作成功');
        } else {
            exit_json(-1, '操作失败');
        }
    }

    /**
     * 获取团长账户基本信息
     */
    public function getLeaderInfo()
    {
        $user = model("User")->where('id', $this->leader_id)->find();
        $data = [
            'total_money' => $user['amount_able'] + $user['amount_lock'],
            'amount_able' => $user['amount_able']
        ];
        exit_json(1, '请求成功', $data);
    }

    /**
     * 获取团员订单
     */
    public function getUserOrderList()
    {
        $page = input('page');
        $page_num = input('page_num');
        $pick_status = input('pick_status'); //订单取货状态
        $keywords = input('keywords'); //高级搜索关键字
        $model = model("Order")->alias("a")->join("User b", "a.user_id=b.id")->join("Group c", "a.group_id=c.id");
        $model->where("a.leader_id", $this->leader_id);
        if (is_numeric($pick_status)) {
            $model->where("a.pick_status", $pick_status);
        }
        $model->where("c.status", 2);
        if ($keywords) {
            $where = [
                "c.title|a.user_telephone|a.user_name" => ["like", "%$keywords%"],
            ];
            $model->where($where);
        }
        $list = $model->field("a.order_no, a.user_name, b.avatar, a.user_telephone, a.pick_status, a.order_money, a.refund_money, a.pick_address, a.is_replace")->order("a.create_time desc")->limit($page * $page_num, $page_num)->select();
        foreach ($list as $item) {
            $order_no = $item['order_no'];
            $item['product_list'] = model('OrderDet')->getOrderPro($order_no);
        }
        exit_json(1, '请求成功', $list);

    }

    /**
     * 获取团员订单详情
     */
    public function getUserOrderDet()
    {
        $order_no = input('order_no');
        $order = model('Order')->where('order_no', $order_no)->field("leader_id, order_no, order_money, refund_money, user_name, pick_address, user_telephone, remarks, pick_status")->find();
        if ($order['leader_id'] != $this->leader_id) {
            exit_json(-1, '订单不存在');
        }
        $order['product_list'] = model("OrderDet")->getOrderPro($order_no);
        exit_json(1, '请求成功', $order);
    }

    /**
     * 处理团员订单
     */
    public function operaUserOrder()
    {
        $order_no = input("order_no");
        $order = model('Order')->where('order_no', $order_no)->find();
        if (!$order || $order['leader_id'] != $this->leader_id) {
            exit_json(-1, '订单不存在');
        }

        if ($order['pick_status'] != 0) {
            exit_json(-1, '订单已处理');
        }
        $res1 = $order->save(['pick_status' => 1]);
        $res2 = model("OrderDet")->save(["status" => 3], ['status' => 0]);
        if ($res1 && $res2) {
            exit_json();
        } else {
            exit_json(-1, '处理失败');
        }


    }

    /**
     * 商品申请退款
     */
    public function applyRefund()
    {
        $order_no = input('order_no');
        $product_id = input('product_id');
        $num = input('num');
        $reason = input('reason');
        $product = model("OrderDet")->where('order_no', $order_no)->where('product_id', $product_id)->find();
        $order = model('Order')->where('order_no', $order_no)->find();
        if ($product) {
            if ($num > $product['num'] - $product['back_num']) {
                exit_json(-1, "退货数量大于订单剩余数量");
            }
            $or = model('OrderRefund')->where("order_no", $order_no)->where("product_id", $product_id)->find();
            if ($or) {
                exit_json(1, '申请成功');
            } else {
                $res = model('OrderRefund')->save([
                    "group_id" => $product['group_id'],
                    "leader_id" => $product['leader_id'],
                    "header_id" => $order['header_id'],
                    "product_id" => $product['product_id'],
                    "header_product_id" => $product["header_product_id"],
                    "order_no" => $product['order_no'],
                    "num" => $num,
                    "group_price" => $product['group_price'],
                    "product_name" => $product['product_name'],
                    "reason" => $reason,
                    "refund_no" => getOrderNo()
                ]);
//                $product->save(["back_num" => $num, "status" => 1]);
                $product->save(["status" => 1]);
                if ($res) {
                    exit_json(1, "申请成功");
                } else {
                    exit_json(-1, '申请失败，刷新后重试');
                }
            }
        } else {
            exit_json(-1, "商品不存在");
        }
    }

    /**
     * 获取我的取货订单
     */
    public function getMyOrderList()
    {
        $pick_status = input('pick_status');
        $title = input('title');
        $page = input('page');
        $page_num = input('page_num');
        $model = model('Group');
        if ($pick_status > 0) {
            $model->where("pick_status", $pick_status);
        } else {
            $model->where("pick_status", "gt", 0);
        }

        if ($title != "") {
            $model->where("title", "like", "%$title%");
        }
        $list = $model->where("leader_id", $this->leader_id)->where('status', 2)->field("id, title, pick_status")->limit($page * $page_num, $page_num)->select();
        foreach ($list as $item) {
            $temp = model("OrderDet")->alias("a")->join("GroupProduct b", "a.product_id=b.id")->where('a.group_id', $item['id'])->group("a.product_id")->field("sum(a.num) sum_num, a.group_price, a.product_name, b.commission")->select();
            $item['product_list'] = $temp;
            $t = model("Order")->where('group_id', $item['id'])->field("sum(order_money) sum_money, sum(refund_money) sum_refund")->find();
            $item['sum_money'] = $t['sum_money'];
            $item['sum_refund'] = $t['sum_refund'];
        }
        exit_json(1, "请求成功", $list);
    }

    /**
     * 获取我的取货订单详情
     */
    public function getMyOrderDetail()
    {
        $group_id = input('group_id');
        $group = model('Group')->alias("a")->join("User b", "a.leader_id=b.id")->where('a.id', $group_id)->field("a.*, b.user_name, b.telephone")->find();
        if (!$group) {
            exit_json(-1, '团购不存在');
        }
        $data = [
            "user_name" => $group['user_name'],
            "user_telephone" => $group['telephone'],
            "pick_address" => $group['pick_address'],
            "pick_status" => $group['pick_status'],
        ];
        $item = model('Group')->getGroupDetail($group_id);
        $data['product_list'] = $item["product_list"];
        $data['sum_money'] = $item['sum_money'];
        $data['sum_refund'] = $item['sum_refund'];
        $data['refund_list'] = model("Group")->getRefundList($group_id);
        exit_json(1, '请求成功', $data);
    }

    /**
     * 取货订单处理
     */
    public function MyOrderSolve()
    {
        $group_id = input("group_id");
        $group = model("Group")->where("id", $group_id)->find();
        if (!$group) {
            exit_json(-1, "团购不存在");
        }
        $res = $group->save(['pick_status' => 2]);
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, "订单状态错误");
        }
    }

    /**
     * 获取退款列表
     */
    public function getRefundList()
    {
        $page = input("page");
        $page_num = input("page_num");
        $status = input('status');
        if (!is_numeric($status) || !in_array($status, [0, 1, 2])) {
            exit_json(-1, "参数错误");
        }
        $list = model("OrderRefund")->where("leader_id", $this->leader_id)->where('status', $status)->limit($page * $page_num, $page_num)->field("id, product_name, create_time")->select();
        exit_json(1, '请求成功', $list);
    }

    /**
     * 获取退款详情
     */
    public function getRefundDetail()
    {
        $refund_id = input('id');
        $refund = model("OrderRefund")->where("id", $refund_id)->find();
        if (!$refund) {
            exit_json(-1, '退款记录不存在');
        } else {
            $data = [];
            //订单详情
            $order = model("Order")->where("order_no", $refund["order_no"])->find();
            $order_detail = [
                "order_no" => $order["order_no"],
                "user_name" => $order["user_name"],
                "user_telephone" => $order["user_telephone"],
                "create_time" => $order["create_time"]
            ];
            $data["order_detail"] = $order_detail;
            //团购详情
            $group = model("Group")->alias("a")->join("User b", "a.leader_id=b.id")->where("a.id", $refund["group_id"])->field("a.*, b.user_name, b.telephone")->find();
            $group_detail = [
                "title" => $group["title"],
                "user_name" => $group["user_name"],
                "telephone" => $group["telephone"],
                "remarks" => $refund["reason"],
                "refuse_reason" => $refund["refuse_reason"]
            ];
            $data["group_detail"] = $group_detail;
            $product = model("GroupProduct")->where("id", $refund["product_id"])->find();
            $data["sale_amount"] = $refund["num"];
            $data["group_price"] = $refund["group_price"];
            $data["commission_rate"] = $product["commission"];
            $data["product_name"] = $product["product_name"];
            $data["status"] = $refund["status"];
            exit_json(1, '请求成功', $data);
        }
    }

    /**
     * 提现
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withDraw()
    {
        $token = input("token");
        if (!cache($this->leader_id . "token" . '2') || $token != cache($this->leader_id . "token" . '2')) {
            exit_json(-1, "请求非法");
        } else {
            cache($this->leader_id . "token" . '2', null);
        }

        $header = model("User")->where("id", $this->leader_id)->find();
        if (!$header["open_id"]) {
            exit_json(2, "账户未绑定微信号");
        }
        $amount = input("money");
        if (!is_numeric($amount) || !$amount || $amount < 10) {
            exit_json(-1, "提现金额不合法");
        }
        if ($amount > $header["amount_able"]) {
            exit_json(-1, "可提现余额不足");
        }
        //计算提现手续费及实际到账金额
        $rate = 0.006;
        $fee = round($amount * $rate, 2);
        $money = $amount - $fee;
        $order_no = getOrderNo();
        model("WithdrawLog")->save([
            "role" => 2,
            "user_id" => $this->leader_id,
            "amount" => $amount,
            "fee" => $fee,
            "money" => $money,
            "status" => 0,
            "order_no" => $order_no
        ]);
        $withdraw_id = model("WithdrawLog")->getLastInsID();
        $log_model = model("WithdrawLog")->where("id", $withdraw_id)->find();
        $weixin = new WeiXinPay();
        $res = $weixin->withdraw([
            "open_id" => $header["open_id"],
            "amount" => $money,
            "check_name" => "NO_CHECK",
            "desc" => "军团提现到账",
            "order_no" => $order_no
        ]);
        if (!is_bool($res)) {
            $log_model->save(["withdraw_time" => $res["payment_time"], "status" => 1]);
            $header->setDec("amount_able", $amount);
            model("HeaderMoneyLog")->saveAll([
                [
                    "header_id" => $this->header_id,
                    "type" => "3",
                    "money" => $money,
                    "order_no" => $order_no
                ],
                [
                    "header_id" => $this->header_id,
                    "type" => "4",
                    "money" => $fee,
                    "order_no" => $order_no
                ]
            ]);
            exit_json();
        } else {
            exit_json(-1, "提现操作失败");
        }
    }

    /**
     * 订单短信通知
     */
    public function sendSms()
    {
        $order_no = input("order_no");
        $order = model("Order")->where("order_no", $order_no)->find();
        if (!$order) {
            exit_json(-1, "订单不存在");
        }

        if ($order["send_num"] >= 2) {
            exit_json(-1, "短信通知咱最多支持两条");
        }

        $phone = [
            $order["user_telephone"]
        ];
        $param = [
            $order["order_no"],
            $order["pick_address"]
        ];
        $sms = new SendSms($phone, "9374132");
        $res = $sms->sendTemplate($param);
        if ($res) {
            $order->setInc("send_num");
            exit_json();
        } else {
            exit_json(-1, $sms->getError());
        }
    }

    /**
     * 获取余额列表
     */
    public function getMoneyLog()
    {
        $type_arr = [
            "1" => "团购佣金结算",
            "2" => "退款佣金冲减",
            "3" => "提现冲减",
            "4" => "提现手续费"
        ];
        $page = input("page");
        $page_num = input("page_num");
        $list = model("LeaderMoneyLog")->where("leader_id", $this->leader_id)->field("id, type, money, order_no, create_time")->limit($page * $page_num, $page_num)->order("create_time desc")->select();
        foreach ($list as $item) {
            $item["type_desc"] = $type_arr[$item["type"]];
        }
        exit_json(1, "请求成功", $list);
    }

    /**
     * 获取余额变动明细
     */
    public function getMoneyLogDetail()
    {
        $log_id = input("id");
        $detail = model("LeaderMoneyLog")->where("id", $log_id)->find();
        switch ($detail["type"]) {
            case 1:
                $header_group = model("Group")->where("id", $detail["order_no"])->find();
                $product_list = model("GroupProduct")->where("group_id", $detail["order_no"])->field("product_name, commission, sell_num, group_price")->select();
                exit_json(1, "", $product_list);
                break;
            case 2:
                $refund = model("OrderRefund")->where("order_no", $detail["order_no"])->find();
                $data = [
                    "product_name" => $refund["product_name"],
                    "group_price" => $refund["group_price"],
                    "num" => $refund["num"]
                ];
                $product = model("HeaderGroupProduct")->where("id", $refund["header_product_id"])->find();
                $data["commission"] = $product["commission"];
                $order = model("Order")->where("order_no", $refund["order_no"])->find();
                $data["order_no"] = $order["order_no"];
                $data["user_name"] = $order["user_name"];
                $data["user_telephone"] = $order["user_telephone"];
                $data["create_time"] = $refund["create_time"];
                $data["opera_time"] = $refund["update_time"];
                $group = model("Group")->alias("a")->join("User b", "a.leader_id=b.id")->where("a.id", $refund["group_id"])->field("a.*, b.user_name, b.telephone")->find();
                $data["title"] = $group["title"];
                $data["leader_name"] = $group["user_name"];
                $data["telephone"] = $group["telephone"];
                $data["remarks"] = $refund["remarks"];
                break;
            default:
                exit_json(-1, "明细不存在");
        }
    }

    /**
     * 获取上次团购地址
     */
    public function getLastAddress()
    {
        $group = model("Group")->where("leader_id", $this->leader_id)->where("pick_type", 1)->order("create_time desc")->find();
        $address = $group["pick_address"];
        exit_json(1, "请求成功", ["address" => $address]);
    }

    /**
     * 获取当前团购订单
     */
    public function getGroupOrder()
    {
        $group_id = input("group_id");
        $page = input('page');
        $page_num = input('page_num');
        $model = model("Order")->alias("a")->join("User b", "a.user_id=b.id")->join("Group c", "a.group_id=c.id");
        $model->where("a.leader_id", $this->leader_id)->where("a.group_id", $group_id);
        $list = $model->field("a.order_no, a.user_name, b.avatar, a.user_telephone, a.pick_status, a.order_money, a.refund_money, a.pick_address, a.is_replace")->limit($page * $page_num, $page_num)->select();
        foreach ($list as $item) {
            $order_no = $item['order_no'];
            $product_list = model('OrderDet')->alias("a")->join("GroupProduct b", "a.product_id=b.id")->where("a.order_no", $order_no)->field("a.*, b.commission")->select();
            $commission = 0;
            $plist = [];
            foreach ($product_list as $value) {
                $commission += $value["commission"] / 100 * ($value["num"] - $value["back_num"]) * $value["group_price"];
                $t = [];
                $t["product_name"] = $value["product_name"];
                $t["num"] = $value["num"];
                $t["back_num"] = $value["back_num"];
                $t["group_price"] = $value["group_price"];
                $t["commission"] = $value["commission"];
                $plist[] = $t;
            }
            $commission = round($commission, 2);
//            $item["order_money"] = $item["order_money"]-$item["refund_money"];
            $item["commission"] = $commission;
            $item["product_list"] = $plist;
        }
        exit_json(1, '请求成功', $list);
    }

    /**
     * 获取当前团购团购订单new
     */
    public function getGroupOrderNew()
    {
        $group_id = input("group_id");
        $keywords = input('keywords'); //高级搜索关键字
        $page = input('page');
        $page_num = input('page_num');
        $model = model("Order")->alias("a")->join("User b", "a.user_id=b.id")->join("Group c", "a.group_id=c.id");
        $model->where("a.leader_id", $this->leader_id);
        $model->where("c.id", $group_id);
        if ($keywords) {
            $where = [
                "c.title|a.user_telephone|a.user_name" => ["like", "%$keywords%"],
            ];
            $model->where($where);
        }
        $list = $model->field("a.order_no, a.user_name, b.avatar, a.user_telephone, a.pick_status, a.order_money, a.refund_money, a.pick_address, a.is_replace")->order("a.create_time desc")->limit($page * $page_num, $page_num)->select();
        foreach ($list as $item) {
            $order_no = $item['order_no'];
            $item['product_list'] = model('OrderDet')->getOrderPro($order_no);
        }
        exit_json(1, '请求成功', $list);
    }


    /**
     * 获取团购汇总
     */
    public function getGroupTotal()
    {
        $id = input("group_id");//团购
        $value = model("GroupProduct")->where("group_id", $id)->field("sell_num, product_name, group_price, commission")->select();
        $sum_money = 0;
        $commission_money = 0;
        foreach ($value as $item) {
            $sum_money += $item["sell_num"] * $item["group_price"];
            $commission_money += $item["sell_num"] * $item["group_price"] * $item["commission"] / 100;
        }
        $data["product_list"] = $value;
        $data["sum_money"] = round($sum_money, 2);
        $data["commission_money"] = round($commission_money, 2);
        exit_json(1, "请求成功", $data);

    }


}