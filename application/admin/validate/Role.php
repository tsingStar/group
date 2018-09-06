<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/9
 * Time: 9:23
 */

namespace app\admin\validate;


use think\Validate;

class Role extends Validate
{

    protected $rule = [
        'role_name'=>'require',
        'node_id'=>'require'
    ];
    protected $message = [
        'role_name'=>[
            'require'=>'角色名称不能为空'
        ],
        'node_id'=>[
            'require'=>'访问节点不能为空'
        ]
    ];
}