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
     * 校验军团是否已创建
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
            if ($group_id) {
                //团购已存在更新团购信息
                $group = model('Group')->where('id', $group_id)->find();
                if (!$group) {
                    exit_json(-1, '团购不存在');
                }
                $res1 = $group->save($data);
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
                $pro_data = [
                    'leader_id' => $data['leader_id'],
                    'header_group_id' => $data['header_group_id'],
                    'group_id' => $group_id,
                    'header_product_id' => $item['header_product_id'],
                    'product_name' => $item['product_name'],
                    'commission' => $item['commission'],
                    'market_price' => $item['market_price'],
                    'group_price' => $item['group_price'],
                    'group_limit' => $item['group_limit'],
                    'self_limit' => $item['self_limit'],
                    'ord' => $key,
                    'product_desc' => $item['product_desc'],
                ];
                if ($item['id']) {
                    $res2 = model('GroupProduct')->where('id', $item['id'])->find()->save($pro_data);
                    $product_id = $item['id'];
                    model('GroupProductSwiper')->where('group_product_id', $product_id)->delete();
                } else {
                    $res2 = model('GroupProduct')->data($pro_data)->isUpdate(false)->save();
                    $product_id = model('GroupProduct')->getLastInsID();
                }
                if (!$res2) {
                    throw new Exception("添加商品失败");
                }
                $swiper = [];
                foreach ($item['product_img'] as $val) {
                    $swiper[] = [
                        'swiper_type' => $val['types'],
                        'swiper_url' => $val['urlImg'],
                        'group_product_id' => $product_id
                    ];
                }
                $res3 = model('GroupProductSwiper')->saveAll($swiper);
                if (!$res3) {
                    throw new Exception("轮播图添加失败");
                }
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
            $item['product_img'] = model('GroupProductSwiper')->where('group_product_id', $item['id'])->field('swiper_type types, swiper_url urlImg')->select();
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
        $group = model('Group')->where("id", $group_id)->field('id group_id, title, notice, pay_type, status')->find();
        if (!$group) {
            exit_json(-1, '当前团购不存在');
        }
        $product_list = model('GroupProduct')->where('group_id', $group_id)->field('id, product_name, product_desc, commission, market_price, group_price')->select();
        foreach ($product_list as $value) {
            $value['product_img'] = model('GroupProductSwiper')->where('group_product_id', $value['id'])->field('swiper_type types, swiper_url urlImg')->find();
        }
        //TODO 添加团购销售情况
        $group['sale_detail'] = [
            "is_show" => 1,
            "detail" => [
                "total_order" => 10,
                "total_sale" => 100,
                "total_money" => 1000
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
            exit_json(-1, '团购已开启');
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
            'total_money'=>$user['amount_able']+$user['amount_lock'],
            'amount_able'=>$user['amount_able']
        ];
        exit_json(1, '请求成功', $data);
    }


}