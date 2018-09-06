<?php
/**
 * 团长控制器
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-28
 * Time: 15:12
 */

namespace app\wapp\controller;


use think\Controller;
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
        exit_json(1, '请求成功', $group);
    }

    /**
     * 保存团购
     */
    public function saveGroup()
    {
        $data = [
            //军团id
            'header_group_id' => input('header_group_id'),
            'leader_id' => input('leader_id'),
            'title' => input('title'),
            'notice' => input('notice'),
            'pay_type' => input('pay_type'),
            'dispatch_type' => input('dispatch_type')
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
        //团购id
        $group_id = input('group_id');
        if ($group_id) {
            //团购已存在更新团购信息
            $group = model('Group')->where('id', $group_id)->find();
            if(!$group){
                exit_json(-1, '团购不存在');
            }
            $res1 = $group->save($data);
        } else {
            //团购不存在根据军团信息填充团购信息
            $res1 = model('Group')->save($data);
            $group_id = model('Group')->getLastInsID();
        }

        $product_list = input('product_list/a');
        foreach ($product_list as $key=>$item){
            $pro_data = [
                'leader_id'=>$data['leader_id'],
                'header_group_id'=>$data['header_group_id'],
                'group_id'=>$group_id,
                'header_product_id'=>$item['header_product_id'],
                'product_name'=>$item['product_name'],
                'commission'=>$item['commission'],
                'market_price'=>$item['market_price'],
                'group_price'=>$item['group_price'],
                'group_limit'=>$item['group_limit'],
                'self_limit'=>$item['self_limit'],
                'ord'=>$key,
                'product_desc'=>$item['product_desc'],
            ];
            $res2 = model('GroupProduct')->save($pro_data);
            $product_id = model('GroupProduct')->getLastInsID();
            $swiper = [];
            foreach ($item['swiper_list'] as $val){
                $swiper[] = [
                    'swiper_type'=>$val['types'],
                    'swiper_url'=>$val['url'],
                    'group_product_id'=>$product_id
                ];
            }
            $res3 = model('GroupProductSwiper')->saveAll($swiper);
            if($res1 && $res2 && $res3){
                model('Group')->commit();
                model('GroupProduct')->commit();
                model('GroupProductSwiper')->commit();
                exit_json();
            }else{
                model('Group')->rollback();
                model('GroupProduct')->rollback();
                model('GroupProductSwiper')->rollback();
                exit_json(-1, '操作失败');
            }
        }

















    }


}