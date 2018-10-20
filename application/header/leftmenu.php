<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/26
 * Time: 16:04
 */
$leftmenu = [
    [
        'navName'=>'系统设置',
        'navChild'=>[
//            [
//                'navName'=>'小程序参数',
//                'url'=>'System/wApp'
//            ],
            [
                'navName'=>'平台设置',
                'url'=>'System/headerConfig'
            ]
        ]
    ],
    [
        'navName'=>'账号管理',
        'navChild'=>[
            [
                'navName'=>'团长列表',
                'url'=>'Leader/index'
            ],
            [
                'navName'=>'团长申请列表',
                'url'=>'Leader/applyList'
            ],
            [
                'navName'=>'团长待审批',
                'url'=>'Leader/readyList'
            ],
            [
                'navName'=>'团长提现记录',
                'url'=>'Leader/withdrawList'
            ],
        ]
    ],
//    [
//        'navName'=>'营销活动',
//        'navChild'=>[
//            [
//                'navName'=>'优惠券',
//                'url'=>'Products/bulk_goods'
//            ],
//            [
//                'navName'=>'秒杀活动',
//                'url'=>'Products/bulk_goods'
//            ],
//        ]
//    ],
    [
        'navName'=>'产品库',
        'navChild'=>[
            [
                'navName'=>'产品列表',
                'url'=>'Product/index'
            ],
//            [
//                'navName'=>'产品库存',
//                'url'=>'Product/remain'
//            ],
        ]
    ],
    [
        'navName'=>'团购管理',
        'navChild'=>[
            [
                'navName'=>'团购列表',
                'url'=>'Group/index'
            ],
            [
                'navName'=>'新建团购',
                'url'=>'Group/add'
            ],
        ]
    ],
    [
        'navName'=>'数据统计',
        'navChild'=>[
            [
                'navName'=>'产品销量',
                'url'=>'Sale/productCount'
            ],
            [
                'navName'=>'每期销售额',
                'url'=>'Sale/saleAmount'
            ],
        ]
    ],
];