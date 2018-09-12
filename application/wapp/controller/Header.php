<?php
/**
 * 城主管理
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-28
 * Time: 15:11
 */

namespace app\wapp\controller;


use app\common\model\ApplyLeaderRecord;
use app\common\model\HeaderGroup;
use app\common\model\HeaderGroupProduct;
use app\common\model\HeaderGroupProductSwiper;
use think\Controller;
use think\Exception;
use think\Log;

class Header extends Controller
{
    private $header_id;

    protected function _initialize()
    {
        parent::_initialize();
        $this->header_id = input('header_id');
        $header = model('Header')->where('id', $this->header_id)->find();
        if (!$header) {
            $this->header_id = input('header_id');
            exit_json(-1, '城主不存在');
        }
    }

    /**
     * 新建团购/编辑团购
     */
    public function applyGroup()
    {
        //团购基础信息
        $data = [
            'group_title' => input('group_title'),
            'header_id' => input('header_id'),
            'group_notice' => input('group_notice'),
            'dispatch_type' => input('dispatch_type'),
            'dispatch_info' => input('dispatch_info'),
            'is_close' => input('is_close'),
            'close_time' => input('close_time'),
            'status' => input('status'),
        ];
        if (input('status') == 1) {
            $data['open_time'] = date('Y-m-d');
        }
        model('HeaderGroup')->startTrans();
        model('HeaderGroupProduct')->startTrans();
        model('HeaderGroupProductSwiper')->startTrans();
        try {
            $group_id = input('group_id');
            if ($group_id > 0) {
                $res1 = HeaderGroup::get($group_id)->save($data);
            } else {
                $res1 = model('HeaderGroup')->save($data);
                $group_id = model('HeaderGroup')->getLastInsID();
            }
            if (!$res1) {
                throw new Exception('创建团购失败');
            }
            //团购商品信息
            $product_list = input('product_list/a');
            foreach ($product_list as $key => $item) {
//            $item = json_decode($item, true);
                $base_id = $item['base_id'];
                if (!$base_id) {
                    //商品库不存在商品，增加商品库信息
                    model('Product')->data([
                        'product_name' => $item['product_name'],
                        'header_id' => input('header_id'),
                        'desc' => $item['product_desc']
                    ])->isUpdate(false)->save();
                    $base_id = model('Product')->getLastInsID();
                    $base_swiper = $item['swiper_list'];
                    $bs = [];
                    foreach ($base_swiper as $item1) {
                        $bs[] = [
                            'product_id' => $base_id,
                            'type' => $item1['swiper_type'],
                            'url' => $item1['swiper_url']
                        ];
                    }
                    model('ProductSwiper')->saveAll($bs);
                }
                $product_data = [
                    'header_id' => input('header_id'),
                    'product_name' => $item['product_name'],
                    'header_group_id' => $group_id,
                    'base_id' => $base_id,
                    'remain' => $item['remain'],
                    'commission' => $item['commission'],
                    'purchase_price' => $item['purchase_price'],
                    'market_price' => $item['market_price'],
                    'group_price' => $item['group_price'],
                    'group_limit' => $item['group_limit'],
                    'self_limit' => $item['self_limit'],
                    'ord' => $key,
                    'product_desc' => $item['product_desc'],
                ];
                if ($item['id']) {
                    $res2 = HeaderGroupProduct::get($item['id'])->save($product_data);
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
                $product_swiper = $item['swiper_list'];
                $swiper = [];
                foreach ($product_swiper as $value) {
                    $swiper[] = [
                        'header_group_product_id' => $product_id,
                        'swiper_type' => $value['swiper_type'],
                        'swiper_url' => $value['swiper_url']
                    ];
                }
                $res3 = model('HeaderGroupProductSwiper')->saveAll($swiper);
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
     * 城主自提点
     */
    public function getAddressList()
    {
        $address_list = db("header_pick_address")->where('header_id', $this->header_id)->field('id, name, address, address_det')->select();
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
            'header_id'=>$this->header_id
        ];
        $id = db('header_pick_address')->insertGetId($data);
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
        $res = db('header_pick_address')->where('id', $id)->update([
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
     * 编辑团购获取详情
     */
    public function editGroupGetDetail()
    {
        $group_id = input('group_id');
        $group = HeaderGroup::get($group_id);
        $group['product_list'] = HeaderGroupProduct::all(function ($query) use ($group_id) {
            $query->where(['header_group_id' => $group_id])->order('ord');
        });
        foreach ($group['product_list'] as $value) {
            $value['img_list'] = HeaderGroupProductSwiper::all(['header_group_product_id' => $value['id']]);
        }
        exit_json(1, "请求成功", $group);
    }


    /**
     * 获取产品库商品信息
     */
    public function getStockProduct()
    {
        $page = input('page') ? input('page') : 0;
        $page_num = input('page_num') ? input('page_num') : 0;
        $product_list = model('Product')->where('header_id', $this->header_id)->limit($page * $page_num, $page_num)->select();
        foreach ($product_list as $product) {
            $swiper = model('ProductSwiper')->where('product_id', $product['id'])->field('type types, url urlImg')->select();
            $product['img_list'] = $swiper;
            $record_list = model('HeaderGroupProduct')->where('base_id', $product['id'])->order('id desc, ord')->select();
            foreach ($record_list as $item) {
                $item['img_list'] = model('HeaderGroupProductSwiper')->where('header_group_product_id', $item['id'])->field('swiper_type types, swiper_url urlImg')->select();
            }
            $product['record_list'] = $record_list;
        }
        exit_json(1, '请求成功', $product_list);
    }

    /**
     * 获取军团列表
     */
    public function getRecordList()
    {
        $page = input('page');
        $page_num = input('page_num');
        $header_group = model('HeaderGroup')->where('header_id', $this->header_id)->field('id, group_title, status, open_time')->limit($page * $page_num, $page_num)->select();
        //TODO 参团数量处理
        foreach ($header_group as $item) {
            //参团人员
            $item['join_list'] = [
                [
                    'avatar' => "https://wx.qlogo.cn/mmopen/vi_32/lahrxb0oKdD5yJK3HTciaKWbZlruWTDRTT2J5HvBkqV7e8JicuFVsUzvkdjiaSXTO7jr9ibRDT3T7xJwkMFP6FR39Q/132",
                ],
                [
                    'avatar' => "https://wx.qlogo.cn/mmopen/vi_32/lahrxb0oKdD5yJK3HTciaKWbZlruWTDRTT2J5HvBkqV7e8JicuFVsUzvkdjiaSXTO7jr9ibRDT3T7xJwkMFP6FR39Q/132",
                ],
                [
                    'avatar' => "https://wx.qlogo.cn/mmopen/vi_32/lahrxb0oKdD5yJK3HTciaKWbZlruWTDRTT2J5HvBkqV7e8JicuFVsUzvkdjiaSXTO7jr9ibRDT3T7xJwkMFP6FR39Q/132",
                ]
            ];
            //团购产品列表
            $item['product_list'] = model('HeaderGroupProduct')->where('header_group_id', $item['id'])->select();
        }
        $data = $header_group;
        exit_json(1, '请求成功', $data);
    }

    /**
     * 获取团购详情
     */
    public function getGroupDetail()
    {
        $group_id = input('group_id');
        $group = model('HeaderGroup')->alias('a')->join('Header b', 'a.header_id=b.id')->where('a.id', $group_id)->field('a.id, a.group_title, a.group_notice, a.status, b.nick_name, b.head_image')->find();
        $product_list = model('HeaderGroupProduct')->where('header_group_id', $group_id)->field('id, product_name, remain, sell_num, commission, market_price, group_price, product_desc')->order('ord')->select();
        foreach ($product_list as $item) {
            $item['img_list'] = model('HeaderGroupProductSwiper')->where('header_group_product_id', $item['id'])->field('swiper_type types, swiper_url urlImg')->select();
        }
        $group['product_list'] = $product_list;
        exit_json(1, '请求成功', $group);
    }

    /**
     * 开启团购
     */
    public function startGroup()
    {
        $group_id = input('group_id');
        $group = HeaderGroup::get($group_id);
        if ($group['header_id'] != $this->header_id) {
            exit_json(-1, '登陆用户与团购创建用户不同');
        } else {
            if ($group['status'] != 0) {
                exit_json(-1, '当前团购不是未开启状态');
            }
            $res = $group->save(['status' => 1, 'open_time' => date('Y-m-d H:i')]);
            if ($res) {
                exit_json();
            } else {
                exit_json(-1, '开启失败');
            }
        }
    }

    /**
     * 更改头像
     */
    public function modifyAvatar()
    {
        $avatar_url = input('avatar_url');
        if (!$avatar_url) {
            exit_json(-1, '参数错误');
        }
        $res = model('Header')->save(['head_image' => $avatar_url], ['id' => $this->header_id]);
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, '修改失败');
        }
    }

    /**
     *  更改昵称
     */
    public function modifyNickName()
    {
        $nick_name = input('nick_name');
        $res = model('Header')->save(['nick_name' => $nick_name], ['id' => $this->header_id]);
        if ($res) {
            exit_json();
        } else {
            exit_json(-1, '修改失败');
        }
    }

    /**
     * 获取城主基础信息
     */
    public function getHeaderInfo()
    {
        $header = model('Header')->where('id', $this->header_id)->field('id header_id, name, nick_name, head_image, amount_able+amount_lock amount, amount_able')->find();
        exit_json(1, '请求成功', $header);
    }

    /**
     * 我的团长列表
     */
    public function getMyLeader()
    {
        $page = input('page');
        $page_num = input('page_num');
        $status = input('status');
        $list = model('ApplyLeaderRecord')->alias('a')->join('User b', 'a.user_id=b.id')->where(['a.header_id' => $this->header_id, 'a.status' => $status])->field('a.id, a.name, a.status, b.avatar')->limit($page * $page_num, $page_num)->select();
        exit_json(1, '请求成功', $list);
    }

    /**
     * 团长申请详情
     */
    public function getMyLeaderDet()
    {
        $apply_id = input('id');
        $data = ApplyLeaderRecord::get($apply_id);

        exit_json(1, '请求成功', $data);
    }

    /**
     * 同意团长申请
     */
    public function agreeLeader()
    {
        $apply_id = input('id');
        $data = ApplyLeaderRecord::get($apply_id);
        if ($data && $data['status'] == 0) {
            if ($data['header_id'] != $this->header_id) {
                exit_json(-1, '权限错误');
            }
            $data->save(['status' => 1]);
            model('User')->where('id', $data['user_id'])->find()->save(['role_status' => 2, "header_id" => $this->header_id]);
            exit_json();
        } else {
            exit_json(-1, '申请记录不存在或已处理');
        }
    }

    /**
     * 团长申请拒绝
     */
    public function refuseLeader()
    {
        $apply_id = input('id');
        $data = ApplyLeaderRecord::get($apply_id);
        $reason = input('reason');
        if ($data && $data['status'] == 0) {
            if ($data['header_id'] != $this->header_id) {
                exit_json(-1, '权限错误');
            }
            $data->save(['status' => 2, 'remarks' => $reason]);
            exit_json();
        } else {
            exit_json(-1, '申请记录不存在或已处理');
        }
    }


}