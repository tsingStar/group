<?php
/**
 * 城主管理
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-28
 * Time: 15:11
 */

namespace app\wapp\controller;


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
            $res1 = model('HeaderGroup')->save($data);
            if (!$res1) {
                throw new Exception('创建团购失败');
            }
            $group_id = model('HeaderGroup')->getLastInsID();
            //团购商品信息
            $product_list = input('product_list/a');
            foreach ($product_list as $item) {
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
                    'ord' => $item['ord'],
                    'product_desc' => $item['product_desc'],
                ];
                $res2 = model('HeaderGroupProduct')->data($product_data)->isUpdate(false)->save();
                if (!$res2) {
                    throw new Exception('商品添加失败');
                }
                $product_id = model('HeaderGroupProduct')->getLastInsID();
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
            $item['join_list'] = [];
            //团购产品列表
            $item['product_list'] = model('HeaderGroupProduct')->where('header_group_id', $item['id']);
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
        foreach ($product_list as $item){
            $item['img_list'] = model('HeaderGroupProductSwiper')->where('header_group_product_id', $item['id'])->field('swiper_type types, swiper_url urlImg')->select();
        }
        $group['product_list'] = $product_list;
        exit_json(1, '请求成功', $group);
    }


}