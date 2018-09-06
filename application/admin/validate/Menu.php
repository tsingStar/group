<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/5
 * Time: 13:49
 */

namespace app\admin\validate;


use think\Validate;

class Menu extends Validate
{
    protected $rule = [
      'name'=>'require',
      'url'=>'require',
//      'describe'=>'require',
      'parent_id'=>'require'
    ];
    protected $message = [
//        'describe'=>[
//            'require'=>'描述不能为空'
//        ]
    ];

}