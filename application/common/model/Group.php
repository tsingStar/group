<?php
/**
 * 团购信息
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-17
 * Time: 17:06
 */

namespace app\common\model;


use think\Model;

class Group extends Model
{

    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 格式化团购列表
     */
    public static function formatGroupList($list)
    {
        foreach ($list as $item) {
            //TODO 上线前放开注释
//            $item['buyer_list'] = model('Order')->alias('a')->join('User b', "a.user_id=b.id")->field('b.avatar')->select();
            $item['buyer_list'] = [
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
            $product_list = model('GroupProduct')->where('group_id', $item['group_id'])->field("id, leader_id, header_group_id, group_id, header_product_id, product_name, commission, market_price, group_price, group_limit, self_limit, product_desc")->order('ord')->select();
            foreach ($product_list as $value) {
                $value['product_img'] = model('GroupProductSwiper')->where('group_product_id', $value['id'])->field('swiper_type types, swiper_url urlImg')->select();
            }
            $item['product_list'] = $product_list;
        }
        return $list;
        
    }

}